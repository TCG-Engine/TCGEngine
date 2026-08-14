<?php
// HMW_202
// Cost 5 - Inferno Squad, We Can Grieve Later - [Cunning][Villainy] - Unit (Ground) 3/6 - unique
// Traits: Imperial, Trooper
// Text: When Played/When Defeated: You may deal 1 damage to a unit and give a Weakness token to it.
//
// TWO trigger windows, ONE effect — registered on the same closure so the two halves can never drift
// apart (the "a multi-branch card lands N-1 of N branches" bug class).
//
// Target set: "a unit" is UNQUALIFIED, so every unit on the board is legal — friendly, enemy, and
// Inferno Squad ITSELF (the text says "a unit", not "another unit"). Hence side 'any' with no
// excludeSelf. The clause is optional ("You may"), so it is a single MZMAYCHOOSE that can be declined
// with '-' — never a YESNO plus a separate choose.
//
// The two halves are joined by "and", not "if you do", so neither gates the other. But they DO compound
// on the same unit: the 1 damage can leave a target at 1 remaining HP that the Weakness (-1/-1) then
// finishes off, which is why the shrink-defeat sweep has to run after attaching (HP loss has no
// state-based defeat of its own).
$whenPlayedAbilities["HMW_202:0"] = $whenDefeatedAbilities["HMW_202:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), (string)$mzID, [
        'continuation' => 'HMW_202#0',
        'may'          => true,
        'side'         => 'any',   // "a unit" — friendly, enemy, and this unit itself are all legal
        'prompt'       => 'Deal_1_damage_to_a_unit_and_give_a_Weakness_token_to_it',
    ]);
};

// Deal the damage FIRST (printed order), then attach the Weakness to the SAME unit.
//
// ⚠ The damage can defeat the target, and SWUDealDamageToUnit runs CleanupRemovedCards on a defeat —
// which compacts the arena and re-indexes every unit behind it. So the chosen mzID string is only
// valid BEFORE the damage: re-resolving the host by UniqueID afterwards is what stops the token being
// stranded on whichever bystander shifted into the vacated slot. A dead target simply gets no token
// (there is no host left) — that is the effect doing as much as it can, not a gate.
$customDQHandlers["HMW_202#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $uid = intval($obj->UniqueID ?? 0);

    SWUDealDamageToUnit((string)$lastDecision, 1, intval($player));

    $playerID = intval($player);
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;                      // defeated by the damage — nothing left to attach to
    DoGiveTokenUpgrade(intval($player), $mz, 'HMW_T02');
    SWUCheckShrinkDefeats();                       // the -1 HP can itself be lethal
};
