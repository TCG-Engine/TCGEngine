<?php
// IC27_066
// Cost 7 - Mother Talzin - The Time Has Come - [Command,Villainy] - Unit (Ground) 5/5 (unique)
//   Traits: Force, Night
// Text: When Played: You may play a Villainy unit that costs 4 or less from your discard pile for free. If it's not
//       a Night or Undead unit, deal damage equal to its cost to this unit.
//
// SHD_242 Gideon's Light Cruiser's shape: an optional pick (MZMAYCHOOSE) over the Villainy UNITS in the resolver's
// own discard with printed cost <= 4, free-played from a CUSTOM continuation through SWUNestedPlay(ignoreCost) — a
// free play bypasses every cost modifier, and the helper keeps the nested play to ONE action close.
// "If it's not a Night or Undead unit" reads the played card's printed traits; the damage is its PRINTED cost (the
// free play cost nothing), dealt by Talzin to herself. It is dealt only when the card actually left the discard —
// a play the engine refused deals nothing.
// Tests: SWUSim/Tests/Cases/ic27/MotherTalzin_TheTimeHasCome.md
$whenPlayedAbilities["IC27_066:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch('myDiscard', ['Unit']) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $cid = $o->CardID ?? '';
        if (strpos(CardAspect($cid) ?? '', 'Villainy') === false) continue;
        if (intval(CardCost($cid)) > 4) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Play_a_Villainy_unit_costing_4_or_less_from_your_discard_for_free?",
        "Play_a_Villainy_unit_costing_4_or_less_from_your_discard_for_free", "IC27_066#0|" . SWUObjUID(GetZoneObject($mzID)));
};

$customDQHandlers["IC27_066#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cid = (string)($o->CardID ?? '');
    $before = count(array_filter(GetDiscard(intval($player)), fn($d) => !SWUObjGone($d) && ($d->CardID ?? '') === $cid));
    SWUNestedPlay(intval($player), $lastDecision, true, 0);   // ignoreCost = free
    $playerID = intval($player);
    $after = count(array_filter(GetDiscard(intval($player)), fn($d) => !SWUObjGone($d) && ($d->CardID ?? '') === $cid));
    if ($after >= $before) return;                                         // the play did not happen
    if (HasTrait($cid, 'Night') || HasTrait($cid, 'Undead')) return;
    $talzin = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($talzin === null) return;
    SWUDealDamageToUnit($talzin, intval(CardCost($cid)), intval($player), $talzin);
};
