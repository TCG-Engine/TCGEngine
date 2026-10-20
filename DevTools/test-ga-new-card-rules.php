<?php
// Cross-cutting rules of the previously blank card definitions, through generated dispatch.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_new_card_rules_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function ncCheck($condition, $label) {
    global $checks;
    if(!$condition) throw new RuntimeException($label);
    ++$checks; echo "PASS $label\n";
}
function ncReset() {
    global $gameDir, $playerID;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/'); $playerID = 1;
    foreach([1,2] as $p) {
        $q = &GetDecisionQueue($p); $q = [];
        $effects = &GetGlobalEffects($p); $effects = [];
        $field = &GetField($p); $field = [];
    }
}
try {
    ncReset();
    foreach([1,2] as $seat) {
        SetTurnPlayer($seat);
        ncCheck(EngineTransitionOverride('WU','AUTO') === null, "seat $seat ordinary phase order");
    }
    $cardActivatedAbilities['n2wtz6ql0a:0'](1);
    AddGlobalEffects(1, 'IGNORE_ELEMENT_NAME:Arcane%20Sight');
    ExpireEffects(true);
    ncCheck(GlobalEffectCount(1, 'TIME_DISTORTED') === 1, 'Distorted time survives end-of-turn cleanup');
    ncCheck(CanPlayerUseCardElement(1, CardNameToID('Arcane Sight'), false, false), 'Solomon name permission survives cleanup without Solomon');
    foreach([1,2] as $seat) {
        SetTurnPlayer($seat);
        SetCurrentPhase('WU'); ncCheck(EvaluateTransition('AUTO') === 'BREC', "seat $seat skips the first materialize phase");
        SetCurrentPhase('MAIN'); ncCheck(EvaluateTransition('PASS') === 'AMAT', "seat $seat gets post-main materialize");
        SetCurrentPhase('AMAT'); ncCheck(EvaluateTransition('AUTO') === 'BEND', "seat $seat proceeds to end after additional materialize");
    }
    ncReset();
    $champ = MZAddZone(1, 'myField', 'gOus3YnrRq');
    $champ->Counters = ['level' => 2]; $champ->Damage = 15;
    $prism = MZAddZone(1, 'myField', 'x3ljhn5iu9');
    ncCheck(CanPlayerMeetCardElementRequirements(1, 'ARCANE') && CanPlayerMeetCardElementRequirements(1, 'UMBRA'), 'Perseverance enables advanced elements at both thresholds');
    $champ->Damage = 14;
    ncCheck(!CanPlayerMeetCardElementRequirements(1, 'ARCANE'), 'Perseverance stops below Damage 15');
    $champ->Damage = 15; $champ->Counters = ['level' => 1];
    ncCheck(!CanPlayerMeetCardElementRequirements(1, 'ARCANE'), 'Perseverance stops below Level 2');
    $champ->Counters = ['level' => 2]; AddTurnEffect($prism->GetMzID(), 'NO_ABILITIES');
    ncCheck(!CanPlayerMeetCardElementRequirements(1, 'ARCANE'), 'Suppressed Perseverance grants no elements');
    ncReset();
    $boon = MZAddZone(1, 'myField', 'OSsRmNoETv');
    $sword = MZAddZone(1, 'myField', CardNameToID('Training Sword'));
    ncCheck(ObjectCurrentPower($sword) === CardPower($sword->CardID)+1, 'Sword Saint gives a Sword one power');
    ncCheck(GetCounterCount($sword,'durability') === CardDurability($sword->CardID)+1, 'Sword Saint adds entry durability');
    AddTurnEffect($boon->GetMzID(), 'NO_ABILITIES');
    ncCheck(ObjectCurrentPower($sword) === CardPower($sword->CardID), 'Suppression removes continuous power');
    ncCheck(GetCounterCount($sword,'durability') === CardDurability($sword->CardID)+1, 'Suppression does not undo placed durability');
    ncReset();
    MZAddZone(1, 'myField', 'woez6MYh46');
    $barrier = MZAddZone(1, 'myField', 'WyNvyDHFdB');
    $barrier->Counters = ['durability' => 3];
    $deck = &GetDeck(1); $deck = [];
    $grave = &GetGraveyard(1); $grave = [];
    for($i=0; $i<6; ++$i) AddDeck(1, CardID: 'em6eEh9q8y');
    RemoveCounters(1, $barrier->GetMzID(), 'durability', 5);
    ncCheck(count(array_filter(GetGraveyard(1), fn($o)=>!$o->removed)) === 3, 'Permeation mills actual counters removed, not the requested excess');
    RemoveCounters(1, $barrier->GetMzID(), 'durability', 1);
    ncCheck(count(array_filter(GetGraveyard(1), fn($o)=>!$o->removed)) === 3, 'Removing no durability mills nothing');
    $other = MZAddZone(2, 'myField', 'WyNvyDHFdB'); $other->Counters = ['durability'=>2];
    RemoveCounters(2, $other->GetMzID(), 'durability', 1);
    ncCheck(count(array_filter(GetGraveyard(1), fn($o)=>!$o->removed)) === 3, 'Opponent Barrier does not trigger your Permeation');
    ncReset();
    MZAddZone(1, 'myField', CardNameToID('Spirit of Fire'));
    ncCheck(!CanPlayerUseCardElement(1, 'bzwj7ztr78', false, false), 'Harness requires its element without a Shenju');
    $shenju = MZAddZone(1, 'myField', 'tf5f2n38g0');
    ncCheck(CanPlayerUseCardElement(1, 'bzwj7ztr78', false, false), 'Arcane Shenju permits Harness without Arcane lineage');
    ApplyPersistentOverride($shenju->GetMzID(), ['element'=>'WATER']);
    ncCheck(!CanPlayerUseCardElement(1, 'bzwj7ztr78', false, false), 'A non-Arcane Shenju does not permit Harness');
    ncReset();
    MZAddZone(1, 'myField', CardNameToID('Spirit of Fire'));
    $hand = &GetHand(1); $hand = [];
    $horse = MZAddZone(1, 'myHand', CardNameToID('Trusty Steed'));
    for($i=0; $i<6; ++$i) MZAddZone(1, 'myHand', 'em6eEh9q8y');
    AddGlobalEffects(1, '8iNWBAkqkS');
    ncCheck(in_array('myHand-0', GetPlayableFastCards(1), true), 'Spurred Gallop offers the next Horse at fast speed');
    ncCheck(CalculateActivationReserveCost(1, $horse, true) === 2 && GlobalEffectCount(1, '8iNWBAkqkS') === 1, 'Cost previews preserve the Gallop grant');
    ncCheck(CalculateActivationReserveCost(1, $horse, false) === 2 && GlobalEffectCount(1, '8iNWBAkqkS') === 0, 'The real Horse cost consumes the Gallop grant');
    ncCheck(!in_array('myHand-0', GetPlayableFastCards(1), true), 'Fast permission is consumed together with the discount');
    ncReset();
    MZAddZone(1, 'myField', 'dnqUeoEgJi');
    $automaton = MZAddZone(1, 'myField', '84lj40Kyhv');
    DoAllyDestroyed(1, $automaton->GetMzID());
    ncCheck(count(ZoneSearch('myField', ['ITEM'], cardSubtypes:['POWERCELL'])) === 1, 'Rosen summons one Powercell for a friendly Automaton death');
    DecisionQueueController::CleanupRemovedCards();
    $other = MZAddZone(2, 'myField', '84lj40Kyhv');
    DoAllyDestroyed(1, $other->GetMzID());
    ncCheck(count(ZoneSearch('myField', ['ITEM'], cardSubtypes:['POWERCELL'])) === 1, 'Rosen ignores opposing Automaton deaths');
    ncReset();
    $champ = MZAddZone(1, 'myField', CardNameToID('Spirit of Fire'));
    $champ->Status = 1;
    AddGlobalEffects(1, 'Z1L77xhMcC');
    ExpireEffects(true);
    ncCheck(GlobalEffectCount(1, 'Z1L77xhMcC') === 1, 'Constellary attack grant survives turn cleanup');
    $deck = &GetDeck(1); $deck = [];
    for($i=0; $i<6; ++$i) MZAddZone(1, 'myDeck', 'em6eEh9q8y');
    $intent = &GetIntent(1); $intent = [];
    for($i=0; $i<2; ++$i) MZAddZone(1, 'myIntent', CardNameToID('Clean Cut'));
    DispatchMacroListeners('OnAttack', 1, ['mzID'=>$champ->GetMzID()], ['GlobalEffects']);
    ncCheck($champ->Status === 1, 'Constellary does not wake with only two intent cards');
    MZAddZone(1, 'myIntent', CardNameToID('Clean Cut'));
    DispatchMacroListeners('OnAttack', 1, ['mzID'=>$champ->GetMzID()], ['GlobalEffects']);
    ncCheck($champ->Status === 2 && intval(DecisionQueueController::GetVariable('glimpseCount')) === 4, 'Constellary wakes champion and glimpses four with three intent cards');
    foreach([1,2] as $seat) {
        ncReset();
        $champ = MZAddZone($seat, 'myField', CardNameToID('Spirit of Fire'));
        $champ->Damage = 10;
        $cardActivatedAbilities['l61ubi93jx:0']($seat);
        OnDealDamage(1, 'hiN2g99FLF', $champ->GetMzID(), 3);
        ncCheck($champ->Damage === 7, "Seize Fate replaces ordinary damage for seat $seat");
        DealUnpreventableDamage(1, 'hiN2g99FLF', $champ->GetMzID(), 2);
        ncCheck($champ->Damage === 5, "Seize Fate replaces unpreventable damage for seat $seat");
        DealUnpreventableDamage(1, 'hiN2g99FLF', $champ->GetMzID(), 8);
        ncCheck($champ->removed && count(GetBanish($seat)) > 0, "Seize Fate banishes champion at zero damage for seat $seat");
    }
    ncReset();
    $champ = MZAddZone(1, 'myField', CardNameToID('Spirit of Fire'));
    $champ->Counters['level'] = 3;
    $grave = &GetGraveyard(1); $grave = [];
    $deck = &GetDeck(1); $deck = [];
    MZAddZone(1, 'myGraveyard', 'C7zFV2K7bL');
    MZAddZone(1, 'myGraveyard', 'C7zFV2K7bL');
    MZAddZone(1, 'myDeck', 'em6eEh9q8y');
    MillCards(1, 'myDeck', 'myGraveyard', 1);
    $returned = array_values(array_filter(ZoneSearch('myField'), fn($mz) => GetZoneObject($mz)->CardID === 'C7zFV2K7bL'));
    ncCheck(count($returned) === 2 && GetZoneObject($returned[0])->Status === 1, 'Mistbound returns rested when another ally is milled at level three');
    foreach(['self', 'non-ally', 'level-two', 'opponent'] as $case) {
        ncReset();
        $champ = MZAddZone(1, 'myField', CardNameToID('Spirit of Fire'));
        $champ->Counters['level'] = $case === 'level-two' ? 2 : 3;
        $grave = &GetGraveyard(1); $grave = [];
        $deck = &GetDeck(1); $deck = [];
        if($case !== 'self') MZAddZone(1, 'myGraveyard', 'C7zFV2K7bL');
        $id = $case === 'self' ? 'C7zFV2K7bL' : ($case === 'non-ally' ? 'hiN2g99FLF' : 'em6eEh9q8y');
        if($case === 'opponent') {
            $otherDeck = &GetDeck(2); $otherDeck = [];
            MZAddZone(2, 'myDeck', $id);
            MillCards(1, 'theirDeck', 'theirGraveyard', 1);
        } else {
            MZAddZone(1, 'myDeck', $id);
            MillCards(1, 'myDeck', 'myGraveyard', 1);
        }
        ncCheck(count(ZoneSearch('myField', ['ALLY'])) === 0, "Mistbound ignores $case mill");
    }
    foreach([1,2] as $seat) {
        ncReset();
        $memory = &GetMemory($seat); $memory = [];
        $banish = &GetBanish($seat); $banish = [];
        MZAddZone($seat, 'myMemory', 'foy5mdrVCR');
        $moved = MZMove($seat, 'myMemory-0', 'myBanish');
        ncCheck(GetCounterCount($moved, 'charge') === 1, "Auravolt gains charge when banished from memory for seat $seat");
        ncCheck(EvaluateStaticPropertyModifier('foy5mdrVCR', $seat, 'activateFromBanish', $moved, $moved) === 1, 'Charge grants Auravolt banishment activation');
        $plain = MZAddZone($seat, 'myBanish', 'foy5mdrVCR');
        ncCheck(EvaluateStaticPropertyModifier('foy5mdrVCR', $seat, 'activateFromBanish', $plain, $plain) === 0, 'Uncharged Auravolt has no banishment permission');
    }
    ncReset();
    $champ = MZAddZone(1, 'myField', CardNameToID('Spirit of Fire'));
    $boon = MZAddZone(1, 'myField', 'wc8IEEJUBL');
    $ally = MZAddZone(1, 'myField', 'em6eEh9q8y');
    $link = MZAddZone(1, 'myField', 'c8ljyevpmu');
    $ally->Subcards = ['c8ljyevpmu'];
    $link->Counters['linkedToAlly'] = $ally->CardID;
    ncCheck(ObjectCurrentPower($ally) === 2, 'Connection grants one power for one linked object');
    $ally->Subcards[] = 'c8ljyevpmu';
    ncCheck(ObjectCurrentPower($ally) === 2, 'Connection counts each linked object once');
    ncCheck(GAGrowingBoonReserve(1, $boon->GetMzID()) === 2, 'Connection starts at two reserve');
    GARecordGrowingBoonActivation(1, 'wc8IEEJUBL');
    ncCheck(GAGrowingBoonReserve(1, $boon->GetMzID()) === 3, 'Connection next activation costs three');
    ExpireEffects(true);
    ncCheck(GAGrowingBoonReserve(1, $boon->GetMzID()) === 3, 'Connection increasing cost persists across turns');
    $handBoon = MZAddZone(1, 'myHand', 'wc8IEEJUBL');
    ncCheck(!CanActivateCard(1, 'myHand-' . $handBoon->mzIndex, false), 'Connection rejects champion below base level two');
    $champ->CardID = CardNameToID('Lorraine, Blademaster');
    ncCheck(CanActivateCard(1, 'myHand-' . $handBoon->mzIndex, false), 'Connection permits champion at base level two');
    ncReset();
    $weapon = MZAddZone(1, 'myField', CardNameToID('Training Sword'));
    $sameName = MZAddZone(1, 'myField', CardNameToID('Training Sword'));
    $chill = MZAddZone(2, 'myField', '7wX0tZmhYb');
    CreateWeaponLink(2, $chill->GetMzID(), $weapon->GetMzID());
    ncCheck(HasNoAbilities($weapon) && !HasNoAbilities($sameName), 'Lakereaving suppresses only the linked weapon identity');
    ncCheck(!in_array($weapon->GetMzID(), GetAvailableWeapons(1), true), 'Lakereaving excludes linked weapon from attack choices');
    $chill->TurnEffects[] = 'NO_ABILITIES';
    ncCheck(!HasNoAbilities($weapon) && in_array($weapon->GetMzID(), GetAvailableWeapons(1), true), 'Suppressing Chill restores linked weapon abilities and attacks');
    $chill->TurnEffects = [];
    OnLeaveField(1, $weapon->GetMzID());
    ncCheck($chill->removed, 'Breaking the opposing weapon link sacrifices Chill');
    ncReset();
    $domain = MZAddZone(2, 'myField', 'Ot9z4Oh3jN');
    $norm = MZAddZone(1, 'myHand', 'em6eEh9q8y');
    $advanced = MZAddZone(1, 'myHand', 'hiN2g99FLF');
    ncCheck(ActivationBlockedBeforeAnnounce(1, $norm, false, true, false), 'Tricastles blocks non-advanced cards for opponent');
    ncCheck(!ActivationBlockedBeforeAnnounce(1, $advanced, false, true, false), 'Tricastles permits advanced cards');
    ncCheck(GAHasPublicActivatedAbility($domain), 'Tricastles offers an ability to its opponent');
    $domain->BuildIndex();
    $buttons = json_decode(GetActivateAbilityButtonStates($domain), true);
    ncCheck(!empty($buttons[0]['enabled']), 'Opponent can use the public Tricastles button');

    $domain->TurnEffects[] = 'NO_ABILITIES';
    ncCheck(!ActivationBlockedBeforeAnnounce(1, $norm, false, true, false), 'Suppressed Tricastles stops restricting cards');
    ncCheck(!GAHasPublicActivatedAbility($domain), 'Suppressed Tricastles cannot be activated again');
    ncReset();
    $boon = MZAddZone(1, 'myField', 'Ss991TWKR9');
    $elysian = MZAddZone(1, 'myField', 'RN7ueRDijA'); $elysian->Status = 2;
    $other = MZAddZone(2, 'myField', 'RN7ueRDijA'); $other->Status = 2;
    ncCheck(count(GADatabaseGrantedAbilities($elysian)) === 1, 'Elysian receives database-authored REST ability');
    ncCheck(GADatabaseGrantedAbilities($other) === [], 'Elysian grant does not affect opponent');
    $elysian->Status = 1;
    ncCheck(GADatabaseGrantedAbilities($elysian) === [], 'Rested Elysian cannot pay the granted REST cost');
    $elysian->Status = 2; $boon->TurnEffects[] = 'NO_ABILITIES';
    ncCheck(GADatabaseGrantedAbilities($elysian) === [], 'Suppressed Elysian boon stops granting abilities');
    ncReset();
    MZAddZone(1, 'myField', 'gOus3YnrRq');
    AddGlobalEffects(1, 'IGNORE_ELEMENT_NAME:Arcane%20Sight');
    ncCheck(CanPlayerUseCardElement(1, CardNameToID('Arcane Sight'), false, false), 'Solomon permits activation of a named Sight');
    ncCheck(!CanPlayerUseCardElement(1, CardNameToID('Arcane Sight'), false, false, false), 'Activation-only element bypass does not permit materialization');
    echo "$checks checks passed\n";
} finally {
    foreach(glob($gameDir.'/*') ?: [] as $file) if(is_file($file)) unlink($file);
    @rmdir($gameDir);
}
