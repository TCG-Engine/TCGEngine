<?php
/**
 * Cost-curve analysis — step 1: dump the SWU card pool to TSV.
 *
 * Reads the GENERATED card dictionaries (never hand-edited; regen with
 * `php zzCardCodeGenerator.php rootName=SWUSim`) and emits one row per CardID.
 *
 * Run inside the swusim container:
 *   docker exec otmtcge-swusim-web-server-1 php -d xdebug.mode=off \
 *     /var/www/html/TCGEngine/SWUSim/DevTools/cost-curve/dump-cards.php > cards.tsv
 */
$dict = __DIR__ . "/../../GeneratedCode/GeneratedCardDictionaries.php";
if (!file_exists($dict)) { fwrite(STDERR, "missing $dict — run zzCardCodeGenerator.php rootName=SWUSim\n"); exit(1); }
include $dict;

$cols = ["id","set","title","subtitle","type","arena","cost","power","hp","aspects","naspects","traits","unique","rarity","text"];
echo implode("\t", $cols) . "\n";
foreach ($titleData as $id => $title) {
  $aspects = $aspectData[$id] ?? "";
  echo implode("\t", [
    $id,
    explode("_", $id)[0],
    $title,
    $subtitleData[$id] ?? "",
    $typeData[$id] ?? "",
    $arenaData[$id] ?? "",
    isset($costData[$id])  ? $costData[$id]  : "",
    isset($powerData[$id]) ? $powerData[$id] : "",
    isset($hpData[$id])    ? $hpData[$id]    : "",
    $aspects,
    $aspects === "" ? 0 : count(explode(",", $aspects)),
    $traitData[$id] ?? "",
    empty($uniqueData[$id]) ? 0 : 1,
    $rarityData[$id] ?? "",
    // the runner splits ability clauses on " ~~ "
    str_replace(["\t", "\r", "\n"], [" ", " ", " ~~ "], (string)($textData[$id] ?? "")),
  ]) . "\n";
}
