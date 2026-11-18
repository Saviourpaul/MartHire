<?php

namespace App\Support;

final class CsvCellSanitizer
{
    /**
     * Keep values that spreadsheet applications could interpret as formulas
     * as literal text when a CSV is opened interactively.
     */
    public static function sanitize(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $formulaPrefix = preg_match('/\A[\p{Z}\p{C}]*[=+\-@]/u', $value);

        // Invalid UTF-8 cannot be checked by the Unicode pattern; still cover
        // the ASCII whitespace and control characters understood by Excel.
        if ($formulaPrefix === 1 || ($formulaPrefix === false && preg_match('/\A[\x00-\x20\x7f]*[=+\-@]/', $value) === 1)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    public static function sanitizeRow(array $row): array
    {
        return array_map(self::sanitize(...), $row);
    }
}
