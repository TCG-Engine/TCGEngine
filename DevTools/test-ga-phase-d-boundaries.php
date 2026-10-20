<?php
// Retained loaded-state boundary checks supplement, but do not replace, gameplay fixtures.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_d_boundaries_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function phaseDCheck($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
    echo "PASS $label\n";
}
function phaseDLoad($slug) {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('Tests/Integration/GrandArchiveSim/' . $slug . '/initial_gamestate.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
}
function phaseDHandCard($id) {
    foreach (GetHand(1) as $obj) if (!$obj->removed && $obj->CardID === $id) return $obj;
    throw new RuntimeException('Missing hand card ' . $id);
}
function phaseDSnapshot() {
    $state = [];
    foreach (GetAllZones() as $zone) $state[$zone] = GetZone($zone);
    return serialize([$state, GetDecisionQueueVariables(), GetMacroTurnIndex(), GetMacroGameIndex(), GetFlashMessage()]);
}
try {
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        foreach([['MAIN', $owner, false], ['MAT', $owner, true], ['MAT', 3 - $owner, false]] as [$phase, $turn, $allowed]) {
            phaseDLoad('eternal-magistrate-material-plain');
            $playerID = $viewer;
            foreach([1, 2] as $seat) foreach(GetField($seat) as $obj) if($obj->CardID === 'taug52u81v') $obj->removed = true;
            $magistrate = AddField(3 - $owner, CardID: 'taug52u81v', Owner: 3 - $owner, Controller: 3 - $owner);
            $magistrate->TurnEffects = ['IMBUED'];
            $source = AddMaterial($owner, 'em6eEh9q8y');
            $index = $source->mzIndex;
            SetCurrentPhase($phase);
            SetTurnPlayer($turn);
            phaseDCheck(GAMaterialCanLeave($source) === $allowed, "Magistrate owner $owner viewer $viewer phase $phase turn $turn legality");
            $before = phaseDSnapshot();
            $moved = MZMove($owner, 'myMaterial-' . $index, 'myHand');
            phaseDCheck(($moved !== null) === $allowed && boolval($source->removed) === $allowed, "Magistrate owner $owner viewer $viewer move result");
            if(!$allowed) {
                phaseDCheck(phaseDSnapshot() === $before, 'Veto preserves source, destination, queues and trackers');
                phaseDCheck($source->Remove() === false && !$source->removed, 'Direct Remove obeys the same departure veto');
                $magistrate->TurnEffects[] = 'NO_ABILITIES';
                phaseDCheck(GAMaterialCanLeave($source), 'Suppression disables the restriction');
                $magistrate->TurnEffects = [];
                phaseDCheck(GAMaterialCanLeave($source), 'Unimbued Magistrate does not restrict departure');
                $magistrate->TurnEffects = ['IMBUED'];
                $magistrate->removed = true;
                phaseDCheck(GAMaterialCanLeave($source), 'Removed Magistrate does not restrict departure');
            }
        }
    }
    foreach(['myHand', 'myMemory', 'myBanish', 'myGraveyard', 'myField', 'myMaterial'] as $destination) {
        phaseDLoad('eternal-magistrate-material-imbued');
        $source = GetZoneObject('myMaterial-4');
        $before = phaseDSnapshot();
        phaseDCheck(MZMove(1, 'myMaterial-4', $destination) === null && phaseDSnapshot() === $before,
            "Magistrate veto prevents source loss or destination side effects for $destination");
    }
    foreach([[], ['NOCOST'], ['CONTINUE', 'myMaterial-4'], ['CONTINUE', 'myMaterial-4', 'NOCOST']] as $parts) {
        phaseDLoad('eternal-magistrate-material-imbued');
        $before = phaseDSnapshot();
        $customDQHandlers['MATERIALIZE'](1, $parts, 'myMaterial-4');
        phaseDCheck(phaseDSnapshot() === $before, 'Materialization refuses blocked choices before any cost, including continuations and ignored costs');
    }
    phaseDLoad('eternal-magistrate-material-imbued');
    $before = phaseDSnapshot();
    DoActivateCard(1, 'myMaterial-4', true);
    phaseDCheck(phaseDSnapshot() === $before, 'Direct ignored-cost activation cannot bypass Magistrate');
    phaseDCheck(!CanActivateCardForSelection(1, GetZoneObject('myMaterial-4')), 'Blocked material activation is not offered');
    phaseDCheck(!in_array('myMaterial-4', GetLegalMaterializeChoices(1), true), 'Blocked materialize choice is not offered');
    phaseDCheck(!ActivateBanishedCard(1, 'myMaterial-4'), 'Activation helper refuses before moving material to hand');
    phaseDLoad('luxera-generation-blocked');
    $luxera = AddField(1, CardID: 'Klb4tguLek', Owner: 1, Controller: 1);
    $luxera->Counters['luxeraName'] = 'Refurbish';
    phaseDCheck(!GACanGenerateCard(1, 'b43adsk77Y') && GACanGenerateCard(2, 'b43adsk77Y'), 'Luxera prohibition belongs to its controller');
    $boon = null;
    foreach(GetField(1) as $candidate) if($candidate->CardID === 'Klb4tguLek') $boon = $candidate;
    $boon->Counters['luxeraUsed'] = 1;
    phaseDCheck(!GACanGenerateCard(1, 'b43adsk77Y'), 'Using the activation reward does not consume the prohibition');
    $boon->TurnEffects[] = 'NO_ABILITIES';
    phaseDCheck(GACanGenerateCard(1, 'b43adsk77Y'), 'Suppressed Luxera does not prohibit generation');
    $boon->TurnEffects = [];
    $boon->removed = true;
    phaseDCheck(GACanGenerateCard(1, 'b43adsk77Y'), 'Removed Luxera does not prohibit generation');
    foreach([
        [$activateAbilityAbilities, '1ubrwubSQN:0', 'ye7f7o5yut', []],
        [$enterAbilities, 'k5Kid67duu:0', 'ek5r5YlFQv', []],
        [$customDQHandlers, '41t71u4bzz:0:ActivateAbility-1', 'vpmu6gvnta', [[], '2']],
        [$customDQHandlers, 'lVvU9lpJ3M:0:ActivateAbility-1', 'ek5r5YlFQv', [[], 'YES']],
        [$customDQHandlers, 'lVvU9lpJ3M:0:ActivateAbility-2', 'ek5r5YlFQv', [[], 'YES']],
        [$onHitAbilities, 'iq4d5vettc:0', 'zrplywc08c', []],
        [$customDQHandlers, '6hjlgx72rf:0:OnKill-1', 'zrplywc08c', [[], 'theirField-0']],
        [$customDQHandlers, 'tf5f2n38g0:0:OnAttack-1', 'pn9gQjV3Rb', [[], '0']],
    ] as [$table, $handler, $generatedID, $args]) {
        phaseDLoad('luxera-generation-blocked');
    $luxera = AddField(1, CardID: 'Klb4tguLek', Owner: 1, Controller: 1);
    $luxera->Counters['luxeraName'] = 'Refurbish';
        foreach(GetField(1) as $boon) if($boon->CardID === 'Klb4tguLek') $boon->Counters['luxeraName'] = CardName($generatedID);
        $before = phaseDSnapshot();
        $table[$handler](1, ...$args);
        phaseDCheck(phaseDSnapshot() === $before, "Luxera gates generation-only route $handler");
    }
    phaseDLoad('luxera-generation-blocked');
    $luxera = AddField(1, CardID: 'Klb4tguLek', Owner: 1, Controller: 1);
    $luxera->Counters['luxeraName'] = 'Refurbish';
    foreach(GetField(1) as $boon) if($boon->CardID === 'Klb4tguLek') $boon->Counters['luxeraName'] = CardName('6fxxgmuesd');
    $before = count(GetMemory(1));
    $customDQHandlers['SablemereWardensGripEffect'](1, ['3'], '');
    phaseDCheck(count(GetMemory(1)) === $before && GetCounterCount(GetField(1)[0], 'lash') === 3,
        'Blocked Sablemere generation still applies its lash counters');
    phaseDLoad('heavenly-guide-played-champion-with-lineage-does-not-qualify');
    $choices = GetLevelUpMaterialIndices(1);
    DoMaterialize(1, $choices[0], false);
    $champ = GetZoneObject(FindChampionMZ(1));
    phaseDCheck(intval($champ->Counters['gaTopUnplayed'] ?? 0) === 1, 'Effect-driven level-up records unplayed provenance');
    phaseDCheck(Delevel(1), 'Champion can delevel after an effect-driven level-up');
    $champ = GetZoneObject(FindChampionMZ(1));
    phaseDCheck(empty($champ->Counters['gaTopUnplayed']), 'Delevel restores the prior played top card provenance');
    $choices = GetLevelUpMaterialIndices(1);
    DoMaterialize(1, $choices[0]);
    $champ = GetZoneObject(FindChampionMZ(1));
    phaseDCheck(empty($champ->Counters['gaTopUnplayed']), 'Ordinary materialization records a played top card');
    phaseDLoad('firetuned-automaton-discards-exalted-fire-as-additional-cost');
    $source = phaseDHandCard('lzjmwuir99');
    phaseDCheck(MandatoryActivationCostBlocker(1, $source) === null, 'Fire discard preview accepts Exalted Fire');
    foreach(GetHand(1) as $card) if($card->CardID === 'HPDawzCDdr') $card->removed = true;
    phaseDCheck(MandatoryActivationCostBlocker(1, $source) !== null, 'The activated card cannot count as its own discard payment');
    phaseDLoad('slipstream-target-cost-ordinary');
    AddEffectStack(CardID: 'HPDawzCDdr', Controller: 2);
    phaseDCheck(GetEffectStackActivationTargets(1, ['elements' => ['FIRE']]) === ['EffectStack-0'], 'Activation element filters accept dual Fire membership');

    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $playerID = $viewer;
        foreach(GetField($owner) as $existing) if(!PropertyContains(EffectiveCardType($existing), "CHAMPION")) $existing->removed = true;
        $dilu = AddField($owner, CardID: 'du4eaktghh', Owner: $owner, Controller: $owner);
        $jueying = AddField($owner, CardID: 'c3plbuv3fr', Owner: $owner, Controller: $owner);
        $human = AddField($owner, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $owner);
        $human->Counters['_overrides'] = ['type' => 'ALLY,UNIQUE', 'subtypes' => 'HUMAN', 'element' => 'EXALTED,WIND'];
        phaseDCheck(HasVigor($dilu) && PrideAmount($dilu) === 0, "Dilu effective Wind human owner $owner viewer $viewer");
        $human->Counters['_overrides']['element'] = 'EXALTED,WATER';
        phaseDCheck(HasStealth($jueying) && HasTrueSight($jueying) && PrideAmount($jueying) === 0,
            "Jueying effective Water human owner $owner viewer $viewer");
        $human->removed = true;
        phaseDCheck(!HasVigor($dilu) && !HasStealth($jueying) && !HasTrueSight($jueying), 'Removed human supplies no conditional Horse keywords');
    }
    phaseDLoad('lorraine-ascendant-pays-2024-and-generates-two-swords');
    $lorraine = GetField(1)[0];
    phaseDCheck(GALorraineAscendantCanActivate($lorraine), 'Ascendant with exactly 2024 reserve can activate');
    $lorraine->Counters['_overrides']['type'] = 'CHAMPION';
    phaseDCheck(!GALorraineAscendantCanActivate($lorraine), '2024 reserve cannot bypass the Ascendant restriction');
    $lorraine->Counters['_overrides']['type'] = 'CHAMPION,ASCENDANT';
    $lorraine->TurnEffects[] = 'NO_ABILITIES';
    phaseDCheck(!GALorraineAscendantCanActivate($lorraine), 'Suppression disables the lineage ability');
    $lorraine->TurnEffects = [];
    GetHand(1)[0]->removed = true;
    phaseDCheck(!GALorraineAscendantCanActivate($lorraine), '2023 reserve is insufficient');
    phaseDLoad('lorraine-ascendant-pays-2024-and-generates-two-swords');
    GALorraineAscendantPay(1, GetField(1)[0]);
    $uid = GetField(1)[0]->UniqueID;
    $customDQHandlers['LorraineAscendantPaid'](1, [strval($uid)], 'myHand-0');
    phaseDCheck(count(GetMemory(1)) === 0 && count(GetField(1)) === 1, 'Incomplete 2024 selection cannot partially pay or generate');
    phaseDLoad('lorraine-ascendant-pays-2024-and-generates-two-swords');
    GALorraineAscendantResolve(1);
    $customDQHandlers['LorraineAscendantMore'](1, [], 'NO');
    phaseDCheck(count(GetField(1)) === 1 && DecisionQueueController::GetVariable('lorraineAscendantSwords') === null,
        'Choosing zero Swords completes generation without creating anything');
    phaseDLoad('slipstream-target-cost-ordinary');
    $before = count(GetField(2));
    AddGlobalEffects(2, 'owq8s5fefw');
    GAPutFieldGroup(1, ['b0qlk9j6le', 'b0qlk9j6le']);
    $dq = new DecisionQueueController();
    $dq->ExecuteStaticMethods(2, '-');
    phaseDCheck(count(GetField(2)) === $before + 2 && GlobalEffectCount(2, 'owq8s5fefw') === 0,
        'Replication copies every regalia in one simultaneous event exactly once');
    $copies = array_values(array_filter(GetField(2), fn($obj) => !$obj->removed && $obj->CardID === 'b0qlk9j6le'));
    phaseDCheck(count($copies) === 2 && IsTokenObject($copies[0]) && IsTokenObject($copies[1]), 'Regalia copies are tokens with effective token identity');
    $before = count(GetGraveyard(2));
    $mz = NormalizeMzIDForController($copies[0]->GetMzID(), 2);
    MZMove(2, $mz, 'myGraveyard');
    phaseDCheck($copies[0]->removed && count(GetGraveyard(2)) === $before, 'A copied regalia token ceases to exist after leaving the field');
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $playerID = $viewer;
        $source = AddField($owner, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $owner);
        $source->Counters['_overrides']['element'] = 'EXALTED,WATER';
        $target = GetField(3 - $owner)[0];
        $target->TurnEffects[] = 'FROSTBITE_WATER_VULN';
        DealDamage($owner, $source->GetMzID(), $target->GetMzID(), 1);
        phaseDCheck($target->Damage === 5 && !in_array('FROSTBITE_WATER_VULN', $target->TurnEffects, true),
            "Frostbite effective dual Water owner $owner viewer $viewer consumes once");
        DealDamage($owner, $source->GetMzID(), $target->GetMzID(), 1);
        phaseDCheck($target->Damage === 6, 'The Frostbite damage bonus cannot be reused');
    }
    phaseDLoad('slipstream-target-cost-ordinary');
    $sword = AddField(1, CardID: 'b0qlk9j6le', Owner: 1, Controller: 1);
    $core = AddField(1, CardID: 'aAJliPQT3F', Owner: 1, Controller: 1);
    CreateWeaponLink(1, $core->GetMzID(), $sword->GetMzID());
    $before = GetCounterCount($sword, 'durability');
    OnDiscardCard(1, 'HPDawzCDdr');
    phaseDCheck(GetCounterCount($sword, 'durability') === $before + 1, 'BladeCore sees Exalted Fire discard membership');
    $core->TurnEffects[] = 'NO_ABILITIES';
    OnDiscardCard(1, 'mttsvbgl6f');
    phaseDCheck(GetCounterCount($sword, 'durability') === $before + 1, 'Suppressed BladeCore cannot grant discard durability');
    phaseDLoad('slipstream-target-cost-ordinary');
    $probe = clone GetHand(1)[0];
    $probe->CardID = 'KqBosnU7pU';
    $base = CalculateActivationReserveCost(1, $probe, true);
    $dawn = AddField(1, CardID: '4coy34bro8', Owner: 1, Controller: 1);
    phaseDCheck(CalculateActivationReserveCost(1, $probe, true) === $base, 'Dawn does not surcharge Exalted Norm');
    $probe->CardID = 't2lW0Q5KJS';
    $cost = CalculateActivationReserveCost(1, $probe, true);
    $otherDawn = AddField(2, CardID: '4coy34bro8', Owner: 2, Controller: 2);
    phaseDCheck(CalculateActivationReserveCost(1, $probe, true) === $cost + 1, 'Separate Dawn surcharges stack');
    $otherDawn->TurnEffects[] = 'NO_ABILITIES';
    phaseDCheck(CalculateActivationReserveCost(1, $probe, true) === $cost, 'Suppressed Dawn supplies no surcharge');
    foreach(['EXALTED,NORM' => 2, 'EXALTED,FIRE' => 0] as $element => $damage) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $source = AddField(1, CardID: 'em6eEh9q8y', Owner: 1, Controller: 1);
        $source->Counters['_overrides']['element'] = $element;
        $princess = AddField(2, CardID: 's2tzwv1uw3', Owner: 2, Controller: 2);
        $princess->TurnEffects = ['IMBUED'];
        DealDamage(1, $source->GetMzID(), $princess->GetMzID(), 2);
        phaseDCheck($princess->Damage === $damage, "Shangxiang Norm exclusion for $element");
    }
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $playerID = $viewer;
        $memory = &GetMemory($owner); $memory = [];
        AddMemory($owner, CardID: 'KqBosnU7pU');
        $domain = AddField($owner, CardID: '4coy34bro8', Owner: $owner, Controller: $owner);
        DomainRevealMemoryUpkeep($owner, $domain->mzIndex, ['NORM'], '4coy34bro8');
        phaseDCheck(!$domain->removed && $playerID === $viewer, "Domain dual Norm upkeep owner $owner viewer $viewer preserves perspective");
        $memory[0]->CardID = 'HPDawzCDdr';
        AddGlobalEffects(3 - $owner, 'pol1nz0j1n');
        DomainRevealMemoryUpkeep($owner, $domain->mzIndex, ['NORM'], '4coy34bro8');
        phaseDCheck(!$domain->removed, 'Memory Norm override satisfies the revealed upkeep condition');
    }
    phaseDLoad('eternal-magistrate-material-imbued');
    phaseDCheck(GetLevelUpMaterialIndices(1) === [] && !LevelUpChampion(1), 'Magistrate blocks effect-driven level-up before changing the old champion');
    AddMaterial(1, 'Dtr3jPRAFJ');
    $spec = GenericGraveyardBanishCostSpec('5upufyoz23');
    phaseDCheck(GenericGraveyardBanishCandidates($spec) === [], 'Material banishment costs have no illegal candidate under Magistrate');
    foreach(['EXALTED,FIRE' => 2, 'FIRE' => 2, 'EXALTED,WATER' => 0] as $element => $drawn) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $hand = &GetHand(1); $hand = [];
        $deck = &GetDeck(1); $deck = [];
        for($i = 0; $i < 2; ++$i) {
            $card = AddHand(1, CardID: 'HPDawzCDdr');
            $card->Counters['_overrides']['element'] = $element;
            AddDeck(1, CardID: 'em6eEh9q8y');
        }
        CoiledFatestoneEnter(1);
        phaseDCheck(count(array_filter(GetHand(1), fn($o) => !$o->removed)) === $drawn, "Coiled Fatestone draws for $element discarded cards");
    }
    foreach(['EXALTED,WIND' => true, 'WIND' => true, 'EXALTED,FIRE' => false] as $element => $qualifies) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $deck = &GetDeck(1); $deck = [];
        $card = AddDeck(1, CardID: 'HPDawzCDdr');
        $card->Counters['_overrides']['element'] = $element;
        AddField(1, CardID: 'em6eEh9q8y', Owner: 1, Controller: 1);
        $queue = &GetDecisionQueue(1); $queue = [];
        $customDQHandlers['ParcenetReveal'](1, [], '');
        $choices = array_filter(GetDecisionQueue(1), fn($o) => $o->Type === 'MZCHOOSE');
        phaseDCheck(!empty($choices) === $qualifies, "Parcenet offers stealth for $element reveal");
    }
    foreach(['EXALTED,FIRE' => true, 'FIRE' => true, 'EXALTED,WATER' => false] as $element => $qualifies) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $graveyard = &GetGraveyard(1); $graveyard = [];
        $card = AddGraveyard(1, CardID: 'HPDawzCDdr');
        $card->Counters['_overrides'] = ['element' => $element, 'subtypes' => 'AETHERCHARGE'];
        $wing = AddField(1, CardID: 'b0qlk9j6le', Owner: 1, Controller: 1);
        DecisionQueueController::StoreVariable('BlazeWing', $wing->GetMzID());
        $queue = &GetDecisionQueue(1); $queue = [];
        $customDQHandlers['BlazeContinueFireLoads'](1, ['2'], '');
        phaseDCheck(!empty(array_filter($queue, fn($o) => $o->Type === 'MZMAYCHOOSE')) === $qualifies,
            "Blazing Cindercharge offers effective $element Aethercharge");
        $card->Counters['_overrides']['type'] = 'ALLY';
        $card->Counters['_overrides']['subtypes'] = 'SLIME';
        DecisionQueueController::StoreVariable('slimeEruptionCount', '0');
        DecisionQueueController::StoreVariable('slimeEruptionNonFire', '2');
        $queue = [];
        SlimeEruptionBanishLoop(1);
        phaseDCheck(!empty(array_filter($queue, fn($o) => $o->Type === 'MZMAYCHOOSE')) === $qualifies,
            "Slime Eruption at non-Fire cap offers effective $element Slime");
        if($qualifies) {
            $customDQHandlers['SlimeEruptionBanishPick'](1, [], 'myGraveyard-0');
            phaseDCheck(intval(DecisionQueueController::GetVariable('slimeEruptionNonFire')) === 2,
                "Slime Eruption does not charge $element against non-Fire cap");
        }
    }
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        foreach(['EXALTED,WIND' => 1, 'WIND' => 1, 'EXALTED,FIRE' => 0] as $element => $buffs) {
            phaseDLoad('slipstream-target-cost-ordinary');
            $playerID = $viewer;
            $champ = GetZoneObject(FindChampionMZ($owner));
            $champ->CardID = 'j6dkdoxyqt';
            $stone = AddField($owner, CardID: 'x2oydmfcre', Owner: $owner, Controller: $owner);
            $entered = AddField($owner, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $owner);
            $entered->Counters['_overrides']['element'] = $element;
            $zone = $owner === $viewer ? 'myField-' : 'theirField-';
            WildgrowthFatestoneOnEnterCheck($owner, $zone . $entered->mzIndex);
            phaseDCheck(GetCounterCount($stone, 'buff') === $buffs,
                "Wildgrowth effective $element owner $owner viewer $viewer");
            $stone->TurnEffects[] = 'NO_ABILITIES';
            WildgrowthFatestoneOnEnterCheck($owner, $zone . $entered->mzIndex);
            phaseDCheck(GetCounterCount($stone, 'buff') === $buffs, 'Suppressed Wildgrowth grants no counter');
        }
        foreach(['K15jWbHAMY' => true, 'HPDawzCDdr' => false, '' => false] as $lineage => $qualifies) {
            phaseDLoad('slipstream-target-cost-ordinary');
            $playerID = $viewer;
            $champ = GetZoneObject(FindChampionMZ($owner));
            $champ->CardID = '29lqrve8fz';
            $champ->Subcards = $lineage === '' ? [] : [$lineage];
            $deck = &GetDeck($owner); $deck = [];
            AddDeck($owner, CardID: 'em6eEh9q8y');
            AddDeck($owner, CardID: 'em6eEh9q8y');
            $temp = &GetTempZone($owner); $temp = [];
            NicoRapturesEmbraceEnter($owner);
            phaseDCheck(count(array_filter(GetTempZone($owner), fn($o) => !$o->removed)) === ($qualifies ? 2 : 0),
                "Nico lineage $lineage owner $owner viewer $viewer excludes its own Water element");
        }
    }
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $playerID = $viewer;
        $graveyard = &GetGraveyard($owner); $graveyard = [];
        $older = AddGraveyard($owner, CardID: 'em6eEh9q8y');
        $killed = AddGraveyard($owner, CardID: 'em6eEh9q8y');
        $snapshot = clone GetField($owner)[0];
        $snapshot->UniqueID = 777;
        DecisionQueueController::StoreVariable('GACaptureDeathUID', '777');
        GACaptureKilledCardDestination($snapshot, $killed);
        DecisionQueueController::ClearVariable('GACaptureDeathUID');
        $token = DecisionQueueController::GetVariable('GACapturedDeathToken');
        phaseDCheck(GAResolveKilledCard($token)['object'] === $killed, 'Killed card reference identifies exact duplicate');
        AddGraveyard($owner, CardID: 'em6eEh9q8y');
        phaseDCheck(GAResolveKilledCard($token)['object'] === $killed, 'New same-name arrival cannot replace killed card');
        $older->Remove();
        phaseDCheck(GAResolveKilledCard($token)['object'] === $killed, 'Earlier tombstone preserves killed live ordinal');
        DecisionQueueController::CleanupRemovedCards();
        phaseDCheck(GAResolveKilledCard($token)['object'] === $killed, 'Physical graveyard compaction preserves killed identity');
        WriteGamestate('./GrandArchiveSim/');
        ParseGamestate('./GrandArchiveSim/');
        $playerID = $viewer;
        phaseDCheck(GAResolveKilledCard($token)['index'] === 0, 'Killed card identity survives save and reload');
        $returned = GAResolveKilledCard($token);
        $returned['object']->Remove();
        AddGraveyard($owner, CardID: 'em6eEh9q8y');
        DecisionQueueController::CleanupRemovedCards();
        phaseDCheck(GAResolveKilledCard($token) === null, 'Departed killed card never falls back to a duplicate');
        DecisionQueueController::StoreVariable('CombatKilledCardID', 'em6eEh9q8y');
        DecisionQueueController::StoreVariable('CombatKilledDestinationToken', $token);
        $before = count(GetField(3 - $owner));
        PossessedReapingOnKill(3 - $owner);
        phaseDCheck(count(GetField(3 - $owner)) === $before, 'Possessed Reaping cannot resurrect an unrelated duplicate after its recipient leaves');

    }
    foreach(['EXALTED,WATER' => true, 'WATER' => true, 'EXALTED,FIRE' => false] as $element => $qualifies) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $temp = &GetTempZone(1); $temp = [];
        $card = AddTempZone(1, CardID: 'HPDawzCDdr');
        $card->Counters['_overrides']['element'] = $element;
        $queue = &GetDecisionQueue(1); $queue = [];
        MisteyeArcherLook(1, 'myField-0');
        phaseDCheck(!empty(array_filter($queue, fn($o) => $o->Type === 'YESNO')) === $qualifies, "Misteye Archer accepts $element");
        phaseDLoad('slipstream-target-cost-ordinary');
        $memory = &GetMemory(1); $memory = [];
        $card = AddMemory(1, CardID: 'HPDawzCDdr');
        $card->Counters['_overrides']['element'] = $element;
        DecisionQueueController::StoreVariable('fractalRefreshCount', '0');
        $customDQHandlers['FractalRefreshPick'](1, ['1'], 'myMemory-0');
        phaseDCheck(intval(DecisionQueueController::GetVariable('fractalRefreshCount')) === ($qualifies ? 1 : 0), "Fractal Refreshment continuation accepts $element");
    }
    foreach(['EXALTED,WIND' => true, 'WIND' => true, 'EXALTED,FIRE' => false] as $element => $qualifies) {
        phaseDLoad('slipstream-target-cost-ordinary');
        GetField(1)[0]->CardID = 'e3z4pyx8bd';
        $deck = &GetDeck(1); $deck = [];
        $temp = &GetTempZone(1); $temp = [];
        $card = AddDeck(1, CardID: 'em6eEh9q8y');
        $card->Counters['_overrides']['element'] = $element;
        DecisionQueueController::StoreVariable('isImbued', 'YES');
        $queue = &GetDecisionQueue(1); $queue = [];
        $enterAbilities['disqw3d0o5:0'](1);
        phaseDCheck(!empty(array_filter($queue, fn($o) => $o->Type === 'MZMAYCHOOSE')) === $qualifies, "Captain Archer accepts $element ally");
        phaseDLoad('slipstream-target-cost-ordinary');
        GetField(2)[0]->CardID = 'j6dkdoxyqt';
        $memory = &GetMemory(2); $memory = [];
        $card = AddMemory(2, CardID: 'HPDawzCDdr');
        $card->Counters['_overrides']['element'] = $element;
        $spearman = AddField(2, CardID: 'pal7cpvn96', Owner: 2, Controller: 2);
        StoreCombatAttackerState('myField-0', 1); // damage is combat damage only when it comes from the stored attacker
        OnDealDamage(1, 'myField-0', 'theirField-' . $spearman->mzIndex, 3);
        phaseDCheck($spearman->Damage === ($qualifies ? 0 : 3), "Intrepid Spearman prevents for $element memory");
    }
    foreach(['EXALTED,FIRE' => true, 'FIRE' => true, 'EXALTED,WATER' => false] as $element => $qualifies) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $attacker = AddField(1, CardID: 'em6eEh9q8y', Owner: 1, Controller: 1);
        $attacker->Counters['_overrides']['element'] = $element;
        $relic = AddField(1, CardID: 'i8g5013x9j', Owner: 1, Controller: 1);
        $relic->Status = 2;
        StoreCombatTargetState('theirField-0', 1);
        $queue = &GetDecisionQueue(1); $queue = [];
        OnHitTrigger(1, $attacker->GetMzID());
        phaseDCheck(!empty(array_filter($queue, fn($o) => $o->Type === 'CUSTOM' && str_starts_with($o->Param, 'RelicDancingEmbers|'))) === $qualifies,
            "Relic of Dancing Embers sees $element combat source");
    }
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        foreach([false, true] as $recipientLeaves) {
            phaseDLoad('slipstream-target-cost-ordinary');
            $playerID = $viewer;
            GetField($owner)[0]->Counters['_overrides']['classes'] = 'ASSASSIN';
            $opponent = 3 - $owner;
            $championID = GetField($opponent)[0]->CardID;
            $field = &GetField($opponent); $field = [];
            $decoy = AddField($opponent, CardID: 'em6eEh9q8y', Owner: $opponent, Controller: $opponent);
            $recipient = AddField($opponent, CardID: $championID, Owner: $opponent, Controller: $opponent);
            $weapon = AddField($owner, CardID: '0dr40tfllk', Owner: $owner, Controller: $owner);
            DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($owner));
            StoreCombatAttackerState('myField-' . GetField($owner)[0]->mzIndex, $owner);
            // Combat coordinates are stored from the attacking controller's perspective.
            StoreCombatTargetState('theirField-' . $recipient->mzIndex, $owner);
            $queue = &GetDecisionQueue($owner); $queue = [];
            MalignantAthameOnHit($owner, $weapon->GetMzID());
            $continuations = array_values(array_filter($queue, fn($o) => $o->Type === 'CUSTOM' && str_starts_with($o->Param, 'MalignantAthameSwap|')));
            phaseDCheck(count($continuations) === 1, "Athame captures original hit owner $owner viewer $viewer");
            $parts = explode('|', $continuations[0]->Param); array_shift($parts);
            $weapon->Remove(); $decoy->Remove();
            if($recipientLeaves) $recipient->Remove();
            DecisionQueueController::CleanupRemovedCards();
            $replacement = AddField($opponent, CardID: $championID, Owner: $opponent, Controller: $opponent);
            DecisionQueueController::ClearVariable('CombatTarget');
            $memory = &GetMemory($opponent); $memory = [];
            for($i = 0; $i < 4; ++$i) AddMemory($opponent, CardID: 'em6eEh9q8y');
            $customDQHandlers['MalignantAthameSwap']($owner, $parts, 'NO');
            phaseDCheck($recipientLeaves ? $replacement->Damage === 0 : $recipient->Damage === 2,
                'Athame binds original recipient after source loss and compaction');
            phaseDCheck($replacement->Damage === 0, 'Athame never damages a replacement at the old slot');
        }
    }
    phaseDLoad('slipstream-target-cost-ordinary');
    DecisionQueueController::StoreVariable('BlastshotPumpDamage', '99');
    DecisionQueueController::StoreVariable('BlastshotPumpSource', 'em6eEh9q8y');
    $customDQHandlers['BlastshotPumpChoose'](1, ['2', 'gmnmp5af09'], 'theirField-0');
    phaseDCheck(GetField(2)[0]->Damage === 2, 'Blastshot continuation uses its captured damage rather than overwritten ambient state');
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        foreach(['spijrps4ny', 'o4h8cfo21a'] as $effect) foreach(['live', 'attacker-left', 'payer-left', 'empty', 'decline'] as $mode) {
            phaseDLoad('slipstream-target-cost-ordinary');
            $playerID = $viewer;
            $champion = GetField($owner)[0];
            $champion->Counters['preparation'] = 3;
            $decoy = AddField($owner, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $owner);
            $attacker = AddField($owner, CardID: $effect, Owner: $owner, Controller: $owner);
            DecisionQueueController::StoreVariable('mzID', $attacker->GetMzID());
            $queue = &GetDecisionQueue($owner); $queue = [];
            GABeginPrepAttackChoice($owner, $effect, $effect === 'spijrps4ny' ? 1 : 3);
            $choices = array_values(array_filter($queue, fn($o) => $o->Type === 'CUSTOM' && str_starts_with($o->Param, 'GAPrepAttackPay|')));
            phaseDCheck(count($choices) === 1, "Prep choice snapshots $effect owner $owner viewer $viewer mode $mode");
            $parts = explode('|', $choices[0]->Param); array_shift($parts);
            $decoy->Remove();
            if($mode === 'attacker-left') {
                $attacker->Remove();
                AddField($owner, CardID: $effect, Owner: $owner, Controller: $owner);
            }
            if($mode === 'payer-left') {
                $champion->Remove();
                $replacement = AddField($owner, CardID: $champion->CardID, Owner: $owner, Controller: $owner);
                $replacement->Counters['preparation'] = 3;
            }
            if($mode === 'empty') $champion->Counters['preparation'] = 0;
            DecisionQueueController::CleanupRemovedCards();
            DecisionQueueController::StoreVariable('CombatAttacker', 'theirField-0');
            $customDQHandlers['GAPrepAttackPay']($owner, $parts, $mode === 'decline' ? 'NO' : 'YES');
            $paid = $mode === 'live';
            phaseDCheck(GetCounterCount($champion, 'preparation') === ($mode === 'empty' ? 0 : ($paid ? 2 : 3)),
                'Bound prep payment never charges a missing or replaced participant');
            phaseDCheck(in_array($effect, $attacker->TurnEffects ?? [], true) === $paid,
                'Bound prep bonus follows the original attacker through compaction only');
        }
    }
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $controller = 3 - $owner;
        $playerID = $controller;
        $target = AddField($controller, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $controller);
        DecisionQueueController::StoreVariable('GACaptureDeathUID', strval($target->UniqueID));
        DoAllyDestroyed($controller, $target->GetMzID());
        DecisionQueueController::ClearVariable('GACaptureDeathUID');
        $token = DecisionQueueController::GetVariable('GACapturedDeathToken');
        $record = GAResolveKilledCard($token);
        phaseDCheck($record !== null && $record['seat'] === $owner, 'Stolen killed ally lands in its actual owner graveyard');
        $playerID = $viewer;
        DecisionQueueController::StoreVariable('CombatKilledCardID', 'em6eEh9q8y');
        DecisionQueueController::StoreVariable('CombatKilledDestinationToken', $token);
        $before = count(GetField($controller));
        PossessedReapingOnKill($controller);
        $liveReturned = array_values(array_filter(GetField($controller), fn($o) => !$o->removed && $o->CardID === 'em6eEh9q8y'));
        $returned = $liveReturned[count($liveReturned) - 1];
        phaseDCheck($returned->CardID === 'em6eEh9q8y' && intval($returned->Owner) === $owner && intval($returned->Controller) === $controller,
            "Reaping returns exact stolen ally owner $owner viewer $viewer");
        phaseDCheck(GAResolveKilledCard($token) === null, 'Successful reanimation consumes killed destination identity');
    }
    foreach(['EXALTED,WIND' => true, 'WIND' => true, 'EXALTED,FIRE' => false] as $element => $qualifies) {
        phaseDLoad('slipstream-target-cost-ordinary');
        $deck = &GetDeck(1); $deck = [];
        $temp = &GetTempZone(1); $temp = [];
        $card = AddDeck(1, CardID: 'em6eEh9q8y');
        $card->Counters['_overrides']['element'] = $element;
        $queue = &GetDecisionQueue(1); $queue = [];
        $cardActivatedAbilities['edg616r0za:0'](1);
        phaseDCheck(!empty(array_filter($queue, fn($o) => $o->Type === 'MZMAYCHOOSE')) === $qualifies,
            "Enhance Hearing accepts effective $element");
        foreach(['YES', 'NO'] as $imbued) foreach([false, true] as $suppressed) {
            phaseDLoad('slipstream-target-cost-ordinary');
            $academy = AddField(1, CardID: 'lxnq80yu75', Owner: 1, Controller: 1);
            if($suppressed) $academy->TurnEffects = ['NO_ABILITIES'];
            DecisionQueueController::StoreVariable('isImbued', $imbued);
            $ally = AddField(1, CardID: 'em6eEh9q8y', Owner: 1, Controller: 1,
                Counters: ['_overrides' => ['element' => $element]]);
            phaseDCheck(in_array('lxnq80yu75', $ally->TurnEffects ?? [], true) === ($qualifies && $imbued === 'YES' && !$suppressed),
                "Gearstride Academy effective $element imbued $imbued suppressed " . intval($suppressed));
        }
    }
    foreach([1, 2] as $owner) foreach([1, 2] as $viewer) {
        foreach(['CHAMPION', 'ALLY', 'ITEM'] as $type) foreach(['live', 'compacted', 'left', 'wrong-controller', 'non-guardian'] as $mode) {
            phaseDLoad('slipstream-target-cost-ordinary');
            $playerID = $owner;
            $decoy = AddField($owner, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $owner);
            $attacker = AddField($owner, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $owner);
            $attacker->Counters['_overrides'] = ['type' => $type, 'classes' => $mode === 'non-guardian' ? 'MAGE' : 'GUARDIAN'];
            GAEnsureStableFieldIdentity($attacker);
            DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($owner));
            StoreCombatAttackerState('myField-' . $attacker->mzIndex, $owner);
            if($mode === 'compacted') { $decoy->Remove(); DecisionQueueController::CleanupRemovedCards(); }
            if($mode === 'left') {
                $attacker->Remove();
                AddField($owner, CardID: 'em6eEh9q8y', Owner: $owner, Controller: $owner);
            }
            if($mode === 'wrong-controller') $attacker->Controller = 3 - $owner;
            $playerID = $viewer;
            $live = !in_array($mode, ['left', 'wrong-controller'], true);
            phaseDCheck($cardActivatedPrereqs['10u5ldz371:0']($owner, '') === ($live && $type === 'CHAMPION'),
                "Meteoric Volley owner $owner viewer $viewer type $type mode $mode");
            phaseDCheck($cardActivatedPrereqs['14hr8i5oix:0']($owner, '') === ($live && $type !== 'ITEM' && $mode !== 'non-guardian'),
                "Overpowering Defense owner $owner viewer $viewer type $type mode $mode");
            phaseDCheck($cardActivatedPrereqs['10u5ldz371:0'](3 - $owner, '') === ($mode === 'wrong-controller' && $type === 'CHAMPION')
                && $cardActivatedPrereqs['14hr8i5oix:0'](3 - $owner, '') === ($mode === 'wrong-controller' && $type !== 'ITEM'),
                'Combat activation restrictions inspect the actual controller');
        }
    }
    echo "$checks Phase D boundary checks passed.\n";
} finally {
    RegressionDeleteDirRecursive($gameDir);
}
