<?php
// IC27_151
// Cost 1 - R2-D2 - The First Protagonist - [Cunning,Heroism] - Unit (Ground) 1/3 (unique)
//   Traits: Rebel, Droid
// Text: On Attack: Choose one:
//       • Ready a resource.
//       • Deal 1 damage to a ground unit.
//       • Look at the top card of your deck. You may discard it.
//
// PREVIEW card (no official rulings). "Choose one" is a MANDATORY OPTIONCHOOSE, and the house modal rule (HMW_221
// Teeka, IC27_021 Grievous) applies: a mode that could only fizzle is not offered, and with one mode left it
// resolves directly with no menu. Labels are single tokens (the decision Param splits on spaces); the client
// shows underscores as spaces.
//   • Ready a resource — one of YOUR resources, the engine-wide reading of the unqualified wording (IC27_007 Asajj,
//     SOR_015 Boba); an owner ruling on "any player's resource" is OPEN. Resources are fungible, so nothing is
//     picked: SWUReadyResources readies one exhausted resource. Viable only while one is exhausted.
//   • Deal 1 damage to a ground unit — no side named, so ANY ground unit on the table (Twin Suns far seats
//     included), R2 himself too. Mandatory once chosen. Always viable while he attacks (he is a ground unit).
//     It resolves before combat damage, so a 1-HP defender is defeated before it strikes back. Source-less, like
//     IC27_005 Maul and SOR_033 Death Trooper.
//   • Look at the top card of your deck; you may discard it — SOR_192 Ezra Bridger's shape: an OPTIONCHOOSE of
//     "@CardID&Discard&Leave" shown to R2's controller only. Nothing is held across the decision: the card stays on
//     top, and Discard mills whatever is on top when it is answered (the same card — nothing can intervene).
//     Viable only with a non-empty deck.
// Tests: SWUSim/Tests/Cases/ic27/R2D2_TheFirstProtagonist.md

function _SWUIc27151Modes(int $player): array {
    $modes = [];
    foreach (GetResources($player) as $r) {
        if (empty($r->removed) && intval($r->Status ?? 1) === 0) { $modes[] = 'Ready_a_resource'; break; }
    }
    if (!empty(_SWUCollectUnitTargets($player, ['side' => 'any', 'arena' => 'Ground']))) $modes[] = 'Deal_1_damage_to_a_ground_unit';
    if (_SWUTopDeckFrontIdx($player) !== -1) $modes[] = 'Look_at_the_top_card';
    return $modes;
}

function _SWUIc27151Resolve(int $player, string $mode): void {
    global $playerID;
    $playerID = $player;
    switch ($mode) {
        case 'Ready_a_resource':
            SWUReadyResources($player, 1);
            break;
        case 'Deal_1_damage_to_a_ground_unit':
            SWUOfferUnitTarget($player, '', [
                'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'side' => 'any', 'arena' => 'Ground',
                'prompt' => "Deal_1_damage_to_a_ground_unit",
            ]);
            break;
        case 'Look_at_the_top_card':
            $idx = _SWUTopDeckFrontIdx($player);
            if ($idx === -1) return;
            $top = (string)(GetDeck($player)[$idx]->CardID ?? '');
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "@{$top}&Discard&Leave", 1,
                tooltip: "You_may_discard_the_top_card_of_your_deck");
            DecisionQueueController::AddDecision($player, "CUSTOM", "IC27_151#1", 1);
            break;
    }
}

$onAttackAbilities["IC27_151:0"] = function($player, $mzID) {
    global $playerID;
    $me = intval($player);
    $playerID = $me;
    $modes = _SWUIc27151Modes($me);
    if (empty($modes)) return;
    if (count($modes) === 1) { _SWUIc27151Resolve($me, $modes[0]); return; }
    DecisionQueueController::AddDecision($me, "OPTIONCHOOSE", implode('&', $modes), 1,
        tooltip: "Choose_one_(ready_a_resource_/_deal_1_to_a_ground_unit_/_look_at_your_top_card)");
    DecisionQueueController::AddDecision($me, "CUSTOM", "IC27_151#0", 1);
};

// Mode chosen. Re-checked against the live modes so a stale or forged answer does nothing.
$customDQHandlers["IC27_151#0"] = function($player, $parts, $lastDecision) {
    $mode = (string)$lastDecision;
    if (!in_array($mode, _SWUIc27151Modes(intval($player)), true)) return;
    _SWUIc27151Resolve(intval($player), $mode);
};

// Look mode answered: Discard mills the top card (From=DECK, discard observers fire); Leave does nothing.
$customDQHandlers["IC27_151#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if ($lastDecision === 'Discard') SWUMillTopCard(intval($player));
};
