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
 * Form for manipulating the data warehouse backends.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_datawarehouse\local\form;

/**
 * Form for manipulating the data warehouse backends.
 *
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backend extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'quiz_datawarehouse\\backend';

    /**
     * Form definition.
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name', 'quiz_datawarehouse'));
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('textarea', 'description', get_string('description', 'quiz_datawarehouse'));
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('text', 'url', get_string('url', 'quiz_datawarehouse'));
        $mform->addRule('url', get_string('required'), 'required', null, 'client');
        $mform->setType('url', PARAM_TEXT);

        $mform->addElement('text', 'username', get_string('username', 'quiz_datawarehouse'));
        $mform->setType('username', PARAM_TEXT);

        $mform->addElement('text', 'password', get_string('password', 'quiz_datawarehouse'));
        $mform->setType('password', PARAM_TEXT);

        $mform->addElement('selectyesno', 'enabled', get_string('enabled', 'quiz_datawarehouse'));
        $mform->setType('enabled', PARAM_INT);

        $mform->addElement('text', 'alloweduser', get_string('alloweduser', 'quiz_datawarehouse'));
        $mform->setType('alloweduser', PARAM_TEXT);

        $this->add_action_buttons();

        if (!empty($this->get_persistent()) && !$this->get_persistent()->can_delete()) {
            $mform->hardFreezeAllVisibleExcept([]);
            $mform->addElement('cancel');
        }
    }

    /**
     * Extra validation.
     *
     * @param  \stdClass $data Data to validate.
     * @param  array $files Array of files.
     * @param  array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    protected function extra_validation($data, $files, array &$errors) {
        $newerrors = [];

        // Check name.
        if (empty($data->name)) {
            $newerrors['name'] = get_string('namerequired', 'quiz_datawarehouse');
        }

        return $newerrors;
    }
}
