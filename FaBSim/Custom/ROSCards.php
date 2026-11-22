<?php
/** Rosetta rules. Choice flows remain in saved, resumable card macros. */
function FaBROSCount(int $p,string $k): int {return FaBARCEffect($p,'ROS_'.$k);}
function FaBROSAdd(int $p,string $k,int $n=1,array $data=[]): void {
 if($k==='PREVENT'&&!empty($data['token'])){
  if(!is_string($data['token'])||$data['token']==='')throw new RuntimeException('Prevention token grant requires a token identity.');
  $data['preventionTrigger']=FaBROSPreventionTokenOrigin($p,$data['token']);
 }
 FaBWTRAddEffect($p,'ROS_'.$k,$n,$data);
}
function FaBROSClear(int $p,string $k): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='ROS_'.$k)));}
function FaBROSEarth(int $p): int {return count(array_filter(FaBChoiceRefs($p,'Banish',['type'=>'Earth']),fn($r)=>empty(FaBIdentityFromMZ($r)['object']->FaceDown)));}
function FaBROSActive(int $p,string $hero): bool {foreach(FaBChoiceRefs($p,'Hero') as $r){$o=FaBIdentityFromMZ($r)['object'];if(($o->CardID===$hero||str_starts_with($o->CardID,$hero.'_'))&&!HasNoAbilities($o)&&FaBROSEarth($p)>=(FaBHasType($o,'Young')?4:8))return true;}return false;}
function FaBROSSigils(int $p,string $zone='Arena'): string {return implode('&',array_filter(FaBChoiceRefs($p,$zone,['type'=>'Aura']),fn($r)=>str_contains(CardName(FaBIdentityFromMZ($r)['object']->CardID),'Sigil')));}
function FaBROSRefs(int $p,string $zone,string $type='',int $exclude=0): string {return implode('&',array_filter(FaBChoiceRefs($p,$zone),function($r)use($type,$exclude){$o=FaBIdentityFromMZ($r)['object'];return intval($o->UniqueID)!==$exclude&&($type===''||($type==='NAA'?FaBHasType($o,'Action')&&!FaBHasType($o,'Attack'):FaBHasType($o,$type)));}));}
function FaBROSAllAuras(int $p,bool $tokens=true,int $max=999,bool $all=false): string {$out=[];foreach($all?FaBLiveSeats():array_merge([$p],FaBAdjacentOpponents($p)) as $v)foreach(FaBChoiceRefs($v,'Arena',['type'=>'Aura']) as $r){$o=FaBIdentityFromMZ($r)['object'];if((!$tokens&&FaBHasType($o,'Token'))||intval(CardCost($o->CardID))>$max)continue;$out[]=$r;}return implode('&',$out);}
function FaBROSDecomposeReady(int $p): bool {$earth=FaBChoiceRefs($p,'Graveyard',['type'=>'Earth']);$action=FaBChoiceRefs($p,'Graveyard',['type'=>'Action']);return count($earth)>=2&&count(array_unique(array_merge($earth,$action)))>=3&&count($action)>0;}
function FaBROSDecompose(int $p,string $earth,string $action): bool {$e=FaBUPRUIDs($earth);$a=FaBUPRUIDs($action);if(count($e)!==2||count($a)!==1||in_array($a[0],$e,true))return false;foreach(array_merge($e,$a) as $i=>$uid){$f=FaBFindUID($uid);if(!$f||$f['player']!==$p||$f['zone']!=='Graveyard'||!FaBHasType($f['object'],$i<2?'Earth':'Action'))return false;}foreach(array_merge($e,$a) as $uid)FaBMoveUID($uid,'Banish',$p);return true;}
function FaBROSActionExcept(int $p,string $earth): string {$ids=FaBUPRUIDs($earth);return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard',['type'=>'Action']),fn($r)=>!in_array(intval(FaBIdentityFromMZ($r)['object']->UniqueID),$ids,true)));}
function FaBROSDecomposeEarth(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Graveyard',['type'=>'Earth']),function($r)use($p){$u=intval(FaBIdentityFromMZ($r)['object']->UniqueID);return count(array_filter(FaBChoiceRefs($p,'Graveyard',['type'=>'Action']),fn($a)=>intval(FaBIdentityFromMZ($a)['object']->UniqueID)!==$u))>0;}));}
function FaBROSReturn(string $refs,string $zone='Hand'): void {foreach(FaBUPRUIDs($refs) as $uid){$f=FaBFindUID($uid);if($f)FaBMoveUID($uid,$zone,intval($f['object']->Owner??$f['player'])?:$f['player']);}}
function FaBROSShuffle(int $p,string $refs): void {$seats=[];foreach(FaBUPRUIDs($refs) as $uid){$f=FaBFindUID($uid);if(!$f)continue;$v=intval($f['object']->Owner??$f['player'])?:$f['player'];FaBMoveUID($uid,'Deck',$v);$seats[$v]=true;}foreach(array_keys($seats) as $v)FaBShuffleDeck($v);}
function FaBROSMark(int $uid,string $key): void {FaBARCSetCard($uid,'ros'.$key,true);}
function FaBROSQueue(int $p,string $id,int $uid=0,array $extra=[]): void {if(!FaBSeatIsLive($p))return;AddStack(CardID:$id,Controller:$p,Kind:'ABILITY',SourceUniqueID:$uid,Params:['rosTrigger'=>true]+$extra);$s=FaBGetState();if($s['pendingPayment']===null)$s['window']='PRIORITY';FaBSetState($s);}
/** Burn's Aura owns the ability, but its captured attacking Dragon deals damage. */
function FaBROSAbilityMacroSourceUID(object $layer): int {
 if(($layer->Kind??'')==='ABILITY'&&!empty($layer->Params['rosTrigger'])
  &&(string)$layer->CardID==='burn_them_all_red'
  &&in_array((string)($layer->Params['rosEvent']??''),['','ability'],true)
  &&intval($layer->Params['rosDamageSourceUID']??0)>0)return intval($layer->Params['rosDamageSourceUID']);
 return intval($layer->SourceUniqueID??0);
}
function FaBROSResolveTrigger(int $p,object $o): void {
 if(($o->Params['rosEvent']??'')==='iarHellboundHit'&&FaBIARHellboundCard((string)$o->CardID)){FaBIARResolveHellboundHit($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='omnLeaveToken'){FaBOMNResolveLeaveToken($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='iarMaliceZombieDied'){FaBIARResolveMaliceDeath($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='mstBastionDefend'){FaBMSTResolveBastionDefense($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='outScalePeelerHit'){FaBOUTResolveScalePeelerHit($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='hvyAssaultAttack'){FaBHVYResolveAssaultAttack($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='eleEmboldenEntryDraw'){FaBELEResolveEmboldenEntry($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='monPulpingAttack'){FaBMONResolvePulpingAttack($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='wtrDisableCrush'){FaBWTRResolveDisableCrush($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='monPoundForPoundPlay'){FaBMONResolvePoundPlay($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='monRiftBindBanishPlay'){FaBMONResolveRiftBindPlay($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='cruFeatherfootPowerGain'){FaBCRUResolveFeatherfoot($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='iarWindHeroHit'){FaBIARResolveWindHit($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='cruDataDollItem'){FaBCRUResolveDataDollItem($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='evrShimmersAttackCounter'){FaBEVRResolveShimmersAttackCounter($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='seaOystenDeathGold'){FaBSEAResolveOystenDeathGold($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='evrEarthloreActionDraw'){FaBEVRResolveEarthloreActionDraw($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='iarChorusHitDraw'){FaBIARResolveChorusHitDraw($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='seaEscalateActionDraw'){FaBSEAResolveEscalateActionDraw($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='dtdRadiantEmptySoul'){FaBDTDResolveRadiantEmptySoul($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='mstSingleEntry'){FaBMSTResolveSingleEntry($p,$o);return;}
 // The first-damage trigger earns its draw independently of later source departure.
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='rosValleyFirstOpponentDamage'){
  if(FaBSeatIsLive($p)&&($o->Kind??'')==='ABILITY'&&intval($o->Controller??0)===$p
   &&(string)($o->CardID??'')==='channel_lightning_valley_yellow'&&intval($o->SourceUniqueID??0)>0){
   $previous=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$p;
   try{DoDrawCard($p,1);}finally{$GLOBALS['fabEffectController']=$previous;}
  }
  return;
 }
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='rosHarvestBeginningAction'){FaBROSResolveHarvestBeginningAction($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='dynTomeBeginningAction'){FaBROSResolveBeginningActionScalar($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='dynReincarnateRandom'){FaBDYNResolveReincarnateRandom($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='dynKimonoDestroyed'){FaBDYNResolveKimonoDestroyed($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='rosFlutterDestroyed'){FaBROSResolveFlutterDestroyed($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='seaDiscardedToken'){FaBSEAResolveDiscardedToken($p,$o);return;}
 if(is_array($o->Params??null)&&($o->Params['rosEvent']??'')==='evoPhantomDestroyed'){FaBEVOResolvePhantomDestroyed($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='omnEbbingFragmented'){
  $uid=intval($o->SourceUniqueID??0);$source=FaBFindUID($uid);$s=FaBGetState();
  $link=intval($o->Params['omnEbbingLink']??0);$turn=intval($o->Params['omnEbbingTurn']??0);$play=intval($o->Params['omnEbbingPlayOrder']??0);
  if(!in_array($o->CardID,['ebbing_arcstride_red','ebbing_arcstride_yellow','ebbing_arcstride_blue'],true)
     ||intval($o->Controller??0)!==$p||!FaBSeatIsLive($p)||$uid<1||$link<1||$play<1||$turn!==intval(GetTurnNumber())
     ||!$source||$source['zone']!=='CombatChain'||($source['object']->Role??'')!=='ATTACK'
     ||$source['object']->CardID!==$o->CardID||intval($source['object']->ChainLink??0)!==$link
     ||intval($s['attackUID']??0)!==$uid||intval($s['chainLink']??0)!==$link
     ||intval(FaBObjectCounters($source['object'])['FAB_PLAY_ORDER']??0)!==$play)return;
  // The earned child is independent of later blanking or control changes.
  FaBWTRTag($source['object'],'GO_AGAIN');return;
 }
 if(($o->Params['rosEvent']??'')==='hntSmokeDefendedMark'&&$o->CardID==='smoke_out_red'){FaBHNTResolveSmokeDefended($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='hntKissHeroHit'&&$o->CardID==='kiss_of_death_red'){FaBHNTResolveKissHeroHit($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='eleNextAAHitDamage'){FaBELEResolveNextAAHitDamage($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='dynBIOSItem'&&$o->CardID==='bios_update_red'){FaBDYNResolveBIOSItem($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='rosMillenniumEntryAmp'&&$o->CardID==='channel_the_millennium_tree_red'){FaBMSTAdd($p,'AMP',3);return;}
 if(($o->Params['rosEvent']??'')==='seaSurfaceEntrySeismics'&&$o->CardID==='surface_shaking_blue'){FaBHVYToken($p,'seismic_surge',3,$p,true);return;}
 if(($o->Params['rosEvent']??'')==='dtdFigmentEruditionEntryPonder'&&$o->CardID==='figment_of_erudition_yellow'){FaBHVYToken($p,'ponder',1,$p,false);return;}
 if(($o->Params['rosEvent']??'')==='supPleiadesCheered'&&in_array($o->CardID,['pleiades','pleiades_superstar'],true)){FaBSEAHeroCreate('FaBHVYToken',$p,'confidence',1,$p,false);return;}
 if(($o->Params['rosEvent']??'')==='mstGrantedAttackDraw'){DoDrawCard($p,1);return;}
 if(($o->Params['rosEvent']??'')==='dtdMirage'){FaBDTDResolveMirage($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='monIlluminateHit'&&in_array($o->CardID,['illuminate_red','illuminate_yellow','illuminate_blue'],true)){FaBMONResolveIlluminateHit($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='monInvigoratingLightPlay'){FaBMONResolveInvigoratingPlay($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='evrSmashingNextAAHit'&&in_array($o->CardID,['smashing_good_time_red','smashing_good_time_yellow','smashing_good_time_blue'],true)){FaBEVRResolveSmashingHit($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='rosPreventionToken'){FaBROSResolvePreventedToken($p,$o);return;}
 if($o->CardID==='cintari_saber'&&($o->Params['rosEvent']??'')==='cruCintariDefended'){FaBCRUResolveCintariDefended($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='eleBriarFirstAADamage'&&in_array($o->CardID,['briar','briar_warden_of_thorns'],true)){FaBWTRCreateArena($p,'embodiment_of_earth');return;}
 if(($o->Params['rosEvent']??'')==='cruMauvrionRuneHit'){FaBARCCreateRunes($p,max(0,intval($o->Params['runeCount']??0)));return;}
 if(($o->Params['rosEvent']??'')==='evrPhantasmalHazeDestroyed'&&FaBWTRBase($o->CardID)==='phantasmal_haze'){FaBWTRCreateArena($p,'spectral_shield');return;}
 if(($o->Params['rosEvent']??'')==='wtrIntimidateReturn'){FaBResolveIntimidateReturn($p,$o);return;}
 if($o->CardID==='embodiment_of_lightning'&&($o->Params['rosEvent']??'')==='eleEmbodimentLightning'){FaBELEResolveEmbodiment($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='wtrAttackIntimidate'&&in_array(FaBWTRBase($o->CardID),['smash_instinct','pack_hunt','alpha_rampage'],true)){FaBWTRResolveAttackIntimidate($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='seaRegainComposure'){FaBSEAResolveRegainComposure($p);return;}
 if(($o->Params['rosEvent']??'')==='arcOverLoopHit'&&in_array($o->CardID,['over_loop_red','over_loop_yellow','over_loop_blue'],true)){FaBARCResolveOverLoopHit($p,$o);return;}
 if(($o->Params['rosEvent']??'')==='arcHeadShot'&&FaBWTRBase($o->CardID)==='head_shot'){FaBARCResolveHeadShot($p,$o);return;}
 if($o->CardID==='talisman_of_warfare_yellow'&&($o->Params['rosEvent']??'')==='evrWarfare'){FaBEVRResolveWarfare($p,$o);return;}
 $source=FaBFindUID(FaBROSAbilityMacroSourceUID($o));FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>$source['mzID']??'','rosEvent'=>$o->Params['rosEvent']??'','iarAttack'=>intval($o->Params['iarAttack']??0),'omnVictim'=>intval($o->Params['omnVictim']??0),'rosTarget'=>intval($o->Params['rosTarget']??0),'rosSource'=>intval($o->SourceUniqueID)]);
}
function FaBROSLife(int $p,int $n): void {FaBROSAdd($p,'LIFE',$n);if(intval(GetTurnPlayer())===$p&&FaBROSActive($p,'verdance')){foreach(FaBChoiceRefs($p,'Hero') as $r){$o=FaBIdentityFromMZ($r)['object'];FaBROSQueue($p,$o->CardID,intval($o->UniqueID));}}}
function FaBROSDamaged(int $p,int $v,int $n,string $type,bool $hero=true): void {if($n<=0)return;FaBROSAdd($p,'DAMAGE',$n);if($type==='ARCANE'){FaBROSAdd($p,'ARCANE',$n);if($hero)FaBROSAdd($v,'ARCANE_TAKEN',$n);if($hero&&$p!==$v)FaBROSAdd($p,'ARCANE_OPP',$n);}
 foreach(array_unique($hero?[$p,$v]:[$p]) as $s)foreach(FaBMONArena($s,'arcane_cussing') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
 if($hero&&$p!==$v&&!FaBROSCount($p,'VALLEY_HIT')){FaBROSAdd($p,'VALLEY_HIT');foreach(FaBMONArena($p,'channel_lightning_valley') as $r){$a=FaBIdentityFromMZ($r);if($a!==null)FaBROSQueue($p,(string)$a['object']->CardID,intval($a['object']->UniqueID),['rosEvent'=>'rosValleyFirstOpponentDamage']);}}
 if($type==='ARCANE'&&FaBROSCount($p,'STAFF_EARTH')){FaBROSClear($p,'STAFF_EARTH');FaBWTRCreateArena($p,'embodiment_of_earth');}
}
function FaBROSAfterMove(int $p,object $o,string $from,string $to): void {
 FaBELEEmboldenAfterMove($p,$o,$from,$to);
 FaBDYNReincarnateAfterMove($o,$from,$to);
 FaBROSHarvestAfterMove($o,$from,$to);
 $uid=intval($o->UniqueID);$b=FaBWTRBase($o->CardID);if(in_array($to,['Hand','Deck','Pitch','Banish'],true)&&FaBROSMeld($o->CardID))FaBARCSetCard($uid,'rosMeld',-1);
 if($to==='Banish'&&FaBHasType($o,'Earth'))FaBROSAdd($p,'EARTH_BANISHED');
 if($to==='Arena'&&$from!=='Arena'){
  if($uid>0&&$o->CardID==='figment_of_erudition_yellow'&&!HasNoAbilities($o)){
   $controller=intval($o->Controller??$p)?:$p;
   FaBROSQueue($controller,$o->CardID,$uid,['rosEvent'=>'dtdFigmentEruditionEntryPonder']);
  }
  if($uid>0&&$o->CardID==='surface_shaking_blue'&&!HasNoAbilities($o)){
   $controller=intval($o->Controller??$p)?:$p;
   FaBROSQueue($controller,$o->CardID,$uid,['rosEvent'=>'seaSurfaceEntrySeismics']);
  }
  if($uid>0&&$o->CardID==='channel_the_millennium_tree_red'&&!HasNoAbilities($o)){
   $controller=intval($o->Controller??$p)?:$p;
   FaBROSQueue($controller,$o->CardID,$uid,['rosEvent'=>'rosMillenniumEntryAmp']);
  }
 if(FaBHasType($o,'Aura'))FaBROSAdd($p,'AURA');if($b==='malefic_incantation')FaBSetObjectCounter($o,'VERSE',4-intval(CardPitch($o->CardID)));}
 if($from==='Arena'&&$to!=='Arena'){$p=intval($o->Controller??$p)?:$p;
  if(str_starts_with($b,'sigil_')&&str_contains(CardName($o->CardID),'Sigil')){if(FaBROSCount($p,'BINDINGS'))FaBMSTAdd($p,'AMP',FaBROSCount($p,'BINDINGS'));if(in_array($b,['sigil_of_aether','sigil_of_brilliance','sigil_of_conductivity','sigil_of_cycles','sigil_of_deadwood','sigil_of_earth','sigil_of_forethought','sigil_of_fyendal','sigil_of_lightning','sigil_of_sanctuary','sigil_of_temporal_manipulation','sigil_of_the_arknight'],true))FaBROSQueue($p,$o->CardID,$uid);}
  if($b==='arcane_cussing'&&$p===intval(GetTurnPlayer()))FaBROSQueue($p,$o->CardID,$uid);
 }
}
/** True destruction only: MONDestroy supplies pre-move LKI and authoritative origin. */
function FaBROSDestroyed(int $p,object $o,string $originZone): void {
 if(($o->CardID??'')!=='fluttersteps'||HasNoAbilities($o))return;
 $origin=$originZone;
 if($origin==='CombatChain'){
  $origin=in_array((string)($o->FromZone??''),['Equipment','Weapons'],true)&&in_array((string)($o->Role??''),['DEFENSE','DEFENSE_REACTION'],true)?'Equipment':'';
 }
 if(!in_array($origin,['Arena','Equipment','Weapons','Hero'],true))return;
 $uid=intval($o->UniqueID??0);$controller=intval($o->Controller??0);
 if($uid<1||!FaBSeatIsLive($controller))return;
 FaBROSQueue($controller,'fluttersteps',$uid,['rosEvent'=>'rosFlutterDestroyed']);
}

/** The earned permission is independent of the destroyed physical source. */
function FaBROSResolveFlutterDestroyed(int $p,object $o): void {
 if(!FaBSeatIsLive($p)||($o->CardID??'')!=='fluttersteps'||($o->Kind??'')!=='ABILITY'
  ||intval($o->Controller??0)!==$p||intval($o->SourceUniqueID??0)<1
  ||!is_array($o->Params??null)||($o->Params['rosTrigger']??false)!==true
  ||($o->Params['rosEvent']??'')!=='rosFlutterDestroyed')return;
 FaBMSTAdd($p,'AURA_INSTANT');
}

function FaBROSPrevent(int $p,int $n,string $type,int $source): int {
 $left=[];$earned=[];
 foreach(FaBWTREffects($p) as $e){
  $k=$e['type']??'';
  if($k==='ROS_SHELTER'&&$type==='ARCANE'||$k==='ROS_PREVENT'||$k==='ROS_SOURCE'&&intval($e['source']??0)===$source){
   $v=min($n,intval($e['amount']));$n-=$v;$e['amount']-=$v;
   if($v>0&&!empty($e['token'])){
    $origin=$e['preventionTrigger']??null;
    if(!FaBROSPreventionTokenPayload($p,$origin)||$origin['token']!==$e['token'])throw new RuntimeException('Prevention token grant has no captured source.');
    $earned[]=$origin;
    // Consume this grant's first-prevention marker before any queue callback.
    unset($e['token'],$e['preventionTrigger']);
   }
   if($e['amount']<=0)continue;
  }
  if($k==='ROS_BRUSH'&&$n<=intval($e['amount'])){$n=0;continue;}
  $left[]=$e;
 }
 FaBWTRSetEffects($p,$left);
 foreach($earned as $origin)FaBROSQueue($origin['controller'],$origin['cardID'],$origin['sourceUID'],
  ['rosEvent'=>'rosPreventionToken','preventionTrigger'=>$origin]);
 return $n;
}

/** Use the live native ABI's captured identity, never a token-to-source lookup. */
function FaBROSPreventionTokenOrigin(int $p,string $token): array {
 $pending=FaBGetState()['resolvingReaction']??null;
 $layer=is_array($pending)?FaBFindUID(intval($pending['uid']??0)):null;
 if(!FaBSeatIsLive($p)||!is_array($pending)||($pending['resolutionClass']??'')!=='ARC_ABILITY'
  ||($pending['kind']??'')!=='ABILITY'||intval($pending['controller']??0)!==$p
  ||$layer===null||$layer['zone']!=='Stack'||!FaBManagedAbilityResolutionOwns($layer['object']))throw new RuntimeException('Prevention token grant requires its resolving native ability.');
 $o=$layer['object'];$uid=intval($pending['sourceUniqueID']??0);$card=(string)($pending['cardID']??'');
 if($uid<1||$card===''||intval($o->Controller??0)!==$p||intval($o->SourceUniqueID??0)!==$uid
  ||(string)$o->CardID!==$card||($o->Params['arcSpec']['cardID']??'')!==$card||!FaBHasType($token,'Token'))throw new RuntimeException('Prevention token grant origin changed.');
 return ['controller'=>$p,'owner'=>$p,'cardID'=>$card,'sourceUID'=>$uid,'grantUID'=>intval($o->UniqueID),
  'token'=>$token,'actionCardEffect'=>FaBHasType($card,'Action')];
}

function FaBROSPreventionTokenPayload(int $p,$origin): bool {
 return is_array($origin)&&intval($origin['controller']??0)===$p&&intval($origin['owner']??0)===$p
  &&is_string($origin['cardID']??null)&&$origin['cardID']!==''&&intval($origin['sourceUID']??0)>0
  &&intval($origin['grantUID']??0)>0&&is_string($origin['token']??null)&&FaBHasType($origin['token'],'Token')
  &&is_bool($origin['actionCardEffect']??null);
}

/** The earned result survives the original physical source leaving or losing abilities. */
function FaBROSResolvePreventedToken(int $p,object $o): void {
 $origin=$o->Params['preventionTrigger']??null;
 if(!FaBSeatIsLive($p)||!FaBROSPreventionTokenPayload($p,$origin)
  ||intval($o->Controller??0)!==$p||(string)$o->CardID!==$origin['cardID']
  ||intval($o->SourceUniqueID??0)!==$origin['sourceUID'])return;
 // Same token factory/replacements as CreateArena, with captured creator/owner
 // and source classification rather than another spell's mutable DQ context.
 FaBHVYToken($p,$origin['token'],1,$p,$origin['actionCardEffect'],false,$origin['owner']);
}
function FaBROSShelters(int $p): array {return array_values(array_filter(FaBChoiceRefs($p,'Arena'),fn($r)=>FaBHasKeyword(FaBIdentityFromMZ($r)['object'],'Arcane Shelter')));}
function FaBROSArcanite(int $p): int {return count(array_filter(FaBChoiceRefs($p,'Equipment'),fn($r)=>str_contains(CardName(FaBIdentityFromMZ($r)['object']->CardID),'Arcanite')));}
function FaBROSMeld(string $id): bool {return str_contains((string)CardFunctional_text_plain($id),'Meld');}
function FaBROSMeldTypes(object $o,array $types): array {$mode=FaBARCCard(intval($o->UniqueID??0),'rosMeld',-1);if(!FaBROSMeld($o->CardID)||$mode<0||$mode===2)return $types;$parts=explode(' // ',(string)CardType_text($o->CardID));return preg_split('/[ -]+/',trim($parts[$mode]??''),-1,PREG_SPLIT_NO_EMPTY);}
function FaBROSMeldOptions(int $p,int $uid): string {$f=FaBFindUID($uid);if(!$f)return ''; $s=FaBGetState();$base=intval(CardCost($f['object']->CardID));$tax=max(0,FaBCardCost($f['object'],$p)-$base);$action=str_contains(explode(' // ',CardType_text($f['object']->CardID))[0],'Action');$window=$s['pendingPayment']['returnWindow']??$s['window'];$canAction=!$action||($p===intval(GetTurnPlayer())&&$window==='ACTION'&&intval(GetActionPoints($p))>0&&count(array_filter(GetStack(),fn($o)=>is_object($o)&&empty($o->removed)&&intval($o->UniqueID)!==$uid))===0);$m=[];if($canAction)$m[]='Left_half';$m[]='Right_half';if($canAction&&FaBAvailablePitch($p,$uid)>=$base*2+$tax)$m[]='Meld_both';FaBARCSetCard($uid,'rosMeldOptions',$m);return implode('&',$m);}
function FaBROSMeldChoose(int $p,int $uid,string $choice): void {$f=FaBFindUID($uid);if(!$f)return;$options=(array)FaBARCCard($uid,'rosMeldOptions',[]);$label=$options[intval($choice)]??'Right_half';$mode=['Left_half'=>0,'Right_half'=>1,'Meld_both'=>2][$label];FaBARCSetCard($uid,'rosMeld',$mode);FaBARCSetCard($uid,'rosSide',$mode===0?0:1);$s=FaBGetState();$f['object']->Kind=$mode!==1&&str_contains(explode(' // ',CardType_text($f['object']->CardID))[0],'Action')?'ACTION':'INSTANT';$s['pendingPayment']['kind']=$f['object']->Kind;$s['pendingPayment']['cost']=FaBCardCost($f['object'],$p)+($mode===2?intval(CardCost($f['object']->CardID)):0);FaBSetState($s);}
function FaBROSMeldFirst(int $p,object $o): bool {$uid=intval($o->UniqueID);if(!FaBROSMeld($o->CardID)||FaBARCCard($uid,'rosMeld',-1)!==2||FaBARCCard($uid,'rosSide')!==1)return false;FaBRunSourceMacro('ResolveCard',$p,$o->CardID,['mzID'=>FaBFindUID($uid)['mzID']]);return true;}
function FaBROSMeldResume(int $uid): void {if(FaBARCCard($uid,'rosMeld',-1)===2&&FaBARCCard($uid,'rosSide')===1){FaBARCSetCard($uid,'rosSide',0);SetPriorityPlayer(intval(GetTurnPlayer()));SetConsecutivePasses(0);$s=FaBGetState();$s['window']='PRIORITY';FaBSetState($s);}}
function FaBROSResurge(int $p,int $uid,string $dest): void {$f=FaBFindUID($uid);if(!$f)return;$o=FaBMoveUID($uid,$dest,intval($f['object']->Owner??$p)?:$p);if($o&&$dest==='Banish')$o->PlayableFromBanish=1;}
function FaBROSAnyOpposing(int $p): string {return implode('&',array_filter(explode('&',FaBUPRAnyTargets($p)),fn($r)=>intval(FaBIdentityFromMZ($r)['player']??0)!==$p));}
function FaBROSInstantStack(int $exclude=0): string {$r=[];foreach(GetStack() as $i=>$o)if(is_object($o)&&empty($o->removed)&&intval($o->UniqueID)!==$exclude&&$o->Kind!=='ABILITY'&&FaBHasType($o,'Instant'))$r[]='Stack-'.$i;return implode('&',$r);}
function FaBROSNull(int $p,int $uid): void {$f=FaBFindUID($uid);if(!$f||$f['zone']!=='Stack'||!FaBHasType($f['object'],'Instant')||intval(CardCost($f['object']->CardID))>=FaBROSCount($p,'ARCANE_OPP'))return;FaBMoveStackUID($uid,'Graveyard',intval($f['object']->Owner??$f['player'])?:$f['player']);}
function FaBROSRegrowth(int $p): string {return implode('&',FaBChoiceRefs($p,'Graveyard',['attackAction'=>true,'maxCost'=>FaBROSCount($p,'ARCANE_OPP')-1]));}
function FaBROSVaporize(int $p,bool $tokens): string {return implode('&',array_filter(array_filter(explode('&',FaBROSAllAuras($p,true,999,true))),function($r)use($p,$tokens){$o=FaBIdentityFromMZ($r)['object'];return FaBHasType($o,'Token')===$tokens&&($tokens||intval(CardCost($o->CardID))<=FaBROSCount($p,'ARCANE_OPP'));}));}
function FaBROSChainAttacks(int $minCost): string {$s=FaBGetState();$r=[];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain',['attackAction'=>true]) as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&intval(CardCost($o->CardID))>=$minCost)$r[]=$ref;}return implode('&',$r);}
function FaBROSReturnLater(string $refs): void {foreach(FaBUPRUIDs($refs) as $uid)FaBARCSetCard($uid,'rosReturn',true);}
function FaBROSFaceUp(string $r): void {$f=FaBIdentityFromMZ($r);if($f&&$f['zone']==='Arsenal')$f['object']->FaceDown=0;}
function FaBROSArsenal(int $p,string $type): string {return FaBROSRefs($p,'Arsenal',$type);}
function FaBROSIntimidated(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Banish'),fn($r)=>!empty(FaBIdentityFromMZ($r)['object']->FaceDown)&&!empty(FaBIdentityFromMZ($r)['object']->ReturnAtEndTurn)));}
function FaBROSTenFoot(int $p): void {foreach(FaBChoiceRefs($p,'Hero') as $r){$o=FaBIdentityFromMZ($r)['object'];FaBSetObjectCounter($o,'ROS_INTELLECT',intval(FaBObjectCounters($o)['ROS_INTELLECT']??0)-2);}}
function FaBROSIntellect(int $p): int {$n=0;foreach(FaBChoiceRefs($p,'Hero') as $r)$n+=intval(FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['ROS_INTELLECT']??0);return $n;}
function FaBROSFinishTurn(int $p): void {foreach(FaBChoiceRefs($p,'Hero') as $r)FaBSetObjectCounter(FaBIdentityFromMZ($r)['object'],'ROS_INTELLECT',0);}
function FaBROSTemporal(int $p): void {$r=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);if(!$f)return;$uid=intval($f['object']->UniqueID);$o=FaBMoveUID($uid,'Banish',$p);if($o&&FaBHasType($o,'Action')&&!FaBHasType($o,'Attack')){$o->PlayableFromBanish=1;FaBARCSetCard($uid,'rosTemporalTurn',intval(GetTurnNumber()));}}
function FaBROSArknight(int $p): void {$r=FaBChoiceRefs($p,'Deck')[0]??'';if(!$r)return;FaBRevealChoices($p,$r);$f=FaBIdentityFromMZ($r);if($f&&FaBWTRIsAttackAction($f['object']))FaBMoveUID(intval($f['object']->UniqueID),'Hand',$p);}
function FaBROSSetTruce(int $uid,string $r): void {$f=FaBFindUID($uid);$target=FaBIdentityFromMZ($r);if($f&&$target)FaBSetObjectCounter($f['object'],'ROS_OPP',$target['player']);}
function FaBROSAllSources(): string {$r=[];foreach(FaBLiveSeats() as $p)foreach(['Hero','Weapons','Equipment','Arena','CombatChain'] as $z)$r=array_merge($r,FaBChoiceRefs($p,$z));foreach(GetStack() as $i=>$o)if(is_object($o)&&empty($o->removed))$r[]='Stack-'.$i;return implode('&',$r);}
function FaBROSMarkSanctuary(int $uid): void {$f=FaBFindUID($uid);if($f)FaBSetObjectCounter($f['object'],'ROS_SANCTUARY',1);}
function FaBROSSlots(int $p,int $uid): string {$f=FaBFindUID($uid);$current=$f?FaBObjectCounters($f['object'])['EVO_SLOT']??'':'';$slots=[];foreach(['Head','Chest','Arms','Legs'] as $s)if($s!==$current&&!FaBChoiceRefs($p,'Equipment',['type'=>$s]))$slots[]=$s;return implode('&',$slots);}
function FaBROSSlot(int $p,int $uid,int $n): void {$slots=explode('&',FaBROSSlots($p,$uid));$f=FaBFindUID($uid);if($f&&!empty($slots[$n]))FaBEVOCounter($f['object'],'EVO_SLOT',$slots[$n]);}
function FaBROSPlan(int $p,int $v): string {if(!FaBSeatIsLive($v))return '';$uids=FaBDYNPrivateHand($p,$v);foreach(FaBChoiceRefs($v,'Arsenal') as $r){$o=FaBIdentityFromMZ($r)['object'];$temp=AddTemp($p,CardID:$o->CardID);$uids[]=intval($temp->UniqueID);}foreach(FaBChoiceRefs($v,'Hero') as $r)FaBSetObjectCounter(FaBIdentityFromMZ($r)['object'],'ROS_PLAN',1);return FaBEVRUIDRefs($uids);}
function FaBROSGrantGo(int $p,int $uid): void {if(!FaBARCCard($uid,'rosSurgeGo')&&FaBWTRMayGoAgain($p)){AddActionPoints($p,intval(GetActionPoints($p))+1);FaBROSGo($p,$uid);}FaBARCSetCard($uid,'rosSurgeGo',true);}
function FaBROSFinishBlocks(): void {$s=FaBGetState();foreach(FaBLiveSeats() as $p){$aa=false;$na=false;$face=null;foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)!==intval($s['chainLink'])||!in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))continue;if($o->CardID==='face_purgatory')$face=$o;if(FaBWTRIsAttackAction($o))$aa=true;elseif(FaBHasType($o,'Action'))$na=true;}if($face&&$aa&&$na&&!FaBARCCard(intval($face->UniqueID),'rosFace'.$s['chainLink'])){FaBARCSetCard(intval($face->UniqueID),'rosFace'.$s['chainLink'],true);FaBROSQueue($p,'face_purgatory',intval($face->UniqueID),['rosTarget'=>intval($s['attacker'])]);}}}
function FaBROSChorus(int $p,int $uid,string $sourceRef=''): int {$f=FaBDamageSourceView($sourceRef,$uid);return $f&&(FaBDamageSourceHasType($f['object'],'Action')||FaBDamageSourceHasType($f['object'],'Instant'))&&!FaBDamageSourceHasType($f['object'],'Token')?FaBROSCount($p,'CHORUS'):0;}
function FaBROSStoredSource(int $p,string $key): int {foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='ROS_'.$key)return intval($e['source']??0);return 0;}
function FaBROSShelterFallback(int $p,int $n,bool $unpreventable): int {foreach(FaBROSShelters($p) as $r){if($n<=0)break;$f=FaBIdentityFromMZ($r);if(!$f)continue;FaBMONDestroy(intval($f['object']->UniqueID));if(!$unpreventable)$n=max(0,$n-1);}return $n;}
