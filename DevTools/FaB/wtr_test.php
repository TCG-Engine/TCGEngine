<?php
require_once __DIR__ . '/upf_test.php';
$failures = [];
$snapshot=json_decode(file_get_contents(__DIR__.'/wtr_abilities.json'),true,512,JSON_THROW_ON_ERROR);
$cache=json_decode(file_get_contents(__DIR__.'/../../FaBSim/GeneratedCode/cardArrayCache.json'),true,512,JSON_THROW_ON_ERROR);
$expected=[];foreach($cache['cardArray']as$card)foreach($card['printings']??[]as$printing)if(($printing['set_id']??'')==='WTR'){$expected[]=$card['id'];break;}
$actual=array_column($snapshot,'cardId');sort($actual);sort($expected);
$check(count($actual)===226&&$actual===$expected,'The checked-in WTR snapshot does not match all set identities.');
$answer = function (int $seat, string $value): void {
    $queue = GetDecisionQueue($seat);
    if (!$queue) throw new RuntimeException('No pending choice for seat '.$seat);
    $dq = new DecisionQueueController();
    $dq->PopDecision($seat);
    $dq->ExecuteStaticMethods($seat, $value);
};
$reset();
$sink = AddCombatChain(4,CardID:'sink_below_red',Owner:4,Controller:4);
$hand = AddHand(4,CardID:'wounding_blow_red');
AddDeck(4,CardID:'wounding_blow_blue');
FaBRunSourceMacro('ResolveCard',4,'sink_below_red',['mzID'=>'p4CombatChain-0']);
$check(GetDecisionQueue(4)[0]->Param === 'p4Hand-0', 'Sink Below must select from player four hand.');
$answer(4,'p4Hand-0');
$check(FaBFindUID(intval($hand->UniqueID))['zone'] === 'Deck', 'Sink Below did not bottom the chosen card.');
$check(GetHand(4)[1]->CardID === 'wounding_blow_blue', 'Sink Below did not draw the original top card.');
$reset();
$rem = AddGraveyard(4,CardID:'remembrance_yellow');
$card = AddGraveyard(4,CardID:'wounding_blow_red');
FaBRunSourceMacro('ResolveCard',4,'remembrance_yellow',['mzID'=>'p4Graveyard-0']);
$answer(4,'p4Graveyard-1');
$check(FaBFindUID(intval($card->UniqueID))['zone'] === 'Deck', 'Remembrance did not shuffle selected card.');
$check(FaBFindUID(intval($rem->UniqueID))['zone'] === 'Banish', 'Remembrance did not banish itself after choice.');
$reset();
SetTurnPlayer(4);SetPriorityPlayer(4);$GLOBALS['playerID']=4;
$strike=AddHand(4,CardID:'enlightened_strike_red');$bottom=AddHand(4,CardID:'wounding_blow_blue');
$check(DoPlayCard(4,'p4Hand-0'),'Enlightened Strike could not be announced by player four.');
$answer(4,'p1Hero-0');
$check(GetDecisionQueue(4)[0]->Param==='p4Hand-1','Enlightened Strike did not ask which card to bottom.');
$answer(4,'p4Hand-1');$answer(4,'1');
$check(FaBFindUID(intval($bottom->UniqueID))['zone']==='Deck','Enlightened Strike did not pay its chosen cost.');
$check(in_array('WTR_POWER:2',FaBStackTop()->TurnEffects,true),'Enlightened Strike lost chosen power mode.');
$reset();
SetTurnPlayer(4);SetPriorityPlayer(4);$GLOBALS['playerID']=4;
GetHero(4)[0]->CardID='katsu';
$attack=AddCombatChain(4,CardID:'head_jab_red',Owner:4,Controller:4,Role:'ATTACK',ChainLink:1);
$discard=AddHand(4,CardID:'wounding_blow_red');
$combo=AddDeck(4,CardID:'rising_knee_thrust_red');AddDeck(4,CardID:'wounding_blow_blue');
$state=FaBGetState();$state['attacker']=4;$state['defender']=1;$state['attackUID']=intval($attack->UniqueID);$state['chainLink']=1;$state['window']='DAMAGE';$state['combatOpen']=true;FaBSetState($state);
OnHit(4,'p4CombatChain-0',3);
$check(count(GetDecisionQueue(4))>0,'Player-four Katsu listener was not dispatched.');
$answer(4,'p4Hand-0');
$check(GetDecisionQueue(4)[0]->Param==='p4Temp-0','Katsu search did not expose only matching combo cards.');
$answer(4,'p4Temp-0');
$banished=FaBFindUID(intval($combo->UniqueID));
$check($banished['zone']==='Banish'&&intval($banished['object']->PlayableFromBanish)===1,'Katsu did not grant play permission.');
$check(empty(FaBChoiceRefs(4,'Temp')),'Katsu did not return unchosen search cards.');
$check(FaBFindUID(intval($discard->UniqueID))['zone']==='Graveyard','Katsu did not discard selected card.');
$reset();
SetTurnPlayer(4);SetPriorityPlayer(4);$GLOBALS['playerID']=4;GetHero(4)[0]->CardID='bravo';
$pitch=AddHand(4,CardID:'wounding_blow_blue');
$check(FaBWTRActivate(4,'p4Hero-0'),'Bravo activation was not payable by pitching.');
$check(FaBGetState()['window']==='PITCH','Bravo did not open payment.');
$check(DoPitchCard(4,'p4Hand-0'),'Bravo could not accept a pitched card.');
$layer=FaBStackTop();$check($layer->Kind==='ABILITY','Bravo bypassed the ability stack.');
DoResolveCard(4,'Stack-'.intval($layer->mzIndex));
$check(intval(GetResources(4))===1&&intval(GetActionPoints(4))===1,'Bravo costs or go again were incorrect.');
$check(FaBWTREffects(4)[0]['type']==='BRAVO_DOMINATE','Bravo did not create its turn effect.');
$reset();
SetTurnPlayer(4);SetPriorityPlayer(4);$GLOBALS['playerID']=4;
$plating=AddEquipment(4,CardID:'tectonic_plating',Owner:4,Controller:4);AddResources(4,2);
$check(FaBWTRActivate(4,'p4Equipment-0'),'Tectonic Plating could not activate.');
$layer=FaBStackTop();DoResolveCard(4,'Stack-'.intval($layer->mzIndex));
$check(!FaBWTRCanActivate(4,'p4Equipment-0'),'Tectonic Plating could activate twice in one turn.');
$check(count(FaBChoiceRefs(4,'Arena',['base'=>'seismic_surge']))===1,'Tectonic Plating did not make its token.');
$reset();
$combo=AddCombatChain(4,CardID:'rising_knee_thrust_red',Owner:4,Controller:4);
FaBWTRAddEffect(4,'NEXT_COMBO_DEFENSE',2);FaBWTRApplyNextDefense(4,$combo);
$check(FaBCurrentDefense($combo,4)===5,'Flic Flak did not increase the next combo defense.');
$next=AddCombatChain(4,CardID:'blackout_kick_red',Owner:4,Controller:4);FaBWTRApplyNextDefense(4,$next);
$check(FaBCurrentDefense($next,4)===3,'Flic Flak incorrectly buffed multiple defending cards.');
$reset();
FaBWTRAddEffect(4,'NEXT_GUARDIAN_COST',1);FaBWTRAddEffect(4,'NEXT_GUARDIAN_COST',1);
$guardian=AddHand(4,CardID:'cartilage_crush_red');
$check(FaBCardCost($guardian,4)===1,'Multiple Seismic Surge reductions did not stack.');
$reset();
$chain=AddCombatChain(1,CardID:'pounding_gale_red',Owner:1,Controller:1,Role:'ATTACK',ChainLink:1);
$state=FaBGetState();$state['attacker']=1;$state['defender']=4;$state['attackUID']=intval($chain->UniqueID);$state['attackTarget']=FaBAttackTargetDescriptor(FaBFindUID(intval(GetHero(4)[0]->UniqueID)));$state['chainLink']=1;$state['combatOpen']=true;FaBSetState($state);
FaBWTRTag($chain,'WTR_DOUBLE_DAMAGE');FaBWTRAddEffect(4,'PREVENT_DAMAGE',5);
FaBBeginDamageStep();
$check(intval(GetHealth(4))===15&&FaBGetState()['damageDealt']===5,'Pounding Gale did not double damage before prevention.');
$reset();
$chain=AddCombatChain(1,CardID:'snatch_red',Owner:1,Controller:1,Role:'ATTACK',ChainLink:1);AddDeck(1,CardID:'wounding_blow_red');
$state=FaBGetState();$state['attacker']=1;$state['defender']=4;$state['attackUID']=intval($chain->UniqueID);$state['attackTarget']=FaBAttackTargetDescriptor(FaBFindUID(intval(GetHero(4)[0]->UniqueID)));$state['chainLink']=1;$state['combatOpen']=true;FaBSetState($state);
FaBWTRAddEffect(4,'PREVENT_DAMAGE',4);FaBBeginDamageStep();
$check(FaBHandCount(1)===0&&!FaBGetState()['attackHit'],'Fully prevented damage incorrectly triggered Snatch.');
if ($failures) { foreach ($failures as $failure) fwrite(STDERR,"FAIL: $failure\n"); exit(1); }
// All pitch variants use the same rules text with distinct numeric values.
$buffFamilies=[
    'awakening_bellow'=>['savage_swing_red',[3,2,1]],
    'barraging_beatdown'=>['savage_swing_red',[4,3,2]],
    'primeval_bellow'=>['savage_swing_red',[5,4,3]],
    'nimblism'=>['head_jab_red',[3,2,1]],
    'sloggism'=>['raging_onslaught_red',[6,5,4]],
    'sharpen_steel'=>['dawnblade',[3,2,1]],
    'driving_blade'=>['dawnblade',[3,2,1]],
    'warriors_valor'=>['dawnblade',[3,2,1]],
    'natures_path_pilgrimage'=>['dawnblade',[3,2,1]],
];
foreach($buffFamilies as$base=>[$attackID,$values])foreach(['red','yellow','blue']as$i=>$color){
    $reset();$source=(object)['CardID'=>$base.'_'.$color,'Params'=>[]];
    FaBWTRResolveCard(4,$source,null);
    $attack=AddStack(CardID:$attackID,Controller:4,Kind:'ATTACK');
    FaBWTRCardPlayed(4,'Stack-0',$attackID,FaBHasType($attackID,'Weapon')?'Weapons':'Hand');
    $state=FaBGetState();$state['attacker']=4;$state['chainLink']=1;
    $check(FaBWTRAttackPowerModifier(4,$attack,$state)===$values[$i],$base.' '.$color.' buff amount is incorrect.');
    $second=AddStack(CardID:$attackID,Controller:4,Kind:'ATTACK');
    FaBWTRCardPlayed(4,'Stack-1',$attackID,'Hand');
    $check(FaBWTRAttackPowerModifier(4,$second,$state)===0,$base.' buff was not consumed.');
}
$combos=['rising_knee_thrust'=>['leg_tap',2],'blackout_kick'=>['rising_knee_thrust',3],'open_the_center'=>['head_jab',1]];
foreach($combos as$base=>[$previous,$power])foreach(['red','yellow','blue']as$color){
    $reset();$attack=AddCombatChain(4,CardID:$base.'_'.$color,Owner:4,Controller:4);
    $state=FaBGetState();$state['attacker']=4;$state['previousAttackCardID']=$previous.'_blue';FaBSetState($state);
    FaBWTRAttackDeclared(4,$attack,1);
    $check(FaBWTRAttackPowerModifier(4,$attack,$state)===$power,$base.' '.$color.' combo power is incorrect.');
}
$reset();$weapon=AddWeapons(4,CardID:'romping_club',Owner:4,Controller:4);
FaBWTRCardDiscarded(4,'savage_swing_red');FaBWTRCardDiscarded(4,'raging_onslaught_red');
$check(count(array_filter($weapon->TurnEffects,fn($tag)=>$tag==='WTR_POWER:1'))===1,'Romping Club triggered more than once per turn.');
$reset();
$source=(object)['CardID'=>'bloodrush_bellow_yellow','Params'=>['discardedPower'=>0]];FaBWTRResolveCard(4,$source,null);
foreach(['romping_club','savage_swing_red']as$id){$attack=AddCombatChain(4,CardID:$id,Owner:4,Controller:4);$state=FaBGetState();$state['attacker']=4;$check(FaBWTRAttackPowerModifier(4,$attack,$state)===2,'Bloodrush Bellow did not buff every Brute attack.');}
$reset();
$state=FaBGetState();$state['attacker']=1;$state['defender']=4;$state['chainLink']=1;$state['window']='DEFEND_DECLARE';FaBSetState($state);
$equipment=AddEquipment(4,CardID:'goliath_gauntlet',Owner:4,Controller:4);$reaction=AddHand(4,CardID:'sink_below_red');$instant=AddHand(4,CardID:'sigil_of_solace_red');
$check(FaBCanBlock(4,'p4Equipment-0'),'Zero-defense equipment could not defend.');
$check(!FaBCanBlock(4,'p4Hand-0')&&!FaBCanBlock(4,'p4Hand-1'),'A defense reaction or card without defense could be declared as a normal block.');
$reset();
$p1a=AddPitch(1,CardID:'wounding_blow_red');$p1b=AddPitch(1,CardID:'wounding_blow_blue');
$p4=AddPitch(4,CardID:'wounding_blow_yellow');
for($i=0;$i<4;++$i)AddHand(1,CardID:'head_jab_red');
AddDeck(1,CardID:'head_jab_blue');
FaBEndTurn(1);
$check(GetDecisionQueue(1)[0]->Type==='MZREARRANGE','Multiple pitched cards did not offer an ordering choice.');
$check(GetDecisionQueue(1)[0]->Param==='Bottom=wounding_blow_red,wounding_blow_blue','Pitch ordering must offer only the bottom pile.');
$answer(1,'Bottom=wounding_blow_blue,wounding_blow_red');GameAfterEngineAction([],[]);
$check(GetDeck(1)[0]->CardID==='head_jab_blue'&&GetDeck(1)[1]->CardID==='wounding_blow_blue'&&GetDeck(1)[2]->CardID==='wounding_blow_red','Chosen pitch ordering was not applied below the existing deck.');
$check(empty(FaBChoiceRefs(4,'Pitch')),'Non-turn player pitch cards were not returned.');
$check(intval(GetTurnPlayer())===2,'Pitch choice did not finish the turn.');
if ($failures) { foreach ($failures as $failure) fwrite(STDERR,"FAIL: $failure\n"); exit(1); }
echo "WTR continuation and card-family checks passed.\n";
$reset();
AddHand(4,CardID:'heart_of_fyendal_blue');AddHand(4,CardID:'head_jab_red');
$check(FaBChoiceRefs(4,'Hand',['cost'=>0])===['p4Hand-1'],'A card without a cost was treated as a zero-cost Katsu discard.');
$kodachi=AddCombatChain(4,CardID:'harmonized_kodachi',Owner:4,Controller:4);AddPitch(4,CardID:'heart_of_fyendal_blue');
$state=FaBGetState();$state['attacker']=4;
$check(!FaBWTRAttackHasGoAgain($state,$kodachi),'A resource card without a cost granted Kodachi go again.');
$reset();
AddCombatChain(4,CardID:'sink_below_red',Owner:4,Controller:4,Role:'DEFENSE_REACTION',ChainLink:1,FromZone:'Hand');
AddCombatChain(4,CardID:'unmovable_red',Owner:4,Controller:4,Role:'DEFENSE_REACTION',ChainLink:1,FromZone:'Arsenal');
$state=FaBGetState();$state['chainLink']=1;
$check(FaBWTRNonEquipmentBlockCount($state)===2,'Barraging Beatdown did not count defense reactions.');
$reset();
SetTurnPlayer(4);SetCurrentPhase('SOT');
AddArena(4,CardID:'show_time_blue',Owner:4,Controller:4);AddDeck(4,CardID:'raging_onslaught_red');
FaBWTRAddEffect(4,'NO_DRAW_ACTION_PHASE',0,[],true);StartOfTurnPhase();
$check(FaBHandCount(4)===0&&empty(FaBChoiceRefs(4,'Arena')),'Cranial Crush did not prevent Show Time draw at the beginning of the action phase.');
if ($failures) { foreach ($failures as $failure) fwrite(STDERR,"FAIL: $failure\n"); exit(1); }
echo "WTR timing and property checks passed.\n";
