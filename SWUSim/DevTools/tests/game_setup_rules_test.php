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

// ── The deck refusal (real fixture decks; legality verified 2026-09-16) ─────────────────────────────
$sor     = file_get_contents('./SWUSim/Tests/BotFixtures/premier_deck_a.txt');                     // Premier ✗, Eternal ✓
$ahsoka  = file_get_contents('./SWUSim/Tests/BotFixtures/meta-2026-09/ahsoka_blue.txt');     // Premier ✓
$krennic = file_get_contents('./SWUSim/Tests/BotFixtures/meta-2026-09/krennic_splash.txt'); // Premier ✓

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
