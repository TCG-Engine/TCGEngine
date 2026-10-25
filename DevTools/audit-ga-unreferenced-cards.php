<?php
// Lists printed cards with effect text whose card ID appears nowhere in the Grand Archive engine code (Custom/*.php, GeneratedMacroCode.php,
// GeneratedKeywordCode.php, the root-level generated PHP). Such a card has no authored ability and no hard-coded handler: its text does nothing.
//   php DevTools/audit-ga-unreferenced-cards.php            print the list
//   php DevTools/audit-ga-unreferenced-cards.php --tsv      tab separated (id, name, type, text)
chdir(dirname(__DIR__));
require 'GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';
global $nameData;
$blob = '';
$files = array_merge(glob('GrandArchiveSim/Custom/*.php'), ['GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php', 'GrandArchiveSim/GeneratedCode/GeneratedKeywordCode.php'], glob('GrandArchiveSim/*.php'));
foreach ($files as $f) {
    if (is_file($f)) $blob .= file_get_contents($f);
}
$tsv = in_array('--tsv', $argv, true);
$n = 0;
foreach (array_keys($nameData) as $id) {
    if (strpos($blob, $id) !== false) continue;
    $t = trim(preg_replace('/\s+/', ' ', strip_tags(strval(CardEffect($id)))));
    if ($t === '') continue;
    echo $tsv ? implode("\t", [$id, CardName($id), CardType($id), $t]) : $id . ' ' . CardName($id) . ' | ' . CardType($id) . ' | ' . substr($t, 0, 120), "\n";
    ++$n;
}
fwrite(STDERR, "$n cards with text and no engine reference\n");
