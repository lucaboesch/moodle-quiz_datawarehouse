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
global $ADMIN;

if ($hassiteconfig) {

    $settings->add(new admin_setting_heading(
        'quiz_datawarehouse/supportedversions',
        '',
        $OUTPUT->notification(get_string('setting:supportedversions', 'quiz_datawarehouse'), 'warning')));
}

if (has_capability('quiz/datawarehouse:managequeries', context_system::instance())) {
    $ADMIN->add('modsettingsquizcat',
        new admin_externalpage(
            'quiz_datawarehouse/query',
            get_string('manage_queries', 'quiz_datawarehouse'),
            new moodle_url('/mod/quiz/report/datawarehouse/query.php'),
            'quiz/datawarehouse:managequeries'
        )
    );
}
