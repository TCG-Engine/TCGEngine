<?php

  class Player implements JsonSerializable {
    private $playerID;
    private $deckLink;
    private $authKey;
    private $gamePlayerID; // This is the ID used in the game, not the lobby
    
    public function __construct($playerID, $deckLink) {
        $this->playerID = $playerID;
        $this->deckLink = $deckLink;
        $this->authKey = bin2hex(random_bytes(16)); // Generate a unique auth key
    }

    public function getPlayerID() {
        return $this->playerID;
    }

    public function getDeckLink() {
        return $this->deckLink;
    }

    public function getAuthKey() {
        return $this->authKey;
    }

    public function getGamePlayerID() {
        return $this->gamePlayerID;
    }
    
    public function setGamePlayerID($gamePlayerID) {
        $this->gamePlayerID = $gamePlayerID;
    }

    // We should never be arbitrarily changing the player ID or authkey once created
    public function setDeckLink($deckLink) {
        $this->deckLink = $deckLink;
    }

    public function jsonSerialize() {
        return [
            'playerID' => $this->getPlayerID(),
            'deckLink' => $this->getDeckLink(),
            'authKey' => $this->getAuthKey()
        ];
    }
  }

?>
