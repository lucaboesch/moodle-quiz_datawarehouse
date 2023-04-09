<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library code for the quiz data warehouse report.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/validateurlsyntax.php');

define('REPORT_CUSTOMSQL_LIMIT_EXCEEDED_MARKER', '-- ROW LIMIT EXCEEDED --');

/**
 * Function to execute a query
 *
 * @param string $sql The SQL query.
 * @param array|null $params An array of parameters or null
 * @param int|null $limitnum A number to limit result rows or null
 * @return moodle_recordset  The recordset of the result to the query
 * @throws dml_exception
 */
function quiz_datawarehouse_execute_query($sql, $params = null, $limitnum = null) {
    global $CFG, $DB;

    if ($limitnum === null) {
        $limitnum = get_config('report_customsql', 'querylimitdefault');
    }

    $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

    foreach ($params as $name => $value) {
        if (((string) (int) $value) === ((string) $value)) {
            $params[$name] = (int) $value;
        }
    }

    // Note: throws Exception if there is an error.
    return $DB->get_recordset_sql($sql, $params, 0, $limitnum);
}

/**
 * A function to substitute the time and user tokens.
 *
 * @param \stdClass $report A report object
 * @param int $timenow A timestamp
 * @return array|string|string[]
 */
function quiz_datawarehouse_prepare_sql($report, $timenow) {
    global $USER;
    $sql = $report->querysql;
    if ($report->runable != 'manual') {
        list($end, $start) = quiz_datawarehouse_get_starts($report, $timenow);
        $sql = quiz_datawarehouse_substitute_time_tokens($sql, $start, $end);
    }
    $sql = quiz_datawarehouse_substitute_user_token($sql, $USER->id);
    return $sql;
}

/**
 * Extract all the placeholder names from the SQL.
 *
 * @param string $sql The SQL query.
 * @return array placeholder names including the leading colon.
 */
function quiz_datawarehouse_get_query_placeholders($sql) {
    preg_match_all('/(?<!:):[a-z][a-z0-9_]*/', $sql, $matches);
    return $matches[0];
}

/**
 * Extract all the placeholder names from the SQL, and work out the corresponding form field names.
 *
 * @param string $querysql The query sql.
 * @return string[] placeholder name => form field name.
 */
function quiz_datawarehouse_get_query_placeholders_and_field_names(string $querysql): array {
    $queryparams = [];
    foreach (quiz_datawarehouse_get_query_placeholders($querysql) as $queryparam) {
        $queryparams[substr($queryparam, 1)] = 'queryparam' . substr($queryparam, 1);
    }
    return $queryparams;
}

/**
 * Return the type of form field to use for a placeholder, based on its name.
 *
 * @param string $name the placeholder name.
 * @return string a formslib element type, for example 'text' or 'date_time_selector'.
 */
function quiz_datawarehouse_get_element_type($name) {
    $regex = '/^date|date$/';
    if (preg_match($regex, $name)) {
        return 'date_time_selector';
    }
    return 'text';
}

/**
 * Generate a CSV
 *
 * @param \stdClass $report A report object
 * @param int $timenow A timestamp
 * @return mixed|null A timestamp
 * @throws dml_exception
 */
function quiz_datawarehouse_generate_csv($report, $timenow) {
    global $DB;
    $starttime = microtime(true);

    $sql = quiz_datawarehouse_prepare_sql($report, $timenow);

    $queryparams = !empty($report->queryparams) ? unserialize($report->queryparams) : array();
    $querylimit  = $report->querylimit ?? get_config('report_customsql', 'querylimitdefault');
    // Query one extra row, so we can tell if we hit the limit.
    $rs = quiz_datawarehouse_execute_query($sql, $queryparams, $querylimit + 1);

    $csvfilenames = array();
    $csvtimestamp = null;
    $count = 0;
    foreach ($rs as $row) {
        if (!$csvtimestamp) {
            list($csvfilename, $csvtimestamp) = quiz_datawarehouse_csv_filename($report, $timenow);
            $csvfilenames[] = $csvfilename;

            if (!file_exists($csvfilename)) {
                $handle = fopen($csvfilename, 'w');
                quiz_datawarehouse_start_csv($handle, $row, $report);
            } else {
                $handle = fopen($csvfilename, 'a');
            }
        }

        $data = get_object_vars($row);
        foreach ($data as $name => $value) {
            if (quiz_datawarehouse_get_element_type($name) == 'date_time_selector' &&
                    quiz_datawarehouse_is_integer($value) && $value > 0) {
                $data[$name] = userdate($value, '%F %T');
            }
        }
        if ($report->singlerow) {
            array_unshift($data, date('%Y-%m-%d', $timenow));
        }
        quiz_datawarehouse_write_csv_row($handle, $data);
        $count += 1;
    }
    $rs->close();

    if (!empty($handle)) {
        if ($count > $querylimit) {
            quiz_datawarehouse_write_csv_row($handle, [REPORT_CUSTOMSQL_LIMIT_EXCEEDED_MARKER]);
        }

        fclose($handle);
    }

    // Update the execution time in the DB.
    $updaterecord = new stdClass();
    $updaterecord->id = $report->id;
    $updaterecord->lastrun = time();
    $updaterecord->lastexecutiontime = round((microtime(true) - $starttime) * 1000);
    $DB->update_record('quiz_datawarehouse_queries', $updaterecord);

    // Report is runable daily, weekly or monthly.
    if ($report->runable != 'manual') {
        if ($csvfilenames) {
            foreach ($csvfilenames as $csvfilename) {
                if (!empty($report->emailto)) {
                    quiz_datawarehouse_email_report($report, $csvfilename);
                }
                if (!empty($report->customdir)) {
                    quiz_datawarehouse_copy_csv_to_customdir($report, $timenow, $csvfilename);
                }
            }
        } else { // If there is no data.
            if (!empty($report->emailto)) {
                quiz_datawarehouse_email_report($report);
            }
            if (!empty($report->customdir)) {
                quiz_datawarehouse_copy_csv_to_customdir($report, $timenow);
            }
        }
    }
    return $csvtimestamp;
}

