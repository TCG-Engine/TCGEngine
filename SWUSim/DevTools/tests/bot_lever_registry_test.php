<?php
// ── The lever registry (Custom/BotLevers.php): a registered lever is a PROPOSAL — inert until its variant names it ─────────────────────
// Many levers hook the scorer through one registry, so the property that matters is the one the ASH meta baseline depends on: with no
// "@try-<lever>" / "@try-<theme>" in the variant, every hook returns its input untouched (a run stays byte-identical to the baseline).
//     php -d xdebug.mode=off SWUSim/DevTools/tests/bot_lever_registry_test.php
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }
chdir(realpath(__DIR__ . '/../../..'));
require_once './SWUSim/Custom/BotFeatures.php';

SWUBotLeverRegister('zztheme', 'zzlever', [
    'play' => fn(float $v, array $ctx, int $seat, string $cid, array $action, array $W) => $v + 10.0,
    'decision' => fn(array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W) => $c === 'YES' ? 7.0 : null,
]);
SWUBotLeverRegister('zztheme', 'zzlever2', ['play' => fn(float $v) => $v * 2.0]);

check(in_array('zzlever', SWUBotProposalList(), true), 'a registered lever is listed as a proposal');
check((SWUBotLeverGroups()['zztheme'] ?? []) === ['zzlever', 'zzlever2'], 'its theme groups the levers registered under it');

// The shipped levers (p54) are pinned off for this section: it drives the hooks with a dummy card, and only the test's own levers are under test.
$GLOBALS['SWUBotPinnedDisabled'] = SWU_BOT_PART54_FEATURES;
SWUBotSetDisabledFeatures([]);
check(SWUBotLeverChain('play', 1.0, [], 1, 'X', [], []) === 1.0, 'with no variant, a chain hook returns its input untouched');
check(SWUBotLeverFirst('decision', [], 1, 't', 'YESNO', 'h', 'YES', []) === null, 'with no variant, a first hook defers (null)');

SWUBotSetDisabledFeatures(SWUBotVariantDisabled('try-zzlever'));
check(SWUBotLeverChain('play', 1.0, [], 1, 'X', [], []) === 11.0, "'@try-zzlever' switches on that lever alone (got " . SWUBotLeverChain('play', 1.0, [], 1, 'X', [], []) . ')');
check(SWUBotLeverFirst('decision', [], 1, 't', 'YESNO', 'h', 'YES', []) === 7.0, 'a first hook returns the active lever\'s score');
check(SWUBotLeverFirst('decision', [], 1, 't', 'YESNO', 'h', 'NO', []) === null, '…and defers where the lever has no opinion');

SWUBotSetDisabledFeatures(SWUBotVariantDisabled('try-zztheme'));
check(SWUBotLeverChain('play', 1.0, [], 1, 'X', [], []) === 22.0, "'@try-zztheme' switches on every lever of the theme, chained in registration order (1+10, x2)");

SWUBotSetDisabledFeatures(SWUBotVariantDisabled('try-zzlever2'));
check(SWUBotLeverChain('play', 1.0, [], 1, 'X', [], []) === 2.0, 'a different lever of the same theme does not switch on its neighbour');
SWUBotSetDisabledFeatures([]);

unset($GLOBALS['SWUBotPinnedDisabled']);
// A SHIPPED lever (feature group p54) is a FEATURE: active with no variant, off under "@no-<name>", and no longer a proposal.
$shippedOn = fn(string $n) => array_key_exists($n, _SWUBotLeverActive('keep'));
check(in_array('wdpaybackkeep', SWUBotFeatureList(), true) && !in_array('wdpaybackkeep', SWUBotProposalList(), true),
    'a shipped lever is listed as a feature, not a proposal');
SWUBotSetDisabledFeatures([]);
check($shippedOn('wdpaybackkeep'), 'a shipped lever runs with no variant (default ON)');
SWUBotSetDisabledFeatures(SWUBotVariantDisabled('no-wdpaybackkeep'));
check(!$shippedOn('wdpaybackkeep'), "'@no-wdpaybackkeep' switches the shipped lever off");
SWUBotSetDisabledFeatures(SWUBotVariantDisabled('no-p54'));
check(!$shippedOn('wdpaybackkeep'), "'@no-p54' switches the whole shipped group off");
SWUBotSetDisabledFeatures([]);
echo "ALL PASS\n";
