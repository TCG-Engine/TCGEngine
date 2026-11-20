<?php
// Portable controlled source-component tests. No game/bootstrap includes or persistent writes.
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);
$paths=['FaBSim/Custom/ELECards.php','FaBSim/Custom/ROSCards.php'];
foreach($paths as $i=>$path){
 $expected=$argv[$i+2]??'';
 if(!preg_match('/^[a-f0-9]{64}$/',$expected)||!hash_equals($expected,hash_file('sha256',$repo.'/'.$path)))throw new RuntimeException('Supply reviewed ELE and ROS SHA256 arguments: '.$path);
}
$extractor=$repo.'/DevTools/FaB/CardRegression/components/extract_component_functions.php';
if(hash_file('sha256',$extractor)!=='ca10febfe5f102c0eca748c0fc82f83b0b1a814e4789d7f955b7fe82aca85199')throw new RuntimeException('Shared pure source extractor changed');
require $extractor;
$bindings=['FaBELEDamaged'=>'FaBSim/Custom/ELECards.php','FaBELECount'=>'FaBSim/Custom/ELECards.php','FaBELEAdd'=>'FaBSim/Custom/ELECards.php','FaBROSQueue'=>'FaBSim/Custom/ROSCards.php','FaBROSResolveTrigger'=>'FaBSim/Custom/ROSCards.php'];
$extracted=[];
foreach($bindings as $name=>$file){$code=ComponentExtractFunction($repo.'/'.$file,$name);$extracted[$name]=hash('sha256',$code);eval($code);}
function bcReset(): void {
 $hero=(object)['CardID'=>'briar','UniqueID'=>1,'Owner'=>1,'Controller'=>1,'TurnEffects'=>[]];
 $source=(object)['CardID'=>'stir_the_wildwood_red','UniqueID'=>5,'Owner'=>1,'Controller'=>1,'TurnEffects'=>[],'attackAction'=>true];
 $GLOBALS['bc']=['state'=>['window'=>'DAMAGE','pendingPayment'=>null,'turnEffects'=>[]],'heroes'=>[1=>[$hero]],'heroActive'=>[1=>true],'source'=>$source,'sourcePresent'=>true,'stack'=>[],'tokens'=>[],'nextUID'=>9];
}
function bcAssert(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
function bcCase(string $name,callable $fn): void {bcReset();$fn();$GLOBALS['passed'][]=$name;}
function bcResolve(): void {foreach($GLOBALS['bc']['stack']as$o)if(empty($o->removed)){$o->removed=true;FaBROSResolveTrigger(intval($o->Controller),$o);}}
function FaBARCEffect(int $p,string $type): int {$n=0;foreach($GLOBALS['bc']['state']['turnEffects'][(string)$p]??[]as$e)if($e['type']===$type)$n+=$e['amount'];return $n;}
function FaBWTRAddEffect(int $p,string $type,int $amount=1,array $extra=[]): void {$GLOBALS['bc']['state']['turnEffects'][(string)$p][]=array_merge(['type'=>$type,'amount'=>$amount,'uses'=>1],$extra);}
function FaBIdentityFromMZ(string $ref): ?array {return $GLOBALS['bc']['sourcePresent']?['object'=>$GLOBALS['bc']['source'],'player'=>intval($GLOBALS['bc']['source']->Controller),'zone'=>str_contains($ref,'Stack')?'Stack':'CombatChain','mzID'=>$ref]:null;}
function FaBWTRBase(string $id): string {return preg_replace('/_(red|yellow|blue)$/','',$id);}
function FaBWTRIsAttackAction(object $o): bool {return (bool)($o->attackAction??false);}
function FaBWTRIsWeapon(object $o): bool {return false;}
function FaBOpponents(int $p): array {return array_values(array_diff([1,2,3,4],[$p]));}
function FaBWTRHeroActive(int $p): bool {return (bool)($GLOBALS['bc']['heroActive'][$p]??false);}
function GetHero(int $p): array {return $GLOBALS['bc']['heroes'][$p]??[];}
function FaBELEFused(int $uid,string $element=''): bool {return false;}
function FaBELEFrost(int $p,int $amount=1): void {bcAssert($amount===0,'Unrelated Frost branch must remain inactive');}
function HasNoAbilities(object $o): bool {return in_array('NO_ABILITIES',(array)($o->TurnEffects??[]),true);}
function FaBCRUHitSuppressed(object $o,bool $all=false): bool {return true;}
function FaBSeatIsLive(int $p): bool {return in_array($p,[1,2,3,4],true);}
function FaBGetState(): array {return $GLOBALS['bc']['state'];}
function FaBSetState(array $s): void {$GLOBALS['bc']['state']=$s;}
function AddStack(string $CardID,int $Controller,string $Kind,int $SourceUniqueID=0,array $Params=[]): object {$o=(object)['CardID'=>$CardID,'Controller'=>$Controller,'Kind'=>$Kind,'SourceUniqueID'=>$SourceUniqueID,'Params'=>$Params,'UniqueID'=>$GLOBALS['bc']['nextUID']++,'removed'=>false];$GLOBALS['bc']['stack'][]=$o;return $o;}
function FaBWTRCreateArena(int $p,string $id): void {$GLOBALS['bc']['tokens'][]=['player'=>$p,'card'=>$id];}
function FaBFindUID(int $uid): ?array {throw new RuntimeException('Earned trigger must not query live source at resolution');}
$passed=[];
bcCase('positive AA damage queues one actual Hero source and no early Earth',function(){FaBELEDamaged(1,4,7,'p1CombatChain-0');$q=$GLOBALS['bc']['stack'];bcAssert(count($q)===1,'Exactly one triggered layer');bcAssert($q[0]->CardID==='briar'&&$q[0]->SourceUniqueID===1&&$q[0]->Controller===1&&$q[0]->Kind==='ABILITY'&&$q[0]->Params===['rosTrigger'=>true,'rosEvent'=>'eleBriarFirstAADamage'],'Captured Hero source/controller/event');bcAssert($GLOBALS['bc']['tokens']===[]&&FaBELECount(1,'BRIAR')===1&&FaBGetState()['window']==='PRIORITY','Immediate first marker, deferred output and responses');});
bcCase('zero damage cannot earn Briar trigger or first marker',function(){FaBELEDamaged(1,4,0,'p1CombatChain-0');bcAssert($GLOBALS['bc']['stack']===[]&&FaBELECount(1,'BRIAR')===0,'No positive damage event');});
bcCase('Runechant Aura damage cannot earn AA damage trigger',function(){$GLOBALS['bc']['source']->CardID='runechant';$GLOBALS['bc']['source']->attackAction=false;FaBELEDamaged(1,4,1,'Stack-0');bcAssert($GLOBALS['bc']['stack']===[]&&FaBELECount(1,'BRIAR')===0,'Non-AA source excluded');});
bcCase('damage to own hero cannot earn opposing hero trigger',function(){FaBELEDamaged(1,1,1,'p1CombatChain-0');bcAssert($GLOBALS['bc']['stack']===[]&&FaBELECount(1,'BRIAR')===0,'Opponent condition');});
bcCase('Hero ability inactive at event cannot queue',function(){$GLOBALS['bc']['heroActive'][1]=false;$GLOBALS['bc']['heroes'][1][0]->TurnEffects=['NO_ABILITIES'];FaBELEDamaged(1,4,7,'p1CombatChain-0');bcAssert($GLOBALS['bc']['stack']===[]&&$GLOBALS['bc']['tokens']===[],'Event-time Hero suppression');});
bcCase('blank attacking card does not suppress separate Hero ability',function(){$GLOBALS['bc']['source']->TurnEffects=['NO_ABILITIES'];FaBELEDamaged(1,4,7,'p1CombatChain-0');bcAssert(count($GLOBALS['bc']['stack'])===1,'Hero is ability source; hit suppression and attacker blanking do not veto');});
bcCase('second damage before first trigger resolves does not queue twice',function(){FaBELEDamaged(1,4,7,'p1CombatChain-0');FaBELEDamaged(1,4,1,'p1CombatChain-0');bcAssert(count($GLOBALS['bc']['stack'])===1&&FaBELECount(1,'BRIAR')===1&&$GLOBALS['bc']['tokens']===[],'First marker applied at event');});
bcCase('second damage after resolution cannot create another Earth',function(){FaBELEDamaged(1,4,7,'p1CombatChain-0');bcResolve();FaBELEDamaged(1,4,1,'p1CombatChain-0');bcAssert(count($GLOBALS['bc']['stack'])===1&&$GLOBALS['bc']['tokens']===[['player'=>1,'card'=>'embodiment_of_earth']],'Single first event per existing marker');});
bcCase('earned layer survives attacking source loss and later Hero ability loss',function(){FaBELEDamaged(1,4,7,'p1CombatChain-0');$GLOBALS['bc']['sourcePresent']=false;$GLOBALS['bc']['heroes'][1]=[];$GLOBALS['bc']['heroActive'][1]=false;bcResolve();bcAssert($GLOBALS['bc']['tokens']===[['player'=>1,'card'=>'embodiment_of_earth']],'No late active-source dependency');});
bcCase('different Hero identity does not gain Briar effect',function(){$GLOBALS['bc']['heroes'][1][0]->CardID='azalea';FaBELEDamaged(1,4,7,'p1CombatChain-0');bcAssert($GLOBALS['bc']['stack']===[],'Named Hero restriction');});
bcCase('captured controller two owns later Earth creation',function(){$h=(object)['CardID'=>'briar','UniqueID'=>2,'Owner'=>2,'Controller'=>2,'TurnEffects'=>[]];$GLOBALS['bc']['heroes'][2]=[$h];$GLOBALS['bc']['heroActive'][2]=true;$GLOBALS['bc']['source']->Controller=2;FaBELEDamaged(2,1,7,'p2CombatChain-0');$q=$GLOBALS['bc']['stack'][0];bcAssert($q->Controller===2&&$q->SourceUniqueID===2,'Captured controller/heroUID');$GLOBALS['bc']['heroes'][2]=[];bcResolve();bcAssert($GLOBALS['bc']['tokens']===[['player'=>2,'card'=>'embodiment_of_earth']],'Controller retained');});
bcCase('AA arcane damage event also qualifies independently of combat hit hook',function(){FaBELEDamaged(1,4,1,'Stack-0');bcAssert(count($GLOBALS['bc']['stack'])===1,'Any actual AA damage, not only OnHit');});
bcCase('adult Briar identity uses same first damage resolver',function(){$GLOBALS['bc']['heroes'][1][0]->CardID='briar_warden_of_thorns';FaBELEDamaged(1,4,7,'p1CombatChain-0');bcResolve();bcAssert($GLOBALS['bc']['tokens']===[['player'=>1,'card'=>'embodiment_of_earth']],'Existing adult identity route');});
echo json_encode(['status'=>'PASS','contracts'=>count($passed),'passed'=>$passed,'extracted_function_sha256'=>$extracted,'limits'=>['Controlled dependencies, no engine includes/game actions/persistent writes.','Actual token ownership, UID allocation, physical damage, Hero suppression implementation, four-seat priority and recorder flow remain native/existing-source scope.','Preserves existing first-marker eligibility. Ordinal-before-ability-recovery behavior and second-NAA Lightning trigger are not certified.']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),PHP_EOL;
