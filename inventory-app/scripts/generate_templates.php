<?php
$basePath = dirname(__DIR__);
$templatesPath = $basePath.'/public/templates';
if (!is_dir($templatesPath)) {
    mkdir($templatesPath, 0777, true);
}

$headers = [
    'sku',
    'name',
    'category',
    'qty',
    'cost_price',
    'sale_price',
    'expire_date',
    'reorder_point',
    'note',
    'is_active',
];

$sampleRow = [
    'P001',
    'ตัวอย่างสินค้า',
    'หมวดหมู่ตัวอย่าง',
    100,
    10,
    15,
    '2025-12-31',
    20,
    'หมายเหตุ',
    1,
];

if (!function_exists('columnLetter')) {
    function columnLetter(int $index): string
    {
        $index += 1;
        $letters = '';
        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }
}

$csvPath = $templatesPath.'/product_template.csv';
$csvHandle = fopen($csvPath, 'w');
fputcsv($csvHandle, $headers);
fputcsv($csvHandle, array_map(static function ($value) {
    return is_bool($value) ? ($value ? 1 : 0) : $value;
}, $sampleRow));
fclose($csvHandle);

$xlsxPath = $templatesPath.'/product_template.xlsx';
if (file_exists($xlsxPath)) {
    unlink($xlsxPath);
}

if (!class_exists('ZipArchive')) {
    throw new RuntimeException('ต้องเปิดใช้งานส่วนขยาย ZipArchive ของ PHP เพื่อสร้างไฟล์ XLSX');
}

$zip = new ZipArchive();
if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('ไม่สามารถสร้างไฟล์เทมเพลต XLSX ได้');
}

$contentTypes = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>
XML;
$zip->addFromString('[Content_Types].xml', $contentTypes);

$rels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
$zip->addFromString('_rels/.rels', $rels);

$workbookRels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>
XML;
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);

$workbook = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="ตัวอย่างสินค้า" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML;
$zip->addFromString('xl/workbook.xml', $workbook);

$styles = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1">
        <font>
            <sz val="11"/>
            <color theme="1"/>
            <name val="Calibri"/>
            <family val="2"/>
        </font>
    </fonts>
    <fills count="1">
        <fill>
            <patternFill patternType="none"/>
        </fill>
    </fills>
    <borders count="1">
        <border>
            <left/>
            <right/>
            <top/>
            <bottom/>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    </cellXfs>
    <cellStyles count="1">
        <cellStyle name="Normal" xfId="0" builtinId="0"/>
    </cellStyles>
</styleSheet>
XML;
$zip->addFromString('xl/styles.xml', $styles);

$sharedStrings = [];
$sharedStringsMap = [];
$registerSharedString = function (string $value) use (&$sharedStrings, &$sharedStringsMap): int {
    if (!array_key_exists($value, $sharedStringsMap)) {
        $sharedStringsMap[$value] = count($sharedStrings);
        $sharedStrings[] = $value;
    }

    return $sharedStringsMap[$value];
};

$headerCells = [];
foreach ($headers as $index => $header) {
    $column = columnLetter($index);
    $headerCells[] = sprintf('<c r="%s1" t="s"><v>%d</v></c>', $column, $registerSharedString($header));
}

$sampleCells = [];
foreach ($sampleRow as $index => $value) {
    $column = columnLetter($index);
    if (is_int($value) || is_float($value)) {
        $sampleCells[] = sprintf('<c r="%s2"><v>%s</v></c>', $column, $value);
    } else {
        $sampleCells[] = sprintf('<c r="%s2" t="s"><v>%d</v></c>', $column, $registerSharedString((string) $value));
    }
}

$sharedStringsItems = '';
foreach ($sharedStrings as $string) {
    $escaped = htmlspecialchars($string, ENT_QUOTES | ENT_XML1);
    $sharedStringsItems .= "    <si><t>{$escaped}</t></si>\n";
}

$sharedStringsXml = sprintf(
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="%1$d" uniqueCount="%1$d">\n%2$s</sst>',
    count($sharedStrings),
    rtrim($sharedStringsItems)
);
$zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);

$sheet = sprintf(
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">\n    <dimension ref="A1:%s2"/>\n    <sheetData>\n        <row r="1" spans="1:%d">%s</row>\n        <row r="2" spans="1:%d">%s</row>\n    </sheetData>\n</worksheet>',
    columnLetter(count($headers) - 1),
    count($headers),
    implode('', $headerCells),
    count($sampleRow),
    implode('', $sampleCells)
);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

$zip->close();

echo "สร้างไฟล์เทมเพลต CSV และ XLSX แล้ว\n";
