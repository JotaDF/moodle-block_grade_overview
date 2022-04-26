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
use core_competency\api as competency_api;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/gradelib.php');

/**
 * Return grade atv of user.
 *
 * @param int $courseid
 * @param int $userid
 * @param int $iteminstance
 * @param int $itemtype
 * @return mixed
 */
function block_grade_overview_get_grade_activity($courseid, $userid, $iteminstance, $itemtype) {
    global $DB;
    if ($courseid > 1) {
        $sql = "SELECT i.itemname,g.userid,g.finalgrade "
                . "FROM {grade_items} i INNER JOIN {grade_grades} g ON i.id=g.itemid "
                . "WHERE i.courseid= :courseid "
                . "AND i.itemmodule= :itemtype "
                . "AND i.iteminstance= :iteminstance "
                . "AND g.userid= :userid";
        $params['courseid'] = $courseid;
        $params['itemtype'] = $itemtype;
        $params['iteminstance'] = $iteminstance;
        $params['userid'] = $userid;
        $gradeatv = $DB->get_record_sql($sql, $params);
        return $gradeatv;
    }
    return false;
}

/**
 * Return count grade atcvity.
 *
 * @param int $courseid
 * @param int $iteminstance
 * @param int $itemtype
 * @return int
 */
function block_grade_overview_get_count_atv_all($courseid, $iteminstance, $itemtype) {
    global $DB;
    if ($courseid > 1) {
        $sql = "SELECT count(*) as total "
                . "FROM {grade_items} i "
                . "INNER JOIN {grade_grades} g ON i.id=g.itemid "
                . "WHERE i.courseid= :courseid "
                . "AND i.itemmodule= :itemtype "
                . "AND g.finalgrade IS NOT NULL "
                . "AND i.iteminstance= :iteminstance";
        $params['courseid'] = $courseid;
        $params['itemtype'] = $itemtype;
        $params['iteminstance'] = $iteminstance;
        $rs = $DB->get_record_sql($sql, $params);
        return $rs->total;
    }
    return false;
}

/**
 * Return total students per course.
 *
 * @param int $courseid
 * @return int
 */
function block_grade_overview_count_students_course($courseid) {
    global $DB;
    if ($courseid > 1) {
        $sql = "SELECT count(*) total FROM {role_assignments} rs"
                . " INNER JOIN {user} u ON u.id=rs.userid"
                . " INNER JOIN {context} e ON rs.contextid=e.id"
                . " INNER JOIN {course} c ON c.id=e.instanceid"
                . " INNER JOIN {role} r ON r.id=rs.roleid"
                . " WHERE e.contextlevel=50 AND r.archetype = 'student' AND c.id= :courseid";
        $params['courseid'] = $courseid;
        $busca = $DB->get_record_sql($sql, $params);
        return $busca->total;
    }
    return false;
}

/**
 * Return users of course.
 *
 * @param int $courseid
 * @param int $groupid
 * @return mixed
 */
function block_grade_overview_get_students_course($courseid, $groupid = 0) {
    global $DB;
    if ($courseid > 1) {
        $wheregroup = "";
        if ($groupid > 0) {
            $wheregroup = " AND u.id IN(SELECT userid FROM {groups_members} WHERE groupid=:groupid) ";
            $params['groupid'] = $groupid;
        }
        $sql = "SELECT u.* FROM {role_assignments} rs"
                . " INNER JOIN {user} u ON u.id=rs.userid"
                . " INNER JOIN {context} e ON rs.contextid=e.id"
                . " INNER JOIN {course} c ON c.id=e.instanceid"
                . " INNER JOIN {role} r ON r.id=rs.roleid"
                . " WHERE e.contextlevel=50 AND r.archetype = 'student' AND c.id=:courseid " . $wheregroup
                . " ORDER BY u.firstname ";
        $params['courseid'] = $courseid;
        $rs = $DB->get_records_sql($sql, $params);
        return $rs;
    }
    return false;
}

/**
 * Return total students who have already accessed the course.
 *
 * @param int $courseid
 * @return int
 */
