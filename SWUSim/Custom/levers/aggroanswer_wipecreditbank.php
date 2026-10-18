<?php
// LEVER 'wipecreditbank' (theme 'aggroanswer', G1-33 bank-credit-for-early-wipe). Traced krennic-vader-1: the R1 Credit went on GNK Power
// Droid ('earlycredits'); in R5 Hyperspace Disaster sat in hand with 6 resources and no Credit, the bot claimed for a next-round wipe and died
// first. Krennic vs Vader casts HSD at median R5.3; held in 31 of 137 games, 18 of them ending in R5.
// play — a Credit-ramp seat (SWUBotBanksCredits: Krennic) facing a space-flavoured aggro leader, from round 3, with a space wipe in hand or
// 2+ in the deck, keeps its LAST Credit banked: a play that would spend it, other than a wipe, waits — so 6R + 1C casts the wipe in R5.
const SWU_BOT_LV_WIPECREDITBANK_FROM = 3;

function _SWUBotLvWipecreditbankIsSpaceWipe(string $cid): bool {
    return in_array('wipe', SWUBotCardTags($cid), true) && _SWUBotWipeArenas($cid) === ['Space'];
}
function _SWUBotLvWipecreditbankWipeComing(int $seat): bool {
    foreach (_SWUBotHandCards($seat) as $hc) if (_SWUBotLvWipecreditbankIsSpaceWipe($hc)) return true;
    $n = 0;
    foreach (GetDeck($seat) as $o) if ($o !== null && empty($o->removed) && _SWUBotLvWipecreditbankIsSpaceWipe(strval($o->CardID ?? ''))) $n++;
    return $n >= 2;
}

SWUBotLeverRegister('aggroanswer', 'wipecreditbank', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if ($v <= -0.5 || intval(GetTurnNumber()) < SWU_BOT_LV_WIPECREDITBANK_FROM || in_array('wipe', SWUBotCardTags($cid), true)) return null;
        if (!SWUBotBanksCredits($seat) || ($spend = SWUBotCreditSpendFor($seat, $cid)) <= 0) return null;
        $usable = count(SWUUsableCreditTokenMzIDs($seat));
        if ($usable - $spend >= 1) return null;
        $opp = SWUBotOpponent($seat);
        if (!SWUBotOpponentIsAggroLeader($seat) || !in_array('space', SWUBotDeckFlavours($opp), true) || !_SWUBotLvWipecreditbankWipeComing($seat)) return null;
        return min($v, -0.5);
    },
]);
