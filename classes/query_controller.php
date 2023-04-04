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
 * Class for manipulating with the query records.
 *
 * @package    quiz_datawarehouse
 * @copyright  2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_datawarehouse;

use core\notification;
use quiz_datawarehouse\local\table\query_list;

/**
 * Class for manipulating with the query records.
 *
 * @copyright  2023 Luca Bösch <luca.boesch@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query_controller {
    /**
     * View action.
     */
    const ACTION_VIEW = 'view';

    /**
     * Add action.
     */
    const ACTION_ADD = 'add';

    /**
     * Edit action.
     */
    const ACTION_EDIT = 'edit';

    /**
     * Delete action.
     */
    const ACTION_DELETE = 'delete';

    /**
     * Hide action.
     */
    const ACTION_HIDE = 'hide';

    /**
     * Show action.
     */
    const ACTION_SHOW = 'show';


    /**
     * Locally cached $OUTPUT object.
     * @var \bootstrap_renderer
     */
    protected $output;

    /**
     * region_manager constructor.
     */
    public function __construct() {
        global $OUTPUT;

        $this->output = $OUTPUT;
    }

    /**
     * Execute required action.
     *
     * @param string $action Action to execute.
     */
    public function execute($action) {

        $this->set_external_page();

        switch($action) {
            case self::ACTION_ADD:
            case self::ACTION_EDIT:
                $this->edit($action, optional_param('id', null, PARAM_INT));
                break;

            case self::ACTION_DELETE:
                $this->delete(required_param('id', PARAM_INT));
                break;

            case self::ACTION_HIDE:
                $this->hide(required_param('id', PARAM_INT));
                break;

            case self::ACTION_SHOW:
                $this->show(required_param('id', PARAM_INT));
                break;

            case self::ACTION_VIEW:
            default:
                $this->view();
                break;
        }
    }

    /**
     * Set external page for the manager.
     */
    protected function set_external_page() {
        admin_externalpage_setup('quiz_datawarehouse/query');
    }

    /**
     * Return record instance.
     *
     * @param int $id
     * @param \stdClass|null $data
     *
     * @return \quiz_datawarehouse\query
     */
    protected function get_instance($id = 0, \stdClass $data = null) {
        return new query($id, $data);
    }

    /**
     * Print out all records in a table.
     */
    protected function display_all_records() {
        $records = query::get_records([], 'id');

        $table = new query_list();
        $table->display($records);
    }

    /**
     * Returns a text for create new record button.
     * @return string
     */
    protected function get_create_button_text() : string {
        return get_string('addquery', 'quiz_datawarehouse');
    }

    /**
     * Returns form for the record.
     *
     * @param \quiz_datawarehouse\query|null $instance
     *
     * @return \quiz_datawarehouse\local\form\query
     */
    protected function get_form($instance) : \quiz_datawarehouse\local\form\query {
        global $PAGE;

        return new \quiz_datawarehouse\local\form\query($PAGE->url->out(false), ['persistent' => $instance]);
    }

    /**
     * View query heading string.
     * @return string
     */
    protected function get_view_heading() : string {
        return get_string('managequeries', 'quiz_datawarehouse');
    }

    /**
     * New query heading string.
     * @return string
     */
    protected function get_new_heading() : string {
        return get_string('newquery', 'quiz_datawarehouse');
    }

    /**
     * Edit query heading string.
     * @return string
     */
    protected function get_edit_heading() : string {
        return get_string('editquery', 'quiz_datawarehouse');
    }

    /**
     * Returns base URL for the manager.
     * @return string
     */
    public static function get_base_url() : string {
        return '/mod/quiz/report/datawarehouse/query.php';
    }

    /**
     * Execute edit action.
     *
     * @param string $action Could be edit or create.
     * @param null|int $id Id of the region or null if creating a new one.
     */
    protected function edit($action, $id = null) {
        global $PAGE;

        $PAGE->set_url(new \moodle_url(static::get_base_url(), ['action' => $action, 'id' => $id]));
        $instance = null;

        if ($id) {
            $instance = $this->get_instance($id);
        }

        $form = $this->get_form($instance);

        if ($form->is_cancelled()) {
            redirect(new \moodle_url(static::get_base_url()));
        } else if ($data = $form->get_data()) {
            unset($data->submitbutton);
            try {
                if (empty($data->id)) {
                    // Inserting a completely new query.
                    $persistent = $this->get_instance(0, $data);
                    $persistent->create();

                    \quiz_datawarehouse\event\query_created::create_strict(
                        $persistent,
                        \context_system::instance()
                    )->trigger();
                    $this->trigger_enabled_event($persistent);
                } else {
                    // Modifying an existing query.
                    $instance->from_record($data);
                    $instance->update();

                    \quiz_datawarehouse\event\query_updated::create_strict(
                        $instance,
                        \context_system::instance()
                    )->trigger();
                    $this->trigger_enabled_event($instance);
                }
                notification::success(get_string('changessaved'));
            } catch (\Exception $e) {
                notification::error($e->getMessage());
            }
            redirect(new \moodle_url(static::get_base_url()));
        } else {
            if (empty($instance)) {
                $this->header($this->get_new_heading());
            } else {
                if (!$instance->can_delete()) {
                    notification::warning(get_string('cantedit', 'quiz_datawarehouse'));
                }
                $this->header($this->get_edit_heading());
            }
        }

        $form->display();
        $this->footer();
    }

    /**
     * Execute delete action.
     *
     * @param int $id ID of the region.
     */
    protected function delete($id) {
        require_sesskey();
        $instance = $this->get_instance($id);

        if ($instance->can_delete()) {
            $instance->delete();
            notification::success(get_string('deleted'));

            \quiz_datawarehouse\event\query_deleted::create_strict(
                $id,
                \context_system::instance()
            )->trigger();

            redirect(new \moodle_url(static::get_base_url()));
        } else {
            notification::warning(get_string('cantdelete', 'quiz_datawarehouse'));
            redirect(new \moodle_url(static::get_base_url()));
        }
    }

    /**
     * Execute view action.
     */
    protected function view() {
        global $PAGE;

        $this->header($this->get_view_heading());
        $this->print_add_button();
        $this->display_all_records();

        // JS for query management.
        $PAGE->requires->js_call_amd('quiz_datawarehouse/managequeries', 'setup');

        $this->footer();
    }

    /**
     * Show the query.
     *
     * @param int $id The ID of the query to show.
     */
    protected function show(int $id) {
        $this->show_hide($id, 1);
    }

    /**
     * Hide the query.
     *
     * @param int $id The ID of the query to hide.
     */
    protected function hide($id) {
        $this->show_hide($id, 0);
    }

    /**
     * Show or Hide the query.
     *
     * @param int $id The ID of the query to hide.
     * @param int $visibility The intended visibility.
     */
    protected function show_hide(int $id, int $visibility) {
        require_sesskey();
        $query = $this->get_instance($id);
        $query->set('enabled', $visibility);
        $query->save();

        $this->trigger_enabled_event($query);

        redirect(new \moodle_url(self::get_base_url()));
    }

    /**
     * Print out add button.
     */
    protected function print_add_button() {
        echo $this->output->single_button(
            new \moodle_url(static::get_base_url(), ['action' => self::ACTION_ADD]),
            $this->get_create_button_text()
        );
    }

    /**
     * Print out page header.
     * @param string $title Title to display.
     */
    protected function header($title) {
        echo $this->output->header();
        echo $this->output->heading($title);
    }

    /**
     * Print out the page footer.
     *
     * @return void
     */
    protected function footer() {
        echo $this->output->footer();
    }

    /**
     * Helper function to fire off an event that informs of if a query is enabled or not.
     *
     * @param \quiz_datawarehouse\query $query The query persistent object.
     */
    private function trigger_enabled_event(query $query) {
        $eventstring = ($query->get('enabled') == 0 ? 'disabled' : 'enabled');

        $func = '\quiz_datawarehouse\event\query_' . $eventstring;
        $func::create_strict(
            $query,
            \context_system::instance()
        )->trigger();
    }

}