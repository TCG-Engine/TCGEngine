<?php
// Standalone component contract for the actual shared parser. Only the metadata
// and text lookup are stubbed; native card scenarios validate full integration.
$sourcePath = $argv[1] ?? __DIR__ . '/../../FaBSim/Custom/GameLogic.php';
$casesPath = $argv[2] ?? __DIR__ . '/printed_keyword_cases.json';
$source = file_get_contents($sourcePath);
if ($source === false) throw new RuntimeException('Could not read parser source.');
$start = null;
$candidate = null;
$offset = 0;
$depth = 0;
$opened = false;
$body = null;
foreach (token_get_all($source) as $token) {
    $text = is_array($token) ? $token[1] : $token;
    if ($start === null && is_array($token) && $token[0] === T_FUNCTION) $candidate = $offset;
    if ($start === null && $candidate !== null && is_array($token) && $token[0] === T_STRING) {
        if ($text === 'FaBPrintedKeywordIsActive') $start = $candidate;
        $candidate = null;
    }
    if ($start !== null && !is_array($token)) {
        if ($token === '{') { ++$depth; $opened = true; }
        if ($token === '}' && $opened && --$depth === 0) {
            $body = substr($source, $start, $offset + strlen($text) - $start);
            break;
        }
    }
    $offset += strlen($text);
}
if ($body === null) throw new RuntimeException('Parser function was not found.');
function FaBHasKeyword(string $cardID, string $keyword): bool {
    return (bool)$GLOBALS['printedKeywordCase']['has_keyword'];
}
function CardFunctional_text_plain(string $cardID): string {
    return (string)$GLOBALS['printedKeywordCase']['text'];
}
eval($body);
$document = json_decode(file_get_contents($casesPath), true, 512, JSON_THROW_ON_ERROR);
$failures = [];
foreach ($document['cases'] as $case) {
    $GLOBALS['printedKeywordCase'] = $case;
    $actual = FaBPrintedKeywordIsActive($case['card_id'] ?? '__component__', $case['keyword']);
    if ($actual !== $case['expected']) $failures[] = ['name' => $case['name'], 'expected' => $case['expected'], 'actual' => $actual];
}
echo json_encode(['source_function_sha256' => hash('sha256', $body),
    'total' => count($document['cases']), 'passed' => count($document['cases']) - count($failures),
    'failures' => $failures, 'scope' => 'Actual extracted parser with synthetic metadata/text inputs; not native game validation.'], JSON_PRETTY_PRINT) . PHP_EOL;
exit($failures ? 1 : 0);
