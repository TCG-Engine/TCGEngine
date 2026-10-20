<?php
/** Change only the initial state of a disposable negative-control fixture. */
declare(strict_types=1);
$repo=__DIR__;
while(!is_file($repo.'/Core/EngineActionRunner.php')&&dirname($repo)!==$repo)$repo=dirname($repo);
if(!is_file($repo.'/Core/EngineActionRunner.php'))$repo=str_replace('\\','/',getcwd());
chdir($repo);require_once $repo.'/Core/EngineActionRunner.php';EngineLoadRootRuntime('FaBSim');
$spec=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
$target=realpath($spec['fixture']);$allowed=realpath($repo.'/Tests/Integration/FaBSim');
if($target===false||dirname($target)!==$allowed||!str_starts_with(basename($target),'negative-control-'))throw new RuntimeException('Expected a disposable negative-control fixture');
/** Four narrow mutations, callable only after the disposable fixture/prefix gate below. */
function NCApplyGrantedHitMutation(string $kind): void {
 $kinds=['remove_one_pummel','wrong_pummel_victim','early_razor_go_again','inert_razor_layer'];
 if(!in_array($kind,$kinds,true))throw new RuntimeException('Unknown granted-hit mutation');
 $state=FaBGetState();$isPummel=in_array($kind,['remove_one_pummel','wrong_pummel_victim'],true);
 $card=$isPummel?'brutal_assault_blue':'wounding_blow_red';
 if(empty($state['combatOpen'])||intval($state['attacker']??0)!==1||intval($state['defender']??0)!==2
    ||intval($state['chainLink']??0)!==1||($state['pendingPayment']??null)!==null)
  throw new RuntimeException('Unexpected granted-hit combat context');
 foreach([1,2,3,4]as$seat)if(count(GetDecisionQueue($seat))!==0)throw new RuntimeException('Expected no pending choice before granted-hit mutation');
 $found=FaBFindUID(intval($state['attackUID']??0));
 if($found===null||$found['zone']!=='CombatChain')throw new RuntimeException('Expected live granted-hit attack source');
 $source=$found['object'];
 if(!empty($source->removed)||$source->CardID!==$card||($source->Role??'')!=='ATTACK'
    ||intval($source->Controller??0)!==1||intval($source->ChainLink??0)!==1||!is_array($source->TurnEffects??null))
  throw new RuntimeException('Unexpected granted-hit source identity');
 $layers=array_values(array_filter(GetStack(),fn($o)=>is_object($o)&&empty($o->removed)));
 if($kind==='remove_one_pummel'){
  if(($state['window']??'')!=='REACTION'||($state['combatStep']??'')!=='REACTION'||!empty($state['attackHit'])||count($layers)!==0)
   throw new RuntimeException('Expected pre-hit two-Pummel reaction boundary');
  $keys=array_keys($source->TurnEffects,'WTR_PUMMEL_HIT',true);
  if(count($keys)!==2)throw new RuntimeException('Expected exactly two Pummel grants');
  $effects=$source->TurnEffects;unset($effects[$keys[1]]);$source->TurnEffects=array_values($effects);
  return;
 }
 if(($state['combatStep']??'')!=='DAMAGE'||!in_array($state['window']??'',['DAMAGE','PRIORITY'],true)
    ||empty($state['attackHit'])||count($layers)!==1)
  throw new RuntimeException('Expected one pending granted-hit layer after damage');
 $layer=$layers[0];$effect=$isPummel?'DISCARD':'GO_AGAIN';
 if(($layer->Kind??'')!=='ABILITY'||$layer->CardID!==$card||intval($layer->Owner??0)!==1||intval($layer->Controller??0)!==1
    ||($layer->SourceZone??'')!=='CombatChain'||intval($layer->SourceUniqueID??0)!==intval($source->UniqueID)
    ||!is_array($layer->Params??null)||($layer->Params['wtrGrantedHitEffect']??'')!==$effect
    ||intval($layer->Params['wtrHitChainLink']??0)!==1)
  throw new RuntimeException('Unexpected granted-hit layer identity or marker');
 if($kind==='wrong_pummel_victim'){
  if(($state['attackTarget']['type']??'')!=='HERO'||intval($state['attackTarget']['player']??0)!==2
     ||intval($layer->Params['wtrHitVictim']??0)!==2||count(FaBChoiceRefs(1,'Hand'))!==0||count(FaBChoiceRefs(2,'Hand'))!==2)
   throw new RuntimeException('Unexpected captured-victim control setup');
  $layer->Params['wtrHitVictim']=1;return;
 }
 if(count(array_keys($source->TurnEffects,'WTR_HIT_GO_AGAIN',true))!==1||in_array('GO_AGAIN',$source->TurnEffects,true))
  throw new RuntimeException('Expected one unfulfilled Razor grant');
 if($kind==='early_razor_go_again'){$source->TurnEffects[]='GO_AGAIN';return;}
 $layer->Params['wtrGrantedHitEffect']='NEGATIVE_CONTROL_NO_EFFECT';
}

/** Only fresh Ice Bolt actor/cleanup/async-source controls, in isolated fixture clones. */
function NCValidateStateAssertionSpec(array $spec, int $actionCount): void {
 if(array_diff(array_keys($spec),['fixture','prefix','state_assertion_mutation'])||!is_string($spec['fixture']??null)||$spec['fixture']===''
    ||!isset($spec['prefix'])||!is_int($spec['prefix'])||$spec['prefix']<1||$spec['prefix']>$actionCount
    ||!is_string($spec['state_assertion_mutation']??null)
    ||!in_array($spec['state_assertion_mutation'],['wrong_arcane_actor','stale_resolution_marker','premature_plain_source_clear'],true))
  throw new RuntimeException('Invalid isolated state assertion mutation spec');
}

function NCApplyStateAssertionMutation(string $kind): void {
 if(!in_array($kind,['wrong_arcane_actor','stale_resolution_marker','premature_plain_source_clear'],true))throw new RuntimeException('Unknown state assertion mutation');
 $pending=$kind==='premature_plain_source_clear';$state=FaBGetState();
 $live=fn($objects)=>array_values(array_filter($objects,fn($o)=>is_object($o)&&empty($o->removed)));
 if(FaBLiveSeats()!==[1,2]||intval(GetTurnPlayer())!==1||!empty($state['combatOpen'])||($state['pendingPayment']??null)!==null
    ||($state['window']??'')!==($pending?'PRIORITY':'ACTION')
    ||intval(GetHealth(1))!==20||intval(GetHealth(2))!==($pending?20:16)
    ||intval(GetResources(1))!==2||intval(GetResources(2))!==($pending?1:0)
    ||intval(GetActionPoints(1))!==0||intval(GetActionPoints(2))!==0)
  throw new RuntimeException('Unexpected isolated Ice Bolt state/economy');
 $stack=$live(GetStack());$grave=$live(GetGraveyard(1));$variables=(string)GetDecisionQueueVariables();
 $decoded=json_decode($variables,true,512,JSON_THROW_ON_ERROR);
 if(!is_array($decoded))throw new RuntimeException('Expected Ice Bolt decision variables');
 $equipment=$live(GetEquipment(2));
 if(count($equipment)!==1||$equipment[0]->CardID!=='nullrune_gloves')throw new RuntimeException('Expected exact Arcane Barrier fixture equipment');
 $queueSnapshot=[];
 foreach([1,2,3,4]as$seat){
  $queue=$live(GetDecisionQueue($seat));
  $queueSnapshot[$seat]=serialize($queue);
  if($pending&&$seat===2){
   if(count($queue)!==2||$queue[0]->Type!=='MZMAYCHOOSE'||$queue[0]->Param!=='p2Equipment-0'
      ||$queue[1]->Type!=='CUSTOM'||preg_match('/^ice_bolt_red:0:ResolveCard-await\|(ice_bolt_red:0:ResolveCard#[0-9]+)\|45\|barrier$/D',(string)$queue[1]->Param,$match)!==1)
    throw new RuntimeException('Expected exact pending Ice Bolt Arcane Barrier queue');
   $frameKey=$match[1];
  }elseif(count($queue)!==0)throw new RuntimeException('Unexpected unrelated decision queue');
 }
 if(!$pending){
  if(count($stack)!==0||count($grave)!==1||$grave[0]->CardID!=='ice_bolt_red'||!empty($decoded['__awaitFrames'])
     ||($state['resolvingReaction']??null)!==null||($state['arcaneDealt']??null)!==[1=>[2=>4]])
   throw new RuntimeException('Expected completed fresh Ice Bolt attribution/cleanup');
  if(intval($grave[0]->Owner??0)!==1||intval($grave[0]->Controller??0)!==1||intval($grave[0]->UniqueID??0)<1)
   throw new RuntimeException('Unexpected completed Ice Bolt source identity');
  if($kind==='wrong_arcane_actor')$state['arcaneDealt']=[2=>[2=>4]];
  else $state['resolvingReaction']=['negativeControl'=>'completed_plain_source_still_pending','uid'=>intval($grave[0]->UniqueID),'cardID'=>'ice_bolt_red'];
  FaBSetState($state);return;
 }
 if(count($stack)!==1||count($grave)!==0)throw new RuntimeException('Expected one pending plain source and empty caster graveyard');
 $source=$stack[0];$uid=intval($source->UniqueID??0);$found=FaBFindUID($uid);$marker=$state['resolvingReaction']??null;
 if($uid<1||$source->CardID!=='ice_bolt_red'||($source->Kind??'')!=='ACTION'||($source->SourceZone??'')!=='Hand'
    ||intval($source->Owner??0)!==1||intval($source->Controller??0)!==1
    ||$found===null||$found['zone']!=='Stack'||$found['mzID']!=='Stack-0'||$found['object']!==$source
    ||!is_array($marker)||($marker['resolutionClass']??'')!=='PLAIN_SPELL'||($marker['kind']??'')!=='ACTION'
    ||($marker['cardID']??'')!=='ice_bolt_red'||intval($marker['uid']??0)!==$uid
    ||intval($marker['owner']??0)!==1||intval($marker['controller']??0)!==1||($marker['sourceMZ']??'')!=='Stack-0'
    ||($decoded['__awaitFrames'][$frameKey]['locals']['mzID']??'')!=='Stack-0'
    ||count($decoded['__awaitFrames']??[])!==1||!empty($state['arcaneDealt']))
  throw new RuntimeException('Unexpected pending plain-spell source/marker/frame binding');
 if(array_key_exists('penNames',$state['arcCards'][(string)$uid]??[])||array_key_exists('penColor',$state['arcCards'][(string)$uid]??[]))
  throw new RuntimeException('Unexpected temporary source overrides before premature clear');
 // Keep real movement callbacks. Exact active bookkeeping for red Ice Bolt is
 // EVR_GRAVEYARD for both live seats and IAR permission cleanup on this UID.
 $expectedState=$state;
 foreach([1,2]as$seat)$expectedState['turnEffects'][(string)$seat][]=['type'=>'EVR_GRAVEYARD','amount'=>1,'uses'=>1];
 $expectedState['arcCards'][(string)$uid]['iarPermission']=[];
 // FaBMoveUID intentionally rejects ordinary Stack sources. Use its bounded Stack helper.
 $moved=FaBMoveStackUID($uid,'Graveyard',1,false);
 $newGrave=$live(GetGraveyard(1));
 if(!is_object($moved)||$moved->CardID!=='ice_bolt_red'||intval($moved->UniqueID??0)!==$uid||empty($source->removed)
    ||intval($moved->Owner??0)!==1||intval($moved->Controller??0)!==1
    ||count($live(GetStack()))!==0||count($newGrave)!==1||$newGrave[0]!==$moved
    ||FaBGetState()!==$expectedState||(string)GetDecisionQueueVariables()!==$variables
    ||intval(GetHealth(1))!==20||intval(GetHealth(2))!==20||intval(GetResources(1))!==2||intval(GetResources(2))!==1
    ||intval(GetActionPoints(1))!==0||intval(GetActionPoints(2))!==0)
  throw new RuntimeException('Premature source clear did not preserve the intended isolated state');
 foreach([1,2,3,4]as$seat)if(serialize($live(GetDecisionQueue($seat)))!==$queueSnapshot[$seat])throw new RuntimeException('Premature clear changed a decision queue');
}

