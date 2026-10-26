<?php
// Guard and extract actual source functions only; no engine bootstrap or game writes.
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);
$paths=['FaBSim/Custom/GameLogic.php','FaBSim/Custom/CRUCards.php','FaBSim/Custom/EVRCards.php','FaBSim/Custom/ROSCards.php','FaBSim/Custom/ARCCards.php'];
foreach($paths as $i=>$path){
 $expected=$argv[$i+2]??'';
 if(!preg_match('/^[a-f0-9]{64}$/',$expected)||!hash_equals($expected,hash_file('sha256',$repo.'/'.$path)))throw new RuntimeException('Supply current GameLogic, CRU, EVR, ROS and ARC SHA256 arguments: '.$path);
}
require __DIR__.'/extract_component_functions.php';
$bindings=[
 'FaBCRUHit'=>'FaBSim/Custom/CRUCards.php',
 'FaBEVRDestroyed'=>'FaBSim/Custom/EVRCards.php',
 'FaBROSQueue'=>'FaBSim/Custom/ROSCards.php',
 'FaBROSResolveTrigger'=>'FaBSim/Custom/ROSCards.php',
 'FaBARCCreateRunes'=>'FaBSim/Custom/ARCCards.php',
 'DoResolveCard'=>'FaBSim/Custom/GameLogic.php',
];
$extracted=[];
foreach($bindings as $name=>$file){$code=ComponentExtractFunction($repo.'/'.$file,$name);$extracted[$name]=hash('sha256',$code);eval($code);}

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
function FaBHasType(object $o,string $type): bool {return $type==='Illusionist'&&in_array(FaBWTRBase($o->CardID),['phantasmal_haze','coalescence_mirage','miraging_metamorph'],true);}
function FaBSeatIsLive(int $p): bool {return $p>=1&&$p<=4;}
function FaBGetState(): array {return $GLOBALS['cx']['state'];}
function FaBSetState(array $state): void {$GLOBALS['cx']['state']=$state;}
function AddStack(string $CardID,int $Controller,string $Kind,int $SourceUniqueID=0,array $Params=[]): object {$o=(object)['CardID'=>$CardID,'Controller'=>$Controller,'Kind'=>$Kind,'SourceUniqueID'=>$SourceUniqueID,'Params'=>$Params,'UniqueID'=>$GLOBALS['cx']['nextUID']++,'removed'=>false];$GLOBALS['cx']['stack'][]=$o;return $o;}
function FaBStackTop(): ?object {foreach(array_reverse($GLOBALS['cx']['stack'])as$o)if(empty($o->removed))return $o;return null;}
function FaBFindUID(int $uid): ?array {return $GLOBALS['cx']['sourcePresent']?['mzID'=>'p1Graveyard-0','zone'=>'Graveyard','player'=>1,'object'=>contractCard('phantasmal_haze_red',$uid)]:null;}
function FaBARCEffect(int $p,string $key): int {contractAssert($key==='ARC_MORDRED','Only Rune creation modifier expected');return $GLOBALS['cx']['creationModifier'];}
function FaBHVYToken(int $p,string $id,int $amount,int $owner=0): void {$GLOBALS['cx']['tokens'][]=['player'=>$p,'card'=>$id,'amount'=>$amount];}
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
echo json_encode(['status'=>'PASS','contracts'=>count($passed),'passed'=>$passed,'extracted_function_sha256'=>$extracted,'limits'=>['Controlled component doubles, no game imports/actions.','Actual token ownership/UIDs/physical destruction, priority passes, and full post-repair recording remain native witness scope.','Phantasm destruction/chain-close body is a boundary double; the reviewed actual DoResolveCard branch restores its resulting pending-layer window.']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;
