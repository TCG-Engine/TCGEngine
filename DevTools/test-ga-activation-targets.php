<?php
if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_targets_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function targetCheck($ok, $label) { global $checks; if(!$ok) throw new RuntimeException($label); ++$checks; echo "PASS $label\n"; }
function targetReset() {
    global $gameDir, $playerID;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir.'/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/'); $playerID = 1;
    foreach([1,2] as $p) foreach(['Field','Hand','Memory','Deck','Graveyard','Banish','DecisionQueue','GlobalEffects','Mastery'] as $zone) { $fn = 'Get'.$zone; $list = &$fn($p); $list = []; }
    $stack = &GetEffectStack(); $stack = [];
    SetMacroTurnIndex('{}'); SetMacroGameIndex('{}');
    MZAddZone(1,'myField',CardNameToID('Rai, Spellcrafter'));
    MZAddZone(2,'myField',CardNameToID('Spirit of Fire'));
}
try {
    targetReset();
    $a = MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $b = MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $id = GATargetIdentity($b->GetMzID());
    DecisionQueueController::StoreVariable('activationTargets', json_encode(['primary'=>[$id]]));
    $a->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    targetCheck(GAReadDeclaredTarget('primary', ZoneSearch('theirField',['ALLY'])) === 'theirField-1', 'Target follows physical identity through field compaction');
    $b->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    $replacement = MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    targetCheck(GAReadDeclaredTarget('primary', ZoneSearch('theirField',['ALLY'])) === '-', 'Same-name replacement in the old slot is not retargeted');
    $id = GATargetIdentity($replacement->GetMzID());
    DecisionQueueController::StoreVariable('activationTargets', json_encode(['primary'=>[$id]]));
    targetCheck(GAReadDeclaredTarget('primary', []) === '-', 'A target that is no longer legal is excluded');
    targetCheck(GAReadDeclaredTarget('secondary', ZoneSearch('theirField',['ALLY'])) === '-', 'Undeclared target groups do not inherit another choice');
    $id['amount'] = 3;
    DecisionQueueController::StoreVariable('activationTargets', json_encode(['split'=>[$id]]));
    targetCheck(GAReadDeclaredTarget('split', ZoneSearch('theirField',['ALLY']), true) === 'theirField-1:3', 'Split damage retains its declared allocation');
    targetReset();
    // Test the declaration flow with a side-effect-free database-shaped profile.
    $spellID = CardNameToID('Reposition');
    $originalRepositionProfile = $activationTargetsAbilities[$spellID.':0'] ?? null;
    $activationTargetsAbilities[$spellID.':0'] = fn($p)=>[GATargetSpec('primary', ZoneSearch('theirField',['ALLY']))];
    $source = MZAddZone(1,'myHand',$spellID);
    $victim = MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    DoActivateCard(1,'myHand-0',true);
    targetCheck(count(GetEffectStack()) === 0 && !$source->removed, 'Target declaration precedes payment and response priority');
    $dq = GetDecisionQueue(1);
    targetCheck($dq[0]->Type === 'MZCHOOSE', 'Activation offers the target declaration');
    $parts = explode('|', $dq[1]->Param); array_shift($parts);
    $queue = &GetDecisionQueue(1); $queue = [];
    $customDQHandlers['GADeclareTarget'](1,$parts,'theirField-0');
    targetCheck(empty(GADeclarationData($source)), 'Forged champion target is rejected for an ally-only action');
    $queue = [];
    $customDQHandlers['GADeclareTarget'](1,$parts,'theirField-1');
    $stack = GetEffectStack();
    targetCheck(count($stack) === 1 && $source->removed, 'Valid declaration announces the action once');
    targetCheck(intval(GADeclarationData($stack[0])['primary'][0]['uid']) === intval($victim->UniqueID), 'Declared identity travels with the stack entry');
    targetCheck(empty($stack[0]->Counters['activationDeclared']), 'Activation triggers wait until costs complete');
    targetReset();
    $nimue = MZAddZone(1,'myField','l52lVIFvpy');
    $victim = MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $originalNimueGranted = $grantedAbilityAbilities['l52lVIFvpy:0'] ?? null;
    $grantedAbilityAbilities['l52lVIFvpy:0'] = function($player) { GANimueDestroy($player, DecisionQueueController::GetVariable('nimueTargetUID')); };
    $targets = json_encode(['primary'=>[GATargetIdentity($victim->GetMzID())]]);
    DecisionQueueController::StoreVariable('ResolvingEffectStack','YES');
    GANimueQueue(1, $nimue->GetMzID(), $targets);

    $stack = GetEffectStack();
    targetCheck(count($stack) === 1 && $stack[0]->TriggerType === 'DTR_EVENT', 'Nimue creates an independent responseable trigger');
    $uid = $stack[0]->Counters['nimueTargetUID'];
    $nimue->removed = true;
    GANimueDestroy(1, $uid);
    targetCheck($victim->removed, 'Queued Nimue trigger survives its source leaving play');
    targetReset();
    $nimue = MZAddZone(1,'myField','l52lVIFvpy');
    $victim = MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    AddTurnEffect($nimue->GetMzID(),'NO_ABILITIES');
    GANimueQueue(1,$nimue->GetMzID(),json_encode(['primary'=>[GATargetIdentity($victim->GetMzID())]]));
    targetCheck(count(GetEffectStack()) === 0, 'Suppressed Nimue does not trigger');
    // CompileActivationTargetPreview.php builds these through the production await compiler.
    $preview = sys_get_temp_dir().'/ga-activation-target-preview.php';
    if(in_array('--generated',$argv,true)) {
        if($originalRepositionProfile === null || $originalNimueGranted === null) throw new RuntimeException('Regenerate from the hosted database first');
        $activationTargetsAbilities[$spellID.':0'] = $originalRepositionProfile;
        $grantedAbilityAbilities['l52lVIFvpy:0'] = $originalNimueGranted;
    } else {
        if(!is_file($preview)) throw new RuntimeException('Run CompileActivationTargetPreview.php first');
        require $preview;
    }
    GAInstallDeclaredTargetResolvers();
    targetReset();
    MZAddZone(1,'myField',CardNameToID('Esteemed Knight'));
    MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $plans = json_decode(file_get_contents('DevTools/card-code-migrations/20261021-activation-targets.json'),true)['cards'];
    set_error_handler(function($severity,$message,$file,$line) { if(error_reporting() & $severity) throw new RuntimeException("$message at $file:$line"); return true; });
    foreach($plans as $plan) {
        $hand = &GetHand(1); $hand = [];
        $source = MZAddZone(1,'myHand',$plan['card_id']);
        $source->Counters['activationTargets']='{}';
        $before = serialize([GetField(1),GetField(2),GetHand(1),GetMemory(1),GetGraveyard(1)]);
        try { $specs=GAActivationTargetSpecs(1,$source); }
        catch(Throwable $e) { throw new RuntimeException($plan['name'].': '.$e->getMessage(),0,$e); }
        targetCheck(is_array($specs) || $specs === null, $plan['name'].' declaration program executes');
        targetCheck($before === serialize([GetField(1),GetField(2),GetHand(1),GetMemory(1),GetGraveyard(1)]), $plan['name'].' declaration does not apply effects or costs');
    }
    restore_error_handler();
    targetReset();
    $source=MZAddZone(1,'myHand',CardNameToID('Harness Lightning'));
    $ally=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $args=[1,'myHand-0',true,null,null,null,null,null];
    GABeginTargetDeclaration(1,$source,$args);
    $q=GetDecisionQueue(1); targetCheck($q[0]->Type === 'MZMODAL','Mode is chosen before its target');
    $parts=explode('|',$q[1]->Param); array_shift($parts); $queue=&GetDecisionQueue(1);$queue=[];
    $customDQHandlers['GADeclareTarget'](1,$parts,'1');
    $q=GetDecisionQueue(1);targetCheck($q[0]->Type === 'MZCHOOSE','Targeting mode opens its own target declaration');
    $parts=explode('|',$q[1]->Param);array_shift($parts);$queue=[];
    $customDQHandlers['GADeclareTarget'](1,$parts,'theirField-1');
    targetCheck(count(GADeclarationData($source)['mode1'] ?? []) === 1,'Mode and target are stored independently');

    targetReset();
    $nimue=MZAddZone(1,'myField','l52lVIFvpy');
    $victim=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $identity=GATargetIdentity($victim->GetMzID());
    $targets=json_encode(['primary'=>[$identity], 'secondary'=>[$identity]]);
    DecisionQueueController::StoreVariable('ResolvingEffectStack','YES');
    DispatchMacroListeners('CardActivationDeclared',2,['type'=>'ACTION','targets'=>$targets],['Field']);
    targetCheck(count(GetEffectStack())===0,'Opponent action does not trigger Nimue');
    DispatchMacroListeners('CardActivationDeclared',1,['type'=>'ATTACK','targets'=>$targets],['Field']);
    targetCheck(count(GetEffectStack())===0,'Attack card does not trigger Nimue');
    DispatchMacroListeners('CardActivationDeclared',1,['type'=>'ACTION','targets'=>$targets],['Field']);
    targetCheck(count(GetEffectStack())===1,'Database listener queues one trigger for the same ally targeted twice');
    $nimue->removed=true;
    ResolveTopOfEffectStack();
    targetCheck($victim->removed,'Actual queued database ability resolves after Nimue leaves play');

    targetReset();
    MZAddZone(1,'myField','l52lVIFvpy');
    $victim=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $champ=GetZoneObject('myField-0'); $champ->CardID=CardNameToID('Spirit of Fire');
    DecisionQueueController::StoreVariable('ResolvingEffectStack','YES');
    DispatchMacroListeners('CardActivationDeclared',1,['type'=>'ACTION','targets'=>json_encode(['primary'=>[GATargetIdentity($victim->GetMzID())]])],['Field']);
    targetCheck(count(GetEffectStack())===0,'Nimue requires the Mage class bonus');

    targetReset();
    $action=AddEffectStack(CardID:CardNameToID('Reposition'),Controller:1,Counters:['activationTargets'=>'{}']);
    QueueTriggeredAbility(1,'l52lVIFvpy','DTR_EVENT',['nimueTargetUID'=>'999']);
    targetCheck(count(GetEffectStack())===1 && count(GetDecisionQueue(1))===0,'A cost trigger cannot open priority before payment finishes');
    targetCheck(count(json_decode($action->Counters['activationCostTriggers'],true))===1,'Cost trigger persists on its activating card');
    $action->Counters['activationDeclared']=1;
    $action->removed=true;

    targetReset();
    MZAddZone(1,'myField','l52lVIFvpy');
    $first=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $second=MZAddZone(1,'myField',CardNameToID('Esteemed Knight'));
    DecisionQueueController::StoreVariable('ResolvingEffectStack','YES');
    $cost=GATargetIdentity($second->GetMzID()); $cost['isTarget']=false;
    DispatchMacroListeners('CardActivationDeclared',1,['type'=>'ACTION','targets'=>json_encode(['primary'=>[GATargetIdentity($first->GetMzID())],'cost'=>[$cost]])],['Field']);
    targetCheck(count(GetEffectStack())===1,'An ally selected as a cost is not a Nimue target');
    $first->removed=true; DecisionQueueController::CleanupRemovedCards();
    $replacement=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    ResolveTopOfEffectStack();
    targetCheck(!$replacement->removed && !$second->removed,'Nimue neither retargets a replacement nor destroys a cost selection');
    targetReset();
    MZAddZone(1,'myField','l52lVIFvpy');
    $victim=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $action=AddEffectStack(CardID:CardNameToID('Reposition'),Controller:1,Counters:['activationDeclared'=>1]);
    DecisionQueueController::StoreVariable('ResolvingEffectStack','YES');
    DispatchMacroListeners('CardActivationDeclared',1,['type'=>'ACTION','targets'=>json_encode(['primary'=>[GATargetIdentity($victim->GetMzID())]])],['Field']);
    $action->removed=true; // Negating the original activation removes only its entry.
    ResolveTopOfEffectStack();
    targetCheck($victim->removed,'Nimue resolves even when the original action is removed from the stack');

    targetReset();
    MZAddZone(1,'myField','l52lVIFvpy');
    $one=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $two=MZAddZone(1,'myField',CardNameToID('Esteemed Knight'));
    DecisionQueueController::StoreVariable('ResolvingEffectStack','YES');
    BeginTriggeredAbilityBatch();
    DispatchMacroListeners('CardActivationDeclared',1,['type'=>'ACTION','targets'=>json_encode(['primary'=>[GATargetIdentity($one->GetMzID()),GATargetIdentity($two->GetMzID())]])],['Field']);
    EndTriggeredAbilityBatch();
    targetCheck(count(GetEffectStack())===2,'Each distinct targeted ally receives a destroy trigger');
    ResolveTopOfEffectStack(); ResolveTopOfEffectStack();
    targetCheck($one->removed && $two->removed,'Nimue destroys both friendly and opposing targeted allies');

    targetReset();
    MZAddZone(1,'myField','l52lVIFvpy');
    DispatchMacroListeners('CardActivationDeclared',1,['type'=>'ACTION','targets'=>'{"primary":[]}'],['Field']);
    targetCheck(count(GetEffectStack())===0,'Declining all optional targets creates no Nimue trigger');
    $victim=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    AddTurnEffect($victim->GetMzID(),'SPELLSHROUD');
    $source=MZAddZone(1,'myHand',CardNameToID('Reposition'));
    $specs=GAActivationTargetSpecs(1,$source);
    targetCheck(in_array($victim->GetMzID(),$specs[0]['targets'],true),'A Skill can declare a spellshroud ally as its target');

    targetReset();
    $source=MZAddZone(1,'myHand',CardNameToID('Reposition'));
    $victim=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $customDQHandlers['GAStarcallDeclare'](1,['myHand-0',$source->CardID,'0'],'-');
    $q=GetDecisionQueue(1); targetCheck($q[0]->Type==='MZCHOOSE','Starcalling declares targets before payment');
    $parts=explode('|',$q[1]->Param);array_shift($parts);$queue=&GetDecisionQueue(1);$queue=[];
    $customDQHandlers['GADeclareTarget'](1,$parts,'theirField-1');
    $stack=GetEffectStack();
    targetCheck(count($stack)===1 && ($stack[0]->Counters['wasStarcalled'] ?? '')==='YES','Starcalling carries its physical card and target onto the stack');
    targetCheck(intval(GADeclarationData($stack[0])['primary'][0]['uid'])===intval($victim->UniqueID),'Starcalling preserves the declared ally identity');
    targetReset();
    $victim=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    $identity=GATargetIdentity($victim->GetMzID());
    $action=AddEffectStack(CardID:CardNameToID('Verdigris Decree'),Controller:1,Counters:['activationTargets'=>json_encode(['mode'=>['mode'=>'0'],'mode0'=>[$identity]])]);
    DecisionQueueController::StoreVariable('isImbued','YES');
    targetCheck(GACompletePostCostTargets(1,$action),'Imbue permits an additional mode after reserve payment');
    $q=GetDecisionQueue(1); targetCheck($q[0]->Type==='MZMODAL','Imbue asks only for the additional mode');
    $parts=explode('|',$q[1]->Param);array_shift($parts);$queue=&GetDecisionQueue(1);$queue=[];
    $customDQHandlers['GADeclareTarget'](1,$parts,'0');
    $q=GetDecisionQueue(1);targetCheck($q[0]->Type==='MZMAYCHOOSE','The additional mode declares its own optional target');
    $parts=explode('|',$q[1]->Param);array_shift($parts);$queue=[];
    $customDQHandlers['GADeclareTarget'](1,$parts,'-');
    $data=GADeclarationData($action);
    targetCheck($data['mode']['mode']==='0,1' && $data['mode0'][0]['uid']===$identity['uid'] && $data['mode1']===[],'Imbue preserves the original mode and target while adding a mode');
    targetReset();
    $source=MZAddZone(1,'myHand',CardNameToID('Ignite the Soul'));
    $victim=MZAddZone(2,'myField',CardNameToID('Esteemed Knight'));
    DecisionQueueController::StoreVariable('peerDepthsIgnoreElement','YES');
    DecisionQueueController::StoreVariable('activationSourceZoneOverride','myBanish');
    DoActivateCard(1,'myHand-0',true);
    $q=GetDecisionQueue(1); targetCheck($q[0]->Type==='MZCHOOSE','An element-bypassing activation can declare its target');
    $parts=explode('|',$q[1]->Param);array_shift($parts);$queue=&GetDecisionQueue(1);$queue=[];
    DecisionQueueController::ClearVariable('peerDepthsIgnoreElement');
    $customDQHandlers['GADeclareTarget'](1,$parts,'theirField-1');
    targetCheck(count(GetEffectStack())===1 && $source->removed,'The element bypass survives the declaration choice frame');
    targetCheck(DecisionQueueController::GetVariable('peerDepthsIgnoreElement')===null,'The one-activation element bypass is consumed');
    echo "$checks activation targeting checks passed.\n";
} finally {
    foreach(glob($gameDir.'/*') ?: [] as $file) if(is_file($file)) unlink($file);
    @rmdir($gameDir);
}
