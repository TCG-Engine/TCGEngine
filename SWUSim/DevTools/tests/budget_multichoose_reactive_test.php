<?php
// The weighted-budget multi-select: "defeat/exhaust any number of units with a combined <metric> of N or
// less" (ASH_053 Pre Vizsla → remaining HP, LOF_201 Qui-Gon Jinn's Lightsaber → cost, LOF_202 Mind Trick
// → power). One modal, a live remaining-budget counter, candidates greying out reactively as picks are
// made, one Confirm.
//
// Two halves, and BOTH have to be guarded here because neither is visible to the schema suite:
//   • the CLIENT parses the weights out of the "~BUDGET~<total>~<label>~<mzID>=<w>…" tooltip side channel
//     and recomputes what is selectable after every click. The schema harness never runs this code.
//     ⚠ There are TWO client paths and they are easy to confuse. UILibraries routes an MZMULTICHOOSE
//     whose specs include popup-zone cards to the Core/MZMultiChooseUI.js MODAL; a choice over ARENA
//     targets goes to the INLINE board-selection path instead (SelectionMode 'MZMULTI_INLINE'), which
//     highlights units in place and draws its own prompt bar. Every card in this family targets units,
//     so the inline path is the one players actually see — implementing only the modal half shipped a
//     prompt bar showing the raw "~BUDGET~…" text and a selection that happily went over budget.
//     Both paths are asserted below.
//   • the SERVER re-derives the pool and re-applies the budget when the answer lands. The harness feeds
//     an answer straight to the handler without consulting the offer or the cap, so a resolver that
//     trusts its input passes green while the real cap does not exist.
// The schema sections assert the emitted tooltip and the server-side drop; this file guards the client
// half and the wiring between them.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

// ── client ────────────────────────────────────────────────────────────────────────────────────────
$path = $root . '/Core/MZMultiChooseUI.js';
$src  = file_get_contents($path);
check($src !== false, 'Core/MZMultiChooseUI.js is readable');
// Assert CODE, not prose: the explanatory comments in this file name every token below, so a guard that
// matched the raw source would pass with the implementation deleted (the exact way the #971 art-path
// guard fooled itself).
$code = preg_replace('~//[^\n]*~', '', $src);

check(strpos($code, '~BUDGET~') !== false, 'the modal reads the ~BUDGET~ tooltip side channel');
check(preg_match('/function\s+parseBudget\s*\(/', $code) === 1, 'parseBudget() is defined');
check(preg_match('/function\s+budgetRemaining\s*\(/', $code) === 1, 'budgetRemaining() is defined');
check(preg_match('/function\s+weightOf\s*\(/', $code) === 1, 'weightOf() is defined');

// The reactive rule itself: an UNSELECTED candidate whose own weight exceeds what is left is blocked.
check(preg_match('/weightOf\(candidate\)\s*>\s*remaining/', $code) === 1,
      'refreshUI blocks candidates whose weight exceeds the remaining budget');
check(preg_match('/mzmulti-disabled\'\s*,\s*blocked/', $code) === 1,
      'the blocked flag drives the greyed-out class');
// …and clicking one must be inert, not merely grey.
check(preg_match('/weightOf\(candidate\)\s*>\s*budgetRemaining\(\)\)\s*return/', $code) === 1,
      'toggleSelection refuses a pick that would overshoot the remaining budget');
// Select All cannot respect a budget, so it must not be offered under one.
check(preg_match('/max\s*===\s*candidates\.length\s*&&\s*!budget/', $code) === 1,
      'Select All is suppressed when a budget is present');

