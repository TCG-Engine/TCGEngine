<?php
// LEVER 'enginewatch' (theme 'controlread', report OG5). 'enginekill' (p44, SWUBotEngineValueFromText) misses three engines the Hemlock DV
// games kept alive: Nuvo Vindi HMW_062 (in ~14 kills, attacked once the turn it landed), Chimaera ASH_052's heal-per-enemy-defeat,
// and Director Krennic SOR's "Each friendly damaged unit gets +1/+0". Per-round value in base-damage points, on enginekill's scale.
const SWU_BOT_LV_ENGINE_WEAKNESS_CHAIN = 2.0;   // one Weakness a round ≈ the Sentinel-strip magnitude (SWU_BOT_ENGINEKILL_SENTINEL)
const SWU_BOT_LV_ENGINE_DAMAGED_AURA = 1.5;     // = SWU_BOT_ENGINEKILL_BUFF

function _SWUBotLvEngineWatchValue(string $cid): float {
    static $memo = [];
    if (isset($memo[$cid])) return $memo[$cid];
    $t = preg_replace('/(^|\n)\s*When Played:[^\n]*/i', '$1', strval(CardText($cid)));   // a When Played fires once — not an engine
    $v = 0.0;
    if (preg_match('/When an enemy unit (?:with a Weakness token on it )?is defeated: You may give a Weakness token to a unit/i', $t)) $v = max($v, SWU_BOT_LV_ENGINE_WEAKNESS_CHAIN);
    if (preg_match('/When an enemy unit is defeated: Heal (\d+) damage from your base/i', $t, $m)) $v = max($v, floatval($m[1]));
    if (preg_match('/Each friendly damaged unit gets \+\d+\/\+0/i', $t)) $v = max($v, SWU_BOT_LV_ENGINE_DAMAGED_AURA);
    return $memo[$cid] = $v;
}

SWUBotLeverRegister('controlread', 'enginewatch', [
    'killextra' => function (float $extra, array $def, array $W, int $seat): ?float {
        $cid = strval($def['cardID'] ?? '');
        // Leaders are priced by leaderkill, as enginekill does — except the damaged-unit aura, which lives ONLY on a leader (Krennic SOR_001;
        // final review 2026-10-19: excluding leaders made that branch unreachable). Either face's text may carry it.
        if (!empty($def['isLeader'])) {
            $aura = '/Each friendly damaged unit gets \+\d+\/\+0/i';
            if (!preg_match($aura, strval(CardText($cid))) && !preg_match($aura, strval(CardDeployText($cid)))) return null;
            return $extra + floatval($W['base']) * SWU_BOT_LV_ENGINE_DAMAGED_AURA;
        }
        $v = _SWUBotLvEngineWatchValue($cid);
        // A heal observer heals only the damage its controller's base has (final review M9; 'healobserver' caps it the same way).
        if ($v > 0.0 && preg_match('/When an enemy unit is defeated: Heal (\d+) damage from your base/i', strval(CardText($cid)))) {
            $o = intval($def['controller'] ?? 0);
            $base = strval((GetBase($o)[0] ?? null)->CardID ?? '');
            $v = min($v, (float)max(0, SWUBaseMaxHp($base) - SWUBaseRemainingHp($o)));
        }
        return $v > 0.0 ? $extra + floatval($W['base']) * $v : null;
    },
]);
