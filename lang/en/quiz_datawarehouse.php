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

$string['addquery'] = 'Add new query';
$string['cantdelete'] = 'The query can\'t be deleted.';
$string['cantedit'] = 'The query can\'t be edited.';
$string['confirmqueryremovalquestion'] = 'Are you sure you want to remove this query?';
$string['confirmqueryremovaltitle'] = 'Confirm query removal?';
$string['datawarehouse'] = 'Data warehouse export';
$string['datawarehouse:managequeries'] = 'Manage data warehouse report queries';
$string['datawarehouse:view'] = 'View data warehouse report';
$string['datawarehouse:viewfiles'] = 'View data warehouse report files';
$string['datawarehousereport'] = 'Quiz data warehouse report';
$string['description'] = 'Description';
$string['editquery'] = 'Edit query';
$string['enabled'] = 'Enabled';
$string['event:querycreated'] = 'Quiz data warehouse query was created';
$string['event:querydeleted'] = 'Quiz data warehouse query was deleted';
$string['event:querydisabled'] = 'Quiz data warehouse query was disabled';
$string['event:queryenabled'] = 'Quiz data warehouse query was enabled';
$string['event:queryupdated'] = 'Quiz data warehouse query was updated';
$string['execute'] = 'Execute';
$string['generateanotherexport'] = 'Generate another export';
$string['invalidquery'] = 'Invalid quiz data warehouse query id {$a}.';
$string['manage_queries'] = 'Quiz data warehouse report queries';
$string['managequeries'] = 'Manage queries';
$string['name'] = 'Name';
$string['namerequired'] = 'A name is required';
$string['newquery'] = 'New query';
$string['pluginname'] = 'Quiz data warehouse export';
$string['plugindescription'] = '<p>Runs an administrator pre-defined query against the Moodle database.</p>
<p>Select the query you want to run, then press the "Execute" button.</p>
<p>Shortly after then, the query result will be retrieavable by the "Quiz report datawarehouse functionalities" web service.</p>
<p>It is probably your automated data warehouse that is going to fetch the result which after ought to be available in your data mart.</p>';
$string['queryfailed'] = 'Error when executing the query: {$a}';
$string['querysql'] = 'Query';
$string['querytorun'] = 'Query to run';
$string['quizinfo'] = 'This quiz has the coursemodule id {$a->coursemoduleid}. It is in the course with id {$a->courseid} and the quiz id is {$a->quizid}.';
$string['setting:supportedversions'] = 'Please note that the following minimum versions of Moodle are required: 4.1.';
$string['used'] = 'In use';

// PRIVACY.
$string['privacy:metadata'] = 'The quiz datawarehouse plugin does not store any personal data about any user.';

