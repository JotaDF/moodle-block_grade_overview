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
 * @copyright  2019 Jose Wilson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/grade_overview/lib.php');
require_login();

global $USER, $SESSION, $COURSE, $OUTPUT, $CFG;

$courseid = required_param('id', PARAM_INT);
$id = required_param('instanceid', PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('course');
$PAGE->set_url('/blocks/grade_overview/view.php', array('id' => $courseid));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title(get_string('pluginname', 'block_grade_overview'));
$PAGE->set_heading(get_string('pluginname', 'block_grade_overview'));

echo $OUTPUT->header();

$blockcontext = CONTEXT_BLOCK::instance($id);
$outputhtml = '<div class="view">';
$grade = new stdClass();
if (isset($SESSION->grade)) {
    $grade = $SESSION->grade;
    $coursedata = block_grade_overview_get_course_activities($courseid);
    $activities = $coursedata['activities'];
    $atvscheck = array();
    foreach ($activities as $index => $activity) {
        $atvcheck = 'atv' . $activity['id'];
        if (isset($grade->config->$atvcheck) && $grade->config->$atvcheck == $activity['id']) {
            $atvscheck[] = $activity;
        }
    }
    // If teacher.
    $context = \context_course::instance($courseid, MUST_EXIST);
    if (has_capability('block/grade_overview:view', $blockcontext, $USER->id)) {
        $outputhtml .= groups_print_course_menu($course, '/blocks/grade_overview/view.php?id=' . $courseid . '&instanceid=' . $id);

        echo $OUTPUT->download_dataformat_selector(get_string('downloadthis', 'block_grade_overview'), 'download.php',
                'dataformat', ['id' => $courseid, 'instanceid' => $id, 'group' => $groupid, 'op' => 'd']);
        $calc = 0;
        if (isset($grade->config->calc) && $grade->config->calc > 0) {
            $calc = $grade->config->calc;
        }
        $users = block_grade_overview_get_students_course($courseid, $groupid);
        $outputhtml .= '<table class="generaltable" id="notas">';
        $outputhtml .= '<tr class="">';
        $outputhtml .= '<td class="cell c0" style=""><strong>' . get_string('name') . '</strong></td>';
        $count = 1;
        $max = count($atvscheck);
        foreach ($atvscheck as $atv) {
            $last = '';
            if ($count == $max && $calc == 0) {
                $last = 'lastcol';
            }
            $outputhtml .= '<td class="cell c' . $count . ' '
                    . $last . ' text-center" style=""><a href="'
                    . $atv['url'] . '">' . $atv['name'] . '</a></td>';
            $count++;
        }

        if ($calc > 0) {
            $outputhtml .= '<td class="cell c' . $count . ' lastcol text-center" style=""><strong>'
                    . get_string('final_grade', 'block_grade_overview') . '</strong></td>';
        }

        $outputhtml .= '</tr>';
        foreach ($users as $userx) {
            $userpictureparams = array('size' => 30, 'link' => false, 'alt' => 'User');
            $userpicture = $OUTPUT->user_picture($userx, $userpictureparams);
            $outputhtml .= '<tr class="">';
            $outputhtml .= '<td class="cell c0" style="">'
                    . '<a class="username" href="' . $CFG->wwwroot . '/user/view.php?id='
                    . $userx->id . '&amp;course=' . $courseid . '">'
                    . $userpicture . $userx->firstname . ' ' . $userx->lastname . '</a></td>';
            $count = 1;
            $max = count($atvscheck);
            $countx = 0;
            $sum = 0;
            $taller = 0;
            $decimal = 2;
            foreach ($atvscheck as $atv) {
                $gradeuser = block_grade_overview_get_user_mod_grade($userx->id, $atv['instance'], $atv['type'], $courseid);
                $last = '';
                if ($count == $max) {
                    $last = 'lastcol';
                }
                if (isset($gradeuser) && $gradeuser) {
                    if (isset($grade->config->decimal_places)) {
                        $decimal = $grade->config->decimal_places;
                    }
                    $outputhtml .= '<td class="cell c' . $count . ' '
                            . $last . ' text-center" style="">'
                            . number_format($gradeuser, $decimal, '.', '') . '</td>';
                    $sum += $gradeuser;
                    if ($gradeuser > $taller) {
                        $taller = $gradeuser;
                    }
                    $countx++;
                } else {
                    $outputhtml .= '<td class="cell c' . $count . ' '
                            . $last . ' text-center" style=""> - </td>';
                }
                $count++;
            }
            $final = 0;
            if ($calc > 0 && $countx > 0) {
                switch ($calc) {
                    case 1:
                        $final = $sum;
                        break;
                    case 2:
                        $final = $sum / $countx;
                        break;
                    case 3:
                        $final = $taller;
                        break;
                }
                $outputhtml .= '<td class="cell c' . $count . ' '
                        . $last . ' text-center" style=""><strong>' . number_format($final, $decimal, '.', '')
                        . '</strong></td>';
            } else {
                if ($calc > 0) {
                    $outputhtml .= '<td class="cell c' . $count . ' '
                            . $last . ' text-center" style=""><strong> - </strong></td>';
                }
            }
            $outputhtml .= '</tr>';
        }
        $outputhtml .= '</table>';
    }
}
$outputhtml .= '</div>';
echo $outputhtml;
echo $OUTPUT->footer();
