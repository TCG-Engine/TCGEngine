<?php
// A card file must not call ActivateCard() directly.
//
// "One card's resolution plays another card" produced FIVE separate bugs in a single week, every one of
// them the same defect wearing a different card's name: the nested play finalises the action, the outer
// effect finalises it too, the turn swaps twice and the acting player gets a FREE EXTRA ACTION.
//
// The reason it kept recurring is not that it is subtle — it is that each fix was invented locally, so
// the next author had no single thing to copy. SWUNestedPlay() is now that thing, and this test is what
// makes it the path of least resistance: a new `ActivateCard(` in a card file fails here by default, and
// the failure message says what to use instead.
//
// ⚠ THIS TEST IS THE POINT, NOT THE HELPER. A helper only prevents repetition if the wrong path is hard
// to take, and an author writing a new card copies whatever the neighbouring card does. Without this,
// the 45th nested play gets hand-rolled again.
//
// ⚠ AND THE EXCEPTIONS ARE REAL — do not "fix" them by migrating. Two of them (Osha, Shien Flurry) were
// migrated wholesale in the sweep and BOTH broke: they already own their finalisation, so the helper
// suppressed it a second time and the action never completed. Shien Flurry surfaced that as a DAMAGE
// assertion, not a turn one, because a phase-scoped "prevent 2" then never expired. A blanket rule that
// ignores genuine variants creates bugs rather than preventing them.

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

// file => why a RAW ActivateCard is correct here. Adding a line to this list is a deliberate act:
// state the reason, and be sure it is one of these two shapes.
$ALLOW = [
    // (a) DELEGATORS — a leader/base Action whose whole action IS the play. ActivateCard's after-action
    //     is the action's ONLY one; suppressing it strands the turn instead of fixing anything.
    'jtl/AdmiralPiett_CommandingTheArmada.php' => 'leader Action delegates its whole action to the play',
    'jtl/LandoCalrissian_BuyingTime.php'       => 'leader Action delegates its whole action to the play',
    'jtl/MajorVonreg_RedBaron.php'             => 'leader Action delegates its whole action to the play',
    'sor/Chewbacca_WalkingCarpet.php'          => 'leader Action delegates its whole action to the play',
    'sor/EnergyConversionLab.php'              => 'base Epic Action delegates its whole action to the play',
];

$root = __DIR__ . '/../../Custom/cards';
$offenders = [];
$allowSeen = [];
foreach (glob($root . '/*/*.php') as $path) {
    $rel = implode('/', array_slice(explode('/', str_replace('\\', '/', $path)), -2));
    if (basename($path) === '_index.generated.php') continue;
    foreach (explode("\n", file_get_contents($path)) as $i => $line) {
        $t = ltrim($line);
        if ($t === '' || str_starts_with($t, '//') || str_starts_with($t, '*')) continue;   // comments don't count
        if (!preg_match('/(?<![A-Za-z_])ActivateCard\s*\(/', $line)) continue;
        // A call wrapped in SWUWithNestedActionFrame() is compliant: the frame is what stops the inner
        // after-action ending the OUTER action, and it is exactly what SWUNestedPlay does internally.
        // Cards that must set their own grant/waiver globals around the play (Osha's Villainy waiver,
        // Shien Flurry's Ambush + prevent-2) cannot use the helper's fixed signature, so this is their
        // supported form rather than a standing exemption.
        if (strpos($line, 'SWUWithNestedActionFrame') !== false) continue;
        if (isset($ALLOW[$rel])) { $allowSeen[$rel] = true; continue; }
        $offenders[] = "{$rel}:" . ($i + 1);
    }
}

check(empty($offenders),
    "no card file calls ActivateCard() directly — use SWUNestedPlay() (CardHelpers.php), which neutralises\n"
  . "        BOTH after-actions: the immediate one AND the deferred one a queued SWU_TRIGGER_RESUME fires\n"
  . "        when the played card arms an entry trigger (an opponent's HMW_171 Trap Field is the common\n"
  . "        way to reach it). If your site genuinely must not be guarded, add it to \$ALLOW in this file\n"
  . "        WITH A REASON.\n"
  . "        Offenders: " . implode(', ', $offenders));

// An allowlist that outlives its entries rots into permission nobody remembers granting.
$stale = array_diff(array_keys($ALLOW), array_keys($allowSeen));
check(empty($stale),
    'every $ALLOW entry still corresponds to a real raw call (stale: ' . implode(', ', $stale) . ')');

echo "PASS\n";
