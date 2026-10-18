<?php
// LEVER 'wdspacefodder' (theme 'aggroanswer', G4-38 no-wasted-sentinel-arena-for-wd-decks). Thrawn Yellow vs Vader Yellow is -40: Vader's damage
// is space, so Thrawn's ground When Defeated fodder (Door Tech, Night Trooper, Nightsister) blocks nothing, while Onyx Squadron Brute (space
// 2/3, "When Defeated: Heal 2") was resourced 54% of the time (Yellow). ('wallkeeparena', p43, does this for Sentinels only.)
// keep — a When Defeated deck (my leader's text reads "When Defeated" or "defeat a friendly unit": Thrawn, Krennic) facing a space-flavoured
// opponent keeps its cheap (cost <= 3) When Defeated SPACE bodies (+50) and lets the ground ones go first (-25).
// (G4-12, the WD-fodder keep this mirrors, is not shipped; the arena preference is applied on its own.)
const SWU_BOT_LV_WDSPACEFODDER_MAX_COST = 3;

function _SWUBotLvWdspacefodderWdDeck(int $seat): bool {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        $c = strval($l->CardID ?? '');
        if (preg_match('/When Defeated|defeat a friendly unit/i', strval(CardText($c)) . ' ' . strval(CardDeployText($c) ?? ''))) return true;
    }
    return false;
}
function _SWUBotLvWdspacefodderSpaceOpp(int $seat): bool {
    foreach (SWUBotOpponents($seat) as $o) if (in_array('space', SWUBotDeckFlavours($o), true)) return true;
    $sp = 0; $gr = 0;
    foreach (SWUBotEnemyUnits($seat) as $u) { if ($u['arena'] === 'Space') $sp++; else $gr++; }
    return $sp >= 2 && $sp >= $gr;
}

SWUBotLeverRegister('aggroanswer', 'wdspacefodder', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!str_contains(strval(CardType($cid)), 'Unit') || intval(CardCost($cid)) > SWU_BOT_LV_WDSPACEFODDER_MAX_COST
            || !preg_match('/When Defeated:/i', strval(CardText($cid)))) return null;
        if (!_SWUBotLvWdspacefodderWdDeck($seat) || !_SWUBotLvWdspacefodderSpaceOpp($seat)) return null;
        return $keep + (_SWUBotPlayArena($cid) === 'Space' ? 50.0 : -25.0);
    },
]);
