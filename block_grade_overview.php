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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/grade_overview/lib.php');
require_login();

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
        global $CFG, $OUTPUT, $USER, $COURSE, $SESSION;

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $this->content->text = '<div class="row card-deck">';

        $outputhtml = '';
        if ($COURSE->id > 1) {
            // Load grade.
            $grade = new stdClass();
            $grade->courseid = $COURSE->id;
            $grade->userid = $USER->id;
            $grade->config = $this->config;
            $SESSION->grade = $grade;

            $coursedata = get_course_activities($COURSE->id);
            $activities = $coursedata['activities'];
            $atvscheck = array();
            foreach ($activities as $index => $activity) {
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
                $outputhtml .= '<table class="generaltable" id="notas">';
                $outputhtml .= '<tr class="">';
                $outputhtml .= '<td class="cell c0 small" style="">'
                        . '<i class="fa fa-bookmark fa-lg" aria-hidden="true"></i> '
                        . get_string('activity', 'block_grade_overview') . '</td>';
                $outputhtml .= '<td class="cell c1 lastcol text-right small" style="">'
                        . '<i class="icon fa fa-user fa-fw " aria-hidden="true"></i> '
                        . get_string('students', 'block_grade_overview') . '</td>';
                $outputhtml .= '</tr>';
                $totalstundents = count_studens_course($COURSE->id);
                foreach ($atvscheck as $atv) {
                    $totalatv = get_count_atv_all($COURSE->id, $atv['instance'], $atv['type']);
                    $imgcheck = '';
                    if ($showcheck) {
                        $imgcheck = '<img title="' . get_string('grade_pending', 'block_grade_overview') . '" src="'
                                . $CFG->wwwroot . '/blocks/grade_overview/pix/nocheck.png"/>';
                        if ($totalatv == $totalstundents) {
                            $imgcheck = '<img title="' . get_string('grade_awarded', 'block_grade_overview') . '" src="'
                                    . $CFG->wwwroot . '/blocks/grade_overview/pix/check.png"/>';
                        }
                    }
                    $outputhtml .= '<tr class="">';
                    $outputhtml .= '<td class="cell c0" style="">' . $imgcheck . ' <a href="'
                            . $atv['url'] . '">' . $atv['name'] . '</a></td>';
                    $outputhtml .= '<td class="cell c1 lastcol text-right" style="">'
                            . $totalatv . '/' . $totalstundents . '</td>';
                    $outputhtml .= '</tr>';
                }
                $totalaccess = count_studens_accessed_course($COURSE->id);
                $outputhtml .= '<tr class="">';
                $outputhtml .= '<td class="cell c0 " style=""><strong>'
                        . '<i class="icon fa fa-user fa-lg" aria-hidden="true"></i>'
                        . get_string('never_access', 'block_grade_overview') . '</strong></td>';
                $outputhtml .= '<td class="cell c1 lastcol text-right" style=""><strong>'
                        . ($totalstundents - $totalaccess) . '</strong></td>';
                $outputhtml .= '</tr>';
                $outputhtml .= '</table>';
                $outputhtml .= '<hr/><div class="w-100 text-right small"><a href="'
                        . $CFG->wwwroot . '/blocks/grade_overview/view.php?id='
                        . $COURSE->id . '&instanceid=' . $this->instance->id
                        . '"><i class="icon fa fa-table fa-lg " aria-hidden="true"></i>'
                        . get_string('detailed_view', 'block_grade_overview') . '</a></div>';
            } else {
                $calc = 0;
                if (isset($this->config->calc)) {
                    $calc = $this->config->calc;
                }

                $outputhtml = '';

                if ($shownameuser) {
                    $outputhtml .= '<div class="w-100 text-right"><span class="text-muted">'
                            . $USER->firstname . ' ' . $USER->lastname . '</span></div>';
                }
                $outputhtml .= '<table class="generaltable" id="notas">';
                $outputhtml .= '<tr class="">';
                $outputhtml .= '<td class="cell c0 small" style=""><i class="fa fa-bookmark fa-lg" aria-hidden="true"></i>'
                        . get_string('activity', 'block_grade_overview') . '</td>';
                $outputhtml .= '<td class="cell c1 lastcol text-right small" style="">'
                        . '<i class="icon fa fa-table fa-fw " aria-hidden="true"></i>'
                        . get_string('grade', 'block_grade_overview') . '</td>';
                $outputhtml .= '</tr>';
                $cont = 0;
                $sum = 0;
                $taller = 0;
                $decimal = 2;
                $totalatv = count($atvscheck);
                foreach ($atvscheck as $atv) {
                    $gradeatv = get_grade_atcvity($COURSE->id, $USER->id, $atv['instance'], $atv['type']);
                    $imgcheck = '';
                    if ($showcheck) {
                        $imgcheck = '<img title="' . get_string('grade_pending', 'block_grade_overview') . '" src="'
                                . $CFG->wwwroot . '/blocks/grade_overview/pix/nocheck.png"/>';
                        if (isset($gradeatv->finalgrade)) {
                            $imgcheck = '<img title="' . get_string('grade_awarded', 'block_grade_overview') . '" src="'
                                    . $CFG->wwwroot . '/blocks/grade_overview/pix/check.png"/>';
                        }
                    }
                    $outputhtml .= '<tr class="">';
                    $outputhtml .= '<td class="cell c0" style="">' . $imgcheck . ' <a href="' . $atv['url'] . '">'
                            . $atv['name'] . '</a></td>';
                    if (isset($gradeatv->finalgrade)) {
                        if (isset($this->config->decimal_places)) {
                            $decimal = $this->config->decimal_places;
                        }
                        $outputhtml .= '<td class="cell c1 lastcol text-right" style="">'
                                . number_format($gradeatv->finalgrade, $decimal, '.', '') . '</td>';
                        $sum += $gradeatv->finalgrade;
                        if ($gradeatv->finalgrade > $taller) {
                            $taller = $gradeatv->finalgrade;
                        }
                        $cont++;
                    } else {
                        $outputhtml .= '<td class="cell c1 lastcol text-right" style=""> - </td>';
                    }
                    $outputhtml .= '</tr>';
                }
                $outputhtml .= '</table>';
                $outputhtml .= '<div class="w-100 text-right">';
                if ($totalatv == $cont && $calc > 0) {
                    $final = 0;
                    switch ($calc) {
                        case 1:
                            $final = $sum;
                            break;
                        case 2:
                            $final = $sum / $totalatv;
                            break;
                        case 3:
                            $final = $taller;
                            break;
                    }
                    $outputhtml .= '<b>' . get_string('final_grade', 'block_grade_overview');
                    $outputhtml .= ': &nbsp;&nbsp;&nbsp;' . number_format($final, $decimal, '.', '') . '</b><br/>';
                }
                if (isset($this->config->desription)) {
                    $outputhtml .= '<span class="text-muted">' . $this->config->desription . '</span>';
                }
                $outputhtml .= '</div>';
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
