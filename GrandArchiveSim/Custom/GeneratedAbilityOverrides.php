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

// Luminous Quartz (40lgjj1yS3, Sheen 12+ item ability): "[Sheen 12+] REST, Remove a preparation
// counter from your champion: As a Spell, deal 1+X damage to target unit, where X is the amount of
// sheen counters on it." Documented in Tests/Integration/GrandArchiveSim/
// luminous-quartz-sheen12-rest-damage/meta.json: customDQHandlers["40lgjj1yS3:0:ActivateAbility-1"]
// (GeneratedCode/GeneratedMacroCode.php ~line 27053) calls RemoveCounters($player, $champMZ,
// "preparation", 1) but never assigns $champMZ anywhere in that handler's scope -- $champMZ is only
// computed locally inside the sibling $activateAbilityAbilities["40lgjj1yS3:0"] body and the
// $activateAbilityPrereqs["40lgjj1yS3:0"] prereq closure (two separate closures/scopes), so the
// customDQHandlers closure hits a genuine "Undefined variable $champMZ" PHP warning and
// RemoveCounters() is called with $champMZ === null, which silently no-ops -- the REST cost IS paid
// ($sourceObj2->Status = 1, correct and preserved below) but the "Remove a preparation counter"
// cost never is. Confirmed activateAbilityPrereqs["40lgjj1yS3:0"] already correctly computes its
// own $champMZ via FindChampionMZ($player) and gates activation on
// GetPrepCounterCount($champObj) >= 1 -- that gate is NOT broken, only this handler's actual
// removal of the counter. Fixed by computing $champMZ the same way other cards in this codebase
// reference "your own champion" (FindChampionMZ($player), Custom/CardDQHandlers.php) before the
// RemoveCounters() call; everything else (REST, target resolution, 1+X damage) is unchanged from
// the generated body.
$customDQHandlers["40lgjj1yS3:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) { //Sheen
    // Retrieve macro parameters
    $mzID = DecisionQueueController::GetVariable("mzID");
    $abilityIndex = DecisionQueueController::GetVariable("abilityIndex");
    DecisionQueueController::StoreVariable("target", $lastDecision);
    if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
    if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "40lgjj1yS3:0:ActivateAbility-1")) return;
    $target = $lastDecision;
    $sourceObj2 = &GetZoneObject($mzID);
    if($sourceObj2 !== null) $sourceObj2->Status = 1;
    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) RemoveCounters($player, $champMZ, "preparation", 1);
    $targetObj = GetZoneObject($target);
    if($targetObj === null || $targetObj->removed) return;
    $damage = 1 + GetCounterCount($targetObj, "sheen");
    DealDamage($player, $mzID, $target, $damage);
};

// Turm, Schwartz Rook (rYyOEGB3tD): "[Alice Bonus] On Leave: Put the buff counters that were on
// CARDNAME on a Pawn ally you control."
//
// The generated leaveFieldAbilities closure is fine up to the target prompt (the stale-target-mzID
// half of this class of bug is fixed engine-side by GameOnZoneElementSpliced(), see
// Custom/GameLogic.php). But its paired CUSTOM follow-up ("rYyOEGB3tD:0:LeaveField-1") re-reads the
// DEPARTING Turm through the ambient "mzID" variable via GetZoneObject($mzID) -- long after the
// leave trigger has finished, Turm has been spliced out of the field and OnLeaveField() has restored
// "mzID" to whatever it held before, so $leavingObj is null (or the wrong object) and the buff
// counters are never put on the Pawn even though the player picked one. Same shape as Green Slime's
// hand-written GreenSlimeTransfer handler: snapshot the count while the departing object is still
// readable (inside the leave closure) and have the follow-up use the snapshot.
$leaveFieldAbilities["rYyOEGB3tD:0"] = function($player) { //Move buffs to Pawn
    // Retrieve macro parameters
    $mzID = DecisionQueueController::GetVariable("mzID");
    if(!IsAliceBonusActive($player)) return;
    $leavingObj = GetZoneObject($mzID);
    if($leavingObj === null) return;
    $buffCount = GetCounterCount($leavingObj, "buff");
    if($buffCount <= 0) return;
    DecisionQueueController::StoreVariable("TurmBuffCount", strval($buffCount));
    $pawns = [];
    $field = GetField($player);
    foreach($field as $i => $fObj) {
        if($fObj->removed) continue;
        if(!PropertyContains(EffectiveCardType($fObj), "ALLY")) continue;
        if(!PropertyContains(EffectiveCardSubtypes($fObj), "PAWN")) continue;
        $pawns[] = "myField-" . $i;
    }
    if(empty($pawns)) return;
    $targetStr = implode("&", $pawns);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "rYyOEGB3tD:0:LeaveField-1", 1);
};

$customDQHandlers["rYyOEGB3tD:0:LeaveField-1"] = function($player, $parts, $lastDecision) { //Move buffs to Pawn
    // Retrieve macro parameters
    $mzID = DecisionQueueController::GetVariable("mzID");
    DecisionQueueController::StoreVariable("chosen", $lastDecision);
    if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
    if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "rYyOEGB3tD:0:LeaveField-1")) return;
    $chosen = $lastDecision;
    if($chosen === "" || $chosen === "-" || $chosen === "PASS") return;
    $buffCount = intval(DecisionQueueController::GetVariable("TurmBuffCount"));
    if($buffCount > 0) {
        AddCounters($player, $chosen, "buff", $buffCount);
    }
    DecisionQueueController::ClearVariable("TurmBuffCount");
};

// Arrow Trap (uoQGe5xGDQ): "Prepare 1 (You may remove a preparation counter from your champion as
// you activate this card.) Return target attacking ally to its owner's hand. [Class Bonus] If
// Arrow Trap was prepared, destroy that ally instead."
//
// The generated $cardActivatedAbilities["uoQGe5xGDQ:0"] had two defects, both fixed here:
//  (1) It read the raw CombatAttacker variable, which is stored relative to the ATTACKER
//      ("myField-1" for attacking player 2), but Arrow Trap resolves in the DEFENDER's action where
//      "myField-1" is the defender's own field -- so the attacker resolved to nothing (or to an
//      unrelated defender object) and was never returned/destroyed. GetCombatAttackerMZ()
//      re-localizes it through CombatAttackerPlayer (see the stored-mzID convention above
//      NormalizeMzIDForController() in Custom/OpportunityLogic.php).
//  (2) "Prepare 1" was never queued (no DeclarePrepareCost decision), so wasPrepared was never
//      YES and the destroy-instead branch was unreachable. Now wired exactly like Thieving Cut
//      (7t9m4muq2r:0): optional YES/NO when the champion has a preparation counter, then the
//      return/destroy resolves in a follow-up CUSTOM step so it only runs after that answer.
$cardActivatedAbilities["uoQGe5xGDQ:0"] = function($player) { //Return attacking ally; CB if prepared destroy instead
    DecisionQueueController::StoreVariable("wasPrepared", "NO");
    if(!IsCombatActive()) return;
    $champMZ = FindChampionMZ($player);
    $champObj = $champMZ !== null ? GetZoneObject($champMZ) : null;
    if($champObj !== null && GetCounterCount($champObj, "preparation") >= 1) {
        DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Pay_Prepare_1?");
        DecisionQueueController::AddDecision($player, "CUSTOM", "DeclarePrepareCost|" . $champMZ . "|1", 1);
    }
    DecisionQueueController::AddDecision($player, "CUSTOM", "ArrowTrapResolve", 1);
};

