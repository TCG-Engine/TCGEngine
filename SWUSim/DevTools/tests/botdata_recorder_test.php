<?php
// The BotData recorder: WHEN it writes, and — the part that matters — when it must NOT.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/botdata_recorder_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/Custom/BotDataSnapshot.php';
include_once './SWUSim/Custom/BotDataRecorder.php';

$rows = function () {
    $d = SWUBotDataDir();
    if ($d === '' || !is_file($d . '/states.jsonl')) return [];
    return array_values(array_filter(explode("\n", file_get_contents($d . '/states.jsonl')), fn($l) => trim($l) !== ''));
};
$wipe = function () {
    $d = SWUBotDataDir();
    if ($d !== '') { array_map('unlink', glob($d . '/*') ?: []); @rmdir($d); }
};

// ── A) A NON-botpractice game records NOTHING ────────────────────────────────────────────────
$build(function ($b) {
    $b->MyLeader('ASH_009'); $b->MyBase('ASH_019');
    $b->FillResourcesForPlayer(1, 'SOR_095', 3);
    $b->WithCardInHandForPlayer(1, 'ASH_248');
});
$check(SWUBotDataDir() === '', 'a normal game does not record; got ' . json_encode(SWUBotDataDir()));
$act(1, 10002, 'myHand-0!FSM!');
$check($rows() === [], 'and writes no rows');

// ── B) A botpractice game records ONE row per real action ────────────────────────────────────
// A REAL Arenabot game: seat 2 is the bot, seat 1 is the human. SetSWUBotPlayers matters — the
// recorder only captures games that actually have a human in them (see section A2).
$mkBot = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('ASH_009'); $b->MyBase('ASH_019');
        $b->TheirLeader('HMW_008'); $b->TheirBase('HMW_021');
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithGroundUnitForPlayer(1, 'LOF_093', false);
        $b->WithCardInHandForPlayer(1, 'LAW_037');   // myHand-0
        $b->WithCardInHandForPlayer(1, 'ASH_248');   // myHand-1
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_BOTPRACTICE');
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
    SetSWUBotPlayers([2]);
};
// ── A2) ⚠ SELF-PLAY RECORDS NOTHING — the corpus is HUMAN-vs-bot only ────────────────────────
// Owner ruling 2026-09-23: "BotData needs to be only human vs bot data." The self-play harness sets
// botPlayers = [1,2] (SWUSimBotSelfPlayTest.php:533) and runs in botpractice mode, so a mode check
// alone captures it too. Bot-vs-bot games are what the existing sweep tooling already covers, and a
// sweep run during a recording session would silently dilute the bundle the analysis reads.
$mkSelfPlay = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('ASH_009'); $b->MyBase('ASH_019');
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithCardInHandForPlayer(1, 'ASH_248');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_BOTPRACTICE');
    });
    SetSWUBotPlayers([1, 2]);                    // EVERY seat is a bot
};
$mkSelfPlay();
$check(SWUGameMode() === 'botpractice', 'fixture: self-play is still botpractice mode');
$check(GetSWUBotPlayers() === [1, 2], 'fixture: both seats are bots; got ' . json_encode(GetSWUBotPlayers()));
$check(SWUBotDataDir() === '', 'a BOT-VS-BOT game does not record; got ' . json_encode(SWUBotDataDir()));
$act(1, 10002, 'myHand-0!FSM!');
$check($rows() === [], 'and writes no rows');
SWUBotDataFinalize(1);
$check($rows() === [], 'nor does its finalize create anything');

$mkBot(); $wipe(); $mkBot();
$check(SWUGameMode() === 'botpractice', 'fixture: the game is botpractice');
$check(GetSWUBotPlayers() === [2], 'fixture: exactly one seat is a bot; got ' . json_encode(GetSWUBotPlayers()));
$check(SWUBotDataDir() !== '', 'botpractice records; dir=' . json_encode(SWUBotDataDir()));
$before = count($rows());
$act(1, 10002, 'myHand-1!FSM!');            // play Neel
$check(count($rows()) === $before + 1, 'one action -> one row; got ' . (count($rows()) - $before));
$all = $rows();
$row = json_decode($all[count($all) - 1], true);
$check(is_array($row) && ($row['seats']['1']['base'] ?? '') === 'ASH_019', 'the row is a full snapshot');
$check(($row['action']['kind'] ?? '') === 'play', 'the action kind is captured; got ' . json_encode($row['action'] ?? null));
// ⚠ Seat 1 is the HUMAN here (bots = [2]). The whole corpus exists to capture how a person plays, so
// an action by the human seat being labelled 'bot' would invert the one distinction that matters —
// and no test asserted it until now.
$check(($row['actor'] ?? '') === 'human', 'a seat-1 action is labelled HUMAN; got ' . json_encode($row['actor'] ?? null));
$check(intval($row['seat'] ?? 0) === 1, 'and carries the acting seat; got ' . json_encode($row['seat'] ?? null));

