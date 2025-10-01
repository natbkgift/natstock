<?php

namespace App\Support;

use DateTimeInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, string>>  $rows
     * @param  array<int, string>|null  $footer
     */
    public static function download(string $filename, array $headers, iterable $rows, ?array $footer = null)
    {
        $callback = function () use ($headers, $rows, $footer): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                throw new RuntimeException('ไม่สามารถเปิดสตรีมสำหรับเขียน CSV ได้');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_map([self::class, 'sanitize'], $headers));

            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'sanitize'], $row));
            }

            if ($footer !== null) {
                fputcsv($handle, array_map([self::class, 'sanitize'], $footer));
            }

            fclose($handle);
        };

        $response = new StreamedResponse($callback);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public static function sanitize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        if (is_float($value)) {
            $value = rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
        }

        $stringValue = (string) $value;
        $trimmed = ltrim($stringValue);


        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            // Excel safe: double single quote prefix
            return "''" . $stringValue;
        }

        return $stringValue;
    }
}