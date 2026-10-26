<?php
// Guard and extract actual source functions only; no engine bootstrap or game writes.
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);
$paths=['FaBSim/Custom/GameLogic.php','FaBSim/Custom/CRUCards.php','FaBSim/Custom/EVRCards.php','FaBSim/Custom/ROSCards.php','FaBSim/Custom/ARCCards.php','FaBSim/Custom/WTRCards.php'];
$sourcePaths=array_combine($paths,array_map(fn($path)=>$repo.'/'.$path,$paths));
$sourcePaths['FaBSim/Custom/ROSCards.php']=$argv[8]??$sourcePaths['FaBSim/Custom/ROSCards.php'];
foreach($paths as $i=>$path){
 $expected=$argv[$i+2]??'';
 if(!preg_match('/^[a-f0-9]{64}$/',$expected)||!hash_equals($expected,hash_file('sha256',$sourcePaths[$path])))throw new RuntimeException('Supply current GameLogic, CRU, EVR, ROS, ARC and WTR SHA256 arguments: '.$path);
}
$extractor=$argv[9]??__DIR__.'/extract_component_functions.php';
if(!hash_equals('ca10febfe5f102c0eca748c0fc82f83b0b1a814e4789d7f955b7fe82aca85199',hash_file('sha256',$extractor)))throw new RuntimeException('Reviewed extractor hash mismatch.');
require $extractor;
$bindings=[
 'FaBCRUHit'=>'FaBSim/Custom/CRUCards.php',
 'FaBEVRDestroyed'=>'FaBSim/Custom/EVRCards.php',
 'FaBROSQueue'=>'FaBSim/Custom/ROSCards.php',
 'FaBROSResolveTrigger'=>'FaBSim/Custom/ROSCards.php',
 'FaBARCCreateRunes'=>'FaBSim/Custom/ARCCards.php',
 'DoResolveCard'=>'FaBSim/Custom/GameLogic.php',
 'FaBManagedAbilityResolutionOwns'=>'FaBSim/Custom/GameLogic.php',
 'FaBManagedVirtualResolutionClass'=>'FaBSim/Custom/GameLogic.php',
 'FaBROSAdd'=>'FaBSim/Custom/ROSCards.php',
 'FaBROSPrevent'=>'FaBSim/Custom/ROSCards.php',
 'FaBROSPreventionTokenOrigin'=>'FaBSim/Custom/ROSCards.php',
 'FaBROSPreventionTokenPayload'=>'FaBSim/Custom/ROSCards.php',
 'FaBROSResolvePreventedToken'=>'FaBSim/Custom/ROSCards.php',
 'FaBWTRAddEffect'=>'FaBSim/Custom/WTRCards.php',
 'FaBWTREffects'=>'FaBSim/Custom/WTRCards.php',
 'FaBWTRSetEffects'=>'FaBSim/Custom/WTRCards.php',
];
$extracted=[];
foreach($bindings as $name=>$file){$code=ComponentExtractFunction($sourcePaths[$file],$name);$extracted[$name]=hash('sha256',$code);eval($code);}

