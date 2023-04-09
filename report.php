<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The language strings for the quiz data warehouse report are defined here.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport.php');
require_once($CFG->dirroot . '/mod/quiz/report/datawarehouse/datawarehousesettings_form.php');

/**
 * The quiz data warehouse report helps teachers export quiz data from Moodle to a Data Warehouse.
 */
class quiz_datawarehouse_report extends quiz_attempts_report {

    /**
     * Returns the list of qtypes set in the plugin config.
     * @return array $configqtypes
     */
    public function datawarehouse_get_config_qtypes() {

        $configqtypes = array();

        if (!empty(get_config('quiz_datawarehouse', 'acceptedqtypes'))) {
            $configqtypes = explode(',', trim(str_replace(' ', '', get_config('quiz_datawarehouse', 'acceptedqtypes'))));
        }

        return $configqtypes;
    }

    /**
     * Returns the list of fileareas set in the plugin config.
     * @param array $configqtypes
     * @return array $fileareas
     */
    public function datawarehouse_get_config_fileareas($configqtypes) {

        $fileareas = array();

        if (!empty(get_config('quiz_datawarehouse', 'qtypefileareas'))) {
            $configfileareas = explode(',', trim(str_replace(' ', '', get_config('quiz_datawarehouse', 'qtypefileareas'))));

            if (is_array($configqtypes) && is_array($configfileareas)) {
                if (count($configqtypes) == count($configfileareas)) {
                    $fileareas = array_combine($configqtypes, $configfileareas);
                }
            }
        }

        return $fileareas;
    }

    /**
     * Receives the list of questions occuring in the quiz, config questiontypes and config file areas.
     * Validates the configuration data.
     * Returns a list of errors that occured during validation and list of valid (installed) qtypes.
     * @param array $questions
     * @param array $configfileareas
     * @param array $configqtypes
     * @return array 'errors => array(),'valid' => array()
     */
    public function datawarehouse_get_valid_qtypes($questions, $configfileareas, $configqtypes) {

        $errors             = array();
        $quizqtypes         = array();
        $validqtypes        = array();
        $invalidqtypes      = array();

        if (count($questions) == 0) {
            return array("errors" => array(get_string('response_noquestions', 'quiz_datawarehouse')),
                "valid" => $validqtypes);
        }

        foreach ($questions as $question) {
            array_push($quizqtypes, $question->qtype);
        }

        $quizqtypes = array_unique($quizqtypes);
        $configqtypes = array_intersect($quizqtypes, $configqtypes);

        if (count($configqtypes) == 0) {
            return array("errors" => array(get_string('response_noconfigqtypes', 'quiz_datawarehouse')),
                "valid" => $validqtypes);
        }

        if (count($configfileareas) == 0) {
            return array("errors" => array(get_string('response_noconfigfileareas', 'quiz_datawarehouse')),
                "valid" => $validqtypes);
        }

        $installedqtypes = question_bank::get_all_qtypes();

        foreach ($configqtypes as $configqtype) {

            if (!property_exists((object) $configfileareas, $configqtype)) {
                array_push($errors, get_string('response_nofilearea', 'quiz_datawarehouse') . $configqtype);
                continue;
            }

            if (isset($installedqtypes[$configqtype])) {
                if (!in_array($configfileareas[$configqtype], $installedqtypes[$configqtype]->response_file_areas())) {
                    array_push($errors, get_string('response_invalidfilearea', 'quiz_datawarehouse'). $configqtype);
                    continue;
                }
            } else {
                array_push($errors, get_string('response_nosuchqtype', 'quiz_datawarehouse') . $configqtype);
                continue;
            }

            array_push($validqtypes, $configqtype);
        }

        return  array("errors" => $errors, "valid" => $validqtypes);
    }