function block_grade_overview_count_students_accessed_course($courseid) {
    global $DB;
    if ($courseid > 1) {
        $sql = "SELECT count(*) total FROM {role_assignments} rs"
                . " INNER JOIN {user} u ON u.id=rs.userid"
                . " INNER JOIN {context} e ON rs.contextid=e.id"
                . " INNER JOIN {course} c ON c.id=e.instanceid"
                . " INNER JOIN {user_lastaccess} ul ON ul.userid=u.id AND ul.courseid =c.id"
                . " INNER JOIN {role} r ON r.id=rs.roleid"
                . " WHERE e.contextlevel=50 AND r.archetype = 'student' AND c.id=?";
        $busca = $DB->get_record_sql($sql, array($courseid));
        return $busca->total;
    }
    return false;
}

/**
 * Returns the activities in current course
 *
 * @param int $courseid ID of the course
 * @return array Activities with completion settings in the course
 * @throws coding_exception
 * @throws moodle_exception
 */
function block_grade_overview_get_course_activities($courseid) {
    $modinfo = get_fast_modinfo($courseid, -1);
    $sections = $modinfo->get_sections();
    $activities = array();
    $types = array();
    $ids = array();
    foreach ($modinfo->instances as $module => $instances) {
        $modulename = get_string('pluginname', $module);
        foreach ($instances as $cm) {
            if ($module != 'label') {
                if (!in_array($module, $types)) {
                    array_push($types, $module);
                }
                array_push($ids, $cm->id);
                $activities[] = array(
                    'type' => $module,
                    'modulename' => $modulename,
                    'id' => $cm->id,
                    'instance' => $cm->instance,
                    'name' => $cm->name,
                    'expected' => $cm->completionexpected,
                    'section' => $cm->sectionnum,
                    'position' => array_search($cm->id, $sections[$cm->sectionnum]),
                    'url' => method_exists($cm->url, 'out') ? $cm->url->out() : '',
                    'context' => $cm->context,
                    'icon' => $cm->get_icon_url(),
                    'available' => $cm->available,
                );
            }
        }
    }
    usort($activities, 'block_grade_overview_compare_activities');
    return array('activities' => $activities, 'types' => $types, 'ids' => $ids);
}

/**
 * Used to compare two activities/resources based on order on course page
 *
 * @param array $a array of event information
 * @param array $b array of event information
 * @return mixed <0, 0 or >0 depending on order of activities/resources on course page
 */
function block_grade_overview_compare_activities($a, $b) {
    if ($a['section'] != $b['section']) {
        return $a['section'] - $b['section'];
    } else {
        return $a['position'] - $b['position'];
    }
}

/**
 * Returns user course progress
 *
 * @param \stdClass $user
 * @param \stdClass $course
 * @return doudle
 */
function block_grade_overview_user_course_progress($user, $course) {
    global $USER;
    $percentage = 0;
    if (($USER->id !== $user->id) && !is_siteadmin($USER->id)) {
        return 0;
    }

    $completion = new \completion_info($course);

    // First, let's make sure completion is enabled.
    if ($completion->is_enabled()) {
        $percentage = \core_completion\progress::get_course_progress_percentage($course, $user->id);
        if (!is_null($percentage)) {
            $percentage = floor($percentage);
        }

        if (is_null($percentage)) {
            $percentage = 0;
        }
    }
    return $percentage;
}

/**
 * Returns html view student
 *
 * @param \stdClass $user
 * @param \stdClass $course
 * @param array $atvscheck
 * @param \stdClass $grade
 * @param boolean $showcheck
 * @param boolean $shownameuser
 * @return string
 */