/**
 * Check whether a passed value is an integer.
 *
 * @param mixed $value some value
 * @return bool whether $value is an integer, or a string that looks like an integer.
 */
function quiz_datawarehouse_is_integer($value) {
    return (string) (int) $value === (string) $value;
}

/**
 * Return a CSV filename
 *
 * @param \stdClass $report A report object
 * @param int $timenow A timestamp
 * @return array
 */
function quiz_datawarehouse_csv_filename($report, $timenow) {
    if ($report->runable == 'manual') {
        return quiz_datawarehouse_temp_csv_name($report->id, $timenow);

    } else if ($report->singlerow) {
        return quiz_datawarehouse_accumulating_csv_name($report->id);

    } else {
        list($timestart) = quiz_datawarehouse_get_starts($report, $timenow);
        return quiz_datawarehouse_scheduled_csv_name($report->id, $timestart);
    }
}

/**
 * Return a temporary CSV filename
 *
 * @param int $reportid The report ID
 * @param int $timestamp A timestamp
 * @return array Some result
 */
function quiz_datawarehouse_temp_csv_name($reportid, $timestamp) {
    global $CFG;
    $path = 'admin_report_customsql/temp/'.$reportid;
    make_upload_directory($path);
    return array($CFG->dataroot.'/'.$path.'/'.date('%Y%m%d-%H%M%S', $timestamp).'.csv',
                 $timestamp);
}

/**
 * Return a temporary CSV filename
 *
 * @param int $reportid The report ID
 * @param int $timestart A timestamp
 * @return array Some result
 */
function quiz_datawarehouse_scheduled_csv_name($reportid, $timestart) {
    global $CFG;
    $path = 'admin_report_customsql/'.$reportid;
    make_upload_directory($path);
    return array($CFG->dataroot.'/'.$path.'/'.date('%Y%m%d-%H%M%S', $timestart).'.csv',
                 $timestart);
}

/**
 * Return an accumulating CSV filename
 *
 * @param int $reportid The report ID
 * @return array Some result
 */
function quiz_datawarehouse_accumulating_csv_name($reportid) {
    global $CFG;
    $path = 'admin_report_customsql/'.$reportid;
    make_upload_directory($path);
    return array($CFG->dataroot.'/'.$path.'/accumulate.csv', 0);
}

/**
 * Return a CSV filename
 *
 * @param \stdClass $report A report object
 * @return array Some result
 */
function quiz_datawarehouse_get_archive_times($report) {
    global $CFG;
    if ($report->runable == 'manual' || $report->singlerow) {
        return array();
    }
    $files = glob($CFG->dataroot.'/admin_report_customsql/'.$report->id.'/*.csv');
    $archivetimes = array();
    foreach ($files as $file) {
        if (preg_match('|/(\d\d\d\d)(\d\d)(\d\d)-(\d\d)(\d\d)(\d\d)\.csv$|', $file, $matches)) {
            $archivetimes[] = mktime($matches[4], $matches[5], $matches[6], $matches[2],
                                     $matches[3], $matches[1]);
        }
    }
    rsort($archivetimes);
    return $archivetimes;
}

/**
 * Substitute time tokens in a SQL string
 *
 * @param string $sql The SQL query.
 * @param int $start A timestamp
 * @param int $end A timestamp
 * @return array|string|string[] Some result
 */