function contractReset(): void {$GLOBALS['cx']=['state'=>['pendingPayment'=>null,'window'=>'DAMAGE','combatOpen'=>true,'combatStep'=>'DAMAGE'],'stack'=>[],'nextUID'=>8,'tokens'=>[],'macros'=>[],'blank'=>false,'hitSuppressed'=>false,'creationModifier'=>0,'sourcePresent'=>true,'priority'=>4,'passes'=>3,'turnPlayer'=>1,'phantasmDestroys'=>true];}
function contractAssert(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function contractCase(string $name,callable $fn): void {contractReset();$fn();$GLOBALS['passed'][]=$name;}
function contractCard(string $id='amplify_the_arknight_red',int $uid=6,array $tags=[]): object {return(object)['CardID'=>$id,'UniqueID'=>$uid,'Owner'=>1,'Controller'=>1,'TurnEffects'=>$tags,'Params'=>[],'Kind'=>'ATTACK','removed'=>false];}
function FaBWTRBase(string $id): string {return preg_replace('/_(red|yellow|blue)$/','',$id);}
function FaBWTRIsAttackAction(object $o): bool {return true;}
function FaBWTRIsWeapon(object $o): bool {return false;}
function FaBCRUHero(int $p,string $id): bool {return false;}
function FaBCRUCount(int $p,string $key): int {return 0;}
function FaBCRUHitSuppressed(object $o,bool $all=false): bool {return $GLOBALS['cx']['hitSuppressed'];}
function HasNoAbilities(object $o): bool {return $GLOBALS['cx']['blank'];}
function FaBHasType($o,string $type): bool {if(is_string($o))return $type==='Token'&&in_array($o,['runechant','ponder'],true);return $type==='Illusionist'&&in_array(FaBWTRBase($o->CardID),['phantasmal_haze','coalescence_mirage','miraging_metamorph'],true);}
function FaBSeatIsLive(int $p): bool {return $p>=1&&$p<=4;}
function FaBGetState(): array {return $GLOBALS['cx']['state'];}
function FaBSetState(array $state): void {$GLOBALS['cx']['state']=$state;}
function AddStack(string $CardID,int $Controller,string $Kind,int $SourceUniqueID=0,array $Params=[]): object {$callback=$GLOBALS['cx']['queueCallback']??null;$GLOBALS['cx']['queueCallback']=null;if(is_callable($callback))$callback();$o=(object)['CardID'=>$CardID,'Controller'=>$Controller,'Kind'=>$Kind,'SourceUniqueID'=>$SourceUniqueID,'Params'=>$Params,'UniqueID'=>$GLOBALS['cx']['nextUID']++,'removed'=>false];$GLOBALS['cx']['stack'][]=$o;return $o;}
function FaBStackTop(): ?object {foreach(array_reverse($GLOBALS['cx']['stack'])as$o)if(empty($o->removed))return $o;return null;}
function FaBFindUID(int $uid): ?array {if(isset($GLOBALS['cx']['abilityObjects']))return $GLOBALS['cx']['abilityObjects'][$uid]??null;return $GLOBALS['cx']['sourcePresent']?['mzID'=>'p1Graveyard-0','zone'=>'Graveyard','player'=>1,'object'=>contractCard('phantasmal_haze_red',$uid)]:null;}
function FaBARCEffect(int $p,string $key): int {contractAssert($key==='ARC_MORDRED','Only Rune creation modifier expected');return $GLOBALS['cx']['creationModifier'];}
function FaBHVYToken(int $p,string $id,int $amount,?int $controller=null,?bool $action=null,bool $wager=false,?int $owner=null): void {$GLOBALS['cx']['tokens'][]=['player'=>$p,'card'=>$id,'amount'=>$amount];$GLOBALS['cx']['factoryCalls'][]=compact('p','id','amount','controller','action','wager','owner');}
function FaBWTRCreateArena(int $p,string $id): void {$GLOBALS['cx']['tokens'][]=['player'=>$p,'card'=>$id,'amount'=>1];}
function FaBRunSourceMacro(string $macro,int $p,string $id,array $params=[]): void {$GLOBALS['cx']['macros'][]=compact('macro','p','id','params');}
function FaBReactionResolutionIsPending(): bool {return false;}
function FaBIdentityFromMZ(string $mz): ?array {if($mz!=='Stack-0')throw new RuntimeException('Unexpected source ref');return ['zone'=>'Stack','object'=>$GLOBALS['cx']['stack'][0]];}
function SetPriorityPlayer(int $p): void {$GLOBALS['cx']['priority']=$p;}
function SetConsecutivePasses(int $n): void {$GLOBALS['cx']['passes']=$n;}
function GetTurnPlayer(): int {return $GLOBALS['cx']['turnPlayer'];}
function FaBMONPhantasm(array $state,bool $resolve=true): bool {
 if(!$GLOBALS['cx']['phantasmDestroys'])return false;
 // Deliberate boundary double: actual Haze producer queues, then modeled chain closure writes ACTION.
 $GLOBALS['cx']['sourcePresent']=false;
 FaBEVRDestroyed(1,contractCard('phantasmal_haze_red',5));
 $state=FaBGetState();$state['combatOpen']=false;$state['combatStep']='NONE';$state['window']='ACTION';FaBSetState($state);return true;
}
function contractResolveQueued(): void {$queued=$GLOBALS['cx']['stack'];foreach($queued as$o)FaBROSResolveTrigger($o->Controller,$o);}
function contractPhantasmSetup(): void {$o=contractCard('phantasmal_haze_red',8);$o->Kind='ABILITY';$o->Params=['uprPhantasm'=>5];$GLOBALS['cx']['stack']=[$o];$GLOBALS['cx']['nextUID']=9;$GLOBALS['cx']['state']['window']='DEFEND_PRIORITY';$GLOBALS['cx']['state']['combatStep']='DEFEND';}

// Native ability/grant inputs are controlled. Actual ownership/admission and
// grant/effect storage, prevention/queue/dispatcher helpers are extracted above.
function preventionNativeContext(string $card='haunting_rendition_red',int $source=5,int $abi=7,int $p=4): void {
 $o=(object)['CardID'=>$card,'UniqueID'=>$abi,'Owner'=>0,'Controller'=>$p,'Kind'=>'ABILITY','SourceUniqueID'=>$source,'SourceZone'=>'Hand',
  'Params'=>['arcSpec'=>['cardID'=>$card,'timing'=>'INSTANT','cost'=>0,'index'=>0,'destroy'=>false,'goAgain'=>false,'once'=>false,'steam'=>0]]];
 $GLOBALS['cx']['abilityObjects'][$abi]=['zone'=>'Stack','player'=>0,'mzID'=>'Stack-0','object'=>$o];
 $s=FaBGetState();$s['resolvingReaction']=['resolutionClass'=>'ARC_ABILITY','kind'=>'ABILITY','uid'=>$abi,'controller'=>$p,'owner'=>$p,'cardID'=>$card,'sourceUniqueID'=>$source];FaBSetState($s);
}
function preventionGrant(string $card='haunting_rendition_red',string $token='runechant',int $source=5,int $abi=7): void {
 preventionNativeContext($card,$source,$abi);FaBROSAdd(4,'PREVENT',2,['token'=>$token]);
}
function preventionExpectFailure(callable $body,string $why): void {
 $failed=false;try{$body();}catch(RuntimeException $e){$failed=true;}contractAssert($failed,$why);
}

$passed=[];
contractCase('single gained hit queues exact host/controller/count without output',function(){FaBCRUHit(1,contractCard(tags:['CRU_RUNE_HIT:3']),6);$s=$GLOBALS['cx']['stack'];contractAssert(count($s)===1&&$s[0]->CardID==='amplify_the_arknight_red'&&$s[0]->Controller===1&&$s[0]->SourceUniqueID===6&&$s[0]->Kind==='ABILITY'&&$s[0]->Params===['rosTrigger'=>true,'rosEvent'=>'cruMauvrionRuneHit','runeCount'=>3],'Exact captured layer');contractAssert($GLOBALS['cx']['tokens']===[]&&FaBGetState()['window']==='PRIORITY','No inline output');});
contractCase('distinct grants queue distinct layers and apply modifiers at each resolution',function(){FaBCRUHit(1,contractCard(tags:['CRU_RUNE_HIT:3','CRU_RUNE_HIT:1']),6);contractAssert(count($GLOBALS['cx']['stack'])===2,'Per-grant layers');$GLOBALS['cx']['creationModifier']=2;contractResolveQueued();contractAssert(array_column($GLOBALS['cx']['tokens'],'amount')===[5,3],'Late per-event creation modifiers');});
contractCase('event-time ability suppression blocks gained hit queue',function(){$GLOBALS['cx']['blank']=true;FaBCRUHit(1,contractCard(tags:['CRU_RUNE_HIT:3']),6);contractAssert($GLOBALS['cx']['stack']===[]&&$GLOBALS['cx']['tokens']===[],'Suppressed event');});
contractCase('event-time hit suppression blocks gained hit queue',function(){$GLOBALS['cx']['hitSuppressed']=true;FaBCRUHit(1,contractCard(tags:['CRU_RUNE_HIT:3']),6);contractAssert($GLOBALS['cx']['stack']===[],'Hit suppression');});
contractCase('earned hit survives source loss and later suppression',function(){FaBCRUHit(1,contractCard(tags:['CRU_RUNE_HIT:3']),6);$GLOBALS['cx']['sourcePresent']=false;$GLOBALS['cx']['blank']=true;$GLOBALS['cx']['hitSuppressed']=true;contractResolveQueued();contractAssert($GLOBALS['cx']['tokens']===[['player'=>1,'card'=>'runechant','amount'=>3]],'Earned event independent of source');});
contractCase('nonpositive or unrelated tags cannot create Rune layers',function(){FaBCRUHit(1,contractCard(tags:['CRU_RUNE_HIT:0','CRU_RUNE_HIT:-1','OTHER:3']),6);contractAssert($GLOBALS['cx']['stack']===[],'No malformed amount output');});
contractCase('Haze destruction queues exact source/controller without inline Shield',function(){$h=contractCard('phantasmal_haze_red',5);$h->Controller=2;FaBEVRDestroyed(2,$h);$s=$GLOBALS['cx']['stack'];contractAssert(count($s)===1&&$s[0]->CardID==='phantasmal_haze_red'&&$s[0]->Controller===2&&$s[0]->SourceUniqueID===5&&$s[0]->Params['rosEvent']==='evrPhantasmalHazeDestroyed','Captured destruction layer');contractAssert($GLOBALS['cx']['tokens']===[]&&$GLOBALS['cx']['macros']===[],'No eager Shield/macro');});
contractCase('Haze event-time loss of abilities prevents queue',function(){$GLOBALS['cx']['blank']=true;FaBEVRDestroyed(1,contractCard('phantasmal_haze_red',5));contractAssert($GLOBALS['cx']['stack']===[],'Blank source at event');});
contractCase('earned Haze Shield uses captured controller despite source loss and later blanking',function(){$h=contractCard('phantasmal_haze_red',5);$h->Controller=2;FaBEVRDestroyed(2,$h);$GLOBALS['cx']['sourcePresent']=false;$GLOBALS['cx']['blank']=true;contractResolveQueued();contractAssert($GLOBALS['cx']['tokens']===[['player'=>2,'card'=>'spectral_shield','amount'=>1]],'Independent earned Shield');});
contractCase('Haze all pitch variants use the same captured destruction route',function(){foreach(['red','yellow','blue']as$color)FaBEVRDestroyed(1,contractCard('phantasmal_haze_'.$color,5));contractAssert(count($GLOBALS['cx']['stack'])===3,'Variant dispatch');});
contractCase('Coalescence and Miraging existing macro routes unchanged',function(){foreach(['coalescence_mirage_red','miraging_metamorph_red']as$id)FaBEVRDestroyed(1,contractCard($id,5));contractAssert(count($GLOBALS['cx']['macros'])===2&&$GLOBALS['cx']['stack']===[],'Unchanged direct dispatch');});
contractCase('Phantasm closure retains new destruction layer and response priority',function(){contractPhantasmSetup();contractAssert(DoResolveCard(1,'Stack-0')===true,'Resolved branch');contractAssert(FaBGetState()['window']==='PRIORITY'&&!FaBGetState()['combatOpen'],'Fresh closed-combat pending window');contractAssert(FaBStackTop()->UniqueID===9&&$GLOBALS['cx']['priority']===1&&$GLOBALS['cx']['passes']===0&&$GLOBALS['cx']['tokens']===[],'Pending layer/response flow');});
contractCase('Phantasm closure without eligible trigger retains empty-stack ACTION',function(){contractPhantasmSetup();$GLOBALS['cx']['blank']=true;DoResolveCard(1,'Stack-0');contractAssert(FaBStackTop()===null&&FaBGetState()['window']==='ACTION','No invented layer/window');});
contractCase('failed Phantasm condition preserves existing defend-priority window',function(){contractPhantasmSetup();$GLOBALS['cx']['phantasmDestroys']=false;DoResolveCard(1,'Stack-0');contractAssert(FaBStackTop()===null&&FaBGetState()['window']==='DEFEND_PRIORITY'&&$GLOBALS['cx']['priority']===1&&$GLOBALS['cx']['passes']===0,'Existing failed-condition path');});

contractCase('prevention grant captures actual ABI source actor creator and Block classification',function(){
 preventionGrant();$e=FaBWTREffects(4);contractAssert(count($e)===1&&$e[0]['amount']===2&&$e[0]['token']==='runechant','prevention grant stored');
 contractAssert($e[0]['preventionTrigger']===['controller'=>4,'owner'=>4,'cardID'=>'haunting_rendition_red','sourceUID'=>5,'grantUID'=>7,'token'=>'runechant','actionCardEffect'=>false],'exact actual native ABI origin');contractAssert($GLOBALS['cx']['tokens']===[]&&$GLOBALS['cx']['stack']===[],'grant did not create/queue before event');
});
contractCase('prevention grant unknown or mutated native origin fails closed before storage',function(){
 preventionExpectFailure(function(){FaBROSAdd(4,'PREVENT',2,['token'=>'runechant']);},'missing ABI origin accepted');contractAssert(FaBWTREffects(4)===[],'unknown origin stored a grant');
 foreach(['controller','source','card','marker','spec']as$mode){$GLOBALS['cx']['state']['turnEffects']=[];preventionNativeContext();if($mode==='controller')$GLOBALS['cx']['abilityObjects'][7]['object']->Controller=1;if($mode==='source')$GLOBALS['cx']['abilityObjects'][7]['object']->SourceUniqueID=9;if($mode==='card')$GLOBALS['cx']['abilityObjects'][7]['object']->CardID='mental_block_blue';if($mode==='marker')$GLOBALS['cx']['state']['resolvingReaction']['uid']=99;if($mode==='spec')$GLOBALS['cx']['abilityObjects'][7]['object']->Params['arcSpec']['cardID']='mental_block_blue';preventionExpectFailure(function(){FaBROSAdd(4,'PREVENT',2,['token'=>'runechant']);},'mutated ABI accepted');contractAssert(FaBWTREffects(4)===[]&&$GLOBALS['cx']['stack']===[],'bad origin changed persistent grant/queue');}
});
contractCase('zero prevention retains first marker and partial prevention consumes it once',function(){
 preventionGrant();contractAssert(FaBROSPrevent(4,0,'PHYSICAL',6)===0&&$GLOBALS['cx']['stack']===[],'zero prevention triggered');contractAssert(isset(FaBWTREffects(4)[0]['token']),'zero event consumed marker');
 contractAssert(FaBROSPrevent(4,1,'PHYSICAL',6)===0,'partial grant did not prevent1');$e=FaBWTREffects(4);contractAssert(count($e)===1&&$e[0]['amount']===1&&!isset($e[0]['token'])&&!isset($e[0]['preventionTrigger']),'partial amount/first marker wrong');contractAssert(count($GLOBALS['cx']['stack'])===1&&$GLOBALS['cx']['tokens']===[],'not exactly one delayed layer');contractAssert(FaBROSPrevent(4,1,'PHYSICAL',6)===0&&count($GLOBALS['cx']['stack'])===1&&FaBWTREffects(4)===[],'remaining prevention triggered twice');
});
contractCase('first marker is persistently consumed before reentrant queue callback',function(){
 preventionGrant();$GLOBALS['cx']['queueCallback']=function(){$e=FaBWTREffects(4);contractAssert(count($e)===1&&$e[0]['amount']===1&&!isset($e[0]['token'])&&!isset($e[0]['preventionTrigger']),'queue saw unconsumed once marker');contractAssert(FaBROSPrevent(4,1,'PHYSICAL',6)===0,'reentrant remaining damage not prevented');};FaBROSPrevent(4,1,'PHYSICAL',6);contractAssert(count($GLOBALS['cx']['stack'])===1&&FaBWTREffects(4)===[],'reentry duplicated layer or overwritten nested effects');
});
contractCase('two independent grants capture separate source token and once results',function(){
 preventionGrant();preventionGrant('mental_block_blue','ponder',9,10);$GLOBALS['cx']['nextUID']=11;contractAssert(FaBROSPrevent(4,3,'PHYSICAL',6)===0,'two grants prevention wrong');$layers=$GLOBALS['cx']['stack'];contractAssert(count($layers)===2&&array_column($layers,'CardID')===['haunting_rendition_red','mental_block_blue']&&array_column($layers,'SourceUniqueID')===[5,9],'per-grant sources inferred/merged');contractAssert($layers[0]->Params['preventionTrigger']['grantUID']===7&&$layers[1]->Params['preventionTrigger']['grantUID']===10,'separate original ABI grants lost');$e=FaBWTREffects(4);contractAssert(count($e)===1&&$e[0]['amount']===1&&!isset($e[0]['token']),'second partial grant marker not consumed');contractResolveQueued();contractAssert($GLOBALS['cx']['tokens']===[['player'=>4,'card'=>'runechant','amount'=>1],['player'=>4,'card'=>'ponder','amount'=>1]],'captured independent results wrong');
});
contractCase('earned prevention resolver keeps captured source controller owner and action after departure',function(){
 preventionGrant();FaBROSPrevent(4,2,'PHYSICAL',6);$GLOBALS['cx']['abilityObjects']=[];$GLOBALS['cx']['sourcePresent']=false;$GLOBALS['cx']['blank']=true;$GLOBALS['cx']['state']['resolvingReaction']=['controller'=>1,'cardID'=>'wounding_blow_red','sourceUniqueID'=>6];$GLOBALS['fabEffectController']=1;contractResolveQueued();unset($GLOBALS['fabEffectController']);
 contractAssert($GLOBALS['cx']['tokens']===[['player'=>4,'card'=>'runechant','amount'=>1]],'late source/mute/current actor changed earned result');contractAssert($GLOBALS['cx']['factoryCalls']===[['p'=>4,'id'=>'runechant','amount'=>1,'controller'=>4,'action'=>false,'wager'=>false,'owner'=>4]],'factory7args not captured actor/owner/Block source');contractAssert($GLOBALS['cx']['macros']===[],'trigger replayed grant body');
});
contractCase('stored metadata-free nonempty-token grant fails closed without mutation or queue',function(){
 FaBWTRAddEffect(4,'ROS_PREVENT',2,['token'=>'runechant']);$before=FaBGetState();preventionExpectFailure(function(){FaBROSPrevent(4,1,'PHYSICAL',6);},'old unknown source token grant accepted');contractAssert(FaBGetState()===$before&&$GLOBALS['cx']['stack']===[]&&$GLOBALS['cx']['tokens']===[],'malformed stored grant changed state/queued fallback');
});
contractCase('empty-token and ordinary source-selective prevention remain outside ABI requirement',function(){
 FaBROSAdd(4,'PREVENT',2,['token'=>'']);contractAssert(FaBROSPrevent(4,1,'PHYSICAL',6)===0&&count(FaBWTREffects(4))===1&&$GLOBALS['cx']['stack']===[],'empty token changed ordinary prevention');FaBWTRSetEffects(4,[]);FaBROSAdd(4,'SOURCE',2,['source'=>9]);contractAssert(FaBROSPrevent(4,1,'ARCANE',6)===1&&FaBWTREffects(4)[0]['amount']===2,'unmatched source prevented');contractAssert(FaBROSPrevent(4,1,'ARCANE',9)===0&&FaBWTREffects(4)[0]['amount']===1&&$GLOBALS['cx']['stack']===[],'matching source ordinary prevention changed');
});

echo json_encode(['status'=>'PASS','contracts'=>count($passed),'passed'=>$passed,'extracted_function_sha256'=>$extracted,'limits'=>['Controlled component doubles, no game imports/actions. Actual native ABI source ownership, grant/effect storage, prevention, queue and dispatcher functions are extracted; external identity/type/factory/transport inputs are controlled.','Actual token ownership/UIDs/physical destruction, priority passes, and full post-repair recording remain native witness scope.','Phantasm destruction/chain-close body is a boundary double; the reviewed actual DoResolveCard branch restores its resulting pending-layer window.','New factory recorder preserves old3field output and separately records all7 real factory arguments; factory/gameplay/token UID behavior remains native scope.','Haunting/Mental native activation/generated macro context is represented by controlled ABI inputs here; unchanged correctHaunting nativeprobe validates actual context transport. MentalBlock remains source-only, no native credit.']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;
