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
 * Quiz report data warehouse external web service functions.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_datawarehouse\external;

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

/**
 * Class quiz_datawarehouse_external_get_all_files
 */
class get_all_files extends \external_api {
    /**
     * Returns description of get_all_files method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            // If this function had any parameters, they would be described here.
            // This example has no parameters, so the array is empty.
        ]);
    }

    /**
     * Return for calling the quiz_datawarehouse get_all_files function.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'group record id'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'name' => new external_value(PARAM_TEXT, 'multilang compatible name, course unique'),
                'description' => new external_value(PARAM_RAW, 'group description text'),
                'enrolmentkey' => new external_value(PARAM_RAW, 'group enrol secret phrase'),
            ])
        );
    }

    /**
     * Get all files.
     *
     * @param array $groups array of group description arrays (with keys groupname and courseid)
     * @return array of newly created groups
     */
    public static function execute($groups) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/group/lib.php");

        $params = self::validate_parameters(self::execute_parameters(), ['groups' => $groups]);

        $transaction = $DB->start_delegated_transaction(); // If an exception is thrown in the below code, all DB queries in this
        // code will be rolled back.

        $groups = array();

        foreach ($params['groups'] as $group) {
            $group = (object)$group;

            if (trim($group->name) == '') {
                throw new invalid_parameter_exception('Invalid group name');
            }
            if ($DB->get_record('groups', ['courseid' => $group->courseid, 'name' => $group->name])) {
                throw new invalid_parameter_exception('Group with the same name already exists in the course');
            }

            // Now security checks.
            $context = get_context_instance(CONTEXT_COURSE, $group->courseid);
            self::validate_context($context);
            require_capability('moodle/course:managegroups', $context);

            // Finally create the group.
            $group->id = groups_create_group($group, false);
            $groups[] = (array) $group;
        }

        $transaction->allow_commit();

        return $groups;
    }
}
