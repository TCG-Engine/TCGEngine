<?php
// HMW_195 Catch the Scent — Event, cost 5, [Aggression], Innate.
// "Create 2 Beast tokens and ready 1 of them."
// One create instruction for 2. The tokens are identical, so which one readies is not a real choice.

$whenPlayedAbilities["HMW_195:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $uids = SWUCreateUnitTokens(intval($player), 'HMW_T03', 2);
    foreach ($uids as $uid) {
        $mz = SWUFindMzByUID(intval($uid));
        if ($mz === null) continue;
        OnReadyCard(intval($player), $mz);
        break;
    }
};