$customDQHandlers["ArrowTrapResolve"] = function($player, $parts, $lastDecision) {
    if(!IsCombatActive()) return;
    $attackerMZ = GetCombatAttackerMZ();
    if($attackerMZ === null) return;
    // Express it relative to $player (the ability's controller) for the $player-relative helpers below.
    $attackerMZ = NormalizeMzIDForController($attackerMZ, $player);
    $attackerObj = GetZoneObject($attackerMZ);
    if($attackerObj === null || $attackerObj->removed) return;
    if(!PropertyContains(EffectiveCardType($attackerObj), "ALLY")) return;
    $wasPrepared = DecisionQueueController::GetVariable("wasPrepared");
    if($wasPrepared === "YES" && IsClassBonusActive($player, explode(",", CardClasses("uoQGe5xGDQ")))) {
        DoAllyDestroyed($player, $attackerMZ);
        DecisionQueueController::CleanupRemovedCards();
    } else {
        // "its owner's hand" (same destination rule as the other return-to-hand effects).
        $dest = intval($attackerObj->Owner) === intval($player) ? "myHand" : "theirHand";
        MZMove($player, $attackerMZ, $dest);
    }
};

// Shadowstrike (o191zv86la, UMBRA ATTACK): "Prepare X. X can't be 0. Shadowstrike gets +X POWER.
// [Class Bonus] If Shadowstrike was prepared, it has unblockable."
//
// The CardEditor ability database has no CardActivated row for this card (the generated ability set
// only holds onAttackAbilities["o191zv86la:0"], which merely checks the PREPARED TurnEffect), so
// nothing ever offered/paid Prepare X or added the +X POWER effect that ObjectCurrentPower()
// (GameLogic.php) already reads as the "o191zv86la_POWER_<X>" TurnEffect. This hand-written entry
// fills the same $cardActivatedAbilities slot the generator would populate (additive: no competing
// generated key; remove it if the database row is ever authored).
//
// Prepare is an optional cost ("you may remove X preparation counters from your champion as you
// activate this card"), so the chain is YES/NO first; on YES a NUMBERCHOOSE offers X = 1..counters
// ("X can't be 0" is the range floor; declining is the only way to not pay). Nothing is asked when
// the champion has no preparation counter. Paying removes X counters, stores wasPrepared = YES
// (read by the generic GA_TagPreparedAttack follow-up, which tags the intent card PREPARED for the
// [Class Bonus] unblockable branch in onAttackAbilities["o191zv86la:0"] / CombatLogic.php) and puts
// the "o191zv86la_POWER_<X>" TurnEffect on this Shadowstrike's intent object. The follow-up steps
// are queued at block 0 so they run immediately after the YES/NO, ahead of the block-1
// GA_TagPreparedAttack that OnCardActivated() queues behind this macro.
function ShadowstrikeFindIntentMZ($player) {
    $intent = GetZone("myIntent");
    for($i = count($intent) - 1; $i >= 0; --$i) {
        if(!$intent[$i]->removed && $intent[$i]->CardID === "o191zv86la") return "myIntent-" . $i;
    }
    return null;
}
function ShadowstrikePreparationCounters($player) {
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return 0;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null) return 0;
    return GetCounterCount($champObj, "preparation");
}
function ShadowstrikeAskX($player, $champMZ, $intentMZ, $max) {
    DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "1|" . $max, 0, tooltip:"Choose_X_for_Prepare_X_(Shadowstrike_gets_+X_POWER)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ShadowstrikePrepareX|" . $champMZ . "|" . $intentMZ, 0, dontSkipOnPass:1);
}
$cardActivatedAbilities["o191zv86la:0"] = function($player) { //Shadowstrike: Prepare X, +X POWER
    DecisionQueueController::StoreVariable("wasPrepared", "NO");
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return;
    if(ShadowstrikePreparationCounters($player) < 1) return;
    $intentMZ = ShadowstrikeFindIntentMZ($player);
    if($intentMZ === null) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Pay_Prepare_X?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "ShadowstrikePrepareAsk|" . $champMZ . "|" . $intentMZ, 1, dontSkipOnPass:1);
};
$customDQHandlers["ShadowstrikePrepareAsk"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return; // declined: not prepared, +0 POWER
    $max = ShadowstrikePreparationCounters($player);
    if($max < 1) return;
    ShadowstrikeAskX($player, $parts[0], $parts[1], $max);
};
$customDQHandlers["ShadowstrikePrepareX"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "PASS" || $lastDecision === "-" || $lastDecision === "" || $lastDecision === null) return; // no X chosen: not prepared
    $champMZ = $parts[0];
    $intentMZ = $parts[1];
    $max = ShadowstrikePreparationCounters($player);
    if($max < 1) return;
    $x = intval($lastDecision);
    if(!is_numeric($lastDecision) || $x < 1 || $x > $max) { // "X can't be 0"; can't pay more counters than the champion has
        ShadowstrikeAskX($player, $champMZ, $intentMZ, $max);
        return;
    }
    $intentObj = GetZoneObject($intentMZ);
    if($intentObj === null || $intentObj->removed || $intentObj->CardID !== "o191zv86la") {
        $intentMZ = ShadowstrikeFindIntentMZ($player);
        if($intentMZ === null) return;
    }
    RemoveCounters($player, $champMZ, "preparation", $x);
    DecisionQueueController::StoreVariable("wasPrepared", "YES");
    AddTurnEffect($intentMZ, "o191zv86la_POWER_" . $x);
};

