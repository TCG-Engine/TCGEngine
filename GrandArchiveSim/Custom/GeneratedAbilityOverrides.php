<?php
// Overrides for GeneratedCode/GeneratedMacroCode.php ability bodies that are wrong at the source
// in the authored card database. These remaining workarounds are being migrated to the hosted
// source in reviewed batches; generated files must never be hand-edited.
// Apply the 20261019 migrations in DevTools/card-code-migrations/ and regenerate before using
// this revision with an older card database (thirty-one cards now have source corrections applied).
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
    $assassins = ZoneSearch("myField", ["ALLY", "CHAMPION"], cardClasses: ["ASSASSIN"]);
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
// "Any amount ... onto any amount of" is the player's choice: a split-assign over every Memorite Obelith you control (up to all the sheen, any distribution, nothing is fine).
$cardActivatedAbilities["lsLd8ADGAe:0"] = function($player) { // Silvergale Monstrosity's Call
    GAQueueFixedPrepare($player, 2);
    DecisionQueueController::AddDecision($player, "CUSTOM", "GASilvergaleResolve", 1, dontSkipOnPass:1);
};
$customDQHandlers["GASilvergaleResolve"] = function($player, $parts, $lastDecision) {
    SummonMemorite($player, "fdnlbJm3hr");
    if(DecisionQueueController::GetVariable("wasPrepared") !== "YES") return;
    $sheen = GetSheenCount($player);
    if($sheen <= 0) return;
    $obeliths = [];
    $field = GetZone("myField");
    for($i = 0; $i < count($field); ++$i) {
        if(!$field[$i]->removed && $field[$i]->CardID === "fdnlbJm3hr") $obeliths[] = "myField-" . $i;
    }
    if(empty($obeliths)) return;
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", $sheen . "|" . implode("&", $obeliths) . "|UPTO", 1, tooltip:"Move_sheen_from_Fractured_Memories_onto_Memorite_Obelith_allies");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GASilvergaleMoveSheen", 1, dontSkipOnPass:1);
};
$customDQHandlers["GASilvergaleMoveSheen"] = function($player, $parts, $lastDecision) {
    $lastDecision = ConsumeDeclinedChoice($player, $lastDecision);
    if($lastDecision === "-" || $lastDecision === "") return;
    foreach(explode(",", $lastDecision) as $pair) {
        $pairParts = explode(":", $pair);
        if(count($pairParts) < 2) continue;
        $targetObj = GetZoneObject($pairParts[0]);
        if($targetObj === null || $targetObj->removed || $targetObj->CardID !== "fdnlbJm3hr") continue; // only allies named Memorite Obelith
        $amount = min(intval($pairParts[1]), GetSheenCount($player));
        if($amount <= 0) continue;
        RemoveSheenFromMastery($player, $amount);
        AddCounters($player, $pairParts[0], "sheen", $amount);
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
      $banishedObj->TurnEffects[] = "KONGMING_TURN_" . GetTurnNumber(); // the turn it was banished: the tags must outlive THIS turn's recollection phase (the level-up happens in the Materialize Phase)
  }
};

// ---------------------------------------------------------------------------------------------
// Tide Diviner (zrBBvgIvt6): "On Enter: Look at the top 1+LV cards of your deck. Put one of those cards into your hand and the rest into your graveyard." The generated body looked with a loop of
// MZMove($player, "myDeck-0", "myTempZone"): Remove() only flags a slot removed (no splice), so "myDeck-0" re-resolved to the same removed slot and only the TOP card was ever looked at (any champion
// level above 0 still showed a single card, and the rest never went to the graveyard). Verbatim body with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$enterAbilities["zrBBvgIvt6:0"] = function($player) { //Look at Top 1+LV Cards
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // On Enter: look at top 1+LV cards of your deck, put one in hand, rest to graveyard
  $champLevel = PlayerLevel($player);
  $n = 1 + $champLevel;
  $deck = &GetDeck($player);
  $availableCards = min($n, count($deck));
  if($availableCards === 0) return;
  for($i = 0; $i < $availableCards; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  $tempCards = ZoneSearch("myTempZone");
  $tempMZList = implode("&", $tempCards);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $tempMZList, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "zrBBvgIvt6:0:Enter-1", 1);
};

// ---------------------------------------------------------------------------------------------
// Nature's Appeal (oj0oh7pjoq): "Reveal the top LV cards of your deck. Put one of those cards into your hand and another into your material deck preserved. Put the rest on the bottom of your deck
// in any order." The generated body revealed with a loop of MZMove($player, "myDeck-0", "myTempZone"): Remove() only flags a slot removed (no splice), so "myDeck-0" re-resolved to the same removed
// slot and only the TOP card was ever revealed (it went straight to hand; no material card, nothing on the bottom). Verbatim body with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$cardActivatedAbilities["oj0oh7pjoq:0"] = function($player) { //oj0oh7pjoq
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Nature's Appeal: Reveal top LV cards. Put one into hand, one into material preserved. Rest to bottom.
  $lvl = PlayerLevel($player);
  $deck = GetDeck($player);
  $available = min($lvl, count($deck));
  if($available == 0) return;
  for($i = 0; $i < $available; $i++) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  $tempCards = ZoneSearch("myTempZone");
  if(count($tempCards) == 1) {
      MZMove($player, $tempCards[0], "myHand");
      return;
  }
  $tempStr = implode("&", $tempCards);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $tempStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "oj0oh7pjoq:0:CardActivated-1", 1);
};

// ---------------------------------------------------------------------------------------------
// Nature's Insight (3bS1Y9OQrF): "Reveal a card from your memory and put it into your material deck preserved. X is that card's reserve cost. Then reveal the top X cards of your deck and put them into
// your material deck preserved." The generated handler moved the deck cards with a loop of MZMove($player, "myDeck-0", "myMaterial") reading $deck[0]: Remove() only flags a slot removed (no splice), so after
// the first card "myDeck-0" / $deck[0] was the same removed slot -- only ONE deck card reached the material deck (the rest of the X stayed on top). Verbatim handler with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$customDQHandlers["3bS1Y9OQrF:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Reveal memory card → material preserved; put top X deck cards into material preserved
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "3bS1Y9OQrF:0:CardActivated-1")) return;
  $chosen = $lastDecision;
  global $Preserve_Cards;
  $chosenObj = GetZoneObject($chosen);
  if($chosenObj === null) return;
  $x = max(0, intval(CardCost_reserve($chosenObj->CardID)));
  $chosenCardID = $chosenObj->CardID;
  MZMove($player, $chosen, "myMaterial");
  $Preserve_Cards[$chosenCardID] = true;
  DecisionQueueController::CleanupRemovedCards();
  for($i = 0; $i < $x; ++$i) {
      $deck = GetZone("myDeck");
      if(empty($deck)) break;
      $movedObj = MZMoveTopOfZone($player, "myDeck", "myMaterial");
      if($movedObj === null) break;
      $Preserve_Cards[$movedObj->CardID] = true;
  }
};

// ---------------------------------------------------------------------------------------------
// Spring Cleaning (dZ0Y2ILgZW): "Put one of your omens into your graveyard. If you do, look at the top three cards of your deck. Banish one of them and put an omen counter on it. Put the rest on the
// bottom of your deck in any order." The generated handler looked with a loop of MZMove($player, "myDeck-0", "myTempZone"): Remove() only flags a slot removed (no splice), so only the TOP card was ever
// looked at (the 'choice' of which to banish was a single card and nothing else went to the bottom). Verbatim handler with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$customDQHandlers["dZ0Y2ILgZW:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //dZ0Y2ILgZW
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosenOmen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "dZ0Y2ILgZW:0:CardActivated-1")) return;
  $chosenOmen = $lastDecision;
  MZMove($player, $chosenOmen, "myGraveyard");
  
  $deck = GetZone("myDeck");
  $revealCount = min(3, count($deck));
  if($revealCount <= 0) return;
  for($i = 0; $i < $revealCount; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  
  $tempCards = ZoneSearch("myTempZone");
  if(empty($tempCards)) return;
  $banishStr = implode("&", $tempCards);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $banishStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "dZ0Y2ILgZW:0:CardActivated-2", 1);
};

// ---------------------------------------------------------------------------------------------
// Advent of the Stormcaller (ZSSegCjquB): "Reveal the top LV cards of your deck. You may banish any amount of arcane element cards from among them. For each card banished this way, choose a unit and
// deal 2 damage to it. Put the rest of the revealed cards on the top or on the bottom of your deck in any order." The generated body revealed with a loop of MZMove($player, "myDeck-0",
// "myTempZone"): Remove() only flags a slot removed (no splice), so only the TOP card was ever revealed. (The banish loop / decline handling in Custom/CardDQHandlers.php is fixed separately.)
// Verbatim body with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$cardActivatedAbilities["ZSSegCjquB:0"] = function($player) { //Advent of the Stormcaller
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $lv = PlayerLevel($player);
  $deck = &GetDeck($player);
  $n = min($lv, count($deck));
  if($n == 0) return;
  for($i = 0; $i < $n; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myTempZone");
  }
  $tempCards = ZoneSearch("myTempZone");
  foreach($tempCards as $tc) {
      DoRevealCard($player, $tc);
  }
  AdventStormcallerBanishLoop($player, 0);
};

// ---------------------------------------------------------------------------------------------
// Desperate Cavalier (slmer06rku): "[Class Bonus] On Attack: If your influence is four or less, banish the top two cards of your deck. As long as they're banished you may activate them..." The generated
// body banished with a loop of MZMove($player, "myDeck-0", "myBanish"): Remove() only flags a slot removed (no splice), so on the second pass "myDeck-0" re-resolved to the same removed slot, MZMove returned
// null and the loop broke -- only ONE card was ever banished (and only that one was tagged). Verbatim body with MZMoveTopOfZone().
// ---------------------------------------------------------------------------------------------
$onAttackAbilities["slmer06rku:0"] = function($player) { //Desperate Cavalier attack banish
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  if(!IsClassBonusActive($player, CardClasses("slmer06rku"))) return;
  if(GetInfluence($player) > 4) return;
  for($i = 0; $i < 2; ++$i) {
      if(empty(ZoneSearch("myDeck"))) break;
      $banishedObj = MZMoveTopOfZone($player, "myDeck", "myBanish");
      if($banishedObj === null) break;
      $banishedObj->AddTurnEffects('_desperateCavalier');
  }
  DecisionQueueController::CleanupRemovedCards();
};

// ---------------------------------------------------------------------------------------------
// Gaia's Songbird (sHzSmygjWY) and Silvie, Earth's Tune (ZR8tnLruR6): "Reveal cards from the top of your deck until you reveal a [Beast ally / tera Animal or Beast ally] card. Put that card into your hand
// and the rest on the bottom of your deck." The generated bodies put the revealed cards that were not taken on the bottom with a loop of MZMove($player, "myDeck-0", "myDeck"): Remove() only flags a slot
// removed (no splice), so "myDeck-0" re-resolved to the same removed slot and only ONE of them reached the bottom (the others stayed on top). Verbatim bodies with MZMoveTopOfZone().
// (The printed "in a random order" is not modelled: the revealed cards keep their order.)
// ---------------------------------------------------------------------------------------------
$enterAbilities["sHzSmygjWY:0"] = function($player) { //sHzSmygjWY
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Gaia's Songbird: [Class Bonus] On Enter: reveal until Beast ally found
  if(!IsClassBonusActive($player)) return;
  $deck = &GetDeck($player);
  $revealed = 0;
  $foundIdx = -1;
  for($i = 0; $i < count($deck); ++$i) {
      $revealed++;
      $type = CardType($deck[$i]->CardID);
      $subtypes = CardSubtypes($deck[$i]->CardID);
      if(PropertyContains($type, "ALLY") && PropertyContains($subtypes, "BEAST")) {
          $foundIdx = $i;
          break;
      }
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

$enterAbilities["ZR8tnLruR6:0"] = function($player) { //ZR8tnLruR6
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Silvie, Earth's Tune: On Enter: reveal until tera Animal/Beast ally found
  $deck = &GetDeck($player);
  $revealed = 0;
  $foundIdx = -1;
  for($i = 0; $i < count($deck); ++$i) {
      $revealed++;
      $element = CardElement($deck[$i]->CardID);
      $type = CardType($deck[$i]->CardID);
      $subtypes = CardSubtypes($deck[$i]->CardID);
      if(PropertyContains($element, "TERA") && PropertyContains($type, "ALLY") && (PropertyContains($subtypes, "ANIMAL") || PropertyContains($subtypes, "BEAST"))) {
          $foundIdx = $i;
          break;
      }
  }
  if($foundIdx >= 0) {
      MZMove($player, "myDeck-" . $foundIdx, "myHand");
      $revealed--;
  }
  // Move remaining revealed cards to bottom in random order
  $deck = &GetDeck($player);
  for($i = 0; $i < $revealed && count($deck) > 0; ++$i) {
      MZMoveTopOfZone($player, "myDeck", "myDeck");
  }
};

// ---------------------------------------------------------------------------------------------
// Blinding Lapse (rKKDhaLJ8w): "Target opponent with influence nine or more puts all cards from their hand into their memory. Then that player banishes three cards at random from their memory."
// The generated body was registered ONLY in $activateCardAbilities, which nothing dispatches (the live registry for an action's effect is $cardActivatedAbilities), so playing Blinding Lapse
// resolved with no effect at all. The generated body also moved the hand with a loop of MZMove($player, "theirHand-0", "theirMemory") under while(count(GetHand())>0): Remove() only flags a slot
// removed, so it would have re-moved the same removed slot forever. Live registration with MZMoveTopOfZone() and one splice up front so the influence count is exact.
// ---------------------------------------------------------------------------------------------
$cardActivatedAbilities["rKKDhaLJ8w:0"] = function($player) { //Memory collapse
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $opponent = ($player == 1) ? 2 : 1;
  DecisionQueueController::CleanupRemovedCards();
  if(count(GetHand($opponent)) + count(GetMemory($opponent)) < 9) return;
  $handCount = count(GetHand($opponent));
  for($i = 0; $i < $handCount; ++$i) {
      MZMoveTopOfZone($player, "theirHand", "theirMemory");
  }
  LiquidAmnesiaBanish($player, "theirMemory", 3);
};

// ---------------------------------------------------------------------------------------------
// Starbirth (qxu89i1mrk): "Draw a card into your memory. Then summon an Astral Shard token." The generated body was registered ONLY in $activateCardAbilities, a table nothing dispatches (an action's
// effect lives in $cardActivatedAbilities), so playing Starbirth paid its cost and resolved with no effect. Live registration of the same body.
// ---------------------------------------------------------------------------------------------
$cardActivatedAbilities["qxu89i1mrk:0"] = function($player) { //Draw into memory and summon token
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DrawIntoMemory($player, 1);
  MZAddZone($player, "myField", "eP07Xxscuq");
};

// ---------------------------------------------------------------------------------------------
// Kingdom's Divide (qy34r8gffr): "Choose a card name. Until the beginning of your next turn, cards with the chosen name cost 2 more to activate." The generated opening body (build the list of card
// names and ask for one) was registered ONLY in the dead $activateCardAbilities table, so playing Kingdom's Divide never asked for a name and nothing was taxed -- although its follow-up handler
// ("qy34r8gffr:0:ActivateCard-1"), the cost surcharge and the expiry all exist. Live registration of the same body.
// ---------------------------------------------------------------------------------------------
$cardActivatedAbilities["qy34r8gffr:0"] = function($player) { //Choose a card name for tax
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $choices = [];
  $seen = [];
  foreach(["myMaterial", "myHand", "myMemory", "theirMemory", "myGraveyard", "theirGraveyard", "myBanish", "theirBanish", "myField", "theirField"] as $zoneName) {
      foreach(ZoneSearch($zoneName) as $choiceMZ) {
          $choiceObj = GetZoneObject($choiceMZ);
          if($choiceObj === null || isset($seen[$choiceObj->CardID])) continue;
          $seen[$choiceObj->CardID] = true;
          $choices[] = $choiceMZ;
      }
  }
  if(empty($choices)) return;
  $choiceStr = implode("&", $choices);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $choiceStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "qy34r8gffr:0:ActivateCard-1", 1);
};

// ---------------------------------------------------------------------------------------------
// Peer the Depths (6JMwc6cpRm): "Activate this card only during your recollection phase." The generated prereq only checked the phase (BREC), not whose recollection phase it was, so the NON-turn
// player was offered Peer the Depths in the turn player's recollection response window. Verbatim prereq plus the turn-player check.
// ---------------------------------------------------------------------------------------------
$activateCardPrereqs["6JMwc6cpRm:0"] = function($player, $mzID, $ignoreCost) { //Recollection-only activation prereq
  return GetCurrentPhase() === "BREC" && GetTurnPlayer() == $player;
};

// ---------------------------------------------------------------------------------------------
// Nameless Champion (18 printings): "This champion can't level up. (6): Draw a card and put a level counter on Nameless Champion. Activate this ability only once."
// The generated body only exists for 15 of the 18 printings (LahboNoSRx, foV3VG5iOr and K7jYO9IibV have no ability rows at all) and its once-only check lives in
// $activateAbilityPrereqs, which DoActivatedAbility()'s direct path never consults (the champion's implicit REST was what made a second activation fail in the same turn;
// with that REST removed the ability could be used every turn). One body for every printing; DoActivatedAbility() refuses a second activation before the cost is paid.
foreach(["9tmr8iel1m", "LahboNoSRx", "98i5ak5nwo", "f4rlv5dsrb", "j9fiu22ltl", "ztjuymn2ge", "as8yfa8ptg", "foV3VG5iOr", "K7jYO9IibV", "pv6ichyxj0", "jk9w4buhwk",
        "b53ccl9ipn", "thaqwi9apy", "k7sz76vn6u", "nq6nhjy85f", "mic7hijxlg", "86flbytki3", "0794z3ffck"] as $gaNamelessID) {
  $activateAbilityAbilities[$gaNamelessID . ":0"] = function($player) use ($gaNamelessID) { //Draw
    $mzID = DecisionQueueController::GetVariable("mzID");
    $obj = &GetZoneObject($mzID);
    if($obj === null || $obj->removed || NamelessChampionAbilityUsed($obj)) return;
    if(!is_array($obj->Counters)) $obj->Counters = [];
    $obj->Counters[$gaNamelessID . "_used"] = 1;
    Draw($player, 1);
    AddCounters($player, $mzID, "level", 1);
  };
}


// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
// Facet Together (XmsEbk19Iu): "Activate this card only during an opponent's turn. Sacrifice any amount of Memorite objects. Target weapon you control gets +X POWER until the end of
// your next turn, where X is the amount of objects sacrificed this way. Then put X sheen counters on your Fractured Memories."
// The effect was generated as an $enterAbilities entry on an Action (which OnCardActivated() now runs as the Action's effect). This override is the same body with the follow-up queued
// with dontSkipOnPass, so declining a further sacrifice ("any amount") still applies the bonus for the objects already sacrificed (FacetTogetherSacrifice consumes the decline).
$cardActivatedAbilities["XmsEbk19Iu:0"] = function($player) { //Facet Together
  DecisionQueueController::StoreVariable("FacetTogetherCount", "0");
  $memorites = ZoneSearch("myField", cardSubtypes: ["MEMORITE"]);
  if(empty($memorites)) return;
  $memStr = implode("&", $memorites);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $memStr, 1, tooltip:"Sacrifice_a_Memorite_object?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "FacetTogetherSacrifice", 1, dontSkipOnPass:1);
};


// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
// Dichroic Scorch (TlhsnnRhGK): "Remove all sheen counters from all units on the field. Then deal X damage to each unit except for your champion, where X is the amount of counters removed this way."
// The generated body had never run (an Action's effect generated as an On Enter ability, see OnCardActivated()) and is broken: its first loop binds `$field = &GetField($p)` and its second loop then
// assigns `$field = GetZone(...)` through that reference, overwriting the last player's real field array with a copy of the other player's field (the opposing champion was replaced by a copy of
// the activating player's champion, which then took the damage that was meant to skip it); and it removed counters through "myField-" paths for both players. Rewritten without the references and
// with each player's field addressed from the acting player's perspective.
$enterAbilities["TlhsnnRhGK:0"] = function($player) { //Dichroic Scorch
  global $playerID;
  $mzID = DecisionQueueController::GetVariable("mzID");
  $units = [];
  for($p = 1; $p <= 2; ++$p) {
      $zone = ($p == $playerID) ? "myField" : "theirField";
      $count = count(GetZone($zone));
      for($i = 0; $i < $count; ++$i) {
          $obj = GetZoneObject($zone . "-" . $i);
          if($obj === null || $obj->removed) continue;
          $type = EffectiveCardType($obj);
          $isChampion = PropertyContains($type, "CHAMPION");
          if(!PropertyContains($type, "ALLY") && !$isChampion) continue;
          $units[] = ["p" => $p, "mz" => $zone . "-" . $i, "champion" => $isChampion];
      }
  }
  $totalSheen = 0;
  foreach($units as $unit) {
      $sheen = GetCounterCount(GetZoneObject($unit["mz"]), "sheen");
      if($sheen <= 0) continue;
      RemoveCounters($unit["p"], $unit["mz"], "sheen", $sheen);
      $totalSheen += $sheen;
  }
  if($totalSheen <= 0) return;
  foreach($units as $unit) {
      if($unit["champion"] && $unit["p"] == $player) continue; // except for your champion
      DealDamage($player, $mzID, $unit["mz"], $totalSheen);
  }
};


// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
// Enthralling Visage (ycwz9gv4vm): "The next time damage would be dealt to target unit this turn, prevent 2 of that damage. When damage is prevented this way, banish target card in a graveyard."
// The generated second step tagged the unit with "ycwz_<graveyard card's Owner>_<cardID>", which CombatLogic.php reads back when the damage is prevented to find the graveyard to banish from. A graveyard
// object's Owner property is not set (it is only stamped when a card enters the field), so the tag read "ycwz__<cardID>" and the prevented damage banished nothing. The graveyard's owner is taken from the
// zone the card was chosen in instead.
$customDQHandlers["ycwz9gv4vm:0:Enter-2"] = function($player, $parts, $lastDecision) { //Enthralling Visage: target graveyard card
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosenGrav", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "ycwz9gv4vm:0:Enter-2")) return;
  $chosenUnit = DecisionQueueController::GetVariable("chosenUnit");
  $gravObj = GetZoneObject($lastDecision);
  if($gravObj === null) return;
  $graveyardOwner = (strpos($lastDecision, "my") === 0) ? $player : GetOpponent($player);
  AddTurnEffect($chosenUnit, "ycwz_" . $graveyardOwner . "_" . $gravObj->CardID);
};


// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
// Judas, Claret Intercessor (AbjQkcN57S): "[Class Bonus] Sacrifice another ally: ..." / "[Class Bonus] (2), Sacrifice another ally: ...". The generated prereqs accepted Judas himself as the ally to sacrifice
// (any ally in play); the sacrifice is now a real activation cost (ActivatedAbilityCost), so another ally has to exist.
$judasPrereq = function($player, $mzID, $abilityIndex) {
  if(!IsClassBonusActive($player, CardClasses("AbjQkcN57S"))) return false;
  foreach(ZoneSearch("myField", ["ALLY"]) as $allyMZ) {
      if($allyMZ !== $mzID) return true;
  }
  return false;
};
$activateAbilityPrereqs["AbjQkcN57S:0"] = $judasPrereq;
$activateAbilityPrereqs["AbjQkcN57S:1"] = $judasPrereq;

// Lucenia's Reign (zrvvwz3ww9): "(2), Discard a Chessman Command card: Target Chessman ally you control gets +1 LIFE until end of turn. Draw a card into your memory." The generated body discarded in the effect
// (after the opponent had priority) and nothing paid the (2); the discard and the (2) are now activation costs (ActivatedAbilityCost), so the effect only chooses the Chessman ally.
$activateAbilityAbilities["zrvvwz3ww9:0"] = function($player) { //Lucenia's Reign: target Chessman ally +1 LIFE, draw into memory
  $chessmanAllies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["CHESSMAN"]);
  if(empty($chessmanAllies)) return;
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $chessmanAllies), 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "zrvvwz3ww9:0:ActivateAbility-2", 1);
};


// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
// Clockwork Musicbox (q2svdv3zb9): "[REST]: You may activate a card banished by CARDNAME. (You still pay its costs.)" The generated body offered EVERY card it had banished (a mandatory choice) and the chosen card was
// moved to the hand before anything checked it could be activated, so picking one you could not pay for left it in your hand for free (after the REST cost was already paid). The ability now needs a banished card
// you can actually begin activating, offers only those (optionally: "you may"), and activates through ActivateBanishedCard(), which validates before it moves anything.
$activateAbilityPrereqs["q2svdv3zb9:0"] = function($player, $mzID, $abilityIndex) { //Activate prereq
  $sourceObject = GetZoneObject($mzID);
  if($sourceObject === null) return false;
  if($sourceObject->Status != 2) return false;
  return !empty(MusicboxActivatableCards($player));
};
$activateAbilityAbilities["q2svdv3zb9:0"] = function($player) { //Activate
  $musicboxCards = MusicboxActivatableCards($player);
  if(empty($musicboxCards)) return;
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $musicboxCards), 1, "Activate_a_card_banished_by_Clockwork_Musicbox");
  DecisionQueueController::AddDecision($player, "CUSTOM", "q2svdv3zb9:0:ActivateAbility-1", 1);
};