    /**
     * Gets the userattempts from database
     * @param int $quizid
     * @param array $validqtypes
     * @return object $userattempts
     */
    public function datawarehouse_get_userattempts($quizid, $validqtypes) {

        if (count($validqtypes) > 0) {

            global $DB;

            list($insql, $inparams) = $DB->get_in_or_equal($validqtypes, SQL_PARAMS_NAMED);

            $sql = "SELECT DISTINCT CONCAT('q', q.id, 'u', u.id, 'a', qza.attempt),
                                    qsa.questionusageid AS quid,
                                    qsa.slot,
                                    qsa.questionid,
                                    qza.attempt         AS num,
                                    (SELECT count(*)
                                    FROM    {quiz_attempts} inner_qza
                                    WHERE   inner_qza.userid  = u.id
                                    AND     inner_qza.quiz    = qza.quiz) as totalnum,
                                    q.name              AS qname,
                                    q.qtype,
                                    u.id                AS userid,
                                    u.idnumber,
                                    u.email,
                                    u.username,
                                    u.firstname,
                                    u.middlename,
                                    u.lastname
                    FROM            {question_attempts} qsa
                    JOIN            {quiz_attempts}     qza ON  qsa.questionusageid = qza.uniqueid
                    JOIN            {question}          q   ON  q.id                = qsa.questionid
                    RIGHT JOIN      {user}              u   ON  u.id                = qza.userid
                                                            AND qza.quiz            = :quizid
                    WHERE           qza.preview = 0
                    AND             qza.id IS NOT NULL
                    AND             q.qtype $insql
                    ORDER BY        u.id";

            $sqlparams  = array('quizid' => $quizid);
            $params     = array_merge($inparams, $sqlparams);

            $userattempts = $DB->get_records_sql($sql, $params);
            return $userattempts;
        } else {
            return false;
        }
    }

    /**
     * Render the downloadpage.
     * @param object $quiz
     * @param cm $cm
     * @param object $course
     * @return bool
     */
    public function display($quiz, $cm, $course) {

        global $OUTPUT, $PAGE;

        $PAGE->set_pagelayout('standard');

        $downloadclicked    = false;
        $filesdownloaded    = false;

        $mform = new quiz_datawarehouse_settings_form();
        $data = $mform->get_data();

        if ($data) {
            // The report generation has been triggered.

            $filename = $this->datawarehouse_get_filename($cm, $course, $quiz);
            $filecontent = $this->datawarehouse_create_txtfile(1, $cm, $course, $quiz);
            $newitemid = get_file_itemid() + 1;
            $filerecord = [
                'component' => 'quiz_datawarehouse',
                'contextid' => \context_system::instance()->id,
                'filearea' => 'data',
                'itemid' => $newitemid,
                'filepath' => '/',
                'filename' => $filename
            ];
            // Create a file and save it.
            write_datawarehouse_file($filerecord, $filecontent);

            echo $this->print_header_and_tabs($cm, $course, $quiz, 'datawarehouse');
            $a = new stdClass();
            $a->link = html_writer::link('https://moodledev.io/general/development/policies/codingstyle',
                get_string('moodlecodingguidelines', 'local_codechecker'));
            $a->path = html_writer::tag('tt', 'local/codechecker');
            $a->excludeexample = html_writer::tag('tt', 'db, backup/*1, *lib*');
            echo html_writer::tag('div', get_string('plugindescription', 'quiz_datawarehouse', $a),
                array('class' => 'plugindescription'));
            $baseurl = new moodle_url($PAGE->url);
            $url = new moodle_url($baseurl, array('id' => $cm->id , 'mode' => 'datawarehouse'));
            echo html_writer::tag('div', html_writer::link($url, get_string('generateanotherexport', 'quiz_datawarehouse')),
                array('class' => 'generateanotherexport'));
            $a = new stdClass();
            $a->coursemoduleid = $cm->id;
            $a->quizid = $quiz->id;
            $a->courseid = $course->id;
            echo html_writer::tag('div', html_writer::link($url, get_string('quizinfo', 'quiz_datawarehouse', $a)),
                array('class' => 'generateanotherexport'));

            // phpcs:disable
            /*
            $downloadclicked = !empty($data->downloadfiles);

            if ($downloadclicked) {
                $questions          = quiz_report_get_significant_questions($quiz);
                $configqtypes       = $this->datawarehouse_get_config_qtypes();
                $configfileareas    = $this->datawarehouse_get_config_fileareas($configqtypes);
                $validqtypes        = $this->datawarehouse_get_valid_qtypes($questions, $configfileareas, $configqtypes);
                $userattempts       = $this->datawarehouse_get_userattempts($quiz->id, $validqtypes["valid"]);
                if ($userattempts) {
                    $filesdownloaded = $this->datawarehouse_process_files(
                        $course,
                        $quiz,
                        $cm->id,
                        $userattempts,
                        $data,
                        $configfileareas);
                }
            }
            */
            // phpcs:enable

        } else {
            // Prompt to trigger the report generation.
            echo $this->print_header_and_tabs($cm, $course, $quiz, 'datawarehouse');

            $a = new stdClass();
            $a->link = html_writer::link('https://moodledev.io/general/development/policies/codingstyle',
                get_string('moodlecodingguidelines', 'local_codechecker'));
            $a->path = html_writer::tag('tt', 'local/codechecker');
            $a->excludeexample = html_writer::tag('tt', 'db, backup/*1, *lib*');
            echo html_writer::tag('div', get_string('plugindescription', 'quiz_datawarehouse', $a),
                array('class' => 'plugindescription'));

            $formdata       = new stdClass;
            $formdata->mode = optional_param('mode', 'datawarehouse', PARAM_ALPHA);
            $formdata->id   = optional_param('id', $quiz->id, PARAM_INT);

            $mform->set_data($formdata);
            $mform->display();
        }

        // phpcs:disable
        if ($downloadclicked) {
        }
        // phpcs:enable
        return true;
    }

