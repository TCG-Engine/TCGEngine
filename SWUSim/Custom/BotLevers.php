<?php
// ── The LEVER REGISTRY (2026-10-17, the ASH lever hunt's step 4b: "do the rest all in one go") ─────────────────────────────────────
// A lever is one bot decision change, measured as an off-by-default PROPOSAL ("@try-<name>", or its theme "@try-<theme>"). Each lives
// in its own file under Custom/levers/, registers itself here, and hooks the scorer only through the fixed hook points below — so many
// levers can be written side by side without editing the shared scorer, and with none switched on every hook is a no-op.
//
// Hook points (who calls them, and the callback's signature). CHAIN hooks pass the running value first and return the new value, or null
// for "unchanged"; FIRST hooks return a score/override, or null to defer (the first non-null wins).
//   chain 'filter'     fn(array $actions, array $ctx): ?array                      — the action list before any rule (BotHeuristic.php)
//   first 'decision'   fn(array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float — a decision candidate
//   first 'ability'    fn(array $ctx, int $seat, array $action, array $W): ?float  — a leader / unit / base Action's value
//   chain 'play'       fn(float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float — a card play's value
//   chain 'attack'     fn(float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float — an attack action's score
//   chain 'target'     fn(float $tv, array $att, ?array $def, array $W): ?float    — one attack target's value ($def null = base)
//   chain 'initiative' fn(float $iv, array $ctx, int $seat, array $W): ?float     — taking the initiative
//   chain 'deploy'     fn(float $dv, array $ctx, int $seat, array $action, array $W): ?float — a leader deploy
//   chain 'sacrifice'  fn(float $cost, array $v): ?float                          — what losing unit $v to a sacrifice costs
//   chain 'killextra'  fn(float $extra, array $def, array $W, int $seat): ?float  — what a kill of $def earns beyond W[kill] x value
//   chain 'keep'       fn(float $keep, array $ctx, int $seat, string $cid, int $i): ?float — resourcing keep (higher = keep in hand)
//   chain 'mulligan'   fn(?bool $mull, int $seat, string $style): ?bool          — the mulligan answer (true = mulligan)
//   chain 'lossfactor'  fn(float $f, array $att): ?float                         — the loss-weight factor of attacker $att (leaderrisk …)
//   chain 'observertax' fn(float $tax, array $att, array $W): ?float             — what losing attacker $att costs through enemy observers
//   chain 'weakness'    fn(float $score, int $seat, array $v, bool $enemy, int $weak, array $W, int $loss): ?float — a Weakness give on $v ($loss = HP it takes)
//   chain 'finisherhp'  fn(int $n, int $seat, array $v): ?int                    — my finisher threshold vs target $v (_SWUBotFinisherHPFor)
//   chain 'splitown'    fn(float $part, int $seat, array $v, int $amt, array $W): ?float — a damage split's share on MY unit $v (_SWUBotSplitScore)
//   first 'allowtarget' fn(array $ctx, array $att, array $def): ?bool               — true keeps unit target $def the style would prune (SWUBotAllowedTargets)
//   ($GLOBALS['SWUBotKillCtx'] === 'removal' while a hostile DEFEAT is priced by _SWUBotTargetScore — 'killextra' callers can tell removal from attacks)

$GLOBALS['SWUBotLevers'] = $GLOBALS['SWUBotLevers'] ?? [];   // name => ['theme' => string, 'hooks' => [hook => callable]]
// SHIPPED levers (feature group p54, BotFeatures.php): default ON — active unless "@no-<name>" (or its group) switches the feature off. They are
// FEATURES, so they leave the proposal list; every other registered lever stays an off-by-default proposal.
function _SWUBotShippedLevers(): array {
    return array_merge(defined('SWU_BOT_PART54_FEATURES') ? SWU_BOT_PART54_FEATURES : [], defined('SWU_BOT_PART56_FEATURES') ? SWU_BOT_PART56_FEATURES : [],
                       defined('SWU_BOT_PART57_FEATURES') ? SWU_BOT_PART57_FEATURES : [],    // p54 + p56 + p57 (only 'readykill' of p57 is a registry lever)
                       defined('SWU_BOT_PART58_FEATURES') ? SWU_BOT_PART58_FEATURES : [],    // + p58 (the tempo layer, levers/tempo_*.php)
                       defined('SWU_BOT_PART59_FEATURES') ? SWU_BOT_PART59_FEATURES : [],    // + p59 (the tempo deck plans, levers/plan_*.php)
                       defined('SWU_BOT_PART67_FEATURES') ? SWU_BOT_PART67_FEATURES : []);   // + p67 (four of the attrition levers, levers/attrition_*.php)
}

function SWUBotLeverRegister(string $theme, string $name, array $hooks): void {
    $GLOBALS['SWUBotLevers'][$name] = ['theme' => $theme, 'hooks' => $hooks];
}
function SWUBotLeverProposals(): array {
    return array_values(array_diff(array_keys($GLOBALS['SWUBotLevers'] ?? []), _SWUBotShippedLevers()));
}
function SWUBotLeverGroups(): array {
    $g = [];
    $shipped = _SWUBotShippedLevers();
    foreach ($GLOBALS['SWUBotLevers'] ?? [] as $name => $l) if (!in_array($name, $shipped, true)) $g[$l['theme']][] = $name;
    return $g;
}
// The callbacks of the levers switched on for THIS seat's variant, for one hook. Cached per disabled-set (the variant can differ by seat).
function _SWUBotLeverActive(string $hook): array {
    static $cache = [];
    $dis = $GLOBALS['SWUBotDisabledFeatures'] ?? [];
    if (empty($GLOBALS['SWUBotLevers'])) return [];
    $key = implode('|', $dis) . '#' . implode('|', $GLOBALS['SWUBotPinnedDisabled'] ?? []);
    if (!isset($cache[$key])) {
        $by = [];
        $shipped = _SWUBotShippedLevers();
        foreach ($GLOBALS['SWUBotLevers'] as $name => $l) {
            $on = in_array($name, $shipped, true) ? SWUBotFeatureOn($name) : in_array("try:$name", $dis, true);
            if (!$on) continue;
            foreach ($l['hooks'] as $h => $fn) $by[$h][$name] = $fn;
        }
        $cache[$key] = $by;
    }
    return $cache[$key][$hook] ?? [];
}
function SWUBotLeverChain(string $hook, $value, ...$args) {
    foreach (_SWUBotLeverActive($hook) as $name => $fn) {
        $r = $fn($value, ...$args);
        if ($r !== null && $r !== $value) { $value = $r; _SWUBotLeverFired($name, $args); }
    }
    return $value;
}
function SWUBotLeverFirst(string $hook, ...$args) {
    foreach (_SWUBotLeverActive($hook) as $name => $fn) {
        $r = $fn(...$args);
        if ($r !== null) { _SWUBotLeverFired($name, $args); return $r; }
    }
    return null;
}
// Coverage "proposal:<lever>" for the seat in the arguments (an int, or a ctx's 'seat'), so a run shows each lever is REACHED.
function _SWUBotLeverFired(string $name, array $args): void {
    $seat = 0;
    foreach ($args as $a) {
        if (is_int($a)) { $seat = $a; break; }
        if (is_array($a) && isset($a['seat']) && !isset($a['uid'])) { $seat = intval($a['seat']); break; }
        if (is_array($a) && isset($a['controller'])) { $seat = intval($a['controller']); break; }
    }
    if ($seat > 0 && function_exists('_SWUBotFired')) _SWUBotFired($name, $seat);
}

foreach (glob(__DIR__ . '/levers/*.php') ?: [] as $f) require_once $f;