/** Exact ordinary no-hit Blade Break source fault, only in a disposable clone. */
function NCValidateBladeBreakClearSpec(array $spec, int $actionCount): void {
 if(array_diff(array_keys($spec),['fixture','prefix','blade_break_clear'])||!is_string($spec['fixture']??null)||$spec['fixture']===''
    ||!isset($spec['prefix'])||!is_int($spec['prefix'])||$spec['prefix']<1||$spec['prefix']>$actionCount
    ||($spec['blade_break_clear']??null)!==true)throw new RuntimeException('Invalid isolated Blade Break clear spec');
}
function NCApplyBladeBreakClear(): void {
 $s=FaBGetState();$live=fn($rows)=>array_values(array_filter($rows,fn($o)=>is_object($o)&&empty($o->removed)));
 if(FaBLiveSeats()!==[1,2]||intval(GetWinner())!==0||intval(GetTurnPlayer())!==1||intval(GetPriorityPlayer())!==1||intval(GetConsecutivePasses())!==0
    ||($s['window']??'')!=='RESOLUTION'||($s['combatStep']??'')!=='RESOLUTION'||($s['combatOpen']??null)!==true
    ||intval($s['chainLink']??0)!==1||intval($s['attacker']??0)!==1||intval($s['defender']??0)!==2
    ||($s['attackHit']??null)!==false||intval($s['damageDealt']??-1)!==0||intval($s['defenseValue']??-1)!==4
    ||($s['pendingPayment']??null)!==null||($s['resolvingReaction']??null)!==null)
  throw new RuntimeException('Unexpected Blade Break no-hit resolution context');
 $variables=(string)GetDecisionQueueVariables();$v=json_decode($variables,true,512,JSON_THROW_ON_ERROR);
 if(!is_array($v)||!empty($v['__awaitFrames'])||count($live(GetStack()))!==0)throw new RuntimeException('Unexpected Blade Break pending work');
 $queues=[];
 foreach([1,2,3,4]as$p){$q=$live(GetDecisionQueue($p));if(count($q)!==0)throw new RuntimeException('Unexpected Blade Break choice');$queues[$p]=serialize($q);}
 $heroes=[];
 foreach([1,2]as$p){
  if(intval(GetHealth($p))!==20||intval(GetResources($p))!==0||intval(GetActionPoints($p))!==0)throw new RuntimeException('Unexpected Blade Break economy');
  $hero=$live(GetHero($p));if(count($hero)!==1||$hero[0]->CardID!=='dorinthea')throw new RuntimeException('Unexpected Blade Break hero');$heroes[$p]=serialize($hero);
  foreach(['Hand','Arsenal','Equipment','Weapons','Arena','Deck','Pitch','Graveyard','Banish','Soul']as$zone)if(count($live(GetZone('p'.$p.$zone)))!==0)throw new RuntimeException('Unexpected unrelated Blade Break zone');
 }
 $attacks=$live(GetCombatChain(1));$defenders=$live(GetCombatChain(2));
 if(count($attacks)!==1||$attacks[0]->CardID!=='wounding_blow_red'||($attacks[0]->Role??'')!=='ATTACK'
    ||intval($attacks[0]->UniqueID??0)!==intval($s['attackUID']??0)||($attacks[0]->FromZone??'')!=='Hand'
    ||count($defenders)!==2||$defenders[0]->CardID!=='ironrot_gauntlet'||$defenders[1]->CardID!=='wounding_blow_blue')
  throw new RuntimeException('Unexpected Blade Break chain composition');
 $source=$defenders[0];$uid=intval($source->UniqueID??0);$found=FaBFindUID($uid);
 if($uid<1||$found===null||$found['zone']!=='CombatChain'||$found['mzID']!=='p2CombatChain-0'||$found['object']!==$source
    ||($source->Role??'')!=='DEFENSE'||($source->FromZone??'')!=='Equipment'||intval($source->Owner??0)!==2||intval($source->Controller??0)!==2
    ||intval($source->ChainLink??0)!==1||(array)($source->TurnEffects??[])!==['DESTROY_ON_CHAIN_CLOSE']||FaBObjectCounters($source)!==[])
  throw new RuntimeException('Unexpected exact Blade Break source binding');
 $meta=$s['arcCards'][(string)$uid]??[];
 if(isset($meta['dynExiled'])||array_key_exists('penNames',$meta)||array_key_exists('penColor',$meta)||!empty($meta['penLunarOriginal'])
    ||intval($meta['hntTrapReplaceTurn']??-1)===intval(GetTurnNumber()))throw new RuntimeException('Unexpected Blade Break move replacement');
 $attackSnapshot=serialize($attacks);$otherSnapshot=serialize($defenders[1]);
 // Preserve all real callbacks: EVR graveyard bookkeeping, MST defense-cache
 // reset, PEN freeze-source cleanup, and IAR permission cleanup are active here.
 $expected=$s;foreach([1,2]as$p)$expected['turnEffects'][(string)$p][]=['type'=>'EVR_GRAVEYARD','amount'=>1,'uses'=>1];
 $expected['arcCards'][(string)$uid]['mstZeroDefense']=false;$expected['arcCards'][(string)$uid]['penFreezeSources']=[];$expected['arcCards'][(string)$uid]['iarPermission']=[];
 $moved=FaBMoveUID($uid,'Graveyard',2,false);$grave=$live(GetGraveyard(2));$chain=$live(GetCombatChain(2));
 if(!is_object($moved)||$moved->CardID!=='ironrot_gauntlet'||intval($moved->UniqueID??0)!==$uid||intval($moved->Owner??0)!==2||intval($moved->Controller??0)!==2
    ||empty($source->removed)||count($grave)!==1||$grave[0]!==$moved||count($chain)!==1||$chain[0]!==$defenders[1]
    ||serialize($chain[0])!==$otherSnapshot||serialize($live(GetCombatChain(1)))!==$attackSnapshot
    ||FaBGetState()!==$expected||(string)GetDecisionQueueVariables()!==$variables||count($live(GetStack()))!==0)
  throw new RuntimeException('Premature Blade Break clear changed unexpected state');
 foreach([1,2]as$p)if(intval(GetHealth($p))!==20||intval(GetResources($p))!==0||intval(GetActionPoints($p))!==0||serialize($live(GetHero($p)))!==$heroes[$p])throw new RuntimeException('Premature Blade Break clear changed unrelated hero/economy');
 foreach([1,2,3,4]as$p)if(serialize($live(GetDecisionQueue($p)))!==$queues[$p])throw new RuntimeException('Premature Blade Break clear changed queue');
}