// ── C) ⚠ THE LOOKAHEAD RECORDS NOTHING ───────────────────────────────────────────────────────
// SWUBotLookahead dispatches REAL actions in memory and rolls them back; they reach the same
// SaveUndoVersion seam. Unguarded, every hypothetical lands in the corpus.
$mkBot(); $wipe(); $mkBot();
$n0 = count($rows());
$seen = SWUBotLookahead(1, ['mode' => 10002, 'cardID' => 'myHand-0!FSM!'], fn() => ['ok' => 1]);
$check($seen !== null, 'fixture: the lookahead applied its action');
$check(count($rows()) === $n0, 'a LOOKAHEAD writes no rows; got ' . (count($rows()) - $n0) . ' extra');
$check(!SWUBotDataSuppressed(), 'and the flag is back down afterwards');

// ── D) NESTED lookaheads: the flag is a DEPTH, not a boolean ─────────────────────────────────
// ⚠ The discriminating shape is an action dispatched in the OUTER lookahead AFTER an inner one has
// returned — which is exactly what _SWUBotLookaheadContinue does as it walks a line's answers. A
// reset-to-zero exit (the boolean bug) lowers the flag while the outer is still running, so that
// dispatch records. Without this third dispatch the section passes under the bug: verified by
// mutation, an earlier version of D that only nested two lookaheads stayed green.
$mkBot(); $wipe(); $mkBot();
$n1 = count($rows());
$inner = null;
SWUBotLookahead(1, ['mode' => 10002, 'cardID' => 'myHand-0!FSM!'], function () use (&$inner) {
    $inner = SWUBotLookahead(1, ['mode' => 10002, 'cardID' => 'myHand-1!FSM!'], fn() => ['ok' => 1]);
    // Still inside the OUTER lookahead: this must remain suppressed.
    _SWUBotLookaheadDispatch(1, ['mode' => 10001, 'cardID' => 'myLeader-0!CustomInput!LeaderAbility']);
    return ['ok' => 1];
});
$check($inner !== null, 'fixture: the inner lookahead applied');
$check(count($rows()) === $n1, 'NESTED lookaheads write no rows; got ' . (count($rows()) - $n1) . ' extra');
$check(!SWUBotDataSuppressed(), 'and the depth unwinds to zero');

// ── E) A real action AFTER a lookahead still records — suppression must not be sticky ────────
$mkBot(); $wipe(); $mkBot();
SWUBotLookahead(1, ['mode' => 10002, 'cardID' => 'myHand-0!FSM!'], fn() => ['ok' => 1]);
$n2 = count($rows());
$act(1, 10002, 'myHand-1!FSM!');
$check(count($rows()) === $n2 + 1, 'a real action after a lookahead DOES record; got ' . (count($rows()) - $n2));

// ── F) REVIEW FOCUS 1 — UNDO ─────────────────────────────────────────────────────────────────
// Bot Practice lets the human take an action back. The rows for an undone action describe something
// that did not happen; an unmarked corpus teaches the analysis a move the player rejected. We do not
// rewrite history (the attempt IS data — a human trying a line and retracting it is a signal) — we
// MARK it, and a reader that wants only the realised line drops back to the preceding marker.
$mkBot(); $wipe(); $mkBot();
$act(1, 10002, 'myHand-1!FSM!');
$beforeUndo = count($rows());
$check($beforeUndo >= 1, 'fixture: the action recorded');
$ok = LoadUndoSnapshot(UndoStackCount() - 1);
$check($ok !== false, 'fixture: the undo applied; got ' . json_encode($ok));
$allF = $rows();
$last = json_decode($allF[count($allF) - 1], true);
$check(count($allF) === $beforeUndo + 1, 'undo appends a marker row, it does not rewrite; got ' . count($allF));
$check(($last['action']['kind'] ?? '') === 'undo', 'the marker names itself; got ' . json_encode($last['action'] ?? null));

