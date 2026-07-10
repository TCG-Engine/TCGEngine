<?php header('Content-Type: text/plain');
require_once __DIR__ . '/../Cosmetics/Catalog.php';
$pass=0;$fail=0; function ok($x,$m){global $pass,$fail; if($x){$pass++;}else{$fail++;echo "FAIL: $m\n";}}

$html = SWUCosmeticSelectHtml('background', 'death-star');
ok(strpos($html, 'data-slot="background"') !== false, "select carries data-slot");
ok(strpos($html, 'class="cos-select"') !== false, "default class cos-select");
ok(strpos($html, '<option value="death-star" data-asset="/TCGEngine/Assets/Boards/SWUSim/death-star.webp" selected>Death Star</option>') !== false, "current option marked selected with asset URL");
ok(substr_count($html, ' selected>') === 1, "exactly one selected option");
ok(strpos($html, '<option value="default"') !== false && strpos($html, 'value="default" data-asset="/TCGEngine/Assets/Boards/SWUSim/default.webp"') !== false, "non-current option present, not selected");

$mat = SWUCosmeticSelectHtml('playmat', 'none', 'swu-gear-cos');
ok(strpos($mat, 'class="swu-gear-cos"') !== false, "custom class honored");
ok(strpos($mat, '<option value="none" data-asset="" selected>None</option>') !== false, "null-asset option -> empty data-asset");

ok(SWUCosmeticSelectHtml('bogus','x') === '<select class="cos-select" data-slot="bogus"></select>', "unknown slot -> empty option list");

echo "PASS=$pass FAIL=$fail\n";
