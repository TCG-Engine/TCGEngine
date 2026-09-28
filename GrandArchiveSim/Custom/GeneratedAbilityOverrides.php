<?php
// Overrides for GeneratedCode/GeneratedMacroCode.php ability bodies that are wrong at the source
// (in the CardEditor MySQL database) and can't be corrected from this sandbox (no DB access, and
// GeneratedCode/* is gitignored/regenerated, so hand-editing the generated file wouldn't survive
// a regen anyway).
//
// This file must be loaded AFTER GeneratedCode/GeneratedMacroCode.php so its assignments win.
// GrandArchiveSim/Custom/GameLogic.php is NOT a safe place for this: GamestateParser.php includes
// GameLogic.php *before* GeneratedMacroCode.php, and every later include of GameLogic.php
// (e.g. CreateGame.php's) uses include_once, which is a no-op once the realpath has already been
// included once — so anything assigned in GameLogic.php gets clobbered by the generated
// definitions, not the other way around. GrandArchiveSim/MatchHooks.php, by contrast, is
// require_once'd by Core/EngineActionRunner.php's EngineLoadRootRuntime() strictly after
// GamestateParser.php (and therefore after GeneratedMacroCode.php) on every single action — see
// this file's require_once from MatchHooks.php. Verified empirically: reassigning these keys from
// GameLogic.php left the bug in place; reassigning them here fixed it.
//
// Bug pattern: several "Destroy target X" CardActivated handlers manually do
// OnLeaveField()+MZMove()-to-graveyard instead of calling AllyDestroyed()/DoAllyDestroyed()
// (the shared destroy dispatcher in GameLogic.php). That bypass skips every "whenever a permanent
// is destroyed" trigger living inside DoAllyDestroyed (e.g. Diao Chan, Idyll Corsage's
// whenever-a-non-token-object-an-opponent-controls-is-destroyed ability) as well as
// DoAllyDestroyed's own destroy-replacement effects (Immortality, Link Shield, Renewable,
// Ephemeral/Fireworks/Xiao Qiao banish redirects, the champion-loss game-over check). Every card
// below has a plain "Destroy target X" printed text with no replacement/"instead" clause, so
// nothing here should legitimately skip that shared path. Found by grepping
// GeneratedCode/GeneratedMacroCode.php for other CardActivated handlers with this same
// manual-move pattern, and confirmed live with a temporary probe: seed a target on player 1's
// field and Diao Chan on player 2's field, invoke the handler directly, and check whether Diao
// Chan's DiaoChanIdyllBanish decision gets queued for player 2. It didn't for any of these until
// fixed here. Mirrors the correct pattern already used by Break Apart
// (4ns2jbt4hq:0:CardActivated-1 in GeneratedMacroCode.php), which just calls AllyDestroyed().

$customDQHandlers["zd83net7x0:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Disenchant: Destroy target phantasia.
    $target = $lastDecision;
    if(GetZoneObject($target) === null) return;
    AllyDestroyed($player, $target);
};

$customDQHandlers["40xhntos3d:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Ghastly Corrosion: Destroy target item or weapon with memory cost 0 or reserve cost 4 or less.
    $target = $lastDecision;
    if(GetZoneObject($target) === null) return;
    AllyDestroyed($player, $target);
};

$customDQHandlers["sbalegbscx:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Reduce to Ash: Destroy target item or weapon with memory cost 0 or reserve cost 4 or less.
    $target = $lastDecision;
    if(GetZoneObject($target) === null) return;
    AllyDestroyed($player, $target);
};

$customDQHandlers["EFelNCz3Zv:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Crumbling Reign: Destroy target item or weapon.
    $chosen = $lastDecision;
    if(GetZoneObject($chosen) === null) return;
    AllyDestroyed($player, $chosen);
};

$customDQHandlers["0s6solta0h:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Rapid Combustion: Destroy target item or weapon with memory cost 0 or reserve cost 3 or less that entered the field this turn.
    $target = $lastDecision;
    if(GetZoneObject($target) === null) return;
    AllyDestroyed($player, $target);
};

$customDQHandlers["9wxcgpy069:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Expel the Departed: Destroy up to one target phantasia. If you control two or more Fatestone/Fatebound objects, draw a card.
    $chosen = $lastDecision;
    if($chosen !== "" && $chosen !== "-" && $chosen !== "PASS") {
        $obj = GetZoneObject($chosen);
        if($obj !== null && !$obj->removed) {
            AllyDestroyed($player, $chosen);
        }
    }
    if(CountFatestoneOrFateboundObjects($player) >= 2) {
        Draw($player, 1);
    }
};

$customDQHandlers["TBVLLRPiwP:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Converge Reflections: Destroy target item or weapon (M0 or R<=4). If that object was a Distortion, draw a card into your memory.
    $chosen = $lastDecision;
    $targetObj = GetZoneObject($chosen);
    if($targetObj === null) return;
    $isDistortion = PropertyContains(CardSubtypes($targetObj->CardID), "DISTORTION");
    AllyDestroyed($player, $chosen);
    if($isDistortion) {
        DrawIntoMemory($player, 1);
    }
};

$customDQHandlers["tdz5of8zuz:0:CardActivated-1"] = function($player, $parts, $lastDecision) { // Shatter the Brittle: Destroy target item or weapon with memory cost 1 or less, or reserve cost 5 or less. Its controller draws a card into their memory.
    $chosen = $lastDecision;
    if($chosen === "-" || $chosen === "" || $chosen === "PASS") return;
    $targetObj = GetZoneObject($chosen);
    if($targetObj === null || $targetObj->removed) return;
    $controller = $targetObj->Controller;
    AllyDestroyed($player, $chosen);
    DrawIntoMemory($controller, 1);
};
