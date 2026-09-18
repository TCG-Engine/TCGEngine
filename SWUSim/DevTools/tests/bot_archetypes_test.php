<?php
// The five ordered bot archetypes (SWUSim/Custom/BotArchetypes.php).
// Spec: docs/superpowers/specs/2026-09-17-swusim-bot-archetypes-design.md.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_archetypes_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/Custom/BotArchetypes.php';
require_once './SWUSim/Custom/BotFlavours.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// ── The ordered scale ───────────────────────────────────────────────────────────────────────────────
$check(SWU_BOT_ARCHETYPES === ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'],
    'the five archetypes, ordered aggro -> control');
foreach (['hyperaggro' => 0, 'softaggro' => 1, 'midrange' => 2, 'softcontrol' => 3, 'hardcontrol' => 4] as $s => $r) {
    $check(SWUBotStyleRank($s) === $r, "rank of $s is $r");
}
$check(SWUBotStyleRank('midrange') > SWUBotStyleRank('softaggro'), 'rank increases toward control');
foreach (SWU_BOT_ARCHETYPES as $s) $check(!str_contains($s, '-'), "id '$s' has no hyphen (heuristic-<style>@no-<feature> parsing)");

// ── The old three names keep working (botStyle is a POST field and is stored on saved lobbies) ─────
$check(SWUBotResolveStyle('aggro') === 'softaggro', "alias: aggro -> softaggro");
$check(SWUBotResolveStyle('normal') === 'midrange', "alias: normal -> midrange");
$check(SWUBotResolveStyle('control') === 'softcontrol', "alias: control -> softcontrol");
$check(SWUBotResolveStyle('HardControl') === 'hardcontrol' && SWUBotResolveStyle(' midrange ') === 'midrange',
    'style resolution is case- and whitespace-insensitive');
$check(SWUBotStyleRank('aggro') === 1 && SWUBotStyleRank('normal') === 2 && SWUBotStyleRank('control') === 3,
    'the aliases rank where their new names rank');
$check(SWUBotStyleRank('nonsense') === 2, 'an unknown style falls back to midrange (rank 2), never to an extreme');

// ── Display names (the Arenabot Play Style dropdown) ───────────────────────────────────────────────
$check(SWUBotStyleDisplay('hyperaggro') === 'Hyper Aggro' && SWUBotStyleDisplay('hardcontrol') === 'Hard Control',
    'display names are title-cased two-word labels');

// ── The weights the spec pins ──────────────────────────────────────────────────────────────────────
foreach (SWU_BOT_ARCHETYPES as $s) {
    $W = SWUBotWeights($s, 1);
    $check($W['base'] === 0.60, "$s: base is flat 0.60 (a point of base damage is 1/30th of a win for everyone)");
    $check($W['chip'] < $W['base'], "$s: chip ({$W['chip']}) is below base — the inverted ratio is the bug");
}
$rankOf = fn($s) => SWUBotStyleRank($s);
$kills = [];
foreach (SWU_BOT_ARCHETYPES as $s) $kills[$s] = SWUBotWeights($s, 1)['kill'];
$check($kills['hyperaggro'] < $kills['softaggro'] && $kills['softaggro'] < $kills['midrange']
    && $kills['midrange'] < $kills['softcontrol'] && $kills['softcontrol'] < $kills['hardcontrol'],
    'kill rises monotonically from hyper aggro to hard control');
$check(SWUBotWeights('softaggro', 1)['burn'] === 0.80
    && SWUBotWeights('softaggro', 1)['burn'] > SWUBotWeights('midrange', 1)['burn'],
    'burn peaks at soft aggro — its plan is base damage from CARDS, which the curve cannot capture');
$check(SWUBotWeights('hardcontrol', 1)['wipe'] === 1.80 && SWUBotWeights('hardcontrol', 1)['draw'] === 1.40,
    'hard control tops the answer and card-advantage weights');
$check(SWUBotWeights('hardcontrol', 1)['loss'] < SWUBotWeights('softcontrol', 1)['loss'],
    'loss is deliberately NON-monotone: hard control takes even trades, so kill - loss > 0.6 (owner, 2026-09-17)');
$check(SWUBotWeights('hardcontrol', 1)['develop'] === SWUBotWeights('midrange', 1)['develop'],
    'develop is flat — bomb commitment is the bombtiming rule, not a lower develop weight');

// ── THE LADDER: what the weights mean in play, asserted as BEHAVIOUR ───────────────────────────────
// A 4-power / 4-cost attacker. base(4) = 0.60 * 4 = 2.40 for every archetype. The cheapest enemy unit each
// archetype stops to remove instead of hitting the base. Spec: "What the numbers mean in play".
$removesAtLeast = function (string $s): ?int {
    $W = SWUBotWeights($s, 1);
    for ($cost = 1; $cost <= 12; $cost++) if ($W['kill'] * $cost > $W['base'] * 4) return $cost;
    return null;
};
foreach (['hyperaggro' => 9, 'softaggro' => 5, 'midrange' => 3, 'softcontrol' => 2, 'hardcontrol' => 2] as $s => $c) {
    $check($removesAtLeast($s) === $c, "$s removes a blocker costing $c+ (got " . var_export($removesAtLeast($s), true) . ')');
}
// The four decisions the owner pinned on 2026-09-17, as arithmetic on the real weights.
$evenTrade = function (string $s): float { $W = SWUBotWeights($s, 1); return $W['kill'] * 4 - $W['loss'] * 4; };
$base4 = fn(string $s) => SWUBotWeights($s, 1)['base'] * 4;
$check($base4('hyperaggro') > SWUBotWeights('hyperaggro', 1)['kill'] * 2, 'pinned: hyper aggro ignores a killable 2-cost blocker');
$check($base4('softaggro') > SWUBotWeights('softaggro', 1)['kill'] * 2, 'pinned: soft aggro ignores a killable 2-cost blocker');
$check($base4('midrange') > SWUBotWeights('midrange', 1)['kill'] * 2, 'pinned: midrange hits the base against a 2-cost');
$check(SWUBotWeights('softcontrol', 1)['kill'] * 2 > $base4('softcontrol'), 'pinned: soft control kills the 2-cost');
$check(SWUBotWeights('hardcontrol', 1)['kill'] * 2 > $base4('hardcontrol'), 'pinned: hard control kills the 2-cost');
$check($evenTrade('hardcontrol') > $base4('hardcontrol'), 'pinned: hard control takes an even 4-cost trade over base damage');
$check($evenTrade('softcontrol') < $base4('softcontrol'), 'control: soft control does NOT take the even trade');
$check(SWUBotWeights('hardcontrol', 1)['chip'] * 4 < $base4('hardcontrol'),
    'pinned: hard control hits the base rather than chipping a survivor (the 462 null attacks)');