// Coronal of Rejuvenation (uvgflagxbb): "[REST]: You may play a card banished by CARDNAME. Activate this ability only at slow speed." The generated body offered every card it had banished (a mandatory choice) and the chosen
// card was moved to the hand before anything checked it could be activated. The ability now needs a banished card that can actually begin activating, offers only those, and activates through ActivateBanishedCard().
$activateAbilityPrereqs["uvgflagxbb:0"] = function($player, $mzID, $abilityIndex) { //Play prereq
  $sourceObject = GetZoneObject($mzID);
  if($sourceObject === null || $sourceObject->Status != 2) return false;
  return !empty(CoronalActivatableCards($player));
};
$activateAbilityAbilities["uvgflagxbb:0"] = function($player) { //Play
  $choices = CoronalActivatableCards($player);
  if(empty($choices)) return;
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $choices), 1, tooltip:"Choose_card_banished_by_Coronal");
  DecisionQueueController::AddDecision($player, "CUSTOM", "CoronalOfRejuvenationActivate", 1);
};


// ----------------------------------------------------------------------------------------------------------------------------------------------------------------
// Advantageous Perch (oLzsAj9mKl): "Your champion becomes distant. Then return up to one target Ranger action card from your graveyard to your hand. Banish CARDNAME." The generated body banished itself with
// MZMove($player, $mzID, ...), but a spell has already left the effect stack for the graveyard when its effect runs, so "mzID" points at nothing: Advantageous Perch stayed in the graveyard (and was itself
// offered as the Ranger action to return). It is looked up in the graveyard instead.
$cardActivatedAbilities["oLzsAj9mKl:0"] = function($player) { //oLzsAj9mKl
  $champMZ = FindChampionMZ($player);
  if($champMZ !== null) BecomeDistant($player, $champMZ);
  BanishResolvedSpellFromGraveyard($player, "oLzsAj9mKl");
  $rangerActions = [];
  $gy = GetZone("myGraveyard");
  for($gi = 0; $gi < count($gy); ++$gi) {
      if($gy[$gi]->removed) continue;
      if(PropertyContains(CardType($gy[$gi]->CardID), "ACTION") && PropertyContains(CardClasses($gy[$gi]->CardID), "RANGER")) {
          $rangerActions[] = "myGraveyard-" . $gi;
      }
  }
  if(empty($rangerActions)) return;
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $rangerActions), 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "oLzsAj9mKl:0:CardActivated-1", 1);
};

// Ranger Boots (fbs9qzo3f6): the generated body for "[Class Bonus] [REST], Banish Ranger Boots: Your champion becomes distant" is filed in the play-time table and called BecomeDistant($player) without the
// champion's mzID (a fatal error). Neutralized; the real field ability is registered in Custom/GameLogic.php ($activateAbilityAbilities["fbs9qzo3f6:0"]).
$cardActivatedAbilities["fbs9qzo3f6:0"] = function($player) { //Ranger Boots: no on-play activated effect
};

// Scale of Souls (0z2snsdwmx) and Refluxal Ribbon (vm4xg2hedp): both printed abilities are field activations. The generator filed them in the play-time
// $cardActivatedAbilities table (misrouted through ActivateCard, which treats the field item as a card being played); the real entries are
// $activateAbilityAbilities["0z2snsdwmx:0"] / ["vm4xg2hedp:0"] in Custom/GameLogic.php.
$cardActivatedAbilities["0z2snsdwmx:0"] = function($player) { //Scale of Souls: no on-play activated effect
};
$cardActivatedAbilities["vm4xg2hedp:0"] = function($player) { //Refluxal Ribbon: no on-play activated effect
};

// ---------------------------------------------------------------------------------------------
// Bare "[Class Bonus]" gates. The generator emitted IsClassBonusActive($player) with NO class list for these cards' abilities, which only means "controls any champion" and is therefore always true:
// every champion got these cards' Class Bonus effects. IsClassBonusActive() now resolves the bare form against $GLOBALS['gaClassBonusSourceClasses'], which the wrappers installed here set to the
// printed class(es) of the card for the duration of each of its ability closures (any table, including the follow-up customDQHandlers whose key starts with "<cardID>:"). A champion matching any
// of the card's classes satisfies the bonus. Installed last so it wraps the final closure of every key (generated or overridden above).
// ---------------------------------------------------------------------------------------------
function GAInstallClassBonusSourceWrappers(array &$tables) {
    $cardIDs = ["2Ch1Gp3jEL", "5qWWpkgQLl", "6e7lRnczfL", "776yt8UxhU", "8lrj52215u", "8yzADlgx4R", "GRkBQ1Uvir", "NwswAHojeq", "SPESFtKHLw", "Tx6iJQNSA6", "W1vZwOXfG3", "WAFNy2lY5t", "WShYN9M3lU",
        "XLbCBxla8K", "XMb6pSHFJg", "XQKyUqsMUg", "XZFXOE9sEV", "YqQsXwEvv5", "dZ960Hnkzv", "dmbBXRTVIk", "ify06tSEVC", "m3n9yvn1uo", "mj3WSrghUH", "mloejozihs", "optpu3fubb", "pnDhApDNvR",
        "qYH9PJP7uM", "qyQLlDYBlr", "sHzSmygjWY", "uTBsOYf15p", "urfp66pv4n", "zcVjsVRBV8"];
    $cardSet = array_flip($cardIDs);
    foreach($tables as &$table) {
        if(!is_array($table)) continue;
        foreach(array_keys($table) as $key) {
            $cardID = strtok(strval($key), ":");
            if(!isset($cardSet[$cardID]) || !is_callable($table[$key])) continue;
            $original = $table[$key];
            // The card dictionary is not loaded yet while this file runs, so the printed classes are resolved when the closure is called.
            $table[$key] = function(...$args) use ($original, $cardID) {
                $previous = $GLOBALS['gaClassBonusSourceClasses'] ?? null;
                $GLOBALS['gaClassBonusSourceClasses'] = array_values(array_filter(array_map('trim', explode(",", strval(CardClasses($cardID))))));
                try {
                    return $original(...$args);
                } finally {
                    $GLOBALS['gaClassBonusSourceClasses'] = $previous;
                }
            };
        }
    }
    unset($table);
}
// (GAInstallClassBonusSourceWrappers() and GAInstallFieldAbilityCostPrereqs() are called at the very END of this file, after every override: they wrap the final closures.)

// Field-item activations misfiled in the play-time table (see the "Field items whose printed activated ability was filed ..." block in Custom/GameLogic.php): neutralize the bodies so they can no longer run
// (free, repeatable) through ActivateCard. Their real entries are $activateAbilityAbilities in GameLogic.php; the generated follow-up handlers ("<id>:0:CardActivated-1") are still used.
foreach(["997vxajn2q", "drnxdiltx3", "K15jWbHAMY", "clgolelsra", "21oy1nd4nw", "pol1nz0j1n", "2ha4dk88zq", "52u81v4c0z", "97n2jnltv5", "si9ux3ak6o", "iqcknwa2vl", "5LoOprBJay", "FQigf17dCr"] as $gaMisfiledItemID) {
    $cardActivatedAbilities[$gaMisfiledItemID . ":0"] = function($player) { // no on-play activated effect: the item's printed ability is a field activation
    };
}


// Seed of Empowerment (XbYtI0XtVH): "Banish CARDNAME: Empower X, where X is the amount of refinement counters that was on CARDNAME." and Extinguishing Synchron (Tx8noEw78s): "Sacrifice CARDNAME: Recover 2+X, where X is the
// amount of refinement counters that was on CARDNAME." The card leaves as the cost now, so the generated bodies (which read the counters off the field object) see nothing: they read the last-known count that
// GAPayFieldAbilityCost() recorded before the card left.
$activateAbilityAbilities["XbYtI0XtVH:0"] = function($player) { //Banish to empower for refinement
    Empower($player, intval(DecisionQueueController::GetVariable("gaLKI_refinement")), "XbYtI0XtVH");
};
$activateAbilityAbilities["Tx8noEw78s:0"] = function($player) { //Sacrifice to recover
    RecoverChampion($player, 2 + intval(DecisionQueueController::GetVariable("gaLKI_refinement")));
};

// $cardActivatedPrereqs timing gates written against phase codes the engine never reports: the opponent's end-phase opportunity window runs with the phase code "BEOP" (BeforeEndOpportunityPhase), and there is no
// "COMBAT" phase code (combat happens during MAIN with an attack in progress). The generated gates ("END" / "COMBAT") would never be true now that these prereqs are enforced.
$cardActivatedPrereqs["dc8P58gmjR:0"] = function($player, $mzID) { // Slime Calling: only during an opponent's end phase
    return in_array(GetCurrentPhase(), ["BEOP", "END"], true) && GetTurnPlayer() != $player;
};
$cardActivatedPrereqs["VXHLfbZ6AB:0"] = function($player, $mzID) { // Break the Line: only during a combat phase
    return IsCombatActive();
};

// Slime Calling (dc8P58gmjR): "[Class Bonus] Look at the top 3+LV cards of your deck." The generated loop moved deck indexes N..1 into the temp zone: the TOP card (index 0) was never looked at and the
// card at index N (one below the printed range) was. Fixed to take indexes N-1..0. (Remove() only flags a slot, so descending order keeps every index valid.)
$cardActivatedAbilities["dc8P58gmjR:0"] = function($player) { //Slime Calling -- Class Bonus deck activation
  if(!IsClassBonusActive($player, CardClasses("dc8P58gmjR"))) return;
  $champLevel = 1;
  $champMZ = FindChampionMZ($player);
  if($champMZ !== null) {
      $champObj = GetZoneObject($champMZ);
      if($champObj !== null) $champLevel = ObjectCurrentLevel($champObj);
  }
  $n = min(3 + $champLevel, count(GetDeck($player)));
  if($n === 0) return;
  for($i = $n - 1; $i >= 0; --$i) {
      MZMove($player, "myDeck-" . $i, "myTempZone");
  }
  SlimeCallingChooseSlime($player, 1);
};

// Activation restrictions inspect the combat participant in the current viewer's frame.
// Use the recorded UID when available; older saved combats retain coordinate support.
function GAControlledCombatAttacker($player) {
    $attackerPlayer = intval(DecisionQueueController::GetVariable("CombatAttackerPlayer") ?? 0);
    if(intval(DecisionQueueController::GetVariable("CombatAttackerUniqueID") ?? 0) > 0
        && in_array($attackerPlayer, [1, 2], true)) {
        if(ResolveCombatAttackerByUniqueID($attackerPlayer) === null) return null;
    }
    $attackerMZ = GetCombatAttackerMZ();
    if($attackerMZ === null) return null;
    $obj = GetZoneObject($attackerMZ);
    if($obj === null || $obj->removed || intval($obj->Controller) !== intval($player)) return null;
    if(!PropertyContains(EffectiveCardType($obj), "ALLY") && !PropertyContains(EffectiveCardType($obj), "CHAMPION")) return null;
    return $obj;
}

// Overpowering Defense: a Guardian unit you control must be attacking.
$cardActivatedPrereqs["14hr8i5oix:0"] = function($player, $mzID) {
    $obj = GAControlledCombatAttacker($player);
    return $obj !== null && PropertyContains(EffectiveCardClasses($obj), "GUARDIAN");
};

// Meteoric Volley (10u5ldz371): "Activate this card only if your champion is attacking." The generator emitted no prereq for it at all, so it could be activated with no attack in progress.
$cardActivatedPrereqs["10u5ldz371:0"] = function($player, $mzID) {
    $attackerObj = GAControlledCombatAttacker($player);
    return $attackerObj !== null && PropertyContains(EffectiveCardType($attackerObj), "CHAMPION");
};

// "Activate this card only during ...": timing restrictions that were missing or written against phase codes the engine never reports. The opponent's / your end phase opportunity window runs as "BEOP"
// (BeforeEndOpportunityPhase), the recollection window as "BREC".
function GAIsEndPhaseWindow() { return in_array(GetCurrentPhase(), ["BEOP", "END"], true); }
$activateCardPrereqs["ps8unuy20m:0"] = function($player, $mzID, $ignoreCost) { // Lurid Dreaming: only during an opponent's end phase
    if(!GAIsEndPhaseWindow() || GetTurnPlayer() == $player) { SetFlashMessage("Lurid Dreaming can only be activated during an opponent's end phase."); return false; }
    return true;
};
$activateCardPrereqs["pwscn0esog:0"] = function($player, $mzID, $ignoreCost) { // Orchestrated Seizure: only during an opponent's end phase
    return GAIsEndPhaseWindow() && GetTurnPlayer() != $player;
};
$cardActivatedPrereqs["dfchplzf6m:0"] = function($player, $mzID) { return GAIsEndPhaseWindow() && GetTurnPlayer() != $player; }; // Ingress of Sanguine Ire: only during an opponent's end phase
$cardActivatedPrereqs["ao1cfkhbp6:0"] = function($player, $mzID) { return GAIsEndPhaseWindow() && GetTurnPlayer() == $player; };  // Stand Fast: only during your end phase
foreach(["vm5kt3q2sv", "nvx7mnu1xh", "PR4OkzJBVr", "j9hjjvkyyr"] as $gaRecollectionOnlyID) { // Vanish from Sight, Attune with Flames, Hemorrhaged Intimidation, Oppressive Presence: only during an opponent's recollection phase
    $cardActivatedPrereqs[$gaRecollectionOnlyID . ":0"] = function($player, $mzID) { return GetCurrentPhase() === "BREC" && GetTurnPlayer() != $player; };
}
// Innervate Fury (wpbhigka5a): "As an additional cost to activate this card, delevel your champion and recover 5." Innervate Agility had the cost wired in (GameLogic.php, InnervateAgilityActivationCost) and a prereq; its sibling had neither, so
// Fury cast for its reserve cost alone, with no delevel and no recover, from a level 1 champion. The cost block now covers both cards; this is Agility's prereq: a champion with no lineage cannot pay it.
// Innervate Knowledge (pcescfpwak) has the identical additional cost and was missing the same wiring.
foreach(["wpbhigka5a:0", "pcescfpwak:0"] as $gaInnervateKey) {
    $cardActivatedPrereqs[$gaInnervateKey] = function($player, $mzID) {
        foreach(GetField($player) as $fieldObj) {
            if($fieldObj->removed || !PropertyContains(EffectiveCardType($fieldObj), "CHAMPION") || ($fieldObj->Controller ?? 0) != $player) continue;
            return !empty(is_array($fieldObj->Subcards) ? $fieldObj->Subcards : []);
        }
        return false;
    };
}
// Duchess, Six of Hearts (qzv380ujf5) Cardistry: "Banish a fire element action card with reserve cost 2 or less from your graveyard. If you do, copy it and you may activate that copy without paying its reserve cost."
// The generated handler pushed a bare stdClass onto the hand and activated it, which fatals in MZMove ("Call to undefined method stdClass::Remove()") -- using the ability crashed the engine. It also compared the
// element to exactly "FIRE", so Exalted fire actions ("EXALTED,FIRE": Blaze Alight, Lavastorm, ...) were never offered. The copy is now a proper temporary card activated with its cost ignored.
// "Activate this ability only once": the generated body never recorded the use (every other Cardistry card sets cardistry_used and has the matching prereq), so it could be activated repeatedly.
$activateAbilityPrereqs["qzv380ujf5:0"] = function($player, $mzID, $abilityIndex) { //Cardistry prereq
  $sourceObject = GetZoneObject($mzID);
  return $sourceObject !== null && !isset($sourceObject->Counters['cardistry_used']);
};
$activateAbilityAbilities["qzv380ujf5:0"] = function($player) { //Cardistry
  $usedObj = &GetZoneObject(DecisionQueueController::GetVariable("mzID"));
  if($usedObj !== null) $usedObj->Counters['cardistry_used'] = 1;
  $validGY = [];
  foreach(GetZone("myGraveyard") as $gi => $gyObj) {
      if($gyObj->removed) continue;
      $cID = $gyObj->CardID;
      if(!PropertyContains(CardElement($cID), "FIRE") || !PropertyContains(CardType($cID), "ACTION") || intval(CardCost_reserve($cID)) > 2) continue;
      $validGY[] = "myGraveyard-" . $gi;
  }
  if(empty($validGY)) return;
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $validGY), 1, "Banish_a_fire_action_to_copy");
  DecisionQueueController::AddDecision($player, "CUSTOM", "qzv380ujf5:0:ActivateAbility-1", 1);
};
$customDQHandlers["qzv380ujf5:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) { //Cardistry
  if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
  $chosenObj = GetZoneObject($lastDecision);
  if($chosenObj === null || $chosenObj->removed) return;
  $chosenCardID = $chosenObj->CardID;
  MZMove($player, $lastDecision, "myBanish");
  DecisionQueueController::CleanupRemovedCards();
  ActivateTemporaryCardCopy($player, $chosenCardID);
};
// Gloamspire, Black Market (waf8urrqtj): "Each player with influence less than seven draws a card into their memory." Influence is the total of cards in hand AND memory. The generated body counted only the memory zone
// (and read "theirMemory" through the activating player's perspective), so every player with fewer than seven cards in memory -- always -- drew.
$activateAbilityAbilities["waf8urrqtj:0"] = function($player) { //Draw
  $opponent = $player == 1 ? 2 : 1;
  foreach([$player, $opponent] as $drawingPlayer) {
      if(GetInfluence($drawingPlayer) < 7) DrawIntoMemory($drawingPlayer, 1);
  }
};
// Element words on printed text count Exalted cards too: an Exalted Fire card IS a fire element card (CardElement() reads "EXALTED,FIRE"), so the
// generated `=== "FIRE"` / `!== "NORM"` comparisons wrongly rejected them. These keep the generated bodies and test element membership instead.
// (Chamber of Reflections' `non-advanced element` check was already right -- EXALTED is an advanced element -- and no Exalted Suited ally or omen exists, so Four of Hearts and Awaken Ombre cannot misfire.)
// Forgelight Shieldmaiden (kuz07nk45s): "Class Bonus: If a fire element card was discarded, put a buff counter on CARDNAME."
if(isset($customDQHandlers["kuz07nk45s:0:OnFoster-1"])) {
  $customDQHandlers["kuz07nk45s:0:OnFoster-1"] = function($player, $parts, $lastDecision) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    DecisionQueueController::StoreVariable("chosen", $lastDecision);
    if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
    if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "kuz07nk45s:0:OnFoster-1")) return;
    $chosenObj = GetZoneObject($lastDecision);
    $wasFire = $chosenObj !== null && GAIsElement($chosenObj->CardID, "FIRE");
    DiscardCard($player, $lastDecision);
    if(IsClassBonusActive($player, ["GUARDIAN"]) && $wasFire) AddCounters($player, $mzID, "buff", 1);
  };
}
// "Activate this ability only once" (Cardistry): the generated bodies of Two of Spades, Three of Spades, Two of Diamonds, Four of Hearts, Five of Spades, Five of Hearts and Five of Diamonds never recorded the use (every other
// Cardistry card sets cardistry_used and has the matching prereq), so those abilities could be activated again and again. The original bodies are wrapped to record the use and a prereq refuses a second activation.
foreach(["e8ygl32jef", "o09csnorqv", "mzwTQzuXZa", "xgax8bbjqj", "i9hf5lhl5f", "idq4ih00rq", "Zq4iWqdjGp"] as $gaCardistryOnceID) {
    $gaCardistryOnceKey = $gaCardistryOnceID . ":0";
    if(!isset($activateAbilityAbilities[$gaCardistryOnceKey])) continue;
    $gaCardistryOnceBody = $activateAbilityAbilities[$gaCardistryOnceKey];
    $activateAbilityAbilities[$gaCardistryOnceKey] = function($player) use ($gaCardistryOnceBody) {
        $usedObj = &GetZoneObject(DecisionQueueController::GetVariable("mzID"));
        if($usedObj !== null) $usedObj->Counters['cardistry_used'] = 1;
        return $gaCardistryOnceBody($player);
    };
    $gaCardistryOncePrereq = $activateAbilityPrereqs[$gaCardistryOnceKey] ?? null;
    $activateAbilityPrereqs[$gaCardistryOnceKey] = function($player, $mzID, $abilityIndex) use ($gaCardistryOncePrereq) {
        $sourceObject = GetZoneObject($mzID);
        if($sourceObject === null || isset($sourceObject->Counters['cardistry_used'])) return false;
        return $gaCardistryOncePrereq === null ? true : $gaCardistryOncePrereq($player, $mzID, $abilityIndex);
    };
}
// "Activate this ability only at slow speed": slow speed is your own main phase with nothing on the stack and no combat. CanActivateAbility() is the single gate for the window listing (OpportunityLogic.php sets
// GAFastWindowAbilityContext while it builds the list and while a listed choice is activated) and for execution, but only 14 cards print the restriction and the generated prereqs either lack it or test HasOpportunity()
// (which is false for the player building the window), so Gloamspire's draw resolved from the end-of-turn window. The ability index is the one the card's text belongs to.
function GAIsSlowSpeed($player) {
    if(!empty($GLOBALS['GAFastWindowAbilityContext'])) return false;
    if(GetTurnPlayer() != $player || GetCurrentPhase() !== "MAIN") return false;
    return !HasOpportunity($player);
}
foreach(["imcmo3l3th:0", "40lgjj1yS3:0", "SGsDKB9CN5:0", "cc0jmpmman:0", "uvgflagxbb:0", "s7pmqsl3jw:1", "al6pqkmgmz:0", "9xycwz9gv4:0", "ts9VdtFtk1:0", "7gz0j8p4sx:0", "s23UHXgcZq:0", "waf8urrqtj:0"] as $gaSlowOnlyKey) {
    $gaSlowOnlyPrereq = $activateAbilityPrereqs[$gaSlowOnlyKey] ?? null;
    $activateAbilityPrereqs[$gaSlowOnlyKey] = function($player, $mzID, $abilityIndex) use ($gaSlowOnlyPrereq) {
        if(!GAIsSlowSpeed($player)) return false;
        return $gaSlowOnlyPrereq === null ? true : $gaSlowOnlyPrereq($player, $mzID, $abilityIndex);
    };
}
// Frigid Bash (k2c7wklzjm): "[Class Bonus] On Hit: The hit object doesn't wake up during its controller's next wake up phase unless that player pays (2). (The optional cost is paid upon the resolution of this trigger.)"
// Only the cost reduction existed; the On Hit never did anything. The hit object's controller decides on resolution: pay (2) and it wakes up normally, otherwise it gets the one-time SKIP_WAKEUP.
$onHitAbilities["k2c7wklzjm:0"] = function($player) {
  if(!IsClassBonusActive($player, ["GUARDIAN"])) return;
  $target = DecisionQueueController::GetVariable("CombatTarget");
  if($target === null || $target === "" || $target === "-") return;
  $targetObj = GetZoneObject($target);
  if($targetObj === null) return;
  $uniqueID = intval($targetObj->UniqueID ?? 0);
  $controller = intval($targetObj->Controller);
  if(CountAvailableReservePayments($controller) >= 2) {
      DecisionQueueController::AddDecision($controller, "YESNO", "-", 1, "Pay_(2)_so_that_it_wakes_up_normally?_(Frigid_Bash)");
      DecisionQueueController::AddDecision($controller, "CUSTOM", "FrigidBashPayChoice|" . $uniqueID, 1);
  } else {
      AddTurnEffect($target, "SKIP_WAKEUP");
  }
};
$customDQHandlers["FrigidBashPayChoice"] = function($payer, $parts, $lastDecision) {
  $uniqueID = intval($parts[0] ?? 0);
  if($lastDecision === "YES" && QueuePreventNegateReserveSelection($payer, 2, "FrigidBashPaid|" . $uniqueID, 1)) return;
  $mz = FindFieldMzByUniqueID($uniqueID);
  if($mz !== "") AddTurnEffect($mz, "SKIP_WAKEUP");
};
$customDQHandlers["FrigidBashPaid"] = function($payer, $parts, $lastDecision) {
  if(ProcessReservePaymentSelections($payer, $lastDecision, 2)) return;
  $mz = FindFieldMzByUniqueID(intval($parts[0] ?? 0));
  if($mz !== "") AddTurnEffect($mz, "SKIP_WAKEUP");
};
// Hailfinch (3XV4QlQXfy): "[Class Bonus] On Hit: You may remove a buff counter from CARDNAME. If you do, rest the hit object and it doesn't wake up during its controller's next wake up phase."
// The generated body asked YESNO and read CombatTarget in the answer handler, but the combat variables are only restored while the trigger itself resolves, so the answer found no target: the buff counter
// was removed and the hit object was never rested. The hit object and Hailfinch are frozen by unique id when the trigger resolves.
$onHitAbilities["3XV4QlQXfy:0"] = function($player) {
  if(!IsClassBonusActive($player, ["TAMER"])) return;
  $mzID = DecisionQueueController::GetVariable("mzID");
  $hailObj = GetZoneObject($mzID);
  if($hailObj === null || GetCounterCount($hailObj, "buff") <= 0) return;
  $target = DecisionQueueController::GetVariable("CombatTarget");
  if($target === null || $target === "-" || $target === "") return;
  $targetObj = GetZoneObject($target);
  if($targetObj === null) return;
  DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Remove_buff_counter_to_rest_hit_object?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "HailfinchHitChoice|" . intval($hailObj->UniqueID ?? 0) . "|" . intval($targetObj->UniqueID ?? 0), 1);
};
$customDQHandlers["HailfinchHitChoice"] = function($player, $parts, $lastDecision) {
  if($lastDecision !== "YES") return;
  $hailMZ = FindFieldMzByUniqueID(intval($parts[0] ?? 0));
  if($hailMZ === "") return;
  $hailObj = GetZoneObject($hailMZ);
  if($hailObj === null || GetCounterCount($hailObj, "buff") <= 0) return;
  RemoveCounters($player, $hailMZ, "buff", 1);
  $targetMZ = FindFieldMzByUniqueID(intval($parts[1] ?? 0));
  if($targetMZ === "") return;
  RestCard($player, $targetMZ);
  AddTurnEffect($targetMZ, "SKIP_WAKEUP");
};
// Surging Obstruction (VpM6CAp4ts): "As long as you control a Chessman Bishop ally, this card costs 1 less to activate. Negate target card activation unless its controller pays (1). If that card has an odd reserve cost,
// negate its activation unless its controller pays (3) instead. (Zero is considered even.)" The card had no implementation at all: it resolved with no effect. The pay amount depends on the chosen target, so the
// target handler works it out and hands over to the shared negate-unless-pays flow (the same one Chained Charge and Frozen Dismissal use). The Chessman Bishop discount is GASurgingObstructionDiscount()
// (GameLogic.php): the card has no row in the generated cost-modifier tables, so a generated-style modifier here would never be consulted.
$cardActivatedAbilities["VpM6CAp4ts:0"] = function($player) {
  QueueNegateActivation($player, [], "default", -1, "SurgingObstructionNegate");
};
$customDQHandlers["SurgingObstructionNegate"] = function($player, $parts, $lastDecision) {
  global $customDQHandlers;
  if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
  $target = GetZoneObject($lastDecision);
  if($target === null || $target->removed) return;
  $reserveCost = intval(CardCost_reserve($target->CardID));
  $payAmount = ($reserveCost > 0 && $reserveCost % 2 === 1) ? 3 : 1;
  $customDQHandlers["NegateActivationResolve"]($player, [$parts[0] ?? "default", $payAmount], $lastDecision);
};
// Oppressive Presence's generated prereq had the test inverted (HasOpportunity() || your own turn -> refuse), so the card was refused in the one window it is meant for -- the opponent's recollection opportunity -- and nothing else offers it.
$activateCardPrereqs["j9hjjvkyyr:0"] = function($player, $mzID, $ignoreCost) {
    if(GetCurrentPhase() !== "BREC" || GetTurnPlayer() == $player) { SetFlashMessage("Oppressive Presence can only be activated during an opponent's recollection phase."); return false; }
    return true;
};

