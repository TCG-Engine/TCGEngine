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
