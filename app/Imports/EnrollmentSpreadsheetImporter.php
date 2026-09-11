<?php

namespace App\Imports;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class EnrollmentSpreadsheetImporter
{
    /** @return list<array<string, string|null>> */
    public function rows(string $path, string $extension): array
    {
        $rows = $extension === 'xlsx' ? $this->xlsxRows($path) : $this->csvRows($path);

        if (count($rows) < 2) {
            return [];
        }

        $headers = array_map(fn (?string $header): string => $this->normalizeHeader((string) $header), array_shift($rows));

        return array_values(array_filter(array_map(function (array $row) use ($headers): array {
            $row = array_pad($row, count($headers), null);

            return array_combine($headers, array_slice($row, 0, count($headers)));
        }, $rows), fn (array $row): bool => collect($row)->contains(fn (?string $value): bool => filled($value))));
    }

    /** @return list<list<string|null>> */
    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('No se pudo leer el archivo.');
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rows[] = array_map(fn (?string $value): ?string => $value === null ? null : trim($value), $row);
        }
        fclose($handle);

        return $rows;
    }

    /** @return list<list<string|null>> */
    private function xlsxRows(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('El archivo Excel no es válido.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml !== false) {
                foreach ($xml->si as $item) {
                    $sharedStrings[] = $this->xmlText($item);
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false || ($sheet = simplexml_load_string($sheetXml)) === false) {
            throw new RuntimeException('No se encontró la primera hoja del archivo Excel.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/^[A-Z]+/', $reference, $matches);
                $index = $this->columnIndex($matches[0] ?? 'A');
                $type = (string) $cell['t'];
                $value = $type === 'inlineStr' ? $this->xmlText($cell->is) : (string) $cell->v;
                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                }
                $values[$index] = trim($value);
            }
            $width = $values === [] ? 0 : max(array_keys($values)) + 1;
            $rows[] = array_values(array_replace(array_fill(0, $width, null), $values));
        }

        return $rows;
    }

    private function xmlText(SimpleXMLElement $element): string
    {
        if (isset($element->t)) {
            return (string) $element->t;
        }

        return collect($element->r)->map(fn (SimpleXMLElement $run): string => (string) $run->t)->implode('');
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        return str($header)->ascii()->lower()->replace([' ', '-'], '_')->trim('_')->toString();
    }
}