// ── F2) ACTOR for a seat that is neither ─────────────────────────────────────────────────────
// Found in the Task 4 hand check of a real recorded game: its FIRST row was seat 0 (the pre-game
// setup flow, before a seat owns the action) and came out labelled actor="human". Seat 0 is not a
// human, and in a live Arenabot game seat 1 IS the human — so "not a bot seat" must not silently
// mean "the person". Anything outside the real seats is 'system'.
$mkBot(); $wipe(); $mkBot();
$savedPid = $GLOBALS['playerID'];
$GLOBALS['playerID'] = 0;
SWUBotDataRecordAction();
$GLOBALS['playerID'] = $savedPid;
$allF2 = $rows();
$r0 = json_decode($allF2[count($allF2) - 1], true);
$check(($r0['actor'] ?? '') === 'system', 'a seat-0 action is actor=system, not human; got ' . json_encode($r0['actor'] ?? null));

// ── F3) BOOKMARK LOAD — undo's unhooked sibling (review finding #4) ──────────────────────────
// SWULoadBookmark rewinds the board exactly as undo does, and bookmarks ARE available in Arenabot
// (SWUIsSoloMode covers botpractice). Unmarked, states.jsonl gets a silent backwards jump a reader
// cannot detect. It also CLEARS GAMEOVER_WINNER so an ended game is live again — so a stale meta.json
// must not survive to block the second, real ending.
$mkBot(); $wipe(); $mkBot();
$act(1, 10002, 'myHand-1!FSM!');
SWUBotDataFinalize(2);
$dirBm = SWUBotDataDir();
$check(is_file($dirBm . '/meta.json'), 'fixture: the game finalized');
$nBm = count($rows());
SWUBotDataMarkRewound('bookmark');
$allBm = $rows();
$lastBm = json_decode($allBm[count($allBm) - 1], true);
$check(count($allBm) === $nBm + 1, 'a bookmark load appends a marker row; got ' . (count($allBm) - $nBm));
$check(($lastBm['action']['kind'] ?? '') === 'bookmark', 'the marker names itself; got ' . json_encode($lastBm['action'] ?? null));
$check(!is_file($dirBm . '/meta.json'), 'and the now-stale meta.json is cleared so the real ending can re-finalize');