// Sidereal Spellshot (xwwkxq0vp3): "[Class Bonus] Glimpse X. Then you may load Sidereal Spellshot into an Aetherwing weapon you control." The Class Bonus covers the Glimpse sentence only; the generated body returned
// before the optional load for a non-Ranger champion (unlike every other Aethercharge card, e.g. Constellation's Blessing).
$cardActivatedAbilities["xwwkxq0vp3:0"] = function($player) { //xwwkxq0vp3
  if(IsClassBonusActive($player, ["RANGER"])) Glimpse($player, CardPower("xwwkxq0vp3"));
  MayLoadIntoAetherwing($player, "xwwkxq0vp3");
};

// ---------------------------------------------------------------------------------------------
// "Destroy target item or weapon with memory cost 0 or reserve cost 3 or less" (Unbroken Droid POD9hwHYy0 On Enter, Thermal Break QJHpGP4kpe body + prereq). The generated target filters called
// CardMemoryCost($cardID) (it takes an object) and CardReserveCost() (does not exist): casting Thermal Break or entering Unbroken Droid with an item or weapon on the field was a fatal.
// Verbatim bodies with ItemLowCostMatches().
// ---------------------------------------------------------------------------------------------
$enterAbilities["POD9hwHYy0:0"] = function($player) { //Destroy up to one cheap item or weapon
  $mzID = DecisionQueueController::GetVariable("mzID");
  $candidates = array_merge(
      ZoneSearch("myField", ["ITEM", "WEAPON"]),
      ZoneSearch("theirField", ["ITEM", "WEAPON"])
  );
  $targets = [];
  foreach($candidates as $candidateMZ) {
      $candidateObj = GetZoneObject($candidateMZ);
      if($candidateObj === null || $candidateObj->CardID === "POD9hwHYy0") continue;
      if(ItemLowCostMatches($candidateObj->CardID, 3)) $targets[] = $candidateMZ;
  }
  if(empty($targets)) return;
  $targetStr = implode("&", $targets);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $targetStr, 1, "Destroy_up_to_one_item_or_weapon");
  DecisionQueueController::AddDecision($player, "CUSTOM", "POD9hwHYy0:0:Enter-1", 1);
};
$cardActivatedAbilities["QJHpGP4kpe:0"] = function($player) { //Destroy cheap item or weapon
  $mzID = DecisionQueueController::GetVariable("mzID");
  $candidates = array_merge(ZoneSearch("myField", ["ITEM", "WEAPON"]), ZoneSearch("theirField", ["ITEM", "WEAPON"]));
  $targets = [];
  foreach($candidates as $candidateMZ) {
      $candidateObj = GetZoneObject($candidateMZ);
      if($candidateObj === null) continue;
      if(ItemLowCostMatches($candidateObj->CardID, 3)) $targets[] = $candidateMZ;
  }
  $targetStr = implode("&", $targets);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $targetStr, 1, "Destroy_target_item_or_weapon");
  DecisionQueueController::AddDecision($player, "CUSTOM", "QJHpGP4kpe:0:CardActivated-1", 1);
};
$cardActivatedPrereqs["QJHpGP4kpe:0"] = function($player, $mzID) { //Destroy cheap item or weapon prereq
  $candidates = array_merge(ZoneSearch("myField", ["ITEM", "WEAPON"]), ZoneSearch("theirField", ["ITEM", "WEAPON"]));
  foreach($candidates as $candidateMZ) {
      $candidateObj = GetZoneObject($candidateMZ);
      if($candidateObj === null) continue;
      if(ItemLowCostMatches($candidateObj->CardID, 3)) return true;
  }
  return false;
};

// ---------------------------------------------------------------------------------------------
// Generated DQ handlers that read variables only defined in the ability body that queued them (PHP closures do not share scope): each logged "Undefined variable" and then ran with null.
//   Inquisitive Magician cbxpjya4u1 Enter-1: $mageCards undefined -> array_filter(null) fatal as soon as the player picked the first Mage card to reveal.
//   Convergent Beam hPwHRfUpN2: $controlsFractal undefined -> always 1 damage (the Fractal upgrade to 3 never applied).
//   Windrider Invoker lx6xwr42i6 Enter-1: $champMZ undefined -> the two enlighten counters were never removed (but the card was drawn and +3 power granted for free).
//   Blinding Orb qYH9PJP7uM: $opponent undefined -> MZMove(null, ...) so the opponent's two cards never reached memory; the opponent is the decision's own $player.
// ---------------------------------------------------------------------------------------------
$customDQHandlers["cbxpjya4u1:0:Enter-1"] = function($player, $parts, $lastDecision) { //Inquisitive Magician: reveal the first Mage card, then choose the second
  $first = $lastDecision;
  if($first === "-" || $first === "PASS" || $first === "" || $first === null) return;
  DecisionQueueController::StoreVariable("first", $first);
  $remaining = [];
  foreach(["Hand", "Memory"] as $zoneName) {
      foreach(ZoneSearch("my" . $zoneName, forPlayer: $player) as $mz) {
          if($mz === $first) continue;
          $zObj = GetZoneObject($mz);
          if($zObj !== null && PropertyContains(CardClasses($zObj->CardID), "MAGE")) $remaining[] = $mz;
      }
  }
  if(empty($remaining)) return;
  DoRevealCard($player, $first);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $remaining), 1, "Reveal_a_second_Mage_card");
  DecisionQueueController::AddDecision($player, "CUSTOM", "cbxpjya4u1:0:Enter-2", 1);
};
$customDQHandlers["hPwHRfUpN2:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Convergent Beam
  $mzID = DecisionQueueController::GetVariable("mzID");
  $chosen = $lastDecision;
  if($chosen === "-" || $chosen === "" || $chosen === null) return;
  $controlsFractal = false;
  foreach(GetField($player) as $fieldObj) {
      if($fieldObj !== null && !$fieldObj->removed && PropertyContains(EffectiveCardSubtypes($fieldObj), "FRACTAL")) { $controlsFractal = true; break; }
  }
  DealDamage($player, $mzID, $chosen, $controlsFractal ? 3 : 1);
};
$customDQHandlers["lx6xwr42i6:0:Enter-1"] = function($player, $parts, $lastDecision) { //Windrider Invoker
  $mzID = DecisionQueueController::GetVariable("mzID");
  if($lastDecision !== "YES") return;
  $champMZ = null;
  foreach(GetField($player) as $fi => $fObj) {
      if(!$fObj->removed && PropertyContains(CardType($fObj->CardID), "CHAMPION")) { $champMZ = "myField-" . $fi; break; }
  }
  if($champMZ === null) return;
  RemoveCounters($player, $champMZ, "enlighten", 2);
  Draw($player, 1);
  AddTurnEffect($mzID, "lx6xwr42i6");
};
$customDQHandlers["qYH9PJP7uM:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) { //Blinding Orb: the opponent's first card
  if($lastDecision === "-" || $lastDecision === "" || $lastDecision === null) return;
  MZMove($player, $lastDecision, "myMemory");
  DecisionQueueController::CleanupRemovedCards(); // close the hand up so the second list holds real indices
  $handCards = ZoneSearch("myHand", forPlayer: $player);
  if(empty($handCards)) return;
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $handCards), 1, "Put_a_second_card_from_your_hand_into_memory");
  DecisionQueueController::AddDecision($player, "CUSTOM", "qYH9PJP7uM:0:ActivateAbility-2", 1);
};
$customDQHandlers["qYH9PJP7uM:0:ActivateAbility-2"] = function($player, $parts, $lastDecision) { //Blinding Orb: the opponent's second card
  if($lastDecision === "-" || $lastDecision === "" || $lastDecision === null) return;
  MZMove($player, $lastDecision, "myMemory");
};

// ---------------------------------------------------------------------------------------------
// More generated handlers that read variables only defined in the body that queued them (closures do not share scope), found by a static scan for variables read before definition.
// Each recomputes the value at resolution:
//   Corhazi Arsonist 0ejcyuvuxn On Hit ($target undefined: the hit unit was never marked, so "banish instead of die" never applied)
//   Lesser Boon of Revelry AOFRjoIHVe (3) ability ($myAllies/$theirAllies)       Carpsong Coda 3omh6h3a4y ($highestLife: 0 damage)
//   Discordia, Harp of Malice 5LoOprBJay ($musicCount: DISCORDIA_MINUS_/PLUS_ with no X)     Juggle Knives 7VxRE6HgZC ($obj: no damage source)
//   Grim Pastiche akmv2ssjhu ($copyCardID: the copy was never activated)   Rhesus Eradication KgjL9uCqm6 ($damageAmount: 0 damage)
//   Aenean Swelling Gusts nbznVwdylT ($windCount: 0 damage)   Guarded Dissipation r5zs29xxoo ($maxPower: PREVENT_ALL_ with no amount)
//   Ignite the Soul rXHo9fLU32 ($obj: no damage source; and the target list was the opponent's WHOLE field instead of units)
//   Discover the Divine YJacXvwTiX ($champMZ: the four enlighten counters were never removed but the champion levelled up)
//   Incinerator Felindroid Vl03t5rMSA ($fireCards: the two fire cards could never be chosen)
//   Decaying Reproach qXIKFip2t4 ($removed: always 3 damage instead of 3 + 2X)
// ---------------------------------------------------------------------------------------------
$onHitAbilities["0ejcyuvuxn:0"] = function($player) { //Corhazi Arsonist: mark the hit unit so it is banished instead of dying
  $mzID = DecisionQueueController::GetVariable("mzID");
  $obj = GetZoneObject($mzID);
  if($obj === null || $obj->CardID !== "0ejcyuvuxn") return;
  $target = DecisionQueueController::GetVariable("CombatTarget");
  if($target === null || $target === "" || $target === "-") return;
  $targetObj = GetZoneObject($target);
  if($targetObj === null) return;
  AddTurnEffect($target, "HIT_BY_0ejcyuvuxn");
};
$customDQHandlers["AOFRjoIHVe:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) { //Lesser Boon of Revelry
  $zone = $lastDecision === "YES" ? "myField" : "theirField";
  $allies = ZoneSearch($zone, ["ALLY"], forPlayer: $player);
  if(count($allies) !== 1) return;
  AddCounters($player, $allies[0], "buff", 1);
};
$customDQHandlers["3omh6h3a4y:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Carpsong Coda
  $mzID = DecisionQueueController::GetVariable("mzID");
  $highestLife = 0;
  foreach(ZoneSearch("myGraveyard", ["ALLY"], cardElements: ["WATER"], cardSubtypes: ["ANIMAL", "BEAST"]) as $gyMZ) {
      $gyObj = GetZoneObject($gyMZ);
      if($gyObj !== null) $highestLife = max($highestLife, intval(CardLife($gyObj->CardID)));
  }
  if($highestLife > 0) DealDamage($player, $mzID, $lastDecision, $highestLife);
};
$customDQHandlers["5LoOprBJay:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Discordia, Harp of Malice
  $mzID = DecisionQueueController::GetVariable("mzID");
  $discObj = GetZoneObject($mzID);
  $musicCount = $discObj === null ? 0 : intval(GetCounterCount($discObj, "music"));
  AddTurnEffect($lastDecision, "DISCORDIA_MINUS_" . $musicCount);
  $myChampMZ = FindChampionMZ($player);
  if($myChampMZ !== null) AddTurnEffect($myChampMZ, "DISCORDIA_PLUS_" . $musicCount);
  AddTurnEffect($mzID, "BANISH_SELF");
};
$customDQHandlers["7VxRE6HgZC:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Juggle Knives
  $mzID = DecisionQueueController::GetVariable("mzID");
  DealDamage($player, $mzID, $lastDecision, 1);
  if(IsClassBonusActive($player, ["ASSASSIN", "RANGER"])) Draw($player, amount: 1);
};
$customDQHandlers["akmv2ssjhu:0:CardActivated-2"] = function($player, $parts, $lastDecision) { //Grim Pastiche
  if($lastDecision !== "YES") return;
  $chosenOmen = DecisionQueueController::GetVariable("chosenOmen");
  $chosenObj = $chosenOmen === null ? null : GetZoneObject($chosenOmen);
  if($chosenObj === null) return;
  ActivateTemporaryCardCopy($player, $chosenObj->CardID);
};
$customDQHandlers["KgjL9uCqm6:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Rhesus Eradication
  $mzID = DecisionQueueController::GetVariable("mzID");
  $elysianCount = 0;
  foreach(GetField($player) as $fieldObj) {
      if($fieldObj !== null && !$fieldObj->removed && PropertyContains(EffectiveCardSubtypes($fieldObj), "ELYSIAN")) $elysianCount++;
  }
  DealDamage($player, $mzID, $lastDecision, PlayerLevel($player) + 2 * $elysianCount);
};
$customDQHandlers["nbznVwdylT:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Aenean Swelling Gusts
  $mzID = DecisionQueueController::GetVariable("mzID");
  $windCount = 0;
  foreach(GetField($player) as $fieldObj) {
      if($fieldObj !== null && !$fieldObj->removed && PropertyContains(EffectiveCardElement($fieldObj), "WIND")) $windCount++;
  }
  DealDamage($player, $mzID, $lastDecision, $windCount);
  if(IsClassBonusActive($player, ["MAGE"]) && PlayerLevel($player) >= 5) DrawIntoMemory($player, 1);
};
$customDQHandlers["r5zs29xxoo:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Guarded Dissipation
  $maxPower = 0;
  foreach(ZoneSearch("myField", ["WEAPON"], cardSubtypes: ["SWORD"]) as $swordMZ) {
      $swordObj = GetZoneObject($swordMZ);
      if($swordObj !== null) $maxPower = max($maxPower, intval(ObjectCurrentPower($swordObj)));
  }
  if($maxPower > 0) AddTurnEffect($lastDecision, "PREVENT_ALL_" . $maxPower);
  if(PlayerLevel($player) >= 1) Draw($player, amount: 1);
};
$cardActivatedAbilities["rXHo9fLU32:0"] = function($player) { //Ignite the Soul: deal 1 damage to target unit
  $mzID = DecisionQueueController::GetVariable("mzID");
  $targets = FilterSpellshroudTargets(array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"])));
  if(empty($targets)) return;
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Deal_1_damage_to_target_unit");
  DecisionQueueController::AddDecision($player, "CUSTOM", "rXHo9fLU32:0:CardActivated-1", 1);
};
$customDQHandlers["rXHo9fLU32:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Ignite the Soul
  $mzID = DecisionQueueController::GetVariable("mzID");
  DealDamage($player, $mzID, $lastDecision, 1);
};
$customDQHandlers["YJacXvwTiX:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Discover the Divine
  if($lastDecision !== "YES") return;
  $champMZ = FindChampionMZ($player);
  if($champMZ === null) return;
  RemoveCounters($player, $champMZ, "enlighten", 4);
  LevelUpChampion($player);
};
$customDQHandlers["Vl03t5rMSA:0:Enter-1"] = function($player, $parts, $lastDecision) { //Incinerator Felindroid: choose the two fire cards
  if($lastDecision !== "A") return;
  $fireCards = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
  if(count($fireCards) < 2) return;
  DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "2|2|" . implode("&", $fireCards), 1, "Choose_two_fire_cards");
  DecisionQueueController::AddDecision($player, "CUSTOM", "Vl03t5rMSA:0:Enter-2", 1);
};
$cardActivatedAbilities["qXIKFip2t4:0"] = function($player) { //Decaying Reproach: remove up to four wither counters from objects you don't control, then deal 3 + 2X damage
  $mzID = DecisionQueueController::GetVariable("mzID");
  $removed = 0;
  foreach(["myField", "theirField"] as $zone) {
      $field = GetZone($zone);
      for($i = 0; $i < count($field) && $removed < 4; ++$i) {
          if($field[$i]->removed || $field[$i]->Controller == $player) continue;
          $count = intval(GetCounterCount($field[$i], "wither"));
          if($count <= 0) continue;
          $toRemove = min(4 - $removed, $count);
          RemoveCounters($player, $zone . "-" . $i, "wither", $toRemove);
          $removed += $toRemove;
      }
  }
  DecisionQueueController::StoreVariable("witherRemoved", $removed);
  $units = FilterSpellshroudTargets(array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"])));
  if(empty($units)) return;
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $units), 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "qXIKFip2t4:0:CardActivated-1", 1);
};
$customDQHandlers["qXIKFip2t4:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Decaying Reproach
  $mzID = DecisionQueueController::GetVariable("mzID");
  $removed = intval(DecisionQueueController::GetVariable("witherRemoved"));
  DealDamage($player, $mzID, $lastDecision, 3 + 2 * $removed);
};

// ---------------------------------------------------------------------------------------------
// Graveyard-activated abilities ("(N), Banish this card from your graveyard: ..."). The engine had no route from a graveyard click to $activateAbilityAbilities (only hand-coded blocks per card), so these
// were unusable: Seaside Ringleader (empty "BLOCKED" body), Stellarion Shift, Proof of Life, Induction Strike (never paid), Voltaic Sphere (not implemented). They are now flagged 'graveyard' in
// GAFieldAbilityCostTable() (the (N) and the banish are paid by the cost spec, and ActionMap() routes the click through ActivateAbility); the bodies below hold only the effects.
// ---------------------------------------------------------------------------------------------
$activateAbilityAbilities["eirpdm44nt:0"] = function($player) { //Seaside Ringleader: draw into memory; Animal and Beast ally cards you activate this turn enter with an additional buff counter
  DrawIntoMemory($player, 1);
  AddGlobalEffects($player, "eirpdm44nt");
};
$activateAbilityAbilities["ms2x2v4qe3:0"] = function($player) { //Stellarion Shift (graveyard): stealth and prevent the next 4 non-combat damage
  $champMZ = FindChampionMZ($player);
  if($champMZ === null) return;
  AddTurnEffect($champMZ, "STEALTH");
  AddTurnEffect($champMZ, "PREVENT_NONCOMBAT_4");
};
$activateAbilityAbilities["mes4idoihs:0"] = function($player) { //Proof of Life (graveyard): wake up your champion
  $champMZ = FindChampionMZ($player);
  if($champMZ !== null) WakeupCard($player, $champMZ);
};
$activateAbilityAbilities["0op3nq0ymv:0"] = function($player) { //Voltaic Sphere (graveyard): the next arcane element Spell card you activate this turn costs 1 less
  AddGlobalEffects($player, "0op3nq0ymv");
};
// Voltaic Sphere: "Deal 3 damage to target unit. If that unit is an ally, deal 5 damage to it instead." The generated body only offered allies, dealt 3 to phantasias and 5 to everything else, and read the
// target as an array.
$cardActivatedAbilities["0op3nq0ymv:0"] = function($player) { //Voltaic Sphere
  $mzID = DecisionQueueController::GetVariable("mzID");
  $targets = FilterSpellshroudTargets(array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"])));
  if(empty($targets)) return;
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Deal_3_damage_(5_to_an_ally)_to_target_unit");
  DecisionQueueController::AddDecision($player, "CUSTOM", "0op3nq0ymv:0:CardActivated-1", 1);
};
$customDQHandlers["0op3nq0ymv:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Voltaic Sphere
  $mzID = DecisionQueueController::GetVariable("mzID");
  $targetObj = GetZoneObject($lastDecision);
  if($targetObj === null) return;
  DealDamage($player, $mzID, $lastDecision, PropertyContains(EffectiveCardType($targetObj), "ALLY") ? 5 : 3);
};

// The generated "Graveyard wake cost" / "Graveyard ability cost" modifiers set the card's own HAND activation cost to (2) (Proof of Life's printed reserve cost is 1); the (2) belongs to the graveyard ability.
$activationCostModifierAbilities["mes4idoihs:0"] = function($player, $subjectObj, $currentValue, $sourceObj) { return 0; };
$activationCostModifierAbilities["ms2x2v4qe3:0"] = function($player, $subjectObj, $currentValue, $sourceObj) { return 0; };

// Lesser Boon of Rosen fJJBJ9M4c4: "(3): Summon a Powercell token rested. Activate this ability only once." The once-only limit was never enforced (and the (3) is paid via GAFieldAbilityCostTable()).
$activateAbilityAbilities["fJJBJ9M4c4:0"] = function($player) { //Rosen: summon a rested Powercell, once
  $mzID = DecisionQueueController::GetVariable("mzID");
  AddCounters($player, $mzID, "fJJBJ9M4c4_used", 1);
  $pcObj = MZAddZone($player, "myField", "qzzadf9q1v");
  if($pcObj !== null) {
      $pcObj->Status = 1;
      $pcObj->Controller = $player;
      $pcObj->Owner = $player;
  }
};
$activateAbilityPrereqs["fJJBJ9M4c4:0"] = function($player, $mzID, $abilityIndex) { //Rosen prereq: only once
  $obj = GetZoneObject($mzID);
  return $obj !== null && GetCounterCount($obj, "fJJBJ9M4c4_used") < 1;
};
// Blinding Orb qYH9PJP7uM: the bare IsClassBonusActive($player) only means "controls a champion" unless it runs inside the class-bonus source wrapper; pass the card's printed classes explicitly.
$activateAbilityAbilities["qYH9PJP7uM:0"] = function($player) { //Blinding Orb
  $opponent = $player == 1 ? 2 : 1;
  if(count(ZoneSearch("myHand", forPlayer: $opponent)) > 0) {
      DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", ZoneSearch("myHand", forPlayer: $opponent)), 1, "Put_a_card_from_your_hand_into_memory");
      DecisionQueueController::AddDecision($opponent, "CUSTOM", "qYH9PJP7uM:0:ActivateAbility-1", 1);
  }
  if(IsClassBonusActive($player, explode(",", CardClasses("qYH9PJP7uM")))) Draw($player, 1);
};

// ---------------------------------------------------------------------------------------------
// Glassgale Flock (KRNYwHCOVM): "[Merlin Bonus] [Sheen 6+] (2), [REST]: If you don't control an ally named Memorite Shardwing, summon a Memorite Shardwing token." The Shardwing condition is part of the EFFECT,
// not of the activation restriction: the generated prereq refused the activation outright while you controlled one. Only the printed restrictions (Merlin Bonus, Sheen 6+) gate the activation.
$activateAbilityPrereqs["KRNYwHCOVM:0"] = function($player, $mzID, $abilityIndex) { //KRNYwHCOVM prereq
  if(!IsMerlinBonusActive($player)) {
      SetFlashMessage("Glassgale Flock requires Merlin bonus.");
      return false;
  }
  if(GetSheenCount($player) < 6) {
      SetFlashMessage("Glassgale Flock requires 6 sheen.");
      return false;
  }
  return true;
};

// Pouvoir Absolu (OylAWd6Tew): "[Ciel Bonus] Banish the top ten cards of your deck and put an omen counter on each of them. For the rest of the game, you may activate your omens." The generated body moved the cards to the
// graveyard and then called a method that does not exist on a zone (a fatal error), and never granted the omen activation. The omens are now banished with their counters and "OMENS_ACTIVATABLE" lets ActionMap
// activate a banished card that carries an omen counter.
$playCardAbilities["OylAWd6Tew:0"] = function($player) { //Pouvoir Absolu
  if(!IsCielBonusActive($player)) return;
  for($omenIdx = 0; $omenIdx < 10; ++$omenIdx) {
      if(count(array_filter(GetDeck($player), fn($c) => !$c->removed)) == 0) break;
      BanishWithOmenCounter($player, "myDeck-0");
      DecisionQueueController::CleanupRemovedCards();
  }
  AddGlobalEffects($player, "OMENS_ACTIVATABLE");
};

// Opponent-side choices must be written in the OPPONENT's frame ("myGraveyard-N" = the decision owner's own graveyard). These generated bodies picked "myX"/"theirX" by comparing the opponent with the ambient
// viewer, so when the opponent answered, "theirGraveyard-N" / "theirMaterial-N" meant the ACTIVATING player's zone: the first banish hit the wrong card (Cleansing Reunion banished itself from its caster's graveyard).
//   Cleansing Reunion xpnjvt9y59   Windfall Check e1jCu0neWY (On Champion Hit)   Crossroads Specter r3i9nmxhnb (On Champion Hit: banish from material deck)
$cardActivatedAbilities["xpnjvt9y59:0"] = function($player) { //Cleansing Reunion
  $isImbued = DecisionQueueController::GetVariable("isImbued");
  $banishCount = ($isImbued === "YES") ? 6 : 3;
  $opponent = ($player == 1) ? 2 : 1;
  $gyCards = ZoneSearch("myGraveyard", forPlayer: $opponent);
  if(empty($gyCards)) return;
  DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", $gyCards), 1, tooltip:"Banish_a_card_from_graveyard_(1/" . $banishCount . ")");
  DecisionQueueController::AddDecision($opponent, "CUSTOM", "CleansingReunionBanish|" . ($banishCount - 1), 1);
};
$onHitAbilities["e1jCu0neWY:0"] = function($player) { //Windfall Check
  $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
  if($hitTarget === null || $hitTarget === "" || $hitTarget === "-") return;
  $hitObj = GetZoneObject($hitTarget);
  if($hitObj === null || $hitObj->removed) return;
  if(!PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) return;
  $opponent = ($player == 1) ? 2 : 1;
  $gyCards = ZoneSearch("myGraveyard", forPlayer: $opponent);
  if(empty($gyCards)) return;
  DecisionQueueController::AddDecision($opponent, "MZCHOOSE", implode("&", $gyCards), 1, tooltip:"Banish_a_card_from_graveyard");
  DecisionQueueController::AddDecision($opponent, "CUSTOM", "WindfallCheckBanish|2", 1);
};
$onHitAbilities["r3i9nmxhnb:0"] = function($player) { //Crossroads Specter
  $hitTarget = DecisionQueueController::GetVariable("CombatTarget");
  if($hitTarget === null || $hitTarget === "-" || $hitTarget === "") return;
  $hitObj = GetZoneObject($hitTarget);
  if($hitObj === null || $hitObj->removed) return;
  if(!PropertyContains(EffectiveCardType($hitObj), "CHAMPION")) return;
  $opp = ($player == 1) ? 2 : 1;
  $matCards = ZoneSearch("myMaterial", forPlayer: $opp);
  if(empty($matCards)) return;
  DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode("&", $matCards), 1, tooltip:"Banish_a_card_from_your_material_deck");
  DecisionQueueController::AddDecision($opp, "MZMOVE", "{<-}->myBanish", 1);
};

