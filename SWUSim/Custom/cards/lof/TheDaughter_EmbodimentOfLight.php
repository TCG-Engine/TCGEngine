<?php
// LOF_252
// Cost 5 - The Daughter - Embodiment of Light - [Heroism] - Power 4 - HP 6
// Text: When damage is dealt to your base: You may use the Force (lose your Force token). If you do, heal 2 damage from your base.

// LOF_252 The Daughter — "When damage is dealt to your base: may use the Force → heal 2 from your base."
// Collected at the base-damage point (_SWUCollectLof252Reaction in SWUDealDamageToBase). Post-damage, so
// no combat-pause needed — the reaction sits on the base owner's queue and resolves after the damage.
$customDQHandlers["LOF_252#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    OnHealBase(intval($player), intval($player), 2);
};