/** New defense3 faults alone opt into exact physical source/stage checks. */
function NCValidateVanillaDefenseSource(array $entry, object $source): void {
 if(!array_key_exists('vanilla_defense_source',$entry))return;
 $binding=$entry['vanilla_defense_source'];
 if(!is_array($binding)||count($binding)!==2||array_diff(array_keys($binding),['sourceUID','attackUID'])
    ||!is_int($binding['sourceUID']??null)||$binding['sourceUID']<1||!is_int($binding['attackUID']??null)||$binding['attackUID']<1||$binding['sourceUID']===$binding['attackUID']
    ||($entry['player']??null)!==2||($entry['zone']??null)!=='CombatChain'||($entry['cardID']??null)!=='critical_strike_blue'||($entry['mode']??null)!=='add'
    ||!in_array($entry['tag']??null,['WTR_DEFENSE:1','WTR_DEFENSE:-1'],true))throw new RuntimeException('Invalid isolated vanilla defense binding');
 $s=FaBGetState();$found=FaBFindUID($binding['sourceUID']);$attack=FaBFindUID($binding['attackUID']);
 if(FaBLiveSeats()!==[1,2]||intval(GetWinner())!==0||intval(GetTurnPlayer())!==1||intval(GetPriorityPlayer())!==2||intval(GetConsecutivePasses())!==0
    ||($s['window']??'')!=='DEFEND_DECLARE'||($s['combatStep']??'')!=='DEFEND'||($s['combatOpen']??null)!==true||intval($s['chainLink']??0)!==1
    ||intval($s['attacker']??0)!==1||intval($s['defender']??0)!==2||intval($s['attackUID']??0)!==$binding['attackUID']||intval($s['defenseValue']??-1)!==3
    ||($s['pendingPayment']??null)!==null||($s['resolvingReaction']??null)!==null||($s['attackHit']??null)!==false||intval($s['damageDealt']??-1)!==0
    ||$found===null||$found['zone']!=='CombatChain'||$found['mzID']!=='p2CombatChain-0'||$found['object']!==$source||!empty($source->removed)
    ||$source->CardID!=='critical_strike_blue'||intval($source->UniqueID??0)!==$binding['sourceUID']||intval($source->Owner??0)!==2||intval($source->Controller??0)!==2
    ||($source->Role??'')!=='DEFENSE'||($source->FromZone??'')!=='Hand'||intval($source->ChainLink??0)!==1||(array)($source->TurnEffects??[])!==[]
    ||$attack===null||$attack['zone']!=='CombatChain'||$attack['mzID']!=='p1CombatChain-0'||!empty($attack['object']->removed)
    ||$attack['object']->CardID!=='wounding_blow_red'||($attack['object']->Role??'')!=='ATTACK'||($attack['object']->FromZone??'')!=='Hand'
    ||intval($attack['object']->Owner??0)!==1||intval($attack['object']->Controller??0)!==1||intval($attack['object']->ChainLink??0)!==1)
  throw new RuntimeException('Unexpected exact vanilla defense source/stage');
 foreach([1,2]as$p)if(intval(GetHealth($p))!==20||intval(GetResources($p))!==0||intval(GetActionPoints($p))!==0)throw new RuntimeException('Unexpected vanilla defense assets');
 $live=fn($rows)=>array_values(array_filter($rows,fn($o)=>is_object($o)&&empty($o->removed)));
 if(count($live(GetStack()))!==0)throw new RuntimeException('Unexpected vanilla defense Stack');
 foreach([1,2,3,4]as$p)if(count($live(GetDecisionQueue($p)))!==0)throw new RuntimeException('Unexpected vanilla defense choice');
 $variables=(string)GetDecisionQueueVariables();$v=$variables===''?[]:json_decode($variables,true,512,JSON_THROW_ON_ERROR);
 if(!is_array($v)||!empty($v['__awaitFrames']))throw new RuntimeException('Unexpected vanilla defense await work');
}

if(array_key_exists('granted_hit_mutation',$spec)){
 if(array_diff(array_keys($spec),['fixture','prefix','granted_hit_mutation'])||!is_string($spec['granted_hit_mutation'])
    ||!isset($spec['prefix'])||!is_int($spec['prefix'])||$spec['prefix']<1)
  throw new RuntimeException('Invalid isolated granted-hit mutation spec');
}
/** Snapshot only: views retain tombstones, metadata and every unmodified row. */
function NCABWorld($source=null,$replacement=null,$choice=null,$choiceReplacement=null): string {
 $zones=['Stack'=>GetStack()];
 foreach([1,2,3,4]as$p)foreach(['Hero','Weapons','Equipment','Arena','Hand','Deck','Pitch','Graveyard','Banish','Soul','Temp','Inventory','CombatChain','DecisionQueue']as$z)$zones['p'.$p.$z]=GetZone('p'.$p.$z);
 foreach($zones as$name=>&$rows)if(is_array($rows))foreach($rows as&$o){if($source!==null&&$o===$source)$o=$replacement;if($choice!==null&&$o===$choice)$o=$choiceReplacement;}unset($rows,$o);
 $assets=[];foreach([1,2,3,4]as$p)$assets[$p]=[GetHealth($p),GetResources($p),GetActionPoints($p)];
 return serialize(['state'=>FaBGetState(),'variables'=>(string)GetDecisionQueueVariables(),'zones'=>$zones,'assets'=>$assets,
   'live'=>FaBLiveSeats(),'winner'=>GetWinner(),'turn'=>GetTurnPlayer(),'priority'=>GetPriorityPlayer(),'passes'=>GetConsecutivePasses(),'phase'=>GetCurrentPhase(),'turnNumber'=>GetTurnNumber()]);
}

/** Eligibility-enumeration mutant only; not supported suppression/stale-answer play. */
function NCApplyABBarrierEligibilityFault(array $binding): void {
 if($binding!==['sourceUID'=>4,'spellUID'=>5,'attackUID'=>3,'victim'=>2,'sourceRef'=>'p2CombatChain-0'])throw new RuntimeException('Invalid isolated modern AB source binding');
 $live=fn($rows)=>array_values(array_filter($rows,fn($o)=>is_object($o)&&empty($o->removed)));
 $s=FaBGetState();$found=FaBFindUID(4);$spell=FaBFindUID(5);$attack=FaBFindUID(3);
 if(FaBLiveSeats()!==[1,2]||intval(GetWinner())!==0||intval(GetTurnPlayer())!==1
    ||($s['combatOpen']??null)!==true||intval($s['chainLink']??0)!==1||intval($s['attackUID']??0)!==3
    ||intval($s['attacker']??0)!==1||intval($s['defender']??0)!==2||($s['combatStep']??'')!=='REACTION'
    ||($s['pendingPayment']??null)!==null||intval($s['resolvingReaction']['uid']??0)!==5
    ||($s['resolvingReaction']['cardID']??'')!=='aether_dart_red'||($s['resolvingReaction']['resolutionClass']??'')!=='PLAIN_SPELL'
    ||intval($s['resolvingReaction']['owner']??0)!==1||intval($s['resolvingReaction']['controller']??0)!==1||($s['resolvingReaction']['sourceMZ']??'')!=='Stack-0'
    ||($s['attackHit']??false)!==false||intval($s['damageDealt']??0)!==0||intval($s['defenseValue']??-1)!==0)
  throw new RuntimeException('Unexpected isolated modern AB combat boundary');
 if(!$found||$found['zone']!=='CombatChain'||$found['mzID']!=='p2CombatChain-0'||intval($found['player'])!==2)throw new RuntimeException('Named defending AB source absent');
 $source=$found['object'];
 if(!empty($source->removed)||$source->CardID!=='aetherstorm_wellingtons'||intval($source->UniqueID??0)!==4||intval($source->Owner??0)!==2||intval($source->Controller??0)!==2
    ||($source->Role??'')!=='DEFENSE'||($source->FromZone??'')!=='Equipment'||intval($source->ChainLink??0)!==1||intval($source->Status??0)!==2||intval($source->Damage??-1)!==0||(array)($source->TurnEffects??[])!==[])
  throw new RuntimeException('Unexpected exact defending AB object');
 if(!$spell||$spell['zone']!=='Stack'||$spell['mzID']!=='Stack-0'||!$attack||$attack['zone']!=='CombatChain'||$attack['mzID']!=='p1CombatChain-0')throw new RuntimeException('Exact spell/attack source absent');
 $layer=$spell['object'];$att=$attack['object'];
 if(!empty($layer->removed)||$layer->CardID!=='aether_dart_red'||($layer->Kind??'')!=='INSTANT'||intval($layer->UniqueID??0)!==5||intval($layer->SourceUniqueID??0)!==5||($layer->SourceZone??'')!=='Banish'||intval($layer->Owner??0)!==1||intval($layer->Controller??0)!==1
    ||!empty($att->removed)||$att->CardID!=='wounding_blow_blue'||($att->Role??'')!=='ATTACK'||($att->FromZone??'')!=='Hand'||intval($att->ChainLink??0)!==1||intval($att->Owner??0)!==1||intval($att->Controller??0)!==1)
  throw new RuntimeException('Unexpected exact AB spell/attack identity');
 foreach([1,2]as$p)if(intval(GetHealth($p))!==15||intval(GetResources($p))!==($p===2?2:0)||intval(GetActionPoints($p))!==0)throw new RuntimeException('Unexpected AB prepayment assets');
 if(count($live(GetStack()))!==1||count($live(GetZone('p2Equipment')))!==0||count($live(GetZone('p2CombatChain')))!==1||count($live(GetZone('p1CombatChain')))!==1)throw new RuntimeException('Unexpected AB source multiplicity');
 foreach([1,2,3,4]as$p)if($p!==2&&count($live(GetDecisionQueue($p)))!==0)throw new RuntimeException('Unexpected foreign AB decision');
 $q=$live(GetDecisionQueue(2));
 if(count($q)!==2||($q[0]->Type??'')!=='MZMAYCHOOSE'||($q[0]->Param??'')!=='p2CombatChain-0'||($q[1]->Type??'')!=='CUSTOM')throw new RuntimeException('Expected sole original modern AB offer/continuation');
 $v=json_decode((string)GetDecisionQueueVariables(),true,512,JSON_THROW_ON_ERROR);$frames=$v['__awaitFrames']??[];
 if(!is_array($frames)||count($frames)!==1)throw new RuntimeException('Expected one exact modern AB await frame');
 $key=array_key_first($frames);$l=$frames[$key]['locals']??[];
 if(!preg_match('/^aether_dart_red:0:ResolveCard#[1-9][0-9]*$/',$key)||($l['mzID']??'')!=='Stack-0'||intval($l['uid']??0)!==5||intval($l['packetSource']??0)!==5||intval($l['packetTarget']??0)!==2
    ||intval($l['victim']??0)!==2||($l['isHero']??null)!==true||intval($l['packet']??0)!==3||($l['used']??null)!==[]||($l['barriers']??'')!=='p2CombatChain-0'
    ||($q[1]->Param??'')!=='aether_dart_red:0:ResolveCard-await|'.$key.'|45|barrier')throw new RuntimeException('Unexpected exact AB frame/payer/source/used set');
 // Audit actual query purity on this exact live world. Never restore mutations.
 $before=NCABWorld();$offered=FaBUPRBarrierChoices(2,[],5);
 if($offered!=='p2CombatChain-0'||NCABWorld()!==$before)throw new RuntimeException('Baseline modern AB enumeration absent or impure');
 $sourceAfter=clone$source;$sourceAfter->TurnEffects=['NO_ABILITIES'];$choiceAfter=clone$q[0];$choiceAfter->Param='';
 $expected=NCABWorld($source,$sourceAfter,$q[0],$choiceAfter);
 $source->TurnEffects=['NO_ABILITIES'];$mutantBefore=NCABWorld();$derived=FaBUPRBarrierChoices(2,[],5);
 if($derived!==''||NCABWorld()!==$mutantBefore)throw new RuntimeException('Suppressed modern AB enumeration present or impure');
 $q[0]->Param=$derived;
 if(NCABWorld()!==$expected)throw new RuntimeException('Modern AB fault changed more than source tag and exact offered Param');
}

