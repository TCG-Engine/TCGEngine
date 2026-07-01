<?php

class GameStateBuilder {
    private int    $_activePlayer    = 1;
    private string $_phase           = 'MAIN';
    private int    $_round           = 1;
    private int    $_initiativePlayer = 1;
    private bool   $_initiativeClaimed = false;
    private array  $_myBase          = [];
    private array  $_theirBase       = [];
    private array  $_myLeader        = [];
    private array  $_theirLeader     = [];
    private array  $_resources       = []; // [[player, cardID, count, allReady, controller]]
    private array  $_hand            = []; // [[player, cardID]]
    private array  $_deck            = []; // [[player, cardID]]
    private array  $_discard         = []; // [[player, cardID]]
    private array  $_groundUnits     = [1 => [], 2 => []];
    private array  $_spaceUnits      = [1 => [], 2 => []];
    private array  $_defeatedPlayers = [];
    private array  $_forcePlayers    = []; // players who control their Force token (CR §37)
    private int    $_nextUID         = 1;

    // ── Turn context ─────────────────────────────────────────────

    public function WithActivePlayer(int $player): self {
        $this->_activePlayer = $player;
        return $this;
    }

    public function WithGamePhase(string $phase): self {
        // 'ActionPhase' maps to engine's 'MAIN'
        $this->_phase = ($phase === 'ActionPhase') ? 'MAIN' : $phase;
        return $this;
    }

    public function WithCurrentRoundBeing(int $round): self {
        $this->_round = $round;
        return $this;
    }

    public function WithInitiativePlayerBeing(int $player): self {
        $this->_initiativePlayer = $player;
        return $this;
    }

    public function WithInitiativeClaimed(): self {
        $this->_initiativeClaimed = true;
        return $this;
    }

    // ── Bases ─────────────────────────────────────────────────────

    public function MyBase(string $cardID, int $damage = 0, bool $epicActionUsed = false, int $numUses = 0): self {
        $this->_myBase = ['cardID' => $cardID, 'damage' => $damage, 'epicActionUsed' => $epicActionUsed, 'numUses' => $numUses];
        return $this;
    }

    public function TheirBase(string $cardID, int $damage = 0, bool $epicActionUsed = false, int $numUses = 0): self {
        $this->_theirBase = ['cardID' => $cardID, 'damage' => $damage, 'epicActionUsed' => $epicActionUsed, 'numUses' => $numUses];
        return $this;
    }

    // ── Leaders ───────────────────────────────────────────────────
    // arg order matches TS: (cardID, ready=true, deployed=false, epicActionUsed=false)

    // $deployMode: '' = leader side only (no board presence); 'unit' = also place a real
    // ground-arena leader unit linked via DeployedUniqueID; 'pilot' = attach the leader as a
    // Pilot upgrade onto the player's first friendly arena unit (host Vehicle).
    // $damage applies to the deployed leader UNIT (deployMode='unit'); ignored otherwise (an
    // undeployed leader and a Pilot-attached leader carry no unit damage of their own).
    // $indexOverride (>=0): ground-arena position to insert the deployed leader unit at, shifting the
    // other units up. ONLY honored for deployMode='unit' (a real deployed leader unit); ignored for
    // pilot/flag/undeployed. -1 = append at the end (default, prior behavior).
    public function MyLeader(string $cardID, bool $ready = true, bool $deployed = false, bool $epicActionUsed = false, string $deployMode = '', int $damage = 0, int $indexOverride = -1): self {
        $this->_myLeader = ['cardID' => $cardID, 'ready' => $ready, 'deployed' => $deployed, 'epicActionUsed' => $epicActionUsed, 'deployMode' => $deployMode, 'damage' => $damage, 'indexOverride' => $indexOverride];
        return $this;
    }

    public function TheirLeader(string $cardID, bool $ready = true, bool $deployed = false, bool $epicActionUsed = false, string $deployMode = '', int $damage = 0, int $indexOverride = -1): self {
        $this->_theirLeader = ['cardID' => $cardID, 'ready' => $ready, 'deployed' => $deployed, 'epicActionUsed' => $epicActionUsed, 'deployMode' => $deployMode, 'damage' => $damage, 'indexOverride' => $indexOverride];
        return $this;
    }

    // ── Resources ─────────────────────────────────────────────────

    public function FillResourcesForPlayer(int $player, string $cardID, int $count, bool $allReady = true, int $controller = 0): self {
        $this->_resources[] = [
            'player'     => $player,
            'cardID'     => $cardID,
            'count'      => $count,
            'allReady'   => $allReady,
            'controller' => $controller ?: $player,
        ];
        return $this;
    }

    // ── Hand / Deck ───────────────────────────────────────────────

    public function WithCardInHandForPlayer(int $player, string $cardID): self {
        $this->_hand[] = ['player' => $player, 'cardID' => $cardID];
        return $this;
    }

    public function WithCardInDeckForPlayer(int $player, string $cardID): self {
        $this->_deck[] = ['player' => $player, 'cardID' => $cardID];
        return $this;
    }