// Behavioural check of the parser, run against the SHIPPED source rather than a copy of it.
$node = trim((string)@shell_exec('command -v node 2>/dev/null'));
if ($node !== '') {
    $start = strpos($src, 'function parseBudget(');
    $end   = strpos($src, 'function parseSpecs(');
    check($start !== false && $end !== false && $end > $start, 'parseBudget() is extractable');
    $fn = substr($src, $start, $end - $start);
    $probe = $fn . "\n" . <<<'JS'
const t = 'Defeat_any_number~BUDGET~6~HP~myGroundArena-0=3~theirGroundArena-1=2';
const r = parseBudget(t);
const plain = parseBudget('Just_a_tooltip');
const out = [
  r.tooltip === 'Defeat_any_number',
  r.budget && r.budget.total === 6,
  r.budget && r.budget.label === 'HP',
  r.budget && r.budget.weights['myGroundArena-0'] === 3,
  r.budget && r.budget.weights['theirGroundArena-1'] === 2,
  plain.budget === null,
  plain.tooltip === 'Just_a_tooltip',
];
console.log(out.every(Boolean) ? 'OK' : 'BAD ' + JSON.stringify(out));
JS;
    $tmp = tempnam(sys_get_temp_dir(), 'budgetprobe') . '.js';
    file_put_contents($tmp, $probe);
    $res = trim((string)shell_exec(escapeshellarg($node) . ' ' . escapeshellarg($tmp) . ' 2>&1'));
    @unlink($tmp);
    check($res === 'OK', "parseBudget round-trips total/label/weights and leaves a plain tooltip alone (got: {$res})");

    // …and the inline path's reactive rule, against the shipped helpers. Board is the reported case:
    // remaining HP 6 (own unit), 4, 2, 3 against a 6 budget.
    $s2 = strpos($uiSrc, 'function InlineMultiWeightOf(');
    $e2 = strpos($uiSrc, 'function UpdateInlineMultiChooseMessage(');
    check($s2 !== false && $e2 !== false && $e2 > $s2, 'the inline budget helpers are extractable');
    $probe2 = "var window = { SelectionMode: {} };\n" . substr($uiSrc, $s2, $e2 - $s2) . "\n" . <<<'JS'
const W = {'my-0':6,'their-0':4,'their-1':2,'their-2':3};
function setup(sel){ window.SelectionMode = { multiBudget:{total:6,label:'HP',weights:W}, multiSelected:sel.slice() }; }
function blocked(sel){ setup(sel); return Object.keys(W).filter(InlineMultiBlocked).sort().join(','); }
function rem(sel){ setup(sel); return InlineMultiBudgetRemaining(); }
setup(['their-0']);
const cases = [
  blocked([]) === '',                              // opening: everything fits the full 6
  rem([]) === 6,
  rem(['their-0']) === 2,                          // took the 4 …
  blocked(['their-0']) === 'my-0,their-2',         // … so the 3 and the 6 drop out, the 2 stays
  rem(['their-1']) === 4,                          // took the 2 …
  blocked(['their-1']) === 'my-0',                 // … so BOTH the 4 and the 3 are still reachable
  blocked(['their-1','their-2']) === 'my-0,their-0',  // then the 3 -> the 4 drops out
  blocked(['their-2']) === 'my-0,their-0',         // took the 3 first -> the 4 and the 6 drop out
  rem(['their-0','their-1']) === 0,                // 4+2 spends it exactly
  blocked(['their-0','their-1']) === 'my-0,their-2',
  InlineMultiBlocked('their-0') === false,         // a SELECTED card is never blocked (revisable)
];
window.SelectionMode = { multiBudget:null, multiSelected:[] };
cases.push(InlineMultiBlocked('their-0') === false);   // no budget -> the rule is completely inert
console.log(cases.every(Boolean) ? 'OK' : 'BAD ' + JSON.stringify(cases));
JS;
    $tmp2 = tempnam(sys_get_temp_dir(), 'inlineprobe') . '.js';
    file_put_contents($tmp2, $probe2);
    $res2 = trim((string)shell_exec(escapeshellarg($node) . ' ' . escapeshellarg($tmp2) . ' 2>&1'));
    @unlink($tmp2);
    check($res2 === 'OK', "the inline reactive rule matches all four reported scenarios (got: {$res2})");
} else {
    // The SWUSim container ships no node, so this block skips there; it does run on a dev host and in
    // any JS-capable environment. The parser's OUTPUT is separately pinned from the server side by the
    // schema sections' exact P1DECISIONTOOLTIP assertions, which fix the grammar this parses.
    echo "  skip: node not on PATH — parser behaviour not executed here\n";
}


// ── client: the INLINE board-selection path (what a unit-targeting card actually uses) ─────────────
$uiFiles = glob($root . '/Core/UILibraries20*.js');
check(count($uiFiles) === 1, 'exactly one UILibraries bundle');
$uiSrc  = file_get_contents($uiFiles[0]);
$uiCode = preg_replace('~//[^\n]*~', '', $uiSrc);   // assert CODE, not the comments that name every token

check(strpos($uiCode, 'ParseBudgetTooltip') !== false,
      'the inline path parses the tooltip through the shared ParseBudgetTooltip grammar');
check(strpos($code, 'window.ParseBudgetTooltip') !== false,
      'MZMultiChooseUI.js exports ParseBudgetTooltip so there is ONE parser, not two');
