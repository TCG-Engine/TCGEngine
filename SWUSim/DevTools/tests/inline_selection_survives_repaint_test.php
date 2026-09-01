<?php
// AN IN-PROGRESS INLINE MULTI-SELECT MUST SURVIVE A REPAINT CAUSED BY THE OPPONENT.
//
// Reported live (2026-08-31): during the pregame "choose 2 cards to resource" step, a player who has
// marked their two cards but not yet confirmed loses both the moment the OPPONENT confirms theirs.
//
// The mechanism is not pregame-specific and not resource-specific — it is every inline multi-select:
//   • NextTurn.php's RenderUpdate() runs on every server update (i.e. whenever the opponent acts) and
//     calls ClearSelectionMode() unconditionally, before including NextTurnRender.php;
//   • ClearSelectionMode() resets `active` to false and `multiSelected` to [];
//   • NextTurnRender.php then re-runs CheckAndShowDecisionQueue(), whose `preserveExisting` guard —
//     written precisely to carry picks across a re-render — REQUIRES window.SelectionMode.active.
// So the guard is dead on the server-update path by construction: the clear always runs first. It only
// ever preserves across the prepare→finalize pair inside one render, which is why the whole thing looks
// wired and still loses every pick.
//
// The pregame is simply where it is most visible: both players act simultaneously there, so the
// opponent's confirm lands while you are still choosing. Mid-game it costs you an in-progress
// multi-pick every time the opponent does anything.
//
// This guard runs the SHIPPED functions under node rather than a copy of them: ClearSelectionMode and
// the MZMULTICHOOSE preserve block are extracted from Core/UILibraries*.js by string match, so the test
// fails if the fix is later reverted or refactored away.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

// ── the client behaviour, driven through the shipped source ────────────────────────────────────────
$uiPaths = glob($root . '/Core/UILibraries*.js');
check(!empty($uiPaths), 'a Core/UILibraries*.js exists');
$uiPath = $uiPaths[count($uiPaths) - 1];
$src = file_get_contents($uiPath);
check($src !== false, basename($uiPath) . ' is readable');

// ClearSelectionMode(), verbatim.
$csStart = strpos($src, 'function ClearSelectionMode() {');
check($csStart !== false, 'ClearSelectionMode() is extractable');
$csEnd = strpos($src, "\nfunction ", $csStart + 10);
check($csEnd !== false, 'ClearSelectionMode() has a following top-level function to bound it');
$clearFn = substr($src, $csStart, $csEnd - $csStart);

// The preserve block of the MZMULTICHOOSE inline branch, verbatim, up to and including the
// re-validation filter that drops picks the fresh render can disprove.
$pbStart = strpos($src, 'const preserveExisting =');
check($pbStart !== false, 'the MZMULTICHOOSE preserve block is extractable');
$pbEnd = strpos($src, '.slice(0, parsed.max);', $pbStart);
check($pbEnd !== false, 'the preserve block ends at the re-validation slice');
$preserveBlock = substr($src, $pbStart, ($pbEnd - $pbStart) + strlen('.slice(0, parsed.max);'));

// CaptureInlineSelectionForRepaint(), the fix's half of the contract. Extracted the same way so a
// stubbed stand-in cannot make this test pass.
$capStart = strpos($src, 'function CaptureInlineSelectionForRepaint(');
$captureFn = '';
if ($capStart !== false) {
    $capEnd = strpos($src, "\nfunction ", $capStart + 10);
    $captureFn = ($capEnd !== false) ? substr($src, $capStart, $capEnd - $capStart) : substr($src, $capStart);
}

$node = trim((string)@shell_exec('command -v node 2>/dev/null'));

