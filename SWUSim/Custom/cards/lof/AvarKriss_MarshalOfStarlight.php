<?php
// LOF_007
// Cost 9 - Avar Kriss - Marshal of Starlight - [Command,Heroism] - Power 4 - HP 10
// Text: Action [Exhaust]: The Force is with you (create your Force token).
// DeployText: While the Force is with you, this unit gets +4/+0 and gains Overwhelm.
// Epic Action: If the number of resources you control plus the number of times you used the Force this phase is 9 or more, deploy this leader.

// LOF_007 Avar Kriss — Action [Exhaust]: The Force is with you (create your Force token). (Her Epic Action
// conditional deploy is gated in SWUDeployLeader: resources + Force-uses-this-phase ≥ 9.)
$leaderAbilities["LOF_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    TheForceIsWithYou($player);
    SWUAfterAction($player);
};
