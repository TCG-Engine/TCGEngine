<?php
// SWUSim/GameSetupRules.php — the Arenabot card pool and the pool every game records.
// Spec: docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md §2.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/game_setup_rules_test.php
chdir(dirname(__DIR__, 3));
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Custom/DeckImport.php';
require_once './SWUSim/GameSetupRules.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ── Which pools Arenabot offers ─────────────────────────────────────────────────────────────────────
$pools = SWUArenabotPools();
foreach (['premier', 'eternal', 'padawan', 'open'] as $p) $check(in_array($p, $pools, true), "Arenabot offers '$p'");
$check(!in_array('twinsuns', $pools, true) && !in_array('botpractice', $pools, true), 'Arenabot offers no Twin Suns pool and never botpractice');

// ── Resolving a request's pool ──────────────────────────────────────────────────────────────────────
$check(SWUArenabotResolvePool(null) === 'open', 'absent → Open (an older client behaves as before)');
$check(SWUArenabotResolvePool(' Premier ') === 'premier', 'case and whitespace are normalised');
$check(SWUArenabotResolvePool('twinsuns') === null, 'a real format that is not an Arenabot pool is refused');
$check(SWUArenabotResolvePool('bogus') === null, 'an unknown pool is refused, never widened to Open');
$check(SWUArenabotResolvePool('') === null, 'an empty value is present-but-invalid, so it is refused');

// ── The pool a game records ─────────────────────────────────────────────────────────────────────────
$check(SWUCardPoolFor('premier', null) === 'premier', 'PvP Premier records premier');
$check(SWUCardPoolFor('botpractice', 'eternal') === 'eternal', 'Arenabot records its chosen pool');
$check(SWUCardPoolFor('botpractice', null) === 'open', 'Arenabot with no pool records open (in-process harness lobbies)');
$check(SWUCardPoolFor('botpractice', 'bogus') === 'open', 'Arenabot with an invalid pool records open');
$check(SWUCardPoolFor('goldfish', null) === 'open' && SWUCardPoolFor('HOTSEAT', null) === 'open', 'Goldfish and Hotseat record open');
$check(SWUCardPoolFor('teamsuns-preview', null) === 'teamsuns-preview', 'Team Suns Preview records its own format');

// ── The deck refusal (inline decks; legality verified 2026-11-02, re-legalised for the 2026-11-09 Premier bans) ──
// all-SOR, so Premier ✗ / Eternal ✓ / Open ✓
$sor = <<<'DECK'
Leader
1 SOR_009
Base
1 SOR_022
Deck
3 SOR_095
3 SOR_098
2 SOR_240
2 SOR_239
2 SOR_243
2 SOR_116
2 SOR_113
2 SOR_108
1 SOR_242
1 SOR_100
2 SOR_093
1 SOR_094
2 SOR_248
1 SOR_096
3 SOR_237
2 SOR_111
2 SOR_112
1 SOR_099
2 SOR_250
3 SOR_124
2 SOR_123
2 SOR_127
2 SOR_103
3 SOR_120
2 SOR_121
DECK;
// Ahsoka Tano (ASH) Blue, Premier ✓
$ahsoka = <<<'DECK'
Leader
1 ASH_009
Base
1 JTL_019
Deck
3 SEC_046
3 LOF_093
3 LAW_037
3 SEC_111
3 LAW_039
3 LAW_038
3 SEC_101
3 ASH_062
3 ASH_056
3 LOF_096
3 ASH_077
3 SEC_099
3 JTL_095
3 JTL_250
3 LAW_041
3 LAW_127
2 JTL_096
DECK;
// Director Krennic (LAW) Blue Splash, Premier ✓
$krennic = <<<'DECK'
Leader
1 LAW_008
Base
1 LAW_020
Deck
3 ASH_118
3 ASH_116
3 JTL_032
3 LAW_159
3 LAW_097
3 ASH_097
3 LOF_059
3 ASH_053
3 SEC_087
3 ASH_048
3 LAW_039
3 ASH_133
1 SEC_068
1 LAW_149
3 LAW_101
1 JTL_041
3 JTL_043
3 LAW_044
1 SEC_078
1 JTL_121
3 LOF_091
DECK;

$check(SWUArenabotDeckRefusal('premier', $ahsoka, '') === null, 'Premier: a legal deck and no bot deck (the bot mirrors it) → allowed');
$check(SWUArenabotDeckRefusal('premier', $ahsoka, $krennic) === null, 'Premier: two legal decks → allowed');
$r = SWUArenabotDeckRefusal('premier', $sor, '');
$check(is_string($r) && str_starts_with($r, 'Your deck is not legal in Premier:') && str_contains($r, 'Choose the Open card pool'), 'Premier: an illegal player deck is refused, naming the player and pointing to Open; got ' . var_export($r, true));
$r = SWUArenabotDeckRefusal('premier', $ahsoka, $sor);
$check(is_string($r) && str_starts_with($r, "The bot's deck is not legal in Premier:"), 'Premier: an illegal bot deck is refused, naming the bot; got ' . var_export($r, true));
$check(SWUArenabotDeckRefusal('eternal', $sor, $sor) === null, 'Eternal: SOR decks are legal → allowed');
$check(SWUArenabotDeckRefusal('open', $sor, $sor) === null, 'Open: anything goes, as Bot Practice did before');
$r = SWUArenabotDeckRefusal('premier', '', '');
$check(is_string($r) && str_starts_with($r, 'Your deck is required'), 'Premier: no player deck at all is refused; got ' . var_export($r, true));

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
