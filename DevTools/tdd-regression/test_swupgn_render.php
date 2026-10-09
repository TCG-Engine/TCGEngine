<?php
// SWU-PGN reader: render() — the %%% STORY narrative (spec §16). Pure PHP, no engine.
//   php DevTools/tdd-regression/test_swupgn_render.php
error_reporting(E_ALL); ini_set('display_errors', 1);
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$text = swupgnFixture('minimal.swupgn');
$identity = fn(string $id) => $id;
$doc = fn(array $events, array $cards = []) => ['header' => [], 'story' => [], 'decks' => [], 'cards' => $cards, 'setup' => [], 'events' => swupgnEvents($events), 'annotations' => []];

$out = SwuPgnRender(SwuPgnParse($text), $identity);
swupgnCheck(str_contains($out, 'ROUND 1') && str_contains($out, '── action ──') && preg_match('/1\. Player 1 plays SOR#108/', $out) === 1, 'renders round and phase banners and numbered actions');
swupgnCheck(preg_match("/1\\. Player 1 attacks[^\\n]*\\n {7}↳ 4 damage to Player 2's base/u", $out) === 1, 'indents a consequence under its action');
$out = SwuPgnRender(SwuPgnParse($text));
swupgnCheck(str_contains($out, 'Player 1 plays Wampa to ground') && !str_contains($out, 'SOR#108'), 'uses the document\'s own CARDS index when no resolver is given');
swupgnCheck(str_contains($out, 'P1  base 30/30   hand 1   resources 2/2   deck 2   leader ready') && str_contains($out, 'initiative: Player 1'), 'shows the board from the round keyframe');

$out = SwuPgnRender($doc([
  ['seq' => 'R1.A.1a', 't' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 2, 'damageType' => 'combat', 'hp' => 28],
  ['seq' => 'R1.A.1b', 't' => 'OVERWHELM', 'p' => 1, 'tgt' => 'base@2', 'amt' => 3, 'hp' => 25],
  ['seq' => 'R1.A.2a', 't' => 'HEAL', 'tgt' => 'base@1', 'amt' => 1, 'hp' => 30],
]), $identity);
swupgnCheck(str_contains($out, "2 damage to Player 2's base — 28 HP left") && str_contains($out, "3 Overwhelm damage to Player 2's base — 25 HP left") && str_contains($out, "1 healed on Player 1's base — 30 HP left"), 'names the relevant base on DAMAGE/OVERWHELM/HEAL');

$names = ['LOF#164' => 'Wampa', 'LOF#215' => 'Ascension Cable', 'SOR#095' => 'Battlefield Marine', 'JTL#058' => 'Academy Graduate', 'LAW#253' => 'Alliance X-Wing'];
$nm = fn(string $id) => $names[$id] ?? $id;
$out = SwuPgnRender($doc([
  ['seq' => 'R1.A.1', 't' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'zone' => 'ground', 'target' => 'LOF#164', 'cost' => 2],
  ['seq' => 'R1.A.2', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'JTL#058', 'kind' => 'upgrade', 'target' => 'LAW#253'],
  ['seq' => 'R1.A.3', 't' => 'DEPLOY_LEADER', 'p' => 2, 'card' => 'SOR#095'],
]), $nm);
swupgnCheck(str_contains($out, 'Player 1 plays Ascension Cable on Wampa (cost 2)') && str_contains($out, 'Player 1 deploys Academy Graduate as a pilot on Alliance X-Wing') && str_contains($out, 'Player 2 deploys Battlefield Marine'), 'names the host of an upgrade and of a pilot leader');
$out = SwuPgnRender($doc([['seq' => 'R1.A.2b', 't' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#095', 'by' => 'LOF#164'], ['seq' => 'R1.A.3b', 't' => 'RESCUE', 'p' => 2, 'card' => 'SOR#095']]), $nm);
swupgnCheck(str_contains($out, 'Player 1 captures Battlefield Marine with Wampa') && str_contains($out, 'Player 2 rescues Battlefield Marine'), 'says who captured what with which unit');
$seat = fn(int $n, array $cards = []) => ['seat' => $n, 'baseHp' => 30, 'baseMaxHp' => 30, 'handSize' => 0, 'hand' => [], 'resourcesReady' => 0, 'resourcesExhausted' => 0, 'credits' => 0, 'hasForce' => false, 'discard' => [], 'cards' => $cards];
$board = SwuPgnRender($doc([['seq' => 'R2.start', 't' => 'ROUND_START', 'round' => 2, 'keyframe' => ['round' => 2, 'phase' => 'action', 'initiative' => 1, 'players' => [
  1 => $seat(1, [['id' => 'LOF#164', 'zone' => 'ground', 'damage' => 0, 'exhausted' => false, 'upgrades' => ['LOF#215'], 'shields' => 0, 'experience' => 0, 'statusTokens' => (object)[], 'captured' => ['SOR#095']]]),
  2 => $seat(2)]]]]), $nm);
swupgnCheck(str_contains($board, 'ground: Wampa [Ascension Cable, holds Battlefield Marine]'), 'shows an upgrade and a held card on the board');
swupgnCheck(trim(SwuPgnRender($doc([['seq' => 'R1.A.0a', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2], ['seq' => 'R1.G.1', 't' => 'READY_RESOURCES', 'p' => 1, 'amount' => 1]]), $nm)) === '', 'prints nothing for the resource counters');

$out = SwuPgnRender($doc([['seq' => 'R1.A.1', 't' => 'PASS', 'p' => 2], ['seq' => 'R1.A.1-undo', 't' => 'UNDO', 'at' => 'R1.A.1', 'by' => 1], ['seq' => 'R1.A.1', 't' => 'PASS', 'p' => 2]]), $identity);
swupgnCheck(str_contains($out, '·· Player 1 undid back to R1.A.1 ··') && preg_match('/\d+\.\s*·· Player 1/u', $out) === 0 && preg_match('/↳.*undid/u', $out) === 0, 'an UNDO is its own marker line, neither numbered nor indented');
swupgnCheck(str_contains(SwuPgnRender($doc([['seq' => 'R1.A.1-undo', 't' => 'UNDO', 'at' => 'R1.A.1']]), $identity), '·· A player undid back to R1.A.1 ··'), 'an UNDO without `by` uses a neutral phrase');

$out = SwuPgnRender($doc([['seq' => 'R1.A.1', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'X', 'kind' => 'action'], ['seq' => 'R1.A.1a', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'Y', 'kind' => 'triggered'], ['seq' => 'R1.A.2', 't' => 'ABILITY_ACTIVATE', 'p' => 2, 'card' => 'Z', 'kind' => 'epic']]), $identity);
swupgnCheck(str_contains($out, '  1. Player 1 uses X') && str_contains($out, '       ↳ Y uses an ability') && str_contains($out, "  2. Player 2 uses Z's Epic Action"), 'an action/epic ability is a numbered action; any other kind is a consequence');
$out = SwuPgnRender($doc([['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108:2', 'zone' => 'ground']], [['id' => 'SOR#108', 'name' => 'Wampa']]));
swupgnCheck(str_contains($out, 'Player 1 plays Wampa #2 to ground'), 'a copy suffix renders as #N after the indexed name');
$r = SwuPgnIndexResolver([['id' => 'SOR#108', 'name' => 'Wampa']]);
swupgnCheck($r('SOR#108:3') === 'Wampa' && $r('SOR#999') === 'SOR#999' && SwuPgnBaseId('TOKEN:advantage#5844562972:2') === 'TOKEN:advantage#5844562972', 'the index resolver strips the copy suffix and falls back to the id');

swupgnFinish();