// VelTech QA Tester (2XWNCcPN6o): "On Enter: The next VelTech item card you activate targeting CARDNAME this turn costs 3 less." The discount source is the QA Tester ON THE FIELD; the generated modifier also ran against the
// card itself while it was being activated (an object with no GetMZID(): a fatal error the moment the QA Tester was played).
$activationCostModifierAbilities["2XWNCcPN6o:0"] = function($player, $subjectObj, $currentValue, $sourceObj) { //VelTech item discount
  if($sourceObj === null || $sourceObj->removed || !($sourceObj instanceof Field)) return 0;
  if($sourceObj->CardID !== "2XWNCcPN6o") return 0;
  if($sourceObj->GetMZID() !== VelTechQATesterDiscountSourceMZ($player)) return 0;
  if(!IsVelTechItemCardID($subjectObj->CardID)) return 0;
  return -3;
};

// The defender's reaction cards read the raw CombatAttacker variable, which is stored relative to the ATTACKER ("myField-1" for attacking player 2). Resolved by the defender it pointed at the defender's own
// field-1 (or nothing), so the attacking unit was never hit / shrunk. GetCombatAttackerMZ() re-localizes it through CombatAttackerPlayer and NormalizeMzIDForController() puts it in the ability controller's
// frame (the Arrow Trap override above documents the convention). Scorching Trap wjbqjdmthh, Visceral Inversion tZtoAl4ojK, Lost Promises gN8uFKSip0.
function GAResolveAttackingUnitForController($player) {
    $attackerMZ = GetCombatAttackerMZ();
    if($attackerMZ === null) return null;
    $attackerMZ = NormalizeMzIDForController($attackerMZ, $player);
    $attackerObj = GetZoneObject($attackerMZ);
    if($attackerObj === null || $attackerObj->removed) return null;
    return $attackerMZ;
}
$cardActivatedAbilities["wjbqjdmthh:0"] = function($player) { //Scorching Trap: 2 damage to target attacking unit
  $mzID = DecisionQueueController::GetVariable("mzID");
  $attackerMZ = GAResolveAttackingUnitForController($player);
  if($attackerMZ === null) return;
  DealDamage($player, $mzID, $attackerMZ, 2);
};
$cardActivatedAbilities["tZtoAl4ojK:0"] = function($player) { //Visceral Inversion: target attacking ally gets -5 POWER (-5 LIFE if ephemeral)
  $attackerMZ = GAResolveAttackingUnitForController($player);
  if($attackerMZ === null) return;
  if(!PropertyContains(EffectiveCardType(GetZoneObject($attackerMZ)), "ALLY")) return;
  AddTurnEffect($attackerMZ, DecisionQueueController::GetVariable("wasEphemerated") === "YES" ? "tZtoAl4ojK_LIFE" : "tZtoAl4ojK_POWER");
};
$cardActivatedAbilities["gN8uFKSip0:0"] = function($player) { //Lost Promises: [Alice Bonus] 3 damage to target attacking unit, end combat
  $mzID = DecisionQueueController::GetVariable("mzID");
  if(!IsAliceBonusActive($player)) return;
  $attackerMZ = GAResolveAttackingUnitForController($player);
  if($attackerMZ === null) return;
  DealDamage($player, $mzID, $attackerMZ, 3);
  EndCombat($player);
};

// Cosmic Bolt (vpmu6gvnta): "Deal 4 damage to target unit plus an additional 2 for each card named CARDNAME in your graveyard and banishment." The resolving Cosmic Bolt is not in the graveyard yet
// (rules: it is on the effect stack), so it does not count itself -- it dealt 6 instead of 4.
$customDQHandlers["vpmu6gvnta:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Deal scaling damage to a unit
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("target", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "vpmu6gvnta:0:CardActivated-1")) return;
  $target = $lastDecision;
  $count = 0;
  $resolvingIdx = GAResolvingGraveyardIndex($player);
  foreach(GetGraveyard($player) as $gi => $obj) {
      if($gi !== $resolvingIdx && !$obj->removed && $obj->CardID === "vpmu6gvnta") ++$count;
  }
  foreach(GetBanish($player) as $obj) {
      if(!$obj->removed && $obj->CardID === "vpmu6gvnta") ++$count;
  }
  DealDamage($player, $mzID, $target, 4 + 2 * $count);
};

// Mist Resonance (hw8dxKAnMX): "Allies you control get +1 LIFE until end of turn. [Class Bonus] Harmonize -- If you've activated a Melody card this turn, allies you control assign damage with their life stat
// instead of power stat until end of turn." The Harmonize sentence was a code comment saying it was not implemented. It is a second global effect, read by CombatDamageStat() (CombatLogic.php).
$cardActivatedAbilities["hw8dxKAnMX:0"] = function($player) { //Allies get +1 LIFE until end of turn; [CB] Harmonize
  AddGlobalEffects($player, "hw8dxKAnMX");
  if(IsClassBonusActive($player, explode(",", CardClasses("hw8dxKAnMX"))) && IsHarmonizeActive($player)) AddGlobalEffects($player, "hw8dxKAnMX_HARMONIZE");
};

// Manabolt Convergence (smse0zjalx): "Until end of turn, you may activate target Aethercharge card in your graveyard. You may load Manabolt Convergence into an Aetherwing weapon you control." The first sentence was a
// TODO stub. The target is chosen here; the grant is spent when that card is activated from the graveyard (GameLogic.php, ManaboltGraveyardActivationGranted()).
$cardActivatedAbilities["smse0zjalx:0"] = function($player) { //Target Aethercharge in GY becomes activatable until end of turn, may load self into Aetherwing
  $targets = ZoneSearch("myGraveyard", cardSubtypes: ["AETHERCHARGE"]);
  if(empty($targets)) { MayLoadIntoAetherwing($player, "smse0zjalx"); return; }
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, tooltip:"Choose_an_Aethercharge_card_in_your_graveyard");
  DecisionQueueController::AddDecision($player, "CUSTOM", "ManaboltConvergenceTarget", 1);
};
$customDQHandlers["ManaboltConvergenceTarget"] = function($player, $parts, $lastDecision) {
  $targetObj = GetZoneObject($lastDecision);
  if($targetObj !== null && !$targetObj->removed) ManaboltAddGraveyardGrant($player, $targetObj->CardID);
  MayLoadIntoAetherwing($player, "smse0zjalx");
};

// Absolving Flames (3fbpyhm271): "[Class Bonus] You may banish four fire element cards from your graveyard. If you do, draw a
// card." The generated body re-read ZoneSearch() after every MZMove() without compacting the zone, so the same graveyard card
// was moved four times: one card was banished (not four) and the draw still happened.
$customDQHandlers["3fbpyhm271:0:CardActivated-1"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    for($i = 0; $i < 4; ++$i) {
        $fireCards = ZoneSearch("myGraveyard", cardElements: ["FIRE"]);
        if(empty($fireCards)) break;
        MZMove($player, $fireCards[0], "myBanish");
        DecisionQueueController::CleanupRemovedCards();
    }
    Draw($player, 1);
};

// Peppered Chef (lcy0lw1veb): "On Enter: You may sacrifice another ally. If you do, Peppered Chef gets +2 POWER until end of
// turn." The generated body sacrificed first and then tagged the Chef through the mz id it had when it entered; the
// sacrifice compacts the field, so that id pointed at an empty slot (or another card) and the Chef never got the power.
$customDQHandlers["lcy0lw1veb:0:Enter-1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    AddTurnEffect($mzID, "lcy0lw1veb");
    DoSacrificeFighter($player, $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
};

// Searing Rebuke (hdvpug4d5m): "Prevent the next 3 damage that would be dealt to your champion this turn. [Class Bonus] Whenever
// damage is prevented this way, deal 3 damage to target champion you don't control." The generated body dealt the 3 damage
// immediately, whether or not anything was ever prevented. It now only arms the prevention (SEARING_REBUKE_3, plus
// SEARING_REBUKE_CB with the Class Bonus); CombatLogic's damage prevention deals the 3 when damage is actually prevented.
$cardActivatedAbilities["hdvpug4d5m:0"] = function($player) {
    $champions = ZoneSearch("myField", ["CHAMPION"]);
    if(empty($champions)) return;
    AddTurnEffect($champions[0], "SEARING_REBUKE_3");
    if(IsClassBonusActive($player, ["CLERIC"])) AddTurnEffect($champions[0], "SEARING_REBUKE_CB");
};

// Eight of Hearts (YGz8gN8M69): "Cardistry -- (8): Draw two cards. ... Activate this ability only once." The generator filed the draw
// under $cardActivatedAbilities (the PLAY-effect table) with a 0 ability-count row, so the field ability did not exist and casting
// the ally drew two cards for free. It is now a real Cardistry field ability like its sibling cards; the count/name row is in
// GAActivateAbilityCountOverrides() and the (8) cost with its Suited-object discount comes from $Cardistry_Cards.
$cardActivatedAbilities["YGz8gN8M69:0"] = function($player) { // Eight of Hearts: no on-play effect
};
$activateAbilityAbilities["YGz8gN8M69:0"] = function($player) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    $obj = &GetZoneObject($mzID);
    if(!is_array($obj->Counters)) $obj->Counters = [];
    $obj->Counters['cardistry_used'] = 1;
    Draw($player, 2);
};
$activateAbilityPrereqs["YGz8gN8M69:0"] = function($player, $mzID, $abilityIndex) {
    $sourceObject = GetZoneObject($mzID);
    if($sourceObject === null) return false;
    return !isset($sourceObject->Counters['cardistry_used']);
};

// Firetongue (j4lx6xwr42): "[Class Bonus] On Attack: You may banish a fire element card from your graveyard. If you do, put a
// durability counter on CARDNAME." A weapon's On Attack runs with the ATTACKER's mz id as "mzID", so the generated body put the
// counter on the attacking champion. The counter belongs on the weapon being used for the attack.
$customDQHandlers["j4lx6xwr42:0:OnAttack-1"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, "myBanish");
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) AddCounters($player, $weaponMZ, "durability", 1);
};

// Dragon's Dawn (9f92917r84): "[Class Bonus] On Attack: You may discard a fire element card and have CARDNAME deal 2 unpreventable
// damage to your champion. If you do, this attack gets +2 [POWER] and gains 'On Champion Hit: Draw a card.'" A weapon's On Attack
// runs with the ATTACKER's mz id as "mzID", so the generated body tagged the champion; the power bonus (ObjectCurrentPower) and the
// On Champion Hit draw (CombatLogic) both read the tags from the WEAPON, so neither ever happened.
$customDQHandlers["9f92917r84:0:OnAttack-2"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $mzID = DecisionQueueController::GetVariable("mzID");
    DoDiscardCard($player, $lastDecision);
    $champMZ = FindChampionMZ($player);
    if($champMZ !== null) DealUnpreventableDamage($player, $mzID, $champMZ, 2);
    $weaponMZ = GetCombatWeapon();
    if($weaponMZ !== null) {
        AddTurnEffect($weaponMZ, "9f92917r84-POWER");
        AddTurnEffect($weaponMZ, "9f92917r84-ONCHIT_DRAW");
    }
};

// Byakko's Command (l4rdualc8p): "Put a buff counter on target Beast ally. If that ally is a Shenju, wake it up."
// The text says "target Beast ally" -- not "you control" -- but the generated ability only searched myField, so an
// opposing Beast could never be chosen. The generated l4rdualc8p:0:CardActivated-1 handler (counter, Shenju wake-up)
// already works on any target mzID, so only the target list is widened here.
$cardActivatedAbilities["l4rdualc8p:0"] = function($player) { //Byakko's Command: any Beast ally
    $beastAllies = array_merge(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]), ZoneSearch("theirField", ["ALLY"], cardSubtypes: ["BEAST"]));
    if(empty($beastAllies)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $beastAllies), 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "l4rdualc8p:0:CardActivated-1", 1);
};

// Ravaging Tempest (WsunZX4IlW): "Banish all allies. For each ally banished this way, its controller draws a card."
// The generated body moved the opponent's allies with MZMove($opp, "theirField-N", ...): MZ ids are relative to the player passed to
// MZMove, so "theirField-N" resolved against the OPPONENT's own opposite side and the opposing allies never left the field (their
// controller still drew). Both sides are now moved from the activating player's frame.
$cardActivatedAbilities["WsunZX4IlW:0"] = function($player) { //Ravaging Tempest: banish all allies, controllers draw
    $opponent = ($player == 1) ? 2 : 1;
    $myAllies = ZoneSearch("myField", ["ALLY"]);
    $theirAllies = ZoneSearch("theirField", ["ALLY"]);
    $myCount = count($myAllies);
    $theirCount = count($theirAllies);
    foreach(array_reverse($myAllies) as $mz) MZMove($player, $mz, "myBanish");
    foreach(array_reverse($theirAllies) as $mz) MZMove($player, $mz, "theirBanish");
    DecisionQueueController::CleanupRemovedCards();
    if($myCount > 0) Draw($player, $myCount);
    if($theirCount > 0) Draw($opponent, $theirCount);
};

// Bagua of Cardinal Fate (jgyx38zpl0): the generated macro had two defects.
//   West: "Glimpse 3. Draw a card." drew immediately, before the glimpse prompt was answered, so the card drawn was not the one the player had
//         just arranged on top. The draw is now queued after the glimpse.
//   East: "Wake up each defending ally. They each get +2 [POWER] until end of turn." woke and pumped every ally on the field (a global
//         effect that also reached allies entering later). It now marks only the allies that are defending (the combat target) with a
//         turn effect that the power calculation reads.
$cardActivatedAbilities["jgyx38zpl0:0"] = function($player) { //Bagua of Cardinal Fate: branch on Shifting Currents
    $dir = GetShiftingCurrents($player);
    if($dir === "NORTH" || $dir === "SOUTH") {
        $allies = ZoneSearch("myField", ["ALLY"]);
        if(empty($allies)) return;
        $north = ($dir === "NORTH");
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allies), 1, tooltip:($north ? "Put_a_buff_counter" : "Return_an_ally_to_hand"));
        DecisionQueueController::AddDecision($player, "CUSTOM", $north ? "BaguaNorthBuff" : "BaguaSouthReturn", 1);
    } else if($dir === "WEST") {
        Glimpse($player, 3);
        QueueDrawAfterGlimpse($player, 1);
    } else if($dir === "EAST") {
        foreach(array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"])) as $mz) {
            if(!IsUnitDefending($mz)) continue;
            $obj = GetZoneObject($mz);
            if($obj === null || $obj->removed) continue;
            if($obj->Status == 1) WakeupCard($player, $mz);
            AddTurnEffect($mz, "jgyx38zpl0_POWER");
        }
    }
};

// "Ranger unit" cards: the generated macros searched with cardSubtypes: ["RANGER"]. An ally carries its class in its subtypes, a CHAMPION does not
// (its classes are only in the class list), so a Ranger champion was never offered. They now search by class, which both kinds of unit have.
// Sleety Retreat (j9fkuzgg9i), Tinderflare Pivot (s3bqtjayfn), Squallbind Pounce (ep3ajxiyd3); Exploit Vulnerability's Assassin search is fixed in place above.
function GARangerUnits() {
    return FilterSpellshroudTargets(array_merge(
        ZoneSearch("myField", ["ALLY", "CHAMPION"], cardClasses: ["RANGER"]),
        ZoneSearch("theirField", ["ALLY", "CHAMPION"], cardClasses: ["RANGER"])
    ));
}
$playCardAbilities["j9fkuzgg9i:0"] = function($player) { //Sleety Retreat
    $targets = GARangerUnits();
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "j9fkuzgg9i:0:PlayCard-1", 1);
};
$cardActivatedAbilities["s3bqtjayfn:0"] = function($player) { //Tinderflare Pivot
    $rangers = GARangerUnits();
    if(empty($rangers)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $rangers), 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "s3bqtjayfn:0:CardActivated-1", 1);
};
$cardActivatedAbilities["ep3ajxiyd3:0"] = function($player) { //Squallbind Pounce
    $rangers = GARangerUnits();
    if(!empty($rangers)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $rangers), 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "SquallbindPounceDistant", 1);
    }
    if(DecisionQueueController::GetVariable("isImbued") !== "YES") return;
    $attackingAllies = [];
    foreach(["theirField", "myField"] as $zone) {
        $field = GetZone($zone);
        for($i = 0; $i < count($field); ++$i) {
            if(!$field[$i]->removed && PropertyContains(CardType($field[$i]->CardID), "ALLY") && IsUnitAttacking($zone . "-" . $i)) {
                $attackingAllies[] = $zone . "-" . $i;
            }
        }
    }
    $attackingAllies = FilterSpellshroudTargets($attackingAllies);
    if(!empty($attackingAllies)) {
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $attackingAllies), 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "SquallbindPounceSuppress", 1);
    }
};

// Razorblade Execution (myvztzk3v8): "[Class Bonus] On Kill: If Razorblade Execution is imbued, put a preparation counter on your champion."
// The generated body read the isImbued decision variable, which is reset when the attack card leaves the stack, so it was never "YES" by the
// time the kill happened. The attack card now carries an IMBUED turn effect from the moment it enters the intent zone.
$onKillAbilities["myvztzk3v8:0"] = function($player) { //Razorblade Execution
    if(!IsClassBonusActive($player, ["ASSASSIN"])) return;
    $source = GetZoneObject(DecisionQueueController::GetVariable("mzID"));
    if($source === null || !in_array("IMBUED", $source->TurnEffects ?? [])) return;
    AddPrepCounter($player, 1);
};

// Blightheart Penitent (v2OmQJWXqX): "[REST]: If you control an Elysian object, cascade--". The generated ability cascaded unconditionally; the condition is the activation prerequisite.
$activateAbilityPrereqs["v2OmQJWXqX:0"] = function($player, $mzID, $abilityIndex) { //Blightheart Penitent prereq
    return PlayerControlsElysianObject($player);
};

// Whirlwind Vizier (5swaf8urrq): "(3), [REST], Sacrifice: Destroy target non-Fractal phantasia." Without a legal target the ability cannot be activated at all (the costs include a sacrifice).
$activateAbilityPrereqs["5swaf8urrq:0"] = function($player, $mzID, $abilityIndex) { //Whirlwind Vizier prereq
    return !empty(ZoneSearch("myField", ["PHANTASIA"], excludeSubtypes: ["FRACTAL"])) || !empty(ZoneSearch("theirField", ["PHANTASIA"], excludeSubtypes: ["FRACTAL"]));
};

// Bushwhack Bandit (kT8CeTFj82): "[Class Bonus] On Enter: Empower 2." had no ability; its "whenever you empower" buff counter lives in Empower() beside Cato's.
$enterAbilities["kT8CeTFj82:0"] = function($player) { //Bushwhack Bandit
    if(!IsClassBonusActive($player, CardClasses("kT8CeTFj82"))) return;
    Empower($player, 2, "kT8CeTFj82");
};

// Lesser Boon of Astraeus 4ZpWIJAmSC: "(3): As a Spell, suppress target ally, item, or weapon. Activate this ability only once." The generator emitted the body as a card activation (reserve 2, no once-only
// limit); it is a field ability like Lesser Boon of Rosen, with the (3) paid via GAFieldAbilityCostTable() and the use counted on the boon.
$cardActivatedAbilities["4ZpWIJAmSC:0"] = function($player) { // Lesser Boon of Astraeus: no on-play effect (the suppress ability lives in $activateAbilityAbilities)
};
$activateAbilityAbilities["4ZpWIJAmSC:0"] = function($player) { //Astraeus: suppress target ally, item or weapon, once
    $mzID = DecisionQueueController::GetVariable("mzID");
    AddCounters($player, $mzID, "4ZpWIJAmSC_used", 1);
    $targets = FilterSpellshroudTargets(array_merge(ZoneSearch("myField", ["ALLY", "ITEM", "WEAPON"]), ZoneSearch("theirField", ["ALLY", "ITEM", "WEAPON"])));
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "4ZpWIJAmSC:0:CardActivated-1", 1);
};
$activateAbilityPrereqs["4ZpWIJAmSC:0"] = function($player, $mzID, $abilityIndex) { //Astraeus prereq: only once, needs a target
    $obj = GetZoneObject($mzID);
    if($obj === null || GetCounterCount($obj, "4ZpWIJAmSC_used") >= 1) return false;
    return !empty(ZoneSearch("myField", ["ALLY", "ITEM", "WEAPON"])) || !empty(ZoneSearch("theirField", ["ALLY", "ITEM", "WEAPON"]));
};

