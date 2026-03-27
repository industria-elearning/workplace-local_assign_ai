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

namespace local_assign_ai\config;

use assign;

/**
 * Assignment configuration helpers for local_assign_ai.
 *
 * @package     local_assign_ai
 * @copyright   2025 Datacurso
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_config {
    /** @var array Plugin setting defaults. */
    private const PLUGIN_DEFAULTS = [
        'enableassignai' => 1,
        'defaultenableai' => 1,
        'defaultautograde' => 0,
        'defaultusedelay' => 0,
        'defaultdelayminutes' => 60,
    ];

    /**
     * Returns the current tenant id for Workplace contexts.
     *
     * @return int
     */
    public static function get_current_tenant_id(): int {
        if (class_exists('\\tool_tenant\\tenancy')) {
            $tenantid = \tool_tenant\tenancy::get_tenant_id();
            if ($tenantid !== null) {
                return (int)$tenantid;
            }
        }

        return 0;
    }

    /**
     * Checks whether assign AI features are globally enabled.
     *
     * @return bool
     */
    public static function is_feature_enabled(): bool {
        $enabled = self::get_plugin_setting('enableassignai', self::PLUGIN_DEFAULTS['enableassignai']);
        return !empty($enabled);
    }

    /**
     * Returns one plugin setting, resolved per-tenant in Workplace.
     *
     * @param string $name Setting name.
     * @param mixed $default Default value.
     * @param int|null $tenantid Tenant id override.
     * @return mixed
     */
    public static function get_plugin_setting(string $name, $default = null, ?int $tenantid = null) {
        if ($tenantid === null) {
            $tenantid = self::get_current_tenant_id();
        }

        if (class_exists('\\tool_tenant\\tenancy')) {
            return tenant_config::get('local_assign_ai', $tenantid, $name, $default);
        }

        $value = get_config('local_assign_ai', $name);
        if ($value !== false && $value !== null && $value !== '') {
            return $value;
        }

        return $default;
    }

    /**
     * Retrieves cached configuration for a given assignment instance.
     *
     * @param int $assignmentid The assignment instance ID (from {assign}).
     * @param int|null $tenantid Tenant id override.
     * @return \stdClass|null
     */
    public static function get(int $assignmentid, ?int $tenantid = null): ?\stdClass {
        global $DB;

        static $cache = [];

        if (!$assignmentid) {
            return null;
        }

        if ($tenantid === null) {
            $tenantid = self::get_current_tenant_id();
        }

        $cachekey = $assignmentid . ':' . $tenantid;

        if (!array_key_exists($cachekey, $cache)) {
            $record = $DB->get_record('local_assign_ai_config', [
                'assignmentid' => $assignmentid,
                'tenantid' => $tenantid,
            ]);

            // Legacy fallback for rows created before tenant support.
            if (!$record) {
                $sql = "SELECT *
                          FROM {local_assign_ai_config}
                         WHERE assignmentid = :assignmentid
                           AND tenantid IS NULL";
                $record = $DB->get_record_sql($sql, ['assignmentid' => $assignmentid]);
            }

            $cache[$cachekey] = $record ?: null;
        }

        return $cache[$cachekey];
    }

    /**
     * Checks whether auto-grading is enabled for a given assignment.
     *
     * @param assign $assign The assignment instance.
     * @param int|null $tenantid Tenant id override.
     * @return bool
     */
    public static function is_autograde_enabled(assign $assign, ?int $tenantid = null): bool {
        if (!self::is_feature_enabled()) {
            return false;
        }

        $config = self::get_effective((int)$assign->get_instance()->id, $tenantid);
        return !empty($config->enableai) && !empty($config->autograde);
    }

    /**
     * Returns the effective configuration for an assignment, falling back to site defaults.
     *
     * @param int $assignmentid The assignment instance ID (from {assign}).
     * @param int|null $tenantid Tenant id override.
     * @return \stdClass
     */
    public static function get_effective(int $assignmentid, ?int $tenantid = null): \stdClass {
        $record = self::get($assignmentid, $tenantid);
        $config = self::get_default_values($tenantid);

        if (!$record) {
            return $config;
        }

        if (isset($record->enableai)) {
            $config->enableai = (int)$record->enableai;
        }
        if (isset($record->autograde)) {
            $config->autograde = (int)$record->autograde;
        }
        if (isset($record->usedelay)) {
            $config->usedelay = (int)$record->usedelay;
        }
        if (isset($record->delayminutes) && (int)$record->delayminutes > 0) {
            $config->delayminutes = (int)$record->delayminutes;
        }
        if (!empty($record->graderid)) {
            $config->graderid = (int)$record->graderid;
        }
        if (isset($record->prompt) && trim((string)$record->prompt) !== '') {
            $config->prompt = (string)$record->prompt;
        }
        if (isset($record->lang) && trim((string)$record->lang) !== '') {
            $config->lang = trim((string)$record->lang);
        }

        return $config;
    }

    /**
     * Returns tenant-aware default configuration values.
     *
     * @param int|null $tenantid Tenant id override.
     * @return \stdClass
     */
    public static function get_default_values(?int $tenantid = null): \stdClass {
        $rawdefaultenableai = self::get_plugin_setting('defaultenableai', self::PLUGIN_DEFAULTS['defaultenableai'], $tenantid);
        $rawdefaultautograde = self::get_plugin_setting('defaultautograde', self::PLUGIN_DEFAULTS['defaultautograde'], $tenantid);
        $rawdefaultusedelay = self::get_plugin_setting('defaultusedelay', self::PLUGIN_DEFAULTS['defaultusedelay'], $tenantid);
        $rawdefaultdelayminutes = self::get_plugin_setting(
            'defaultdelayminutes',
            self::PLUGIN_DEFAULTS['defaultdelayminutes'],
            $tenantid
        );
        $rawdefaultprompt = self::get_plugin_setting(
            'defaultprompt',
            get_string('promptdefaulttext', 'local_assign_ai'),
            $tenantid
        );
        $rawdefaultlang = get_config('core', 'lang');

        $defaultenableai = ($rawdefaultenableai === false || $rawdefaultenableai === '') ? 1 : (int)$rawdefaultenableai;
        $defaultautograde = ($rawdefaultautograde === false || $rawdefaultautograde === '') ? 0 : (int)$rawdefaultautograde;
        $defaultusedelay = ($rawdefaultusedelay === false || $rawdefaultusedelay === '') ? 0 : (int)$rawdefaultusedelay;
        $defaultdelayminutes = ($rawdefaultdelayminutes === false || $rawdefaultdelayminutes === '')
            ? 60
            : max(1, (int)$rawdefaultdelayminutes);
        $defaultprompt = ($rawdefaultprompt === false || trim((string)$rawdefaultprompt) === '')
            ? get_string('promptdefaulttext', 'local_assign_ai')
            : (string)$rawdefaultprompt;
        $defaultlang = ($rawdefaultlang === false || trim((string)$rawdefaultlang) === '')
            ? current_language()
            : trim((string)$rawdefaultlang);

        return (object) [
            'enableai' => $defaultenableai,
            'autograde' => $defaultautograde,
            'usedelay' => $defaultusedelay,
            'delayminutes' => $defaultdelayminutes,
            'graderid' => null,
            'prompt' => $defaultprompt,
            'lang' => $defaultlang,
        ];
    }
}