/** Exact GA pilot faults only. Runs solely inside the existing disposable helper. */
function NCApplyInnateGAFault(array $entry, object $source): void {
 $b=$entry['innate_ga_source']??null;
 if(!is_array($b)||array_diff(array_keys($entry),['player','zone','cardID','tag','mode','innate_ga_source'])||array_diff(array_keys($b),['control','sourceUID','attackUID']))throw new RuntimeException('Invalid isolated GA effect fields');
 $kind=$b['control']??'';$power=$kind==='innate_ga_printed_power';$defense=$kind==='innate_ga_full_block_defense_loss';
 if(!in_array($kind,['innate_ga_missing_refund','innate_ga_printed_power','innate_ga_full_block_defense_loss'],true))throw new RuntimeException('Unknown isolated GA source fault');
 $cid=$power?'fry_blue':'surging_strike_red';$player=$defense?2:1;$uid=$defense?4:3;$tag=$defense?'WTR_DEFENSE:-2':($power?'WTR_POWER:1':'PEN_BLANK_ATTACK');
 if($b!==['control'=>$kind,'sourceUID'=>$uid,'attackUID'=>3]||$entry['player']!==$player||$entry['zone']!=='CombatChain'||$entry['cardID']!==($defense?'wounding_blow_blue':$cid)||$entry['tag']!==$tag||$entry['mode']!=='add')throw new RuntimeException('Unexpected isolated GA source binding');
 $s=FaBGetState();$live=fn($rows)=>array_values(array_filter($rows,fn($o)=>is_object($o)&&empty($o->removed)));
 if(FaBLiveSeats()!==[1,2]||intval(GetWinner())!==0||intval(GetTurnPlayer())!==1||GetCurrentPhase()!=='MAIN'||intval(GetTurnNumber())!==2||intval(GetPriorityPlayer())!==1||intval(GetConsecutivePasses())!==0
    ||($s['window']??'')!=='REACTION'||($s['combatStep']??'')!=='REACTION'||($s['combatOpen']??null)!==true||intval($s['chainLink']??0)!==1
    ||intval($s['attackUID']??0)!==3||intval($s['attacker']??0)!==1||intval($s['defender']??0)!==2||($s['attackHit']??null)!==false||intval($s['damageDealt']??-1)!==0
    ||intval($s['defenseValue']??-1)!==($power?0:6)||!empty($s['attackGoAgain'])||($s['pendingPayment']??null)!==null||($s['resolvingReaction']??null)!==null)
  throw new RuntimeException('Unexpected actual GA Reaction prefix');
 $target=$s['attackTarget']??[];
 if(($target['type']??'')!=='HERO'||intval($target['player']??0)!==2||intval($target['uid']??0)!==2)throw new RuntimeException('Unexpected actual GA target');
 $v=json_decode((string)GetDecisionQueueVariables(),true,512,JSON_THROW_ON_ERROR);
 if(!is_array($v)||!empty($v['__awaitFrames'])||count($live(GetStack()))!==0)throw new RuntimeException('Unexpected GA pending layer/frame');
 foreach([1,2,3,4]as$p)if(count($live(GetDecisionQueue($p)))!==0)throw new RuntimeException('Unexpected GA chooser');
 foreach([1,2]as$p)if(intval(GetHealth($p))!==20||intval(GetResources($p))!==0||intval(GetActionPoints($p))!==0)throw new RuntimeException('Unexpected GA pre-damage assets');
 $f=FaBFindUID($uid);$a=FaBFindUID(3);
 if(!$f||$f['object']!==$source||$f['zone']!=='CombatChain'||$f['mzID']!=='p'.$player.'CombatChain-0'||intval($f['player'])!==$player||!$a||$a['zone']!=='CombatChain'||$a['mzID']!=='p1CombatChain-0')throw new RuntimeException('Exact GA source UID/reference absent');
 $attack=$a['object'];
 if($attack->CardID!==$cid||intval($attack->Owner??0)!==1||intval($attack->Controller??0)!==1||($attack->Role??'')!=='ATTACK'||($attack->FromZone??'')!=='Hand'||intval($attack->ChainLink??0)!==1||(array)($attack->TurnEffects??[])!==[]
    ||intval(CardPower($attack->CardID))!==($power?1:5)||!FaBPrintedKeywordIsActive($attack->CardID,'Go again'))throw new RuntimeException('Unexpected unmodified printed GA attack');
 if($source->CardID!==$entry['cardID']||intval($source->UniqueID??0)!==$uid||intval($source->Owner??0)!==$player||intval($source->Controller??0)!==$player||($source->Role??'')!==($defense?'DEFENSE':'ATTACK')||($source->FromZone??'')!=='Hand'||intval($source->ChainLink??0)!==1||(array)($source->TurnEffects??[])!==[])throw new RuntimeException('Unexpected named GA object properties');
 $expectedHand=$power?'wounding_blow_blue':'energy_potion_blue';$hand=$live(GetZone('p1Hand'));$p2cc=$live(GetZone('p2CombatChain'));
 if(count($hand)!==1||$hand[0]->CardID!==$expectedHand||intval($hand[0]->UniqueID??0)!==($power?4:6)||count($live(GetZone('p2Hand')))!==0||count($live(GetZone('p1CombatChain')))!==1||count($p2cc)!==($power?0:2))throw new RuntimeException('Unexpected actual GA supporting-card layout');
 foreach($p2cc as$i=>$o)if($o->CardID!=='wounding_blow_blue'||intval($o->UniqueID??0)!==4+$i||intval($o->Owner??0)!==2||intval($o->Controller??0)!==2||($o->Role??'')!=='DEFENSE'||($o->FromZone??'')!=='Hand'||intval($o->ChainLink??0)!==1||(array)($o->TurnEffects??[])!==[]||intval(CardDefense($o->CardID))!==3)throw new RuntimeException('Unexpected exact original Hand blocker');
 foreach([1,2]as$p)foreach(['Weapons','Equipment','Arena','Deck','Pitch','Graveyard','Arsenal','Banish','Soul','Temp','Inventory']as$z)if(count($live(GetZone('p'.$p.$z)))!==0)throw new RuntimeException('Unexpected unrelated GA zone');
 foreach([1,2]as$p){$hero=$live(GetZone('p'.$p.'Hero'));if(count($hero)!==1||$hero[0]->CardID!==($p===1?($power?'aurora':'fai'):'dorinthea')||intval($hero[0]->UniqueID??0)!==$p)throw new RuntimeException('Unexpected legal GA hero');}
 // Do not refresh derived numeric caches after injection: the retained real
 // computed/native assertions must discover the changed effect themselves.
 $before=NCABWorld();$after=clone$source;$after->TurnEffects=[$tag];$expected=NCABWorld($source,$after);
 if(NCABWorld()!==$before)throw new RuntimeException('GA footprint snapshot changed source state');
 $source->TurnEffects=[$tag];
 if(NCABWorld()!==$expected)throw new RuntimeException('GA fault changed more than one original object tag');
}

