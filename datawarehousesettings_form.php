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
 * Plugin administration pages are defined here.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Settingsform for datawarehouse report.
 */
class quiz_datawarehouse_settings_form extends moodleform {

    /**
     * Add elements to form
     */
    public function definition() {

        global $CFG;

        $mform = $this->_form;

        // phpcs:disable
        /*
        $showdownloadsettings = get_config('quiz_datawarehouse', 'chooseablefilestructure') == 1
            || get_config('quiz_datawarehouse', 'chooseableanonymization') == 1;

        if ($showdownloadsettings) {
            $mform->addElement('header', 'preferencespage', get_string('downloadsettings', 'quiz_datawarehouse'));
        }

        if (get_config('quiz_datawarehouse', 'chooseablefilestructure')) {

            $mform->addElement('select', 'zip_inonefolder', get_string('zip_inonefolder', 'quiz_datawarehouse'), array(
                0 => get_string('no'),
                1 => get_string('yes')
            ));

            $mform->addHelpButton('zip_inonefolder', 'zip_inonefolder', 'quiz_datawarehouse');
        }

        if (get_config('quiz_datawarehouse', 'chooseableanonymization')) {

            $mform->addElement('select', 'chooseableanonymization',
                get_string('adminsetting_anonymizedownload', 'quiz_datawarehouse'), array(
                    0 => get_string('no'),
                    1 => get_string('yes')
                ));

            $mform->addHelpButton('chooseableanonymization', 'adminsetting_anonymizedownload', 'quiz_datawarehouse');
        }

        if ($showdownloadsettings) {
            $mform->closeHeaderBefore('downloadfiles');
        }
        */
        // phpcs:enable

        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', '');
        $mform->setType('mode', PARAM_ALPHA);

        $options = [];
        $queries = quiz_datawarehouse_get_queries();
        foreach ($queries as $query) {
            $options[$query->id] = format_string($query->name, false, ["context" => \context_system::instance()]);
        }
        $mform->addElement('select', 'queryid', get_string('querytorun', 'quiz_datawarehouse'), $options);

        $options = [];
        $backends = quiz_datawarehouse_get_backends();
        if (count($backends) > 1) {
            foreach ($backends as $backend) {
                $options[$backend->id] = format_string($backend->name, false, ["context" => \context_system::instance()]);
            }
            $mform->addElement('select', 'backendid', get_string('backendtosend', 'quiz_datawarehouse'), $options);
        } else {
            $mform->addElement('hidden', 'backendid', $backend->id);
            $mform->setType('backendid', PARAM_INT);
        }
        $mform->addElement('submit', 'downloadfiles', get_string('execute', 'quiz_datawarehouse'));
    }
}
