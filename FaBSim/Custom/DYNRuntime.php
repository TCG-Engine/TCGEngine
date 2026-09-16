<?php
function FaBDYNAbilityRows(): array {return [
 'amethyst_tiara'=>[['INSTANT',0,true,false,false,0,'Give Runechants spellvoid']],
 'annals_of_sutcliffe'=>[['ACTION',3,false,false,true,0,'Draw a card']],
 'blacktek_whisperers'=>[['REACTION',0,true,false,false,0,'Gain go again on hit']],
 'mask_of_perdition'=>[['REACTION',0,true,false,false,0,'Banish on hit']],
 'gold'=>[['ACTION',2,true,true,false,0,'Draw a card']],
 'ornate_tessen'=>[['INSTANT',1,true,false,false,0,'Bottom a card and draw']],
 'sandscour_greatbow'=>[['ACTION',1,false,true,true,0,'Look and load an arrow']],
 'seerstone'=>[['ACTION',3,false,false,false,0,'Look at top card and ponder']],
 'surgent_aethertide'=>[['ACTION',2,false,true,true,0,'Deal arcane damage']],
 'imperial_edict_red'=>[['ACTION',0,true,true,false,0,'Prohibit a card']],
 'imperial_ledger_red'=>[['ACTION',0,false,false,false,0,'Shuffle ledger for treasure']],
 'imperial_warhorn_red'=>[['ACTION',1,true,false,false,0,'Destroy chosen permanents']],
 'yoji_royal_protector'=>[['INSTANT',3,false,false,true,0,'Protect another hero']],
 'emperor_dracai_of_aesir'=>[['ACTION',3,false,false,false,0,'Attack with Command and Conquer']],
];}
function FaBDYNAbilityLegal(int $p,array $f,array $spec): bool {
 $b=FaBWTRBase($f['object']->CardID);if(in_array($b,['blacktek_whisperers','mask_of_perdition'],true)&&FaBDYNAttacks($p,'ASSASSIN')==='')return false;
 if($b==='ornate_tessen'&&FaBHandCount($p)===0)return false;
 if($b==='imperial_ledger'&&$f['zone']!=='Arena')return false;
 if($spec['timing']==='ACTION'&&FaBDYNCount($p,'IMMOBILE')&&count(FaBARCPlayed($p,true))>=1)return false;
 return true;
}
function FaBDYNWeaponCost(string $id): ?int {return ['hunters_klaive'=>2,'mark_of_the_huntsman'=>2,'graphene_chelicera'=>1,'kunai_of_retribution'=>1,'obsidian_fire_vein'=>1,'star_fall'=>1,'rotwood_reaper'=>2,'beckoning_mistblade'=>2,'tiger_taming_khakkara'=>2,'ball_breaker'=>2,'high_riser'=>3,'hot_streak'=>1,'millers_grindstone'=>3,'mini_meataxe'=>2,'parry_blade'=>1,'graven_call'=>2,'bank_breaker'=>1,'banksy'=>1,'symbiosis_shot'=>0,'teklo_leveler'=>3,'teklovossen_the_mechropotent'=>3,'beaming_blade'=>2,'hell_hammer'=>2,'flail_of_agony'=>0,'decimator_great_axe'=>3,'rugged_roller'=>1,'jubeel_spellbane'=>1,'merciless_battleaxe'=>3,'quicksilver_dagger'=>1,'spiders_bite'=>2,'nerve_scalpel'=>2,'orbitoclast'=>2,'scale_peeler'=>2,'rok'=>3,'hanabi_blaster'=>0,'nitro_mechanoid'=>0][$id]??null;}
function FaBDYNWeaponCanAttack(int $p,array $f): bool {
 $o=$f['object'];$cost=FaBDYNWeaponCost($o->CardID);$s=FaBGetState();if($cost===null||$f['player']!==$p||!in_array($f['zone'],['Weapons','Equipment','Hero'],true)||HasNoAbilities($o)||FaBUPRFrozen($o)||FaBUPRLocked($p))return false;
 if(!FaBHNTWeaponLegal($p)||!FaBEVOWeaponLegal($p,$o)||!FaBDTDRestrictions($p,$o,true,true))return false;
 if($o->CardID==='rugged_roller'&&!FaBDTDCount($p,'ROLL_SIX'))return false;
 if($o->CardID==='flail_of_agony'&&intval(GetHealth($p))<1)return false;
 if($o->CardID==='rok'&&FaBHandCount($p)>0)return false;
 if($o->CardID==='hanabi_blaster'&&intval(FaBObjectCounters($o)['STEAM']??0)<2)return false;
 if($o->CardID==='nitro_mechanoid'&&!count(FaBObjectCounters($o)['DYN_MATERIAL']??[]))return false;
 return $p===intval(GetTurnPlayer())&&(in_array($o->CardID,['nitro_mechanoid','symbiosis_shot','teklovossen_the_mechropotent'],true)||FaBCRUWeaponReady($o))&&intval(GetActionPoints($p))>0&&in_array($s['window'],['ACTION','RESOLUTION'],true)&&FaBAvailablePitch($p)>=FaBWTRAbilityCost($p,['timing'=>'ACTION','cost'=>$cost,'cardID'=>$o->CardID,'sourceUID'=>intval($o->UniqueID)]);
}
function FaBDYNWeaponAttack(int $p,array $f): bool {
 if(!FaBDYNWeaponCanAttack($p,$f))return false;$o=$f['object'];$uid=intval($o->UniqueID);$target=FaBClaimOrRequestAttackTarget($p,$uid,'ACTIVATE');if($target===null)return true;if($target===false)return false;
 $s=FaBGetState();$stack=AddStack(CardID:$o->CardID,Controller:$p,Kind:'ATTACK',SourceZone:$f['zone'],SourceUniqueID:$uid,Params:['attackTarget'=>$target]);
 $cost=FaBWTRAbilityCost($p,['timing'=>'ACTION','cost'=>FaBDYNWeaponCost($o->CardID),'cardID'=>$o->CardID,'sourceUID'=>intval($o->UniqueID),'attackTarget'=>$target]);
 if(in_array($o->CardID,['nitro_mechanoid','teklovossen_the_mechropotent'],true)){FaBSetObjectCounter($stack,'MON_ARENA_ATTACK',1);FaBSetObjectCounter($stack,'MON_SOURCE_UID',$uid);}
 $s['pendingPayment']=['player'=>$p,'uid'=>intval($stack->UniqueID),'weaponUID'=>$uid,'cost'=>$cost,'fromZone'=>$f['zone'],'kind'=>'ATTACK','isWeaponAttack'=>true,'returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep']];$s['window']='PITCH';FaBSetState($s);SetConsecutivePasses(0);
 if(in_array($o->CardID,['nitro_mechanoid','teklovossen_the_mechropotent'],true)){FaBRunSourceMacro('PrepareCard',$p,$o->CardID,['mzID'=>FaBFindUID(intval($stack->UniqueID))['mzID']]);return true;}
 return FaBTryCompletePayment();
}
function FaBDYNMaterialOptions(int $stackUID): string {$f=FaBFindUID($stackUID);$source=$f?FaBFindUID(intval($f['object']->SourceUniqueID)):null;return $source?implode('&',array_map(fn($id)=>str_replace(' ','_',CardName($id)),FaBObjectCounters($source['object'])['DYN_MATERIAL']??[])):'';}
function FaBDYNPayMaterial(int $stackUID,int $index): void {$f=FaBFindUID($stackUID);$source=$f?FaBFindUID(intval($f['object']->SourceUniqueID)):null;if(!$source)return;$o=$source['object'];$c=FaBObjectCounters($o);$cards=$c['DYN_MATERIAL']??[];if(!isset($cards[$index]))return;$id=$cards[$index];AddBanish($source['player'],CardID:$id);array_splice($cards,$index,1);$c['DYN_MATERIAL']=$cards;$c['DYN_MATERIAL_POWER']=in_array('galvanic_bender',$cards,true)?1:0;$o->Counters=$c;}
function FaBDYNCanPlay(int $p,array $f): bool {
 $o=$f['object'];$b=FaBWTRBase($o->CardID);foreach(FaBGetState()['dynEdicts']??[] as $e)if(strcasecmp($e['name'],(string)CardName($o->CardID))===0)return false;
 if(FaBDYNCount($p,'IMMOBILE')&&FaBHasType($o,'Action')){$aa=FaBWTRIsAttackAction($o);foreach(FaBARCPlayed($p) as $id)if(FaBHasType($id,'Action')&&FaBHasType($id,'Attack')===$aa)return false;}
 if(in_array($b,['rumble_grunting','savage_beatdown'],true)&&FaBCRUCount($p,'DISCARD_SIX')===0)return false;
 if(in_array($b,['madcap_charger','madcap_muscle','savage_beatdown'],true)&&FaBHandCount($p)<=($f['zone']==='Hand'?1:0))return false;
 if($b==='invoke_suraya'&&!FaBMONArena($p,'spectral_shield'))return false;
 if($b==='construct_nitro_mechanoid'&&count(FaBChoiceRefs($p,'Arena',['base'=>'hyper_driver']))<3)return false;
 if($b==='point_the_tip'&&FaBDYNAimTargets($p)==='')return false;
 if($b==='reinforce_steel'&&FaBDYNEquipment($p,'REPAIR_'.(4-intval(CardPitch($o->CardID))))==='')return false;
 if($b==='withstand'&&FaBDYNEquipment($p,'OFFHAND')==='')return false;
 return FaBDYNBlockLegal($p,$f);
}
function FaBDYNBlockLegal(int $p,array $f): bool {
 $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a||$p!==intval($s['defender']))return true;
 if($a['object']->CardID==='regicide_blue'){foreach(FaBChoiceRefs($p,'Banish') as $r)if(CardName(FaBIdentityFromMZ($r)['object']->CardID)===CardName($f['object']->CardID))return false;}
 if(FaBHasType($f['object'],'Action')&&FaBDYNOverpower($a['object'],$s))foreach(FaBChoiceRefs($p,'CombatChain') as $r){$b=FaBIdentityFromMZ($r)['object'];if(intval($b->ChainLink)===intval($s['chainLink'])&&in_array($b->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBHasType($b,'Action'))return false;}
 return true;
}
function FaBDYNOverpower(object $o,array $s): bool {return FaBHVYOverpower(intval($s['attacker']),$o,$s)||FaBEVOOverpower(intval($s['attacker']),$o,$s)||FaBHasKeyword($o,'Overpower')||in_array('OVERPOWER',(array)$o->TurnEffects,true)||(FaBWTRBase($o->CardID)==='wall_breaker'&&FaBMONCount(intval($s['attacker']),'BANISHED_SIX'))||in_array('DYN_OVERPOWER',(array)$o->TurnEffects,true)||($o->CardID==='merciless_battleaxe'&&FaBAttackPower($s)>2*intval(CardPower($o->CardID)));}
function FaBDYNPlayed(int $p,object $o,string $from): void {
 $b=FaBWTRBase($o->CardID);$uid=intval($o->UniqueID);$attack=FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o);
 if($attack){$left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='DYN_NEXT'&&FaBDYNMatch($o,$e['kind'])){if(intval($e['amount']))FaBTagUID($uid,'WTR_POWER:'.intval($e['amount']));foreach($e['tags'] as $tag)if($tag!=='DYN_DEAD_EYE'||FaBDYNAimed($uid))FaBTagUID($uid,$tag);}else $left[]=$e;}FaBWTRSetEffects($p,$left);}
 if($o->CardID==='crouching_tiger'&&FaBDYNCount($p,'ROAR'))FaBTagUID($uid,'WTR_POWER:'.FaBDYNCount($p,'ROAR'));
 if($b==='spectral_prowler'&&FaBMONArena($p,'spectral_shield'))FaBTagUID($uid,'GO_AGAIN');
 if($b==='spectral_rider'&&FaBMONArena($p,'spectral_shield'))FaBTagUID($uid,'DYN_OVERPOWER');
 if(FaBHasKeyword($o,'Boost')){FaBDYNAdd($p,'BOOST_PLAYED');if(FaBDYNCount($p,'BOOST_PLAYED')===3)foreach(FaBChoiceRefs($p,'Weapons',['base'=>'hanabi_blaster']) as $r)FaBARCSteam(FaBIdentityFromMZ($r)['object'],1);}
 if(FaBDYNContract($o->CardID)!==''&&(FaBOUTHero($p,'arakni_huntsman')||((GetHero($p)[0]->CardID??'')==='arakni'&&FaBWTRHeroActive($p))))FaBRunSourceMacro('ResolveAbility',$p,'arakni',['mzID'=>FaBFindUID(FaBUPRHeroUID($p))['mzID']]);
 if($from!=='Weapons'){
  $bonus=FaBDYNCount($p,'BLESS_AETHER');FaBDYNClear($p,'BLESS_AETHER');
  if(str_contains((string)CardFunctional_text_plain($o->CardID),'arcane damage')){
   if(!FaBDYNCount($p,'SURGENT_USED')){$bonus+=FaBDYNCount($p,'SURGENT_DAMAGE');FaBDYNAdd($p,'SURGENT_USED');}
   foreach([0,1,2] as $cost)if(intval(CardCost($o->CardID))<=$cost){$bonus+=FaBDYNCount($p,'TEMPEST_'.$cost);FaBDYNClear($p,'TEMPEST_'.$cost);}
   if($bonus)FaBTagUID($uid,'DYN_ARCANE:'.$bonus);
  }
 }
}
function FaBDYNPower(int $p,object $o): int {
 $n=FaBArakniStringsApplies($p,$o)&&FaBDYNRoyal(intval(FaBGetState()['defender']))?1:0;$b=FaBWTRBase($o->CardID);if($b==='spectral_procession')$n+=count(FaBMONArena($p,'spectral_shield'))-intval(CardPower($o->CardID));
 if($b==='long_shot'&&FaBDYNAimed(intval($o->UniqueID)))$n+=2;
 $piercing=0;foreach((array)$o->TurnEffects as $t)if(str_starts_with($t,'DYN_PIERCING:'))$piercing+=intval(substr($t,13));
 if(FaBDYNMatch($o,'BLADE'))$piercing+=FaBDYNCount($p,'FORGE');
 if(in_array($o->CardID,['spiders_bite','graven_call','hunters_klaive'],true)||($b==='drill_shot'&&FaBDYNAimed(intval($o->UniqueID))))++$piercing;
 if($piercing&&FaBDYNDefendingEquipment()!=='')$n+=$piercing;
 if(in_array($o->CardID,['nitro_mechanoid','teklovossen_the_mechropotent'],true)){$f=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));if($f)$n+=intval(FaBObjectCounters($f['object'])['DYN_MATERIAL_POWER']??0);}
 return $n;
}
function FaBDYNDefense(int $p,object $o): int {
 $n=0;$s=FaBGetState();$b=FaBWTRBase($o->CardID);foreach((array)($o->TurnEffects??[]) as $t){if(str_starts_with($t,'DYN_DEFENSE:'))$n+=intval(substr($t,12));if(str_starts_with($t,'DYN_WITHSTAND_ACTIVE:'))$n+=intval(substr($t,21));}
 if($b==='blazen_yoroi'&&intval($o->ChainLink??0)>=4)$n+=4;
 if($b==='shield_wall'&&FaBDYNEquipment($p,'OFFHAND')!=='')$n+=4;
 if(FaBHasType($o,'Equipment'))foreach(FaBWTREffects(intval($s['attacker'])) as $e)if(($e['type']??'')==='DYN_SCRAMBLE')$n-=intval($e['amount']);
 return $n;
}
function FaBDYNDefended(int $p,object $o): void {foreach((array)$o->TurnEffects as $t)if(str_starts_with($t,'DYN_WITHSTAND:')){FaBTagUID(intval($o->UniqueID),'DYN_WITHSTAND_ACTIVE:'.intval(substr($t,14)));$o->TurnEffects=array_values(array_diff($o->TurnEffects,[$t]));}}
function FaBDYNDefendGroup(int $p): void {$s=FaBGetState();$n=FaBDYNCount($p,'SPIDER');if(!$n)return;$hit=false;foreach($s['declaredBlockUIDs'] as $uid){$f=FaBFindUID(intval($uid));if($f&&FaBWTRIsAttackAction($f['object'])){FaBTagUID(intval($uid),'DYN_DEFENSE:'.(-$n));$hit=true;}}if($hit)FaBDYNClear($p,'SPIDER');}
function FaBDYNAttack(int $p,object $o): void {
 FaBArakniAttack($p,$o);
 $b=FaBWTRBase($o->CardID);if($b==='scramble_pulse')FaBDYNAdd($p,'SCRAMBLE');
 if($b==='regicide'){$s=FaBGetState();$s['dynRegicide'][$p]=true;FaBSetState($s);}
 if($b==='rok')FaBTagUID(intval($o->UniqueID),'UPR_UNPREVENTABLE');
 if($b==='quicksilver_dagger')foreach(FaBChoiceRefs($p,'Weapons') as $r){$w=FaBIdentityFromMZ($r)['object'];if(intval($w->UniqueID)!==intval(FaBObjectCounters($o)['WEAPON_UID']??0)&&in_array('GO_AGAIN',(array)$w->TurnEffects,true))FaBTagUID(intval($o->UniqueID),'GO_AGAIN');}
}
function FaBDYNHit(int $p,object $o,int $n): void {
 if($n<=0)return;$uid=intval($o->UniqueID);$r=FaBFindUID($uid)['mzID']??'';
 if(FaBHasType($o,'Gun'))foreach(FaBMONArena($p,'powder_keg') as $k)FaBRunSourceMacro('ResolveAbility',$p,'powder_keg_blue',['mzID'=>$k]);
 if(!FaBFaiHeroHit()){foreach((array)$o->TurnEffects as $tag)if($tag==='DYN_CLEAVE')FaBRunSourceMacro('ResolveAbility',$p,'buckle_blue',['mzID'=>$r,'dynHitMode'=>$tag,'dynHitAmount'=>$n,'dynVictim'=>intval(FaBGetState()['defender'])]);return;}$victim=intval(FaBGetState()['defender']);
 if($o->CardID==='jubeel_spellbane'&&!FaBMONArena($p,'spellbane_aegis'))FaBWTRCreateArena($p,'spellbane_aegis');
 if($o->CardID==='spiders_bite')FaBDYNAdd($victim,'SPIDER');
 if(FaBHasType($o,'Sword'))FaBDYNAdd($p,'SWORD_HIT');
 foreach((array)$o->TurnEffects as $tag){
  if($tag==='DYN_MASK')FaBDYNBanishTop($p,$victim,1);
  if(str_starts_with($tag,'DYN_REAP:'))FaBARCCreateRunes($p,intval(substr($tag,9)));
  if(in_array($tag,['DYN_BUCKLE','DYN_CLEAVE','DYN_DEAD_EYE'],true))FaBRunSourceMacro('ResolveAbility',$p,'buckle_blue',['mzID'=>$r,'dynHitMode'=>$tag,'dynHitAmount'=>$n,'dynVictim'=>$victim]);
 }
}
function FaBDYNBoost(int $p,int $uid,int $banishedUID): void {
 if(FaBDYNCount($p,'BIOS_POWER')){FaBTagUID($uid,'WTR_POWER:'.(3*FaBDYNCount($p,'BIOS_POWER')));FaBDYNClear($p,'BIOS_POWER');}
 $f=FaBFindUID($banishedUID);if(!$f)return;$o=$f['object'];
 if(FaBWTRBase($o->CardID)==='crankshaft')FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>$f['mzID']]);
 if(FaBDYNCount($p,'BIOS_ITEM')&&FaBHasType($o,'Mechanologist')&&FaBHasType($o,'Item')&&intval(CardCost($o->CardID))<=2){FaBDYNClear($p,'BIOS_ITEM');$o=FaBMoveUID($banishedUID,'Arena',$p);if($o)FaBARCEnterItem($p,$o);}
}
function FaBDYNEnterItem(int $p,object $o): void {
 $b=FaBWTRBase($o->CardID);if($b==='hyper_driver')FaBSetObjectCounter($o,'STEAM',intval(FaBObjectCounters($o)['STEAM']??0)+max(1,4-intval(CardPitch($o->CardID))));
 if($b==='plasma_mainline')FaBSetObjectCounter($o,'STEAM',5);
 if(FaBHasType($o,'Mechanologist')&&intval(CardCost($o->CardID))<=2)foreach(FaBMONArena($p,'plasma_mainline') as $r)FaBRunSourceMacro('ResolveAbility',$p,'plasma_mainline_red',['mzID'=>$r,'dynItemUID'=>intval($o->UniqueID)]);
}
function FaBDYNRandomDiscard(int $p,int $uid,string $id): void {
 if($id==='skull_crack_red')AddResources($p,intval(GetResources($p))+1);
 if(FaBWTRBase($id)==='reincarnate'&&!FaBDYNCount($p,'BERSERK'))FaBARCToDeck($p,$uid,false);
 if(intval(CardPower($id))>=6){
  foreach(FaBCRUEquipment($p,'beaten_trackers') as $r)FaBRunSourceMacro('ResolveAbility',$p,'beaten_trackers',['mzID'=>$r]);
  if(FaBDYNCount($p,'BERSERK'))FaBRunSourceMacro('ResolveAbility',$p,'berserk_yellow',['mzID'=>FaBFindUID($uid)['mzID']??'','dynDiscardUID'=>$uid,'dynReincarnate'=>FaBWTRBase($id)==='reincarnate'?1:0]);
 }
}
function FaBDYNWard(int $p,int $amount,bool $unpreventable=false): int {
 while($amount>0){$ward=null;$value=0;foreach(array_merge(FaBChoiceRefs($p,'Arena'),FaBChoiceRefs($p,'Equipment')) as $r){$f=FaBIdentityFromMZ($r);$o=$f['object'];if(FaBWTRBase($o->CardID)==='mini_forcefield'&&!HasNoAbilities($o)){$ward=$o;$value=intval(FaBObjectCounters($o)['STEAM']??0);if($value>0)break;}if(FaBMSTWardActive($o)){$ward=$o;$value=FaBMSTWard($p,$o);break;}}
  if(!$ward){foreach(FaBCRUEquipment($p,'empyrean_rapture') as $r){$o=FaBIdentityFromMZ($r)['object'];if(in_array('DTD_WARD:1',(array)$o->TurnEffects,true)){$ward=$o;$value=1;break;}}}
  if(!$ward)break;if(!$unpreventable)$amount=max(0,$amount-$value);FaBMONDestroy(intval($ward->UniqueID));
 }return $amount;
}
function FaBDYNDestroyed(int $p,object $o): void {
 if($o->CardID==='wave_of_reality')FaBWTRCreateArena($p,'spectral_shield');
 if($o->CardID==='celestial_kimono'||(!FaBHasType($o,'Token')&&preg_match('/\bWard \d+/i',(string)CardFunctional_text_plain($o->CardID)))){
  $kimono=FaBCRUEquipment($p,'celestial_kimono');if(($o->CardID==='celestial_kimono'||$kimono)&&!FaBDYNCount($p,'KIMONO')){FaBDYNAdd($p,'KIMONO');AddResources($p,intval(GetResources($p))+1);}
 }
}
function FaBDYNLeaving(int $p,object $o,string $zone): void {
 if(FaBWTRBase($o->CardID)==='ironsong_pride')foreach(FaBChoiceRefs($p,'Weapons',['type'=>'Sword']) as $r)FaBSetObjectCounter(FaBIdentityFromMZ($r)['object'],'POWER',0);
 $e=FaBARCCard(intval($o->UniqueID),'dynExiled');if(is_array($e)){$f=FaBFindUID(intval($e['uid']));if($f&&$f['zone']==='Banish'){FaBARCSetCard(intval($o->UniqueID),'dynExiled',null);FaBMoveUID(intval($e['uid']),'Arena',intval($e['player']));}}
 if($o->CardID==='nitro_mechanoid'&&$zone!=='CombatChain'){foreach(FaBObjectCounters($o)['DYN_MATERIAL']??[] as $id)AddGraveyard($p,CardID:$id);$o->CardID='construct_nitro_mechanoid_yellow';}
 if($o->CardID==='suraya_archangel_of_knowledge'&&!empty(FaBObjectCounters($o)['DYN_SURAYA']))$o->CardID='invoke_suraya_yellow';
}
function FaBDYNDamaged(int $p,int $n,string $ref): void {$f=FaBIdentityFromMZ($ref);if(!$f||$n<=0)return;$id=$f['object']->CardID;if($id==='surgent_aethertide')FaBDYNAdd($p,'SURGENT_DAMAGE',$n);if($id==='suraya_archangel_of_knowledge')FaBCRUGainLife($p,$n);}
function FaBDYNDrawn(int $p,int $n): void {if(GetCurrentPhase()!=='MAIN')return;for($i=0;$i<$n*FaBDYNCount($p,'BRAINSTORM');++$i)FaBRunSourceMacro('ResolveAbility',$p,'brainstorm_blue',['mzID'=>FaBFindUID(FaBUPRHeroUID($p))['mzID']??'']);}
function FaBDYNArcaneBonus(int $uid): int {$f=FaBFindUID($uid);$n=0;if($f)foreach((array)($f['object']->TurnEffects??[]) as $t)if(str_starts_with($t,'DYN_ARCANE:'))$n+=intval(substr($t,11));return $n;}
function FaBDYNStart(int $p): void {
 $s=FaBGetState();$s['dynEdicts']=array_values(array_filter($s['dynEdicts']??[],fn($e)=>intval($e['player'])!==$p));FaBSetState($s);
 foreach(FaBUPRUIDs(implode('&',FaBChoiceRefs($p,'Arena'))) as $uid){$f=FaBFindUID($uid);if(!$f)continue;$b=FaBWTRBase($f['object']->CardID);if((str_starts_with($b,'blessing_of_')&&$b!=='blessing_of_qi')||in_array($b,['mindstate_of_tiger','never_yield','tome_of_aeo'],true))FaBRunSourceMacro('StartTurn',$p,$f['object']->CardID,['mzID'=>$f['mzID']]);}
 foreach(FaBChoiceRefs($p,'Graveyard') as $r){$f=FaBIdentityFromMZ($r);if(in_array($f['object']->CardID,['blacktek_whisperers','mask_of_perdition'],true)&&count(FaBMONArena($p,'silver'))>=2)FaBRunSourceMacro('StartTurn',$p,$f['object']->CardID,['mzID'=>$r]);}
}
function FaBDYNEnd(int $p): void {
 foreach(FaBUPRUIDs(implode('&',FaBChoiceRefs($p,'Arena'))) as $uid){$f=FaBFindUID($uid);if(!$f)continue;$b=FaBWTRBase($f['object']->CardID);
  if($b==='ponder'&&!FaBOUTHasDiseases($p)){FaBMONDestroy($uid);DoDrawCard($p,1);}
  if($b==='looming_doom')FaBRunSourceMacro('ResolveAbility',$p,$f['object']->CardID,['mzID'=>$f['mzID']]);
  if($b==='ironsong_pride'&&!FaBDYNCount($p,'SWORD_HIT'))FaBMONDestroy($uid);
 }
 for($i=0;$i<FaBDYNCount($p,'HEAT_SEEKER');++$i){$r=FaBChoiceRefs($p,'Deck')[0]??'';if($r!=='')FaBARCLoadArsenal($p,$r,true);}
 $s=FaBGetState();$s['dynIllusionNames']=[];$s['dynYoji']=[];FaBSetState($s);
}
function FaBDYNClose(): void {$s=FaBGetState();$players=array_keys($s['dynRegicide']??[]);$s['dynRegicide']=[];FaBSetState($s);foreach($players as $p)if(FaBSeatIsLive(intval($p))&&!intval(GetWinner()))FaBEliminateSeat(intval($p));foreach(FaBLiveSeats() as $p){FaBDYNClear($p,'SCRAMBLE');foreach(['Equipment','Weapons','CombatChain'] as $zone)foreach(FaBChoiceRefs($p,$zone) as $r){$o=FaBIdentityFromMZ($r)['object'];$o->TurnEffects=array_values(array_filter((array)$o->TurnEffects,fn($t)=>!str_starts_with($t,'DYN_DEFENSE:')&&!str_starts_with($t,'DYN_WITHSTAND_ACTIVE:')));}}}

function FaBDYNBerserk(int $p,int $uid): void {$f=FaBFindUID($uid);if(!$f||$f['zone']!=='Graveyard')return;FaBMoveUID($uid,'Banish',$p);$top=FaBChoiceRefs($p,'Deck')[0]??'';FaBRevealChoices($p,$top);$f=FaBIdentityFromMZ($top);if($f&&intval(CardPower($f['object']->CardID))>=6)DoDrawCard($p,1);}
function FaBDYNReincarnate(int $p,int $uid): void {$f=FaBFindUID($uid);if($f&&$f['zone']==='Graveyard')FaBARCToDeck($p,$uid,false);}