if ($node !== '') {
$probe = <<<'JS'
globalThis.window = globalThis;
globalThis.document = { getElementById: () => null, querySelectorAll: () => [] };
const SM_DEFAULTS = () => ({
  active: false, mode: '', allowedZones: [], allowedDecisionZones: [], inlineSpecs: [],
  popupCards: [], zoneBindings: [], callback: null, decisionIndex: null, mayPass: false,
  multiMin: 0, multiMax: 0, multiSelected: [], multiBudget: null, entrySignature: null
});
window.SelectionMode = SM_DEFAULTS();
function HideMZChoosePopup() {}
JS;
$probe .= "\n" . $clearFn . "\n" . $captureFn . "\n";
$probe .= <<<'JS'

// The pregame step exactly as the server queues it: MZMULTICHOOSE "2|2|myHand" at block 40.
const ENTRY = { Type: 'MZMULTICHOOSE', Param: '2|2|myHand', Tooltip: 'Choose_2_cards_to_resource' };
// Six cards in hand, unchanged by anything the opponent does.
window.myHandData = ['a','b','c','d','e','f'].join('<|>');

// The shipped preserve block, with the locals CheckAndShowDecisionQueue would have bound.
function runInlineSetup(i) {
  const entry = ENTRY;
  const parsed = { min: 2, max: 2, specs: ['myHand'] };
  const categorized = { inlineSpecs: ['myHand'], popupCards: [] };
  const budgetParsed = { tooltip: entry.Tooltip, budget: null };
  const entrySignature = 'MZMULTICHOOSE|' + (entry.Param || '') + '|' + (entry.Tooltip || '');
JS;
$probe .= "\n" . $preserveBlock . "\n";
$probe .= <<<'JS'
  return window.SelectionMode.multiSelected;
}

// 1. The prompt appears; nothing picked yet.
runInlineSetup(5);
const afterOpen = window.SelectionMode.multiSelected.slice();

// 2. The player marks two cards (what InlineMultiSelect does on click).
window.SelectionMode.multiSelected = ['myHand-1', 'myHand-4'];

// 3. The OPPONENT confirms theirs. A server update arrives, so RenderUpdate() repaints: capture,
//    clear, then NextTurnRender re-runs CheckAndShowDecisionQueue for the SAME still-pending decision.
if (typeof CaptureInlineSelectionForRepaint === 'function') CaptureInlineSelectionForRepaint();
ClearSelectionMode();
const afterRepaint = runInlineSetup(5).slice();

// 4. A repaint whose update DID invalidate a pick must still drop it — the re-validation filter is the
//    safety net that makes carrying picks forward safe at all.
window.SelectionMode.multiSelected = ['myHand-1', 'myHand-4'];
if (typeof CaptureInlineSelectionForRepaint === 'function') CaptureInlineSelectionForRepaint();
ClearSelectionMode();
window.myHandData = ['a','b'].join('<|>');   // hand shrank; myHand-4 no longer exists
const afterShrink = runInlineSetup(5).slice();

// 5. A DIFFERENT decision must not inherit the picks.
window.myHandData = ['a','b','c','d','e','f'].join('<|>');
window.SelectionMode.multiSelected = ['myHand-1', 'myHand-4'];
if (typeof CaptureInlineSelectionForRepaint === 'function') CaptureInlineSelectionForRepaint();
ClearSelectionMode();
ENTRY.Param = '1|1|myHand';                   // a new, unrelated multi-select
const afterDifferent = runInlineSetup(5).slice();

console.log(JSON.stringify({ afterOpen, afterRepaint, afterShrink, afterDifferent }));
JS;

$tmp = tempnam(sys_get_temp_dir(), 'inlinesel') . '.js';
file_put_contents($tmp, $probe);
$out = trim((string)shell_exec(escapeshellarg($node) . ' ' . escapeshellarg($tmp) . ' 2>&1'));
@unlink($tmp);
$res = json_decode($out, true);
check(is_array($res), "the probe ran (got: {$out})");
check($captureFn !== '', 'the probe ran against the REAL CaptureInlineSelectionForRepaint, not a stub');

check($res['afterOpen'] === [], 'a freshly opened multi-select starts with nothing picked');
check($res['afterRepaint'] === ['myHand-1', 'myHand-4'],
      'BOTH picks survive the repaint caused by the opponent confirming (got: '
      . json_encode($res['afterRepaint']) . ')');
check($res['afterShrink'] === ['myHand-1'],
      'a pick the fresh render disproves is still dropped (got: ' . json_encode($res['afterShrink']) . ')');
check($res['afterDifferent'] === [],
      'a DIFFERENT decision does not inherit the carried picks (got: '
      . json_encode($res['afterDifferent']) . ')');
} else {
    echo "  ~~ node not present: the behavioural half is skipped, the structural contract below is not\n";
}

// ── the render path: a server update always repaints, and repainting clears the selection ──────────
$nt = file_get_contents($root . '/NextTurn.php');
check($nt !== false, 'NextTurn.php is readable');
$ntCode = preg_replace('~//[^\n]*~', '', $nt);
check(strpos($ntCode, 'ClearSelectionMode();') !== false,
      'RenderUpdate() still clears the selection on a repaint (the behaviour under test)');
// The capture must run BEFORE the clear, or there is nothing left to capture.
check(preg_match('/CaptureInlineSelectionForRepaint\(\);\s*\n\s*\}?\s*\n?\s*ClearSelectionMode\(\);/', $ntCode) === 1
      || preg_match('/CaptureInlineSelectionForRepaint\(\);[^}]*?ClearSelectionMode\(\);/s', $ntCode) === 1,
      'the repaint captures the in-progress inline selection BEFORE clearing it');


// ── the contract, asserted on the shipped source so a revert or refactor cannot pass silently ──────
$uiCode = preg_replace('~//[^\n]*~', '', $src);
check(strpos($uiCode, 'function CaptureInlineSelectionForRepaint(') !== false,
      'CaptureInlineSelectionForRepaint() is defined');
check(strpos($uiCode, 'window.CaptureInlineSelectionForRepaint = CaptureInlineSelectionForRepaint;') !== false,
      '…and exported on window, which is how NextTurn.php reaches it');
check(preg_match('/sm\.mode\s*!==\s*\x27MZMULTI_INLINE\x27/', $uiCode) === 1,
      'the capture is scoped to the inline multi-select, not to every selection mode');
check(strpos($uiCode, 'window.SelectionMode.entrySignature = entrySignature;') !== false,
      'the pending decision is stamped with a signature the carry can be matched against');
check(preg_match('/carry\.signature\s*===\s*entrySignature/', $uiCode) === 1,
      'a carried selection is adopted ONLY for a decision with the same signature');
check(preg_match('/window\.__inlineSelectionCarry\s*=\s*null;/', $uiCode) === 1,
      'the carry is one-shot — it cannot leak past a single repaint');
// The re-validation filter is what makes carrying safe; without it a stale mzID would be resubmitted.
check(strpos($uiCode, 'existingSelected.filter(') !== false,
      'carried picks still go through the re-validation filter');

echo "PASS: inline_selection_survives_repaint_test\n";
