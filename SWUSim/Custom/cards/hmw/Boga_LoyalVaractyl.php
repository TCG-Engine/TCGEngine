<?php
// HMW_122
// Cost 6 - Boga - Loyal Varactyl - [Command,Heroism] - Unit (Ground) 6/6
// Traits: Creature - Unique
// Text: When Played/When Defeated: Choose a non-Vehicle unit in your discard pile not named Boga. For this
//       phase, you may play that unit from your discard pile. It costs [1 resource] less.
//
// TWI_201 Aid from the Innocent's permission at 1: the chosen entry is stamped with the own-discard
// modifier 'TPP1U' — play it at cost (TPP), 1 less (1), UNIT-ONLY (U) — which SWUClearDiscardModifiers
// wipes at regroup start ("for this phase"), and which SWUPlayFromDiscard runs through the full play
// pipeline (every reducer, Exploit, Credits). 'U' because "play that UNIT": a Pilot played by a "play a
// unit" ability may only be played as a unit (official Piloting ruling, 03/06/2025).
//
// • "Choose" is mandatory (SWUQueueChooseTarget, no pass); the PLAY is the optional half.
// • "Your discard pile" is the CONTROLLER's — the player resolving the trigger. On the When Defeated,
//   Boga itself is already in a discard pile, and is out of the pool by name.
// • A card already carrying a BETTER permission keeps it: a free play (TPF — SHD_115 Cobb Vanth, Second
//   Chance) or a bigger discount (TPP2). An OPPONENT's permission on the same entry (OTPN — TS26_26 Mother
//   Talzin) is also left alone: one Modifier field cannot hold both, and erasing another player's grant is
//   the worse failure. Boga's grant is lost on that entry only.

function _SWUHmw122Pool(int $player): array {
    $out = [];
    foreach (ZoneSearch('myDiscard') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        $cid = (string)($o->CardID ?? '');
        if (strpos(CardType($cid) ?? '', 'Unit') === false) continue;   // a unit
        if (HasTrait($cid, 'Vehicle')) continue;                        // non-Vehicle
        if (CardTitle($cid) === 'Boga') continue;                       // not named Boga
        $out[] = $mz;
    }
    return $out;
}

function _SWUHmw122Begin(int $player): void {
    global $playerID; $playerID = $player;
    $pool = _SWUHmw122Pool($player);
    if (empty($pool)) return;
    SWUQueueChooseTarget($player, $pool,
        "Choose_a_non-Vehicle_unit_in_your_discard_pile_not_named_Boga", 'HMW_122#0');
}

$whenPlayedAbilities["HMW_122:0"]   = function ($player, $mzID = '') { _SWUHmw122Begin(intval($player)); };
$whenDefeatedAbilities["HMW_122:0"] = function ($player, $mzID = '') { _SWUHmw122Begin(intval($player)); };

$customDQHandlers["HMW_122#0"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = (string)$lastDecision;
    if (SWUDecisionDeclined($mz) || strpos($mz, 'myDiscard-') !== 0) return;
    $entry = GetZoneObject($mz);
    if (SWUObjGone($entry)) return;
    $cur = SWUParseDiscardModifier((string)($entry->Modifier ?? ''));
    // Keep a better (or an opponent's) permission; replace nothing, a plain at-cost one, or a smaller one.
    if ($cur['kind'] === '' || ($cur['kind'] === 'TPP' && $cur['discount'] < 1)) {
        $entry->Modifier = 'TPP1U';
    }
};
