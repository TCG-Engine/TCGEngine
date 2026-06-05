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

    public function MyLeader(string $cardID, bool $ready = true, bool $deployed = false, bool $epicActionUsed = false): self {
        $this->_myLeader = ['cardID' => $cardID, 'ready' => $ready, 'deployed' => $deployed, 'epicActionUsed' => $epicActionUsed];
        return $this;
    }

    public function TheirLeader(string $cardID, bool $ready = true, bool $deployed = false, bool $epicActionUsed = false): self {
        $this->_theirLeader = ['cardID' => $cardID, 'ready' => $ready, 'deployed' => $deployed, 'epicActionUsed' => $epicActionUsed];
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

    public function WithGroundUnitForPlayer(int $player, string $cardID, bool $ready = true, int $damage = 0, int $controller = 0): self {
        $this->_groundUnits[$player][] = [
            'cardID'     => $cardID,
            'ready'      => $ready,
            'damage'     => $damage,
            'controller' => $controller ?: $player,
            'upgrades'   => [],
        ];
        return $this;
    }

    public function WithSpaceUnitForPlayer(int $player, string $cardID, bool $ready = true, int $damage = 0, int $controller = 0): self {
        $this->_spaceUnits[$player][] = [
            'cardID'     => $cardID,
            'ready'      => $ready,
            'damage'     => $damage,
            'controller' => $controller ?: $player,
            'upgrades'   => [],
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

        // Bases
        if (!empty($this->_myBase)) {
            $b = $this->_myBase;
            AddBase(1, $b['cardID'], $b['damage'], $b['epicActionUsed'], $b['numUses']);
        }
        if (!empty($this->_theirBase)) {
            $b = $this->_theirBase;
            AddBase(2, $b['cardID'], $b['damage'], $b['epicActionUsed'], $b['numUses']);
        }

        // Leaders — AddLeader signature: (player, CardID, EpicActionUsed, Ready, Deployed, ...)
        if (!empty($this->_myLeader)) {
            $l = $this->_myLeader;
            AddLeader(1, $l['cardID'], $l['epicActionUsed'], $l['ready'], $l['deployed']);
        }
        if (!empty($this->_theirLeader)) {
            $l = $this->_theirLeader;
            AddLeader(2, $l['cardID'], $l['epicActionUsed'], $l['ready'], $l['deployed']);
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
                               $unit['controller'], '-', $subcards, $uid);
            }
            foreach ($this->_spaceUnits[$player] as $unit) {
                $uid      = $this->_nextUID++;
                $status   = $unit['ready'] ? 1 : 0;
                $subcards = empty($unit['upgrades']) ? '-' : $unit['upgrades'];
                AddSpaceArena($player, $unit['cardID'], $status, $player, $unit['damage'],
                              $unit['controller'], '-', $subcards, $uid);
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

        // Sync unique ID counter so NextUniqueID() doesn't collide
        global $gUniqueIDCounter;
        $gUniqueIDCounter = $this->_nextUID;
    }
}
