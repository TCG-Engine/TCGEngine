<?php
// Core/DecisionQueueController.php
// Helper class for managing player decision queues in the game engine.
// ASSUMES: You have a per-player zone named "DecisionQueue"

class DecisionQueueController {
    private $numPlayers = 2;

    public function __construct() {

    }

    // Returns true if both players' queues are empty
    public function AllQueuesEmpty() {
        for($i=1; $i<=$this->numPlayers; ++$i) {
            $playerQueue = &GetDecisionQueue($i);
            if(!empty($playerQueue)) {
                return false;
            }
        }
        return true;
    }

    // Returns true if either player has a pending decision
    public function AnyQueuePending() {
        return !AllQueuesEmpty();
    }

    // Get the next decision for a player (returns null if none)
    public function NextDecision($player) {
        $playerQueue = &GetDecisionQueue($player);
        if (!empty($playerQueue)) {
            return $playerQueue[0];
        }
        return null;
    }

    // Remove the first decision for a player (after processing)
    public function PopDecision($player) {
        $playerQueue = &GetDecisionQueue($player);
        if (!empty($playerQueue)) {
            array_shift($playerQueue);
        }
    }

    // Add a decision to a player's queue
    public function AddDecision($player, $type, $param = '', $block = 0) {
        $playerQueue = &GetDecisionQueue($player);
        $playerQueue[] = new DecisionQueue($type . " " . $param . " " . $block);
    }
}