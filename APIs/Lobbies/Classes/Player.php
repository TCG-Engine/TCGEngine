<?php

  class Player implements JsonSerializable {
    private $playerID;
    private $deckLink;
    private $preconstructedDeck;
    private $authKey;
    private $gamePlayerID; // This is the ID used in the game, not the lobby
    private $userId; // account id of the human who created this seat (null for guests/bots)

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

    // We should never be arbitrarily changing the player ID or authkey once created
    public function setDeckLink($deckLink) {
        $this->deckLink = $deckLink;
    }

    public function setPreconstructedDeck($preconstructedDeck) {
        $this->preconstructedDeck = $preconstructedDeck;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        return [
            'playerID' => $this->getPlayerID(),
            'deckLink' => $this->getDeckLink(),
            'preconstructedDeck' => $this->getPreconstructedDeck(),
            'authKey' => $this->getAuthKey(),
            'userId' => $this->getUserId()
        ];
    }
  }

?>
