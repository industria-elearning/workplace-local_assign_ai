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

/**
 * Tenant settings page for local_assign_ai.
 *
 * @package     local_assign_ai
 * @copyright   2025 Datacurso
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

if (!class_exists('\\tool_tenant\\tenancy')) {
    throw new moodle_exception('error');
}

$tenantid = \local_assign_ai\config\assignment_config::get_current_tenant_id();

$url = new moodle_url('/local/assign_ai/admin/settings_tenant.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'local_assign_ai'));
$PAGE->set_heading(get_string('pluginname', 'local_assign_ai'));

$form = new \local_assign_ai\form\settings_tenant_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/category.php', ['category' => 'localplugins']));
}

if ($data = $form->get_data()) {
    $values = [
        'enableassignai' => empty($data->enableassignai) ? '0' : '1',
        'defaultenableai' => empty($data->defaultenableai) ? '0' : '1',
        'defaultautograde' => empty($data->defaultautograde) ? '0' : '1',
        'defaultusedelay' => empty($data->defaultusedelay) ? '0' : '1',
        'defaultdelayminutes' => (string)max(1, (int)$data->defaultdelayminutes),
        'defaultprompt' => trim((string)$data->defaultprompt),
    ];

    if ($values['defaultprompt'] === '') {
        $values['defaultprompt'] = get_string('promptdefaulttext', 'local_assign_ai');
    }

    foreach ($values as $name => $value) {
        \local_assign_ai\config\tenant_config::set('local_assign_ai', $tenantid, $name, $value);
    }

    redirect($url, get_string('changessaved'));
}

$defaults = \local_assign_ai\config\assignment_config::get_default_values($tenantid);
$enabled = \local_assign_ai\config\assignment_config::get_plugin_setting('enableassignai', 1, $tenantid);

$form->set_data((object)[
    'enableassignai' => (int)$enabled,
    'defaultenableai' => (int)$defaults->enableai,
    'defaultautograde' => (int)$defaults->autograde,
    'defaultusedelay' => (int)$defaults->usedelay,
    'defaultdelayminutes' => max(1, (int)$defaults->delayminutes),
    'defaultprompt' => (string)$defaults->prompt,
]);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_assign_ai'));
$form->display();
echo $OUTPUT->footer();
