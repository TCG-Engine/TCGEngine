<?php
function FaBELEAbilityRows(): array {return [
    'lexi'=>[['ACTION',0,false,true,true,0,'Reveal arsenal']], 'lexi_livewire'=>[['ACTION',0,false,true,true,0,'Reveal arsenal']],
    'oldhim'=>[['DEFENSE',3,false,false,true,0,'Elemental defense']], 'oldhim_grandfather_of_eternity'=>[['DEFENSE',3,false,false,true,0,'Elemental defense']],
    'shock_charmers'=>[['INSTANT',2,false,false,false,0,'Deal one on next hit']],
    'heart_of_ice'=>[['ACTION',1,false,true,true,0,'Tax opposing heroes']],
    'coat_of_frost'=>[['ACTION',0,true,true,false,0,'Create Frostbite']],
    'crown_of_seeds'=>[['INSTANT',1,false,false,true,0,'Cycle arsenal and prevent damage']],
    'deep_blue'=>[['ACTION',0,true,true,false,0,'Gain three resources']],
    'cracker_jax'=>[['ACTION',0,true,true,false,0,'Empower next attack']],
    'honing_hood'=>[['INSTANT',0,true,false,false,0,'Exchange arsenal']],
    'ragamuffins_hat'=>[['INSTANT',0,true,false,false,0,'Cycle a card']],
    'runaways'=>[['INSTANT',0,true,false,false,0,'Prevent one damage']],
    'plume_of_evergrowth'=>[['INSTANT',3,true,false,false,0,'Recover Earth card']],
    'amulet_of_earth_blue'=>[['INSTANT',0,true,false,false,0,'Empower attack actions']],
    'amulet_of_ice_blue'=>[['INSTANT',0,true,false,false,0,'Tax a hero']],
    'amulet_of_lightning_blue'=>[['INSTANT',0,true,false,false,0,'Grant go again']],
    'shiver'=>[['INSTANT',1,false,false,true,0,'Load arrow']],
    'voltaire_strike_twice'=>[['INSTANT',1,false,false,true,0,'Load arrow (first use)'],['INSTANT',1,false,false,true,0,'Load arrow (second use)']],
    'sutcliffes_suede_hides'=>[['REACTION',1,true,false,false,0,'Grant go again']],
    'spellbound_creepers'=>[['INSTANT',1,false,false,true,0,'Play a non-attack as an instant']],
];}
function FaBELEAbilityLegal(int $p,array $f,array $spec): bool {
    $o=$f['object'];$id=$o->CardID;$s=FaBGetState();
    if(($f['zone']==='Equipment'||($o->FromZone??'')==='Equipment')&&FaBELECount($p,'NO_EQUIPMENT'))return false;
    if($spec['timing']==='DEFENSE'&&($s['window']!=='REACTION'||!FaBIsDefendingHero($p,$s)))return false;
    if(in_array($id,['lexi','lexi_livewire','crown_of_seeds'],true)&&FaBELEFaceDown($p)==='')return false;
    if($id==='deep_blue'&&!FaBHandCount($p))return false;
    if($id==='ragamuffins_hat'&&FaBHandCount($p)!==1)return false;
    if($id==='runaways'&&!FaBELECount($p,'DAMAGED'))return false;
    if($id==='sutcliffes_suede_hides'&&(!count(FaBARCPlayed($p,true))||FaBELECombatChoices('AA')===''))return false;
    if($id==='amulet_of_lightning_blue'&&FaBELECombatChoices('Action')==='')return false;
    if($id==='spellbound_creepers'&&!FaBELECount($p,'AA_COMBAT'))return false;
    foreach(['amulet_of_earth_blue'=>'Earth','amulet_of_ice_blue'=>'Ice','amulet_of_lightning_blue'=>'Lightning'] as $card=>$e)if($id===$card&&!FaBELECount($p,'FUSED_'.$e))return false;
    return true;
}
function FaBELEFaceDown(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arsenal'),fn($r)=>intval(FaBIdentityFromMZ($r)['object']->FaceDown??1)===1));}
function FaBELEActivated(int $p,object $stack): void {
    foreach(FaBMONArena($p,'frostbite') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
    if(FaBELECount($p,'ENDLESS'))FaBELEFrost($p,FaBELECount($p,'ENDLESS'));
    if($stack->CardID==='spellbound_creepers'){$f=FaBFindUID(intval($stack->SourceUniqueID));if($f)FaBSetObjectCounter($f['object'],'BIND',intval(FaBObjectCounters($f['object'])['BIND']??0)+1);}
}
function FaBELERecordPitch(int $p,string $id): void {
    $s=FaBGetState();$pending=$s['pendingPayment']??null;if(!$pending||intval($pending['player'])!==$p)return;
    foreach(['Earth','Ice','Lightning'] as $e)if(FaBHasType($id,$e))FaBARCSetCard(intval($pending['uid']),'elePitched'.$e,true);
}
function FaBELEDealArcane(int $p,int $target,int $n,int $payment,int $uid): int {
    if(FaBEVRUnpreventable($p,$target))$payment=0;
    $payment=max(0,min($payment,intval(GetResources($target))));AddResources($target,intval(GetResources($target))-$payment);
    $f=FaBFindUID($uid);$dealt=DoDamage($p,$f['mzID']??'',$target,max(0,$n-$payment),'ARCANE');
    $s=FaBGetState();$s['arcaneDealt'][(string)$p][(string)$target]=intval($s['arcaneDealt'][(string)$p][(string)$target]??0)+$dealt;FaBSetState($s);return $dealt;
}
function FaBELEDestroyHook(int $uid): void {
    $f=FaBFindUID($uid);if($f&&$f['object']->CardID==='new_horizon')foreach(FaBChoiceRefs($f['player'],'Arsenal') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
}
function FaBELEAttack(int $p,object $o): void {
    if(FaBWTRBase($o->CardID)==='ball_lightning'&&!HasNoAbilities($o))FaBELEAdd($p,'BALL');
    if(FaBELEPower($p,$o)>0)FaBELEKorshemActivity();
    if(FaBWTRIsAttackAction($o))FaBELEAdd($p,'AA_COMBAT');$uid=intval($o->UniqueID);
    if(FaBELECount($p,'FLASH_ICE'))FaBRunSourceMacro('ResolveAbility',$p,'flashfreeze_red',['mzID'=>FaBFindUID($uid)['mzID']]);
    $n=(FaBWTRIsAttackAction($o)?count(FaBMONArena($p,'sting_of_sorcery')):0);foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'ELE_ARCANE:'))$n+=intval(substr($tag,11));
    for($i=0;$i<$n;++$i)FaBRunSourceMacro('ResolveAbility',$p,'sting_of_sorcery_blue',['mzID'=>FaBFindUID($uid)['mzID']]);
    if($o->CardID==='duskblade'&&FaBELEBothActions($p)){$f=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));if($f)FaBSetObjectCounter($f['object'],'POWER',intval(FaBObjectCounters($f['object'])['POWER']??0)+1);}
}
function FaBELEDefended(int $p,object $o): void {
    if(FaBELEDefense($p,$o)>0)FaBELEKorshemActivity();
    if(FaBELEPower($p,$o)>0)FaBELEKorshemActivity();
    if(FaBWTRIsAttackAction($o))FaBELEAdd($p,'AA_COMBAT');$s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f)return;$a=$f['object'];
    if($a->CardID==='endless_winter_red'&&FaBELEFused(intval($a->UniqueID)))FaBELEFrost($p);
    if(($o->FromZone??'')==='Hand'&&FaBELEElement($a,'Lightning|Elemental'))foreach(FaBCRUEquipment(intval($s['attacker']),'mark_of_lightning') as $r)FaBRunSourceMacro('ResolveAbility',intval($s['attacker']),'mark_of_lightning',['mzID'=>$r]);
    if($o->CardID==='rampart_of_the_rams_head')FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);
}
function FaBELEHit(int $p,object $o,int $n): void {
    if(!FaBFaiHeroHit()||$n<=0)return;$s=FaBGetState();$target=intval($s['defender']);$uid=intval($o->UniqueID);$ref=FaBFindUID($uid)['mzID'];
    if(FaBELECount($p,'FORCE')&&FaBWTRIsAttackAction($o)&&FaBAttackPower($s)>intval(CardPower($o->CardID)))DoDrawCard($p,FaBELECount($p,'FORCE'));
    FaBELEFrost($target,FaBELECount($p,'QUAKE'));
    if(FaBELEElement($o,'Ice|Elemental')){FaBELEFrost($target,FaBELECount($p,'CHILL'));FaBELEClear($p,'CHILL');}
    $events=[];
    foreach(FaBWTREffects($p) as $e){$k=$e['type']??'';if($k==='ELE_BUZZ'||$k==='ELE_FLASH_LIGHTNING'||($k==='ELE_ELECTRIFY'&&FaBWTRIsAttackAction($o)))$events[]=intval($e['amount']);}
    foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'ELE_HIT_DAMAGE:'))$events[]=intval(substr($tag,15));
    if(FaBWTRIsAttackAction($o))FaBELEClear($p,'ELECTRIFY');
    foreach($events as $damage)if($damage>0)DoDamage($p,$ref,$target,$damage,'PHYSICAL');
    if(in_array('ELE_SEEK_DESTROY',(array)$o->TurnEffects,true))FaBWTRAddEffect($target,'ELE_SEEK_DESTROY',1,['expiresAfterTurnOf'=>$target],true);
    if(in_array('ELE_TEAR',(array)$o->TurnEffects,true))FaBRunSourceMacro('ResolveAbility',$p,'tear_asunder_blue',['mzID'=>$ref]);
}
