<?php
// SHD_107
// Cost 4 - Enterprising Lackeys - [Command,Command] - Power 5 - HP 5
// Text: When Defeated: You may defeat a friendly resource. If you do, put this unit into play as a resource. / Smuggle [6 resources Command Command]

// ─── SHD_107 Enterprising Lackeys ─────────────────────────────────────────────
// When Defeated: You may defeat a friendly resource. If you do, put this unit into play as a
// resource (enters EXHAUSTED — no "ready it" wording). One MZMAYCHOOSE over the controller's
// resources: pick = pay + ramp, pass = stays in discard.
$whenDefeatedAbilities["SHD_107:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26); the p{n} mzIDs a teammate's
    // resources come back as are what makes the transport REVEAL them instead of showing card backs.
    $res = SWUFriendlyResourceMzIDs(intval($player));
    SWUQueueMayChooseTarget(intval($player), $res,
        "Defeat_a_friendly_resource_to_put_this_into_play_as_a_resource?",
        "Defeat_a_friendly_resource_to_put_this_into_play_as_a_resource?", "SHD_107#0");
};

$customDQHandlers["SHD_107#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!SWUDefeatResource(intval($player), $lastDecision)) return;
    $mz = _SWUFindSelfInDiscardMzID(intval($player), 'SHD_107');
    if ($mz !== null) SWURampResourceExhausted(intval($player), $mz);
};
