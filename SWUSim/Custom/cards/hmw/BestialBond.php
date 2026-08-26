<?php
// HMW_038
// Cost 3 - Bestial Bond - [Command][Vigilance] - Upgrade (+2/+2)
// Traits: Innate
// Text: When Played: If attached unit is a Creature or a Force unit, create a Beast token.
//
// No printed attach restriction, so under CR 2.e it may be played on ANY unit — friendly or enemy —
// and SWUGetUpgradeValidTargets' default is already that. Nothing to register.
//
// Not a Pilot, so CollectWhenPlayedAsUpgradeTriggers falls back to the plain WhenPlayed window and
// hands this closure the HOST unit's mzID. $player is whoever PLAYED the upgrade, and that is who gets
// the Beast — the two differ whenever it is bonded to an enemy Creature (guarded by
// EnemyCreatureHost_TokenGoesToTheUpgradesController, which asserts both boards). Same reading as
// HMW_265 Twi'lek Kalikori's "your deck".
//
// Two things the condition is NOT:
//   • It is not two triggers. "a Creature OR a Force unit" is ONE condition with two ways to be true,
//     so a host that is both (SOR_056) still makes exactly one Beast — written as a single || rather
//     than two ifs (guarded by CreatureAndForceHost_StillOnlyOneBeast).
//   • It is not a printed-trait lookup. TraitContains reads the LIVE object, so SEC_054 Exiled from
//     the Force genuinely removes the Force trait and the token is not created (guarded by
//     ForceTraitRemoved_NoBeast). A bare-CardID HasTrait would still see "Force" in the dictionary.
//
// The Beast is HMW_T03 (a 3/3 ground Creature). SWUCreateUnitToken is the right API even though there
// is no rider to carry: it already routes ASH_094 Moff Jerjerrod's "create twice that number instead"
// through _SWUMaybeOfferJerjerrodDouble.

$whenPlayedAbilities["HMW_038:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    if (!TraitContains($host, 'Creature') && !TraitContains($host, 'Force')) return;
    SWUCreateUnitToken(intval($player), 'HMW_T03');
};