function quiz_datawarehouse_substitute_time_tokens($sql, $start, $end) {
    return str_replace(array('%%STARTTIME%%', '%%ENDTIME%%'), array($start, $end), $sql);
}

/**
 * Substitute user tokens in a SQL string.
 *
 * @param string $sql The SQL query.
 * @param int $userid A user id
 * @return array|string|string[] Some result
 */
function quiz_datawarehouse_substitute_user_token($sql, $userid) {
    return str_replace('%%USERID%%', $userid, $sql);
}

/**
 * Create url to $relativeurl.
 *
 * @param string $relativeurl Relative url.
 * @param array $params Parameter for url.
 * @return moodle_url the relative url.
 */
function quiz_datawarehouse_url($relativeurl, $params = []) {
    return new moodle_url('/report/customsql/' . $relativeurl, $params);
}

/**
 * Create the download url for the report.
 *
 * @param int $reportid The reportid.
 * @param array $params Parameters for the url.
 * @return moodle_url The download url.
 */
function quiz_datawarehouse_downloadurl($reportid, $params = []) {
    $downloadurl = moodle_url::make_pluginfile_url(
        context_system::instance()->id,
        'quiz_datawarehouse',
        'download',
        $reportid,
        null,
        null
    );
    // Add the params to the url.
    // Used to pass values for the arbitrary number of params in the sql report.
    $downloadurl->params($params);

    return $downloadurl;
}

/**
 * Return an array of strings that can't be used
 *
 * @return string[] Array of bad words strings
 */
function quiz_datawarehouse_bad_words_list() {
    return array('ALTER', 'CREATE', 'DELETE', 'DROP', 'GRANT', 'INSERT', 'INTO',
                 'TRUNCATE', 'UPDATE');
}

/**
 * Whether a query contains bad words
 *
 * @param string $string The query to check
 * @return false|int Whether a query includes bad words
 */
function quiz_datawarehouse_contains_bad_word($string) {
    return preg_match('/\b('.implode('|', quiz_datawarehouse_bad_words_list()).')\b/i', $string);
}

/**
 * Get the list of actual column headers from the list of raw column names.
 *
 * This matches up the 'Column name' and 'Column name link url' columns.
 *
 * @param string[] $row the row of raw column headers from the CSV file.
 * @return array with two elements: the column headers to use in the table, and the columns that are links.
 */
function quiz_datawarehouse_get_table_headers($row) {
    $colnames = array_combine($row, $row);
    $linkcolumns = [];
    $colheaders = [];

    foreach ($row as $key => $colname) {
        if (substr($colname, -9) === ' link url' && isset($colnames[substr($colname, 0, -9)])) {
            // This is a link_url column for another column. Skip.
            $linkcolumns[$key] = -1;

        } else if (isset($colnames[$colname . ' link url'])) {
            $colheaders[] = $colname;
            $linkcolumns[$key] = array_search($colname . ' link url', $row);
        } else {
            $colheaders[] = $colname;
        }
    }

    return [$colheaders, $linkcolumns];
}

/**
 * Prepare the values in a data row for display.
 *
 * This deals with $linkcolumns as detected above and other values that looks like links.
 * Auto-formatting dates is handled when the CSV is generated.
 *
 * @param string[] $row the row of raw data.
 * @param int[] $linkcolumns
 * @return string[] cell contents for output.
 */
function quiz_datawarehouse_display_row($row, $linkcolumns) {
    $rowdata = array();
    foreach ($row as $key => $value) {
        if (isset($linkcolumns[$key]) && $linkcolumns[$key] === -1) {
            // This row is the link url for another row.
            continue;
        } else if (isset($linkcolumns[$key])) {
            // Column with link url coming from another column.
            if (validateUrlSyntax($row[$linkcolumns[$key]], 's+H?S?F?E?u-P-a?I?p?f?q?r?')) {
                $rowdata[] = '<a href="' . s($row[$linkcolumns[$key]]) . '">' . s($value) . '</a>';
            } else {
                $rowdata[] = s($value);
            }
        } else if (validateUrlSyntax($value, 's+H?S?F?E?u-P-a?I?p?f?q?r?')) {
            // Column where the value just looks like a link.
            $rowdata[] = '<a href="' . s($value) . '">' . s($value) . '</a>';
        } else {
            $rowdata[] = s($value);
        }
    }
    return $rowdata;
}

/**
 * Prettify the column names
 *
 * @param \stdClass $row A row
 * @param string $querysql The query sql.
 * @return array
 */
