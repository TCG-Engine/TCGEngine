<?php

// Static Smuggle cost and aspect-bracket lookup for all SHD Smuggle cards.
// Ported from smuggle.ts in the Karabast reference.
$smuggleCostData = [
    'SHD_032' => 5, 'SHD_036' => 4, 'SHD_050' => 9, 'SHD_052' => 6,
    'SHD_065' => 7, 'SHD_075' => 3, 'SHD_086' => 4, 'SHD_089' => 7,
    'SHD_097' => 4, 'SHD_107' => 6, 'SHD_111' => 3, 'SHD_113' => 6,
    'SHD_119' => 5, 'SHD_127' => 3, 'SHD_129' => 2, 'SHD_148' => 5,
    'SHD_149' => 5, 'SHD_160' => 3, 'SHD_174' => 3, 'SHD_175' => 4,
    'SHD_184' => 4, 'SHD_197' => 4, 'SHD_201' => 6, 'SHD_203' => 6,
    'SHD_204' => 6, 'SHD_213' => 7, 'SHD_215' => 4, 'SHD_217' => 5,
    'SHD_225' => 4, 'SHD_248' => 4, 'SHD_252' => 3,
];

// Aspect brackets for each Smuggle card's Smuggle keyword (CR 8.22).
// These are the aspects checked when paying via own Smuggle — NOT the card's printed aspects.
$smuggleAspectsData = [
    'SHD_032' => ['Vigilance', 'Villainy'],
    'SHD_050' => ['Aggression', 'Heroism'],
    'SHD_052' => ['Vigilance'],
    'SHD_065' => ['Vigilance'],
    'SHD_075' => ['Vigilance'],
    'SHD_086' => ['Command', 'Villainy'],
    'SHD_089' => ['Command', 'Villainy'],
    'SHD_097' => ['Command', 'Heroism'],
    'SHD_107' => ['Command', 'Command'],
    'SHD_111' => ['Command'],
    'SHD_113' => ['Command'],
    'SHD_119' => ['Command'],
    'SHD_127' => ['Command'],
    'SHD_129' => ['Command'],
    'SHD_148' => ['Aggression', 'Heroism'],
    'SHD_149' => ['Aggression', 'Heroism'],
    'SHD_160' => ['Aggression'],
    'SHD_174' => ['Cunning'],
    'SHD_175' => ['Aggression'],
    'SHD_184' => ['Cunning', 'Villainy'],
    'SHD_197' => ['Cunning', 'Heroism'],
    'SHD_201' => ['Cunning', 'Heroism'],
    'SHD_203' => ['Cunning', 'Heroism'],
    'SHD_204' => ['Cunning', 'Heroism'],
    'SHD_213' => ['Cunning', 'Cunning'],
    'SHD_215' => ['Cunning'],
    'SHD_217' => ['Vigilance'],
    'SHD_225' => ['Cunning'],
    'SHD_248' => ['Heroism'],
    'SHD_252' => ['Heroism'],
];

function GetSmuggleAspects(string $cardID): array {
    global $smuggleAspectsData;
    return $smuggleAspectsData[$cardID] ?? [];
}

// Returns true if the player controls SHD_248 (Tech) in their ground arena.
function PlayerHasTechInPlay(int $player): bool {
    $ground = &GetGroundArena($player);
    for ($i = 0; $i < count($ground); $i++) {
        $obj = $ground[$i];
        if (isset($obj->removed) && $obj->removed) continue;
        if (($obj->CardID ?? '') === 'SHD_248' && intval($obj->Controller ?? $player) === $player) return true;
    }
    return false;
}

// Aspect penalty for a Smuggle play using the card's Smuggle bracket aspects.
// Mirrors SWUAspectPenalty but uses the Smuggle keyword's own aspect list.
function SWUSmuggleAspectPenalty(int $player, string $cardID): int {
    $aspects = GetSmuggleAspects($cardID);
    if (empty($aspects)) return 0;
    $provided = PlayerAspects($player);
    $penalty  = 0;
    foreach ($aspects as $need) {
        $idx = array_search($need, $provided);
        if ($idx !== false) {
            array_splice($provided, $idx, 1);
        } else {
            $penalty += 2;
        }
    }
    return $penalty;
}

// Returns the effective Smuggle cost for $player to play $cardID from the resource zone.
// Evaluates both paths (native Smuggle + Tech) and returns the minimum.
// Native path:  SmuggleCost + SWUSmuggleAspectPenalty  (Smuggle bracket aspects)
// Tech path:    CardCost + 2 + SWUAspectPenalty         (printed card aspects)
// Returns -1 when no Smuggle path is available.
function GetEffectiveSmuggleCost(int $player, string $cardID): int {
    global $smuggleCostData;
    $best  = PHP_INT_MAX;
    $found = false;

    if (isset($smuggleCostData[$cardID])) {
        $cost  = $smuggleCostData[$cardID] + SWUSmuggleAspectPenalty($player, $cardID);
        $best  = min($best, $cost);
        $found = true;
    }

    if (PlayerHasTechInPlay($player)) {
        $printed = intval(CardCost($cardID) ?? -1);
        if ($printed >= 0) {
            $cost  = $printed + 2 + SWUAspectPenalty($player, $cardID);
            $best  = min($best, $cost);
            $found = true;
        }
    }

    return $found ? $best : -1;
}

// Display flag for the resource-zone Smuggle icon (schema Virtual: HasSmuggle).
// Returns 1 when this resource currently has a Smuggle path — printed Smuggle or
// granted (e.g. SHD_248 Tech makes every friendly resource Smuggle). Keyed off the
// resource's owning player ($obj->PlayerID), matching the smuggle-PLAY detection;
// the per-object Controller field isn't reliably populated on resource objects, so
// HasKeyword_Smuggle($obj) (which reads Controller) reports false here. Returns int
// 1/0 for the Image counter (ShowZero=false hides 0).
function ResourceHasSmuggle($obj): int {
    if (!isset($obj->CardID) || !isset($obj->PlayerID)) return 0;
    if (SWUIsCreditToken($obj->CardID)) return 0; // Credit tokens aren't resources
    return GetEffectiveSmuggleCost(intval($obj->PlayerID), $obj->CardID) >= 0 ? 1 : 0;
}
