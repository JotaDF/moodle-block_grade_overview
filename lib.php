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

/**
 * Return grade atv of user.
 *
 * @param int $courseid
 * @param int $userid
 * @param int $iteminstance
 * @param int $itemtype
 * @return mixed
 */
function get_grade_atcvity($courseid, $userid, $iteminstance, $itemtype) {
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
function get_count_atv_all($courseid, $iteminstance, $itemtype) {
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
function count_studens_course($courseid) {
    global $DB;
    if ($courseid > 1) {
        $sql = "SELECT count(*) total FROM {role_assignments} rs"
                . " INNER JOIN {user} u ON u.id=rs.userid"
                . " INNER JOIN {context} e ON rs.contextid=e.id"
                . " INNER JOIN {course} c ON c.id=e.instanceid"
                . " WHERE e.contextlevel=50 AND rs.roleid=5 AND c.id= :courseid";
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
 * @return mixed
 */
function get_studens_course($courseid) {
    global $DB;
    if ($courseid > 1) {
        $sql = "SELECT u.* FROM {role_assignments} rs"
                . " INNER JOIN {user} u ON u.id=rs.userid"
                . " INNER JOIN {context} e ON rs.contextid=e.id"
                . " INNER JOIN {course} c ON c.id=e.instanceid"
                . " WHERE e.contextlevel=50 AND rs.roleid=5 AND c.id=:courseid";
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
function count_studens_accessed_course($courseid) {
    global $DB;
    if ($courseid > 1) {
        $sql = "SELECT count(*) total FROM {role_assignments} rs"
                . " INNER JOIN {user} u ON u.id=rs.userid"
                . " INNER JOIN {context} e ON rs.contextid=e.id"
                . " INNER JOIN {course} c ON c.id=e.instanceid"
                . " INNER JOIN {user_lastaccess} ul ON ul.userid=u.id AND ul.courseid =c.id"
                . " WHERE e.contextlevel=50 AND rs.roleid=5 AND c.id=?";
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
function get_course_activities($courseid) {
    $modinfo = get_fast_modinfo($courseid, -1);
    $sections = $modinfo->get_sections();
    $activities = array();
    $types = array();
    $ids = array();
    foreach ($modinfo->instances as $module => $instances) {
        $modulename = get_string('pluginname', $module);
        foreach ($instances as $index => $cm) {
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
    usort($activities, 'compare_activities');
    return array('activities' => $activities, 'types' => $types, 'ids' => $ids);
}

/**
 * Used to compare two activities/resources based on order on course page
 *
 * @param array $a array of event information
 * @param array $b array of event information
 * @return mixed <0, 0 or >0 depending on order of activities/resources on course page
 */
function compare_activities($a, $b) {
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
function user_course_progress($user, $course) {
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
