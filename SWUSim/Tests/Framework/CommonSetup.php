<?php

// ═══════════════════════════════════════════════════════════════════
// CommonSetup — quick GameStateBuilder wiring via 3-char color codes.
//
// Color → Aspect mapping (BGRYKW + N):
//   B = Blue    = Vigilance
//   G = Green   = Command
//   R = Red     = Aggression
//   Y = Yellow  = Cunning
//   K = Black   = Villainy  (leader alignment)
//   W = White   = Heroism   (leader alignment)
//   N = Neutral = no aspect (bases only)
//
// Code format: {base_color}{leader_aspect}{leader_alignment}
//   e.g. "grw" = Command base + Aggression/Heroism leader
//                → SOR_024 (Echo Base) + SOR_014 (Sabine Wren)
//
// Canonical leader choices (SOR, most iconic per combo):
//   bk = Vigilance+Villainy  → SOR_002 Iden Versio
//   bw = Vigilance+Heroism   → SOR_005 Luke Skywalker
//   gk = Command+Villainy    → SOR_007 Grand Moff Tarkin
//   gw = Command+Heroism     → SOR_009 Leia Organa
//   rk = Aggression+Villainy → SOR_010 Darth Vader
//   rw = Aggression+Heroism  → SOR_014 Sabine Wren
//   yk = Cunning+Villainy    → SOR_016 Grand Admiral Thrawn
//   yw = Cunning+Heroism     → SOR_017 Han Solo
//
// Canonical base choices (simple 30 HP, no abilities):
//   b → SOR_020 Capital City       (Vigilance)
//   g → SOR_024 Echo Base          (Command)
//   r → SOR_026 Catacombs of Cadera (Aggression)
//   y → SOR_029 Administrator's Tower (Cunning)
//   n → JTL_031 Lake Country       (no aspect)
// ═══════════════════════════════════════════════════════════════════

function CommonSetup(
    GameStateBuilder $b,
    string $myCode,
    string $theirCode = "grw",
    array $myOpts   = [],
    array $theirOpts = []
): GameStateBuilder {
    [$myLeaderID, $myBaseID]     = _resolveLeaderBase($myCode);
    [$theirLeaderID, $theirBaseID] = _resolveLeaderBase($theirCode);

    // Explicit leader override (opt 'myLeader'/'theirLeader'): the 3-char code still drives the base,
    // but the leader is swapped to any cardID — e.g. a JTL pilot leader for myLeaderDeployedPilot.
    $myLeaderID    = $myOpts['leaderCardID']    ?? $myLeaderID;
    $theirLeaderID = $theirOpts['leaderCardID'] ?? $theirLeaderID;

    // Explicit base override (opt 'myBase'/'theirBase'): the 3-char code's base letter still drives the
    // color, but the base card itself is swapped to any cardID — needed for the many non-canonical bases
    // (different HP, Epic Actions, Force triggers, starting-hand modifiers) that the 5 color bases can't
    // stand in for.
    $myBaseID    = $myOpts['baseCardID']    ?? $myBaseID;
    $theirBaseID = $theirOpts['baseCardID'] ?? $theirBaseID;

    // Deploy mode: 'pilot' (attach as a Pilot upgrade onto a host Vehicle) takes precedence over
    // 'unit' (place a real ground-arena leader unit). Either implies the leader's Deployed flag.
    $myDeployMode    = !empty($myOpts['leaderDeployedPilot'])    ? 'pilot'
                     : (!empty($myOpts['leaderDeployed'])        ? 'unit' : '');
    $theirDeployMode = !empty($theirOpts['leaderDeployedPilot']) ? 'pilot'
                     : (!empty($theirOpts['leaderDeployed'])     ? 'unit' : '');

    // Deployed flag decoupled from deploy mode: 'leaderDeployedFlag' sets Leader.Deployed=true WITHOUT
    // any board presence (deployMode=''), matching the legacy P1LeaderBase `:deployed` field where the
    // deployed leader unit is declared separately via WithP{n}GroundArena. Any real deploy mode also
    // implies the flag.
    $myDeployedFlag    = $myDeployMode !== ''    || !empty($myOpts['leaderDeployedFlag']);
    $theirDeployedFlag = $theirDeployMode !== '' || !empty($theirOpts['leaderDeployedFlag']);

    $b->MyBase($myBaseID,
            $myOpts['baseDamage']          ?? 0,
            $myOpts['baseEpicActionUsed']  ?? false,
            $myOpts['baseNumUses']         ?? 0)
      ->MyLeader($myLeaderID,
            $myOpts['leaderReady']         ?? true,
            $myDeployedFlag,
            $myOpts['leaderEpicActionUsed'] ?? false,
            $myDeployMode,
            $myOpts['leaderDamage']        ?? 0)
      ->TheirBase($theirBaseID,
            $theirOpts['baseDamage']          ?? 0,
            $theirOpts['baseEpicActionUsed']  ?? false,
            $theirOpts['baseNumUses']         ?? 0)
      ->TheirLeader($theirLeaderID,
            $theirOpts['leaderReady']         ?? true,
            $theirDeployedFlag,
            $theirOpts['leaderEpicActionUsed'] ?? false,
            $theirDeployMode,
            $theirOpts['leaderDamage']        ?? 0);

    $vanilla = Cards::UNITS_SOR_BATTLEFIELD_MARINE;

    if (!empty($myOpts['resourceCount'])) {
        $b->FillResourcesForPlayer(1, $vanilla, $myOpts['resourceCount']);
    }
    if (!empty($theirOpts['resourceCount'])) {
        $b->FillResourcesForPlayer(2, $vanilla, $theirOpts['resourceCount']);
    }

    foreach ($myOpts['handCardIds'] ?? [] as $id) {
        $b->WithCardInHandForPlayer(1, $id);
    }
    foreach ($theirOpts['handCardIds'] ?? [] as $id) {
        $b->WithCardInHandForPlayer(2, $id);
    }

    foreach ($myOpts['discardCardIds'] ?? [] as $id) {
        $b->WithCardInDiscardForPlayer(1, $id);
    }
    foreach ($theirOpts['discardCardIds'] ?? [] as $id) {
        $b->WithCardInDiscardForPlayer(2, $id);
    }

    return $b;
}

