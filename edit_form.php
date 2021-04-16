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
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/blocks/grade_overview/lib.php');
require_login();

/**
 * Class config form definition.
 *
 * @package    block_grade_overview
 * @copyright  José Wilson <j.wilson.df@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_grade_overview_edit_form extends block_edit_form {

    /**
     * Block grade overview form definition
     *
     * @param mixed $mform
     * @return void
     */
    protected function specific_definition($mform) {
        global $COURSE, $OUTPUT;

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', get_string('config_title', 'block_grade_overview'));
        $mform->setDefault('config_title', '');
        $mform->setType('config_title', PARAM_TEXT);
        $mform->addHelpButton('config_title', 'config_title', 'block_grade_overview');

        // Control visibility name user.
        $mform->addElement('selectyesno', 'config_show_name_user', get_string('config_show_name_user', 'block_grade_overview'));
        $mform->setDefault('config_show_name_user', 1);
        $mform->addHelpButton('config_show_name_user', 'config_show_name_user', 'block_grade_overview');

        // Control visibility check activity.
        $mform->addElement('selectyesno', 'config_show_check', get_string('config_show_check', 'block_grade_overview'));
        $mform->setDefault('config_show_check', 1);
        $mform->addHelpButton('config_show_check', 'config_show_check', 'block_grade_overview');

        // Description text.
        $mform->addElement('text', 'config_desription', get_string('config_desription', 'block_grade_overview'));
        $mform->setDefault('config_desription', '');
        $mform->setType('config_desription', PARAM_TEXT);
        $mform->addHelpButton('config_desription', 'config_desription', 'block_grade_overview');

        $mform->addElement('html', '<hr/>');
        $mform->addElement('html', get_string('config_select_activitys', 'block_grade_overview'));
        // Control calculation.
        $calcoptions = array(
            0 => get_string('none', 'block_grade_overview'),
            1 => get_string('sum', 'block_grade_overview'),
            2 => get_string('med', 'block_grade_overview'),
            3 => get_string('taller', 'block_grade_overview'));
        $mform->addElement('select', 'config_calc', get_string('config_calc', 'block_grade_overview'), $calcoptions);
        $mform->setDefault('config_calc', 0);
        $mform->addHelpButton('config_calc', 'config_calc', 'block_grade_overview');

        // Options decimal places.
        $options = array(0 => 0, 1 => 1, 2 => 2);
        $mform->addElement('select', 'config_decimal_places',
                get_string('config_decimal_places', 'block_grade_overview'), $options);
        $mform->setDefault('config_decimal_places', 2);
        $mform->addHelpButton('config_decimal_places', 'config_decimal_places', 'block_grade_overview');

        /* Enable/Disable by activity or section */
        $coursedata = grade_overview_get_course_activities($COURSE->id);
        $activities = $coursedata['activities'];
        foreach ($activities as $activity) {
            $attributes = ['class' => 'iconlarge activityicon'];
            $icon = $OUTPUT->pix_icon('icon', $activity['modulename'], $activity['type'], $attributes);
            $activityoption = array();
            $activityoption[] = & $mform->createElement(
                            'advcheckbox', 'config_atv' . $activity['id'], '', null, null, array(0, $activity['id'])
            );
            $mform->addGroup(
                    $activityoption, 'config_activity_' . $activity['id'],
                    $icon . format_string($activity['name']), array(' '), false
            );
        }
    }

}
