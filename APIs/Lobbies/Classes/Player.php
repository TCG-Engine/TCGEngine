<?php

  class Player implements JsonSerializable {
    private $playerID;
    private $deckLink;
    private $preconstructedDeck;
    private $authKey;
    private $gamePlayerID; // This is the ID used in the game, not the lobby
    private $userId; // account id of the human who created this seat (null for guests/bots)
    private $deckOk = false; // Twin Suns room roster: whether this seat's current deck passed format legality
    private $seat = null;  // Table position 1..4. NULL until a team is picked. Team Suns reassigns
                           // this freely; $playerID must NOT move, because endpoints authenticate on it.
    private $team = null;  // 'red' | 'blue' | null (unassigned)
    private $leaders = []; // Resolved leader CardIDs for this seat's current deck. Cached whenever
                           // the deck is validated, so the team leader-conflict check never
                           // re-resolves four decks on every roster poll.
    private $base = '';    // Resolved base CardID. DISPLAY ONLY (no rule reads it), cached beside the
                           // leaders so the room roster can show each seat's identity before start.
    private $lastSeen = 0;  // Unix ts of this seat's last poll. A seat that stops polling has closed its
                           // browser (or crashed, or lost the network) and is reaped — see
                           // SWUReapAbsentSeats. Set at JOIN too, or a seat that has not polled yet
                           // would be reaped the instant it sat down.
    private $ready = false; // Seat has pressed Ready. CLEARED whenever the seat's deck changes — a
                           // deck swapped after readying is not the deck anyone agreed to.
    private $identityCards = []; // [['id','name','url','kind'], …] — the roster's identity strip, built
                           // by the sim's LobbyAdapter. DISPLAY ONLY, and deliberately GENERIC: Player
                           // never knows what a leader is, which is what lets the shared page serve any
                           // sim. Cached beside the leaders because it is resolved where the card
                           // dictionary and the art seam are ALREADY loaded (deck validation) — the
                           // roster poll is a 30s long-poll and must not pull in a ~1MB dictionary to
                           // put a name on a thumbnail.

    public function __construct($playerID, $deckLink, $preconstructedDeck = '', $userId = null) {
        $this->playerID = $playerID;
        $this->deckLink = $deckLink;
        $this->preconstructedDeck = $preconstructedDeck;
        $this->userId = $userId;
        $this->authKey = bin2hex(random_bytes(16)); // Generate a unique auth key
    }

    public function getPlayerID() {
        return $this->playerID;
    }

    public function getDeckLink() {
        return $this->deckLink;
    }

    public function getPreconstructedDeck() {
        return $this->preconstructedDeck;
    }

    public function getAuthKey() {
        return $this->authKey;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getGamePlayerID() {
        return $this->gamePlayerID;
    }

    public function setGamePlayerID($gamePlayerID) {
        $this->gamePlayerID = $gamePlayerID;
    }

    // Only called from StartRoom.php to compact lobby seats 1..N before a game exists
    // (a mid-room leave can otherwise leave a gap) — never mid-game.
    public function setPlayerID($playerID) {
        $this->playerID = $playerID;
    }

    // We should never be arbitrarily changing the player ID or authkey once created
    public function setDeckLink($deckLink) {
        $this->deckLink = $deckLink;
    }

    public function setPreconstructedDeck($preconstructedDeck) {
        $this->preconstructedDeck = $preconstructedDeck;
    }

    public function getDeckOk() {
        return $this->deckOk;
    }

    public function setDeckOk($deckOk) {
        $this->deckOk = (bool)$deckOk;
    }

    public function getSeat() { return $this->seat; }
    public function setSeat($seat) { $this->seat = ($seat === null) ? null : intval($seat); }

    public function getTeam() { return $this->team; }
    public function setTeam($team) { $this->team = ($team === null) ? null : strval($team); }

    public function getLeaders() { return is_array($this->leaders) ? $this->leaders : []; }
    public function setLeaders($leaders) { $this->leaders = array_values(array_filter((array)$leaders, fn($l) => $l !== '' && $l !== null)); }

    public function getBase() { return is_string($this->base) ? $this->base : ''; }
    public function setBase($base) { $this->base = ($base === null) ? '' : strval($base); }

    public function getIdentityCards() { return is_array($this->identityCards) ? $this->identityCards : []; }

    // "I am happy with my deck and this matchup." Distinct from deckOk, which only says the deck is
    // LEGAL — a legal deck you are still swapping is not a deck you are ready to play.
    public function getLastSeen() { return intval($this->lastSeen); }
    public function touch($now = null) { $this->lastSeen = ($now === null) ? time() : intval($now); }

    public function getReady() { return !empty($this->ready); }
    public function setReady($v) { $this->ready = (bool)$v; }

    // Cache the deck IDENTITY a room roster displays: leader CardIDs, the base, and the display cards
    // the page renders.
    // ★ One call so the three can never drift — a seat showing last deck's base under this deck's
    // leaders is worse than showing nothing, and that is exactly what happens when a caller remembers
    // setLeaders() and forgets the others. Every failure path in the adapter passes empties here.
    public function setDeckIdentity($leaders, $base, array $cards = []): void {
        $this->setLeaders($leaders);
        $this->setBase($base);
        // Drop malformed rows rather than letting them reach the client as broken <img> tags. A row
        // needs an id and a url to render at all; the name falls back to the id.
        $clean = [];
        foreach ($cards as $c) {
            if (!is_array($c)) continue;
            $id  = strval($c['id']  ?? '');
            $url = strval($c['url'] ?? '');
            if ($id === '' || $url === '') continue;
            $name = strval($c['name'] ?? '');
            // ⚠ THIS IS A WHITELIST. A key the adapter sends but this does not name is silently
            // DROPPED — which is exactly how the aspect ring shipped grey: `colors` was built
            // correctly and thrown away here. Add a key in BOTH places, always.
            // Colours are validated to hex because the page interpolates them into a CSS
            // `background:` value; anything else is discarded rather than escaped.
            $colors = [];
            foreach ((array)($c['colors'] ?? []) as $col) {
                $col = strval($col);
                if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $col)) $colors[] = $col;
            }
            $clean[] = [
                'id'     => $id,
                'name'   => $name !== '' ? $name : $id,
                'url'    => $url,
                'kind'   => strval($c['kind'] ?? ''),
                'colors' => $colors,
            ];
        }
        $this->identityCards = $clean;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return [
            'playerID' => $this->getPlayerID(),
            'deckLink' => $this->getDeckLink(),
            'preconstructedDeck' => $this->getPreconstructedDeck(),
            'authKey' => $this->getAuthKey(),
            'userId' => $this->getUserId(),
            'seat' => $this->getSeat(),
            'team' => $this->getTeam()
        ];
    }
  }

?>