// ---------------------------------------------------------------------------------------------
// Fixed-cost "Prepare N" wiring for cards whose CardEditor ability row never queued the Prepare cost
// ---------------------------------------------------------------------------------------------
// Prepare N ("You may remove N preparation counters from your champion as you activate this card")
// is paid through the same three pieces every working Prepare card uses (Thieving Cut 7t9m4muq2r,
// Slice and Dice 3jg01o26b4, Arrow Trap uoQGe5xGDQ): a $cardActivatedAbilities["<id>:0"] entry that
// queues a YES/NO "Pay Prepare N?" plus the generic DeclarePrepareCost handler (RemoveCounters +
// wasPrepared = YES), and, for ATTACK cards, the generic GA_TagPreparedAttack follow-up that
// OnCardActivated() queues behind that entry so the intent card is tagged PREPARED only after the
// answer. The cards below had the card's own "if prepared" rider generated (onAttackAbilities /
// onHitAbilities / enterAbilities, or a CardActivated body that merely READS wasPrepared) but no
// CardActivated row that ever offers the cost, so wasPrepared was never YES and the rider was dead
// code (live state dumps in the fixtures' meta.json notes: after paying the reserve cost the
// decision queue went straight to the attack-target / ally-target choice, preparation counters
// untouched, and answering YES was rejected as "Invalid selection" or silently ignored).
// These hand-authored entries fill the same slot the generator would populate (the same additive
// workaround as Slice and Dice / Shadowstrike); each becomes redundant once a CardEditor database
// row is authored. Nothing is asked when the champion has fewer than N preparation counters.
function GAQueueFixedPrepare($player, $n, $classBonusRequiredCardID = null) {
    DecisionQueueController::StoreVariable("wasPrepared", "NO");
    // "[Class Bonus] Prepare N" (Condemning Evisceration): the Prepare cost itself only exists while the class bonus is active.
    if($classBonusRequiredCardID !== null && !IsClassBonusActive($player, explode(",", CardClasses($classBonusRequiredCardID)))) return false;
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return false;
    $champObj = GetZoneObject($champMZ);
    if($champObj === null || GetCounterCount($champObj, "preparation") < $n) return false;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Pay_Prepare_" . $n . "?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "DeclarePrepareCost|" . $champMZ . "|" . $n, 1);
    return true;
}
// ATTACK cards: the riders already exist (onAttackAbilities / onHitAbilities read the PREPARED tag or
// wasPrepared); only the cost was missing.
foreach([
    "5qWWpkgQLl" => 4, // Coup de Grace: Prepare 4
    "GRkBQ1Uvir" => 1, // Ignited Stab: Prepare 1
    "XLbCBxla8K" => 1, // Thousand Refractions: Prepare 1
    "ekkjn37cx6" => 3, // Final Stroke: Prepare 3
    "2lukkhisu5" => 2, // Striking Illuminance: Prepare 2
    "TDI5DOrWB5" => 1, // Stillshard Strike: Prepare 1
    "DHn9J7gX6g" => 2, // Strike from the Mist: Prepare 2 (generated body was a no-op "(engine)" stub; the PREPARED check lives in CombatLogic.php)
] as $gaPrepareCardID => $gaPrepareN) {
    $cardActivatedAbilities[$gaPrepareCardID . ":0"] = function($player) use ($gaPrepareN) {
        GAQueueFixedPrepare($player, $gaPrepareN);
    };
}
$cardActivatedAbilities["r84E55KBLM:0"] = function($player) { // Condemning Evisceration: [Class Bonus] Prepare 1
    GAQueueFixedPrepare($player, 1, "r84E55KBLM");
};
// The generated riders for Coup de Grace / Ignited Stab / Thousand Refractions gate their "[Class Bonus]"
// with IsClassBonusActive($player) and NO class list, which only checks "controls any champion" and is
// always true. Re-gate them on the cards' real class (ASSASSIN) so a prepared copy played by a
// non-Assassin champion gets no rider.
function GAWrapClassBonusGate(&$abilityTable, $key, $classes) {
    $original = $abilityTable[$key] ?? null;
    if($original === null) return;
    $abilityTable[$key] = function($player) use ($original, $classes) {
        if(!IsClassBonusActive($player, $classes)) return;
        $original($player);
    };
}
GAWrapClassBonusGate($onAttackAbilities, "5qWWpkgQLl:0", ["ASSASSIN"]); // Coup de Grace: [Class Bonus] critical 4
GAWrapClassBonusGate($onAttackAbilities, "GRkBQ1Uvir:0", ["ASSASSIN"]); // Ignited Stab: [Class Bonus] +2 POWER
GAWrapClassBonusGate($onHitAbilities, "XLbCBxla8K:0", ["ASSASSIN"]);    // Thousand Refractions: [Class Bonus] wake up + return

// Soultrace Tessellation (7ePq6I4uZ8, ACTION): "Prepare 1. Put three sheen counters on target unit. If
// Soultrace Tessellation was prepared, put an additional sheen counter on that unit for every three
// cards in your banishment." The generated ability was registered as enterAbilities["7ePq6I4uZ8:0"],
// which is only dispatched for permanents entering the field -- an ACTION never triggers it, so the card
// resolved with NO effect at all (no target prompt, no sheen). Registered as a CardActivated entry
// instead (Prepare cost first, then the target choice, then the sheen resolution).
$cardActivatedAbilities["7ePq6I4uZ8:0"] = function($player) { // Soultrace Tessellation
    GAQueueFixedPrepare($player, 1);
    $units = array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"]));
    $units = FilterSpellshroudTargets($units);
    if(empty($units)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $units), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "GASoultraceTessellationResolve", 1, dontSkipOnPass:1);
};
$customDQHandlers["GASoultraceTessellationResolve"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS" || $lastDecision === null) return;
    $target = GetZoneObject($lastDecision);
    if($target === null || $target->removed) return;
    $sheen = 3;
    if(DecisionQueueController::GetVariable("wasPrepared") === "YES") {
        $sheen += intval(floor(count(ZoneSearch("myBanish")) / 3));
    }
    AddCounters($player, $lastDecision, "sheen", $sheen);
};

// Exploit Vulnerability (hy83sghwfi, ACTION): "Prepare 1. Draw a card. Then if Exploit Vulnerability was
// prepared, choose an Assassin unit you control and it gains 'On Ally Hit: Destroy the hit ally' until
// end of turn." The generated body drew and read wasPrepared synchronously (always stale/NO) and never
// offered the cost. The draw and the prepared branch now run after the Prepare answer.
$cardActivatedAbilities["hy83sghwfi:0"] = function($player) { // Exploit Vulnerability
    GAQueueFixedPrepare($player, 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "GAExploitVulnerabilityResolve", 1, dontSkipOnPass:1);
};
$customDQHandlers["GAExploitVulnerabilityResolve"] = function($player, $parts, $lastDecision) {
    Draw($player, 1);
    if(DecisionQueueController::GetVariable("wasPrepared") !== "YES") return;
    $assassins = ZoneSearch("myField", ["ALLY", "CHAMPION"], cardSubtypes: ["ASSASSIN"]);
    if(empty($assassins)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $assassins), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "GAExploitVulnerabilityGrant", 1, dontSkipOnPass:1);
};
$customDQHandlers["GAExploitVulnerabilityGrant"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS" || $lastDecision === null) return;
    $unit = GetZoneObject($lastDecision);
    if($unit === null || $unit->removed) return;
    AddTurnEffect($lastDecision, "EXPLOIT_VULNERABILITY_ON_HIT"); // consumed by CombatLogic.php's On Ally Hit resolution
};