// Every archetype must return every key — a missing key would silently score 0 in the fallback.
$keys = array_keys(SWUBotWeights('midrange', 1));
foreach (SWU_BOT_ARCHETYPES as $s) {
    $check(array_keys(SWUBotWeights($s, 1)) === $keys, "$s returns the same weight keys as midrange");
}
foreach (['base','kill','loss','chip','grit','develop','unitPlay','removal','wipe','damage','draw','heal','burn',
          'buff','bounce','exhaust','deploy','ability','ready','initiative','attackFirst','maxUnits','stopPass'] as $k) {
    $check(in_array($k, $keys, true), "weight key '$k' exists");
}

// ── Racing is a rank SHIFT, not three hand-coded style swaps ───────────────────────────────────────
// Spec: "racing shifts rank toward aggro by 2, floored at 0". SWUBotIsRacing needs a board, so this checks the
// pure shift arithmetic through the seam; bot_baserace_test.php covers it on a real board.
$GLOBALS['SWUBotTestForceRacing'] = true;
foreach (['hardcontrol' => 2, 'softcontrol' => 1, 'midrange' => 0, 'softaggro' => 0, 'hyperaggro' => 0] as $s => $want) {
    $check(SWUBotRacingRank($s, 1) === $want, "racing: $s acts at rank $want (shift 2, floored at 0)");
}
$GLOBALS['SWUBotTestForceRacing'] = false;
foreach (SWU_BOT_ARCHETYPES as $s) {
    $check(SWUBotRacingRank($s, 1) === SWUBotStyleRank($s), "not racing: $s acts at its own rank");
}
unset($GLOBALS['SWUBotTestForceRacing']);

// ── Derivation: a FALLBACK for decks with no '# Style:' line (a human's deck in Arenabot) ──────────
// ⚠ NOT the default. Only hard control separates cleanly (event share >= 40%: the three hard-control fixtures are
// 46.8-51.0%, every other deck <= 26.7%). On mean unit cost the owner's labels interleave badly, so the curve bands
// below are an explicit best guess and the caller must LOG them. Spec: "Assignment".
$deck = function (array $spec): array {   // [[qty, cost, type], ...]
    return array_map(fn($r) => ['qty' => $r[0], 'cost' => $r[1], 'type' => $r[2]], $spec);
};
// 26 units at mean cost 5.4, 27 events -> 51% events: Aurra Red's shape.
$check(SWUBotDeriveStyle($deck([[26, 5, 'Unit'], [27, 3, 'Event']])) === 'hardcontrol',
    'derive: half the deck is events -> hard control');
// 31 units at mean 2.2, 12 events -> 28% events but a very low curve: Chewbacca's shape.
$check(SWUBotDeriveStyle($deck([[31, 2, 'Unit'], [12, 3, 'Event']])) === 'hyperaggro',
    'derive: a 2.0 curve is hyper aggro even with a fifth of the deck in events');
$check(SWUBotDeriveStyle($deck([[45, 3, 'Unit'], [3, 3, 'Event']])) === 'softaggro',
    'derive: a 3.0-3.5 curve is soft aggro');
$check(SWUBotDeriveStyle($deck([[43, 4, 'Unit'], [5, 3, 'Event']])) === 'midrange',
    'derive: a 3.6-4.2 curve is midrange');
$check(SWUBotDeriveStyle($deck([[40, 5, 'Unit'], [8, 3, 'Event']])) === 'softcontrol',
    'derive: a high curve without the event density is soft control');
$check(SWUBotDeriveStyle([]) === 'midrange', 'derive: an empty deck falls back to midrange, never to an extreme');
$check(SWUBotDeriveStyle($deck([[40, 3, 'Upgrade']])) === 'midrange',
    'derive: a deck with no units at all falls back to midrange');

// ── Flavours can shift rank (feature 'flavourrank') ────────────────────────────────────────────────
// Owner, 2026-09-17: "a tempo deck would trade. a normal midrange deck would hit base" — so tempo is +1 toward control.
$check(SWU_BOT_FLAVOUR_RANK_SHIFT['tempo'] === 1, 'tempo shifts +1 toward control');
$check(!isset(SWU_BOT_FLAVOUR_RANK_SHIFT['combo']) && !isset(SWU_BOT_FLAVOUR_RANK_SHIFT['space']),
    'only flavours with a measured reason carry a shift; the rest are descriptive');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