    public function WithCardInDiscardForPlayer(int $player, string $cardID): self {
        $this->_discard[] = ['player' => $player, 'cardID' => $cardID];
        return $this;
    }

    // ── Arena units ───────────────────────────────────────────────
    // ready=true → Status 1 (ready); ready=false → Status 0 (exhausted)
    // controller=0 → same as owner ($player)

    // $turnEffects: '~'-delimited active TurnEffects on the unit (e.g. "LOF_045~SENTINEL^SEC_041"),
    // or "-" for none. Serialized straight into the arena entry's TurnEffects field.
    public function WithGroundUnitForPlayer(int $player, string $cardID, bool $ready = true, int $damage = 0, int $controller = 0, string $turnEffects = '-'): self {
        $this->_groundUnits[$player][] = [
            'cardID'      => $cardID,
            'ready'       => $ready,
            'damage'      => $damage,
            'controller'  => $controller ?: $player,
            'upgrades'    => [],
            'turnEffects' => $turnEffects,
        ];
        return $this;
    }

    public function WithSpaceUnitForPlayer(int $player, string $cardID, bool $ready = true, int $damage = 0, int $controller = 0, string $turnEffects = '-'): self {
        $this->_spaceUnits[$player][] = [
            'cardID'      => $cardID,
            'ready'       => $ready,
            'damage'      => $damage,
            'controller'  => $controller ?: $player,
            'upgrades'    => [],
            'turnEffects' => $turnEffects,
        ];
        return $this;
    }

    // ── Upgrades ──────────────────────────────────────────────────
    // Call AFTER the unit-adding method. unitIndex = 0-based index of the unit for that player.

    public function WithUpgradesOnGroundUnitForPlayer(int $player, int $unitIndex, array $upgrades): self {
        $this->_groundUnits[$player][$unitIndex]['upgrades'] = $upgrades;
        return $this;
    }

    public function WithUpgradesOnSpaceUnitForPlayer(int $player, int $unitIndex, array $upgrades): self {
        $this->_spaceUnits[$player][$unitIndex]['upgrades'] = $upgrades;
        return $this;
    }

    // ── Win condition ─────────────────────────────────────────────

    public function WithDefeatedPlayer(int $player): self {
        $this->_defeatedPlayers[] = $player;
        return $this;
    }

    // Grant a player control of their Force token (CR §37) — sets the SWU_HAS_FORCE flag at build.
    public function WithForceForPlayer(int $player): self {
        $this->_forcePlayers[] = $player;
        return $this;
    }

    // ── Static helper ─────────────────────────────────────────────

    public static function Upgrade(string $cardID, int $player, int $owner = 0): array {
        return [
            'CardID'      => $cardID,
            'Owner'       => $owner ?: $player,
            'Controller'  => $player,
            'TurnEffects' => [],
            'IsPilot'     => false,
        ];
    }

    // ── Build ─────────────────────────────────────────────────────

    public function Build(): self {
        $this->_applyToGlobals();
        return $this;
    }

