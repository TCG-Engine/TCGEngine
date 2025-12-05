<?php
// Core/DecisionQueueController.php
// Helper class for managing player decision queues in the game engine.
// ASSUMES: You have a per-player zone named "DecisionQueue"

class DecisionQueueController {
    private $numPlayers = 2;
    private static $debugMode = true;

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
        while($decision = $this->NextDecision($player)) {
            if(self::$debugMode) echo("Processing decision for player " . $player . ": " . $decision->Type . " " . $decision->Param . " Last decision: " . $lastDecision . "<BR>");
            $this->PopDecision($player);
            switch($decision->Type) {
                case "PASSPARAMETER":
                    $lastDecision = $decision->Param;
                    break;
                case "MZMOVE":
                    if($lastDecision == "PASS") break;
                    $resolvedParam = str_replace("{<-}", $lastDecision, $decision->Param);
                    $parts = explode("->", $resolvedParam);
                    $source = $parts[0];
                    $destination = explode("-", $parts[1])[0];
                    $removed = GetZoneObject($source);
                    $removed->Remove();
                    MZAddZone($player, $destination, $removed->CardID);
                    break;
                case "CUSTOM":
                    if($lastDecision == "PASS") break;
                    global $customDQHandlers;
                    $parts = explode("|", $decision->Param);
                    $handlerName = array_shift($parts);
                    $customDQHandlers[$handlerName]($player, $parts, $lastDecision);
                    break;
                case "SYSTEM":
                    if($lastDecision == "PASS") break;
                    global $systemDQHandlers;
                    $parts = explode("|", $decision->Param);
                    $handlerName = array_shift($parts);
                    $systemDQHandlers[$handlerName]($player, $parts, $lastDecision);
                    break;
                default:
                    // Not static, return
                    if($decision->Type == "MZCHOOSE") { //We need to validate every decision type separately
                        $numChoices = 0;
                        $zones = $this->MZZoneArray($decision->Param);
                        foreach($zones as $zoneName) {
                            $numChoices+=MZZoneCount($zoneName);
                        }
                        if($numChoices === 0) {
                            // No valid choices, auto-PASS
                            $lastDecision = "PASS";
                            break;
                        }
                    }
                    // Put it back at the front
                    $playerQueue = &GetDecisionQueue($player);
                    array_unshift($playerQueue, $decision);
                    if(self::$debugMode) echo("Re-adding decision to player " . $player . " queue: " . $decision->Type . " " . $decision->Param . "<BR>");
                    return;
            }
        }
        AutoAdvance();
    }

    // Add a decision to a player's queue
    public static function AddDecision($player, $type, $param = '', $block = 0, $tooltip = '') {
        $tooltip = str_replace(' ', '_', $tooltip);
        $playerQueue = &GetDecisionQueue($player);
        $insertIndex = 0;
        for($i = 0; $i < count($playerQueue); $i++){
            if($playerQueue[$i]->Block > $block){
                break;
            }
            $insertIndex = $i + 1;
        }
        if(self::$debugMode) echo("Adding decision to player " . $player . " queue: " . $type . " " . $param . " Block: " . $block . " at index " . $insertIndex . "<BR>");
        array_splice($playerQueue, $insertIndex, 0, [new DecisionQueue($type . " " . $param . " " . $block . " " . $tooltip)]);
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