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
 * Quiz report data warehouse external functions and service definitions.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We define the web service functions to install.
$functions = [
    'quiz_datawarehouse_get_all_files' => [
        'classname'    => 'quiz_datawarehouse\external\get_all_files',
        'description'  => 'List all files present in the datawarehouse filearea',
        'ajax'         => false,
        'type'         => 'read',
        'capabilities' => 'quiz/datawarehouse:viewfiles'
    ],
];

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'quiz report datawarehouse functionalities' => [
        'functions' => ['quiz_datawarehouse_get_all_files'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ]
);
