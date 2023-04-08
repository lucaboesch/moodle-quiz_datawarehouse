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
 * Unit test for writing to file area.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_datawarehouse;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * Unit test for writing to file area.
 *
 * @package     quiz_datawarehouse
 * @copyright   2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_storage_test extends \advanced_testcase {

    /**
     * Tests saving in the file area.
     *
     * @covers \quiz_datawarehouse\local\form\query
     */
    public function test_file_area() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $newitemid = get_file_itemid() + 1;
        $filerecord = [
            'component' => 'quiz_datawarehouse',
            'contextid' => \context_system::instance()->id,
            'filearea' => 'data',
            'itemid' => $newitemid,
            'filepath' => '/',
            'filename' => 'file.txt'
        ];

        // Create a file and save it.
        write_datawarehouse_file($filerecord, 'File content');

        // Control that it exists.
        $files = $DB->get_records('files', ['component' => 'quiz_datawarehouse', 'filearea' => 'data',
            'contextid' => \context_system::instance()->id, 'itemid' => $newitemid, 'filename' => 'file.txt']);
        $this->assertEquals(1, count($files));

        // Now retrieve it.
    }
}