    public function _applyToGlobals(): void {
        InitializeGamestate();

        // Turn context
        AddTurnPlayer($this->_activePlayer);
        AddFirstPlayer($this->_activePlayer);
        AddTurnNumber($this->_round);
        AddCurrentPhase($this->_phase);

        // Initiative counter — P{n}_CLAIMED or P{n}_UNCLAIMED
        $suffix = $this->_initiativeClaimed ? 'CLAIMED' : 'UNCLAIMED';
        AddInitiativeCounter("P{$this->_initiativePlayer}_{$suffix}");

        // Bases. Seed the per-game use budget for repeatable base Actions (e.g. LOF_022) when the test
        // didn't set numUses explicitly, mirroring CreateGame so the harness and real game match.
        global $baseActionNumUses;
        if (!empty($this->_myBase)) {
            $b  = $this->_myBase;
            $nu = ($b['numUses'] === 0 && isset($baseActionNumUses[$b['cardID']]))
                ? intval($baseActionNumUses[$b['cardID']]) : $b['numUses'];
            AddBase(1, $b['cardID'], $b['damage'], $b['epicActionUsed'], $nu);
        }
        if (!empty($this->_theirBase)) {
            $b  = $this->_theirBase;
            $nu = ($b['numUses'] === 0 && isset($baseActionNumUses[$b['cardID']]))
                ? intval($baseActionNumUses[$b['cardID']]) : $b['numUses'];
            AddBase(2, $b['cardID'], $b['damage'], $b['epicActionUsed'], $nu);
        }

        // Leaders — AddLeader signature: (player, CardID, EpicActionUsed, Ready, Deployed, ...)
        $leaderObjs = [1 => null, 2 => null];
        if (!empty($this->_myLeader)) {
            $l = $this->_myLeader;
            $leaderObjs[1] = AddLeader(1, $l['cardID'], $l['epicActionUsed'], $l['ready'], $l['deployed']);
        }
        if (!empty($this->_theirLeader)) {
            $l = $this->_theirLeader;
            $leaderObjs[2] = AddLeader(2, $l['cardID'], $l['epicActionUsed'], $l['ready'], $l['deployed']);
        }

        // Deployed-leader Pilot mode (CR Pilot): attach the leader as a Pilot upgrade onto the
        // player's first friendly arena unit (host Vehicle) BEFORE the arena loop materializes it,
        // so the host's Subcards carry the pilot and IsLeaderUnit() recognizes it. DeployedUniqueID
        // stays 0 (the leader is a Subcard, not a standalone arena unit). No host unit → no-op.
        foreach ([1 => $this->_myLeader, 2 => $this->_theirLeader] as $player => $leader) {
            if (($leader['deployMode'] ?? '') !== 'pilot') continue;
            $pilot = self::Upgrade($leader['cardID'], $player);
            $pilot['IsPilot'] = true;
            if (!empty($this->_groundUnits[$player])) {
                $this->_groundUnits[$player][0]['upgrades'][] = $pilot;
            } elseif (!empty($this->_spaceUnits[$player])) {
                $this->_spaceUnits[$player][0]['upgrades'][] = $pilot;
            }
        }

        // Resources — Status 1=ready, 0=exhausted
        foreach ($this->_resources as $r) {
            $status = $r['allReady'] ? 1 : 0;
            for ($i = 0; $i < $r['count']; $i++) {
                AddResources($r['player'], $r['cardID'], $status);
            }
        }

        // Hand
        foreach ($this->_hand as $h) {
            AddHand($h['player'], $h['cardID']);
        }

        // Deck
        foreach ($this->_deck as $d) {
            AddDeck($d['player'], $d['cardID']);
        }

        // Discard (From='PLAY' — seeded as if defeated/resolved into the pile)
        foreach ($this->_discard as $d) {
            AddDiscard($d['player'], $d['cardID'], 'PLAY');
        }

        // Arena units — Status 1=ready, 0=exhausted; units placed by builder start in correct state
        foreach ([1, 2] as $player) {
            foreach ($this->_groundUnits[$player] as $unit) {
                $uid      = $this->_nextUID++;
                $status   = $unit['ready'] ? 1 : 0;
                $subcards = empty($unit['upgrades']) ? '-' : $unit['upgrades'];
                AddGroundArena($player, $unit['cardID'], $status, $player, $unit['damage'],
                               $unit['controller'], $unit['turnEffects'] ?? '-', $subcards, $uid);
            }
            foreach ($this->_spaceUnits[$player] as $unit) {
                $uid      = $this->_nextUID++;
                $status   = $unit['ready'] ? 1 : 0;
                $subcards = empty($unit['upgrades']) ? '-' : $unit['upgrades'];
                AddSpaceArena($player, $unit['cardID'], $status, $player, $unit['damage'],
                              $unit['controller'], $unit['turnEffects'] ?? '-', $subcards, $uid);
            }
        }

        // Deployed-leader Unit mode: place the leader as a real ground-arena unit (after any
        // GIVEN-declared units, so they keep their indices) and link it via DeployedUniqueID —
        // matching SWUDeployLeader's Unit branch. The arena unit's ready state follows the leader
        // spec's `ready`; a Leader CardID in an arena is auto-recognized by IsLeaderUnit().
        foreach ([1, 2] as $player) {
            $leader = $player === 1 ? $this->_myLeader : $this->_theirLeader;
            if (($leader['deployMode'] ?? '') !== 'unit' || $leaderObjs[$player] === null) continue;
            $uid    = $this->_nextUID++;
            $status = $leader['ready'] ? 1 : 0;
            AddGroundArena($player, $leader['cardID'], $status, $player, $leader['damage'] ?? 0, $player, '-', '-', $uid);
            $leaderObjs[$player]->DeployedUniqueID = $uid;

            // indexOverride: scoot the just-appended leader unit to a specific ground-arena index,
            // shifting the GIVEN-declared units up. Reindex mzIndex so it matches array position.
            $override = intval($leader['indexOverride'] ?? -1);
            if ($override >= 0) {
                $zone = &GetGroundArena($player);
                $leaderUnit = array_pop($zone);                    // remove from the end
                $pos = max(0, min($override, count($zone)));       // clamp into range
                array_splice($zone, $pos, 0, [$leaderUnit]);
                for ($i = 0; $i < count($zone); $i++) $zone[$i]->mzIndex = $i;
                unset($zone);
            }
        }

        // Defeated players — set base damage = base HP
        foreach ($this->_defeatedPlayers as $dp) {
            $base = &GetBase($dp);
            for ($i = 0; $i < count($base); $i++) {
                if (isset($base[$i]->removed) && $base[$i]->removed) continue;
                $base[$i]->Damage = intval(CardHp($base[$i]->CardID));
                global $gWinner;
                $gWinner = $dp === 1 ? 2 : 1;
                break;
            }
        }

        // The Force token (CR §37) — player state via the SWU_HAS_FORCE GlobalEffects flag.
        foreach ($this->_forcePlayers as $fp) {
            TheForceIsWithYou(intval($fp));
        }

        // Sync unique ID counter so NextUniqueID() doesn't collide
        global $gUniqueIDCounter;
        $gUniqueIDCounter = $this->_nextUID;
    }
}