// Perse, Relentless Raptor (nl1gxrpx8j) and Ouragan Sentinel (2zs1pana81): "[REST]: Suppress target ally, item, or weapon. Activate this ability only if CARDNAME is distant." Perse's generated body
// only offered the OPPONENT's objects (the text says any), and the Sentinel's ability was never generated at all.
foreach(["nl1gxrpx8j", "2zs1pana81"] as $gaDistantSuppressID) {
    $activateAbilityAbilities[$gaDistantSuppressID . ":0"] = function($player) use ($gaDistantSuppressID) {
        $targets = FilterSpellshroudTargets(array_merge(
            ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]),
            ZoneSearch("myField", ["REGALIA"], cardSubtypes: ["ITEM"]), ZoneSearch("theirField", ["REGALIA"], cardSubtypes: ["ITEM"]),
            ZoneSearch("myField", ["REGALIA"], cardSubtypes: ["WEAPON"]), ZoneSearch("theirField", ["REGALIA"], cardSubtypes: ["WEAPON"])
        ));
        $mzID = DecisionQueueController::GetVariable("mzID");
        $targets = array_values(array_filter($targets, fn($mz) => $mz !== $mzID));
        if(empty($targets)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "");
        DecisionQueueController::AddDecision($player, "CUSTOM", $gaDistantSuppressID . ":0:ActivateAbility-1", 1);
    };
    $activateAbilityPrereqs[$gaDistantSuppressID . ":0"] = function($player, $mzID, $abilityIndex) { //distant suppress prereq
        $sourceObject = GetZoneObject($mzID);
        if($sourceObject === null || $sourceObject->Status != 2 || !IsDistant($sourceObject)) return false;
        $targets = array_merge(ZoneSearch("myField", ["ALLY"]), ZoneSearch("theirField", ["ALLY"]),
            ZoneSearch("myField", ["REGALIA"], cardSubtypes: ["ITEM"]), ZoneSearch("theirField", ["REGALIA"], cardSubtypes: ["ITEM"]),
            ZoneSearch("myField", ["REGALIA"], cardSubtypes: ["WEAPON"]), ZoneSearch("theirField", ["REGALIA"], cardSubtypes: ["WEAPON"]));
        $targets = array_values(array_filter($targets, fn($mz) => $mz !== $mzID));
        return !empty($targets);
    };
}
$customDQHandlers["2zs1pana81:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) { //Ouragan Sentinel: suppress the chosen object
    if($lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    SuppressAlly($player, $lastDecision);
};

// Schwartz Castler PDLfDZFari: "[Alice Bonus] On Enter: If an opponent controls a unit with an even life stat, summon a Pawn Piece token." The generated body never checked the Alice Bonus.
$enterAbilities["PDLfDZFari:0"] = function($player) { //Schwartz Castler
    if(!IsAliceBonusActive($player)) return;
    $opponent = GetOpponent($player);
    foreach(GetField($opponent) as $unit) {
        if($unit === null || $unit->removed) continue;
        if(ObjectCurrentHP($unit) % 2 === 0) {
            SummonPawnPieceToken($player, 1);
            break;
        }
    }
};

// Crystalvein Awakening jH3ZOavGPR: only the "Imbue 2" is a [Merlin Bonus]; the Memorite Obelith is summoned for everyone (the generated body returned early without the bonus). GetCardImbueOptions() withholds the imbue
// from a non-Merlin champion, so isImbued is only YES for Merlin.
$cardActivatedAbilities["jH3ZOavGPR:0"] = function($player) { //Crystalvein Awakening
    global $playerID;
    SummonMemorite($player, "fdnlbJm3hr");
    $zone = $player == $playerID ? "myField" : "theirField";
    $field = GetZone($zone);
    $newIdx = count($field) - 1;
    $sheenCount = DecisionQueueController::GetVariable("isImbued") === "YES" ? 2 : 1;
    AddCounters($player, $zone . "-" . $newIdx, "sheen", $sheenCount);
};

// Portly Raccoon 9ZapXmmBc9: "...When you do, target opponent banishes a card from their graveyard." The generated body had the Raccoon's controller choose the card and banished it into their own banishment;
// the opponent chooses from their own graveyard and it goes to their banishment (the Purifying Thurible loop already does exactly that).
$customDQHandlers["9ZapXmmBc9:0:Enter-1"] = function($player, $parts, $lastDecision) { //Portly Raccoon
    if($lastDecision !== "YES") return;
    PurifyingThuribleBanishLoop($player, GetOpponent($player), 1);
};

// Six of Spades tdRR5lQHMN: "Cardistry — (6): CARDNAME loses stealth and gets +2 LIFE until end of turn. You may change the target of an attack to CARDNAME." The generated body stored its own
// zone id as CombatTarget (the wrong perspective, and without moving the target marker), so the attack still hit its original target. It now offers the redirect the way Atmos Shield does.
$activateAbilityAbilities["tdRR5lQHMN:0"] = function($player) { //Cardistry
    $mzID = DecisionQueueController::GetVariable("mzID");
    $obj = &GetZoneObject($mzID);
    if(!isset($obj->Counters) || !is_array($obj->Counters)) $obj->Counters = [];
    $obj->Counters['cardistry_used'] = 1;
    AddTurnEffect($mzID, "LOSE_STEALTH");
    AddTurnEffect($mzID, "tdRR5lQHMN");
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Change_the_attack's_target_to_Six_of_Spades?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AtmosShieldRedirect|" . $mzID, 1);
};

// Gear Haul: each Scavenge now finishes synchronously before the next starts.
$cardActivatedAbilities["n70QM0hkc9:0"] = function($player) {
    ScavengeForSubtype($player, 5, "VELTECH");
    ScavengeForSubtype($player, 5, "VELTECH");
};

// Spectral Beacon yxk7e8opr6: "(1), Banish CARDNAME: Banish target card in a graveyard." The generated body sent every target to the activator's own banishment; it goes to its owner's.
$customDQHandlers["yxk7e8opr6:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) { //Spectral Beacon
    if($lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    MZMove($player, $lastDecision, BanishDestinationOf($lastDecision));
    DecisionQueueController::CleanupRemovedCards();
};

// Lesser Boon of Regret uQeQhFl5Qm: "As you gain this boon, put up to seven cards from your hand on the bottom of your deck in any order. Then draw that many cards." (no ability existed)
$cardActivatedAbilities["uQeQhFl5Qm:0"] = function($player) { //Lesser Boon of Regret
    $hand = ZoneSearch("myHand", forPlayer: $player);
    if(empty($hand)) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|" . min(7, count($hand)) . "|" . implode("&", $hand), 1, tooltip:"Put_up_to_seven_cards_from_your_hand_on_the_bottom_of_your_deck");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LesserBoonOfRegretBottom", 1);
};
$customDQHandlers["LesserBoonOfRegretBottom"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    $chosen = array_values(array_filter(explode("&", $lastDecision)));
    usort($chosen, function($a, $b) { return intval(substr($b, strrpos($b, "-") + 1)) <=> intval(substr($a, strrpos($a, "-") + 1)); });
    foreach($chosen as $mz) MZMove($player, $mz, "myTempZone");
    PutTempZoneOnBottomOfDeck($player);
    DecisionQueueController::CleanupRemovedCards();
    Draw($player, count($chosen));
};

// Lesser Boon of Awilix jQMIfe3Xvw: "As you gain this boon, put up to four target regalia cards from any banishment into their owner's material deck." (no ability existed)
$cardActivatedAbilities["jQMIfe3Xvw:0"] = function($player) { //Lesser Boon of Awilix
    $regalia = array_merge(ZoneSearch("myBanish", ["REGALIA"], forPlayer: $player), ZoneSearch("theirBanish", ["REGALIA"], forPlayer: $player));
    if(empty($regalia)) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|" . min(4, count($regalia)) . "|" . implode("&", $regalia), 1, tooltip:"Put_up_to_four_regalia_from_banishment_into_their_owners_material_deck");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LesserBoonOfAwilixReturn", 1);
};
$customDQHandlers["LesserBoonOfAwilixReturn"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    $chosen = array_values(array_filter(explode("&", $lastDecision)));
    usort($chosen, function($a, $b) { return intval(substr($b, strrpos($b, "-") + 1)) <=> intval(substr($a, strrpos($a, "-") + 1)); });
    foreach($chosen as $mz) MZMove($player, $mz, strpos($mz, "their") === 0 ? "theirMaterial" : "myMaterial");
    DecisionQueueController::CleanupRemovedCards();
};

// Gunsmith's Arsenal e6mAjsbItw: "Materialize a Bullet or Gun card from your material deck." The generated body opened the unfiltered MaterializeChoice() (any legal material card, champions included).
$cardActivatedAbilities["e6mAjsbItw:0"] = function($player) { //Gunsmith's Arsenal
    $choices = [];
    foreach(GetZone("myMaterial") as $i => $obj) {
        if($obj === null || $obj->removed) continue;
        $subtypes = CardSubtypes($obj->CardID);
        if(PropertyContains($subtypes, "BULLET") || PropertyContains($subtypes, "GUN")) $choices[] = "myMaterial-" . $i;
    }
    if(empty($choices)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $choices), 1, tooltip:"Materialize_a_Bullet_or_Gun");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MATERIALIZE", 1);
};

// Raised Slash fuqfxq13uz: "put a buff counter on up to two target Suited allies you control" -- the second prompt offered the first target again, so one ally could get both counters.
$customDQHandlers["fuqfxq13uz:0:OnAttack-1"] = function($player, $parts, $lastDecision) { //Raised Slash
    $first = $lastDecision;
    if($first === "-" || $first === "PASS" || $first === "") return;
    AddCounters($player, $first, "buff", 1);
    $others = array_values(array_diff(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["SUITED"]), [$first]));
    if(empty($others)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $others), 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "fuqfxq13uz:0:OnAttack-2", 1);
};

// Beckon Attention (rTesQpssPz): the generated body only rewrote the CombatTarget variable, which neither redirected the damage nor read the target in the attacker's perspective.
// The stored target is relative to the attacker; the caster's champion has to be re-expressed the same way (as Trump Set does) and the combat target state/marker re-set.
$cardActivatedAbilities["rTesQpssPz:0"] = function($player) {
    $attackerPlayer = intval(DecisionQueueController::GetVariable("CombatAttackerPlayer") ?? "0");
    $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
    if($attackerPlayer <= 0 || $combatTarget === null || $combatTarget === "" || $combatTarget === "-") return;
    $targetObj = GetZoneObjectForPlayerPerspective($attackerPlayer, $combatTarget);
    if($targetObj === null || $targetObj->removed) return;
    if(!PropertyContains(EffectiveCardType($targetObj), "ALLY") && !PropertyContains(EffectiveCardType($targetObj), "CHAMPION")) return;
    if(intval($targetObj->Controller) === intval($player)) return;
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return;
    $redirectTarget = ConvertMzToPlayerPerspective($champMZ, $attackerPlayer);
    StoreCombatTargetState($redirectTarget, $attackerPlayer);
    MarkCombatTarget($attackerPlayer, $redirectTarget);
    GainCrowdsFavor($player);
};

// Lesser Boon of Allurement (JuKoCVIvCG): the generated modifier also set the *boon card's own* activation cost to 5 - phantasias (that is the cost of its (5) ability, which the
// ability body already pays), so playing the 0-cost boon demanded five payments. Only the "next phantasia costs (2) less" global-effect branch belongs in the card-cost modifier.
$activationCostModifierAbilities["JuKoCVIvCG:0"] = function($player, $subjectObj, $currentValue, $sourceObj) {
    if($sourceObj !== null && $sourceObj->CardID === "JuKoCVIvCG" && isset($sourceObj->_sourceZone) && $sourceObj->_sourceZone === "GlobalEffects") {
        if(intval($sourceObj->Controller ?? 0) !== intval($player)) return 0;
        if(!PropertyContains(CardType($subjectObj->CardID), "PHANTASIA")) return 0;
        return ["delta" => -2, "consume" => true, "applied" => true];
    }
    return 0;
};

// Uther, Illustrious King (5h8asbierp): the banished object was tagged with the TurnEffect UTHER_BANISHED, which expires at the end of the turn, so On Leave on any later turn
// found nothing to return. The tag is now a counter (counters survive turn changes) and the return moves the card from its owner's banishment with that owner's perspective.
$customDQHandlers["5h8asbierp:0:Enter-2"] = function($player, $parts, $lastDecision) {
    $chosen = $lastDecision;
    if($chosen === "" || $chosen === "-" || $chosen === "PASS") return;
    $chosenObj = GetZoneObject($chosen);
    if($chosenObj === null) return;
    $ownerPlayer = $chosenObj->Owner;
    OnLeaveField($player, $chosen);
    $banishZone = ($ownerPlayer == $player) ? "myBanish" : "theirBanish";
    $banishObj = MZMove($player, $chosen, $banishZone);
    if($banishObj !== null) {
        $banishObj->ClearTurnEffects();
        if(!is_array($banishObj->Counters)) $banishObj->Counters = [];
        $banishObj->Counters['utherBanished'] = 1;
    }
};
$leaveFieldAbilities["5h8asbierp:0"] = function($player) {
    for($p = 1; $p <= 2; ++$p) {
        $banish = GetBanish($p);
        for($bi = count($banish) - 1; $bi >= 0; --$bi) {
            if($banish[$bi]->removed || !is_array($banish[$bi]->Counters) || !isset($banish[$bi]->Counters['utherBanished'])) continue;
            $returnedObj = MZMove($p, "myBanish-" . $bi, "myField");
            if($returnedObj !== null) {
                unset($returnedObj->Counters['utherBanished']);
                $returnedObj->Status = 1;
            }
            return;
        }
    }
};

// Claimed From Beyond (nzt7qILJSl): "Banish target non-champion object IF one of its types matches one of the types among cards in your banishment". The generated handler skipped the
// condition and always sent the card to the caster's banishment instead of its owner's.
$customDQHandlers["nzt7qILJSl:0:CardActivated-1"] = function($player, $parts, $lastDecision) {
    $chosen = $lastDecision;
    if($chosen === "" || $chosen === "-" || $chosen === "PASS") return;
    $obj = GetZoneObject($chosen);
    if($obj === null || $obj->removed) return;
    $objectTypes = array_filter(["ALLY", "DOMAIN", "ITEM", "PHANTASIA", "WEAPON"], fn($t) => PropertyContains(EffectiveCardType($obj), $t));
    $matches = false;
    foreach(GetBanish($player) as $banished) {
        if($banished->removed) continue;
        foreach($objectTypes as $t) if(PropertyContains(CardType($banished->CardID), $t)) $matches = true;
    }
    if(!$matches) return;
    MZMove($player, $chosen, BanishDestinationOf($chosen));
};

// Votive Runeblade (cnjo0ehqyk): "sacrifice ANOTHER Sword regalia". The generated filter compared the candidates with the attacker's mzID (the champion), so the Runeblade itself was
// always offered; the weapon being used for this attack is the one to exclude.
$onAttackAbilities["cnjo0ehqyk:0"] = function($player) {
    if(!IsClassBonusActive($player, ["WARRIOR"])) return;
    $weaponMZ = GetCombatWeapon();
    $filtered = [];
    foreach(ZoneSearch("myField", ["REGALIA"], cardSubtypes: ["SWORD"]) as $s) {
        if($weaponMZ !== null && $s === $weaponMZ) continue;
        $filtered[] = $s;
    }
    if(empty($filtered)) return;
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $filtered), 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "cnjo0ehqyk:0:OnAttack-1", 1);
};

// Umbilical Ritual (TfBuA9PUAO): the additional cost "sacrifice an ally" needs an ally to sacrifice before the card can be activated.
$cardActivatedPrereqs["TfBuA9PUAO:0"] = function($player, $mzID) {
    return !empty(ZoneSearch("myField", ["ALLY"]));
};

// Radiant Repudiation (RO2CcBrILQ): the damage target was stored as a zone name relative to whoever cast it, but the prevention fires while the opponent's attack resolves from the other
// seat, so "theirField-1" named the wrong object. It is stored by the target's UniqueID now (looked up where the damage is dealt).
$customDQHandlers["RO2CcBrILQ:0:CardActivated-2"] = function($player, $parts, $lastDecision) {
    $pTarget = DecisionQueueController::GetVariable("RR_ProtectTarget");
    $protectObj = &GetZoneObject($pTarget);
    if($protectObj === null) return;
    AddTurnEffect($pTarget, "RADIANT_REPUDIATION_4");
    $damageObj = ($lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") ? null : GetZoneObject($lastDecision);
    if(!is_array($protectObj->Counters)) $protectObj->Counters = [];
    if($damageObj !== null && isset($damageObj->UniqueID)) $protectObj->Counters['_radiantTargetUID'] = intval($damageObj->UniqueID);
};

// Perishing Florets (Vwc52lmFvi): "memory cost 0" needs a printed memory cost of 0. The generated filter used CardMemoryCost(), which clamps "no memory cost" (-1)
// up to 0, so every ally (and every other reserve-only object) was a legal target regardless of its reserve cost.
$enterAbilities["Vwc52lmFvi:0"] = function($player) {
    $validTargets = [];
    foreach(array_merge(ZoneSearch("myField"), ZoneSearch("theirField")) as $objMZ) {
        $obj = GetZoneObject($objMZ);
        if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) continue;
        $reserveCost = intval(CardCost_reserve($obj->CardID));
        $printedMemory = CardCost_memory($obj->CardID);
        $memoryZero = $printedMemory !== null && intval($printedMemory) >= 0 && CardMemoryCost($obj) == 0;
        if(($reserveCost >= 1 && $reserveCost <= 3) || $memoryZero) $validTargets[] = $objMZ;
    }
    if(empty($validTargets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $validTargets), 1, "");
    DecisionQueueController::AddDecision($player, "CUSTOM", "Vwc52lmFvi:0:Enter-1", 1);
};

// Scepter of Awakening (2zh208013h): the [Diao Chan Bonus] is the first paragraph only (activating the card from the material deck). The (2),[REST] ability is
// printed without a bonus, but the generated prereq demanded Diao Chan for it.
$activateAbilityPrereqs["2zh208013h:0"] = function($player, $mzID, $abilityIndex) {
    $sourceObject = GetZoneObject($mzID);
    if($sourceObject === null) return false;
    if($sourceObject->Status != 2) return false;
    if(CountAvailableReservePayments($player, $mzID) < 2) return false;
    return !empty(ZoneSearch("myField", ["PHANTASIA"]));
};

// Heartsong Reclamation (d253WtyIXr): the buff counters are a [Silvie Bonus], but the generated ability gated them on the Tamer class bonus, so every Tamer
// champion got five buff counters on each Animal/Beast ally.
$cardActivatedAbilities["d253WtyIXr:0"] = function($player) {
    foreach(["theirField", "myField"] as $zoneRef) {
        $zone = GetZone($zoneRef);
        for($i = count($zone) - 1; $i >= 0; --$i) {
            if($zone[$i]->removed) continue;
            if(!PropertyContains(EffectiveCardType($zone[$i]), "REGALIA")) continue;
            if(intval($zone[$i]->Controller) === $player) continue;
            DoAllyDestroyed($player, $zoneRef . "-" . $i);
        }
    }
    DecisionQueueController::CleanupRemovedCards();
    AddGlobalEffects($player, "d253WtyIXr_CLEAVE");
    $isSilvie = false;
    foreach(["GKEpAulogu", "mdwbkuhtjm", "nllCALIXDT", "RfPP8h16Wv", "ZR8tnLruR6"] as $silvieID) {
        if(ChampionHasInLineage($player, $silvieID)) { $isSilvie = true; break; }
    }
    if($isSilvie) {
        foreach(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["ANIMAL", "BEAST"]) as $allyMZ) {
            AddCounters($player, $allyMZ, "buff", 5);
        }
    }
};

// Consumption Ring (g8q7imka92): "Activate this ability only during an opponent's recollection phase." The generated prereq refused it while ANY opportunity
// window was pending (so the REC_START window, the one place it is legal, never offered it) and allowed it on every other step of the opponent's turn.
$activateAbilityPrereqs["g8q7imka92:0"] = function($player, $mzID, $abilityIndex) {
    return GetCurrentPhase() === "BREC" && GetTurnPlayer() != $player;
};

// Accursed Strength (j3fkza233s): the discount is a [Diana Bonus], but the generated modifier gated it on the Ranger class bonus, so every Ranger champion paid 1 less.
$activationCostModifierAbilities["j3fkza233s:0"] = function($player, $subjectObj, $currentValue, $sourceObj) {
  if($subjectObj->CardID !== "j3fkza233s") return 0;
  return IsDianaBonus($player) ? -1 : 0;
};

// Maledictum Vitae (24I0xn0OQ1): "Put CARDNAME on the bottom of TARGET champion's lineage ... that champion's controller recovers 4." The generated body always used the caster's own
// champion and never asked for a target, so it could not be pointed at an opponent's champion.
$cardActivatedAbilities["24I0xn0OQ1:0"] = function($player) {
    $champions = FilterSpellshroudTargets(array_merge(ZoneSearch("myField", ["CHAMPION"]), ZoneSearch("theirField", ["CHAMPION"])));
    if(empty($champions)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $champions), 1, "Choose_a_champion_for_Maledictum_Vitae");
    DecisionQueueController::AddDecision($player, "CUSTOM", "MaledictumVitaeLineage", 1);
};
$customDQHandlers["MaledictumVitaeLineage"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    $champObj = GetZoneObject($lastDecision);
    if($champObj === null || $champObj->removed) return;
    $owner = intval($champObj->Controller);
    AddToChampionLineage($owner, "24I0xn0OQ1");
    RecoverChampion($owner, 4);
};

// Dissipation (Q9b0d7fkGv): "Destroy all phantasias" -- every phantasia on either field, not only the caster's own.
$cardActivatedAbilities["Q9b0d7fkGv:0"] = function($player) {
    $phantasias = array_merge(ZoneSearch("myField", ["PHANTASIA"]), ZoneSearch("theirField", ["PHANTASIA"]));
    foreach($phantasias as $pMZ) {
        $obj = GetZoneObject($pMZ);
        if($obj === null || $obj->removed) continue;
        $gravZone = ($player == $obj->Owner) ? "myGraveyard" : "theirGraveyard";
        DoAllyDestroyed($player, $pMZ);
        MZMove($player, $pMZ, $gravZone);
    }
    DecisionQueueController::CleanupRemovedCards();
};

// Aenean Cyclone (eCxOH7BgsS): "[Class Bonus] Efficiency" is already applied by $Efficiency_Cards (CalculateActivationReserveCost);
// the generated -PlayerLevel cost modifier discounted it a second time (13 - 2 x level).
$activationCostModifierAbilities["eCxOH7BgsS:0"] = function($player, $subjectObj, $currentValue, $sourceObj) { return 0; };

// Aenean Cyclone: the generated "Banish CARDNAME" step moved the stale activation index, so the card stayed in the graveyard. Same shape as Shattering Discharge: banish it out of the graveyard.
function AeneanCycloneBanishSelf($player) {
    $gy = GetZone("myGraveyard");
    for($gi = count($gy) - 1; $gi >= 0; --$gi) {
        if(!$gy[$gi]->removed && $gy[$gi]->CardID === "eCxOH7BgsS") {
            MZMove($player, "myGraveyard-" . $gi, "myBanish");
            break;
        }
    }
}
$cardActivatedAbilities["eCxOH7BgsS:0"] = function($player) {
    $targets = FilterSpellshroudTargets(AllNonChampionObjectTargets());
    if(empty($targets)) { AeneanCycloneBanishSelf($player); return; }
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|" . count($targets) . "|" . implode("&", $targets), 1, "Suppress_any_amount_of_non-champion_objects");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AeneanCycloneSuppress", 1);
};
$customDQHandlers["AeneanCycloneSuppress"] = function($player, $parts, $lastDecision) {
    SuppressSelectedObjects($player, $lastDecision);
    AeneanCycloneBanishSelf($player);
};

// Pearled Prayer (HLcmZLe7Xr): "If you activated this card during your main phase, recover 7. Otherwise, recover 4." The generated check only
// looked at the phase, so a copy cast in the opponent's main phase (it is fast) still recovered 7.
$cardActivatedAbilities["HLcmZLe7Xr:0"] = function($player) {
    DrawIntoMemory($player, 1);
    if(GetCurrentPhase() === "MAIN" && GetTurnPlayer() == $player) RecoverChampion($player, 7);
    else RecoverChampion($player, 4);
};

// Psychopomp's Gale (LHFUG6stdS): the resolving spell is not "in the graveyard" yet, so it must not be banished along with the rest (ZoneSearch skips that slot).
$customDQHandlers["PsychopompsGaleBanish"] = function($player, $parts, $lastDecision) {
    $self = $lastDecision === "YES";
    $banish = $self ? "myBanish" : "theirBanish";
    $targets = ZoneSearch($self ? "myGraveyard" : "theirGraveyard");
    rsort($targets, SORT_NATURAL);
    foreach($targets as $mz) MZMove($player, $mz, $banish);
};

// Anointed Purifier (mt1I9kfowQ): "[REST], Remove two buff counters from CARDNAME: Destroy target phantasia." The counters were never removed, so it could be used every turn.
$activateAbilityAbilities["mt1I9kfowQ:0"] = function($player) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    $targets = array_merge(ZoneSearch("myField", ["PHANTASIA"]), ZoneSearch("theirField", ["PHANTASIA"]));
    if(empty($targets)) return;
    RemoveCounters($player, $mzID, "buff", 2);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Destroy_target_phantasia");
    DecisionQueueController::AddDecision($player, "CUSTOM", "mt1I9kfowQ:0:ActivateAbility-1", 1);
};

