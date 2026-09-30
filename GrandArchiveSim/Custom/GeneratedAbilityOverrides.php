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

// Band of Burning Verdict (7mmve2l328, REGALIA/ITEM/TAMER accessory -- CardType() confirms
// "REGALIA,ITEM", not ally, despite the card's role as Guo Jia-deck support): "On Enter: Draw a
// card. [Class Bonus] [REST]: Target Animal or Beast ally you control gets +1 POWER and gains true
// sight until end of turn." Two defects confirmed live and documented in Tests/Integration/
// GrandArchiveSim/band-of-burning-verdict-enter-draw/meta.json, both from the [REST] ability's
// condition and body being generated into the WRONG dispatch tables entirely -- the tables that
// gate/resolve PLAYING this card from hand, not the tables for a field ability of a permanent
// already on the field:
//
// (1) activateCardPrereqs["7mmve2l328:0"] is consulted by CanActivateCard()
// (GeneratedCode/GeneratedMacroCode.php), the legality gate checked before a card can be
// materialized from hand at all. The generator wired the [REST] ability's own gating there --
// requiring IsClassBonusActive($player,["TAMER"]) and an Animal/Beast ally already on the field --
// so the card could not be played AT ALL without both, even though neither is a printed
// restriction on playing this ally; both belong only to the separate "[Class Bonus] [REST]:"
// activated ability. Overridden to unconditionally legal.
//
// (2) cardActivatedAbilities["7mmve2l328:0"] is not a "REST-costed activated ability" table -- it's
// the dictionary OnCardActivated() (Custom/GameLogic.php, ~line 5549) unconditionally invokes
// immediately after ANY materialize/play of this card resolves (the same table ACTION cards use to
// resolve their on-play effect, e.g. the Advent of the Shenju/Unity's Gale entries in
// Custom/GameLogic.php). Because the generator wired the [REST] buff body into this same table, the
// buff auto-fired on Enter with no REST cost ever paid -- confirmed live: after only playing the
// card (no activation, no tap), an unprompted MZCHOOSE for the buff target appeared, and answering
// it applied the power buff + TRUE_SIGHT while the card was still untapped. Overridden to a no-op
// so materializing the card only runs its real On Enter effect (enterAbilities["7mmve2l328:0"]'s
// Draw(), untouched by this override).
//
// The genuinely correct home for a REST-costed field ability with no reserve cost is
// $activateAbilityAbilities / $activateAbilityPrereqs, dispatched via ActivateAbility() ->
// DoActivatedAbility() (which pays the REST cost itself, Custom/GameLogic.php ~line 7085) and
// reached in real play via the AbilityOpportunity window's "mzID@Activate-N@label" encoded choice
// (see ResolveOpportunitySelection() in Custom/OpportunityLogic.php) -- exactly the pattern already
// generated for e.g. Balmshot Nurse (activateAbilityAbilities["jetWcli3ZL:0"]) and hand-authored for
// Charm of Anticipation (activateAbilityAbilities["vkL2RFh0yM:0"], Custom/GameLogic.php). Registered
// there (see Custom/GameLogic.php) rather than here, since GeneratedCode/GeneratedMacroCode.php has
// no activateAbilityAbilities/activateAbilityPrereqs entry for this card at all -- that's a missing
// addition, not a wrong-entry override, so it follows the Charm of Anticipation/Advent of the
// Shenju precedent instead (purely additive, nothing to clobber).
$activateCardPrereqs["7mmve2l328:0"] = function($player, $mzID, $ignoreCost) { // Band of Burning Verdict: playing this ally has no printed restriction
    return true;
};
$cardActivatedAbilities["7mmve2l328:0"] = function($player) { // Band of Burning Verdict: no on-play activated effect
    // Intentionally empty: see comment above. This card's real On Enter ability
    // (enterAbilities["7mmve2l328:0"], Draw a card) already runs independently of this table; the
    // printed [Class Bonus][REST] buff ability lives in $activateAbilityAbilities instead
    // (Custom/GameLogic.php) and only fires from an explicit later activation.
};