    /**
     * Processes the attempts and creates a zip file.
     * @param object $course
     * @param object $quiz
     * @param int $cmid
     * @param array $attempts
     * @param array $configfileareas
     * @param array $data
     * @return bool
     */
    protected function datawarehouse_process_files($course, $quiz, $cmid, $attempts, $configfileareas, $data = null) {

        global $DB, $CFG;

        require_once($CFG->libdir . '/filelib.php');

        raise_memory_limit(MEMORY_EXTRA);
        core_php_time_limit::raise();

        $queryid      = context_course::instance($course->id)->id;
        $zipcontent     = array();
        $zipname        = clean_filename("$course->fullname - $quiz->name - $cmid.zip");
        $zipname        = preg_replace('/[^a-zA-Z0-9.]/', '_', $zipname);
        $zipname        = preg_replace('/_+/', '_', $zipname);

        if (strlen($zipname) > 50) {
            $zipname = substr($zipname, 0, 50) . '.zip';
        }

        $quiz           = $DB->get_record('quiz', array('id' => $quiz->id), '*', MUST_EXIST);
        $cm             = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
        $course         = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $quizobj        = new quiz($quiz, $cm, $course);
        $structure      = $quizobj->get_structure();

        foreach ($attempts as $attempt) {
            $quba               = question_engine::load_questions_usage_by_activity($attempt->quid);
            $qubacontextid      = $quba->get_owning_context()->id;
            $questionattempt    = $quba->get_question_attempt($attempt->slot);

            $filearea           = $configfileareas[$attempt->qtype];
            $lastqtvar          = $questionattempt->get_last_qt_var($filearea);
            $responsefileareas  = $questionattempt->get_question()->qtype->response_file_areas();
            $questionnumber     = $structure->get_displayed_number_for_slot($attempt->slot);

            if (isset($lastqtvar) && in_array($filearea , $responsefileareas)) {
                $files = $questionattempt->get_last_qt_files($filearea, $qubacontextid);
            } else {
                continue;
            }

            $path = $this->datawarehouse_create_pathes($data, $attempt, $questionnumber);

            $txtfile = $this->datawarehouse_create_txtfile(
                $queryid,
                $attempt,
                $course->fullname,
                $course->id,
                $attempt->qname,
                $attempt->questionid,
                $data);

            if ($txtfile) {
                $pathname = clean_param($path[0] . $path[1] . $path[2] . $txtfile->get_filename(), PARAM_PATH);
                $zipcontent[$pathname] = $txtfile;
                $txtfile->delete();
            }

            foreach ($files as $zipfilepath => $file) {
                if ($file->get_filepath() == '/') {
                    $filename = preg_replace('/[^a-zA-Z0-9.]/', '_', $file->get_filename());
                    $filename  = preg_replace('/_+/', '_', $filename);
                    if (strlen($filename) > 30) {
                        $filename = substr($filename, 0, 30) . '.zip';
                    }
                    $pathname = clean_param($path[0] . $path[1] . $path[2] . $path[3] . $filename, PARAM_PATH);
                    $zipcontent[$pathname] = $file;
                }
            }
        }

        if (count($zipcontent) == 0) {
            return false;
        } else {
            $event = \quiz_datawarehouse\event\update_log::create(array('context' => context_module::instance($cmid)));
            $event->trigger();

            $zippacker = new zip_packer();
            $zipfile = tempnam($CFG->tempdir . '/', 'quiz_file_submissions_');

            if ($zippacker->archive_to_pathname($zipcontent, $zipfile)) {
                send_temp_file($zipfile, $zipname);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Creates a pathname for the currently processed file
     * @param object $data
     * @param object $attempt
     * @param int $questionnumber
     * @return array $path
     */
    public function datawarehouse_create_pathes($data, $attempt, $questionnumber) {

        $attempt->idnumber = (empty($attempt->idnumber)) ? 'xxxxxx' : $attempt->idnumber;

        $path = array();

        $path[0] = 'Question ' . $questionnumber . ' - ' . preg_replace("/[^a-zA-Z0-9.]/", " ", $attempt->qname) . '/';

        $path[1] = $attempt->idnumber . '(' . $attempt->userid . ')' . ' ' .
            preg_replace("/[^a-zA-Z0-9.]/", " ", $attempt->firstname) . ' ' .
            preg_replace("/[^a-zA-Z0-9.]/", " ", $attempt->lastname);

        if (isset($data->chooseableanonymization)) {
            if ($data->chooseableanonymization == 1) {
                $path[1] = $attempt->idnumber . '(' . $attempt->userid . ')';
            }
        }

        $path[2] = '/';
        $path[3] = '';
        if ($attempt->totalnum > 1) {
            $path[3] = 'Attempt ' . $attempt->num . '/';
        }

        if (isset($data->zip_inonefolder)) {
            if ($data->zip_inonefolder == 1) {
                $path[2] = '-';
                $path[3] = 'Attempt ' . $attempt->num . '-';
            }
        }

        return $path;
    }

    /**
     * Creates a text that contains the query result
     *
     * @param int $queryid the query to run.
     * @param \stdClass|\cm_info $cm the course-module for this quiz.
     * @param \stdClass $course the course for this quiz.
     * @param \quiz $quiz this quiz.
     * @return object $file
     */
    public function datawarehouse_create_txtfile($queryid, $cm, $course, $quiz) {
        global $DB;
        $sql = "SELECT 'File content' AS content";
        $params = [];
        $results = $DB->get_records_sql($sql, $params);
        return "File content";
    }

    /**
     * Generates and returns the data warehouse report query result file name.
     *
     * @param \stdClass|\cm_info $cm the course-module for this quiz.
     * @param \stdClass $course the course for this quiz.
     * @param \quiz $quiz this quiz.
     * @return string
     * @throws coding_exception
     */
    public function datawarehouse_get_filename($cm, $course, $quiz) :string {
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

        $queryname = "THE query";
        return $USER->id . '-' . $quiz->id . '-' . str_replace(' ', '_', $queryname) . '-' . $timestamp . '-' . $timestamptext;
    }
}