// ── J) ATTACK TARGETS ────────────────────────────────────────────────────────────────────────
// The open-time snapshot is taken BEFORE the target is chosen, so an 'attack' row says only who
// swung. Analysing 5 real games, the one question the corpus could not answer was "is the bot hitting
// units when it should be hitting the base?" — and that is exactly the decision behind its flat ~3
// base damage per round. ExecuteSWUAttack is the single point where attacker AND resolved target are
// both known, so it emits a second row, kind 'attack-resolved', carrying the target.
$mkAtk = function ($withEnemyUnit) use ($build) {
    $build(function ($b) use ($withEnemyUnit) {
        $b->MyLeader('ASH_009'); $b->MyBase('ASH_019');
        $b->TheirLeader('HMW_008'); $b->TheirBase('HMW_021');
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithGroundUnitForPlayer(1, 'LOF_093', true);          // ready attacker
        if ($withEnemyUnit) $b->WithGroundUnitForPlayer(2, 'HMW_103', true);
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_BOTPRACTICE');
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
    SetSWUBotPlayers([2]);
};
// (a) attacking the BASE
$mkAtk(false); $wipe(); $mkAtk(false);
$act(1, 10002, 'myGroundArena-0!FSM!');
$resolved = array_values(array_filter(array_map(fn($l) => json_decode($l, true), $rows()),
    fn($r) => ($r['action']['kind'] ?? '') === 'attack-resolved'));
$check(count($resolved) === 1, 'an attack emits exactly one attack-resolved row; got ' . count($resolved));
$check(($resolved[0]['action']['target']['kind'] ?? '') === 'base',
    'a base attack records target kind base; got ' . json_encode($resolved[0]['action']['target'] ?? null));
$check(($resolved[0]['action']['card'] ?? '') === 'LOF_093',
    'and names the ATTACKER; got ' . json_encode($resolved[0]['action']['card'] ?? null));

// (b) attacking a UNIT — the target's CardID is what makes "did it trade or race?" answerable.
$mkAtk(true); $wipe(); $mkAtk(true);
$act(1, 10002, 'myGroundArena-0!FSM!');
// With an enemy unit on the board the attack raises a target prompt; declaring is not resolving.
$act(1, 100, 'theirGroundArena-0');
$rowsB = array_map(fn($l) => json_decode($l, true), $rows());
$resB = array_values(array_filter($rowsB, fn($r) => ($r['action']['kind'] ?? '') === 'attack-resolved'));
$check(count($resB) >= 1, 'a unit attack also emits attack-resolved; got ' . count($resB));
if ($resB) {
    $t = $resB[0]['action']['target'] ?? [];
    $check(($t['kind'] ?? '') === 'unit' && ($t['id'] ?? '') === 'HMW_103',
        'a unit attack records the DEFENDER card; got ' . json_encode($t));
}

// ── K) A FINAL STATE ROW CLOSES THE TRAJECTORY ───────────────────────────────────────────────
// Every row is a PRE-action snapshot, so the last action of a game — the killing blow — has no
// after-state and its effect is invisible. Finalize appends one.
$mkBot(); $wipe(); $mkBot();
$act(1, 10002, 'myHand-1!FSM!');
$beforeFinal = count($rows());
SWUBotDataFinalize(1);
$allK = $rows();
$lastK = json_decode($allK[count($allK) - 1], true);
$check(count($allK) === $beforeFinal + 1, 'finalize appends one row; got ' . (count($allK) - $beforeFinal));
$check(($lastK['action']['kind'] ?? '') === 'final', 'and it is marked final; got ' . json_encode($lastK['action'] ?? null));
$check(isset($lastK['seats']['1']['baseHp']), 'carrying the closing board');

// ── L) RESOURCE CARD IDS ─────────────────────────────────────────────────────────────────────
// Which card a seat resources is a real decision (feature 'resourcing3'), and the snapshot recorded
// only a COUNT — so the corpus could not show what was resourced. Card ids make the resource row a
// readable decision without recording any new event.
$mkBot(); $wipe(); $mkBot();
$act(1, 10002, 'myHand-1!FSM!');
$rowL = json_decode($rows()[count($rows()) - 1], true);
$check(is_array($rowL['seats']['1']['resCards'] ?? null), 'the seat snapshot lists its resource cards');
$check(count($rowL['seats']['1']['resCards']) === intval($rowL['seats']['1']['res']['total']),
    'one entry per resource; got ' . json_encode($rowL['seats']['1']['resCards'] ?? null)
    . ' vs total ' . json_encode($rowL['seats']['1']['res']['total'] ?? null));

// ── G) REVIEW FOCUS 4 — two concurrent games write to SEPARATE directories ───────────────────
$mkBot(); $wipe(); $mkBot();
$dirA = SWUBotDataDir();
$savedName = $GLOBALS['gameName'];
$GLOBALS['gameName'] = $savedName . 'b';
$dirB = SWUBotDataDir();
$GLOBALS['gameName'] = $savedName;
$check($dirA !== '' && $dirB !== '' && $dirA !== $dirB, 'each game gets its own dir; ' . json_encode([$dirA, $dirB]));
$check(basename($dirA) === preg_replace('/[^A-Za-z0-9_]/', '', $savedName), 'the dir is keyed by gameName');

// ── H) REVIEW FOCUS 3 — an unwritable target degrades to "no data", never to an exception ────
// The container runs as root, which ignores mode bits, so chmod cannot simulate this. Point the
// writer at a path that CANNOT be created instead: a directory segment under an existing FILE.
$mkBot(); $wipe(); $mkBot();
$savedName2 = $GLOBALS['gameName'];
$blocker = dirname(SWUBotDataDir()) . '/blockerfile';
@mkdir(dirname($blocker), 0777, true);
file_put_contents($blocker, 'not a directory');
$GLOBALS['gameName'] = 'blockerfile';
$check(SWUBotDataAppend('states', ['x' => 1]) === false, 'an uncreatable BotData dir reports failure');
$threw = false;
try { SWUBotDataRecordAction(); } catch (\Throwable $e) { $threw = true; }
$check(!$threw, 'and the recorder does not throw');
$GLOBALS['gameName'] = $savedName2;
$threw2 = false;
try { $act(1, 10002, 'myHand-1!FSM!'); } catch (\Throwable $e) { $threw2 = true; }
$check(!$threw2, 'a player action completes regardless');
$u = null;
foreach (SWUBotUnits(1) as $v) if ($v['cardID'] === 'ASH_248') $u = $v;
$check($u !== null, 'and the action itself still happened');
@unlink($blocker);

// ── H2) THE VALUE LOGGER IS GUARDED TOO (review finding #3) ──────────────────────────────────
// It is the one write this feature added on a live request with no @, no return check and no
// try/catch. This codebase has already been bitten by exactly that: CreateGame.php:29-38 documents a
// bare file_put_contents warning corrupting the JoinQueue JSON response. An unwritable BotData must
// degrade to "no value rows", not to warning text in the middle of a game's JSON.
$mkBot(); $wipe(); $mkBot();
$savedName3 = $GLOBALS['gameName'];
$blocker2 = dirname(SWUBotDataDir()) . '/blockerfile2';
@mkdir(dirname($blocker2), 0777, true);
file_put_contents($blocker2, 'not a directory');
$GLOBALS['gameName'] = 'blockerfile2';
putenv('SWU_VALUE_LOG');                                   // force the BotData path, not the env path
ob_start();
$threwV = false;
try { SWUValueLogPosition(['kind' => 'free-play', 'seat' => 1, 'style' => 'softaggro']); }
catch (\Throwable $e) { $threwV = true; }
$noise = ob_get_clean();
$GLOBALS['gameName'] = $savedName3;
$check(!$threwV, 'an unwritable BotData does not make the value logger throw');
$check($noise === '', 'and it emits NO warning text into the response; got ' . json_encode(substr($noise, 0, 120)));
@unlink($blocker2);

// ── I) SEAM COVERAGE — each action kind produces exactly one row, correctly classified ───────
// The seam's own header claims it is reached by play, attack, ability, deploy, take-initiative,
// smuggle and play-from-discard. This proves the three the fixture can reach; the rest are noted
// as unproven rather than left looking covered.
// ⚠ The attack case needs a READY attacker: $mkBot builds Gungi exhausted (WithGroundUnitForPlayer's
// third arg is $ready, and it defaults TRUE — passing false means exhausted). With an exhausted unit
// ActionMap refuses the attack and never reaches the seam, so the section silently measured 0 rows.
$mkReady = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('ASH_009'); $b->MyBase('ASH_019');
        $b->TheirLeader('HMW_008'); $b->TheirBase('HMW_021');
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->WithGroundUnitForPlayer(1, 'LOF_093', true);   // READY
        $b->WithCardInHandForPlayer(1, 'LAW_037');
        $b->WithCardInHandForPlayer(1, 'ASH_248');
        $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_BOTPRACTICE');
        $b->WithInitiativePlayerBeing(2);
        $b->WithInitiativeClaimed();
    });
    SetSWUBotPlayers([2]);
};
$kinds = [
    ['play',    10002, 'myHand-1!FSM!',                              $mkBot],
    ['attack',  10002, 'myGroundArena-0!FSM!',                       $mkReady],
    ['ability', 10001, 'myLeader-0!CustomInput!LeaderAbility',        $mkBot],
];
foreach ($kinds as [$want, $mode, $wire, $mk]) {
    $mk(); $wipe(); $mk();
    $act(1, $mode, $wire);
    // Count only OPEN rows: an attack also emits its own 'attack-resolved' row (section J), which is
    // an addition to the trajectory, not a duplicate of the open one.
    $open = array_values(array_filter(array_map(fn($l) => json_decode($l, true), $rows()),
        fn($r) => ($r['action']['kind'] ?? '') !== 'attack-resolved'));
    $check(count($open) === 1, "$want: exactly one open row; got " . count($open));
    if ($open) {
        $check(($open[0]['action']['kind'] ?? '') === $want,
            "$want: classified correctly; got " . json_encode($open[0]['action'] ?? null));
    }
}
// NOT PROVEN here: deploy, initiative, smuggle, play-from-discard — this fixture cannot reach them
// (no 6-resource leader deploy, initiative already claimed, no Smuggle card, empty discard).

$wipe();
bot_test_finish();
