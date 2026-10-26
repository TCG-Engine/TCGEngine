<?php
// Isolated actual-function component: no game bootstrap, source writes or native game.
// CLI: php native_ability_frame_component.php [GameLogic.php] [mutant] [shared-extractor.php]
$source = $argv[1] ?? dirname(__DIR__,4).'/FaBSim/Custom/GameLogic.php';
$mutant = $argv[2] ?? 'none';
$extractor = $argv[3] ?? __DIR__.'/extract_component_functions.php';
$expectedSource = '706caa1ff28dedb0c9a0e3fdf2d7b8ca1deb02dfb6f131825b10b99ea8e372b3';
$expectedExtractor = 'ca10febfe5f102c0eca748c0fc82f83b0b1a814e4789d7f955b7fe82aca85199';
$zoneSource = $argv[4] ?? dirname(__DIR__,4).'/FaBSim/ZoneAccessors.php';
$rosSource = $argv[5] ?? dirname(__DIR__,4).'/FaBSim/Custom/ROSRuntime.php';
$expectedZone = '9967567a9a10df81a6f22df820d4d77258828657e95c4ad145b8b13ce17866a7';
$expectedROS = '3b8c1f2c03339b46500221a61c83a2aef06eb2845331c6bcf18c6f72287c61e1';
$allowedMutants = ['none','classifier-allowlist','frame-allowlist','ignore-frame-only'];
$component_checks = 0; $component_function_hashes = []; $completedContracts = [];
$activeContract = 'input guards';

function FaBGetState() { return $GLOBALS['componentState']; }
function FaBSetState($state) { $GLOBALS['componentState'] = $state; }
function GetDecisionQueueVariables() { return json_encode($GLOBALS['componentVariables']); }
function GetDecisionQueue($seat) { return $GLOBALS['componentQueues'][$seat]; }
function FaBSeatOrder() { return [1,2,3,4]; }
function FrameComponentReset(?array $pending = null): void {
    $GLOBALS['componentState'] = $pending === null ? [] : ['resolvingReaction'=>$pending];
    $GLOBALS['componentVariables'] = [];
    $GLOBALS['componentQueues'] = [1=>[],2=>[],3=>[],4=>[]];
    $GLOBALS['fabReactionResolutionDepth'] = 0;
}
function FrameComponentPending(): array {
    return ['resolutionClass'=>'ARC_ABILITY','kind'=>'ABILITY','cardID'=>'dreadbore',
        'sourceMZ'=>'p1Weapons-0','baselineFrameKeys'=>[],'ownedFrameKeys'=>[]];
}
function FrameComponentContract(string $name, callable $body): void {
    $GLOBALS['activeContract'] = $name;
    $body();
    $GLOBALS['completedContracts'][] = $name;
}
function FrameComponentLayer(string $kind='ABILITY', array $params=['arcSpec'=>[]], string $card='dreadbore'): object {
    return (object)['Kind'=>$kind,'CardID'=>$card,'Params'=>$params,'SourceZone'=>'Weapons','SourceUniqueID'=>5];
}
function FrameComponentSetFrame(string $key, string $sourceMZ='p1Weapons-0'): void {
    $GLOBALS['componentVariables']['__awaitFrames'][$key] = ['locals'=>['mzID'=>$sourceMZ]];
}


