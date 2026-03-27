<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_assign_ai\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Tenant-specific settings form for local_assign_ai.
 *
 * @package     local_assign_ai
 * @copyright   2025 Datacurso
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_tenant_form extends \moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('header', 'local_assign_ai_header', get_string('pluginname', 'local_assign_ai'));

        $mform->addElement(
            'advcheckbox',
            'enableassignai',
            get_string('enableassignai', 'local_assign_ai'),
            get_string('enableassignai_desc', 'local_assign_ai')
        );
        $mform->setType('enableassignai', PARAM_INT);

        $mform->addElement(
            'advcheckbox',
            'defaultenableai',
            get_string('defaultenableai', 'local_assign_ai'),
            get_string('defaultenableai_desc', 'local_assign_ai')
        );
        $mform->setType('defaultenableai', PARAM_INT);

        $mform->addElement(
            'advcheckbox',
            'defaultautograde',
            get_string('defaultautograde', 'local_assign_ai'),
            get_string('defaultautograde_desc', 'local_assign_ai')
        );
        $mform->setType('defaultautograde', PARAM_INT);

        $mform->addElement(
            'advcheckbox',
            'defaultusedelay',
            get_string('defaultusedelay', 'local_assign_ai'),
            get_string('defaultusedelay_desc', 'local_assign_ai')
        );
        $mform->setType('defaultusedelay', PARAM_INT);

        $mform->addElement(
            'text',
            'defaultdelayminutes',
            get_string('defaultdelayminutes', 'local_assign_ai')
        );
        $mform->setType('defaultdelayminutes', PARAM_INT);
        $mform->addHelpButton('defaultdelayminutes', 'defaultdelayminutes', 'local_assign_ai');
        $mform->addRule('defaultdelayminutes', null, 'numeric', null, 'client');

        $mform->addElement(
            'textarea',
            'defaultprompt',
            get_string('defaultprompt', 'local_assign_ai'),
            ['rows' => 5, 'cols' => 60]
        );
        $mform->setType('defaultprompt', PARAM_TEXT);
        $mform->addHelpButton('defaultprompt', 'defaultprompt', 'local_assign_ai');

        $mform->hideIf('defaultautograde', 'defaultenableai', 'eq', 0);
        $mform->hideIf('defaultusedelay', 'defaultenableai', 'eq', 0);
        $mform->hideIf('defaultdelayminutes', 'defaultenableai', 'eq', 0);
        $mform->hideIf('defaultprompt', 'defaultenableai', 'eq', 0);

        $mform->hideIf('defaultusedelay', 'defaultautograde', 'eq', 0);
        $mform->hideIf('defaultdelayminutes', 'defaultautograde', 'eq', 0);
        $mform->hideIf('defaultdelayminutes', 'defaultusedelay', 'eq', 0);

        $this->add_action_buttons();
    }
}