/** New controls only: validate shape before replaying any disposable prefix. */
function NCValidateWeaponNextSpec(array $spec, int $actionCount): void {
 if(array_diff(array_keys($spec),['fixture','prefix','remove_effect','weapon_next_control'])
    ||!is_string($spec['fixture']??null)||$spec['fixture']===''
    ||($spec['prefix']??null)!==3||$actionCount!==15
    ||!is_string($spec['weapon_next_control']??null))throw new RuntimeException('Invalid isolated weapon-next spec');
 $types=['weapon_next_missing_grant'=>'NEXT_WEAPON','dagger_next_missing_grant'=>'HNT_NEXT','axe_next_missing_grant'=>'DYN_NEXT'];
 if(!isset($types[$spec['weapon_next_control']])||($spec['remove_effect']??null)!==$types[$spec['weapon_next_control']])throw new RuntimeException('Unknown isolated weapon-next binding');
}

/** Read-only exact prefix audit; the existing remove_effect operation follows. */
function NCAuditWeaponNextRemoval(array $spec): void {
 $id=$spec['weapon_next_control'];
 $defs=[
  'weapon_next_missing_grant'=>['en_garde_red','cintari_saber',['type'=>'NEXT_WEAPON','amount'=>3,'uses'=>1]],
  'dagger_next_missing_grant'=>['cut_deep_red','quicksilver_dagger',['type'=>'HNT_NEXT','amount'=>4,'uses'=>1,'kind'=>'DAGGER','tags'=>[],'chain'=>false]],
  'axe_next_missing_grant'=>['felling_swing_red','hatchet_of_body',['type'=>'DYN_NEXT','amount'=>6,'uses'=>1,'kind'=>'AXE','tags'=>[]]],
 ];
 if(!isset($defs[$id]))throw new RuntimeException('Unknown isolated weapon-next context');
 [$card,$weapon,$expected]=$defs[$id];$s=FaBGetState();
 $live=fn($rows)=>array_values(array_filter($rows,fn($o)=>is_object($o)&&empty($o->removed)));
 if(FaBLiveSeats()!==[1,2]||intval(GetWinner())!==0||intval(GetTurnPlayer())!==1||GetCurrentPhase()!=='MAIN'||intval(GetTurnNumber())!==2
    ||intval(GetPriorityPlayer())!==1||intval(GetConsecutivePasses())!==0||($s['window']??'')!=='ACTION'||($s['combatStep']??'')!=='NONE'
    ||($s['combatOpen']??null)!==false||intval($s['chainLink']??-1)!==0||intval($s['attackUID']??-1)!==0
    ||($s['pendingPayment']??null)!==null||($s['resolvingReaction']??null)!==null)
  throw new RuntimeException('Unexpected exact weapon-next action prefix');
 if(count($live(GetStack()))!==0)throw new RuntimeException('Unexpected weapon-next pending layer');
 $vars=json_decode((string)GetDecisionQueueVariables(),true,512,JSON_THROW_ON_ERROR);
 if(!is_array($vars)||!empty($vars['__awaitFrames'])||intval($GLOBALS['fabReactionResolutionDepth']??0)!==0)throw new RuntimeException('Unexpected weapon-next continuation work');
 foreach([1,2,3,4]as$p)if(count($live(GetDecisionQueue($p)))!==0)throw new RuntimeException('Unexpected weapon-next choice');
 foreach([1,2]as$p){
  if(intval(GetHealth($p))!==20||intval(GetResources($p))!==($p===1?1:0)||intval(GetActionPoints($p))!==($p===1?1:0))throw new RuntimeException('Unexpected exact weapon-next assets');
  $heroes=$live(GetHero($p));if(count($heroes)!==1||$heroes[0]->CardID!==($p===1?'boltyn':'ira_crimson_haze')||intval($heroes[0]->UniqueID??0)!==$p||intval($heroes[0]->Owner??0)!==$p||intval($heroes[0]->Controller??0)!==$p)throw new RuntimeException('Unexpected exact weapon-next legal hero');
 }
 $source=FaBFindUID(3);$original=FaBFindUID(4);
 if($source===null||$source['player']!==1||$source['zone']!=='Graveyard'||$source['mzID']!=='p1Graveyard-0'
    ||$source['object']->CardID!==$card||intval($source['object']->Owner??0)!==1||intval($source['object']->Controller??0)!==1
    ||(array)($source['object']->TurnEffects??[])!==[])
  throw new RuntimeException('Unexpected original resolved weapon-next source');
 if($original===null||$original['player']!==1||$original['zone']!=='Weapons'||$original['mzID']!=='p1Weapons-0'
    ||$original['object']->CardID!==$weapon||intval($original['object']->Owner??0)!==1||intval($original['object']->Controller??0)!==1
    ||intval($original['object']->Status??0)!==2||intval($original['object']->Damage??-1)!==0
    ||(array)($original['object']->TurnEffects??[])!==[]||(array)($original['object']->Counters??[])!==[])
  throw new RuntimeException('Unexpected original unactivated weapon');
 foreach([1,2]as$p)foreach(['Weapons','Equipment','Arena','Hand','Deck','Pitch','Graveyard','Banish','Soul','Temp','Inventory','CombatChain']as$zone){
  $objects=$live(GetZone('p'.$p.$zone));$want=$p===1&&in_array($zone,['Weapons','Graveyard'],true)?1:0;
  if(count($objects)!==$want)throw new RuntimeException('Unexpected unrelated weapon-next zone');
 }
 $matches=[];
 foreach((array)($s['turnEffects']??[])as$seat=>$effects){
  if(!is_array($effects)||array_keys($effects)!==array_keys(array_values($effects)))throw new RuntimeException('Unexpected non-list weapon-next effect bucket');
  foreach($effects as$e){if(!is_array($e))throw new RuntimeException('Unexpected weapon-next effect row');if(($e['type']??'')===$spec['remove_effect'])$matches[]=[intval($seat),$e];}
 }
 if($matches!==[[1,$expected]])throw new RuntimeException('Expected sole exact named weapon-next grant');
}

