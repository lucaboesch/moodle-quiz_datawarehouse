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
require_once(__DIR__ . '/locallib.php');

/**
 * The quiz data warehouse report helps teachers export quiz data from Moodle to a Data Warehouse.
 */
class quiz_datawarehouse_report extends quiz_attempts_report {

    /**
     * Render the quiz data warehouse report page.
     * @param object $quiz The quiz
     * @param cm $cm The course module
     * @param object $course The course
     * @return bool
     */
    public function display($quiz, $cm, $course) {

        global $OUTPUT, $PAGE, $DB;

        $PAGE->set_pagelayout('standard');

        $downloadclicked    = false;
        $filesdownloaded    = false;

        $mform = new quiz_datawarehouse_settings_form();
        $data = $mform->get_data();

        if ($data) {
            // The report generation has been triggered.
            if (isset($data->queryid)) {
                $query = $DB->get_record('quiz_datawarehouse_queries', ['id' => $data->queryid]);
                $backend = $DB->get_record('quiz_datawarehouse_backends', ['id' => $data->backendid]);
                if (!$query) {
                    throw new moodle_exception('invalidquery', 'quiz_datawarehouse', quiz_datawarehouse_url('query.php'),
                        $data->queryid);
                }
                try {
                    // Create a file and save it.
                    $csvtimestamp = quiz_datawarehouse_generate_csv($query, $backend, time(), $quiz, $cm, $course);
                } catch (Exception $e) {
                    throw new moodle_exception('queryfailed', 'quiz_datawarehouse', quiz_datawarehouse_url('query.php'),
                        $e->getMessage());
                }

                echo $this->print_header_and_tabs($cm, $course, $quiz, 'datawarehouse');
                $a = new stdClass();
                $a->link = html_writer::link('https://moodledev.io/general/development/policies/codingstyle',
                    get_string('moodlecodingguidelines', 'local_codechecker'));
                $a->path = html_writer::tag('tt', 'local/codechecker');
                $a->excludeexample = html_writer::tag('tt', 'db, backup/*1, *lib*');
                echo html_writer::tag('div', get_string('plugindescription', 'quiz_datawarehouse', $a),
                    array('class' => 'plugindescription'));
                $baseurl = new moodle_url($PAGE->url);
                $url = new moodle_url($baseurl, array('id' => $cm->id, 'mode' => 'datawarehouse'));
                echo html_writer::tag('div', html_writer::link($url, get_string('generateanotherexport', 'quiz_datawarehouse')),
                    array('class' => 'generateanotherexport'));

                $a = new stdClass();
                $a->coursemoduleid = $cm->id;
                $a->quizid = $quiz->id;
                $a->courseid = $course->id;

                echo html_writer::tag('div', get_string('quizinfo', 'quiz_datawarehouse', $a),
                    array('class' => 'generateanotherexport'));
            }

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

            $a = new stdClass();
            $a->coursemoduleid = $cm->id;
            $a->quizid = $quiz->id;
            $a->courseid = $course->id;

            echo html_writer::tag('div', get_string('quizinfo', 'quiz_datawarehouse', $a),
                array('class' => 'generateanotherexport mb-3'));

            $formdata       = new stdClass;
            $formdata->mode = optional_param('mode', 'datawarehouse', PARAM_ALPHA);
            $formdata->id   = optional_param('id', $quiz->id, PARAM_INT);

            $mform->set_data($formdata);
            $mform->display();
        }

        return true;
    }
}
