<?php
// HMW_159
// Cost 7 - General Grievous - Scourge of Dathomir - [Aggression][Villainy] - Unit (Ground) 8/5 -
// Traits: Separatist, Official - Unique - Legendary
// Text: Bases can't be healed.
//       When Played: Deal 4 damage to a base.
//
// Clause 1 ("Bases can't be healed.") is NOT here — it is a continuous, GLOBAL lock and lives at the
// single base-heal funnel, `OnHealBase` in CombatLogic.php, via the `_SWUBasesCantBeHealed()` CardID
// list it shares with TWI_132 Confederate Tri-Fighter. OnHealBase is the only place a base's Damage
// is ever decremented (Restore included), so one gate covers every heal path.
//
// Clause 2: "a base" carries NO controller word, so BOTH bases are legal targets — a player may aim
// the 4 at their OWN base (which matters for the base-damage-threshold family, e.g. HMW_074). Two
// bases are always in play, so this choice never auto-resolves and is a real MZCHOOSE every time.
// Mandatory: no "may", no "up to".
$whenPlayedAbilities["HMW_159:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    SWUOfferBaseTarget(intval($player), [
        'continuation' => 'DEAL_BASE_DAMAGE', 'amount' => 4, 'baseSide' => 'any',
        'prompt' => "Deal_4_damage_to_a_base",
    ]);
};
