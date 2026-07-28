<?php
// SHD_017
// Cost 4 - Lando Calrissian - With Impeccable Taste - [Cunning,Heroism] - Power 2 - HP 5
// Text: Action [Exhaust]: Play a card using Smuggle. It costs 2 resources less. Defeat a resource you own and control.
// DeployText: Action: Play a card using Smuggle. It costs 2 resources less. Defeat a resource you own and control. Use this ability only once each round.
// Epic Action: If you control 4 or more resources, deploy this leader.

$customDQHandlers["SHD_017#smuggle"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    if ($mz === '' || $mz === '-' || $mz === 'PASS') { SWUAfterAction(intval($player)); return; }
    $idx = intval(substr($mz, strrpos($mz, '-') + 1));
    SWUSmuggleResource(intval($player), $idx, 2, 'SHD_017#defeat');   // -2 cost; defer entry until after resource-defeat
};

// Deferred: the Smuggled slot is replaced; now defeat a resource you own and control, THEN fire the
// Smuggled card's When Played (via LandoCalrissianWithImpeccableTasteFireDeferred → _SWUSmuggleFireEntry).
$customDQHandlers["SHD_017#defeat"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $specs = [];
    foreach (GetResources(intval($player)) as $i => $r) {
        if (!empty($r->removed) || SWUIsCreditToken($r->CardID ?? '')) continue;
        // "you own AND control" — resources in your zone are yours unless STOLEN (Owner explicitly the
        // enemy). Owner 0/unset defaults to you (the documented zone-Owner gotcha).
        $owner = intval($r->Owner ?? 0);
        if ($owner > 0 && $owner !== intval($player)) continue;
        $specs[] = "myResources-{$i}";
    }
    if (empty($specs)) { LandoCalrissianWithImpeccableTasteFireDeferred(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $specs, "Defeat_a_resource_you_own_and_control", "SHD_017#resolve");
};

$customDQHandlers["SHD_017#resolve"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') SWUDefeatResource(intval($player), $lastDecision);
    LandoCalrissianWithImpeccableTasteFireDeferred(intval($player));
};

$leaderAbilities["SHD_017"] = function(int $player): void {   // front: [Exhaust] (leader exhausted by SWULeaderAction)
    global $playerID; $playerID = $player;
    if (!LandoCalrissianWithImpeccableTasteOffer($player)) SWUAfterAction($player);
};

$unitActionCostKind["SHD_017"] = 'none';

// deployed Action: no exhaust; gated once-per-round
$unitAbilities["SHD_017"] = function($player, $mzID) {
    AddGlobalEffects(intval($player), 'SWU_SHD017_USED');   // consume the once-per-round use
    if (!LandoCalrissianWithImpeccableTasteOffer(intval($player))) SWUAfterAction(intval($player));
};

function LandoCalrissianWithImpeccableTasteOffer(int $player): bool {
    global $playerID; $playerID = $player;
    $ready   = SWUResourceCount($player, readyOnly: true);
    $specs   = [];
    $logical = 0;
    foreach (GetResources($player) as $r) {
        if (!empty($r->removed) || SWUIsCreditToken($r->CardID ?? '')) continue;
        $cid = $r->CardID ?? '';
        if (stripos(CardType($cid) ?? '', 'Unit') !== false) {
            $c = GetEffectiveSmuggleCost($player, $cid);
            // Lando -2, then the shared modifier delta (surcharge/discount), then halve — matching
            // SWUSmuggleResource's paid amount so Lando's offer matches what can actually be paid.
            if ($c >= 0 && $ready >= SWUApplyCostHalving($player, max(0, $c - 2 + _SWUPlayCostModifierDelta($player, $r, null, true)))) $specs[] = "myResources-{$logical}";
        }
        $logical++;   // logical index counts every non-credit non-removed resource (matches SWUSmuggleResource)
    }
    if (empty($specs)) return false;
    SWUQueueChooseTarget($player, $specs, "Play_a_card_using_Smuggle_(costs_2_less)", "SHD_017#smuggle");
    return true;
}

function LandoCalrissianWithImpeccableTasteFireDeferred(int $player): void {
    $d = $GLOBALS['gSmuggleDeferred'] ?? null;
    if ($d === null) { SWUAfterAction($player); return; }
    unset($GLOBALS['gSmuggleDeferred']);
    _SWUSmuggleFireEntry(intval($d['player']), $d['cardID'], $d['mz'], $d['arena']);
}