// Chamberlain Toad (vgu1C2Lw6e): "Activate this ability only during an opponent's recollection phase." The generated prereq only refused windows where the player already
// had an opportunity pending and any turn but their own, so it was offered at every other step of the opponent's turn and not in the REC_START window (phase "BREC").
$activateAbilityPrereqs["vgu1C2Lw6e:0"] = function($player, $mzID, $abilityIndex) {
    if(!IsClassBonusActive($player, ["TAMER"])) return false;
    if(GetCurrentPhase() !== "BREC" || GetTurnPlayer() == $player) return false;
    $hand = GetHand($player);
    return count(array_filter($hand, fn($c) => !isset($c->removed) || !$c->removed)) >= 2;
};

// Labyrinth, Jeweled Opus (A58xZJJMz6): "[Class Bonus] This card costs 3 less to activate." The generated modifier returned early whenever a source object was passed, and the subject is always
// offered to its own modifiers as that source (ApplyGeneratedReserveLikeCostModifiers), so the discount was never applied.
$activationCostModifierAbilities["A58xZJJMz6:0"] = function($player, $subjectObj, $currentValue, $sourceObj) {
  if($subjectObj->CardID !== "A58xZJJMz6") return 0;
  if($sourceObj !== null && $sourceObj !== $subjectObj && ($sourceObj->CardID ?? "") === "A58xZJJMz6") return 0; // another copy on the field is not the subject
  return IsClassBonusActive($player, ["ASSASSIN"]) ? -3 : 0;
};

// Calculated Foresight (sl7dedg616): banishing a floating-memory card from your graveyard has to tell Nico, Whiplash Allure (lash counter); the generated handler moved it without calling NicoOnFloatingMemoryBanished.
$customDQHandlers["sl7dedg616:0:CardActivated-1"] = function($player, $parts, $lastDecision) {
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "sl7dedg616:0:CardActivated-1")) return;
  if($lastDecision === "-") return;
  MZMove($player, $lastDecision, "myBanish");
  NicoOnFloatingMemoryBanished($player);
  $champions = ZoneSearch("myField", ["CHAMPION"]);
  if(!empty($champions)) AddTurnEffect($champions[0], "RANGED_3");
};

// Sablemere, Warden's Grip (WAodKSuGuX): the opponent-graveyard condition belongs to activating it from the material deck (handled where that click is routed); the field ability "[Nico Bonus] (3+X), Banish CARDNAME"
// has no such condition, but the generated prereq demanded three cards in an opponent's graveyard for it as well.
$activateAbilityPrereqs["WAodKSuGuX:0"] = function($player, $mzID, $abilityIndex) {
  if(!IsNicoBonusActive($player)) return false;
  return CountAvailableReservePayments($player, $mzID) >= 3;
};

// Hemorrhaged Intimidation (PR4OkzJBVr): "[Class Bonus] The next card your opponent activates this turn costs (1) more to activate for every eight damage counters on your champion."
// The generated body only drew the card; the tax is one PR4OkzJBVr_COST global effect per eight damage counters on the opponent (consumed together by the next activation, see CalculateActivationReserveCost).
$cardActivatedAbilities["PR4OkzJBVr:0"] = function($player) {
    DrawIntoMemory($player, 1);
    if(IsClassBonusActive($player, ["WARRIOR"])) {
        $opponent = ($player == 1) ? 2 : 1;
        for($tax = intdiv(GetChampionDamage($player), 8); $tax > 0; --$tax) AddGlobalEffects($opponent, "PR4OkzJBVr_COST");
    }
};

// Auspicious Manifestation (vZH2xr4yq2): "[Guo Jia Bonus] Banish a Shenju ally you control." This is a choice, not a target, but the generated body ran its candidates through
// FilterSpellshroudTargets -- and every Shenju prints spellshroud, so there was never a candidate -- and OnCardActivated() strips spellshroud objects from every MZCHOOSE a Spell's opener queues
// (GASpellshroudFilterNewDecisions). The choice is therefore queued by a CUSTOM decision that runs after that filter.
$cardActivatedAbilities["vZH2xr4yq2:0"] = function($player) {
    if(!IsGuoJiaBonus($player)) return;
    DecisionQueueController::AddDecision($player, "CUSTOM", "AuspiciousManifestationChoose", 1);
};
$customDQHandlers["AuspiciousManifestationChoose"] = function($player, $parts, $lastDecision) {
    $shenju = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["SHENJU"]);
    if(empty($shenju)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $shenju), 1, tooltip:"Banish_a_Shenju_ally_you_control");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AuspiciousManifestationBanish", 1);
};
$customDQHandlers["AuspiciousManifestationBanish"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    SuppressAllyTransformedNextEnd($player, $lastDecision);
};

// Lesser Boon of Nuwa (CW73nq8jCR): "As you gain this boon, generate a Refurbish card and put it into your memory." (The Craft-activation trigger is in OnCardActivated().)
$playCardAbilities["CW73nq8jCR:0"] = function($player) {
    GAGenerateCard($player, "myMemory", "b43adsk77Y");
};
$customDQHandlers["NuwaWeaponPower"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "CW73nq8jCR_POWER");
};

// Greater Boon of Horses (asZfaFnTXs): "As you gain this boon, put any amount of Horse ally cards with total reserve cost 10 or less from your memory onto the field. They each enter the field with an additional
// buff counter on them. Then draw a card into your memory for each ally put onto the field this way."
$playCardAbilities["asZfaFnTXs:0"] = function($player) {
    DecisionQueueController::StoreVariable("HorsesBudget", "10");
    DecisionQueueController::StoreVariable("HorsesCount", "0");
    GreaterBoonOfHorsesOffer($player);
};
function GreaterBoonOfHorsesOffer($player) {
    $budget = intval(DecisionQueueController::GetVariable("HorsesBudget"));
    $choices = [];
    foreach(ZoneSearch("myMemory", ["ALLY"], cardSubtypes: ["HORSE"]) as $memoryMZ) {
        $horseObj = GetZoneObject($memoryMZ);
        if($horseObj !== null && intval(CardCost_reserve($horseObj->CardID)) <= $budget) $choices[] = $memoryMZ;
    }
    if(empty($choices)) {
        $count = intval(DecisionQueueController::GetVariable("HorsesCount"));
        if($count > 0) DrawIntoMemory($player, $count);
        return;
    }
    DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $choices), 1, tooltip:"Put_a_Horse_ally_from_memory_onto_the_field");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GreaterBoonOfHorsesPut", 1);
}
$customDQHandlers["GreaterBoonOfHorsesPut"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") {
        $count = intval(DecisionQueueController::GetVariable("HorsesCount"));
        if($count > 0) DrawIntoMemory($player, $count);
        return;
    }
    $horseObj = GetZoneObject($lastDecision);
    if($horseObj === null) return;
    $cost = intval(CardCost_reserve($horseObj->CardID));
    DecisionQueueController::StoreVariable("HorsesBudget", strval(intval(DecisionQueueController::GetVariable("HorsesBudget")) - $cost));
    DecisionQueueController::StoreVariable("HorsesCount", strval(intval(DecisionQueueController::GetVariable("HorsesCount")) + 1));
    $newObj = MZMove($player, $lastDecision, "myField");
    DecisionQueueController::CleanupRemovedCards();
    if($newObj !== null) AddCounters($player, $newObj->GetMzID(), "buff", 1);
    GreaterBoonOfHorsesOffer($player);
};

// Greater Boon of Enki (fZZqzAXNAc): "As you gain this boon, draw a card." (The token-copy trigger is in FieldAfterAdd().)
$playCardAbilities["fZZqzAXNAc:0"] = function($player) {
    Draw($player, 1);
};

// Greater Boon of Inari (ozltjoTDQe): "Remove a preparation counter from your champion: You gain agility 3 for this turn. Activate this ability only at slow speed and only once per turn." The cost is paid by the
// GAFieldAbilityCostTable() spec; the "whenever you gain agility" +2 POWER is in GainAgility() / OnAttackTrigger().
$activateAbilityAbilities["ozltjoTDQe:0"] = function($player) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    AddTurnEffect($mzID, "INARI_USED");
    GainAgility($player, 3);
};
$activateAbilityPrereqs["ozltjoTDQe:0"] = function($player, $mzID, $abilityIndex) {
    $obj = GetZoneObject($mzID);
    return $obj !== null && GAIsSlowSpeed($player) && !in_array("INARI_USED", $obj->TurnEffects ?? [], true);
};

// Greater Boon of Luxera (Klb4tguLek): "As you gain this boon, choose a non-champion non-regalia card name. The next time you activate a card with the same name this game, draw two cards and recover 3.
// You can't generate cards with the chosen name." The name is kept on the boon (the activation trigger is in OnCardActivated()). Generation checks below enforce the named prohibition.
$playCardAbilities["Klb4tguLek:0"] = function($player) {
    DecisionQueueController::AddDecision($player, "NAMECARD", "Non-champion_non-regalia_cards", 1, tooltip:"Choose_a_non-champion_non-regalia_card_name");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GreaterBoonOfLuxeraName", 1);
};
$customDQHandlers["GreaterBoonOfLuxeraName"] = function($player, $parts, $lastDecision) {
    $cardID = ResolveNamedCardInputToRepresentativeID(trim(strval($lastDecision)));
    if($cardID === null || PropertyContains(CardType($cardID), "CHAMPION") || PropertyContains(CardType($cardID), "REGALIA")) return;
    $field = &GetField($player);
    for($fi = count($field) - 1; $fi >= 0; --$fi) {
        if($field[$fi]->removed || $field[$fi]->CardID !== "Klb4tguLek") continue;
        if(!is_array($field[$fi]->Counters)) $field[$fi]->Counters = [];
        $field[$fi]->Counters['luxeraName'] = CardName($cardID);
        return;
    }
};

// Lesser Boon of Knox (WLPf8QTdsP): "As you gain this boon, each opponent banishes a card at random from your memory face down. As long as that card is banished, that opponent may look at it and activate it, ignoring
// its elemental requirements. You draw a card for each card banished from your memory this way." The card goes to its owner's banishment tagged like a Tristan, Shadowreaver card (playable by the opponent, element ignored).
$playCardAbilities["WLPf8QTdsP:0"] = function($player) {
    global $playerID;
    $opponent = ($player == 1) ? 2 : 1;
    $memoryZone = ($player == $playerID) ? "myMemory" : "theirMemory";
    $memoryCards = ZoneSearch($memoryZone);
    if(empty($memoryCards)) return;
    $chosenMZ = $memoryCards[array_rand($memoryCards)];
    $banished = MZMove($player, NormalizeMZForPlayerPerspective($player, $chosenMZ), "myBanish");
    DecisionQueueController::CleanupRemovedCards();
    if($banished === null) return;
    if(!isset($banished->Counters) || !is_array($banished->Counters)) $banished->Counters = [];
    $banished->Counters["_shadowreaverPlayableBy"] = $opponent;
    $banished->Counters["_shadowreaverFaceDown"] = 1;
    Draw($player, 1);
};

// Greater Boon of Detachment (MzGWQK0E2o): "(3): Target Ranger weapon gets +3 POWER until end of turn. Activate this ability only once during each of your turns, and only if your champion is distant."
// (Declaring attacks with unloaded Ranger weapons is in GetAvailableWeapons().)
function GreaterBoonOfDetachmentTargets() {
    $targets = [];
    foreach(["myField", "theirField"] as $zone) {
        foreach(ZoneSearch($zone, ["WEAPON"]) as $weaponMZ) {
            $weaponObj = GetZoneObject($weaponMZ);
            if($weaponObj !== null && PropertyContains(EffectiveCardClasses($weaponObj), "RANGER")) $targets[] = $weaponMZ;
        }
    }
    return FilterSpellshroudTargets($targets);
}
$activateAbilityAbilities["MzGWQK0E2o:0"] = function($player) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    AddTurnEffect($mzID, "DETACHMENT_USED");
    $targets = GreaterBoonOfDetachmentTargets();
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, tooltip:"Target_Ranger_weapon_gets_+3_POWER");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GreaterBoonOfDetachmentPower", 1);
};
$customDQHandlers["GreaterBoonOfDetachmentPower"] = function($player, $parts, $lastDecision) {
    if($lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    AddTurnEffect($lastDecision, "MzGWQK0E2o_POWER");
};
$activateAbilityPrereqs["MzGWQK0E2o:0"] = function($player, $mzID, $abilityIndex) {
    $obj = GetZoneObject($mzID);
    if($obj === null || GetTurnPlayer() != $player || in_array("DETACHMENT_USED", $obj->TurnEffects ?? [], true)) return false;
    $champMZ = FindChampionMZ($player);
    $champObj = $champMZ === null ? null : GetZoneObject($champMZ);
    if($champObj === null || !IsDistant($champObj)) return false;
    return !empty(GreaterBoonOfDetachmentTargets());
};

// Gate of Alterity (j9s7xQdpf1): "[REST]: Put CARDNAME on the bottom of your champion's lineage then choose a class. CARDNAME gains 'Inherited Effect: This object is the chosen class in addition to its other classes.'"
// A lineage card keeps no state of its own, so the chosen classes are recorded on the champion (EffectiveCardClasses() reads them).
$activateAbilityAbilities["j9s7xQdpf1:0"] = function($player) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    $gateObj = &GetZoneObject($mzID);
    if($gateObj === null) return;
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return;
    OnLeaveField($player, $mzID);
    $gateObj->Remove();
    DecisionQueueController::CleanupRemovedCards();
    $champMZ = FindChampionMZ($player);
    $champObj = &GetZoneObject($champMZ);
    if(!is_array($champObj->Subcards)) $champObj->Subcards = [];
    array_unshift($champObj->Subcards, "j9s7xQdpf1");
    DecisionQueueController::AddDecision($player, "MZMODAL", "1|1|" . implode("&", array_map(fn($i, $class) => chr(65 + $i) . ":_" . ucfirst(strtolower($class)), array_keys(GAGateClasses()), GAGateClasses())), 1, "Choose_a_class_(Gate_of_Alterity)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "GateOfAlterityClass", 1);
};
function GAGateClasses() { return ["ASSASSIN", "CLERIC", "GUARDIAN", "MAGE", "RANGER", "TAMER", "WARRIOR"]; }
$customDQHandlers["GateOfAlterityClass"] = function($player, $parts, $lastDecision) {
    $classes = GAGateClasses();
    $chosen = $classes[intval($lastDecision)] ?? null;
    if($chosen === null) return;
    $champMZ = FindChampionMZ($player);
    if($champMZ === null) return;
    $champObj = &GetZoneObject($champMZ);
    if(!is_array($champObj->Counters)) $champObj->Counters = [];
    $champObj->Counters['gateClasses'] = ($champObj->Counters['gateClasses'] ?? "") === "" ? $chosen : $champObj->Counters['gateClasses'] . "," . $chosen;
};

// Prismatic Spirit (W3m0tnw4sr): "On Enter: Draw six cards then choose two basic elements. CARDNAME gains 'Inherited Effect: This object is each of the chosen elements in addition to its other elements.'"
// The chosen elements are recorded on the entering champion; GetPlayerEnabledElements() reads them.
$enterAbilities["W3m0tnw4sr:0"] = function($player) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    DecisionQueueController::StoreVariable("PrismaticSpiritMZ", strval($mzID));
    Draw($player, 6);
    DecisionQueueController::AddDecision($player, "MZMODAL", "2|2|A:_Fire&B:_Water&C:_Wind", 1, "Choose_two_basic_elements_(Prismatic_Spirit)");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PrismaticSpiritElements", 1);
};
$customDQHandlers["PrismaticSpiritElements"] = function($player, $parts, $lastDecision) {
    $elements = ["FIRE", "WATER", "WIND"];
    $chosen = [];
    foreach(explode(",", strval($lastDecision)) as $choiceIndex) if(isset($elements[intval($choiceIndex)]) && $choiceIndex !== "") $chosen[] = $elements[intval($choiceIndex)];
    if(empty($chosen)) return;
    $mzID = strval(DecisionQueueController::GetVariable("PrismaticSpiritMZ"));
    $obj = $mzID !== "" ? GetZoneObject($mzID) : null;
    if($obj === null || $obj->removed || $obj->CardID !== "W3m0tnw4sr") { $champMZ = FindChampionMZ($player); $obj = $champMZ === null ? null : GetZoneObject($champMZ); }
    if($obj === null) return;
    if(!is_array($obj->Counters)) $obj->Counters = [];
    $obj->Counters['prismaticElements'] = implode(",", array_unique($chosen));
};

// Greater Boon of Astraeus (B9RzxSut57): "As you gain this boon, draw a card for each of up to two times you've suppressed an object this turn." (The X+Y cost reduction is in CalculateActivationReserveCost().)
$playCardAbilities["B9RzxSut57:0"] = function($player) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    $suppressed = min(2, intval($_ti["SuppressCount"][$player] ?? 0));
    if($suppressed > 0) Draw($player, $suppressed);
};

// Element membership (Phase D): "a fire/wind/water element card" is membership in the card's element list, so an Exalted Fire (or Wind/Water) card counts. These generated bodies compared
// CardElement() to a single element with === / !==, which refuses every dual-element card. The bodies below are the generated ones with each such comparison turned into GAIsElement().
$activateAbilityAbilities["96659ytyj2:0"] = function($player) { //Banish
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $abilityIndex = DecisionQueueController::GetVariable("abilityIndex");
  $opponent = ($player == 1) ? 2 : 1;
  $memory = GetMemory($opponent);
  if(count($memory) == 0) return;
  $indices = range(0, count($memory) - 1);
  shuffle($indices);
  $revealCount = min(2, count($memory));
  for($i = 0; $i < $revealCount; $i++) {
      $idx = $indices[$i];
      $cardID = $memory[$idx]->CardID;
      if(GAIsElement($cardID, "WIND")) {
          MZMove($player, "theirMemory-" . $idx, "theirBanish");
          // After a move, re-fetch memory and adjust remaining indices
          DecisionQueueController::CleanupRemovedCards();
          $memory = GetMemory($opponent);
          for($j = $i + 1; $j < $revealCount; $j++) {
              if($indices[$j] > $idx) $indices[$j]--;
          }
      }
  }
};
$enterAbilities["1o0tKizBZ6:0"] = function($player) { //Windstream Mutt Enter
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // [Class Bonus] On Enter: Reveal a random card from memory.
  // If wind element, choose another ally and put a buff counter on it.
  if(!IsClassBonusActive($player, ["TAMER"])) return;
  
  $memoryCards = ZoneSearch("myMemory");
  if(empty($memoryCards)) return;
  
  // Pick a random card from memory
  $randomMZ = $memoryCards[array_rand($memoryCards)];
  $randomObj = GetZoneObject($randomMZ);
  $revealedCardID = $randomObj->CardID;
  
  // Reveal the card
  Reveal($player, revealedMZ: $randomMZ);
  
  // Check if the revealed card is wind element
  if(!GAIsElement($revealedCardID, "WIND")) return;
  
  // Choose another ally you control to put a buff counter on
  $allies = ZoneSearch("myField", ["ALLY"]);
  $allies = array_values(array_filter($allies, fn($a) => $a !== $mzID));
  if(empty($allies)) return;
  $options = implode("&", $allies);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", "$options", 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "1o0tKizBZ6:0:Enter-1", 1);
};
$enterAbilities["8eyeqhc37y:0"] = function($player) { //8eyeqhc37y
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $field = &GetField($player);
  $hasFireInLineage = false;
  foreach($field as $fObj) {
      if(!$fObj->removed && PropertyContains(EffectiveCardType($fObj), "CHAMPION") && $fObj->Controller == $player) {
          $subcards = is_array($fObj->Subcards) ? $fObj->Subcards : [];
          foreach($subcards as $scID) {
              if($scID !== "8eyeqhc37y" && GAIsElement($scID, "FIRE")) {
                  $hasFireInLineage = true;
                  break;
              }
          }
          break;
      }
  }
  if($hasFireInLineage) {
      AddGlobalEffects($player, "POLKHAWK_NEXT_RANGER_DISTANT");
  }
};
$enterAbilities["ba0tqvwlp1:0"] = function($player) { //Equestrian: Discard fire cards to draw into memory
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Check if you control a Horse ally
  if(empty(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["HORSE"]))) return;
  
  // Find fire element cards in hand
  $fireInHand = [];
  $hand = GetZone("myHand");
  for($i = 0; $i < count($hand); ++$i) {
      if(!$hand[$i]->removed && GAIsElement($hand[$i]->CardID, "FIRE")) {
          $fireInHand[] = "myHand-" . $i;
      }
  }
  
  if(empty($fireInHand)) return;
  
  // Ask player to choose up to 2 fire cards to discard
  $targetStr = implode("&", $fireInHand);
  $maxDiscard = min(2, count($fireInHand));
  DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", 0 . "|" . $maxDiscard . "|" . $targetStr, 1, "Choose_up_to_2_fire_cards_to_discard");
  DecisionQueueController::AddDecision($player, "CUSTOM", "RitaiStablemasterProcess", 1);
};
$enterAbilities["wc8tuhuy4x:0"] = function($player) { //wc8tuhuy4x
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $hand = GetHand($player);
  $fireCards = [];
  foreach($hand as $i => $handObj) {
      if(GAIsElement($handObj->CardID, "FIRE")) {
          $fireCards[] = "myHand-" . $i;
      }
  }
  if(empty($fireCards)) return;
  $fireStr = implode("&", $fireCards);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $fireStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "wc8tuhuy4x:0:Enter-1", 1);
};
$enterAbilities["x8bd7ozuj6:0"] = function($player) { //If another wind element card in lineage, glimpse 4
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Vanitas, Obliviate Schemer: if another wind element card in lineage, glimpse 4
  $lineage = GetChampionLineage($player);
  $hasWindInLineage = false;
  foreach($lineage as $idx => $lcID) {
      if($idx === 0) continue;
      if(GAIsElement($lcID, "WIND")) {
          $hasWindInLineage = true;
          break;
      }
  }
  if($hasWindInLineage) {
      Glimpse($player, 4);
  }
};
$enterAbilities["qe1pkerbi3:0"] = function($player) { //qe1pkerbi3
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  if(PlayerLevel($player) < 2) return;
  // Banish all non-water cards from each player's graveyard
  $myGY = ZoneSearch("myGraveyard");
  for($i = count($myGY) - 1; $i >= 0; --$i) {
      $obj = GetZoneObject($myGY[$i]);
      if($obj !== null && !GAIsElement($obj->CardID, "WATER")) {
          MZMove($player, $myGY[$i], "myBanish");
      }
  }
  $theirGY = ZoneSearch("theirGraveyard");
  for($i = count($theirGY) - 1; $i >= 0; --$i) {
      $obj = GetZoneObject($theirGY[$i]);
      if($obj !== null && !GAIsElement($obj->CardID, "WATER")) {
          MZMove($player, $theirGY[$i], "theirBanish");
      }
  }
};
$cardActivatedAbilities["y6isxy5lh2:0"] = function($player) { //y6isxy5lh2
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Force Load: Choose a fire/norm 0-cost Bullet from material deck, load into Gun.
  $materialZone = GetZone("myMaterial");
  $bulletMZs = [];
  for($i = 0; $i < count($materialZone); ++$i) {
      $obj = $materialZone[$i];
      if(!$obj->removed && PropertyContains(CardSubtypes($obj->CardID), "BULLET")
          && CardMemoryCost($obj) == 0
          && (GAIsElement($obj->CardID, "FIRE") || GAIsElement($obj->CardID, "NORM"))) {
          $bulletMZs[] = "myMaterial-" . $i;
      }
  }
  if(empty($bulletMZs)) return;
  $bulletStr = implode("&", $bulletMZs);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $bulletStr, 1, "Choose_a_fire/norm_Bullet");
  DecisionQueueController::AddDecision($player, "CUSTOM", "ForceLoadStoreBullet", 1);
};
$discardCardAbilities["KEhmWGivJp:0"] = function($player) { //Fire discard buff counter
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  $discardedCardID = DecisionQueueController::GetVariable("discardedCardID");
  if(!GAIsElement($discardedCardID, "FIRE")) return;
  
  $field = GetField($player);
  for($i = 0; $i < count($field); ++$i) {
      if($field[$i]->removed || $field[$i]->CardID !== "KEhmWGivJp") continue;
      if(GetCounterCount($field[$i], "buff") > 0) continue;
      AddCounters($player, "myField-" . $i, "buff", 1);
  }
};
$onAttackAbilities["7xgwve1d47:0"] = function($player) { //7xgwve1d47
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  if(!IsClassBonusActive($player, ["RANGER"])) return;
  $deck = &GetDeck($player);
  if(empty($deck)) return;
  MZMove($player, "myDeck-0", "myTempZone");
  $tempCards = ZoneSearch("myTempZone");
  if(empty($tempCards)) return;
  $topObj = GetZoneObject($tempCards[0]);
  if($topObj === null || !GAIsElement($topObj->CardID, "WATER")) {
      PutTempZoneOnTopOfDeck($player);
      return;
  }
  DecisionQueueController::AddDecision($player, "YESNO", "-", 1, "Put_water_card_into_graveyard?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "7xgwve1d47:0:OnAttack-1", 1);
};
$onAttackAbilities["hreqhj1trn:0"] = function($player) { //hreqhj1trn
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Windpiercer: On Attack: Glimpse 1, then reveal top card. If wind element, +2 POWER.
  Glimpse($player, 1);
  $deck = GetDeck($player);
  if(empty($deck)) return;
  $topCard = $deck[0];
  DoRevealCard($player, "myDeck-0");
  if(GAIsElement($topCard->CardID, "WIND")) {
      AddTurnEffect($mzID, "hreqhj1trn-power");
  }
};
$onAttackAbilities["iuixf9rdmu:0"] = function($player) { //[Diana Bonus] On Attack: per wind in intent, ally +1 power
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  if(!IsDianaBonus($player)) return;
  $intentCards = GetIntentCards($player);
  $windCount = 0;
  foreach($intentCards as $iMZ) {
      $iObj = GetZoneObject($iMZ);
      if($iObj === null) continue;
      if(GAIsElement($iObj->CardID, "WIND")) $windCount++;
  }
  if($windCount <= 0) return;
  LegionsWingspanBuffLoop($player, $windCount);
};
$onAttackAbilities["oqk1l75tlz:0"] = function($player) { //oqk1l75tlz
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // [Polkhawk Bonus] On Attack: if there's a fire card in attacker's intent, deal 2 to each defending unit
  $champMZs = ZoneSearch("myField", ["CHAMPION"]);
  if(empty($champMZs)) return;
  $champObj = GetZoneObject($champMZs[0]);
  if($champObj->CardID !== "ryvfq3huqj" || HasNoAbilities($champObj)) return;
  
  $hasFireInIntent = false;
  $intentCards = GetIntentCards($player);
  foreach($intentCards as $iMZ) {
      $iObj = GetZoneObject($iMZ);
      if($iObj !== null && GAIsElement($iObj->CardID, "FIRE")) {
          $hasFireInIntent = true;
          break;
      }
  }
  if(!$hasFireInIntent) return;
  
  $weaponMZ = GetCombatWeapon();
  $defendingUnits = ZoneSearch("theirField", ["ALLY", "CHAMPION"]);
  foreach($defendingUnits as $targetMZ) {
      DealDamage($player, $weaponMZ, $targetMZ, 2);
  }
};
$onAttackAbilities["q5CXVeHGIf:0"] = function($player) { //Link VelTech item and draw
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  global $AllyLink_Cards;
  $candidates = [];
  foreach(array_merge(ZoneSearch("myHand"), ZoneSearch("myMemory")) as $candidateMZ) {
      $candidateObj = GetZoneObject($candidateMZ);
      if($candidateObj === null || !isset($AllyLink_Cards[$candidateObj->CardID])) continue;
      if(!PropertyContains(CardSubtypes($candidateObj->CardID), "VELTECH")) continue;
      if(GAIsAnyElement($candidateObj->CardID, ["NORM", "FIRE", "WATER", "WIND"])) $candidates[] = $candidateMZ;
  }
  if(empty($candidates)) return;
  $candidateStr = implode("&", $candidates);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $candidateStr, 1, "Put_a_VelTech_Ally_Link_item_onto_the_field?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "q5CXVeHGIf:0:OnAttack-1", 1);
};
$onAttackAbilities["qmyn2rz308:0"] = function($player) { //CB OnAttack: discard fire card for +3 POWER
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  if(!IsClassBonusActive($player, ["CLERIC"])) return;
  $combatTarget = DecisionQueueController::GetVariable("CombatTarget");
  if($combatTarget == "-" || $combatTarget == "") return;
  $targetObj = GetZoneObject($combatTarget);
  if($targetObj === null || !PropertyContains(EffectiveCardType($targetObj), "ALLY")) return;
  $fireCards = [];
  $hand = GetZone("myHand");
  foreach($hand as $hi => $hObj) {
      if(!$hObj->removed && GAIsElement($hObj->CardID, "FIRE")) {
          $fireCards[] = "myHand-" . $hi;
      }
  }
  if(empty($fireCards)) return;
  $fireStr = implode("&", $fireCards);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", $fireStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "qmyn2rz308:0:OnAttack-1", 1);
};
$onHitAbilities["TgYTZg6TaG:0"] = function($player) { //On Hit: reveal random memory; if wind, put CARDNAME into memory
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  // Wind Cutter: On Hit: Reveal a card at random from your memory. If wind element, put CARDNAME into owner's memory.
  $memory = GetZone("myMemory");
  if(empty($memory)) return;
  $randomIdx = array_rand($memory);
  $revealedMZ = "myMemory-" . $randomIdx;
  DoRevealCard($player, $revealedMZ);
  $revealedObj = GetZoneObject($revealedMZ);
  if($revealedObj === null) return;
  if(!GAIsElement($revealedObj->CardID, "WIND")) return;
  // Revealed card is wind — put Wind Cutter into owner's memory
  $intentCards = GetIntentCards($player);
  foreach($intentCards as $iMZ) {
      $iObj = GetZoneObject($iMZ);
      if($iObj !== null && $iObj->CardID === "TgYTZg6TaG") {
          MZMove($player, $iMZ, "myMemory");
          break;
      }
  }
};
$customDQHandlers["THjSE7caau:0:Enter-1"] = function($player, $parts, $lastDecision) { //THjSE7caau
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("discarded", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "THjSE7caau:0:Enter-1")) return;
  $discarded = $lastDecision;
  $discObj = GetZoneObject($discarded);
  $discCardID = $discObj !== null ? $discObj->CardID : null;
  DoDiscardCard($player, $discarded);
  if(!IsMerlinBonusActive($player) || $discCardID === null || !GAIsElement($discCardID, "FIRE")) return;
  $allUnits = array_merge(
      ZoneSearch("myField", ["ALLY", "CHAMPION"]),
      ZoneSearch("theirField", ["ALLY", "CHAMPION"])
  );
  if(empty($allUnits)) return;
  $unitStr = implode("&", $allUnits);
  DecisionQueueController::AddDecision($player, "MZCHOOSE", $unitStr, 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "THjSE7caau:0:Enter-2", 1);
};
$customDQHandlers["r7oifozaog:0:OnAttack-1"] = function($player, $parts, $lastDecision) { //Discard for memory draw and buff
  // Retrieve macro parameters
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "r7oifozaog:0:OnAttack-1")) return;
  $chosen = $lastDecision;
  if($chosen === "-" || $chosen === "" || $chosen === "PASS") return;
  $discardedObj = GetZoneObject($chosen);
  if($discardedObj === null) return;
  $isFire = GAIsElement($discardedObj->CardID, "FIRE");
  MZMove($player, $chosen, "myGraveyard");
  DrawIntoMemory($player, 1);
  if($isFire) AddTurnEffect($mzID, "r7oifozaog");
};

