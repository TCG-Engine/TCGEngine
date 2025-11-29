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
            return array_shift($playerQueue);
        }
        return null;
    }

    function ExecuteStaticMethods($player, $lastDecision = null) {
        $playerQueue = &GetDecisionQueue($player);
        while($decision = $this->NextDecision($player)) {
            switch($decision->Type) {
                case "MZMOVE":
                    if($lastDecision == "PASS") break;
                    $resolvedParam = str_replace("{<-}", $lastDecision, $decision->Param);
                    $parts = explode("->", $resolvedParam);
                    $source = $parts[0];
                    $destination = explode("-", $parts[1])[0];
                    echo("Source: " . $source . " Dest: " . $destination . "<BR>");
                    $removed = GetZoneObject($source);
                    $removed->Remove();
                    echo("Moving CardID " . $removed->CardID . "<BR>");
                    MZAddZone($player, $destination, $removed->CardID);
                    break;
                case "CUSTOM":
                    if($lastDecision == "PASS") break;
                    global $customDQHandlers;
                    $parts = explode("|", $decision->Param);
                    $handlerName = array_shift($parts);
                    $customDQHandlers[$handlerName]($player, $parts, $lastDecision);
                    break;
                default:
                    // Not static, return
                    $numChoices = 0;
                    $zones = $this->MZZoneArray($decision->Param);
                    foreach($zones as $zoneName) {
                        $numChoices+=MZZoneCount($zoneName);
                    }
                    if($numChoices === 0) {
                        // No valid choices, auto-PASS
                        $this->PopDecision($player);
                        $lastDecision = "PASS";
                        break;
                    } else return;
            }
            $this->PopDecision($player);
        }
        AutoAdvance();
    }

    // Add a decision to a player's queue
    public function AddDecision($player, $type, $param = '', $block = 0, $tooltip = '') {
        $tooltip = str_replace(' ', '_', $tooltip);
        $playerQueue = &GetDecisionQueue($player);
        $playerQueue[] = new DecisionQueue($type . " " . $param . " " . $block . " " . $tooltip);
    }

    private function MZZoneArray($zoneStr) {
        $zones = explode("&", $zoneStr);
        $output = [];
        for($i=0; $i<count($zones); ++$i) {
            $zone = explode(":", $zones[$i]);
            $output[] = $zone[0];
        }
        return $output;
    }
}