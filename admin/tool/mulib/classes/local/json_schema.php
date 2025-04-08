<?php
// This file is part of Additional tools library for Moodle™.

namespace tool_mulib\local;

/**
 * JSON Schema validation related helper code.
 *
 * @package     tool_mulib
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class json_schema {
    public static function validate($data, $schema): array {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $validator = new \Opis\JsonSchema\Validator();
        try {
            $result = $validator->validate($data, $schema);
            $valid = $result->isValid();
            if ($valid) {
                $errors = [];
            } else {
                $error = $result->error();
                $formatter = new \Opis\JsonSchema\Errors\ErrorFormatter();
                $errors = $formatter->formatKeyed($error);
            }
        } catch (\Opis\JsonSchema\Exceptions\SchemaException $e) {
            $valid = false;
            $errors = [];
            $errors['/'][] = $e->getMessage();
        }
        return [$valid, $errors];
    }

    /**
     * Normalise objects and arrays for JSON processing.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function normalise_data($data) {
        require_once __DIR__ . '/../../vendor/autoload.php';

        return \Opis\JsonSchema\Helper::toJSON($data);
    }
}
