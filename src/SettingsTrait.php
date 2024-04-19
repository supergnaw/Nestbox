<?php

declare(strict_types=1);

namespace Supergnaw\Nestbox;

use Supergnaw\Nestbox\Exception\InvalidTableException;

trait SettingsTrait
{
    public const string nestbox_settings_table = 'nestbox_settings';

    protected function create_class_table_nestbox_settings(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS `nestbox_settings` (
                    `package_name` VARCHAR( 64 ) NOT NULL ,
                    `setting_name` VARCHAR( 64 ) NOT NULL ,
                    `setting_type` VARCHAR( 64 ) NOT NULL ,
                    `setting_value` VARCHAR( 128 ) NULL ,
                    PRIMARY KEY ( `setting_name` )
                ) ENGINE = InnoDB DEFAULT CHARSET=UTF8MB4 COLLATE=utf8mb4_general_ci;";

        return $this->query_execute($sql);
    }

    public function load_settings(): array
    {
        $where = ['package_name' => self::PACKAGE_NAME];

        try {
            $settings = $this->parse_settings($this->select(table: $this::nestbox_settings_table, where: $where));
        } catch (InvalidTableException) {
            var_dump("creating settings table", $this->create_class_table_nestbox_settings());
            $this->load_table_schema();
            $this->parse_settings($this->select(table: $this::nestbox_settings_table, where: $where));
        }


        foreach ($settings as $name => $value) {
            if (property_exists($this, $name)) {
                $this->update_setting($name, $value);
            }
        }

        return $settings;
    }

    public function update_setting(string $name, string $value): bool
    {
        if (!property_exists($this, $name)) return false;

        $this->$name = $value;

        return true;
    }

    public function save_settings(): void
    {
        $sql = "INSERT INTO `nestbox_settings` (
                    `package_name`, `setting_name`, `setting_type`, `setting_value`
                ) VALUES (
                    :package_name, :setting_name, :setting_type, :setting_value
                ) ON DUPLICATE KEY UPDATE
                    `package_name` = :package_name,
                    `setting_name` = :setting_name,
                    `setting_type` = :setting_type,
                    `setting_value` = :setting_value;";

        foreach (get_class_vars(get_class($this)) as $name => $value) {
            if (!str_starts_with($name, needle: self::PACKAGE_NAME)) {
                continue;
            }

            $params = [
                "package_name" => self::PACKAGE_NAME,
                "setting_name" => $name,
                "setting_type" => $this->parse_setting_type($value),
                "setting_value" => strval($value),
            ];

            $this->query_execute($sql, $params);
        }
    }

    protected function parse_settings(array $settings): array
    {
        $output = [];
        foreach ($settings as $setting) {
            $output[$setting['setting_name']] = $this->setting_type_conversion(type: $setting['setting_type'], value: $setting['setting_value']);
        }
        return $output;
    }

    protected function parse_setting_type(int|float|bool|array|string $setting): string
    {
        if (is_int($setting)) return "string";
        if (is_float($setting)) return "float";
        if (is_bool($setting)) return "boolean";
        if (is_array($setting)) return "array";
        if (json_validate($setting)) return "json";
        return "string";
    }

    protected function setting_type_conversion(string $type, string $value): int|float|bool|array|string
    {
        if ("int" == strtolower($type)) {
            return intval($value);
        }

        if (in_array(strtolower($type), ["double", "float"])) {
            return floatval($value);
        }

        if ("bool" == strtolower($type)) {
            return boolval($value);
        }

        if (in_array(strtolower($type), ["array", "json"])) {
            return json_decode($value, associative: true);
        }

        return $value;
    }
}