// Fishing Accident (RRx0KK6g6D, ACTION): "Prepare 2. Rest target ally. If Fishing Accident was prepared,
// put that ally on the bottom of its owner's deck instead." Same shape as the generated body (target
// choice, then a CUSTOM that reads wasPrepared) plus the missing Prepare cost, queued first so the answer
// lands before the target resolves.
$cardActivatedAbilities["RRx0KK6g6D:0"] = function($player) { // Fishing Accident
    GAQueueFixedPrepare($player, 2);
    $allies = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
    if(empty($allies)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allies), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "GAFishingAccidentResolve", 1, dontSkipOnPass:1);
};
$customDQHandlers["GAFishingAccidentResolve"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS" || $lastDecision === null) return;
    $ally = GetZoneObject($lastDecision);
    if($ally === null || $ally->removed) return;
    if(DecisionQueueController::GetVariable("wasPrepared") === "YES") {
        // "its owner's deck" (same owner-relative destination rule as Arrow Trap above); MZMove to a deck appends = bottom.
        $dest = intval($ally->Owner) === intval($player) ? "myDeck" : "theirDeck";
        MZMove($player, $lastDecision, $dest);
        DecisionQueueController::CleanupRemovedCards();
    } else {
        RestCard($player, $lastDecision);
    }
};

// Silvergale Monstrosity's Call (lsLd8ADGAe, ACTION): "Prepare 2. Summon a Memorite Obelith token. If
// Silvergale Monstrosity's Call was prepared, move any amount of sheen counters from your Fractured
// Memories onto any amount of allies named Memorite Obelith you control." The generated body summoned and
// read wasPrepared synchronously and never offered the cost. Resolution now runs after the Prepare answer.
// "Any amount" is resolved as the generated body did (all the sheen, onto the first Memorite Obelith you
// control), but the target is now matched by NAME (Memorite Obelith) instead of any MEMORITE subtype.
$cardActivatedAbilities["lsLd8ADGAe:0"] = function($player) { // Silvergale Monstrosity's Call
    GAQueueFixedPrepare($player, 2);
    DecisionQueueController::AddDecision($player, "CUSTOM", "GASilvergaleResolve", 1, dontSkipOnPass:1);
};
$customDQHandlers["GASilvergaleResolve"] = function($player, $parts, $lastDecision) {
    SummonMemorite($player, "fdnlbJm3hr");
    if(DecisionQueueController::GetVariable("wasPrepared") !== "YES") return;
    $sheen = GetSheenCount($player);
    if($sheen <= 0) return;
    $obelithMZ = null;
    $field = GetZone("myField");
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "fdnlbJm3hr") { $obelithMZ = "myField-" . $i; break; }
    }
    if($obelithMZ === null) return;
    RemoveSheenFromMastery($player, $sheen);
    AddCounters($player, $obelithMZ, "sheen", $sheen);
};

// ---------------------------------------------------------------------------------------------
// dontSkipOnPass overrides (decline-tolerant CUSTOM follow-ups queued by generated abilities)
//
// Core/DecisionQueueController.php ExecuteStaticMethods() skips a CUSTOM handler outright when the
// preceding answer was "PASS" (a declined MZMAYCHOOSE) unless the decision was queued with
// dontSkipOnPass. The generated closures below queue their paired follow-up WITHOUT that flag, but
// the follow-up handler's own decline branch is required by the card text to still run. Each
// closure is the generated body verbatim except for dontSkipOnPass:1 on the follow-up AddDecision.
// ---------------------------------------------------------------------------------------------

// Foraging Fox (b0ssellm84): "On Enter: Look at the top five cards of your deck. You may reveal a
// Fatestone card from among them and put it into your memory. Put the rest on the bottom of your
// deck in any order." Declining the reveal must still put all five cards on the bottom; the
// generated follow-up ("b0ssellm84:0:Enter-1" -> ForagingFoxChooseBottom) was skipped on PASS and
// stranded the five cards in the temp zone.
$enterAbilities["b0ssellm84:0"] = function($player) { //Find Fatestone from top five
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $deck = GetDeck($player);
  $lookCount = min(5, count($deck));
  if($lookCount <= 0) return;
  for($i = $lookCount - 1; $i >= 0; --$i) {
    MZMove($player, "myDeck-" . $i, "myTempZone");
  }
  $candidates = ZoneSearch("myTempZone", cardSubtypes: ["FATESTONE"]);
  if(empty($candidates)) {
      ForagingFoxChooseBottom($player);
      return;
  }
  $candidateStr = implode("&", $candidates);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "b0ssellm84:0:Enter-1", 1, dontSkipOnPass:1);
};

// Malevolent Vow (up6fw61vf1): "Discard up to three cards. Recover 3+X, where X is three times amount
// of cards discarded this way. Put Malevolent Vow on the bottom of your champion's lineage." Declining
// ANY of the three optional discards (including the first, i.e. discarding zero cards) must still
// finish the spell: recover and move the Vow to the lineage. The generated closure queued
// 'MalevolentVow1' without dontSkipOnPass, so declining the first prompt skipped the handler and the
// spell did nothing (Vow stranded in the graveyard, no recover).
$cardActivatedAbilities["up6fw61vf1:0"] = function($player) { //Malevolent Vow
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Discard up to three cards. Recover 3+X where X=3*discardCount. Put on bottom of lineage.
  DecisionQueueController::StoreVariable("malevolentVowDiscardCount", "0");
  $hand = ZoneSearch("myHand");
  if(empty($hand)) {
      // No cards to discard — recover 3 and put on lineage immediately
      RecoverChampion($player, 3);
      $gy = GetZone("myGraveyard");
      for($gi = count($gy)-1; $gi >= 0; --$gi) {
          if(!$gy[$gi]->removed && $gy[$gi]->CardID === "up6fw61vf1") {
              MZRemove($player, "myGraveyard-" . $gi);
              DecisionQueueController::CleanupRemovedCards();
              break;
          }
      }
      AddToChampionLineage($player, "up6fw61vf1");
      return;
  }
  $handStr = implode("&", $hand);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $handStr, 1, tooltip:"Discard_a_card_(Malevolent_Vow_1/3)");
  DecisionQueueController::AddDecision($player, "CUSTOM", "MalevolentVow1", 1, dontSkipOnPass:1);
};

// Gildas, Faesworn Monarch (g99PIuhU0O): "[Mordred Bonus] (2), [REST]: Prevent the next 4 damage ..."
// A [REST] cost can only be paid by an awake unit. The generated prereq only checked
// IsMordredBonusActive(), and DoActivatedAbility() merely SETS Status = 1 as the implicit REST, so an
// already-rested Gildas was still offered (and accepted) for activation again, paying another (2).
// CanActivateAbility() is the single gate for both the opportunity-window listing and execution, so
// requiring Status == 2 (awake) here makes the second activation neither offered nor accepted.
$activateAbilityPrereqs["g99PIuhU0O:0"] = function($player, $mzID, $abilityIndex) { //Prevent prereq
  if(!IsMordredBonusActive($player)) return false;
  $selfObj = GetZoneObject($mzID);
  if($selfObj === null || $selfObj->removed) return false;
  return isset($selfObj->Status) && intval($selfObj->Status) === 2;
};

