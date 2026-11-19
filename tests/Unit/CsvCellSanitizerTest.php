<?php

use App\Support\CsvCellSanitizer;

it('marks formula-leading strings as literal text for spreadsheet imports', function (string $value) {
    expect(CsvCellSanitizer::sanitize($value))->toBe("'".$value);
})->with([
    '=HYPERLINK("https://example.test","open")',
    '+1+1',
    '-1+1',
    '@SUM(A1:A2)',
    "\t=1+1",
    "\r\n@SUM(A1:A2)",
    "\u{00A0}+1+1",
    "\u{FEFF}=1+1",
]);

it('preserves ordinary text, empty values, and numeric CSV cells', function () {
    expect(CsvCellSanitizer::sanitize('Candidate name'))->toBe('Candidate name')
        ->and(CsvCellSanitizer::sanitize(''))->toBe('')
        ->and(CsvCellSanitizer::sanitize(0))->toBe(0)
        ->and(CsvCellSanitizer::sanitize(-12.5))->toBe(-12.5)
        ->and(CsvCellSanitizer::sanitizeRow(['=1+1', 5]))->toBe(["'=1+1", 5]);
});
