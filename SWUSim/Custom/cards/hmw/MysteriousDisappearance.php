<?php
// HMW_058
// Cost 2 - Mysterious Disappearance - [Cunning][Vigilance] - Event - Trait: Trick
// Text: A player chooses a non-leader unit they control. You may defeat that unit. If you do, that player
//       creates a Beast token.
//
// Three steps, three seats of responsibility:
//   1. The CASTER chooses "a player" — any live seat, the caster and a TEAMMATE included (a player, not an
//      opponent: SWUQueueChoosePlayer, never SWUQueueChooseOpponent). ELIGIBILITY = WHO ACTS: the chosen
//      player acts on their own board, so only seats controlling a non-leader unit are offered, and a lone
//      eligible seat is picked silently.
//   2. THAT player picks one of their own non-leader units (SWUOpponentChoosesOwnUnit with the seat passed
//      explicitly — it serves the caster's own seat too). NonLeaderUnitFilter excludes deployed leaders and
//      leader-pilot hosts; token units count. "They CONTROL": a stolen unit is theirs to pick.
//   3. The CASTER may defeat it (YESNO), and only if the defeat actually HAPPENS ("If you do" —
//      SWUDefeatUnit returns false when e.g. SHD_187 refuses an enemy defeat) does THAT player create a
//      Beast (HMW_T03). The Beast goes to the chooser, even when the defeated card returns to another
//      OWNER's discard.
// The chosen unit crosses a decision (and possibly a seat) before step 3, so it rides the CUSTOM Param as
// a UniqueID; the play waits on the other seat's pick via the generic cross-player play pause.

if (!function_exists('_SWUHmw058EligibleSeats')) {
    function _SWUHmw058EligibleSeats(): array {
        global $playerID;
        $saved = $playerID;
        $out = [];
        foreach (GetLiveSeatsArray() as $seat) {
            $playerID = intval($seat);
            if (!empty(ZoneSearch('myGroundArena', NonLeaderUnitFilter))
                || !empty(ZoneSearch('mySpaceArena', NonLeaderUnitFilter))) $out[] = intval($seat);
        }
        $playerID = $saved;
        return $out;
    }
}

$whenPlayedAbilities["HMW_058:0"] = function($player, $mzID = '') {
    SWUQueueChoosePlayer(intval($player), "HMW_058#0",
        "Choose_a_player_(they_choose_a_non-leader_unit_they_control)", _SWUHmw058EligibleSeats());
};

// Step 2 — the chosen seat picks its unit. $player is the caster here.
$customDQHandlers["HMW_058#0"] = function($player, $parts, $lastDecision) {
    $chosen = SWUPickedOpponent($lastDecision);
    if ($chosen <= 0) return;
    SWUOpponentChoosesOwnUnit(intval($player), true,
        "Choose_a_non-leader_unit_you_control_(Mysterious_Disappearance)", "HMW_058#1|" . intval($player), $chosen);
};

// Step 3a — runs as the CHOSEN seat (its frame resolves $lastDecision); hand the defeat question back to
// the caster, carrying the unit by UniqueID and the chooser's seat.
$customDQHandlers["HMW_058#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $chosen = intval($player);
    $caster = intval($parts[0] ?? 0);
    if ($caster <= 0) return;
    $playerID = $chosen;
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o)) return;
    $uid   = intval($o->UniqueID ?? 0);
    $title = str_replace(' ', '_', (string)CardTitle($o->CardID ?? ''));
    $playerID = $caster;
    DecisionQueueController::AddDecision($caster, "YESNO", "-", 1,
        tooltip: "Defeat_{$title}?_If_you_do,_its_controller_creates_a_Beast_token.");
    DecisionQueueController::AddDecision($caster, "CUSTOM", "HMW_058#2|{$uid}|{$chosen}", 1);
};

// Step 3b — the caster's answer. Defeat as the caster (so enemy-ability immunity applies), then the Beast
// only on a real defeat.
$customDQHandlers["HMW_058#2"] = function($player, $parts, $lastDecision) {
    global $playerID;
    if ($lastDecision !== 'YES') return;
    $uid    = intval($parts[0] ?? 0);
    $chosen = intval($parts[1] ?? 0);
    if ($uid <= 0 || $chosen <= 0) return;
    $playerID = intval($player);
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    if (SWUDefeatUnit(intval($player), $mz)) {
        SWUCreateUnitTokens($chosen, 'HMW_T03', 1);
    }
};