// Key Slime Pudding (4wuq20gvcg) and Baby Blue Slime (9ggfiy38t2): both printed abilities are FIELD
// activated abilities, but the generator filed them under $cardActivatedAbilities (the PLAY-effect
// dictionary) with a 0 $CardActivateAbilityCountData row. The correct registrations
// ($activateAbilityAbilities / $activateAbilityPrereqs / the "9ggfiy38t2:0:ActivateAbility-1" handler and
// the ability count/name rows) live in Custom/GameLogic.php next to Charm of Anticipation / Band of
// Burning Verdict; this file only (1) neutralizes the misfiled generated PLAY-table bodies and (2)
// applies the count/name rows on top of the wholesale-reassigned generated arrays.
//
// (1) $cardActivatedAbilities["9ggfiy38t2:0"] was the REST "prevent the next 2 damage" body, so playing
// Baby Blue Slime queued a free target prompt on enter (no REST paid) and the real REST ability never
// existed. "4wuq20gvcg:0" would banish Key Slime Pudding and set its global effect with no cost check if
// anything ever dispatched it through ActivateCard()/OnCardActivated(); the field ability now pays its
// Banish cost in ActivatedAbilityCost(). Neither card has a printed on-play effect, so both are no-ops.
$cardActivatedAbilities["9ggfiy38t2:0"] = function($player) { // Baby Blue Slime: no on-play effect (REST ability lives in $activateAbilityAbilities)
};
$cardActivatedAbilities["4wuq20gvcg:0"] = function($player) { // Key Slime Pudding: no on-play effect (Banish ability lives in $activateAbilityAbilities)
};
// (2) Same scope as the generated arrays (this file is included from the same scope that included
// GeneratedMacroCode.php); guarded so a differently-scoped include can never replace them with a
// partial array. GAApplyActivateAbilityCountOverrides() (GameLogic.php) re-applies the same table lazily
// on paths that never load this file (the render path).
if(isset($CardActivateAbilityCountData) && is_array($CardActivateAbilityCountData)
    && isset($CardActivateAbilityCountNamesData) && is_array($CardActivateAbilityCountNamesData)) {
    foreach(GAActivateAbilityCountOverrides() as $gaOverrideCardID => $gaOverrideNames) {
        if(($CardActivateAbilityCountData[$gaOverrideCardID] ?? 0) >= count($gaOverrideNames)) continue;
        $CardActivateAbilityCountData[$gaOverrideCardID] = count($gaOverrideNames);
        foreach($gaOverrideNames as $gaOverrideIdx => $gaOverrideName) {
            $CardActivateAbilityCountNamesData[$gaOverrideCardID . ":" . $gaOverrideIdx] = $gaOverrideName;
        }
    }
}

// ---------------------------------------------------------------------------------------------
// Fixed-index MZMove loop overrides (generated ability bodies that moved the top N cards with a loop of
// MZMove($player, "myDeck-0", ...))
//
// Remove() only flags a slot removed (no splice -- the splice happens in
// DecisionQueueController::CleanupRemovedCards()), so in a loop "myDeck-0" resolves to the SAME removed slot on
// every iteration after the first and MZMove's own "already removed" guard silently no-ops: only the top card was
// ever moved (proven by 86027696, Galestream Insight). Each closure below is the generated body verbatim except
// that the loop uses MZMoveTopOfZone() (GameLogic.php), which compacts the zone when the top slot is a removed
// phantom and then MZMoves the real top card with full MZMove semantics.
// ---------------------------------------------------------------------------------------------

// Paired Minds, Kindred Souls (7qjnqww067): "Look at the top ten cards of your deck. Reveal a Horse ally card from
// among them and put it into your hand. Put the rest on the bottom of your deck in any order."
$cardActivatedAbilities["7qjnqww067:0"] = function($player) { //7qjnqww067
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $deck = GetZone("myDeck");
  $count = min(10, count($deck));
  if($count == 0) return;
  for($i = 0; $i < $count; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  PairedMindsChoose($player);
  DecisionQueueController::AddDecision($player, "CUSTOM", "7qjnqww067:0:CardActivated-1", 1);
};

// SignalTech X Ultra (vFvhZeunOc): "[REST]: Look at the top three cards of your deck. You may reveal an ally card from among them and
// put it into your hand. Put the rest on the bottom of your deck in any order. If a card was put into your hand this way, sacrifice
// CARDNAME." and Lucia, Reclaimed Blight (fIQR28QmYg): "[Class Bonus] On Enter: Look at the top six cards of your deck. You may
// reveal a Spell card from among them and put it into your memory. Put the rest on the bottom of your deck in any order."
//
// Both generated pairs share ResolveTopDeckOptionalSelection() (GameLogic.php), and each had three defects the generated source
// cannot fix in place: (1) the "rest on the bottom" loop was a fixed-index MZMove($player, "myDeck-0", "myDeck") loop (fixed in
// GameLogic.php with MZMoveTopOfZone()); (2) the follow-up handler ("...:ActivateAbility-1" / "...:Enter-1") passed an UNDEFINED
// $topCount (the variable only exists in the other closure), so after a reveal NOTHING was put on the bottom; the count is now
// carried in the "TopDeckOptionalCount" decision-queue variable; (3) the follow-up was queued without dontSkipOnPass, so declining
// the optional reveal (MZMAYCHOOSE answered PASS) skipped it and left the looked-at cards on top instead of on the bottom.
$activateAbilityAbilities["vFvhZeunOc:0"] = function($player) { //Find ally in top three
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $abilityIndex = DecisionQueueController::GetVariable("abilityIndex");
  $topCount = min(3, count(GetDeck($player)));
  if($topCount <= 0) return;
  DecisionQueueController::StoreVariable("TopDeckOptionalCount", strval($topCount));
  $candidates = [];
  for($i = 0; $i < $topCount; ++$i) {
      $deckObj = GetZoneObject("myDeck-" . $i);
      if($deckObj !== null && PropertyContains(CardType($deckObj->CardID), "ALLY")) $candidates[] = "myDeck-" . $i;
  }
  if(empty($candidates)) {
      ResolveTopDeckOptionalSelection($player, "-", $topCount, "myHand", null);
      return;
  }
  $candidateStr = implode("&", $candidates);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "Reveal_an_ally_and_put_it_into_your_hand?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "vFvhZeunOc:0:ActivateAbility-1", 1, dontSkipOnPass:1);
};
$customDQHandlers["vFvhZeunOc:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) { //Find ally in top three
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $abilityIndex = DecisionQueueController::GetVariable("abilityIndex");
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "vFvhZeunOc:0:ActivateAbility-1")) return;
  $chosen = $lastDecision;
  $topCount = intval(DecisionQueueController::GetVariable("TopDeckOptionalCount"));
  ResolveTopDeckOptionalSelection($player, $chosen, $topCount, "myHand", $mzID);
};
$enterAbilities["fIQR28QmYg:0"] = function($player) { //Find Spell in top six
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  if(!IsClassBonusActive($player, CardClasses("fIQR28QmYg"))) return;
  $topCount = min(6, count(GetDeck($player)));
  if($topCount <= 0) return;
  DecisionQueueController::StoreVariable("TopDeckOptionalCount", strval($topCount));
  $candidates = [];
  for($i = 0; $i < $topCount; ++$i) {
      $deckObj = GetZoneObject("myDeck-" . $i);
      if($deckObj !== null && PropertyContains(CardSubtypes($deckObj->CardID), "SPELL")) $candidates[] = "myDeck-" . $i;
  }
  if(empty($candidates)) {
      ResolveTopDeckOptionalSelection($player, "-", $topCount, "myMemory", null);
      return;
  }
  $candidateStr = implode("&", $candidates);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "Reveal_a_Spell_and_put_it_into_memory?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "fIQR28QmYg:0:Enter-1", 1, dontSkipOnPass:1);
};
$customDQHandlers["fIQR28QmYg:0:Enter-1"] = function($player, $parts, $lastDecision) { //Find Spell in top six
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "fIQR28QmYg:0:Enter-1")) return;
  $chosen = $lastDecision;
  $topCount = intval(DecisionQueueController::GetVariable("TopDeckOptionalCount"));
  ResolveTopDeckOptionalSelection($player, $chosen, $topCount, "myMemory", null);
};