// Combat reader (Phase D): CombatAttacker / CombatTarget are stored in the ATTACKER's frame. Flamebreak Chorus (yky280mtts) compared the raw CombatTarget with the chosen ally (chosen in the caster's
// frame), so a defending ally was never woken or given its level bonus when the defender cast it. Enervating Decay (jh9s424gjr) assumed the attacker is always the opponent (str_replace of
// myField) and fell back to EVERY opposing ally when the attacker was not an ally or there was no combat; "target attacking ally" is the attacking ally, whoever casts it.
$customDQHandlers["yky280mtts:0:CardActivated-1"] = function($player, $parts, $lastDecision) { //Flamebreak Chorus
  $mzID = DecisionQueueController::GetVariable("mzID");
  DecisionQueueController::StoreVariable("chosen", $lastDecision);
  if(function_exists('ApplyVirgilProgramTargetDiscount')) ApplyVirgilProgramTargetDiscount($player, $lastDecision);
  if(function_exists('AllowGeneratedTargetResolution') && !AllowGeneratedTargetResolution($player, $lastDecision, "yky280mtts:0:CardActivated-1")) return;
  $chosen = $lastDecision;
  AddTurnEffect($chosen, "yky280mtts_POWER");
  // Check if the chosen ally is the current combat target (defending)
  $combatTarget = GetCombatTargetMZ();
  if($combatTarget !== null) $combatTarget = NormalizeMzIDForController($combatTarget, $player);
  if($combatTarget !== null && $combatTarget === $chosen) {
      WakeupCard($player, $chosen);
      $lv = PlayerLevel($player);
      if($lv > 0) {
          AddTurnEffect($chosen, "yky280mtts_LV_" . $lv);
      }
  }
};
function GAEnervatingDecayTargets($player) {
  $attackerMZ = GAResolveAttackingUnitForController($player);
  if($attackerMZ === null) return [];
  $attackerObj = GetZoneObject($attackerMZ);
  if($attackerObj === null || !PropertyContains(EffectiveCardType($attackerObj), "ALLY")) return [];
  return FilterSpellshroudTargets([$attackerMZ]);
}
$cardActivatedPrereqs["jh9s424gjr:0"] = function($player, $mzID) { //Enervating Decay needs an attacking ally
  return !empty(GAEnervatingDecayTargets($player));
};
$playCardAbilities["jh9s424gjr:0"] = function($player) { //Enervating Decay
  $targets = GAEnervatingDecayTargets($player);
  if(empty($targets)) return;
  DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "");
  DecisionQueueController::AddDecision($player, "CUSTOM", "jh9s424gjr:0:PlayCard-1", 1);
};

// Temporary Control Wake (3iG6h4jAPl): "...wake it up" wrote Status = 2 on any target, so a Briar or a Cataleptic Constellation-held ally woke anyway.
if(isset($customDQHandlers["3iG6h4jAPl:0:CardActivated-1"])) {
  $customDQHandlers["3iG6h4jAPl:0:CardActivated-1"] = (function($original) {
    return function($player, $parts, $lastDecision) use ($original) {
      $targetObj = GetZoneObject($lastDecision);
      $statusBefore = $targetObj !== null ? $targetObj->Status : null;
      $original($player, $parts, $lastDecision);
      $targetObj = GetZoneObject($lastDecision);
      if($targetObj !== null && $statusBefore !== null && GAWakeUpLocked($targetObj)) $targetObj->Status = $statusBefore;
    };
  })($customDQHandlers["3iG6h4jAPl:0:CardActivated-1"]);
}

// Nico, Whiplash Allure / Wield-Not-Wrath style lash triggers: these generated handlers banish a floating-memory card from the graveyard without
// telling Nico (NicoOnFloatingMemoryBanished). Wrap each so a net gain in the banish zone notifies him once the original has run.
foreach(["cfpwakb1k0:0:Enter-1", "fbvt9rdhkj:0:Enter-1", "qb6zhphtw6:0:Enter-1", "2ha4dk88zq:0:CardActivated-1",
         "dxAEI20h8F:0:CardActivated-1", "3p6i0iqmyn:0:OnAttack-1", "d5og6z31q9:0:OnAttack-1"] as $nicoHandlerKey) {
  if(!isset($customDQHandlers[$nicoHandlerKey])) continue;
  $customDQHandlers[$nicoHandlerKey] = (function($original) {
    return function($player, $parts, $lastDecision) use ($original) {
      $before = count(GetZone("myBanish"));
      $original($player, $parts, $lastDecision);
      if(count(GetZone("myBanish")) > $before) NicoOnFloatingMemoryBanished($player);
    };
  })($customDQHandlers[$nicoHandlerKey]);
}

// Ardus, Floodborne Deacon (EKiMJfgvSI): "...have an additional instance of each deluge ability and effect they have." DelugeInstances($player) is 2 with him active; the threshold
// (DelugeAmount) stays the real count. Static stat bonuses and Treasure of the Depths multiply in GameLogic.php; the generated one-shot and cost handlers are wrapped here.
// Do1blsupu0 Dynasty Chancellor, qe1pkerbi3 Drowned Exorcist and rqjnvhf26m Mistsworn Magister are On Death deluge abilities: run once per instance.
foreach(["do1blsupu0:0", "qe1pkerbi3:0", "rqjnvhf26m:0"] as $delugeKey) {
  if(!isset($allyDestroyedAbilities[$delugeKey])) continue;
  $allyDestroyedAbilities[$delugeKey] = (function($original) {
    return function($player) use ($original) {
      for($inst = DelugeInstances($player); $inst > 0; --$inst) $original($player);
    };
  })($allyDestroyedAbilities[$delugeKey]);
}
// Disciple of the Waves / Sleety Retreat: the cost reduction applies once per instance.
foreach(["m9sfzj5d1i:0", "j9fkuzgg9i:0"] as $delugeKey) {
  if(!isset($activationCostModifierAbilities[$delugeKey])) continue;
  $activationCostModifierAbilities[$delugeKey] = (function($original) {
    return function($player, $subjectObj, $currentValue, $sourceObj) use ($original) {
      $reduction = $original($player, $subjectObj, $currentValue, $sourceObj);
      return $reduction < 0 ? $reduction * DelugeInstances($player) : $reduction;
    };
  })($activationCostModifierAbilities[$delugeKey]);
}
// Conniving Plans: the deluge half (preparation counter + draw into memory) happens once per instance.
$cardActivatedAbilities["2b2w1ydw5z:0"] = function($player) {
  MillCards($player, "myDeck", "myGraveyard", 2);
  if(DelugeAmount($player) < 4) return;
  for($inst = DelugeInstances($player); $inst > 0; --$inst) {
    $champions = ZoneSearch("myField", ["CHAMPION"]);
    if(!empty($champions)) AddCounters($player, $champions[0], "preparation", 1);
    DrawIntoMemory($player, 1);
  }
};
// Brookside Scout (load an Aethercharge) and Liquidation (7 damage to a unit) ask for a choice, so an extra instance is re-offered after the first one resolved
// (DQ variable "delugeExtra:<handler>") instead of being queued up front with stale indexes.
foreach([["9asvHr4bsz:0", "9asvHr4bsz:0:Enter-1", "enterAbilities"], ["b6BFtEqtQM:0", "b6BFtEqtQM:0:CardActivated-1", "cardActivatedAbilities"]] as [$delugeAbilityKey, $delugeHandlerKey, $delugeAbilityTable]) {
  if(!isset($customDQHandlers[$delugeHandlerKey]) || !isset($$delugeAbilityTable[$delugeAbilityKey])) continue;
  $delugeStart = $$delugeAbilityTable[$delugeAbilityKey];
  $$delugeAbilityTable[$delugeAbilityKey] = (function($original, $handlerKey) {
    return function($player) use ($original, $handlerKey) {
      DecisionQueueController::StoreVariable("delugeExtra:" . $handlerKey, strval(DelugeInstances($player) - 1));
      $original($player);
    };
  })($delugeStart, $delugeHandlerKey);
  $customDQHandlers[$delugeHandlerKey] = (function($original, $start, $handlerKey) {
    return function($player, $parts, $lastDecision) use ($original, $start, $handlerKey) {
      $original($player, $parts, $lastDecision);
      $extra = intval(DecisionQueueController::GetVariable("delugeExtra:" . $handlerKey));
      if($extra > 0) {
        DecisionQueueController::StoreVariable("delugeExtra:" . $handlerKey, strval($extra - 1));
        if($handlerKey === "9asvHr4bsz:0:Enter-1") {
          // The generated handler may have queued a weapon choice and LoadAetherwingSelect.
          // Finish that load before rebuilding the next instance's graveyard choices.
          DecisionQueueController::AddDecision($player, "CUSTOM", "GABrooksideNextDeluge", 1, dontSkipOnPass: 1);
        } else {
          $start($player);
        }
      }
    };
  })($customDQHandlers[$delugeHandlerKey], $delugeStart, $delugeHandlerKey);
  if($delugeHandlerKey === "9asvHr4bsz:0:Enter-1") {
    $customDQHandlers["GABrooksideNextDeluge"] = function($player, $parts, $lastDecision) use ($delugeStart) {
      $delugeStart($player);
    };
  }
}

// These sacrifices are additional activation costs, paid by GenericSacrificeCost.
// The generated bodies incorrectly paid them at resolution.
$cardActivatedPrereqs["Pr48kXnasw:0"] = function($player, $mzID) {
    return !empty(ZoneSearch("myField", ["ALLY"]));
};
$cardActivatedAbilities["Pr48kXnasw:0"] = function($player) {
    Draw($player, 2);
};
$cardActivatedAbilities["7Pc1aiu3Bq:0"] = function($player) {
    $targets = FilterSpellshroudTargets(array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"])));
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, tooltip:"Deal_damage_to_target_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "7Pc1aiu3Bq:0:CardActivated-2", 1);
};
$customDQHandlers["7Pc1aiu3Bq:0:CardActivated-2"] = function($player, $parts, $lastDecision) {
    if(!AllowGeneratedTargetResolution($player, $lastDecision, "7Pc1aiu3Bq:0:CardActivated-2")) return;
    ApplyVirgilProgramTargetDiscount($player, $lastDecision);
    $source = DecisionQueueController::GetVariable("mzID");
    $durability = intval(DecisionQueueController::GetVariable("moltenImpactDurability"));
    DealDamage($player, $source, $lastDecision, 2 + $durability);
};

// Genbu's Command and Suzaku's Command are Skills. Spellshroud only
// restricts Spells, and "target Beast ally" permits either player's ally.
foreach (["jjwp945rlb", "5v598k3m1w"] as $commandID) {
    $cardActivatedAbilities[$commandID . ":0"] = function($player) use ($commandID) {
        $targets = array_merge(
            ZoneSearch("myField", ["ALLY"], cardSubtypes: ["BEAST"]),
            ZoneSearch("theirField", ["ALLY"], cardSubtypes: ["BEAST"])
        );
        if(empty($targets)) return;
        DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Choose_a_Beast_ally");
        DecisionQueueController::AddDecision($player, "CUSTOM", $commandID . ":0:CardActivated-1", 1);
    };
}

// Powerforged Burst is a Skill; its additional cost has already been paid.
$cardActivatedPrereqs["OWnWJNstCO:0"] = function($player, $mzID) {
    return !empty(array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"])));
};
$cardActivatedAbilities["OWnWJNstCO:0"] = function($player) {
    $targets = array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"]));
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Choose_target_unit");
    DecisionQueueController::AddDecision($player, "CUSTOM", "PowerforgedBurstTarget", 1);
};
$customDQHandlers["PowerforgedBurstTarget"] = function($player, $parts, $lastDecision) {
    if(!AllowGeneratedTargetResolution($player, $lastDecision, "PowerforgedBurstTarget")) return;
    PowerforgedBurstResolve($player, DecisionQueueController::GetVariable("mzID"), "", $lastDecision);
};

// Preparation has already been paid and X restored from this stack entry.
$cardActivatedAbilities["nt1lyk1dvd:0"] = function($player) {
    DecisionQueueController::AddDecision($player, "CUSTOM", "PenumbralWaltzEffect", 1);
};

$cardActivatedPrereqs["okZKM5DGRu:0"] = function($player, $mzID) {
    return !empty(SparklingAdornmentSources());
};

$cardActivatedPrereqs["vve1szpv86:0"] = function($player, $mzID) {
    return !empty(GetEffectStackActivationTargets($player));
};
$cardActivatedAbilities["vve1szpv86:0"] = function($player) {
    $amount = intval(DecisionQueueController::GetVariable("horticounterX")) + intval(DecisionQueueController::GetVariable("horticounterY"));
    QueueNegateActivation($player, [], "banish", $amount, "HorticounterTarget");
};

// Phase B: pay printed costs through the standard cost table, before effects resolve.
$activateAbilityAbilities["sdbzr5zs29:0"] = function($player) {
    $units = array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"]));
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $units), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "sdbzr5zs29:0:ActivateAbility-1", 1);
};
$activateAbilityAbilities["wuir99sx6q:0"] = function($player) {
    $paid = DecisionQueueController::GetVariable("GeniPaidCard") ?? "";
    DecisionQueueController::ClearVariable("GeniPaidCard");
    $floating = DecisionQueueController::GetVariable("GeniPaidFloating") === "YES";
    DecisionQueueController::ClearVariable("GeniPaidFloating");
    if($paid === "") return;
    if(PropertyContains(CardType($paid), "ALLY") && PropertyContains(CardSubtypes($paid), "AUTOMATON")) MZAddZone($player, "myField", "mu6gvnta6q");
    if($floating) {
        $allies = ZoneSearch("myField", ["ALLY"], cardSubtypes: ["AUTOMATON"]);
        if(!empty($allies)) {
            DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $allies), 1);
            DecisionQueueController::AddDecision($player, "CUSTOM", "GeniMechanistBuff", 1);
        }
    }
};

// A champion can acquire Ranger from a boon or another effective-property override.

$activateAbilityAbilities["l8PytM9CpG:0"] = function($player) {
    for($i = 0; $i < 2; ++$i) DecisionQueueController::AddDecision($player, "CUSTOM", "ReserveCard", 1);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", GADistanceTargets()), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "l8PytM9CpG:0:ActivateAbility-1", 1);
};
$activateAbilityPrereqs["l8PytM9CpG:0"] = function($player, $mzID, $abilityIndex) {
    $obj = GetZoneObject($mzID);
    return $obj !== null && !$obj->removed && GetCounterCount($obj, "l8PytM9CpG_used") < 3 && !empty(GADistanceTargets());
};
// Sharpshooter's Ranged lasts for this attack and does not itself grant distant.
$customDQHandlers["yakyp6odw3:0:OnAttack-1"] = function($player, $parts, $lastDecision) {
    if($lastDecision !== "YES") return;
    $mz = DecisionQueueController::GetVariable("mzID");
    $obj = GetZoneObject($mz);
    if($obj === null || $obj->removed) return;
    $aim = GetCounterCount($obj, "aim");
    RemoveCounters($player, $mz, "aim", $aim);
    AddTurnEffect($mz, "yakyp6odw3-RANGED_" . ($aim * 2));
};

// Powercell is sacrificed before its target is chosen. Read the event's known card identity,
// rather than the former field index, which can now refer to a different object.
$gaPowercellTarget = $customDQHandlers["qzzadf9q1v:0:ActivateAbility-1"];
$customDQHandlers["qzzadf9q1v:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) use ($gaPowercellTarget) {
    $gaPowercellTarget($player, $parts, $lastDecision);
    $target = is_string($lastDecision) && preg_match('/^(my|their|p[12])Field-[0-9]+$/', $lastDecision) ? GetZoneObject($lastDecision) : null;
    if($target === null || $target->removed || $target->Controller != $player || !PropertyContains(EffectiveCardType($target), "ALLY")) return;
    if(!IsClassBonusActive($player, ["TAMER"])) return;
    foreach(ZoneSearch("myField", ["ALLY"]) as $mz) {
        $bird = GetZoneObject($mz);
        if($bird->CardID === "84lj40Kyhv" && !HasNoAbilities($bird)) AddTurnEffect($mz, "TRAINED_BIRDROID_POWER");
    }
};

// Furnace Drone: dual-element Fire cards qualify for its mandatory graveyard cost.
$activateCardPrereqs["cbNF64gCsS:0"] = function($player, $mzID, $ignoreCost) {
    $eligible = 0;
    foreach(GetZone("myGraveyard") as $obj) {
        if($obj === null || $obj->removed) continue;
        if(PropertyContains(CardElement($obj->CardID), "FIRE") || PropertyContains(CardSubtypes($obj->CardID), "AUTOMATON")) ++$eligible;
    }
    if($eligible >= 3) return true;
    SetFlashMessage("Furnace Drone requires 3 fire and/or Automaton cards in your graveyard.");
    return false;
};

// On Enter negation belongs to the selected ally's identity, not every copy of its card.
foreach(["bdhjszsj2z:0:ActivateAbility-1" => 3, "bYrLVjKSCL:0:CardActivated-1" => -1] as $gaEnterNegateKey => $gaEnterNegatePay) {
    $customDQHandlers[$gaEnterNegateKey] = function($player, $parts, $lastDecision) use ($gaEnterNegateKey, $gaEnterNegatePay) {
        if(!AllowGeneratedTargetResolution($player, $lastDecision, $gaEnterNegateKey)) return;
        $targetObj = GetZoneObject($lastDecision);
        if($targetObj !== null && !$targetObj->removed && intval($targetObj->UniqueID ?? 0) > 0) {
            QueueNegateAllActivationsByCard($player, $targetObj->CardID, intval($targetObj->Controller), "default", $gaEnterNegatePay, intval($targetObj->UniqueID));
        }
        if($gaEnterNegatePay < 0) MayLoadIntoAetherwing($player, "bYrLVjKSCL");
    };
}
unset($gaEnterNegateKey, $gaEnterNegatePay);