// ── Internal resolver ────────────────────────────────────────────────────────

function _resolveLeaderBase(string $code): array {
    if (strlen($code) !== 3) {
        throw new InvalidArgumentException("CommonSetup: code must be 3 chars, got '$code'");
    }

    // Lazy-init: class constants can't be used in static var initializers pre-PHP-8.1.
    static $baseMap   = null;
    static $leaderMap = null;
    if ($baseMap === null) {
        $baseMap = [
            'b' => Cards::BASES_COMMON_BLUE_30HP,    // SOR_020 Capital City        — Vigilance
            'g' => Cards::BASES_COMMON_GREEN_30HP,   // SOR_024 Echo Base          — Command
            'r' => Cards::BASES_COMMON_RED_30HP,     // SOR_026 Catacombs of Cadera — Aggression
            'y' => Cards::BASES_COMMON_YELLOW_30HP,  // SOR_029 Administrator's Tower — Cunning
            'n' => Cards::BASES_JTL_LAKE_COUNTRY,    // JTL_031 Lake Country         — no aspect
        ];
        $leaderMap = [
            // SOR canonical (most iconic leader per aspect+alignment combo)
            'bk' => Cards::LEADERS_SOR_IDEN_VERSIO,           // Vigilance+Villainy
            'bw' => Cards::LEADERS_SOR_LUKE_SKYWALKER,       // Vigilance+Heroism
            'gk' => Cards::LEADERS_SOR_GRAND_MOFF_TARKIN,    // Command+Villainy
            'gw' => Cards::LEADERS_SOR_LEIA_ORGANA,          // Command+Heroism
            'rk' => Cards::LEADERS_SOR_DARTH_VADER,          // Aggression+Villainy
            'rw' => Cards::LEADERS_SOR_SABINE_WREN,          // Aggression+Heroism
            'yk' => Cards::LEADERS_SOR_GRAND_ADMIRAL_THRAWN, // Cunning+Villainy
            'yw' => Cards::LEADERS_SOR_HAN_SOLO,             // Cunning+Heroism
        ];
    }

    $baseCode   = strtolower($code[0]);
    $leaderCode = strtolower(substr($code, 1));

    if (!isset($baseMap[$baseCode])) {
        throw new InvalidArgumentException("CommonSetup: unknown base code '$baseCode' in '$code'");
    }
    if (!isset($leaderMap[$leaderCode])) {
        throw new InvalidArgumentException("CommonSetup: unknown leader code '$leaderCode' in '$code'");
    }

    return [$leaderMap[$leaderCode], $baseMap[$baseCode]];
}