function FaBFindUID(int $uid): ?array { $r=$GLOBALS['drObjects'][$uid]??null;return $r!==null&&empty($r['object']->removed)?$r:null; }
function FaBAddToZone(string $zone,int $p,object $source): ?object {
    $o=clone $source;$o->removed=false;$o->Location=$zone;$o->mzIndex=0;
    $GLOBALS['drObjects'][intval($o->UniqueID)]=['object'=>$o,'zone'=>$zone,'player'=>$p,'mzID'=>'p'.$p.$zone.'-0'];
    DRTrace('zone_add',['uid'=>intval($o->UniqueID),'to'=>$zone,'player'=>$p,'owner'=>intval($o->Owner),'controller'=>intval($o->Controller)]);return $o;
}
function FaBARCCard($uid,$key,$default=0) {return $default;}
function FaBWTRBase(string $cid): string {return preg_replace('/_(red|yellow|blue)$/','',$cid);}
function FaBHasType($o,string $type): bool {return is_object($o)&&in_array($type,(array)($o->types??[]),true);}
function FaBHasKeyword($o,string $keyword): bool {return false;}
function EffectiveCardType(object $o): array {return (array)($o->types??[]);}
function FaBMPGSuppressed(object $o): bool {return false;}
function FaBSUPSuppressed(object $o): bool {return !empty($GLOBALS['drCannotGain']);}
function FaBMSTHidden(object $o): bool {return false;}
function FaBWTRHeroActive(int $p): bool {return true;}
function FaBPrintedKeywordIsActive(string $cid,string $keyword): bool {return !empty($GLOBALS['drPrintedGA']);}
function EvaluateGoAgainModifier(...$args): int {return 0;}
function FaBUPRFog(): bool {return !empty($GLOBALS['drFog']);}
function FaBWTRMayGoAgain(int $p): bool {return empty($GLOBALS['drNoGoAgain']);}
function FaBSeatIsLive(int $p): bool {return in_array($p,[1,2,3,4],true);}
// Controlled existing game-clock boundary; all reviewed fixtures are Turn 2.
function GetTurnNumber(): int {return 2;}
function GetTurnPlayer(): int {return $GLOBALS['drTurnPlayer'];}
function GetCurrentPhase(): string {return 'MAIN';}
function &GetActionPoints($p) {return $GLOBALS['drAP'][intval($p)];}
function FaBROSCount($p,$key): int {++$GLOBALS['drROSCountCalls'];return 0;}
function FaBROSStoredSource($p,$key): int {return 0;}
function FaBROSQueue(...$args): void {DRTrace('ros_queue');}
function SetPriorityPlayer($p): void {$GLOBALS['drPriority']=intval($p);DRTrace('priority',['player'=>intval($p)]);}
function SetConsecutivePasses($n): void {$GLOBALS['drPasses']=intval($n);}
function FaBStackTop(): ?object {foreach($GLOBALS['drObjects']as$r)if($r['zone']==='Stack'&&empty($r['object']->removed))return $r['object'];return null;}
function DRTrace(string $event,array $data=[]): void {
    $GLOBALS['drTrace'][]=['event'=>$event,'pending'=>is_array(FaBGetState()['resolvingReaction']??null),'depth'=>intval($GLOBALS['fabReactionResolutionDepth']??0)]+$data;
}
function DRMoveHook(...$args): void {
    DRTrace('move_hook');$cb=$GLOBALS['drMoveCallback']??null;$GLOBALS['drMoveCallback']=null;if(is_callable($cb))$cb();
}
foreach(['FaBCRUAfterMove','FaBEVRAfterMove','FaBMONAfterMove','FaBSEAAfterMove','FaBMPGAfterMove','FaBSUPAfterMove','FaBPENAfterMove','FaBOMNAfterMove','FaBIARAfterMove']as$name)eval('function '.$name.'(...$args): void {DRMoveHook(...$args);}');
function FaBWTRApplyNextDefense(int $p,object $o): void {++$GLOBALS['drNextDefenseCalls'];DRTrace('apply_next_defense',['uid'=>intval($o->UniqueID),'role'=>$o->Role,'link'=>$o->ChainLink,'from'=>$o->FromZone]);}
function OnDefended($p,$mz,$defender): void {
    ++$GLOBALS['drDefendedCalls'];DRTrace('on_defended',['player'=>intval($p),'mzID'=>$mz,'defender'=>intval($defender)]);
    $cb=$GLOBALS['drDefendedCallback']??null;$GLOBALS['drDefendedCallback']=null;if(is_callable($cb))$cb();
}
function DRObject(int $uid,string $cid,string $zone,int $player,array $fields=[]): void {
    $o=(object)array_replace(['UniqueID'=>$uid,'CardID'=>$cid,'Owner'=>$player,'Controller'=>$player,'removed'=>false,'TurnEffects'=>[],'Counters'=>[],'types'=>['Defense Reaction']],$fields);
    $GLOBALS['drObjects'][$uid]=['object'=>$o,'zone'=>$zone,'player'=>$zone==='Stack'?0:$player,'mzID'=>$zone==='Stack'?'Stack-0':'p'.$player.$zone.'-0'];
}
function DRFixture(int $owner=4): void {
    FrameComponentReset();$GLOBALS['drObjects']=[];$GLOBALS['drTrace']=[];$GLOBALS['drNextDefenseCalls']=0;$GLOBALS['drDefendedCalls']=0;
    $GLOBALS['drCannotGain']=false;$GLOBALS['drPrintedGA']=false;$GLOBALS['drNoGoAgain']=false;$GLOBALS['drFog']=false;
    $GLOBALS['drTurnPlayer']=1;$GLOBALS['drAP']=[1=>0,2=>0,3=>0,4=>0];$GLOBALS['drROSCountCalls']=0;
    $GLOBALS['drMoveCallback']=null;$GLOBALS['drDefendedCallback']=null;$GLOBALS['drPriority']=0;$GLOBALS['drPasses']=4;
    DRObject(7,'raging_onslaught_red','CombatChain',1,['types'=>['Generic','Action','Attack'],'Role'=>'ATTACK','ChainLink'=>1]);
    DRObject(5,'take_cover_red','Stack',4,['Owner'=>$owner,'Controller'=>4,'Kind'=>'DEFENSE_REACTION','SourceZone'=>'Hand','SourceUniqueID'=>5]);
    $pending=['uid'=>5,'owner'=>$owner,'controller'=>4,'kind'=>'DEFENSE_REACTION','resolutionClass'=>'DEFENSE_REACTION','completionSource'=>(array)FaBFindUID(5)['object'],
        'cardID'=>'take_cover_red','sourceMZ'=>'Stack-0','chainLink'=>1,'attackUID'=>7,'fromZone'=>'Hand','baselineFrameKeys'=>[],'ownedFrameKeys'=>[]];
    FaBSetState(['window'=>'REACTION','combatOpen'=>true,'combatStep'=>'REACTION','chainLink'=>1,'attackUID'=>7,'attacker'=>1,'defender'=>4,'handBlockUIDs'=>[],'resolvingReaction'=>$pending]);
}
function DRCheckUnfinished(): void {
    ComponentCheck(FaBFindUID(5)['zone']==='Stack'&&is_array(FaBGetState()['resolvingReaction']??null),'physical DR stays Stack with marker');
    ComponentCheck($GLOBALS['drNextDefenseCalls']===0&&$GLOBALS['drDefendedCalls']===0&&FaBGetState()['handBlockUIDs']===[],'no early defender bookkeeping/hooks');
    ComponentCheck($GLOBALS['drAP']===[1=>0,2=>0,3=>0,4=>0],'no early go-again AP');
}
function DRCheckCompleted(int $owner=4): void {
    $r=FaBFindUID(5);ComponentCheck($r!==null&&$r['zone']==='CombatChain'&&$r['player']===4&&$r['object']->Owner===$owner&&$r['object']->Controller===4,'same UID/owner/controller becomes defense');
    ComponentCheck($r['object']->Role==='DEFENSE_REACTION'&&$r['object']->ChainLink===1&&$r['object']->FromZone==='Hand','exact completed defense role/link/origin');
    ComponentCheck($GLOBALS['drNextDefenseCalls']===1&&$GLOBALS['drDefendedCalls']===1&&FaBGetState()['handBlockUIDs']===[5],'next defense/OnDefended/hand bookkeeping once');
    ComponentCheck(!isset(FaBGetState()['resolvingReaction'])&&!FaBReactionResolutionIsPending()&&$GLOBALS['drPriority']===$GLOBALS['drTurnPlayer']&&$GLOBALS['drPasses']===0,'finisher clears marker and restores turn-player priority');
    ComponentCheck(FaBGetState()['window']==='REACTION'&&FaBGetState()['combatOpen']===true,'combat response flow retained');
}