// Veiled Dash permits zero, one or two distinct targets before its reveal choice.
$cardActivatedAbilities["08kuz07nk4:0"] = function($player) {
    $units = array_merge(ZoneSearch("myField", ["ALLY", "CHAMPION"]), ZoneSearch("theirField", ["ALLY", "CHAMPION"]));
    $units = array_values(array_filter(FilterSpellshroudTargets($units), function($mz) {
        $obj = GetZoneObject($mz);
        return $obj !== null && !$obj->removed;
    }));
    if(empty($units)) return;
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|" . min(2, count($units)) . "|" . implode("&", $units), 1,
        tooltip:"Choose_up_to_two_units_to_become_distant");
    DecisionQueueController::AddDecision($player, "CUSTOM", "VeiledDashTargets", 1);
};
$customDQHandlers["VeiledDashTargets"] = function($player, $parts, $lastDecision) {
    if(!AllowGeneratedTargetResolution($player, $lastDecision, "VeiledDashTargets")) return;
    $targets = [];
    foreach(array_unique(explode("&", strval($lastDecision))) as $mz) {
        if(!preg_match('/^(my|their)Field-[0-9]+$/', $mz)) continue;
        $obj = GetZoneObject($mz);
        if($obj === null || $obj->removed || (!PropertyContains(EffectiveCardType($obj), "ALLY") && !PropertyContains(EffectiveCardType($obj), "CHAMPION"))) continue;
        $targets[] = $mz;
        BecomeDistant($player, $mz);
        if(count($targets) === 2) break;
    }
    VeiledDashApplyPrevention($player, $targets[0] ?? null, $targets[1] ?? null);
};

// Slipstream resolves only the ally declared before payment, even after field compaction.
$cardActivatedAbilities["6ilt42sehq:0"] = function($player) {
    $uid = intval(DecisionQueueController::GetVariable("resolvedSlipstreamTargetUID") ?? 0);
    DecisionQueueController::ClearVariable("resolvedSlipstreamTargetUID");
    $targetMZ = FindFieldMzByUniqueID($uid);
    if(!in_array($targetMZ, GASlipstreamVaultTargets($player), true)) return;
    if(!AllowGeneratedTargetResolution($player, $targetMZ, "6ilt42sehq:0:CardActivated-1")) return;
    BecomeDistant($player, $targetMZ);
    // "Negate all card activations targeting it."
    GASlipstreamVaultNegateTargeting($player, intval(GetZoneObject($targetMZ)->UniqueID ?? 0));
};

// The Alice discount commits the activation to a Specter before payment.
$activationCostModifierAbilities["Dtr3jPRAFJ:0"] = function($player, $subjectObj, $currentValue, $sourceObj) {
    if($subjectObj->CardID !== "Dtr3jPRAFJ" || !IsAliceBonusActive($player)) return 0;
    if(($subjectObj->Counters["spectralSpecterOnly"] ?? null) === "NO") return 0;
    return empty(GASpectralHauntingTargets($player, true)) ? 0 : -4;
};
$cardActivatedAbilities["Dtr3jPRAFJ:0"] = function($player) {
    $specterOnly = DecisionQueueController::GetVariable("resolvedSpectralSpecterOnly") === "YES";
    DecisionQueueController::ClearVariable("resolvedSpectralSpecterOnly");
    $targets = $specterOnly
        ? GASpectralHauntingTargets($player, true)
        : GASpectralHauntingTargets($player);
    if(empty($targets)) return;
    DecisionQueueController::StoreVariable("spectralResolutionSpecterOnly", $specterOnly ? "YES" : "NO");
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1);
    DecisionQueueController::AddDecision($player, "CUSTOM", "SpectralResolveTarget", 1);
};
$customDQHandlers["SpectralResolveTarget"] = function($player, $parts, $lastDecision) {
    $specterOnly = DecisionQueueController::GetVariable("spectralResolutionSpecterOnly") === "YES";
    DecisionQueueController::ClearVariable("spectralResolutionSpecterOnly");
    $targets = $specterOnly
        ? GASpectralHauntingTargets($player, true)
        : GASpectralHauntingTargets($player);
    if(!in_array($lastDecision, $targets, true)) return;
    if(!AllowGeneratedTargetResolution($player, $lastDecision, "Dtr3jPRAFJ:0:CardActivated-1")) return;
    $newObj = MZMove($player, $lastDecision, "myField");
    if($newObj !== null) MakeEphemeral($newObj->GetMzID());
};

// Astral Seal's free activation is restricted to the declared banished-name category.
$activationCostModifierAbilities["e3aebjvwbc:0"] = function($player, $subjectObj, $currentValue, $sourceObj) {
    if($subjectObj->CardID !== "e3aebjvwbc" || !IsClassBonusActive($player, ["CLERIC"])) return 0;
    if(($subjectObj->Counters["astralBanishOnly"] ?? null) === "NO") return 0;
    return empty(GAAstralSealTargets($player, true)) ? 0 : -3;
};
$playCardAbilities["e3aebjvwbc:0"] = function($player) {
    $banishOnly = DecisionQueueController::GetVariable("astralBanishOnly") === "YES";
    DecisionQueueController::ClearVariable("astralBanishOnly");
    $names = strval(DecisionQueueController::GetVariable("astralBanishedNames") ?? "");
    DecisionQueueController::ClearVariable("astralBanishedNames");
    $targets = GAAstralSealTargets($player, $banishOnly, $names === "" ? [] : explode(",", $names));
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $targets), 1, "Choose_activation_to_negate");
    DecisionQueueController::AddDecision($player, "CUSTOM", "AstralResolveTarget|" . ($banishOnly ? "YES" : "NO") . "|" . $names, 1);
};
$customDQHandlers["AstralResolveTarget"] = function($player, $parts, $lastDecision) {
    if(count($parts) !== 2 || !in_array($parts[0], ["YES", "NO"], true)) return;
    if(!in_array($lastDecision, GAAstralSealTargets($player, $parts[0] === "YES", $parts[1] === "" ? [] : explode(",", $parts[1])), true)) return;
    NegateCardActivation($player, $lastDecision, "banish");
};

// Every existing generation-only body is gated independently of activation costs and targets.
// Mixed-effect bodies use GAGenerateCard so their other instructions still happen.
function GACanGenerateCard($player, $cardID) {
    $name = NormalizeNamedCardString(strval(CardName($cardID)));
    foreach(GetField($player) as $boon) {
        if($boon === null || $boon->removed || $boon->CardID !== "Klb4tguLek" || HasNoAbilities($boon)) continue;
        $chosen = strval($boon->Counters['luxeraName'] ?? "");
        if($chosen !== "" && NormalizeNamedCardString($chosen) === $name) return false;
    }
    return true;
}
function GAGenerateCard($player, $zone, $cardID) {
    return GACanGenerateCard($player, $cardID) ? MZAddZone($player, $zone, $cardID) : null;
}
foreach([
    [&$activateAbilityAbilities, "1ubrwubSQN:0", "ye7f7o5yut"],
    [&$enterAbilities, "k5Kid67duu:0", "ek5r5YlFQv"],
    [&$customDQHandlers, "41t71u4bzz:0:ActivateAbility-1", "vpmu6gvnta"],
    [&$customDQHandlers, "lVvU9lpJ3M:0:ActivateAbility-1", "ek5r5YlFQv"],
    [&$customDQHandlers, "lVvU9lpJ3M:0:ActivateAbility-2", "ek5r5YlFQv"],
    [&$onHitAbilities, "iq4d5vettc:0", "zrplywc08c"],
    [&$customDQHandlers, "6hjlgx72rf:0:OnKill-1", "zrplywc08c"],
] as &$gaGenerationSpec) {
    $gaGenerationTable = &$gaGenerationSpec[0];
    $gaGenerationKey = $gaGenerationSpec[1];
    $gaGeneratedID = $gaGenerationSpec[2];
    if(!isset($gaGenerationTable[$gaGenerationKey])) continue;
    $gaGenerationOriginal = $gaGenerationTable[$gaGenerationKey];
    $gaGenerationTable[$gaGenerationKey] = function($player, ...$args) use ($gaGenerationOriginal, $gaGeneratedID) {
        if(GACanGenerateCard($player, $gaGeneratedID)) return $gaGenerationOriginal($player, ...$args);
    };
}
unset($gaGenerationSpec, $gaGenerationTable, $gaGenerationKey, $gaGeneratedID, $gaGenerationOriginal);
foreach(["tf5f2n38g0", "GKKYGv6t3R", "fcfxhkqda6"] as $gaSeiryuuID) {
    $gaSeiryuuKey = $gaSeiryuuID . ":0:OnAttack-1";
    if(!isset($customDQHandlers[$gaSeiryuuKey])) continue;
    $gaSeiryuuOriginal = $customDQHandlers[$gaSeiryuuKey];
    $customDQHandlers[$gaSeiryuuKey] = function($player, $parts, $answer) use ($gaSeiryuuOriginal) {
        if($answer === "0" && !GACanGenerateCard($player, "pn9gQjV3Rb")) return;
        return $gaSeiryuuOriginal($player, $parts, $answer);
    };
}
unset($gaSeiryuuID, $gaSeiryuuKey, $gaSeiryuuOriginal);
foreach([1, 2] as $gaDiamondStep) {
    $customDQHandlers["NsnBhlVzTV:0:ActivateAbility-" . $gaDiamondStep] = function($player, $parts, $answer) use ($gaDiamondStep) {
        if($answer === "PASS" || $answer === "-" || $answer === "" || $answer === null) return;
        $choices = ZoneSearch("myMemory", cardSubtypes: ["SUITED"]);
        if(!in_array($answer, $choices, true)) return;
        DoRevealCard($player, $answer);
        GAGenerateCard($player, "myMemory", "ek5r5YlFQv");
        if($gaDiamondStep === 2) return;
        DecisionQueueController::StoreVariable("chosen1", $answer);
        $remaining = array_values(array_diff($choices, [$answer]));
        if(empty($remaining)) return;
        DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $remaining), 1);
        DecisionQueueController::AddDecision($player, "CUSTOM", "NsnBhlVzTV:0:ActivateAbility-2", 1);
    };
}
unset($gaDiamondStep);

// Dungeon Guide levels up a champion without playing the new top card.
$customDQHandlers["em6eEh9q8y:0:Enter-2"] = function($player, $parts, $answer) {
    if(!in_array($answer, GetLevelUpMaterialIndices($player, true), true)) return;
    DoMaterialize($player, $answer, false);
};
$enterAbilities["tkdky3sxao:0"] = function($player) {
    if(PlayerLevel($player) < 2) return;
    foreach(GetField($player === 1 ? 2 : 1) as $champion) {
        if($champion === null || $champion->removed || !PropertyContains(EffectiveCardType($champion), "CHAMPION")) continue;
        if(PropertyContains(EffectiveCardClasses($champion), "SPIRIT")) continue;
        if(intval($champion->Counters['gaTopUnplayed'] ?? 0) === 1) {
            LevelUpChampion($player);
            return;
        }
    }
};

// Firetuned's generated prereq must agree with the additional-cost chooser and preview.
$activateCardPrereqs["lzjmwuir99:0"] = function($player, $mzID, $ignoreCost) {
    if($ignoreCost) return true;
    $source = GetZoneObjectForPlayerPerspective($player, $mzID);
    foreach(GetHand($player) as $candidate) {
        if($candidate !== null && !$candidate->removed && $candidate !== $source && PropertyContains(CardElement($candidate->CardID), "FIRE")) return true;
    }
    return false;
};

$activateAbilityAbilities["81gvGHkuVb:0"] = function($player) { GALorraineAscendantResolve($player); };
$activateAbilityPrereqs["81gvGHkuVb:0"] = function($player, $mzID, $abilityIndex) {
    return GALorraineAscendantCanActivate(GetZoneObject($mzID));
};
GAApplyActivateAbilityCountOverrides();

$cardActivatedAbilities["dt4YncETqE:0"] = function($player) { GAPutFieldGroup($player, ["bd7ozuj68m", "soporhlq2k"]); };
$cardActivatedAbilities["m6lGw6bQtb:0"] = function($player) { GAPutFieldGroup($player, ["qzzadf9q1v", "qzzadf9q1v"], true); };

$onAttackAbilities["5du8f077ua:0"] = function($player) {
    $eligible = false;
    foreach(GetField($player) as $human) {
        if($human === null || $human->removed || !PropertyContains(EffectiveCardType($human), "ALLY")
            || !PropertyContains(EffectiveCardType($human), "UNIQUE") || !PropertyContains(EffectiveCardSubtypes($human), "HUMAN")) continue;
        if(GAElementMatchesAny(EffectiveCardElement($human), ["FIRE", "TERA"])) { $eligible = true; break; }
    }
    if(!$eligible || empty(GetHand($player))) return;
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip:"Discard_a_card_to_draw_a_card?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "RedHareDiscardDraw", 1);
};

// Freeze the preparation payer and attacking recipient before any On Attack answer.
$onAttackAbilities["spijrps4ny:0"] = function($player) {
    GABeginPrepAttackChoice($player, "spijrps4ny", 1);
};
$onAttackAbilities["o4h8cfo21a:0"] = function($player) {
    if(!IsClassBonusActive($player, ["ASSASSIN"])) return;
    GABeginPrepAttackChoice($player, "o4h8cfo21a", 3);
};

// Vorpal Sword (PIcB5KuuMd): "[Merlin Bonus] [REST], Banish a card from your material deck: CARDNAME gains spellshroud until end of turn."
// The generated body banished $mat[0] without asking and nothing paid the REST. Both costs now live in GAFieldAbilityCostTable() (rest + banishMaterial, Merlin gate);
// the body only grants the effect.
$activateAbilityAbilities["PIcB5KuuMd:0"] = function($player) { //Spellshroud
  $mzID = DecisionQueueController::GetVariable("mzID");
  AddTurnEffect($mzID, "SPELLSHROUD");
};

// Bloom: Autumn's Fall (pebu7agtcd): "[Element Bonus] (2), Discard this card from your hand: Empower 2. Draw a card."
// The generated rows had no hand ability for it; the count comes from GAHandActivateAbilityCountOverrides(), the cost from ActivatedAbilityCost().
// Summer's Glow (a708z5ethq) and Winter's Chill (b4jvyh23y1) print the same hand ability and had no rows either.
foreach(["pebu7agtcd", "a708z5ethq", "b4jvyh23y1"] as $bloomSeasonID) {
    $handActivatedAbilityAbilities[$bloomSeasonID . ":0"] = function($player) use ($bloomSeasonID) {
        Empower($player, 2, $bloomSeasonID);
        Draw($player, 1);
    };
    $handActivatedAbilityPrereqs[$bloomSeasonID . ":0"] = function($player, $mzID, $abilityIndex) use ($bloomSeasonID) {
        return IsElementBonusActive($player, $bloomSeasonID);
    };
}

// Cometary Vantage (y4c89re0bd): "Choose one. If your champion is distant, choose two instead-- Glimpse 4 / Draw a card / If a unit is attacking, each Aethercharge card in that attacker's intent
// gains 'On Hit: Load this card into an Aetherwing weapon you control.'" The generated body skipped the third mode and picked the first two whenever the champion was distant.
$cardActivatedAbilities["y4c89re0bd:0"] = function($player) {
    $champMZ = FindChampionMZ($player);
    $champObj = ($champMZ !== null) ? GetZoneObject($champMZ) : null;
    $picks = ($champObj !== null && IsDistant($champObj)) ? 2 : 1;
    DecisionQueueController::AddDecision($player, "MZMODAL", $picks . "|" . $picks . "|A:_Glimpse_4&B:_Draw_a_card&C:_Aethercharge_cards_in_the_attacker's_intent_gain_On_Hit_load", 1, "Cometary_Vantage");
    DecisionQueueController::AddDecision($player, "CUSTOM", "CometaryVantageModes", 1);
};
$customDQHandlers["CometaryVantageModes"] = function($player, $parts, $lastDecision) {
    $picked = array_map('intval', array_filter(explode(",", strval($lastDecision)), fn($x) => $x !== ""));
    sort($picked);
    foreach($picked as $mode) {
        if($mode === 0) Glimpse($player, 4);
        else if($mode === 1) Draw($player, 1);
        else if($mode === 2) {
            $attackerMZ = GetCombatAttackerMZ();
            $attackerObj = $attackerMZ === null ? null : GetZoneObject($attackerMZ);
            if($attackerObj === null || $attackerObj->removed) continue;
            foreach(GetIntentCards(intval($attackerObj->Controller)) as $intentMZ) {
                $intentObj = GetZoneObject($intentMZ);
                if($intentObj === null || $intentObj->removed || !PropertyContains(CardSubtypes($intentObj->CardID), "AETHERCHARGE")) continue;
                AddTurnEffect($intentMZ, "y4c89re0bd_LOAD_ON_HIT");
            }
        }
    }
};
// "On Hit: Load this card into an Aetherwing weapon you control." for an intent card marked above (called from OnHitTrigger()). The card leaves the intent zone right away: combat
// cleanup would otherwise send it to the graveyard before a queued decision could load it.
function GAQueueIntentAetherwingLoad($player, $intentMZ) {
    $wings = GetAetherwingWeapons($player);
    if(empty($wings)) return;
    $intentObj = GetZoneObject($intentMZ);
    if($intentObj === null || $intentObj->removed) return;
    $loadedCardID = $intentObj->CardID;
    $intentObj->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    $wings = GetAetherwingWeapons($player);
    if(count($wings) === 1) { GALoadCardIDIntoAetherwing($wings[0], $loadedCardID); return; }
    DecisionQueueController::StoreVariable("CometaryVantageLoadedCard", $loadedCardID);
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $wings), 1, tooltip:"Load_into_an_Aetherwing_weapon");
    DecisionQueueController::AddDecision($player, "CUSTOM", "CometaryVantageLoad", 1);
}
function GALoadCardIDIntoAetherwing($wingMZ, $cardID) {
    $wingObj = &GetZoneObject($wingMZ);
    if($wingObj === null) return;
    if(!is_array($wingObj->Subcards)) $wingObj->Subcards = [];
    $wingObj->Subcards[] = $cardID;
}
$customDQHandlers["CometaryVantageLoad"] = function($player, $parts, $lastDecision) {
    $cardID = DecisionQueueController::GetVariable("CometaryVantageLoadedCard");
    if($cardID === null || $cardID === "" || $lastDecision === "-" || $lastDecision === "" || $lastDecision === "PASS") return;
    GALoadCardIDIntoAetherwing($lastDecision, $cardID);
};

// Spirelle, Schwartz Queen (p2n1953som): "(2), [REST], Reveal a card banished by Spirelle with reserve cost X: Negate each activation of cards with reserve cost X. Put each card
// negated this way and each card banished by Spirelle into their owner's memories. Then trigger each of Spirelle's on enter abilities."
// The generated body offered EVERY banished card (omens, Tristan's cards, ...) and only moved the revealed one to memory; the cards Spirelle banished carry a spirelle_banished counter.
function GASpirelleBanishedCards($player) {
    $found = [];
    $banish = GetBanish($player);
    for($bi = 0; $bi < count($banish); ++$bi) {
        if(!$banish[$bi]->removed && GetCounterCount($banish[$bi], "spirelle_banished") > 0) $found[] = "myBanish-" . $bi;
    }
    return $found;
}
$activateAbilityAbilities["p2n1953som:0"] = function($player) {
    $banished = GASpirelleBanishedCards($player);
    if(empty($banished)) return;
    DecisionQueueController::AddDecision($player, "MZCHOOSE", implode("&", $banished), 1, "Reveal_a_card_banished_by_Spirelle");
    DecisionQueueController::AddDecision($player, "CUSTOM", "p2n1953som:0:ActivateAbility-1", 1);
};
$activateAbilityPrereqs["p2n1953som:0"] = function($player, $mzID, $abilityIndex) {
    $sourceObject = GetZoneObject($mzID);
    if($sourceObject === null) return false;
    return $sourceObject->Status == 2 && count(GetHand($player)) >= 2 && !empty(GASpirelleBanishedCards($player));
};
$customDQHandlers["p2n1953som:0:ActivateAbility-1"] = function($player, $parts, $lastDecision) {
    $mzID = DecisionQueueController::GetVariable("mzID");
    $chosen = $lastDecision;
    $chosenObj = GetZoneObject($chosen);
    if($chosenObj === null || $chosenObj->removed || GetCounterCount($chosenObj, "spirelle_banished") <= 0) return;
    DoRevealCard($player, $chosen);
    $cost = intval(CardCost_reserve($chosenObj->CardID));
    $targets = GetEffectStackActivationTargets($player, ["reserveCost" => $cost]);
    for($i = count($targets) - 1; $i >= 0; --$i) {
        NegateCardActivation($player, $targets[$i], "memory");
    }
    // Every card banished by Spirelle (negating may have shifted the banish zone, so look them up again).
    $banished = GASpirelleBanishedCards($player);
    for($i = count($banished) - 1; $i >= 0; --$i) {
        MZMove($player, $banished[$i], "myMemory");
    }
    DecisionQueueController::CleanupRemovedCards();
    OnEnter($player, $mzID);
};

// KEEP LAST: wrap the final closures. Anything defined after these calls would escape the class-bonus source wrappers and the printed-cost prereqs.
// ---------------------------------------------------------------------------------------------
// (The tables live in this file's scope -- see the header comment: the file is included from inside EngineLoadRootRuntime() -- so they are handed over by reference rather than read from $GLOBALS.)
$gaClassBonusTables = [&$customDQHandlers, &$cardActivatedAbilities, &$enterAbilities, &$activateAbilityAbilities, &$activateAbilityPrereqs, &$activationCostModifierAbilities, &$onAttackAbilities,
    &$onHitAbilities, &$onKillAbilities, &$allyDestroyedAbilities, &$activateCardAbilities, &$activateCardPrereqs, &$cardActivatedPrereqs, &$memoryCostModifierAbilities, &$leaveFieldAbilities,
    &$playCardAbilities, &$onFosterAbilities, &$enterPrereqs, &$reserveCostModifierAbilities, &$dealDamageAbilities, &$onAttackPrereqs, &$onBanishAbilities, &$restCardAbilities, &$revealAbilities];
GAInstallClassBonusSourceWrappers($gaClassBonusTables);
unset($gaClassBonusTables);

// Printed-cost prereqs for the abilities in GAFieldAbilityCostTable() (Custom/GameLogic.php): wraps the final $activateAbilityPrereqs closures.
GAInstallFieldAbilityCostPrereqs($activateAbilityPrereqs);

// Total Whiteout (O4mD40xpbY): "Rested non-champion objects you don't control lose all activated abilities." CanActivateAbility() is the single gate, but most abilities have no prereq closure at all, so one is
// installed for every printed activated ability: it refuses while a Total Whiteout of the other player is on the field and the object is a rested non-champion.
function GATotalWhiteoutBlocks($player, $mzID) {
    $obj = GetZoneObject($mzID);
    if($obj === null || $obj->removed || !isset($obj->Controller) || ($obj->Status ?? 2) != 1) return false;
    if(PropertyContains(EffectiveCardType($obj), "CHAMPION")) return false;
    foreach(GetField($obj->Controller == 1 ? 2 : 1) as $fieldObj) {
        if($fieldObj !== null && !$fieldObj->removed && $fieldObj->CardID === "O4mD40xpbY" && !HasNoAbilities($fieldObj)) return true;
    }
    return false;
}
foreach($CardActivateAbilityCountData as $gaWhiteoutCardID => $gaWhiteoutCount) {
    for($gaWhiteoutIdx = 0; $gaWhiteoutIdx < $gaWhiteoutCount; ++$gaWhiteoutIdx) {
        $gaWhiteoutKey = $gaWhiteoutCardID . ":" . $gaWhiteoutIdx;
        $gaWhiteoutOriginal = $activateAbilityPrereqs[$gaWhiteoutKey] ?? null;
        $activateAbilityPrereqs[$gaWhiteoutKey] = function($player, $mzID, $abilityIndex) use ($gaWhiteoutOriginal) {
            if(GATotalWhiteoutBlocks($player, $mzID)) return false;
            return $gaWhiteoutOriginal === null ? true : $gaWhiteoutOriginal($player, $mzID, $abilityIndex);
        };
    }
}

// Slime Party (AJeuUDTPmV): "costs 1 less for each different element Slime ally you control" -- Silvie bonus. Generated body keyed a set on the
// whole element string, so two Exalted/Fire Slimes counted once; each Slime now counts when it has an element no other counted Slime uses.
$activationCostModifierAbilities["AJeuUDTPmV:0"] = function($player, $subjectObj, $currentValue, $sourceObj) {
  if($subjectObj->CardID !== "AJeuUDTPmV") return 0;
  $isSilvie = ChampionHasInLineage($player, "GKEpAulogu")
      || ChampionHasInLineage($player, "mdwbkuhtjm")
      || ChampionHasInLineage($player, "nllCALIXDT")
      || ChampionHasInLineage($player, "RfPP8h16Wv")
      || ChampionHasInLineage($player, "ZR8tnLruR6");
  if(!$isSilvie) return 0;
  $slimeIDs = [];
  foreach(ZoneSearch("myField", ["ALLY"], cardSubtypes: ["SLIME"]) as $mz) {
      $obj = GetZoneObject($mz);
      if($obj === null || $obj->removed) continue;
      $slimeIDs[] = $obj->CardID;
  }
  $discount = GAMaxDistinctElementCount($slimeIDs);
  return max(0, intval($currentValue) - $discount) - intval($currentValue);
};
