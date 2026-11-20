<?php
// The SWUSim main menu's mode tiles: every stat line in a tile's foot ("No deck needed", "3 players in queue", …) is at
// most SWU_MENU_STAT_MAX_CHARS characters (owner rule, 2026-11-05 — it shares one line with "Enter →" at every width).
// Checks the fixed lines written in SharedUI/Sites/SWUSim/MainMenu.php and the live ones from SWUMenuStatLabel
// (SWUSim/Custom/MenuLobbyStats.php), at counts far past anything real.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/menu_stat_lines_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Custom/MenuLobbyStats.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
const SWU_MENU_STAT_MAX_CHARS = 24;
$fits = fn(string $s) => mb_strlen(html_entity_decode($s, ENT_QUOTES, 'UTF-8')) <= SWU_MENU_STAT_MAX_CHARS;

echo "FixedLines:\n";
$menu = strval(file_get_contents('./SharedUI/Sites/SWUSim/MainMenu.php'));
preg_match_all('~<span class="mode__stat"[^>]*>([^<]*)</span>~u', $menu, $m);
$fixed = array_values(array_filter(array_map('trim', $m[1]), fn($s) => $s !== ''));
$check(count($fixed) >= 4, 'found the fixed stat lines: ' . json_encode($fixed, JSON_UNESCAPED_UNICODE));
foreach ($fixed as $s) $check($fits($s), '"' . $s . '" is ' . mb_strlen($s) . ' characters (max ' . SWU_MENU_STAT_MAX_CHARS . ')');

echo "LiveLines:\n";
foreach ([0, 1, 2, 99, 9999, 99999] as $n) {
    foreach ([SWUMenuStatLabel('pvp', ['pvpPlayers' => $n]), SWUMenuStatLabel('multi', ['multiTables' => $n])] as $s) {
        $check($fits($s), '"' . $s . '" is ' . mb_strlen($s) . ' characters (max ' . SWU_MENU_STAT_MAX_CHARS . ')');
    }
}

// Each tile's TITLE ("Twin Suns", "Fun Formats", …) is at most SWU_MENU_TITLE_MAX_CHARS characters (owner rule, 2026-11-13:
// "Alternate Formats", 17, ran past the tile's right edge). Measured in Chromium, Firefox and WebKit from 390 to 1920 wide:
// the tightest widths (768, 1600, 1920) fit 14 characters of ordinary words, 10 of the widest letters (W, M), so 14 holds
// for real titles; DevTools/ui-harness/swusim-alternate-formats-xbrowser.mjs measures the rendered fit as well.
const SWU_MENU_TITLE_MAX_CHARS = 14;
echo "Titles:\n";
preg_match_all('~<h3 class="mode__title"[^>]*>([^<]*)</h3>~u', $menu, $mt);
$titles = array_values(array_filter(array_map(fn($s) => trim(html_entity_decode($s, ENT_QUOTES, 'UTF-8')), $mt[1]), fn($s) => $s !== ''));
$check(count($titles) >= 6, 'found the tile titles: ' . json_encode($titles, JSON_UNESCAPED_UNICODE));
foreach ($titles as $s) $check(mb_strlen($s) <= SWU_MENU_TITLE_MAX_CHARS, '"' . $s . '" is ' . mb_strlen($s) . ' characters (max ' . SWU_MENU_TITLE_MAX_CHARS . ')');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
