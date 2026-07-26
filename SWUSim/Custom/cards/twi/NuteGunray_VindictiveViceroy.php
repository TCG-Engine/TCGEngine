<?php
// TWI_002
// Cost 6 - Nute Gunray - Vindictive Viceroy - [Vigilance,Villainy] - Power 2 - HP 8
// Text: Action [Exhaust]: If 2 or more friendly units were defeated this phase, create a Battle Droid token.
// DeployText: On Attack: Create a Battle Droid token.
// Epic Action: If you control 6 or more resources, deploy this leader.

// TWI_002 Nute Gunray (deployed) — "On Attack: Create a Battle Droid token."
$onAttackAbilities["TWI_002:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'TWI_T01');
    // Combat owns the after-action.
};

// TWI_002 Nute Gunray (front) — "Action [Exhaust]: If 2 or more friendly units were defeated this phase,
// create a Battle Droid token." (Affordability gates the ≥2 condition.)
$leaderAbilities["TWI_002"] = function(int $player): void {
    SWUCreateUnitToken($player, 'TWI_T01');
    SWUAfterAction($player);
};
