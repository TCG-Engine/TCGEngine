<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_botpractice_mode.php
//
// Guards the Bot Practice format contract. READ-ONLY: pure config reads and a source scan.
header('Content-Type: text/plain');
require_once __DIR__ . '/../../AppCore/SWU/Formats.php';

$checks = [];
$ROOT = __DIR__ . '/../..';

$fmt = SWUGetFormat('botpractice');
$checks['botpractice format exists']        = is_array($fmt);
$checks['botpractice is localMode']         = !empty($fmt['localMode']);
$checks['botpractice is unrestricted']      = !empty($fmt['unrestricted']);
$checks['botpractice has a display name']   = is_string($fmt['displayName'] ?? null) && $fmt['displayName'] !== '';

// ── 'enabled' => FALSE ON PURPOSE, UNTIL PHASE 5 ─────────────────────────────
// This assertion is INVERTED relative to Phase 1's first draft, and deliberately so.
// SWUListFormats() returns only ENABLED formats, and SharedUI/Sites/SWUSim/MainMenu.php builds its
// format dropdown from it — so 'enabled' => true made "Bot Practice" selectable while MainMenu's
// applyFormatUI() `isMode` predicate was still `goldfish || hotseat`. Picking it hid the deck-2
// field and the Start button and offered Join Queue instead: a dead menu entry. Wiring that menu is
// Phase 5 (it needs a SWUSim/Tests/Visual/ case and a cross-browser pass).
//
// WHEN PHASE 5 LANDS: flip AppCore/SWU/Formats.php to 'enabled' => true and flip these two checks
// back, in the same change that wires MainMenu. Do not flip either one alone.
$checks['botpractice is DISABLED until Phase 5 wires MainMenu'] = ($fmt['enabled'] === false);
$checks['botpractice is hidden from the format menu']           = !array_key_exists('botpractice', SWUListFormats());

// Disabled must mean HIDDEN, never UNREACHABLE. Phase 1 is driven programmatically (the queue POST,
// DevTools/SWUSimBotSelfPlayTest.php), and every one of those paths resolves the format through
// SWUGetFormat() — which deliberately still answers for a disabled format (see Formats.php header).
// If this ever fails, the mode is dead code and disabling it was the wrong call.
$checks['botpractice still resolves while disabled'] = (SWUGetFormat('botpractice') !== null);
$checks['a disabled botpractice is still localMode'] = !empty(SWUGetFormat('botpractice')['localMode']);

// localMode is load-bearing: it keeps practice games out of meta stats.
$statsFormats = SWUStatsFormats();
$checks['botpractice is NOT stats-eligible'] = !in_array('botpractice', $statsFormats, true);
$checks['premier IS stats-eligible']         = in_array('premier', $statsFormats, true);

// JoinQueue must accept it for SWUSim (source scan — the predicate is inline, not a function).
// ⚠ Accepting the FORMAT is not the same as building a playable LOBBY, and the difference is what
// made every endpoint-created game dead on arrival for eight tasks: this scan passed the whole time
// while seat 2 fell through to goldfish's empty-sponge Player. The lobby itself is covered by
// DevTools/tdd-regression/test_swusim_botpractice_joinqueue_lobby.php, which POSTs the real endpoint
// and reads the seats out of the game it creates. Do not extend this scan to cover that — a source
// grep proves WRITTEN, not REACHABLE.
$jq = @file_get_contents("$ROOT/APIs/Lobbies/JoinQueue.php");
$checks['JoinQueue allows botpractice for SWUSim'] =
    is_string($jq) && preg_match("/rootName === 'SWUSim'[^\\n]*botpractice/", $jq) === 1;
// The local-mode branch must give seat 2 a REAL deck. Structural only; the e2e above is the proof.
$checks['JoinQueue has a botpractice second-player arm'] =
    is_string($jq) && strpos($jq, '$isBotPractice') !== false;

// ── Mode plumbing ────────────────────────────────────────────────────────────
// Source scans only: exercising SWUGameMode() needs a loaded gamestate, which the
// self-play harness (DevTools/SWUSimBotSelfPlayTest.php) covers end to end.
$cg = @file_get_contents("$ROOT/SWUSim/CreateGame.php");
$gl = @file_get_contents("$ROOT/SWUSim/Custom/GameLogic.php");

$checks['CreateGame recognises botpractice']  = is_string($cg) && strpos($cg, "'botpractice'") !== false;
$checks['CreateGame sets the mode flag']      = is_string($cg) && strpos($cg, 'SWU_MODE_BOTPRACTICE') !== false;
$checks['SWUGameMode returns botpractice']    = is_string($gl) && strpos($gl, "SWU_MODE_BOTPRACTICE") !== false;
$checks['bot seat helpers exist']             = is_string($gl)
    && strpos($gl, 'function SetSWUBotPlayers') !== false
    && strpos($gl, 'function GetSWUBotPlayers') !== false;

// REGRESSION GUARD (spec Section 1): seat 2 in a bot game is a REAL seat. Every goldfish gate
// must stay keyed to 'goldfish' exactly — never to a generic local-mode predicate — or bot seat 2
// silently inherits the passive sponge behaviour (no deck, no mulligan, unkillable base).
preg_match_all('/SWUGameMode\(\)\s*===\s*\x27([a-z]+)\x27/', $gl, $modeCmp);
$checks['no generic local-mode gate in GameLogic'] =
    empty(array_diff(array_unique($modeCmp[1] ?? []), ['goldfish', 'hotseat', 'botpractice']));
$checks['goldfish sponge gate is goldfish-only'] =
    is_string($gl) && strpos($gl, "SWUGameMode() === 'goldfish'") !== false;

// Undo consent: botpractice is a ONE-DECISION-MAKER mode. The bot cannot answer the undo-approval
// popup (it is driven by a SWU var read in GameLayoutShared.php, not by a DecisionQueue entry), so a
// consent request routed to seat 2 hangs the undo forever. Behavioural coverage lives in
// DevTools/tdd-regression/test_undo_solo_modes_no_consent.php; this is the cheap structural echo.
$checks['SWUIsSoloMode counts botpractice'] = is_string($gl)
    && preg_match("/function SWUIsSoloMode.{0,400}botpractice/s", $gl) === 1;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    exit(1);
}
echo "PASS (" . count($checks) . " checks)\n";
