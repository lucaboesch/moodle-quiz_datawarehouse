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
        $limitnum = 0;
    }

    $sql = preg_replace('/\bprefix_(?=\w+)/i', $CFG->prefix, $sql);

    if (isset($params)) {
        foreach ($params as $name => $value) {
            if (((string) (int) $value) === ((string) $value)) {
                $params[$name] = (int) $value;
            }
        }
    } else {
        $params = [];
    }

    // Note: throws Exception if there is an error.
    return $DB->get_recordset_sql($sql, $params, 0, $limitnum);
}

/**
 * A function to substitute the time and user tokens.
 *
 * @param \stdClass $query A query object
 * @param int $timenow A timestamp
 * @param object $quiz The quiz
 * @param cm $cm The course module
 * @param object $course The course
 * @return array|string|string[]
 */
function quiz_datawarehouse_prepare_sql($query, $timenow, object $quiz, $cm, object $course) {
    global $USER;
    $sql = $query->querysql;
    $sql = quiz_datawarehouse_substitute_user_token($sql, $USER->id);
    $sql = quiz_datawarehouse_substitute_course_module_id($sql, $cm->id);
    $sql = quiz_datawarehouse_substitute_course_id($sql, $course->id);
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
 * @param \stdClass $query A query object
 * @param int $timenow A timestamp
 * @param object $quiz The quiz
 * @param cm $cm The course module
 * @param object $course The course
 * @return mixed|null A timestamp
 * @throws dml_exception
 */
function quiz_datawarehouse_generate_csv($query, $timenow, $quiz, $cm, $course) {
    global $DB;
    $starttime = microtime(true);

    $itemid = get_file_itemid() + 1;

    $sql = quiz_datawarehouse_prepare_sql($query, $timenow, $quiz, $cm, $course);

    $rs = quiz_datawarehouse_execute_query($sql);

    $csvfilenames = array();
    $csvtimestamp = null;
    $count = 0;
    $filename = quiz_datawarehouse_get_filename($cm, $course, $quiz, $query, $itemid);
    foreach ($rs as $row) {
        if (!$csvtimestamp) {
            list($csvfilename, $tempfolder, $csvtimestamp) = quiz_datawarehouse_csv_filename($filename, $timenow);
            $csvfilenames[] = $csvfilename;

            if (!file_exists($csvfilename)) {
                $handle = fopen($csvfilename, 'w');
                quiz_datawarehouse_start_csv($handle, $row, $query);
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
        quiz_datawarehouse_write_csv_row($handle, $data);
        $count += 1;
    }
    $rs->close();

    if (!empty($handle)) {
        if (isset($querylimit)) {
            if ($count > $querylimit) {
                quiz_datawarehouse_write_csv_row($handle, [REPORT_CUSTOMSQL_LIMIT_EXCEEDED_MARKER]);
            }
        }

        fclose($handle);
    }

    // Now copy the file over to the 'real' files in moodledata.
    $fs = get_file_storage();

    $filerecord = [
        'component' => 'quiz_datawarehouse',
        'contextid' => \context_system::instance()->id,
        'filearea' => 'data',
        'itemid' => $itemid,
        'filepath' => '/',
        'filename' => $filename];
    $fs->create_file_from_pathname($filerecord, $tempfolder . '/' . $filename);

    $url = $DB->get_field('quiz_datawarehouse_backends', 'url', array('id' => 1));
    // Initiate cURL object.
    $curl = curl_init();
    // Set your URL.
    curl_setopt($curl, CURLOPT_URL, $url . $filename);
    // Indicate, that you plan to upload a file.
    curl_setopt($curl, CURLOPT_UPLOAD, true);
    // Indicate your protocol.
    curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    // Set flags for transfer.
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
    // Disable header (optional).
    curl_setopt($curl, CURLOPT_HEADER, false);
    // Set HTTP method to PUT.
    curl_setopt($curl, CURLOPT_PUT, 1);
    // Indicate the file you want to upload.
    curl_setopt($curl, CURLOPT_INFILE, fopen($tempfolder . '/' . $filename, 'rb'));
    // Indicate the size of the file (it does not look like this is mandatory, though).
    curl_setopt($curl, CURLOPT_INFILESIZE, filesize($tempfolder . '/' . $filename));
    // Only use below option on TEST environment if you have a self-signed certificate!!! On production this can cause security
    // issues.
    // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // Execute.
    curl_exec($curl);

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
 * @param string $filename The file name
 * @param int $timenow A timestamp
 * @return array
 */
function quiz_datawarehouse_csv_filename($filename, $timenow) {
    return quiz_datawarehouse_temp_csv_name($filename, $timenow);
}

/**
 * Return a temporary CSV filename
 *
 * @param string $filename The file name
 * @param int $timestamp A timestamp
 * @return array Some result
 */
function quiz_datawarehouse_temp_csv_name($filename, $timestamp) {
    global $CFG;
    // Prepare temp area.
    $tempfolder = make_temp_directory('quiz_datawarehouse');
    $tempfile = $tempfolder . '/' . $filename;
    return [$tempfile, $tempfolder, $timestamp];
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
 * Substitute course ids in a SQL string.
 *
 * @param string $sql The SQL query.
 * @param int $courseid A user id
 * @return array|string|string[] Some result
 */
function quiz_datawarehouse_substitute_course_id($sql, $courseid) {
    return str_replace('%%COURSEID%%', $courseid, $sql);
}

/**
 * Substitute course module ids in a SQL string.
 *
 * @param string $sql The SQL query.
 * @param int $cmid A course module id
 * @return array|string|string[] Some result
 */
function quiz_datawarehouse_substitute_course_module_id($sql, $cmid) {
    return str_replace('%%CMID%%', $cmid, $sql);
}

/**
 * Create url to $relativeurl.
 *
 * @param string $relativeurl Relative url.
 * @param array $params Parameter for url.
 * @return moodle_url the relative url.
 */
function quiz_datawarehouse_url($relativeurl, $params = []) {
    return new moodle_url('/mod/quiz/report/datawarehouse/query.php' . $relativeurl, $params);
}

/**
 * Create the download url for the report.
 *
 * @param int $queryid The query id.
 * @param array $params Parameters for the url.
 * @return moodle_url The download url.
 */
function quiz_datawarehouse_downloadurl($queryid, $params = []) {
    $downloadurl = moodle_url::make_pluginfile_url(
        context_system::instance()->id,
        'quiz_datawarehouse',
        'download',
        $queryid,
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
        if (!isset($value)) {
            $value = '';
        }
        $value = str_replace('%%WWWROOT%%', $CFG->wwwroot, $value);
        $value = str_replace('%%Q%%', '?', $value);
        $value = str_replace('%%C%%', ':', $value);
        $value = str_replace('%%S%%', ';', $value);
        $escapeddata[] = '"' . str_replace('"', '""', $value) . '"';
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
 * @param \stdClass $query A query object
 * @throws coding_exception
 */
function quiz_datawarehouse_start_csv($handle, $firstrow, $query) {
    $colnames = quiz_datawarehouse_prettify_column_names($firstrow, $query->querysql);
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
 * @param object $query query settings from the database.
 * @return string the usable version of the name.
 */
function quiz_datawarehouse_plain_text_report_name($query): string {
    return format_string($query->displayname, true,
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

/**
 * Generates and returns the data warehouse report query result file name.
 *
 * @param \stdClass|\cm_info $cm the course-module for this quiz.
 * @param \stdClass $course the course for this quiz.
 * @param \quiz $quiz this quiz.
 * @param \stdClass $query A query object
 * @param string $itemid The item id
 * @throws coding_exception
 */
function quiz_datawarehouse_get_filename($cm, $course, $quiz, stdClass $query, string $itemid) :string {
    global $USER;
    $timezone = \core_date::get_user_timezone_object();
    $timestamp = time();
    $calendartype = \core_calendar\type_factory::get_calendar_instance();
    $timestamparray = $calendartype->timestamp_to_date_array($timestamp, $timezone);
    $timestamptext = $timestamparray['year'] . "-" .
        sprintf("%02d", $timestamparray['mon']) . "-" .
        sprintf("%02d", $timestamparray['mday']) . "-" .
        sprintf("%02d", $timestamparray['hours']) . "-" .
        sprintf("%02d", $timestamparray['minutes']) . "-" .
        sprintf("%02d", $timestamparray['seconds']);

    $queryname = $query->name;
    return $USER->id . '-' . $itemid . '-' . $quiz->id . '-' . str_replace(' ', '_', $queryname) . '-' . $timestamp . '-' .
        $timestamptext . '.csv';
}

/**
 * Return the queries ids, names and descriptions in the order from the database.
 *
 * @return array An array of queries id, names and descriptions.
 */
function quiz_datawarehouse_get_queries() {
    global $DB;
    return $DB->get_records_sql("
            SELECT qdq.id, qdq.name, qdq.description
              FROM {quiz_datawarehouse_queries} qdq
          ORDER BY sortorder ASC", []);
}
