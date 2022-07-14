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
 * Block grade new view.
 *
 * @package    block_grade_overview
 * @copyright  José Wilson <j.wilson.df@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/grade_overview/lib.php');

/**
 * Class block grade new view.
 *
 * @package    block_grade_overview
 * @copyright  José Wilson <j.wilson.df@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_grade_overview extends block_base {

    /**
     * Sets the block title
     *
     * @return none
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_grade_overview');
    }

    /**
     * Controls the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
        global $course;

        // Need the bigger course object.
        $this->course = $course;

        // Override the block title if an alternative is set.
        if (isset($this->config->title) && trim($this->config->title) != '') {
            $this->title = format_string($this->config->title);
        }
    }

    /**
     * Creates the block's main content
     *
     * @return string
     */
    public function get_content() {
        global $USER, $COURSE, $SESSION;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $this->content->text = '<div class="row card-deck">';

        $outputhtml = '';
        if ($COURSE->id != SITEID) {
            // Load grade.
            $grade = new stdClass();
            $grade->courseid = $COURSE->id;
            $grade->userid = $USER->id;
            $grade->config = $this->config;
            $SESSION->grade = $grade;

            $coursedata = block_grade_overview_get_course_activities($COURSE->id);
            $activities = $coursedata['activities'];
            $atvscheck = array();
            foreach ($activities as $activity) {
                $atvcheck = 'atv' . $activity['id'];
                if (isset($this->config->$atvcheck) && $this->config->$atvcheck == $activity['id']) {
                    $atvscheck[] = $activity;
                }
            }
            $shownameuser = !isset($this->config->show_name_user) || $this->config->show_name_user == 1;
            $showcheck = !isset($this->config->show_check) || $this->config->show_check == 1;

            // If teacher.
            $context = \context_course::instance($COURSE->id, MUST_EXIST);
            if (has_capability('moodle/course:viewhiddenactivities', $context, $USER->id)) {
                $outputhtml .= block_grade_overview_get_view_editor($COURSE, $this->instance->id, $atvscheck, $showcheck, $grade);
            } else {
                $outputhtml .= block_grade_overview_get_view_student($USER, $COURSE, $atvscheck, $grade, $showcheck, $shownameuser);
            }
        }

        $outputhtml .= '</div>';

        $this->content->text .= $outputhtml;

        return $this->content;
    }

    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view' => true,
            'site-index' => true,
            'mod' => true,
            'my' => true
        );
    }

    /**
     * Allow instance block.
     *
     * @return booblean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Config block.
     *
     * @return booblean
     */
    public function has_config() {
        return true;
    }

    /**
     * Config cron block.
     *
     * @return booblean
     */
    public function cron() {
        mtrace("Hey, my cron script is running");

        return true;
    }

}
