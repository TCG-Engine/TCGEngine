<?php
// IC27_029
// Cost 10 - Chimaera - Grand Admiral's Might - [Vigilance,Villainy] - Unit (Space) 8/8
//   Traits: Imperial, Vehicle, Capital Ship - unique
// Text: Shielded
//       When Played: Give an Experience token to each friendly unit for each upgrade on it. Then, heal 1 damage from your
//       base for each friendly upgrade.
//
// Shielded is generated keyword wiring; with it, Chimaera has TWO entry triggers and the player orders them — Shield
// first means Chimaera counts its own Shield and gets an Experience token.
//   • "for each upgrade on it" — EVERY upgrade on the unit, whoever controls it (an opponent's upgrade on your unit
//     counts), tokens and pilots included, captives not (GetUpgradesOnUnit). All counts are taken BEFORE any token is
//     given: one instruction, so a unit's own new Experience does not feed back into its count.
//   • "each friendly unit" / "your base" / "each friendly upgrade" — friendly spans the team in Team Suns.
//   • "friendly upgrade" = an upgrade a friendly player CONTROLS, on ANY unit. A non-token upgrade is controlled by whoever
//     played it, even on an enemy unit (CR 2.f — your Bounty upgrade on an enemy counts; an opponent's upgrade on your
//     unit does not). A TOKEN upgrade is controlled by the HOST's controller (CR 2.g — an enemy's Experience/Shield on
//     your unit is yours); the token primitives stamp it that way. Counted after the Experience lands ("Then").
//   • One attach event per unit (Sabine ASH_208's "1 or more upgrades attach"), the LOF_239 batch shape.
// Tests: SWUSim/Tests/Cases/ic27/Chimaera_GrandAdmiralsMight.md

$whenPlayedAbilities["IC27_029:0"] = function($player, $mzID = '') {
    global $playerID;
    $me = intval($player);
    $playerID = $me;

    $plan = [];
    foreach (SWUFriendlyUnits() as $mz) {
        $u = GetZoneObject($mz);
        if (SWUObjGone($u)) continue;
        $n = count(GetUpgradesOnUnit($u));
        if ($n > 0) $plan[] = [$mz, $n];
    }
    foreach ($plan as [$mz, $n]) {
        for ($i = 0; $i < $n; $i++) DoGiveExperienceToken($me, $mz, false);
        $playerID = $me;
        $u = GetZoneObject($mz);
        if (!SWUObjGone($u)) _SWUAsh208OnUpgradeAttach($me, $u);
    }

    $friendlySeats = array_merge([$me], SWUTeammatesOf($me));
    $friendlyUpgrades = 0;
    foreach (GetLiveSeatsArray() as $seat) {
        foreach (GetUnitsInPlay($seat) as $u) {
            if (!empty($u->removed)) continue;
            foreach (GetUpgradesOnUnit($u) as $sub) {
                if (in_array(intval($sub->Controller ?? $sub->Owner ?? 0), $friendlySeats, true)) $friendlyUpgrades++;
            }
        }
    }
    $playerID = $me;
    if ($friendlyUpgrades > 0) OnHealBase($me, $me, $friendlyUpgrades);
};
