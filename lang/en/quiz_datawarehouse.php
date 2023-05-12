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

$string['addbackend'] = 'Add new backend';
$string['addquery'] = 'Add new query';
$string['alloweduser'] = 'Allowed user';
$string['backendtosend'] = 'Backend to send the result to';
$string['cantdelete'] = 'The query can\'t be deleted.';
$string['cantedit'] = 'The query can\'t be edited.';
$string['confirmbackendremovalquestion'] = 'Are you sure you want to remove this backend?';
$string['confirmqueryremovalquestion'] = 'Are you sure you want to remove this query?';
$string['confirmbackendremovaltitle'] = 'Confirm backend removal?';
$string['confirmqueryremovaltitle'] = 'Confirm query removal?';
$string['datawarehouse'] = 'Data warehouse export';
$string['datawarehouse:managebackends'] = 'Manage data warehouse report backends';
$string['datawarehouse:managequeries'] = 'Manage data warehouse report queries';
$string['datawarehouse:view'] = 'View data warehouse report';
$string['datawarehouse:viewfiles'] = 'View data warehouse report files';
$string['datawarehousereport'] = 'Quiz data warehouse report';
$string['description'] = 'Description';
$string['editbackend'] = 'Edit backend';
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
$string['manage_backends'] = 'Quiz data warehouse report backends';
$string['manage_queries'] = 'Quiz data warehouse report queries';
$string['managebackends'] = 'Manage backends';
$string['managequeries'] = 'Manage queries';
$string['name'] = 'Name';
$string['namerequired'] = 'A name is required';
$string['newbackend'] = 'New backend';
$string['newquery'] = 'New query';
$string['note'] = 'Notes';
$string['password'] = 'Password';
$string['pluginname'] = 'Quiz data warehouse export';
$string['plugindescription'] = '<p>Runs an administrator pre-defined query against the Moodle database.</p>
<p>Select the query you want to run, then press the "Execute" button.</p>
<p>Shortly after then, the query result will be retrievable by the "Quiz report datawarehouse functionalities" web service.</p>
<p>It is probably your automated data warehouse that is going to fetch the result which after ought to be available in your data mart.</p>';
$string['querynote'] = '<ul>
<li>The token <code>%%COURSEID%%</code> in the query will be replaced with the course id of the course the report is called in, before the query is executed. The same happens with <code>%%CMID%%</code> that will be replaced with the course module id.</li>
<li>The query must include <code>WHERE cm.course=%%COURSEID%% AND cm.id = %%CMID%% AND ((uid.data IS NULL) OR (uid.data !=\'1\'))</code> at the end, whereas the uid.data value comes from an user_info_data field of which a value of 1 expresses that the user opts out of analytics.</li>
<li>You cannot use the characters <code>:</code>, <code>;</code> or <code>?</code> in strings in your query.<ul>
    <li>If you need them in output data (such as when outputting URLs), you can use the tokens <code>%%C%%</code>, <code>%%S%%</code> and <code>%%Q%%</code> respectively.</li>
    <li>If you need them in input data (such as in a regular expression or when querying for the characters), you will need to use a database function to get the characters and concatenate them yourself. In Postgres, respectively these are CHR(58), CHR(59) and CHR(63); in MySQL CHAR(58), CHAR(59) and CHAR(63).</li>
</ul></li>
</ul>';
$string['setting:plugininstruction'] = '<p>The quiz database report is a three fold plug-in consisting of a admin backend, a frontend quiz report and backend web services.<br/>
In the site administration, administrators define queries that selected user can run.<br/>
The user interface to run those queries is found under the quiz reports.<br/>
Then, the results can be fetched through web service.<br/>
This allows for an automated regular fetching, in order that the data can be fed to a data warehouse.</p>
<p>To set everything up correctly, the following steps have to be made:</p>
<ul>
<li>The site must have <a href="../admin/settings.php?section=optionalsubsystems">Web services</a> enabled.</li>
<li>The site must have a <a href="../admin/settings.php?section=webserviceprotocols">Web services protocol</a> (preferrably REST) enabled.</li>
<li>The capability "moodle/webservice:createtoken" should be allowed to the <a href="../admin/roles/manage.php">"Authenticated user"</a> role in order that it\'s possible to generate a security key.</li>
<li>To fetch the provided data<sup><small>*</small></sup>, a user with a <a href="../admin/roles/manage.php">newly created role</a> "Data warehouse webservice user", based on no other role or archetype, and granted the "quiz/datawarehouse:view", "quiz/datawarehouse:viewfiles", as well as "webservice/rest:use" on "System" and "Course" level has to be used. She/he has to include her/his token ("Key") retrieved under <a href="../user/managetoken.php">Security keys</a> in the call.</li>
<li>The administrator defines queries in the <a href="../mod/quiz/report/datawarehouse/query.php">site administration page</a> and give them a distinguishable name.
<li>The capability "quiz/datawarehouse:view" should be allowed to the user that should be able to run a query and to generate a data set out of a quiz.</li>
<li>The web service <a href="../admin/settings.php?section=externalservices">Quiz report datawarehouse functionalities</a> must have the "Can download files" option checked.</li>
<li>To use the web service the token ("Key") retrieved under <a href="../user/managetoken.php">Security keys</a> has to be included in the call <sup><small>**</small></sup>.</li>
</ul><p><sup><small>*</small></sup> A call can be made with <span style="font-family: monospace">curl "&#60;host&#62;/webservice/rest/server.php?wstoken=&#60;token&#62;&wsfunction=quiz_datawarehouse_get_all_files&moodlewsrestformat=json"</span>.<br/>
<sup><small>**</small></sup> A call can be made with <span style="font-family: monospace">curl "&#60;host&#62;/webservice/pluginfile.php/1/quiz_datawarehouse/data/&#60;filename&#62;?token=&#60;token&#62;"</span>.</p>
';
$string['queryfailed'] = 'Error when executing the query: {$a}';
$string['querysql'] = 'Query';
$string['querytorun'] = 'Query to run';
$string['quizinfo'] = 'This quiz has the coursemodule id {$a->coursemoduleid}. It is in the course with id {$a->courseid} and the quiz id is {$a->quizid}.';
$string['setting:supportedversions'] = 'Please note that the following minimum versions of Moodle are required: 4.1.';
$string['url'] = 'URL';
$string['used'] = 'In use';
$string['username'] = 'Username';

// PRIVACY.
$string['privacy:metadata'] = 'The quiz datawarehouse plugin does not store any personal data about any user.';
