<?php
// Validate every bot fixture's MAIN DECK against its base's minimum deck size: 50, +10 on JTL_024 Data Vault,
// -5 on JTL_025 Thermal Oscillator (the only two bases that change it). A short deck means the melee list was
// short, or APIs/MeleeLinkToJson.php silently dropped a card whose name it could not match.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/check_fixture_sizes.php
chdir('/var/www/html/TCGEngine');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
const SWU_DECK_MIN = ['JTL_024' => 60, 'JTL_025' => 45];
$bad = 0; $n = 0;
foreach (glob('SWUSim/Tests/BotFixtures/*/*.txt') as $f) {
    $sec = ''; $base = ''; $cards = 0;
    foreach (file($f, FILE_IGNORE_NEW_LINES) as $l) {
        $l = trim($l);
        if ($l === '' || $l[0] === '#') continue;
        if ($l === 'Leader' || $l === 'Base' || $l === 'Deck') { $sec = $l; continue; }
        if (preg_match('/^(\d+)\s+(\S+)/', $l, $m)) {
            if ($sec === 'Base') $base = $m[2];
            elseif ($sec === 'Deck') $cards += intval($m[1]);
        }
    }
    $n++;
    $min = SWU_DECK_MIN[$base] ?? 50;
    if ($cards < $min) { $bad++; printf("SHORT %-58s %d cards, minimum %d (%s)\n", $f, $cards, $min, CardTitle($base)); }
}
printf("%d fixtures checked, %d short\n", $n, $bad);
exit($bad === 0 ? 0 : 1);