/** Three exact disposable pending CnC hit-layer controls. */
function NCValidateCncHitSpec(array $spec, int $actionCount): void {
 if(array_diff(array_keys($spec),['fixture','prefix','cnc_hit_control'])
    ||!is_string($spec['fixture']??null)||$spec['fixture']===''||($spec['prefix']??null)!==10||$actionCount!==16
    ||!in_array($spec['cnc_hit_control']??null,['cnc_missing_hit_layer','cnc_premature_arsenal_destruction','cnc_wrong_captured_victim'],true))
  throw new RuntimeException('Invalid isolated CnC hit control spec');
}
function NCApplyCncHitFault(string $kind): void {
 if(!in_array($kind,['cnc_missing_hit_layer','cnc_premature_arsenal_destruction','cnc_wrong_captured_victim'],true))throw new RuntimeException('Unknown CnC hit fault');
 $s=FaBGetState();$live=fn($rows)=>array_values(array_filter($rows,fn($o)=>is_object($o)&&empty($o->removed)));
 if(FaBLiveSeats()!==[1,2]||intval(GetWinner())!==0||intval(GetTurnPlayer())!==1||GetCurrentPhase()!=='MAIN'||intval(GetTurnNumber())!==2
    ||intval(GetPriorityPlayer())!==1||intval(GetConsecutivePasses())!==0||($s['window']??'')!=='PRIORITY'||($s['combatStep']??'')!=='DAMAGE'
    ||($s['combatOpen']??null)!==true||intval($s['chainLink']??0)!==1||intval($s['attackUID']??0)!==3
    ||intval($s['attacker']??0)!==1||intval($s['defender']??0)!==2||($s['attackTarget']['type']??'')!=='HERO'
    ||intval($s['attackTarget']['player']??0)!==2||intval($s['attackTarget']['uid']??0)!==2
    ||($s['attackHit']??null)!==true||intval($s['damageDealt']??-1)!==6||intval($s['defenseValue']??-1)!==0
    ||($s['pendingPayment']??null)!==null||($s['resolvingReaction']??null)!==null||intval($GLOBALS['fabReactionResolutionDepth']??0)!==0)
  throw new RuntimeException('Unexpected exact pending CnC hit boundary');
 $vars=json_decode((string)GetDecisionQueueVariables(),true,512,JSON_THROW_ON_ERROR);
 if(!is_array($vars)||!empty($vars['__awaitFrames']))throw new RuntimeException('Unexpected CnC continuation');
 foreach([1,2,3,4]as$p)if(count($live(GetDecisionQueue($p)))!==0)throw new RuntimeException('Unexpected CnC choice');
 foreach([1,2]as$p){
  if(intval(GetHealth($p))!==($p===1?20:14)||intval(GetResources($p))!==0||intval(GetActionPoints($p))!==0)throw new RuntimeException('Unexpected CnC exact assets');
  $heroes=$live(GetHero($p));if(count($heroes)!==1||$heroes[0]->CardID!=='dorinthea'||intval($heroes[0]->UniqueID??0)!==$p||intval($heroes[0]->Owner??0)!==$p||intval($heroes[0]->Controller??0)!==$p)throw new RuntimeException('Unexpected CnC exact heroes');
  foreach(['Weapons','Equipment','Arena','Hand','Deck','Pitch','Graveyard','Banish','Soul','Temp','Inventory','CombatChain','Arsenal']as$zone){
   $want=($p===1&&$zone==='CombatChain')||($p===2&&$zone==='Arsenal')?1:0;
   if(count($live(GetZone('p'.$p.$zone)))!==$want)throw new RuntimeException('Unexpected unrelated CnC zone');
  }
 }
 $attack=FaBFindUID(3);$arsenal=FaBFindUID(4);$layers=$live(GetStack());
 if($attack===null||$attack['player']!==1||$attack['zone']!=='CombatChain'||$attack['mzID']!=='p1CombatChain-0'
    ||$attack['object']->CardID!=='command_and_conquer_red'||intval($attack['object']->Owner??0)!==1||intval($attack['object']->Controller??0)!==1
    ||($attack['object']->Role??'')!=='ATTACK'||($attack['object']->FromZone??'')!=='Hand'||intval($attack['object']->ChainLink??0)!==1
    ||(array)($attack['object']->TurnEffects??[])!==[]||(array)($attack['object']->Counters??[])!==['FAB_PLAY_ORDER'=>1])
  throw new RuntimeException('Unexpected original physical CnC source');
 if($arsenal===null||$arsenal['player']!==2||$arsenal['zone']!=='Arsenal'||$arsenal['mzID']!=='p2Arsenal-0'
    ||$arsenal['object']->CardID!=='wounding_blow_blue'||intval($arsenal['object']->Owner??0)!==2||intval($arsenal['object']->Controller??0)!==2
    ||intval($arsenal['object']->FaceDown??0)!==1||(array)($arsenal['object']->TurnEffects??[])!==[]||(array)($arsenal['object']->Counters??[])!==[]
    ||array_key_exists('penNames',$s['arcCards'][4]??[])||array_key_exists('penColor',$s['arcCards'][4]??[]))
  throw new RuntimeException('Unexpected exact current victim Arsenal');
 if(count($layers)!==1)throw new RuntimeException('Expected one exact queued CnC layer');
 $layer=$layers[0];$uid=intval($layer->UniqueID??0);$found=FaBFindUID($uid);
 if($uid<=4||$found===null||$found['zone']!=='Stack'||$found['mzID']!=='Stack-0'||$found['object']!==$layer
    ||$layer->CardID!=='command_and_conquer_red'||($layer->Kind??'')!=='ABILITY'||intval($layer->Owner??0)!==1||intval($layer->Controller??0)!==1
    ||($layer->SourceZone??'')!=='CombatChain'||intval($layer->SourceUniqueID??0)!==3
    ||($layer->Params??null)!==['wtrCncVictim'=>2,'returnWindow'=>'DAMAGE','returnCombatStep'=>'DAMAGE'])
  throw new RuntimeException('Unexpected CnC captured-victim/source layer');
 if($kind!=='cnc_premature_arsenal_destruction'){
  $expected=clone$layer;
  if($kind==='cnc_missing_hit_layer')$expected->removed=true;
  else $expected->Params['wtrCncVictim']=1;
  $world=NCABWorld($layer,$expected);
  if($kind==='cnc_missing_hit_layer')$layer->removed=true;
  else $layer->Params['wtrCncVictim']=1;
  if(NCABWorld()!==$world)throw new RuntimeException('CnC fault changed more than one original layer field');
  return;
 }
 // Deliberately execute the existing effect too early, retaining its pending layer.
 // Keep real MONDestroy and movement callbacks; never suppress/restore their effects.
 $before=unserialize(NCABWorld());$hadActor=array_key_exists('fabEffectController',$GLOBALS);$actor=$hadActor?$GLOBALS['fabEffectController']:null;
 FaBWTRResolveCommandAndConquerHit(1,$layer);
 $after=unserialize(NCABWorld());$moved=FaBFindUID(4);
 if($moved===null||$moved['player']!==2||$moved['zone']!=='Graveyard'||$moved['mzID']!=='p2Graveyard-0'
    ||$moved['object']->CardID!=='wounding_blow_blue'||intval($moved['object']->Owner??0)!==2||intval($moved['object']->Controller??0)!==2
    ||(array)($moved['object']->TurnEffects??[])!==[]||(array)($moved['object']->Counters??[])!==[]||empty($arsenal['object']->removed)
    ||count($live(GetZone('p2Arsenal')))!==0||count($live(GetZone('p2Graveyard')))!==1
    ||array_key_exists('fabEffectController',$GLOBALS)!==$hadActor||($hadActor&&$GLOBALS['fabEffectController']!==$actor))
  throw new RuntimeException('Premature CnC effect did not preserve physical identity/actor');
 $expected=$before;
 foreach([1,2]as$p)$expected['state']['turnEffects'][$p][]=['type'=>'EVR_GRAVEYARD','amount'=>1,'uses'=>1];
 $expected['state']['turnEffects'][2][]=['type'=>'SEA_BLUE_GRAVE','amount'=>1,'uses'=>1];
 $expected['state']['arcCards'][4]['iarPermission']=[];
 // Only the two moved-card rows vary by generated zone representation; audit
 // their physical fields above and compare every other state/zone/asset exactly.
 foreach(['p2Arsenal','p2Graveyard']as$z){unset($expected['zones'][$z],$after['zones'][$z]);}
 if(serialize($after)!==serialize($expected))throw new RuntimeException('Premature CnC effect changed unexpected world components');
}