function quiz_datawarehouse_prettify_column_names($row, $querysql) {
    $colnames = [];

    foreach (get_object_vars($row) as $colname => $ignored) {
        // Databases tend to return the columns lower-cased.
        // Try to get the original case from the query.
        if (preg_match('~SELECT.*?\s(' . preg_quote($colname, '~') . ')\b~is',
                $querysql, $matches)) {
            $colname = $matches[1];
        }

        // Change underscores to spaces.
        $colnames[] = str_replace('_', ' ', $colname);
    }
    return $colnames;
}

/**
 * Writes a CSV row and replaces placeholders.
 *
 * @param resource $handle the file pointer
 * @param array $data a data row
 */
function quiz_datawarehouse_write_csv_row($handle, $data) {
    global $CFG;
    $escapeddata = array();
    foreach ($data as $value) {
        $value = str_replace('%%WWWROOT%%', $CFG->wwwroot, $value);
        $value = str_replace('%%Q%%', '?', $value);
        $value = str_replace('%%C%%', ':', $value);
        $value = str_replace('%%S%%', ';', $value);
        $escapeddata[] = '"'.str_replace('"', '""', $value).'"';
    }
    fwrite($handle, implode(',', $escapeddata)."\r\n");
}

/**
 * Read the next row of data from a CSV file.
 *
 * Wrapper around fgetcsv to eliminate the non-standard escaping behaviour.
 *
 * @param resource $handle pointer to the file to read.
 * @return array|false|null next row of data (as for fgetcsv).
 */
function quiz_datawarehouse_read_csv_row($handle) {
    static $disablestupidphpescaping = null;
    if ($disablestupidphpescaping === null) {
        // One-time init, can be removed once we only need to support PHP 7.4+.
        $disablestupidphpescaping = '';
        if (!check_php_version('7.4')) {
            // This argument of fgetcsv cannot be unset in PHP < 7.4, so substitute a character which is unlikely to ever appear.
            $disablestupidphpescaping = "\v";
        }
    }

    return fgetcsv($handle, 0, ',', '"', $disablestupidphpescaping);
}

/**
 * Start the CSV writing.
 *
 * @param \stdClass $handle The handle
 * @param \stdClass $firstrow The first row
 * @param \stdClass $report A report object
 * @throws coding_exception
 */
function quiz_datawarehouse_start_csv($handle, $firstrow, $report) {
    $colnames = quiz_datawarehouse_prettify_column_names($firstrow, $report->querysql);
    if ($report->singlerow) {
        array_unshift($colnames, get_string('queryrundate', 'report_customsql'));
    }
    quiz_datawarehouse_write_csv_row($handle, $colnames);
}

/**
 * Check the list of userids are valid, and have permission to access the report.
 *
 * @param array $userids user ids.
 * @param string $capability capability name.
 * @return string|null null if all OK, else error message.
 */
function quiz_datawarehouse_validate_users($userids, $capability) {
    global $DB;
    if (empty($userstring)) {
        return null;
    }

    $a = new stdClass();
    $a->capability = $capability;
    $a->whocanaccess = get_string('whocanaccess', 'report_customsql');

    foreach ($userids as $userid) {
        // Cannot find the user in the database.
        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            return get_string('usernotfound', 'report_customsql', $userid);
        }
        // User does not have the chosen access level.
        $context = context_user::instance($user->id);
        $a->userid = $userid;
        $a->name = s(fullname($user));
        if (!has_capability($capability, $context, $user)) {
            return get_string('userhasnothiscapability', 'report_customsql', $a);
        }
    }
    return null;
}

/**
 * Get a report name as plain text, for use in places like cron output and email subject lines.
 *
 * @param object $report report settings from the database.
 * @return string the usable version of the name.
 */
function quiz_datawarehouse_plain_text_report_name($report): string {
    return format_string($report->displayname, true,
            ['context' => context_system::instance()]);
}


/**
 * Gives out the highest itemid for files saved in the quiz_datawarehouse component data file area.
 *
 * @return int the highest itemid
 * @throws dml_exception
 */
function get_file_itemid() :int {
    global $DB;
    $highestitemid = $DB->get_field_sql("SELECT max(itemid) FROM {files} WHERE component = 'quiz_datawarehouse'
        AND filearea = 'data'");
    return $highestitemid ?: 0;
}

/**
 * Writes a quiz report datawarehouse file.
 *
 * @param stdClass|array $filerecord contains itemid, filepath, filename and optionally other
 *      attributes of the new file
 * @param string $content the content of the new file
 * @return bool
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function write_datawarehouse_file($filerecord, $content) :bool {
    $fs = get_file_storage();
    // Create a file and save it.
    if (($fs->create_file_from_string($filerecord, $content)) != null) {
        return true;
    } else {
        return false;
    }
}