function block_grade_overview_get_view_student($user, $course, $atvscheck, $grade, $showcheck, $shownameuser) {
    global $CFG;
    $outputhtml = '';
    $calc = 0;
    if (isset($grade->config->calc)) {
        $calc = $grade->config->calc;
    }
    $decimalplaces = 2;
    if (isset($grade->config->decimal_places)) {
        $decimalplaces = $grade->config->decimal_places;
    }
    $desription = "";
    if (isset($grade->config->desription)) {
        $desription = $grade->config->desription;
    }
    if ($shownameuser) {
        $outputhtml .= '<div class="w-100 text-right"><span class="text-muted">'
                . $user->firstname . ' ' . $user->lastname . '</span></div>';
    }
    $outputhtml .= '<table class="generaltable" id="notas">';
    $outputhtml .= '<tr class="">';
    $outputhtml .= '<td class="cell c0 small" style=""><i class="fa fa-bookmark fa-lg" aria-hidden="true"></i><br/>'
            . get_string('activity', 'block_grade_overview') . '</td>';
    $outputhtml .= '<td class="cell c1 lastcol text-right small" style="">'
            . '<i class="icon fa fa-table fa-fw " aria-hidden="true"></i><br/>'
            . get_string('grade', 'block_grade_overview') . '</td>';
    $outputhtml .= '</tr>';
    $count = 0;
    $sum = 0;
    $taller = 0;
    $decimal = 2;
    foreach ($atvscheck as $atv) {
        $gradeuser = block_grade_overview_get_user_mod_grade($user->id, $atv['instance'], $atv['type'], $course->id);
        $imgcheck = '';
        if ($showcheck) {
            $imgcheck = '<img title="' . get_string('grade_pending', 'block_grade_overview') . '" src="'
                    . $CFG->wwwroot . '/blocks/grade_overview/pix/nocheck.png"/>';
            if (isset($gradeuser) && $gradeuser) {
                $imgcheck = '<img title="' . get_string('grade_awarded', 'block_grade_overview') . '" src="'
                        . $CFG->wwwroot . '/blocks/grade_overview/pix/check.png"/>';
            }
        }
        $outputhtml .= '<tr class="">';
        $outputhtml .= '<td class="cell c0" style="">' . $imgcheck . ' <a href="' . $atv['url'] . '">'
                . $atv['name'] . '</a></td>';
        if (isset($gradeuser) && $gradeuser) {
            if (isset($decimalplaces)) {
                $decimal = $decimalplaces;
            }
            $outputhtml .= '<td class="cell c1 lastcol text-right" style="">'
                    . number_format($gradeuser, $decimal, '.', '') . '</td>';
            $sum += $gradeuser;
            if ($gradeuser > $taller) {
                $taller = $gradeuser;
            }
            $count++;
        } else {
            $outputhtml .= '<td class="cell c1 lastcol text-right" style=""> - </td>';
        }
        $outputhtml .= '</tr>';
    }
    $outputhtml .= '</table>';
    $outputhtml .= '<div class="w-100 text-right">';
    if ($calc > 0 && $count > 0) {
        $final = 0;
        switch ($calc) {
            case 1:
                $final = $sum;
                break;
            case 2:
                $final = $sum / $count;
                break;
            case 3:
                $final = $taller;
                break;
        }
        $outputhtml .= '<b>' . get_string('final_grade', 'block_grade_overview');
        $outputhtml .= ': &nbsp;&nbsp;&nbsp;' . number_format($final, $decimal, '.', '') . '</b><br/>';
    }
    if (isset($desription)) {
        $outputhtml .= '<span class="text-muted">' . $desription . '</span>';
    }
    $outputhtml .= '</div>';

    return $outputhtml;
}

/**
 * Returns html view editor
 *
 * @param \stdClass $course
 * @param int $instanceid
 * @param array $atvscheck
 * @param boolean $showcheck
 * @param int $grade
 * @return string
 */