// ---------------------------------------------------------------------------------------------
// Storm Tyrant's Eye (EQZZsiUDyl): the generated body moved the arcane card to hand and then put the rest on the bottom with a loop of
// MZMove($player, "myDeck-0", "myDeck"): Remove() only flags a slot removed, so "myDeck-0" re-resolved to the same removed slot and only ONE
// revealed card reached the bottom (the others stayed on top). Verbatim body with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$activateAbilityAbilities["EQZZsiUDyl:0"] = function($player) { //
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $abilityIndex = DecisionQueueController::GetVariable("abilityIndex");
  // Storm Tyrant's Eye: [cost: banish self] Reveal until arcane found; deal unpreventable damage = cards revealed; put 1 to hand, rest to bottom
  $deck = &GetDeck($player);
  $revealed = 0;
  $foundIdx = -1;
  for($i = 0; $i < count($deck); ++$i) {
      $revealed++;
      $element = CardElement($deck[$i]->CardID);
      if(PropertyContains($element, "ARCANE")) {
          $foundIdx = $i;
          break;
      }
  }
  if($revealed > 0) {
      DealChampionDamage($player, $revealed);
  }
  if($foundIdx >= 0) {
      MZMove($player, "myDeck-" . $foundIdx, "myHand");
      $revealed--;
  }
  $deck = &GetDeck($player);
  for($i = 0; $i < $revealed && count($deck) > 0; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myDeck");
  }
};

// ---------------------------------------------------------------------------------------------
// Pure Cytosynth (172utOanGk): "[Dante Bonus] On Enter: Put the top three cards of your deck into your graveyard. Then empower X, where X is the
// amount of water element cards in your graveyard." The generated body milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"): Remove()
// only flags a slot removed (no splice), so "myDeck-0" re-resolved to the same removed slot and only ONE card was ever milled. Verbatim body with
// MillCards() (distinct slots, honours the Purging Tempest / Sasha banish redirects; it also cleans up before the water count below).
// ---------------------------------------------------------------------------------------------
$enterAbilities["172utOanGk:0"] = function($player) { //Dante Bonus — Mill three, then empower
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  MillCards($player, "myDeck", "myGraveyard", 3);
  DecisionQueueController::CleanupRemovedCards();
  $waterCount = count(ZoneSearch("myGraveyard", cardElements: ["WATER"]));
  Empower($player, $waterCount, "172utOanGk");
};

// ---------------------------------------------------------------------------------------------
// Overflow the Barrow (OiyjVzW7Av): "Put a haunt counter on your Phantasmagoria. Then put the top X cards from your deck into your graveyard, where X is
// the amount of haunt counters on your Phantasmagoria." The generated body milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"): Remove() only
// flags a slot removed (no splice), so only ONE card was ever milled. Verbatim body with MillCards() (distinct slots, honours Purging Tempest / Sasha).
// ---------------------------------------------------------------------------------------------
$cardActivatedAbilities["OiyjVzW7Av:0"] = function($player) { //OiyjVzW7Av
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  AddHauntToMastery($player, 1);
  $hauntCount = GetHauntCount($player);
  if($hauntCount > 0) MillCards($player, "myDeck", "myGraveyard", $hauntCount);
};

// ---------------------------------------------------------------------------------------------
// Icebound Slam (6fxxgmuesd): "On Attack: Put the top five cards of your deck into your graveyard. Then if there are five or more water element cards in your
// graveyard, Icebound Slam gets +5 POWER." The generated body milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"): Remove() only flags a slot
// removed (no splice), so only ONE card was ever milled. Verbatim body with MillCards() (distinct slots, honours Purging Tempest / Sasha); the cleanup
// splices the milled slots out before the water cards are counted.
// ---------------------------------------------------------------------------------------------
$onAttackAbilities["6fxxgmuesd:0"] = function($player) { //6fxxgmuesd
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Icebound Slam: On Attack: Mill 5, then if 5+ water in graveyard, +5 POWER
  MillCards($player, "myDeck", "myGraveyard", 5);
  DecisionQueueController::CleanupRemovedCards();
  $waterCards = ZoneSearch("myGraveyard", cardElements: ["WATER"]);
  if(count($waterCards) >= 5) {
      $mzID = DecisionQueueController::GetVariable("mzID");
      AddTurnEffect($mzID, "6fxxgmuesd");
  }
};

// ---------------------------------------------------------------------------------------------
// Waterfall Veiler (x6jo8zxhl9): "On Champion Hit: That opponent puts the top four cards of their deck into their graveyard." The generated body milled with a
// loop of MZMove($player, "theirDeck-0", "theirGraveyard"): Remove() only flags a slot removed (no splice), so only ONE card was ever milled. Verbatim body with
// MillCards() (distinct slots, honours Purging Tempest / Sasha; "theirDeck"/"theirGraveyard" are in the Veiler controller's perspective).
// ---------------------------------------------------------------------------------------------
$onHitAbilities["x6jo8zxhl9:0"] = function($player) { //x6jo8zxhl9
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Waterfall Veiler: On Champion Hit: opponent puts top 4 cards of deck into graveyard.
  $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
  if($hitTarget === null || $hitTarget === "-" || $hitTarget === "") return;
  $hitObj = GetZoneObject($hitTarget);
  if($hitObj === null || $hitObj->removed) return;
  if(!PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) return;
  MillCards($player, "theirDeck", "theirGraveyard", 4);
};

