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
 * Main library of plugin.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serves any files associated with the quiz datawarehouse plugin.
 *
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param context $context Context
 * @param string $filearea File area for data privacy
 * @param array $args Arguments
 * @param bool $forcedownload If we are forcing the download
 * @param array $options More options
 * @return bool Returns false if we don't find a file.
 */
function quiz_datawarehouse_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'quiz_datawarehouse', $filearea, $args[0], '/', $args[1]);
    if (!$file) {
        return false; // No such file.
    }
    send_stored_file($file, null, 0, $forcedownload, $options);
    return true;
}

/**
 * Gives out the highest itemid for files saved in the quiz_datawarehouse component data file area.
 *
 * @return int the highest itemid
 * @throws dml_exception
 */
function get_file_itemid() :int {
    global $DB;
    $highestitemid = $DB->get_field_sql("SELECT max(itemid) FROM {files} WHERE component = 'quiz_datawarehouse'
        AND filearea = 'data'");
    return $highestitemid ?: 0;
}

/**
 * Writes a quiz report datawarehouse file.
 *
 * @param stdClass|array $filerecord contains itemid, filepath, filename and optionally other
 *      attributes of the new file
 * @param string $content the content of the new file
 * @return bool
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function write_datawarehouse_file($filerecord, $content) :bool {
    $fs = get_file_storage();
    // Create a file and save it.
    if (($fs->create_file_from_string($filerecord, $content)) != null) {
        return true;
    } else {
        return false;
    }
}