// The bug this caught: the raw side channel was rendered to the player as prompt text.
check(preg_match('/ParseBudgetTooltip\(rawTooltip\)/', $uiCode) === 1,
      'the side channel is split off the RAW tooltip, before the underscore-to-space pass');
check(preg_match('/function\s+InlineMultiBlocked\s*\(/', $uiCode) === 1, 'InlineMultiBlocked() is defined');
check(preg_match('/function\s+InlineMultiBudgetRemaining\s*\(/', $uiCode) === 1, 'InlineMultiBudgetRemaining() is defined');
check(preg_match('/function\s+InlineMultiWeightOf\s*\(/', $uiCode) === 1, 'InlineMultiWeightOf() is defined');

// Blocking must reach all THREE places, or a full render tick silently restores an unaffordable target.
check(substr_count($uiCode, 'InlineMultiBlocked(') >= 4,
      'InlineMultiBlocked is consulted by the click handler, the DOM pass and the render path');
check(preg_match('/InlineMultiBlocked\(cardId\)\)\s*return/', $uiCode) === 1,
      'the inline click handler refuses a pick that would overshoot the remaining budget');
check(preg_match('/InlineMultiBlocked\(mzid\)/', $uiCode) === 1,
      'ApplyInlineMultiSelectionDomState dims over-budget cards');
check(preg_match('/InlineMultiBlocked\(id\)/', $uiCode) === 1,
      'the RenderRows highlight path re-derives the over-budget state');
check(preg_match("/selectAllBtn\.style\.display\s*=\s*'none'/", $uiCode) === 1,
      'Select All is removed from the inline prompt bar under a budget');
check(preg_match('/multiBudget\s*:\s*null/', $uiCode) === 1
      && substr_count($uiCode, 'multiBudget: null') >= 2,
      'multiBudget is in BOTH the SelectionMode default and the ClearSelectionMode reset (no stale budget)');

// ── server ────────────────────────────────────────────────────────────────────────────────────────
$gl = preg_replace('~//[^\n]*~', '', file_get_contents($root . '/SWUSim/Custom/GameLogic.php'));
check(preg_match('/function\s+SWUQueueBudgetMultiChoose\s*\(/', $gl) === 1, 'SWUQueueBudgetMultiChoose() is defined');
check(preg_match('/function\s+SWUFilterBudgetAnswer\s*\(/', $gl) === 1, 'SWUFilterBudgetAnswer() is defined');
check(strpos($gl, "'~BUDGET~'") !== false, 'the queue helper emits the ~BUDGET~ side channel');
// The offer must drop anything that cannot fit even alone, or the client greys it out from the start.
check(preg_match('/intval\(\$w\)\s*<=\s*\$budget/', $gl) === 1,
      'the offer excludes candidates that do not fit the budget on their own');
// dontSkipOnPass: the resolver IS the effect here, and a sticky PASS would otherwise skip it (bug #972).
check(preg_match("/AddDecision\(\\\$player,\s*'CUSTOM',\s*\\\$handler,\s*\\\$block,\s*'',\s*1\)/", $gl) === 1,
      'the resolver is queued with dontSkipOnPass=1');

// Every caller must re-validate — a resolver that trusts its answer is the whole trap.
foreach ([
    'SWUSim/Custom/cards/ash/PreVizsla_StrongWilledRuler.php' => 'ASH_053 Pre Vizsla',
    'SWUSim/Custom/CardDQHandlers.php'                        => 'SWU_BUDGET_EXHAUST (LOF_201 / LOF_202)',
] as $rel => $label) {
    $c = preg_replace('~//[^\n]*~', '', file_get_contents($root . '/' . $rel));
    check(strpos($c, 'SWUQueueBudgetMultiChoose(') !== false, "{$label} offers via the shared budget helper");
    check(strpos($c, 'SWUFilterBudgetAnswer(') !== false, "{$label} re-validates the answer server-side");
}

// ASH_053 defeats a BATCH, and each defeat compacts its arena — the picks have to be pinned by UniqueID
// before anything is removed or the later mzIDs address whichever unit slid into the slot.
$pv = preg_replace('~//[^\n]*~', '', file_get_contents($root . '/SWUSim/Custom/cards/ash/PreVizsla_StrongWilledRuler.php'));
check(strpos($pv, 'SWUFindMzByUID(') !== false, 'ASH_053 re-resolves each pick by UniqueID before defeating it');
check(preg_match('/if\s*\(SWUDefeatUnit\(.*\)\)\s*\$count\+\+/', $pv) === 1,
      'ASH_053 counts tokens from defeats that actually happened, not from picks made');

echo "PASS\n";