// ---------------------------------------------------------------------------------------------
// Dormant Sacrificial Altar (px8jypwc8t): "Sacrifice an Automaton ally and a Human ally: Put the top two cards of your deck into your graveyard." The generated
// second-sacrifice handler milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"): Remove() only flags a slot removed (no splice), so only ONE card
// was ever milled. Verbatim body with MillCards() (distinct slots, honours Purging Tempest / Sasha).
// ---------------------------------------------------------------------------------------------
$customDQHandlers["px8jypwc8t:0:ActivateAbility-2"] = function($player, $parts, $lastDecision) { //Sacrifice
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $abilityIndex = DecisionQueueController::GetVariable("abilityIndex");
  DecisionQueueController::StoreVariable("humanTarget", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "px8jypwc8t:0:ActivateAbility-2")) return;
  $autoTarget = DecisionQueueController::GetVariable("autoTarget");
  $humanTarget = $lastDecision;
  DoSacrificeFighter($player, $humanTarget);
  DecisionQueueController::CleanupRemovedCards();
  MillCards($player, "myDeck", "myGraveyard", 2);
};

// ---------------------------------------------------------------------------------------------
// Lena, Dorumegia's Herald (gwve1d47o7) and Enhance Hearing (edg616r0za): "Look at the top N cards of your deck. You may reveal a ... card from among them and put it
// into your hand. Put the rest on the bottom of your deck in any order." The generated bodies looked with a loop of MZMove($player, "myDeck-0", "myTempZone"):
// Remove() only flags a slot removed (no splice), so "myDeck-0" re-resolved to the same removed slot and only the TOP card was ever looked at. Also the
// "may reveal" follow-up CUSTOM was skipped on PASS (dontSkipOnPass missing), so declining stranded the looked-at card in the temp zone. Verbatim bodies with
// MZMoveTopOfZone() and dontSkipOnPass.
// ---------------------------------------------------------------------------------------------
$activateAbilityAbilities["gwve1d47o7:0"] = function($player) { //Look
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $abilityIndex = DecisionQueueController::GetVariable("abilityIndex");
  // Pay reserve cost: (4), or (2) if distant
  $obj = GetZoneObject($mzID);
  $baseCost = 4;
  if($obj !== null && IsDistant($obj)) $baseCost = 2;
  for($i = 0; $i < $baseCost; ++$i) {
      DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
  }
  // Look at top 4
  $deck = &GetDeck($player);
  $count = min(4, count($deck));
  if($count === 0) return;
  for($i = 0; $i < $count; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  // Find Ranger allies
  $eligible = [];
  $tempCards = ZoneSearch("myTempZone");
  foreach($tempCards as $tmz) {
      $tobj = GetZoneObject($tmz);
      if($tobj !== null && PropertyContains(CardType($tobj->CardID), "ALLY") && PropertyContains(CardSubtypes($tobj->CardID), "RANGER")) {
          $eligible[] = $tmz;
      }
  }
  if(empty($eligible)) {
      EnhanceHearingFinish($player, "PASS");
      return;
  }
  $eligibleStr = implode("&", $eligible);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $eligibleStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "gwve1d47o7:0:ActivateAbility-1", 1, "", 1);
};

$cardActivatedAbilities["edg616r0za:0"] = function($player) { //Look at top 3, may take wind/reaction
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $deck = &GetDeck($player);
  $count = min(3, count($deck));
  if($count === 0) return;
  for($i = 0; $i < $count; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  $eligible = [];
  $tempCards = ZoneSearch("myTempZone");
  foreach($tempCards as $tmz) {
      $tobj = GetZoneObject($tmz);
      if(CardElement($tobj->CardID) === "WIND" || PropertyContains(CardSubtypes($tobj->CardID), "REACTION")) {
          $eligible[] = $tmz;
      }
  }
  if(empty($eligible)) {
      EnhanceHearingFinish($player, "PASS");
      return;
  }
  $eligibleStr = implode("&", $eligible);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $eligibleStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "edg616r0za:0:CardActivated-1", 1, "", 1);
};

// ---------------------------------------------------------------------------------------------
// Captain Archer (disqw3d0o5): "[Class Bonus] On Enter: If Captain Archer is imbued, look at the top six cards of your deck. You may put a wind element ally card with
// reserve cost 3 or less from among them onto the field distant. Put the rest on the bottom of your deck in any order." Three defects in the generated body/handler:
// (1) the look was a loop of MZMove($player, "myDeck-0", "myTempZone"): Remove() only flags a slot removed (no splice), so only the TOP card was ever looked at;
// (2) the rest went back with PutTempZoneOnTopOfDeck() (the TOP of the deck, reversed) instead of the bottom; (3) the "may put" follow-up CUSTOM was skipped on PASS
// (dontSkipOnPass missing), so declining left the looked-at card in the temp zone; (4) the reserve-cost check called CardCost(), which does not exist in GrandArchiveSim
// (fatal as soon as a wind ally was among the six): now CardCost_reserve(). Verbatim bodies with MZMoveTopOfZone(), PutTempZoneOnBottomOfDeck() and dontSkipOnPass.
// ---------------------------------------------------------------------------------------------
$enterAbilities["disqw3d0o5:0"] = function($player) { //disqw3d0o5
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Captain Archer: [CB] On Enter: if imbued, look at top 6.
  // May put a wind element ally with reserve cost 3 or less onto field distant.
  // Put the rest on bottom in any order.
  if(!IsClassBonusActive($player, ["RANGER"])) return;
  $isImbued = DecisionQueueController::GetVariable("isImbued");
  if($isImbued !== "YES") return;
  $deck = GetDeck($player);
  if(empty($deck)) return;
  $lookCount = min(6, count($deck));
  for($i = 0; $i < $lookCount; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  $tempCards = ZoneSearch("myTempZone");
  $validTargets = [];
  foreach($tempCards as $tc) {
      $tcObj = GetZoneObject($tc);
      if($tcObj !== null && PropertyContains(CardType($tcObj->CardID), "ALLY")
          && CardElement($tcObj->CardID) === "WIND"
          && intval(CardCost_reserve($tcObj->CardID)) <= 3) {
          $validTargets[] = $tc;
      }
  }
  if(empty($validTargets)) {
      PutTempZoneOnBottomOfDeck($player);
      return;
  }
  $validStr = implode("&", $validTargets);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $validStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "disqw3d0o5:0:Enter-1", 1, "", 1);
};

$customDQHandlers["disqw3d0o5:0:Enter-1"] = function($player, $parts, $lastDecision) { //disqw3d0o5
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "disqw3d0o5:0:Enter-1")) return;
  $chosen = $lastDecision;
  if($chosen !== "-" && $chosen !== "PASS" && $chosen !== "") {
      MZMove($player, $chosen, "myField");
      // Find the newly added card and make it distant
      $field = &GetField($player);
      $newIdx = count($field) - 1;
      BecomeDistant($player, "myField-" . $newIdx);
  }
  PutTempZoneOnBottomOfDeck($player);
};

// ---------------------------------------------------------------------------------------------
// Restoring Embers (FnTT1G4OQg): "Recover 4. Then if your influence is four or less, draw a card into your memory." The generated body called DrawToMemory(), which does
// not exist in GrandArchiveSim (the engine function is DrawIntoMemory()): a fatal "undefined function" every time the influence condition held, after the recovery.
// Verbatim body with DrawIntoMemory().
// ---------------------------------------------------------------------------------------------
$cardActivatedAbilities["FnTT1G4OQg:0"] = function($player) { //FnTT1G4OQg
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  RecoverChampion($player, 4);
  if(GetInfluence($player) <= 4) {
      DrawIntoMemory($player, 1);
  }
};

