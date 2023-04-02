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

$string['datawarehouse'] = 'Data warehouse export';
$string['pluginname'] = 'Quiz data warehouse export';
$string['plugindescription'] = '<p>Checks code against some aspects of the {$a->link}.</p>
<p>Enter a path relative to the Moodle code root, for example: {$a->path}.</p>
<p>You can enter either a specific PHP file, or to a folder to check all the files it contains.
Multiple entries are supported (files or folders), one per line.</p>
<p>To exclude files, a comma separated list of substr matching paths can be used, for example: {$a->excludeexample}. Asterisks are allowed as wildchars at any place.</p>';

// PRIVACY.
$string['privacy:metadata'] = 'The quiz datawarehouse plugin does not store any personal data about any user.';