/** New opt-in mutations in existing disposable-fixture helper; not a game runner. */
function NCFourSourceBindings(): array { return json_decode('{"card-sigil-of-solace-red-four-live-hold-priority-correct-rule-before-probe":{"initial_gamestate.txt":"d09fc863fc6e810df6dc49c7aceccdff835b56f72b96a8c2a1e3fd31f9d8336d","actions.json":"46f52a911b52e5dd9ba5001f62869e6a7ede2618717f4117f01c2681f229811e","assertions.json":"5d234e6c97a5a80a0c18e9f867c90ef396d40a9671653a365a80bf05dcb95470","expected_final_gamestate.txt":"6d6cf85e59fb4a721651f90b2c64a57e482f1ab390b3aeaab274eb5f012468de","meta.json":"2d8fde6036184540e0075402ff2d5dc14cbb26579af7e9e2beef5dc305be1e86"},"card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4":{"initial_gamestate.txt":"ac60286980012708a041bd926fa6ab7622c74309eb8a40bd14c7fb8b2b910a02","actions.json":"f3af111339ea2cc984793321dd7b91d5f03c81cad31164befdb24cda346a5701","assertions.json":"4631353746996bbab3c6c436b25ecbac4520a8329cc6bdff713085892cf6d6e2","expected_final_gamestate.txt":"459bcd968f16853b210f97f15a7bd4f87dfe0ec644657053178e262e782a0c27","meta.json":"21c5f2f578cb90efc80d264721a695b7767f600d84fc378ae6d9025caf2626c9"},"card-strike-gold-red-upf-four-live-adjacent-four-all-seat-sigil-responses":{"initial_gamestate.txt":"8b7bcd8981b60d2e7d779982ebecbcce01e27f9ea1d95afd37f8897b3a3c0167","actions.json":"602bb44013ed20ff7eeba0e86821d7a7a36f2bf2706720d9758141e1e19d55ee","assertions.json":"17036116e71ca6bded70d64fcc1b611d674baff97929262bdfe221799c60839e","expected_final_gamestate.txt":"f53ca1bfe632d9030b2749f8005e8e18a33e525659c0fbce7e376dee6b2b5112","meta.json":"dc0ca2c00f5c6962ff75f1ffa8eb2cd4ee0226100f70c603ce6d98489045845f"}}',true,512,JSON_THROW_ON_ERROR); }
function NCValidateFourLiveSpec(array $spec, int $actionCount): void {
 if(array_diff(array_keys($spec),['fixture','prefix','four_live_control'])||!is_string($spec['fixture']??null)||$spec['fixture']===''
    ||!is_int($spec['prefix']??null)||!is_string($spec['four_live_control']??null))throw new RuntimeException('Invalid isolated four-live control spec');
 $id=$spec['four_live_control'];
 $cases=[
  'four_sigil_hold_false'=>['card-sigil-of-solace-red-four-live-hold-priority-correct-rule-before-probe',3,8],
  'four_dart_source_lost_during_victim4_choice'=>['card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4',13,14],
  'four_dart_wrong_arcane_actor'=>['card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4',14,14],
  'four_dart_nonadjacent_offer'=>['card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4',1,14]];
 if(!isset($cases[$id])||[$spec['prefix'],$actionCount]!==array_slice($cases[$id],1))throw new RuntimeException('Unexpected exact four-live prefix or action count');
 $binding=NCFourSourceBindings()[$cases[$id][0]]??null;
 if(!is_array($binding)||count($binding)!==5)throw new RuntimeException('Four-live actual raw5 binding pending; no mutation permitted');
 foreach($binding as$name=>$hash)if(!is_file($spec['fixture'].'/'.$name)||hash_file('sha256',$spec['fixture'].'/'.$name)!==$hash)throw new RuntimeException('Four-live canonical source bytes changed: '.$name);
}
function NCFourWorld($source=null,$replacement=null,$choice=null,$choiceReplacement=null): string {
 $prefs=[];foreach([1,2,3,4]as$p)$prefs[$p]=GetShortcutPreferencesState($p);
 return serialize(['world'=>NCABWorld($source,$replacement,$choice,$choiceReplacement),'prefs'=>$prefs,'seatOrder'=>(string)GetSeatOrder(),'liveSeats'=>(string)GetLiveSeats()]);
}
function NCApplyFourLiveFault(string $id): void {
 $sigil=$id==='four_sigil_hold_false';
 if(!$sigil&&!in_array($id,['four_dart_source_lost_during_victim4_choice','four_dart_wrong_arcane_actor','four_dart_nonadjacent_offer'],true))throw new RuntimeException('Unknown four-live fault');
 $live=fn($rows)=>array_values(array_filter($rows,fn($o)=>is_object($o)&&empty($o->removed)));
 $state=FaBGetState();$terminal=$id==='four_dart_wrong_arcane_actor';$prepare=$id==='four_dart_nonadjacent_offer';
 if(FaBSeatOrder()!==[1,2,3,4]||FaBLiveSeats()!==[1,2,3,4]||(string)GetSeatOrder()!=='1234'||(string)GetLiveSeats()!=='1234'
    ||intval(GetWinner())!==0||intval(GetTurnPlayer())!==1||intval(GetTurnNumber())!==2||GetCurrentPhase()!=='MAIN'
    ||($state['gameMode']??'')!=='UPF'||($state['passiveSeats']??null)!==[]||($state['combatOpen']??null)!==false)
  throw new RuntimeException('Unexpected four-live profile or turn');
 foreach([1,2,3,4]as$p){
  $hero=$live(GetHero($p));$expected=$sigil?'dorinthea':['kano','ira_crimson_haze','dorinthea','katsu'][$p-1];
  if(count($hero)!==1||$hero[0]->CardID!==$expected||intval($hero[0]->UniqueID??0)!==$p||intval($hero[0]->Owner??0)!==$p||intval($hero[0]->Controller??0)!==$p)
   throw new RuntimeException('Unexpected exact four-live hero');
  $hp=$sigil?20:($p===1?15:($p===3&&!$prepare?23:($p===4&&$terminal?18:20)));
  if(intval(GetHealth($p))!==$hp||intval(GetResources($p))!==0||intval(GetActionPoints($p))!==($p===1&&($sigil||$prepare)?1:0))throw new RuntimeException('Unexpected exact four-live economy');
  $prefs=GetShortcutPreferencesState($p);
  if(($prefs['holdPriority']??null)!==true||!is_array($prefs['windows']??null)||array_filter($prefs['windows'],fn($v)=>$v!==false))throw new RuntimeException('Expected all four held/manual preferences');
 }
 $variables=(string)GetDecisionQueueVariables();$decoded=$sigil&&$variables===''?[]:json_decode($variables,true,512,JSON_THROW_ON_ERROR);
 if(!is_array($decoded))throw new RuntimeException('Unexpected four-live decision variables');
 if($sigil){
  if(($state['window']??'')!=='ACTION'||($state['pendingPayment']??null)!==null||($state['resolvingReaction']??null)!==null
     ||intval(GetPriorityPlayer())!==4||intval(GetConsecutivePasses())!==3||count($live(GetStack()))!==0||!empty($decoded['__awaitFrames']))throw new RuntimeException('Expected exact pre-cast four-live Sigil boundary');
  foreach([1,2,3,4]as$p)if(count($live(GetDecisionQueue($p)))!==0)throw new RuntimeException('Unexpected pre-cast four-live decision');
  $found=FaBFindUID(5);
  if($found===null||$found['zone']!=='Hand'||$found['mzID']!=='p4Hand-0'||intval($found['player'])!==4)throw new RuntimeException('Expected original P4 Hand source UID5');
  $source=$found['object'];
  if($source->CardID!=='sigil_of_solace_red'||intval($source->Owner??0)!==4||intval($source->Controller??0)!==4||count($live(GetHand(4)))!==1)throw new RuntimeException('Unexpected original physical Sigil source');
  $before=unserialize(NCFourWorld());$prefs=GetShortcutPreferencesState(4);$prefs['holdPriority']=false;
  $before['prefs'][4]=$prefs;SetShortcutPreferencesState(4,$prefs);
  if(NCFourWorld()!==serialize($before))throw new RuntimeException('Four-live hold fault changed more than P4 holdPriority');
  return;
 }
 $found=FaBFindUID(5);$where=$terminal?'Graveyard':'Stack';$ref=$terminal?'p1Graveyard-0':'Stack-0';
 if($found===null||$found['zone']!==$where||$found['mzID']!==$ref)throw new RuntimeException('Expected exact original Dart source location');
 $source=$found['object'];
 if($source->CardID!=='aether_dart_red'||intval($source->UniqueID??0)!==5||intval($source->Owner??0)!==1||intval($source->Controller??0)!==1||!empty($source->removed)
    ||(!$terminal&&(($source->Kind??'')!=='ACTION'||($source->SourceZone??'')!=='Hand'||intval($source->SourceUniqueID??0)!==5)))throw new RuntimeException('Unexpected original physical Dart UID5');
 $queues=[];foreach([1,2,3,4]as$p)$queues[$p]=$live(GetDecisionQueue($p));
 if($terminal){
  if(($state['window']??'')!=='ACTION'||($state['pendingPayment']??null)!==null||($state['resolvingReaction']??null)!==null||($state['arcaneDealt']??null)!==[1=>[4=>2]]
     ||intval(GetPriorityPlayer())!==1||intval(GetConsecutivePasses())!==0||count($live(GetStack()))!==0||!empty($decoded['__awaitFrames'])||array_filter($queues))throw new RuntimeException('Expected completed real P4 prevention and original caster attribution');
  $cloak=FaBFindUID(6);$sigilCard=FaBFindUID(7);
  if($cloak===null||$cloak['mzID']!=='p4Graveyard-0'||$cloak['object']->CardID!=='spell_fray_cloak'||intval($cloak['object']->Owner??0)!==4||intval($cloak['object']->Controller??0)!==4
     ||$sigilCard===null||$sigilCard['mzID']!=='p3Graveyard-0'||$sigilCard['object']->CardID!=='sigil_of_solace_red')throw new RuntimeException('Expected real completed physical source destinations');
  $before=unserialize(NCFourWorld());$world=unserialize($before['world']);$state['arcaneDealt']=[4=>[4=>2]];$world['state']=$state;$before['world']=serialize($world);
  FaBSetState($state);if(NCFourWorld()!==serialize($before))throw new RuntimeException('Wrong actor fault changed more than arcaneDealt');return;
 }
 $chooser=$prepare?1:4;$type=$prepare?'MZCHOOSE':'MZMAYCHOOSE';$param=$prepare?'p2Hero-0&p4Hero-0':'p4Equipment-0';$tip=$prepare?'Choose_damage_target':'Destroy_Spellvoid';
 foreach([1,2,3,4]as$p)if($p!==$chooser&&count($queues[$p])!==0)throw new RuntimeException('Unexpected unrelated four-live decision');
 $queueShape=count($live(GetStack()))===1&&count($queues[$chooser])===($prepare?4:2)&&$queues[$chooser][0]->Type===$type&&$queues[$chooser][0]->Param===$param&&$queues[$chooser][0]->Tooltip===$tip&&$queues[$chooser][1]->Type==='CUSTOM';
 if($prepare&&$queueShape){
  foreach([0,1]as$i)if(intval($queues[1][$i]->Block??-1)!==1)$queueShape=false;
  foreach([['PASSPARAMETER','1'],['SYSTEM','PlayCard_AfterAction']]as$i=>$expected){
   $row=$queues[1][$i+2];
   if(($row->Type??'')!==$expected[0]||(string)($row->Param??'')!==$expected[1]||intval($row->Block??-1)!==99
      ||(string)($row->Tooltip??'')!==''||intval($row->DontSkipOnPass??-1)!==0||!empty($row->removed))$queueShape=false;
  }
 }
 if(!$queueShape){
  $queueAudit=[];foreach($queues as$seat=>$rows)$queueAudit[$seat]=['count'=>count($rows),'rows'=>array_map(fn($r)=>['type'=>$r->Type??null,'param'=>$r->Param??null,'block'=>$r->Block??null,'tooltip'=>$r->Tooltip??null,'dontSkipOnPass'=>$r->DontSkipOnPass??null],$rows)];
  throw new RuntimeException('Expected exact real P1target or P4Spellvoid queue: '.json_encode(['stage'=>$prepare?'PREPARE':'RESOLVE','stackLive'=>count($live(GetStack())),'queues'=>$queueAudit],JSON_THROW_ON_ERROR));
 }
 $macro=$prepare?'PrepareCard':'ResolveCard';$tail=$prepare?'1|chosen':'26|void';$raw=(string)$queues[$chooser][1]->Param;
 if(preg_match('/^aether_dart_red:0:'.$macro.'-await\|(aether_dart_red:0:'.$macro.'#[0-9]+)\|'.preg_quote($tail,'/').'$/D',$raw,$match)!==1)throw new RuntimeException('Unexpected exact original Dart continuation binding');
 $locals=$decoded['__awaitFrames'][$match[1]]['locals']??null;
 if(!is_array($locals)||intval($locals['uid']??0)!==5||($locals['mzID']??'')!=='Stack-0')throw new RuntimeException('Unexpected original Dart source frame');
 if($prepare){
  $payment=$state['pendingPayment']??null;
  if(($state['window']??'')!=='PITCH'||($state['resolvingReaction']??null)!==null||!is_array($payment)||intval($payment['uid']??0)!==5||intval($payment['player']??0)!==1||($payment['kind']??'')!=='ACTION'||intval($payment['cost']??-1)!==0)throw new RuntimeException('Expected actual Dart target preparation before costs');
  $choice=$queues[1][0];$replacement=clone$choice;$replacement->Param='p2Hero-0&p3Hero-0&p4Hero-0';$expected=NCFourWorld(null,null,$choice,$replacement);
  $choice->Param=$replacement->Param;if(NCFourWorld()!==$expected)throw new RuntimeException('Adjacency fault changed more than one real offered Param');return;
 }
 $marker=$state['resolvingReaction']??null;$cloak=FaBFindUID(6);
 if(($state['window']??'')!=='PRIORITY'||($state['pendingPayment']??null)!==null||!is_array($marker)||($marker['resolutionClass']??'')!=='PLAIN_SPELL'||intval($marker['uid']??0)!==5||intval($marker['controller']??0)!==1||intval($marker['owner']??0)!==1||($marker['cardID']??'')!=='aether_dart_red'
    ||intval($locals['packetSource']??0)!==5||intval($locals['packetTarget']??0)!==4||intval($locals['victim']??0)!==4||intval($locals['packet']??0)!==3
    ||$cloak===null||$cloak['mzID']!=='p4Equipment-0'||$cloak['object']->CardID!=='spell_fray_cloak'||intval($cloak['object']->Owner??0)!==4||intval($cloak['object']->Controller??0)!==4)throw new RuntimeException('Expected live P4 Spellvoid before packet and source cleanup');
 $replacement=clone$source;$replacement->removed=true;$expected=NCFourWorld($source,$replacement);$source->removed=true;
 if(NCFourWorld()!==$expected)throw new RuntimeException('Lost source fault changed more than original physical removed flag');
}

