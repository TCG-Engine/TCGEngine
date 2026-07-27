<?php
// SHD_057  |  Reprints: LAW_115
// Cost 2 - Rickety Quadjumper - [Vigilance] - Power 1 - HP 3
// Text: On Attack: You may reveal the top card of your deck. If it's not a unit, give an Experience token to another unit. (Leave the revealed card on top of your deck.)

// LAW_115 Rickety Quadjumper — On Attack: you may reveal the top card of your deck. If it's not a unit,
// give an Experience token to another unit. (Leave the revealed card on top.)
$onAttackAbilities["LAW_115:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (_SWUTopDeckFrontIdx(intval($player)) === -1) return;   // empty deck
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Reveal_the_top_card_of_your_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_115#0|{$uid}", 1);
};

$customDQHandlers["LAW_115#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $idx = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $topID = GetDeck(intval($player))[$idx]->CardID;
    AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . GameLogCardRef($topID) . ' (top of deck)', 'ALL');
    if (stripos(CardType($topID) ?? '', 'Unit') !== false) return;   // it IS a unit → no Experience
    $uid = intval($parts[0] ?? 0);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'GIVE_EXPERIENCE', 'excludeUID' => $uid,
        'prompt' => "Give_an_Experience_token_to_another_unit",
    ]);
};

// ─── SHD_057 Rickety Quadjumper ───────────────────────────────────────────────
// On Attack: You may reveal the top card of your deck. If it's not a unit, give an Experience token to
// another unit. (Leave the revealed card on top.)
$onAttackAbilities["SHD_057:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, tooltip:"Reveal_the_top_card_of_your_deck?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "SHD_057#0|{$selfUID}", 1);
};

$customDQHandlers["SHD_057#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'YES') return;
    $selfUID = intval($parts[0] ?? 0);
    $deck = &GetDeck(intval($player));
    $idx  = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $top = $deck[$idx]->CardID;
    AddGameLogEntry('ABILITY', 'Revealed ' . CardTitle($top) . ' (left on top of deck)', 'ALL');
    if (strpos(CardType($top) ?? '', 'Unit') !== false) return;    // it IS a unit → no Experience
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'GIVE_EXPERIENCE', 'excludeUID' => $selfUID,
        'prompt' => "Give_an_Experience_token_to_another_unit",
    ]);
};