try {
    if (PHP_SAPI !== 'cli') throw new RuntimeException('CLI only.');
    if (!in_array($mutant, $allowedMutants, true)) throw new RuntimeException('Unknown mutant.');
    if (!is_file($source) || hash_file('sha256',$source) !== $expectedSource) throw new RuntimeException('Reviewed GameLogic byte hash mismatch.');
    if (!is_file($extractor) || hash_file('sha256',$extractor) !== $expectedExtractor) throw new RuntimeException('Reviewed shared extractor byte hash mismatch.');
    if (!is_file($zoneSource)||hash_file('sha256',$zoneSource)!==$expectedZone) throw new RuntimeException('Reviewed ZoneAccessors byte hash mismatch.');
    if (!is_file($rosSource)||hash_file('sha256',$rosSource)!==$expectedROS) throw new RuntimeException('Reviewed ROSRuntime byte hash mismatch.');
    require $extractor;
    foreach (['FaBManagedVirtualResolutionClass','FaBTrackReactionAwaitFrames',
        'FaBReactionResolutionHasWork','FaBReactionResolutionIsPending'] as $name) {
        $changes = [];
        if ($name === 'FaBManagedVirtualResolutionClass' && $mutant === 'classifier-allowlist') {
            $changes["&& empty(\$stack->Params['rosTrigger'])) return 'ARC_ABILITY';"] =
                "&& empty(\$stack->Params['rosTrigger']) && in_array((string)\$stack->CardID, ['opal_amulet_blue','potion_of_deja_vu_blue'], true)) return 'ARC_ABILITY';";
        }
        if ($name === 'FaBTrackReactionAwaitFrames' && $mutant === 'frame-allowlist') {
            $changes["&& (\$pending['kind'] ?? '') === 'ABILITY';"] =
                "&& (\$pending['kind'] ?? '') === 'ABILITY' && in_array((string)\$pending['cardID'], ['opal_amulet_blue','potion_of_deja_vu_blue'], true);";
        }
        if ($name === 'FaBReactionResolutionHasWork' && $mutant === 'ignore-frame-only') {
            $changes["return is_array(\$variables) && !empty(\$variables['__awaitFrames']);"] = 'return false;';
        }
        ComponentLoadFunction($source,$name,$changes);
    }

    foreach (['FaBFinishReactionResolution','FaBFinishDefenseReactionLayer','FaBMoveStackUID','FaBMoveUID',
        'FaBBeforeActionPoints','HasNoAbilities','FaBObjectCounters'] as $name) ComponentLoadFunction($source,$name);
    ComponentLoadFunction($zoneSource,'AddActionPoints');
    ComponentLoadFunction($rosSource,'FaBROSGo');

    FrameComponentContract('01 native Dreadbore arcSpec ability admitted', function() {
        ComponentCheck(FaBManagedVirtualResolutionClass(FrameComponentLayer()) === 'ARC_ABILITY','native admission');
    });
    FrameComponentContract('02 admission uses native layer protocol rather than card allowlist', function() {
        foreach (['native_component_sentinel','opal_amulet_blue','potion_of_deja_vu_blue'] as $card)
            ComponentCheck(FaBManagedVirtualResolutionClass(FrameComponentLayer(card:$card)) === 'ARC_ABILITY','protocol-based admission');
    });
    FrameComponentContract('03 rosTrigger no-arcSpec and non-ability layers stay excluded', function() {
        foreach ([FrameComponentLayer(params:['arcSpec'=>[],'rosTrigger'=>true]),
            FrameComponentLayer(params:[]),FrameComponentLayer('ATTACK'),
            FrameComponentLayer('WEAPON'),FrameComponentLayer('ACTION'),FrameComponentLayer('TRIGGER')] as $layer)
            ComponentCheck(FaBManagedVirtualResolutionClass($layer) === '','separate layer path');
    });
    FrameComponentContract('04 marked Runechant trigger predicate remains exact', function() {
        $r = FrameComponentLayer('TRIGGER',['arcRunechant'=>true],'runechant'); $r->SourceZone='Arena';
        ComponentCheck(FaBManagedVirtualResolutionClass($r) === 'RUNECHANT_TRIGGER','marked Runechant');
        foreach (['unmarked','wrong-zone','zero-source','wrong-card'] as $variant) {
            $v=clone $r;
            if ($variant==='unmarked') $v->Params=[];
            if ($variant==='wrong-zone') $v->SourceZone='Graveyard';
            if ($variant==='zero-source') $v->SourceUniqueID=0;
            if ($variant==='wrong-card') $v->CardID='component_other';
            ComponentCheck(FaBManagedVirtualResolutionClass($v) === '','Runechant boundary');
        }
    });
    FrameComponentContract('05 actual tracker owns new Dreadbore ResolveAbility frame', function() {
        FrameComponentReset(FrameComponentPending());
        FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');
        FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === ['dreadbore:0:ResolveAbility#1'],'new native ability frame ownership');
    });
    FrameComponentContract('06 pre-existing baseline frame stays unowned', function() {
        $p=FrameComponentPending();$p['baselineFrameKeys']=['dreadbore:0:ResolveAbility#1'];
        FrameComponentReset($p);FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'baseline exclusion');
    });
    FrameComponentContract('07 foreign card namespace stays unowned', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('opal_amulet_blue:0:ResolveAbility#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'namespace exclusion');
    });
    FrameComponentContract('08 ResolveCard frame cannot impersonate native ability frame', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('dreadbore:0:ResolveCard#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'macro namespace exclusion');
    });
    FrameComponentContract('09 matching namespace with foreign physical source stays unowned', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('dreadbore:0:ResolveAbility#1','p2Weapons-0');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === [],'physical source exclusion');
    });
    FrameComponentContract('10 repeated tracking deduplicates while retaining earlier owned keys', function() {
        $p=FrameComponentPending();$p['ownedFrameKeys']=['dreadbore:0:ResolveAbility#1'];
        FrameComponentReset($p);FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');FrameComponentSetFrame('dreadbore:0:ResolveAbility#2');
        FaBTrackReactionAwaitFrames();FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === ['dreadbore:0:ResolveAbility#1','dreadbore:0:ResolveAbility#2'],'deduplicated owned frames');
    });
    FrameComponentContract('11 Runechant marker still tracks ResolveCard not ResolveAbility', function() {
        $p=FrameComponentPending();$p['resolutionClass']='RUNECHANT_TRIGGER';$p['kind']='TRIGGER';$p['cardID']='runechant';$p['sourceMZ']='Stack-0';
        FrameComponentReset($p);FrameComponentSetFrame('runechant:0:ResolveCard#1','Stack-0');FrameComponentSetFrame('runechant:0:ResolveAbility#1','Stack-0');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys'] === ['runechant:0:ResolveCard#1'],'unchanged trigger macro ownership');
    });
    FrameComponentContract('12 frame-only work holds resolution with every decision queue empty', function() {
        FrameComponentReset(FrameComponentPending());FrameComponentSetFrame('dreadbore:0:ResolveAbility#1');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBReactionResolutionHasWork(),'actual completion work gate sees frame without queue mask');
        ComponentCheck(FaBReactionResolutionIsPending(),'serialized resolution marker is pending');
    });
    FrameComponentContract('13 any seat static decision row holds completion', function() {
        FrameComponentReset();$GLOBALS['componentQueues'][4]=[(object)['Type'=>'CUSTOM','Param'=>'component-static']];
        ComponentCheck(FaBReactionResolutionHasWork(),'fourth-seat static work');
    });
    FrameComponentContract('14 callback depth independently prevents premature completion', function() {
        FrameComponentReset();$GLOBALS['fabReactionResolutionDepth']=1;
        ComponentCheck(FaBReactionResolutionHasWork() && FaBReactionResolutionIsPending(),'callback depth guards');
    });
    FrameComponentContract('15 drained queues and frames release work gate but preserve marker until finisher', function() {
        FrameComponentReset(FrameComponentPending());
        ComponentCheck(!FaBReactionResolutionHasWork() && FaBReactionResolutionIsPending(),'work gate drained while marker awaits actual finisher');
        FrameComponentReset();ComponentCheck(!FaBReactionResolutionIsPending(),'no marker or callback is pending');
    });

    FrameComponentContract('16 actual tracker owns DR ResolveCard source not foreign namespace', function() {
        DRFixture();FrameComponentSetFrame('take_cover_red:0:ResolveCard#1','Stack-0');FrameComponentSetFrame('take_cover_red:0:ResolveAbility#1','Stack-0');FrameComponentSetFrame('take_cover_yellow:0:ResolveCard#1','Stack-0');FaBTrackReactionAwaitFrames();
        ComponentCheck(FaBGetState()['resolvingReaction']['ownedFrameKeys']===['take_cover_red:0:ResolveCard#1'],'DR frame ownership exact');
    });
    FrameComponentContract('17 actual queued Reload body holds source and every completion effect', function() {
        DRFixture();$GLOBALS['componentQueues'][4]=[(object)['Type'=>'MZMAYCHOOSE'],(object)['Type'=>'CUSTOM']];FrameComponentSetFrame('take_cover_red:0:ResolveCard#1','Stack-0');FaBTrackReactionAwaitFrames();
        ComponentCheck(!FaBFinishReactionResolution(),'queued unresolved body cannot complete');DRCheckUnfinished();
    });
    FrameComponentContract('18 actual frame-only gate holds DR after queue drains', function() {
        DRFixture();FrameComponentSetFrame('take_cover_red:0:ResolveCard#1','Stack-0');FaBTrackReactionAwaitFrames();ComponentCheck(!FaBFinishReactionResolution(),'frame-only DR work lost');DRCheckUnfinished();$GLOBALS['componentVariables']=[];ComponentCheck(FaBFinishReactionResolution(),'drained DR did not complete');DRCheckCompleted();
    });
    FrameComponentContract('19 synchronous DR completion moves and defends once with callback reentry held', function() {
        DRFixture();$GLOBALS['drMoveCallback']=function(){ComponentCheck(FaBReactionResolutionIsPending(),'move hook lost depth ownership');ComponentCheck(!FaBFinishReactionResolution(),'move hook reentry completed twice');};$GLOBALS['drDefendedCallback']=function(){ComponentCheck(FaBReactionResolutionIsPending(),'defend hook lost depth ownership');ComponentCheck(!FaBFinishReactionResolution(),'defend hook reentry completed twice');};
        ComponentCheck(FaBFinishReactionResolution(),'sync DR completion failed');DRCheckCompleted();ComponentCheck(!FaBFinishReactionResolution()&&$GLOBALS['drNextDefenseCalls']===1&&$GLOBALS['drDefendedCalls']===1,'repeat completion duplicated defending effects');
    });
    FrameComponentContract('20 foreign owner is retained on controllers completed chain', function() {
        DRFixture(2);ComponentCheck(FaBFinishReactionResolution(),'foreign-owner DR completion failed');DRCheckCompleted(2);
    });
    FrameComponentContract('21 actual self-move captures latest LKI and never resurrects source', function() {
        DRFixture();$GLOBALS['drTurnPlayer']=4;FaBFindUID(5)['object']->TurnEffects=['NO_ABILITIES','GO_AGAIN'];$GLOBALS['fabReactionResolutionDepth']=1;
        ComponentCheck(FaBMoveUID(5,'Hand')!==null,'own resolving DR cannot use existing self-move API');$GLOBALS['fabReactionResolutionDepth']=0;
        ComponentCheck(FaBGetState()['resolvingReaction']['completionSource']['TurnEffects']===['NO_ABILITIES','GO_AGAIN'],'departure LKI lost later grant');
        ComponentCheck(FaBFinishReactionResolution(),'self-moved DR did not finish');ComponentCheck(FaBFindUID(5)['zone']==='Hand'&&$GLOBALS['drDefendedCalls']===0&&$GLOBALS['drNextDefenseCalls']===0,'self-moved source resurrected as defender');ComponentCheck($GLOBALS['drAP'][4]===1,'LKI granted GA lost under ordinary ability loss');
    });
    FrameComponentContract('22 closed or replaced chain clears remaining source to its owner', function() {
        foreach(['closed','chain','attack']as$mode){DRFixture(2);$s=FaBGetState();if($mode==='closed')$s['combatOpen']=false;if($mode==='chain')$s['chainLink']=2;if($mode==='attack')$s['attackUID']=99;FaBSetState($s);ComponentCheck(FaBFinishReactionResolution(),'stale chain DR cannot finish');$r=FaBFindUID(5);ComponentCheck($r!==null&&$r['zone']==='Graveyard'&&$r['player']===2&&$r['object']->Owner===2,'stale chain resurrected defender/wrong owner clear');ComponentCheck($GLOBALS['drDefendedCalls']===0&&$GLOBALS['drNextDefenseCalls']===0,'stale chain emitted defender event');}
    });
    FrameComponentContract('23 ordinary ability loss preserves granted GA and nonapplicable Fog does not block DR', function() {
        DRFixture();$GLOBALS['drTurnPlayer']=4;$GLOBALS['drFog']=true;FaBFindUID(5)['object']->TurnEffects=['NO_ABILITIES','GO_AGAIN'];ComponentCheck(FaBFinishReactionResolution(),'granted GA DR did not finish');DRCheckCompleted();ComponentCheck($GLOBALS['drAP'][4]===1,'ordinary base ability loss or nonapplicable Fog removed granted DR GA');
    });
    FrameComponentContract('24 explicit cannot-gain and no-go-again restrictions still apply', function() {
        foreach(['cannot_gain','no_ga']as$mode){DRFixture();$GLOBALS['drTurnPlayer']=4;FaBFindUID(5)['object']->TurnEffects=['GO_AGAIN'];if($mode==='cannot_gain')$GLOBALS['drCannotGain']=true;else $GLOBALS['drNoGoAgain']=true;ComponentCheck(FaBFinishReactionResolution(),'restricted DR did not complete');ComponentCheck($GLOBALS['drAP'][4]===0,'explicit restriction allowed go-again AP');}
    });
    FrameComponentContract('25 actual off-turn AP hook and ROS guard prevent off-turn benefit', function() {
        DRFixture();FaBFindUID(5)['object']->TurnEffects=['GO_AGAIN'];ComponentCheck(FaBFinishReactionResolution(),'off-turn DR did not finish');DRCheckCompleted();ComponentCheck($GLOBALS['drAP'][4]===0&&$GLOBALS['drROSCountCalls']===0,'raw AP stub or off-turn ROS activity fabricated benefit');
    });
    FrameComponentContract('26 printed ability loss without a separate grant does not grant GA', function() {
        DRFixture();$GLOBALS['drTurnPlayer']=4;$GLOBALS['drPrintedGA']=true;FaBFindUID(5)['object']->TurnEffects=['NO_ABILITIES'];ComponentCheck(FaBFinishReactionResolution(),'blank DR did not finish');ComponentCheck($GLOBALS['drAP'][4]===0,'lost printed GA granted AP');
    });
    FrameComponentContract('27 actual body depth blocks early completion before any queue exists', function() {
        DRFixture();$GLOBALS['fabReactionResolutionDepth']=1;ComponentCheck(!FaBFinishReactionResolution(),'body-depth completion escaped');DRCheckUnfinished();$GLOBALS['fabReactionResolutionDepth']=0;ComponentCheck(FaBFinishReactionResolution(),'body return never completed');DRCheckCompleted();
    });
    FrameComponentContract('28 other Stack kinds remain rejected while prior AR lifecycle remains supported', function() {
        DRFixture();FaBFindUID(5)['object']->Kind='ABILITY';ComponentCheck(FaBMoveUID(5,'Hand')===null&&FaBFindUID(5)['zone']==='Stack','general Stack movement permission expanded');
        DRFixture();$s=FaBGetState();unset($s['resolvingReaction']['resolutionClass']);$s['resolvingReaction']['kind']='ATTACK_REACTION';FaBSetState($s);FaBFindUID(5)['object']->Kind='ATTACK_REACTION';ComponentCheck(FaBFinishReactionResolution(),'existing AR completion failed');ComponentCheck(FaBFindUID(5)['zone']==='Graveyard'&&$GLOBALS['drDefendedCalls']===0&&$GLOBALS['drNextDefenseCalls']===0,'existing AR wrongly became defense');
        DRFixture();$s=FaBGetState();unset($s['resolvingReaction']['resolutionClass']);FaBSetState($s);FaBFindUID(5)['object']->Kind='ATTACK_REACTION';ComponentCheck(FaBMoveUID(5,'Hand')!==null,'existing AR self-move was rejected');
    });

    echo json_encode(['status'=>'PASS','contracts'=>count($completedContracts),'checks'=>$component_checks,
        'mutant'=>$mutant,'source_sha256'=>$expectedSource,'extractor_sha256'=>$expectedExtractor,'zone_sha256'=>$expectedZone,'ros_sha256'=>$expectedROS,
        'actual_functions'=>$component_function_hashes,'completed_contracts'=>$completedContracts,
        'scope'=>'Actual extracted admission/tracker/work/completion/self-move/LKI/AP-hook functions; external state, zone/card-rule/hooks and queue boundaries are controlled. No native game, full entry/macro transport, token factory or per-card qualification.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR,json_encode(['status'=>'FAIL','mutant'=>$mutant,'failed_contract'=>$activeContract,
        'error'=>$error->getMessage(),'completed_contracts'=>$completedContracts,
        'actual_functions'=>$component_function_hashes],JSON_UNESCAPED_SLASHES).PHP_EOL);
    exit(1);
}