$gameName='regression_fab_negative_'.uniqid();$gameDir=$repo.'/FaBSim/Games/'.$gameName;
try{
 RegressionEnsureDir($gameDir);copy($target.'/initial_gamestate.txt',$gameDir.'/Gamestate.txt');
 ParseGamestate('./FaBSim/');
 $actions=json_decode(file_get_contents($target.'/actions.json'),true,512,JSON_THROW_ON_ERROR);
 if(array_key_exists('four_live_control',$spec))NCValidateFourLiveSpec($spec,count($actions));
 if(array_key_exists('cnc_hit_control',$spec))NCValidateCncHitSpec($spec,count($actions));
 if(array_key_exists('weapon_next_control',$spec))NCValidateWeaponNextSpec($spec,count($actions));
 if(array_key_exists('state_assertion_mutation',$spec))NCValidateStateAssertionSpec($spec,count($actions));
 if(array_key_exists('blade_break_clear',$spec))NCValidateBladeBreakClearSpec($spec,count($actions));
 if(array_key_exists('ab_barrier_fault',$spec)&&(array_diff(array_keys($spec),['fixture','prefix','ab_barrier_fault'])||!isset($spec['prefix'])||!is_int($spec['prefix'])||$spec['prefix']<1||$spec['prefix']>=count($actions)||!is_array($spec['ab_barrier_fault'])))throw new RuntimeException('Invalid isolated modern AB fault spec');
 foreach(array_slice($actions,0,intval($spec['prefix']??0)) as $action){
  $result=EngineRunAction($action,'FaBSim',$gameName,['updateCache'=>false,'disableRecording'=>true]);
  if(!$result['success'])throw new RuntimeException('Negative-control prefix action failed');
 }
 if(array_key_exists('ab_barrier_fault',$spec)){
  ParseGamestate('./FaBSim/'); // New AB fault alone audits the persisted action boundary.
  NCApplyABBarrierEligibilityFault($spec['ab_barrier_fault']);
 }
 if(array_key_exists('four_live_control',$spec)){ParseGamestate('./FaBSim/');NCApplyFourLiveFault($spec['four_live_control']);}
 if(array_key_exists('cnc_hit_control',$spec)){ParseGamestate('./FaBSim/');NCApplyCncHitFault($spec['cnc_hit_control']);}
 if(isset($spec['granted_hit_mutation']))NCApplyGrantedHitMutation($spec['granted_hit_mutation']);
 if(isset($spec['state_assertion_mutation']))NCApplyStateAssertionMutation($spec['state_assertion_mutation']);
 if(isset($spec['blade_break_clear']))NCApplyBladeBreakClear();
 foreach($spec['add_hand']??[] as $entry)AddHand(intval($entry['player']),CardID:$entry['cardID'],Owner:intval($entry['player']),Controller:intval($entry['player']));
 foreach($spec['set_health']??[] as $entry){
  $seat=intval($entry['player']);$value=intval($entry['value']);
  if(!in_array($seat,[1,2],true)||$value<1||$value>100)throw new RuntimeException('Invalid negative-control health override');
  AddHealth($seat,$value);
 }
 if(isset($spec['remove_stack_param'])){
  $removed=0;
  foreach(GetStack() as $layer)if(is_object($layer)&&empty($layer->removed)&&isset($layer->Params[$spec['remove_stack_param']])){unset($layer->Params[$spec['remove_stack_param']]);++$removed;}
  if($removed!==1)throw new RuntimeException('Expected one isolated stack parameter to remove');
 }
 if(isset($spec['replace_stack_card'])){
  $changed=0;
  foreach(GetStack() as $layer)if(is_object($layer)&&empty($layer->removed)&&$layer->CardID===$spec['replace_stack_card']['from']){$layer->CardID=$spec['replace_stack_card']['to'];++$changed;}
  if($changed!==1)throw new RuntimeException('Expected one isolated layer identity to replace');
 }
 $gaEntries=array_values(array_filter((array)($spec['object_effect']??[]),fn($e)=>is_array($e)&&array_key_exists('innate_ga_source',$e)));
 if($gaEntries){
  if(array_diff(array_keys($spec),['fixture','prefix','object_effect'])||count($gaEntries)!==1||count($spec['object_effect'])!==1||!is_int($spec['prefix']??null)||!in_array($spec['prefix'],[8,10],true)||$spec['prefix']>=count($actions))throw new RuntimeException('Invalid isolated exact GA fault spec');
  ParseGamestate('./FaBSim/'); // Only new GA faults inspect persisted prefix coordinates.
 }
 foreach($spec['object_effect']??[] as $entry){
  $objects=$entry['zone']==='Stack'?GetStack():GetZone('p'.intval($entry['player']).$entry['zone']);$matches=[];
  foreach($objects as $obj)if(is_object($obj)&&empty($obj->removed)&&$obj->CardID===$entry['cardID'])$matches[]=$obj;
  if(isset($entry['innate_ga_source']))$matches=array_values(array_filter($matches,fn($o)=>intval($o->UniqueID??0)===intval($entry['innate_ga_source']['sourceUID']??0)));
  if(count($matches)!==1)throw new RuntimeException('Expected one isolated effect source');
  if(isset($entry['innate_ga_source'])){NCApplyInnateGAFault($entry,$matches[0]);continue;}
  $obj=$matches[0];NCValidateVanillaDefenseSource($entry,$obj);$tag=$entry['tag'];$effects=(array)$obj->TurnEffects;
  if($entry['mode']==='add'){
   if(in_array($tag,$effects,true))throw new RuntimeException('Isolated added effect already exists');
   $effects[]=$tag;
  }else{
   if(count(array_filter($effects,fn($e)=>$e===$tag))!==1)throw new RuntimeException('Expected exactly one isolated object effect');
   $effects=array_values(array_filter($effects,fn($e)=>$e!==$tag));
  }
  $obj->TurnEffects=$effects;
 }
 foreach($spec['move_card']??[] as $entry){
  $matches=[];
  foreach(FaBChoiceRefs(intval($entry['player']),$entry['from']) as $ref){$f=FaBIdentityFromMZ($ref);if($f['object']->CardID===$entry['cardID'])$matches[]=$f;}
  if(count($matches)!==1)throw new RuntimeException('Expected one initial card to move');
  FaBMoveUID(intval($matches[0]['object']->UniqueID),$entry['to'],intval($entry['player']),false);
 }
 if(array_key_exists('weapon_next_control',$spec)){ParseGamestate('./FaBSim/');NCAuditWeaponNextRemoval($spec);}
 if(isset($spec['remove_effect'])){
  $state=FaBGetState();$removed=0;
  foreach($state['turnEffects']??[] as $seat=>$effects){
   $state['turnEffects'][$seat]=array_values(array_filter($effects,function($e)use($spec,&$removed){if(($e['type']??'')===$spec['remove_effect']){++$removed;return false;}return true;}));
  }
  if($removed!==1)throw new RuntimeException('Expected exactly one isolated effect to remove');
  FaBSetState($state);
 }
 WriteGamestate('./FaBSim/');copy($gameDir.'/Gamestate.txt',$target.'/initial_gamestate.txt');
}finally{
 if(str_starts_with($gameDir,$repo.'/FaBSim/Games/regression_fab_negative_')){RegressionClearGamestateMemory($gameName);RegressionDeleteDirRecursive($gameDir);}
}
