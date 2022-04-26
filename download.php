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
 * Game block caps.
 *
 * @package    block_grade_overview
 * @copyright  José Wilson <j.wilson.df@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/grade_overview/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . "/dataformatlib.php");

require_login();

$courseid = required_param('id', PARAM_INT);
$id = required_param('instanceid', PARAM_INT);
$groupid = optional_param('group', 0, PARAM_INT);
$op = optional_param('op', '', PARAM_ALPHA);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('course');
$PAGE->set_url('/blocks/grade_overview/download.php', array('id' => $courseid, 'instanceid' => $id));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title(get_string('completion_report', 'block_grade_overview'));
$PAGE->set_heading(get_string('completion_report', 'block_grade_overview'));

$dataformat = optional_param('dataformat', '', PARAM_ALPHA);

if (isset($courseid) && $op === "c") {
    $grade = $SESSION->grade;
    $coursedata = block_grade_overview_get_course_activities($courseid);
    $activities = $coursedata['activities'];
    $atvscheck = array();
    foreach ($activities as $index => $activity) {
        $atvscheck[] = $activity;
    }
    $columns = [get_string('name'),
        get_string('email'),
        get_string('group')];
    foreach ($atvscheck as $atv) {
        $columns[] = $atv['name'];
        $columns[] = ' Status ';
    }
    $users = block_grade_overview_get_students_course($courseid, $groupid);
    foreach ($users as $userx) {
        $groups = \groups_get_user_groups($courseid, $userx->id);
        $txtgroup = '';
        foreach ($groups as $group) {
            $countgroups = count($group);
            for ($i = 0; $i <= $countgroups; $i++) {
                if (isset($group[$i])) {
                    if ($i > 0) {
                        $txtgroup .= ', ';
                    }
                    $txtgroup .= \groups_get_group_name($group[$i]);
                }
            }
        }

        $decimal = 2;
        $grade = [$userx->firstname . ' ' . $userx->lastname, $userx->email, $txtgroup];
        foreach ($atvscheck as $atv) {
            $gradeuser = block_grade_overview_get_user_mod_grade($userx->id, $atv['instance'], $atv['type'], $courseid);
            $completedmod = block_grade_overview_is_completed_module($userx->id, $courseid, $atv['id']);

            $txtcompleted = ' - ';
            if ($completedmod) {
                $txtcompleted = ' Concluído ';
            }
            if (isset($gradeuser) && $gradeuser) {
                if (isset($grade->config->decimal_places)) {
                    $decimal = $grade->config->decimal_places;
                }
                $grade[] = number_format($gradeuser, $decimal, '.', '');
                $grade[] = $txtcompleted;
            } else {
                $grade[] = ' - ';
                $grade[] = ' - ';
            }
        }
        $rows[] = $grade;
    }

    download_as_dataformat('completion_report', $dataformat, $columns, $rows);
}

if (isset($courseid) && $op === "d") {
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
    $columns = [get_string('name'),
        get_string('email'),
        get_string('group')];
    foreach ($atvscheck as $atv) {
        $columns[] = $atv['name'];
    }
    $calc = 0;
    if (isset($grade->config->calc) && $grade->config->calc > 0) {
        $calc = $grade->config->calc;
    }
    if ($calc > 0) {
        $columns[] = get_string('final_grade', 'block_grade_overview');
    }
    $users = block_grade_overview_get_students_course($courseid, $groupid);
    foreach ($users as $userx) {
        $groups = \groups_get_user_groups($courseid, $userx->id);
        $txtgroup = '';
        foreach ($groups as $group) {
            $countgroups = count($group);
            for ($i = 0; $i <= $countgroups; $i++) {
                if (isset($group[$i])) {
                    if ($i > 0) {
                        $txtgroup .= ', ';
                    }
                    $txtgroup .= \groups_get_group_name($group[$i]);
                }
            }
        }



        $grade = [$userx->firstname . ' ' . $userx->lastname, $userx->email, $txtgroup];

        $count = 1;
        $max = count($atvscheck);
        $countx = 0;
        $sum = 0;
        $taller = 0;
        $decimal = 2;
        foreach ($atvscheck as $atv) {
            $gradeuser = block_grade_overview_get_user_mod_grade($userx->id, $atv['instance'], $atv['type'], $courseid);

            if (isset($gradeuser) && $gradeuser) {
                if (isset($grade->config->decimal_places)) {
                    $decimal = $grade->config->decimal_places;
                }
                $grade[] = number_format($gradeuser, $decimal, '.', '');
                $sum += $gradeuser;
                if ($gradeuser > $taller) {
                    $taller = $gradeuser;
                }
                $countx++;
            } else {
                $grade[] = ' - ';
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
            $grade[] = number_format($final, $decimal, '.', '');
        } else if ($calc > 0) {
            $grade[] = ' - ';
        }
        $rows[] = $grade;
    }
    download_as_dataformat('detail_report', $dataformat, $columns, $rows);
}