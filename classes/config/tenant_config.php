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

/**
 * Tenant-aware settings storage for local_assign_ai.
 *
 * @package     local_assign_ai
 * @copyright   2025 Datacurso
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant_config {
    /** @var string Database table name. */
    private const TABLE = 'local_assign_ai_tenant_cfg';

    /**
     * Stores one tenant-specific setting value.
     *
     * @param string $plugin Plugin component name.
     * @param int $tenantid Tenant identifier.
     * @param string $name Setting name.
     * @param string $value Setting value.
     * @return void
     */
    public static function set(string $plugin, int $tenantid, string $name, string $value): void {
        global $DB;

        $table = new \xmldb_table(self::TABLE);
        if (!$DB->get_manager()->table_exists($table)) {
            return;
        }

        $conditions = [
            'plugin' => $plugin,
            'tenantid' => $tenantid,
            'name' => $name,
        ];

        $existing = $DB->get_record(self::TABLE, $conditions, 'id', IGNORE_MISSING);
        if ($existing) {
            $existing->value = $value;
            $DB->update_record(self::TABLE, $existing);
            return;
        }

        $record = (object)$conditions;
        $record->value = $value;
        $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Fetches one tenant-specific setting value with fallback.
     *
     * @param string $plugin Plugin component name.
     * @param int $tenantid Tenant identifier.
     * @param string $name Setting name.
     * @param mixed $default Fallback value.
     * @return mixed
     */
    public static function get(string $plugin, int $tenantid, string $name, $default = null) {
        global $DB;

        $table = new \xmldb_table(self::TABLE);
        if ($DB->get_manager()->table_exists($table)) {
            $value = $DB->get_field(self::TABLE, 'value', [
                'plugin' => $plugin,
                'tenantid' => $tenantid,
                'name' => $name,
            ]);

            if ($value !== false) {
                return $value;
            }
        }

        $global = get_config($plugin, $name);
        if ($global !== false && $global !== null && $global !== '') {
            return $global;
        }

        return $default;
    }
}