// ---------------------------------------------------------------------------------------------
// Genuflecting Execution (iqzaNLhqk4): "Destroy up to two target rested allies." Both generated target handlers called DestroyAlly(), which does not exist in
// GrandArchiveSim (the engine function is DoAllyDestroyed()): a fatal "undefined function" as soon as the first target was chosen, so nothing was ever destroyed.
// Verbatim handlers with DoAllyDestroyed().
// ---------------------------------------------------------------------------------------------
$customDQHandlers["iqzaNLhqk4:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //iqzaNLhqk4
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen1", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "iqzaNLhqk4:0:CardActivated-1")) return;
  $chosen1 = $lastDecision;
  if($chosen1 == "-") return;
  DoAllyDestroyed($player, $chosen1);
  DecisionQueueController::CleanupRemovedCards();
  $allAllies2 = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]));
  $rested2 = [];
  foreach($allAllies2 as $aMZ2) {
      $aObj2 = GetZoneObject($aMZ2);
      if($aObj2 !== null && isset($aObj2->Status) && $aObj2->Status == 1) {
          $rested2[] = $aMZ2;
      }
  }
  $rested2 = FilterSpellshroudTargets($rested2);
  if(empty($rested2)) return;
  $targetStr2 = implode("&", $rested2);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr2, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "iqzaNLhqk4:0:CardActivated-2", 1);
};

$customDQHandlers["iqzaNLhqk4:0:CardActivated-2"] = function($player, $parts, $lastDecision) { //iqzaNLhqk4
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen2", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "iqzaNLhqk4:0:CardActivated-2")) return;
  $chosen1 = DecisionQueueController::GetVariable("chosen1");
  $chosen2 = $lastDecision;
  if($chosen2 == "-") return;
  DoAllyDestroyed($player, $chosen2);
};

// ---------------------------------------------------------------------------------------------
// Naia, Diviner of Fortunes (jdmthh88rx): "[Class Bonus] On Enter: Reveal the top three cards from your deck. Banish one of those cards and put the rest into your graveyard. If the banished
// card is a Spell card, you may activate it as long as you control CARDNAME." The generated body revealed with a loop of MZMove($player, "myDeck-0", "myTempZone"): Remove() only flags a slot
// removed (no splice), so "myDeck-0" re-resolved to the same removed slot and only the TOP card was ever revealed (it was the only choice, and nothing else went to the graveyard).
// Verbatim body with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$enterAbilities["jdmthh88rx:0"] = function($player) { //jdmthh88rx
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Naia, Diviner of Fortunes: [CB] On Enter: Reveal top 3, banish 1, rest to GY.
  // If banished card is a Spell, may activate it while controlling Naia.
  if(!IsClassBonusActive($player, ["MAGE"])) return;
  $deck = GetDeck($player);
  if(empty($deck)) return;
  $revealCount = min(3, count($deck));
  // Move top N cards to TempZone for display
  for($i = 0; $i < $revealCount; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  $tempCards = ZoneSearch("myTempZone");
  if(empty($tempCards)) return;
  $tempStr = implode("&", $tempCards);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $tempStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "jdmthh88rx:0:Enter-1", 1);
};

// ---------------------------------------------------------------------------------------------
// Kind Beastcaller (k02kvfblwa): "[Class Bonus] On Enter: Look at the top five cards of your deck. You may reveal an Animal or Beast ally card from among them and put it into your hand. Put the rest on
// the bottom of your deck in any order." The generated body looked with a loop of MZMove($player, "myDeck-0", "myTempZone"): Remove() only flags a slot removed (no splice), so only the TOP card was
// ever looked at. The "may reveal" follow-up CUSTOM was also added without dontSkipOnPass, so declining (PASS) skipped it and stranded the looked-at card in the temp zone. Verbatim bodies with
// MZMoveTopOfZone(), dontSkipOnPass:1 and a PASS guard in the handler.
// ---------------------------------------------------------------------------------------------
$enterAbilities["k02kvfblwa:0"] = function($player) { //k02kvfblwa
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Kind Beastcaller: [Class Bonus] On Enter: Look at top 5.
  // May reveal an Animal or Beast ally and put it into hand. Rest to bottom.
  if(!IsClassBonusActive($player, ["TAMER"])) return;
  $deck = GetDeck($player);
  if(empty($deck)) return;
  $lookCount = min(5, count($deck));
  for($i = 0; $i < $lookCount; $i++) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  $tempCards = ZoneSearch("myTempZone");
  $validTargets = [];
  foreach($tempCards as $tc) {
      $tcObj = GetZoneObject($tc);
      if($tcObj !== null && PropertyContains(CardType($tcObj->CardID), "ALLY")) {
          $subtypes = CardSubtypes($tcObj->CardID);
          if(PropertyContains($subtypes, "ANIMAL") || PropertyContains($subtypes, "BEAST")) {
              $validTargets[] = $tc;
          }
      }
  }
  if(empty($validTargets)) {
      PutTempZoneOnBottomOfDeck($player);
      return;
  }
  $validStr = implode("&", $validTargets);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $validStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "k02kvfblwa:0:Enter-1", 1, "", 1);
};

$customDQHandlers["k02kvfblwa:0:Enter-1"] = function($player, $parts, $lastDecision) { //k02kvfblwa
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "k02kvfblwa:0:Enter-1")) return;
  $chosen = $lastDecision;
  if($chosen !== "-" && $chosen !== "" && $chosen !== "PASS") {
      Reveal($player, revealedMZ: $chosen);
      MZMove($player, $chosen, "myHand");
  }
  PutTempZoneOnBottomOfDeck($player);
};

// ---------------------------------------------------------------------------------------------
// Kongming, Erudite Strategist (0i139x5eub): "Kongming Lineage -- On Enter: Banish the top card of your deck. Until the beginning of your next turn, you may play it as long as your Shifting
// Currents face North. Repeat this process for East, South, and West." The generated body banished with a loop of MZMove($player, "myDeck-0", "myBanish"): Remove() only flags a slot removed
// (no splice), so "myDeck-0" re-resolved to the same removed slot, MZMove returned null on the second pass and the loop broke -- only the North card was ever banished (East, South and West
// never happened). Verbatim body with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$enterAbilities["0i139x5eub:0"] = function($player) { //0i139x5eub
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Banish top card of deck while facing each direction; tag with direction for "may play until next turn"
  $directions = ["NORTH", "EAST", "SOUTH", "WEST"];
  foreach($directions as $dir) {
      $deck = GetZone("myDeck");
      if(empty($deck)) break;
      $banishedObj = MZMoveTopOfZone($player, "myDeck", "myBanish");
      if($banishedObj === null) break;
      if(!is_array($banishedObj->TurnEffects)) $banishedObj->TurnEffects = [];
      $banishedObj->TurnEffects[] = "KONGMING_" . $dir;
  }
};