function block_grade_overview_get_view_editor($course, $instanceid, $atvscheck, $showcheck, $grade) {
    global $CFG, $USER;

    $showreportcompletion = false;
    if (isset($grade->config->show_report_completion)) {
        $showreportcompletion = $grade->config->show_report_completion;
    }
    $showreportgrade = false;
    if (isset($grade->config->show_report_grade)) {
        $showreportgrade = $grade->config->show_report_grade;
    }

    $outputhtml = '<table class="generaltable" id="notas">';
    $outputhtml .= '<tr class="">';
    $outputhtml .= '<td class="cell c0 small" style="">'
            . '<i class="fa fa-bookmark fa-lg" aria-hidden="true"></i><br/>'
            . get_string('activity', 'block_grade_overview') . '</td>';
    $outputhtml .= '<td class="cell c1 lastcol text-right small" style="">'
            . '<i class="icon fa fa-user fa-fw " aria-hidden="true"></i><br/>'
            . get_string('students', 'block_grade_overview') . '</td>';
    $outputhtml .= '</tr>';
    $totalstundents = block_grade_overview_count_students_course($course->id);
    foreach ($atvscheck as $atv) {
        $totalatv = block_grade_overview_get_count_atv_all($course->id, $atv['instance'], $atv['type']);
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
    $totalaccess = block_grade_overview_count_students_accessed_course($course->id);
    $outputhtml .= '<tr class="">';
    $outputhtml .= '<td class="cell c0 " style=""><strong>'
            . '<i class="icon fa fa-user fa-lg" aria-hidden="true"></i>'
            . get_string('never_access', 'block_grade_overview') . '</strong></td>';
    $outputhtml .= '<td class="cell c1 lastcol text-right" style=""><strong>'
            . ($totalstundents - $totalaccess) . '</strong></td>';
    $outputhtml .= '</tr>';
    $outputhtml .= '</table>';

    // Search user group.
    $groupid = 0;
    if ($course->groupmode == 1 || $course->groupmode == 2) {
        $groups = \groups_get_all_groups($course->id, $USER->id);
        foreach ($groups as $group) {
            $groupid = $group->id;
        }
    }
    $linkgroup = '&group=' . $groupid;

    $outputhtml .= '<hr/><div class="w-100 text-right small">';
    if ($showreportcompletion) {
        $outputhtml .= '<a href="'
        . $CFG->wwwroot . '/blocks/grade_overview/view_completion.php?id='
        . $course->id . '&instanceid=' . $instanceid . $linkgroup
        . '"><i class="icon fa fa-check-square fa-lg " aria-hidden="true"></i>'
        . get_string('completion_view', 'block_grade_overview') . '</a>  ';

        if ($showreportgrade) {
            $outputhtml .= ' | ';
        }
    }
    if ($showreportgrade) {
        $outputhtml .= '<a href="'
            . $CFG->wwwroot . '/blocks/grade_overview/view.php?id='
            . $course->id . '&instanceid=' . $instanceid . $linkgroup
            . '"><i class="icon fa fa-table fa-lg " aria-hidden="true"></i>'
            . get_string('detailed_view', 'block_grade_overview') . '</a>';
    }
    $outputhtml .= '</div>';

    return $outputhtml;
}

/**
 * Utility method to get the grade for a user.
 * @param int $userid
 * @param int $instanceid
 * @param string $type
 * @param int $courseid
 * @return int
 */
function block_grade_overview_get_user_mod_grade($userid, $instanceid, $type, $courseid) {
    $gradebookgrades = \grade_get_grades($courseid, 'mod', $type, $instanceid, $userid);
    if (isset($gradebookgrades->items)) {
        $gradebookitem = array_shift($gradebookgrades->items);
        if (isset($gradebookitem->grades[$userid])) {
            $grade = $gradebookitem->grades[$userid];
            if (!isset($grade->grade)) {
                return false;
            }
            return $grade->grade;
        }
    }
    return false;
}

/**
 * Validates the font size that was entered by the user.
 *
 * @param string $userid the font size integer to validate.
 * @param string $courseid the font size integer to validate.
 * @param string $cmid the font size integer to validate.
 * @return true|false
 */
function block_grade_overview_is_completed_module($userid, $courseid, $cmid) {
    global $DB;
    $countok = $DB->get_record_sql("SELECT COUNT(c.id) AS total FROM {course_modules_completion} c"
            . " INNER JOIN {course_modules} m ON c.coursemoduleid = m.id WHERE c.userid="
            . $userid . " AND m.course=" . $courseid . " AND c.coursemoduleid=" . $cmid .
            " AND m.completion > 0 AND c.completionstate > 0 AND m.deletioninprogress = 0");

    if ($countok->total > 0) {
        return true;
    }
    return false;
}

/**
 * Validates module is visibled.
 *
 * @param string $courseid id course.
 * @param string $cmid id module.
 * @return true|false
 */
function block_grade_overview_is_visibled_module($courseid, $cmid) {
    global $DB;
    $sql = "SELECT COUNT(id) AS total FROM {course_modules} "
            . "WHERE course= :courseid AND id= :cmid AND completion > 0 AND deletioninprogress = 0";
    $params['courseid'] = $courseid;
    $params['cmid'] = $cmid;
    $countatv = $DB->get_record_sql($sql, $params);
    if ($countatv->total > 0) {
        return true;
    }
    return false;
}
