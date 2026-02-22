<?php
// Core/DecisionQueueController.php
// Helper class for managing player decision queues in the game engine.
// ASSUMES: You have a per-player zone named "DecisionQueue"

class DecisionQueueController {
    private $numPlayers = 2;
    private static $debugMode = false;

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
                    if($lastDecision == "PASS" && !$decision->DontSkipOnPass) break;
                    $resolvedParam = str_replace("{<-}", $lastDecision, $decision->Param);
                    $parts = explode("->", $resolvedParam);
                    $source = $parts[0];
                    $destination = explode("-", $parts[1])[0];
                    MZMove($player, $source, $destination);
                    break;
                case "CUSTOM":
                    if($lastDecision == "PASS" && !$decision->DontSkipOnPass) break;
                    global $customDQHandlers;
                    $parts = explode("|", $decision->Param);
                    $handlerName = array_shift($parts);
                    $customDQHandlers[$handlerName]($player, $parts, $lastDecision);
                    break;
                case "SYSTEM":
                    if($lastDecision == "PASS" && !$decision->DontSkipOnPass) break;
                    global $systemDQHandlers;
                    $parts = explode("|", $decision->Param);
                    $handlerName = array_shift($parts);
                    $systemDQHandlers[$handlerName]($player, $parts, $lastDecision);
                    break;
                default:
                    // Not static, return
                    if($decision->Type == "MZCHOOSE") { //We need to validate every decision type separately
                        // Use the new counting method that handles both zones and specific cards
                        $numChoices = $this->MZCountChoices($decision->Param);
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
    public static function AddDecision($player, $type, $param = '', $block = 0, $tooltip = '', $dontSkipOnPass = 0) {
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
        array_splice($playerQueue, $insertIndex, 0, [new DecisionQueue($type . " " . $param . " " . $block . " " . $tooltip . " " . $dontSkipOnPass)]);
    }

    private function MZZoneArray($zoneStr) {
        $zones = explode("&", $zoneStr);
        $output = [];
        for($i=0; $i<count($zones); ++$i) {
            $zone = explode(":", $zones[$i]);
            $zoneOrCard = $zone[0];
            // Check if this is a specific card reference (zoneName-index)
            if (preg_match('/^(.+)-(\d+)$/', $zoneOrCard, $matches)) {
                // It's a specific card reference - extract zone name
                $output[] = $matches[1];
            } else {
                // It's a zone reference
                $output[] = $zoneOrCard;
            }
        }
        return $output;
    }
    
    // Parse zone string into array of specs with zone names and optional specific indices
    // Returns array of ['zone' => 'zoneName', 'specificIndex' => int|null]
    private function MZParseSpecs($zoneStr) {
        $zones = explode("&", $zoneStr);
        $output = [];
        for($i=0; $i<count($zones); ++$i) {
            $zone = explode(":", $zones[$i]);
            $zoneOrCard = $zone[0];
            // Check if this is a specific card reference (zoneName-index)
            if (preg_match('/^(.+)-(\d+)$/', $zoneOrCard, $matches)) {
                // It's a specific card reference
                $output[] = [
                    'zone' => $matches[1],
                    'specificIndex' => intval($matches[2]),
                    'original' => $zoneOrCard
                ];
            } else {
                // It's a zone reference
                $output[] = [
                    'zone' => $zoneOrCard,
                    'specificIndex' => null,
                    'original' => $zoneOrCard
                ];
            }
        }
        return $output;
    }
    
    // Count available choices from a zone string (handles both zones and specific cards)
    private function MZCountChoices($zoneStr) {
        $specs = $this->MZParseSpecs($zoneStr);
        $numChoices = 0;
        foreach($specs as $spec) {
            if ($spec['specificIndex'] !== null) {
                // Specific card - counts as 1 if the card exists in the zone
                $zoneCount = MZZoneCount($spec['zone']);
                if ($spec['specificIndex'] < $zoneCount) {
                    $numChoices += 1;
                }
            } else {
                // Whole zone - count all cards
                $numChoices += MZZoneCount($spec['zone']);
            }
        }
        return $numChoices;
    }
    
    // Variable storage for await syntax using DecisionQueueVariables zone
    public static function StoreVariable($name, $value) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) $vars = [];
        $vars[$name] = $value;
        SetDecisionQueueVariables(json_encode($vars));
    }
    
    public static function GetVariable($name) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) return null;
        return $vars[$name] ?? null;
    }

    public static function ClearVariable($name) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) return;
        unset($vars[$name]);
        SetDecisionQueueVariables(json_encode($vars));
    }
    
    public static function ClearVariables() {
        SetDecisionQueueVariables('{}');
    }
    
    public static function CleanupRemovedCards() {
        $allZones = GetAllZones();
        foreach ($allZones as $zoneName) {
            $zone = &GetZone($zoneName);
            if (!is_array($zone)) continue;
            
            // Physically remove cards marked as removed (reverse iteration to safely splice)
            for ($i = count($zone) - 1; $i >= 0; $i--) {
                if (isset($zone[$i]) && method_exists($zone[$i], 'Removed') && $zone[$i]->Removed()) {
                    array_splice($zone, $i, 1);
                }
            }
            
            // Rebuild mzIndex values and indexed properties for remaining cards
            for ($i = 0; $i < count($zone); $i++) {
                if (isset($zone[$i])) {
                    $zone[$i]->mzIndex = $i;
                    $zone[$i]->BuildIndex();
                }
            }
        }
    }
}