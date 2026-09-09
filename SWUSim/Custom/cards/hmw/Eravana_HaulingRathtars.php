<?php
// HMW_047
// Cost 5 - Eravana, Hauling Rathtars - [Command][Cunning] - Power 1 - HP 7 - Space
// Traits: Underworld, Vehicle, Transport (unique)
// Text: On Attack: Create a Beast token and ready it.

// HMW_047 Eravana — On Attack: create a Beast token (HMW_T03) and ready it.
//
// Two details make this a one-liner rather than the hand-rolled shape it looks like:
//
// 1. THE TOKEN IS A GROUND UNIT, NOT A SPACE ONE. Eravana lives in the space arena but HMW_T03
//    Beast is `arena=Ground` (a 3/3 Creature), and _SWUCreateOneToken already routes by
//    CardTargetArena($tokenID) — so the cross-arena hop is free. Do NOT try to place it beside
//    the source.
//
// 2. "AND READY IT" IS THE `$ready` PARAM, NOT A POST-HOC OnReadyCard. A created token enters
//    EXHAUSTED by default, so the second half is real work — but SWUCreateUnitToken's third
//    argument already threads it, and crucially it also threads it into
//    _SWUMaybeOfferJerjerrodDouble($player, $tokenID, 1, $ready). That matters: ASH_094 Moff
//    Jerjerrod makes his doubled tokens LATER, inside his own decision handler, so a rider
//    stamped on the UID returned here would miss them entirely (the TS26_14 Yoda / TS26_55 Jedi
//    General bug shape). HMW_047 is the first card whose Jerjerrod rider is READINESS rather than
//    a token upgrade; the guard is Jerjerrod_DoublesTheTokens_AndBOTHAreReady.
//
// Combat owns the after-action for an On Attack trigger — no SWUAfterAction here.
$onAttackAbilities["HMW_047:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'HMW_T03', true);
};
