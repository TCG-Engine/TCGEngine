<?php
function FaBIARAbilityRows(): array {
    $r=[];
    $r['gate_to_iarathael']=[['INSTANT',1,true,false,false,0,'Open the Gate']];
    foreach(['malice','malice_domina_of_the_dead'] as $id)$r[$id]=[['ACTION',1,false,true,false,0,'Play a zombie from graveyard']];
    foreach(['grille_of_repentance','path_of_repentance','robe_of_repentance'] as $id)$r[$id]=[['INSTANT',0,true,false,false,0,'Turn blood debt face down']];
    $r['hex_gauntlet']=[['INSTANT',0,false,false,false,0,'Banish to turn blood debt face down']];
    $r['grasp_of_the_darknight']=[['ACTION',1,true,true,false,0,'Opt and create Runechant']];
    $r['appalling_bearers']=[['INSTANT',0,true,false,false,0,'Discard a zombie to prevent damage']];
    $r['astral_ambience_yellow']=[['INSTANT',0,false,false,false,0,'Tap a shield for go again']];
    foreach(['corpse_cover','rally_the_shadow_horde'] as $b)foreach(['red','yellow','blue'] as $c)$r[$b.'_'.$c]=[['INSTANT',0,false,false,true,0,'Pay defending ability cost']];
    foreach(['restless_cleric','restless_corporal','restless_plowman','restless_looter'] as $b)$r[$b.'_red']=[[$b==='restless_looter'?'INSTANT':'ACTION',0,false,$b!=='restless_looter',false,0,'Use zombie ability']];
    foreach(FaBIARHandAbilities() as $id)$r[$id]=[['INSTANT',$id==='apex_buster_yellow'?2:(in_array(FaBWTRBase($id),['consuming_appetite','consuming_lash','consuming_strength','cogwerx_prong_bot'],true)?1:0),false,false,false,0,'Use card from hand']];
    return $r;
}
function FaBIARHandAbilities(): array {
    $ids=['blood_harvest','consuming_appetite_yellow','consuming_lash_yellow','consuming_strength_yellow','fallen_herald_yellow','tribute_to_greater_power_red','apex_buster_yellow','hoodwink_blue','deadly_spinneret_red','cogwerx_prong_bot_yellow'];
    foreach(['cleave_the_heavens','satiate_bloodthirst','runic_disposition','runic_reaving'] as $b)foreach(['red','yellow','blue'] as $c)$ids[]=$b.'_'.$c;
    return $ids;
}
function FaBIARNeedsPrepare(string $id): bool {return in_array(FaBWTRBase($id),['gate_to_iarathael','malice','malice_domina_of_the_dead','corpse_cover','rally_the_shadow_horde','appalling_bearers','apex_buster','hoodwink','astral_ambience'],true);}
function FaBIARSpecialZone(array $f): bool {
    $b=FaBWTRBase($f['object']->CardID);
    if($f['zone']==='Hand'&&in_array($f['object']->CardID,FaBIARHandAbilities(),true))return true;
    return $f['zone']==='CombatChain'&&intval($f['object']->ChainLink)===intval(FaBGetState()['chainLink'])&&
        (($b==='astral_ambience'&&$f['object']->Role==='ATTACK')||(in_array($b,['corpse_cover','rally_the_shadow_horde'],true)&&in_array($f['object']->Role,['DEFENSE','DEFENSE_REACTION'],true)));
}
function FaBIARAbilityLegal(int $p, array $f): bool {
    $id=$f['object']->CardID;if(!isset(FaBIARAbilityRows()[$id]))return true;
    $b=FaBWTRBase($id);
    if($b==='apex_buster')return $f['zone']==='Hand'&&FaBIARApexTargets($p)!=='';
    if($b==='corpse_cover')return FaBIARSpecialZone($f)&&FaBIARAllyCosts($p)!=='';
    if($b==='rally_the_shadow_horde')return FaBIARSpecialZone($f)&&FaBHandCount($p)>0;
    if($b==='appalling_bearers')return count(FaBChoiceRefs($p,'Hand',['type'=>'Zombie']))>0;
    if($b==='astral_ambience')return FaBIARSpecialZone($f)&&FaBIARReadyShields($p)!=='';
    if(in_array($id,FaBIARHandAbilities(),true))return $f['zone']==='Hand';
    if(FaBIARNeedsPrepare($id))return ($id==='gate_to_iarathael'||intval($f['object']->Status)===2)&&FaBIARPermissionChoices($p,$id==='gate_to_iarathael'?'Gate':'Zombie')!=='';
    if(str_starts_with($id,'restless_'))return $f['zone']==='Arena'&&intval($f['object']->Status)===2&&($id!=='restless_looter_red'||FaBHandCount($p)>0)&&($id!=='restless_corporal_red'||FaBChoiceRefs($p,'Banish'));
    return true;
}
function FaBIARAbilityPaid(int $p, object $o): void {
    $id=$o->CardID;$uid=intval($o->UniqueID);
    if(in_array($id,FaBIARHandAbilities(),true)) {
        $f=FaBFindUID($uid);if(!$f)return;
        if(str_starts_with($id,'runic_')||in_array($id,['apex_buster_yellow','hoodwink_blue','deadly_spinneret_red','cogwerx_prong_bot_yellow'],true))FaBDiscardChoice($p,$f['mzID']);else FaBMoveUID($uid,'Banish',$p);
    }
    if($id==='hex_gauntlet')FaBMoveUID($uid,'Banish',$p);
    if(in_array($id,['malice','malice_domina_of_the_dead'],true)||str_starts_with($id,'restless_'))$o->Status=1;
    if($id==='gate_to_iarathael')FaBIARAdd($p,'GATE');
}
function FaBIARDebtActions(int $p): string {
    return implode('&',array_filter(explode('&',FaBIARBanishRefs($p,'DEBT')),fn($r)=>($f=FaBIdentityFromMZ($r))&&FaBHasType($f['object'],'Action')));
}
function FaBIARPermissionChoices(int $p, string $kind): string {
    if($kind==='Zombie')return implode('&',FaBChoiceRefs($p,'Graveyard',['type'=>'Zombie']));
    $refs=[];
    // Planar Chaos targets a card. UPF target restrictions apply to its controller.
    foreach(FaBIARCount($p,'PLANAR')?array_merge([$p],FaBAdjacentOpponents($p)):[$p] as $seat)$refs=array_merge($refs,explode('&',FaBIARDebtActions($seat)));
    return implode('&',array_filter($refs));
}
function FaBIARStorePermission(int $p, int $uid, string $ref, string $kind): void {
    if(!in_array($ref,explode('&',FaBIARPermissionChoices($p,$kind)),true))return;
    $f=FaBIdentityFromMZ($ref);
    FaBARCSetCard($uid,'iarGrant',['uid'=>intval($f['object']->UniqueID),'zone'=>$f['zone'],'seat'=>$f['player'],'kind'=>$kind]);
    if($kind==='Gate'&&FaBIARCount($p,'PLANAR'))FaBIARAdd($p,'PLANAR',-1);
}
function FaBIARGrantPermission(int $p, int $uid): void {
    $g=FaBARCCard($uid,'iarGrant',[]);$f=FaBFindUID(intval($g['uid']??0));
    if(!$f||$f['zone']!==($g['zone']??'')||!empty($f['object']->FaceDown)||!FaBSeatIsLive($p))return;
    if(($g['kind']??'')==='Gate'&&(!FaBHasType($f['object'],'Action')||!FaBHasKeyword($f['object'],'Blood Debt')))return;
    if(($g['kind']??'')==='Zombie'&&!FaBHasType($f['object'],'Zombie'))return;
    FaBARCSetCard(intval($f['object']->UniqueID),'iarPermission',['player'=>$p,'zone'=>$f['zone'],'turn'=>intval(GetTurnNumber())]);
}
function FaBIARCanPlay(int $p, array $f): bool {
    $o=$f['object'];$b=FaBWTRBase($o->CardID);
    if(in_array($b,['consuming_lash','consuming_strength'],true)&&!FaBIARHasBlasmophet($p))return false;
    if(in_array($b,['harbinger_of_destruction','tome_of_necrosis','favorable_winds'],true)&&FaBIARAdditionalCosts($p,intval($o->UniqueID))==='')return false;
    $a=FaBFindUID(intval(FaBGetState()['attackUID']));
    if(FaBHasType($o,'Defense Reaction')&&$a&&in_array('IAR_NO_DR',(array)$a['object']->TurnEffects,true))return false;
    return true;
}
function FaBIARHasBlasmophet(int $p): bool {
    foreach(array_merge(FaBChoiceRefs($p,'Arena'),FaBChoiceRefs($p,'Hero')) as $r)if(str_starts_with(CardName(FaBIdentityFromMZ($r)['object']->CardID),'Blasmophet,'))return true;
    return false;
}
function FaBIARAlternative(int $p, int $uid, string $ref, bool $discard): void {
    $f=FaBIdentityFromMZ($ref);$s=FaBGetState();if(!$f||$f['zone']!=='Hand'||$f['player']!==$p||intval($s['pendingPayment']['uid']??0)!==$uid)return;
    if($discard){if(!FaBHasType($f['object'],'Ally')||!FaBDiscardChoice($p,$ref))return;}else FaBOMNBottom($p,$ref,true);
    $s=FaBGetState();$card=FaBFindUID($uid);if(!$card)return;
    $s['pendingPayment']['cost']=max(0,intval($s['pendingPayment']['cost'])-intval(CardCost($card['object']->CardID)));FaBSetState($s);
}
function FaBIARReadyHero(int $p): void {foreach(GetHero($p) as $o)if(is_object($o)&&empty($o->removed))$o->Status=2;}
function FaBIARHeadache(int $p): void {
    foreach(['Hand','Arsenal'] as $zone)foreach(FaBChoiceRefs($p,$zone) as $r){
        FaBRevealChoices($p,$r);$f=FaBIdentityFromMZ($r);if(!$f||!FaBHasType($f['object'],'Action')||FaBWTRIsAttackAction($f['object']))continue;
        if($zone==='Hand')FaBDiscardChoice($p,$r);else FaBMONDestroy(intval($f['object']->UniqueID));
    }
}
function FaBIAREcho(): bool {
    $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f||!FaBWTRIsAttackAction($f['object']))return false;
    $name=CardName($f['object']->CardID);$n=0;foreach(FaBLiveSeats() as $p)foreach(FaBARCPlayed($p) as $id)if(CardName($id)===$name)++$n;return $n>1;
}
function FaBIARFerryman(int $p, int $uid, string $ref): void {
    if(!in_array($ref,explode('&',FaBIARDebtActions($p)),true))return;
    FaBMoveUID(FaBPENUID($ref),'Deck',$p);$f=FaBFindUID($uid);if($f)FaBMoveUID($uid,'Deck',intval($f['object']->Owner?:$p));
}
function FaBIARRuneTrigger(int $p, string $base, int $attack, string $event): void {
    if($event==='iarDestroy'){FaBARCCreateRunes($p,1);return;}
    if($event!=='iarUsurp')return;
    if($base==='runechant_of_envy')FaBCRUGainLife($p,1);
    if($base==='runechant_of_gluttony')AddResources($p,intval(GetResources($p))+1);
    if($base==='runechant_of_greed')DoDrawCard($p,1);
    if($base==='runechant_of_lust')FaBARCCreateRunes($p,1);
    foreach(['runechant_of_pride'=>'WTR_POWER:1','runechant_of_sloth'=>'GO_AGAIN','runechant_of_wrath'=>'OVERPOWER'] as $b=>$tag)if($base===$b)FaBTagUID($attack,$tag);
}
function FaBIARAfterMove(int $p, object $o, string $from, string $to): void {
    if($from==='Arena'&&$to!=='Arena')foreach(FaBIARBoundMarks(intval($o->UniqueID),true) as $r)FaBMONDestroy(FaBPENUID($r));
    if($from!==$to)FaBARCSetCard(intval($o->UniqueID),'iarPermission',[]);
    if($to==='Banish'&&empty($o->FaceDown)){
        if(FaBHasKeyword($o,'Blood Debt'))FaBIARAdd($p,'BANISHED_DEBT');
        $b=FaBWTRBase($o->CardID);
        if($b==='arknight_descendancy'||($b==='open_the_gate_to_iarathael'&&in_array($from,['Hand','Deck'],true)))FaBROSQueue($p,$o->CardID,intval($o->UniqueID),['rosEvent'=>'iarBanish']);
    }
    if($to==='Soul')foreach(FaBMONArena($p,'blessing_of_suraya') as $r)FaBWTRCreateArena($p,'ponder');
    if($to==='Arena'&&$from!=='Arena'&&$o->CardID==='gate_to_iarathael')FaBIARAdd($p,'GATE');
    if($to==='Arena'&&$from!=='Arena'&&FaBHasType($o,'Ally')) {
        foreach(FaBCRUEquipment($p,'danse_macabre') as $r)if(FaBIARDanseReady(FaBPENUID($r)))FaBROSQueue($p,'danse_macabre',FaBPENUID($r),['rosEvent'=>'iarEnter','iarAttack'=>intval($o->UniqueID)]);
        if(FaBHasType($o,'Zombie')&&FaBMONWeapon($p,'vox_necropolis')&&intval(GetTurnPlayer())===$p&&GetCurrentPhase()==='MAIN'&&in_array(FaBARCCard(intval($o->UniqueID),'iarPlayedFrom',''),['Banish','Graveyard'],true)){
            $o->Status=1;FaBROSQueue($p,'vox_necropolis',intval($o->UniqueID),['rosEvent'=>'iarAttack']);
        }
    }
}
function FaBIARCreatedRunes(int $p, int $n): void {
    if($n<1)return;FaBIARAdd($p,'RUNES_CREATED',$n);
    foreach(GetHero($p) as $o)if(is_object($o)&&empty($o->removed)&&!HasNoAbilities($o)&&in_array($o->CardID,['viserai_between_worlds','viserai_the_forsaken'],true))FaBROSQueue($p,$o->CardID,intval($o->UniqueID),['rosEvent'=>'iarRune']);
}
function FaBIARTraverse(int $p, string $from): void {
    foreach(GetHero($p) as $o)if(is_object($o)&&empty($o->removed)&&$o->CardID===$from&&!HasNoAbilities($o)){
        if($from==='viserai_usurper'){$front=(string)(FaBObjectCounters($o)['IAR_FRONT']??'');if($front!=='')$o->CardID=$front;}
        elseif(in_array($from,['viserai_between_worlds','viserai_the_forsaken'],true)){
            $c=FaBObjectCounters($o);$c['IAR_FRONT']=$from;$o->Counters=$c;$o->CardID='viserai_usurper';
        }
    }
}
function FaBIARActionPhase(int $p): void {
    FaBIARWindPhase($p);
    foreach(explode('&',FaBIARRunechants($p)) as $r){$f=FaBIdentityFromMZ($r);if($f&&str_starts_with($f['object']->CardID,'runechant_of_'))FaBMONDestroy(intval($f['object']->UniqueID));}
    foreach(FaBMONArena($p,'sigil_of_the_muse') as $r)FaBROSQueue($p,'sigil_of_the_muse_red',FaBPENUID($r),['rosEvent'=>'iarStart']);
}
function FaBIARStart(): void {
    foreach(FaBLiveSeats() as $p)foreach(FaBMONArena($p,'bridge_of_damnation') as $r)FaBROSQueue($p,'bridge_of_damnation_blue',FaBPENUID($r),['rosEvent'=>'iarStart']);
}
function FaBIARDrawReplacement(int $p): bool {
    if(!in_array(GetCurrentPhase(),['MAIN'],true))return false;
    foreach(FaBLiveSeats() as $seat)if(FaBMONArena($seat,'sigil_of_the_muse')){FaBWTRCreateArena($p,'ponder');return true;}
    return false;
}
function FaBIARBeforeDeath(int $uid): bool {
    $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Arena'||!FaBHasKeyword($f['object'],'Incarnate'))return false;
    $f['object']->removed=true;
    foreach(FaBIARBoundMarks($uid) as $r)FaBMONDestroy(FaBPENUID($r));
    return true;
}
function FaBIARZombieDied(int $p, object $o): void {
    if(!FaBHasType($o,'Zombie'))return;
    if(!HasNoAbilities($o)&&FaBHasKeyword($o,'Decay'))foreach(FaBMONArena($p,'restless_templar') as $r)FaBWTRCreateArena($p,'gate_to_iarathael');
    if(!HasNoAbilities($o)&&$o->CardID==='restless_outlaw_red')FaBIARCorpse($p);
    if(FaBMONHero($p,'malice')||FaBMONHero($p,'malice_domina_of_the_dead')){
        $f=FaBFindUID(intval($o->UniqueID));if($f&&$f['zone']==='Graveyard'){
            $f['object']->FaceDown=1;$f['object']->PlayableFromBanish=0;
            FaBMoveUID(intval($o->UniqueID),'Banish',intval($o->Owner?:$p));
        }
        FaBIARCorpse($p);
    }
}

function FaBIARPayZombies(int $p, string $refs): int {
    $n=0;foreach(array_slice(array_unique(explode('&',$refs)),0,3) as $r)if(in_array($r,explode('&',FaBIARAllyCosts($p,true)),true)&&FaBIARPayAlly($p,$r))++$n;return $n;
}
function FaBIARForsaken(int $p,int $uid,string $mode): void {
    foreach(array_unique(explode(',',$mode)) as $m){
        if($m==='0')FaBTagUID($uid,'IAR_FORSKEN_GATE');
        if($m==='1')FaBTagUID($uid,'WTR_POWER:2');
        if($m==='2')FaBTagUID($uid,'GO_AGAIN');
    }
}
function FaBIARPayRunes(int $p,string $refs): int {
    $n=0;foreach(array_unique(explode('&',$refs)) as $r)if(in_array($r,explode('&',FaBIARRunechants($p)),true)){
        $uid=FaBPENUID($r);FaBMONDestroy($uid);$f=FaBFindUID($uid);if(!$f||$f['zone']!=='Arena')++$n;
    }return $n;
}
function FaBIARHoodwink(int $p,int $stack,string $refs): void {
    $f=FaBFindUID($stack);$source=FaBFindUID(intval($f['object']->SourceUniqueID??0));$n=$source?intval(CardDefense($source['object']->CardID)):0;
    foreach(array_unique(explode('&',$refs)) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&$f['zone']==='Hand'&&(!$source||$f['object']->UniqueID!==$source['object']->UniqueID)){
        $def=intval(CardDefense($f['object']->CardID));if(FaBDiscardChoice($p,$r))$n+=$def;
    }}FaBARCSetCard($stack,'iarHoodwink',$n);
}
function FaBIARApexTargets(int $p): string {
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a||intval($s['attacker'])!==$p||FaBMONBasePower($p,$a['object'])<6)return '';
    $refs=[];foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))$refs[]=$r;}return implode('&',$refs);
}
function FaBIARApex(int $p,int $uid): void {$f=FaBFindUID($uid);if($f&&in_array($f['mzID'],explode('&',FaBIARApexTargets($p)),true))FaBMONDestroy($uid);}
function FaBIARCrankItems(int $p): string {return implode('&',array_filter(FaBChoiceRefs($p,'Arena',['type'=>'Item']),fn($r)=>FaBHasKeyword(FaBIdentityFromMZ($r)['object'],'Crank')));}
function FaBIARSteam(int $p,string $r): void {if(in_array($r,explode('&',FaBIARCrankItems($p)),true))FaBARCSteam(FaBIdentityFromMZ($r)['object'],1);}
function FaBIARSpinneret(int $p): void {for($i=0,$n=FaBHNTOpenHands($p);$i<$n;++$i)AddWeapons($p,CardID:'graphene_chelicera',Owner:$p,Controller:$p,Status:2);}
function FaBIARBond(int $uid,string $element): bool {return (bool)FaBARCCard($uid,'elePitched'.$element,false);}
function FaBIARFrostSlots(int $p): void {
    foreach(['Head','Chest','Arms','Legs'] as $slot)if(FaBMPGExposed($p,$slot)){
        $o=FaBWTRCreateArena($p,'frostbite');if($o){$c=FaBObjectCounters($o);$c['MPG_SLOT']=$slot;$o->Counters=$c;}
    }
}
function FaBIARReadyShields(int $p): string {return implode('&',array_filter(FaBMONArena($p,'spectral_shield'),fn($r)=>intval(FaBIdentityFromMZ($r)['object']->Status)===2));}
function FaBIARTap(string $r): void {$f=FaBIdentityFromMZ($r);if($f)$f['object']->Status=1;}
function FaBIARAttacksHero(): bool {return (FaBGetState()['attackTarget']['type']??'HERO')==='HERO';}
function FaBIARGusto(int $uid,int $victim,string $r): void {
    $f=FaBIdentityFromMZ($r);if(!$f||$f['player']!==$victim||$f['zone']!=='Arena'||!FaBHasType($f['object'],'Aura'))return;
    FaBARCSetCard($uid,'iarGusto',CardName($f['object']->CardID));FaBMoveUID(intval($f['object']->UniqueID),'Hand',intval($f['object']->Owner));
}
function FaBIARGustoHit(int $uid,int $victim): void {
    $name=FaBARCCard($uid,'iarGusto','');if($name==='')return;
    foreach(FaBChoiceRefs($victim,'Arena',['type'=>'Aura']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(CardName($o->CardID)===$name)FaBMoveUID(intval($o->UniqueID),'Hand',intval($o->Owner));}
}
function FaBIARAllyTargets(int $p): string {return implode('&',array_filter(explode('&',FaBPENTargets($p)),fn($r)=>($f=FaBIdentityFromMZ($r))&&FaBHasType($f['object'],'Ally')));}
function FaBIARConsecrate(int $uid): void {$f=FaBFindUID($uid);if($f&&$f['zone']==='Arena'&&FaBHasType($f['object'],'Ally'))FaBARCSetCard($uid,'iarConsecrate',intval(GetTurnNumber()));}
function FaBIARConsecrateDamage(int $source,int $amount): int {
    $f=FaBFindUID($source);if(!$f||$amount<1)return $amount;
    $uid=intval(FaBObjectCounters($f['object'])['MON_SOURCE_UID']??$source);
    if(intval(FaBARCCard($uid,'iarConsecrate',-1))!==intval(GetTurnNumber()))return $amount;
    $ally=FaBFindUID($uid);if(!$ally)return $amount;
    if(FaBHasType($ally['object'],'Shadow')){$o=FaBMoveUID($uid,'Banish',intval($ally['object']->Owner));if($o)$o->FaceDown=1;}
    return 0;
}
function FaBIARDanseReady(int $uid): bool {$f=FaBFindUID($uid);return $f&&FaBDYNEquipmentStillPresent($uid)&&intval($f['object']->Status)===2&&!HasNoAbilities($f['object']);}
function FaBIARDanse(int $source,int $ally): void {
    $f=FaBFindUID($source);$a=FaBFindUID($ally);if(!$f||!$a||$a['zone']!=='Arena'||!FaBIARDanseReady($source))return;
    $f['object']->Status=1;FaBARCSetCard($ally,'iarDanse',intval(GetTurnNumber()));FaBARCSetCard($ally,'iarDanseGo',true);
}
function FaBIARTagged(int $uid,string $tag): bool {$f=FaBFindUID($uid);return $f&&in_array($tag,(array)$f['object']->TurnEffects,true);}
function FaBIARSharpenDaggers(int $p): void {foreach(FaBChoiceRefs($p,'Weapons',['type'=>'Dagger']) as $r)FaBBoltynSharpen($p,$r);}
function FaBIARForgeMark(int $p,int $uid,int $victim): void {
    $a=FaBFindUID($uid);if(!$a)return;$w=FaBFindUID(intval(FaBObjectCounters($a['object'])['WEAPON_UID']??$uid));if(!$w)return;
    $n=intval(FaBObjectCounters($w['object'])['POWER']??0);if($n<1)return;FaBSetObjectCounter($w['object'],'POWER',$n-1);FaBHNTMark($victim);
}
function FaBIARBoundMarks(int $ally,bool $includeSuppressed=false): array {
    $refs=[];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'Arena',['type'=>'Aura']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(($includeSuppressed||!HasNoAbilities($o))&&str_starts_with($o->CardID,'mark_of_')&&intval(FaBARCCard(intval($o->UniqueID),'iarBound'))===$ally)$refs[]=$r;}return $refs;
}
function FaBIARBoundTriggers(int $ally,bool $dies=false): void {
    $source=FaBFindUID($ally);
    foreach(FaBIARBoundMarks($ally,true) as $r){$f=FaBIdentityFromMZ($r);
        if($source&&!HasNoAbilities($source['object'])&&!HasNoAbilities($f['object']))FaBROSQueue(intval($source['object']->Controller?:$source['player']),$f['object']->CardID,$ally,['rosEvent'=>'iarBound']);
        if($dies)FaBMONDestroy(intval($f['object']->UniqueID));
    }
}
function FaBIARFreeZombieAttack(int $p,int $uid,string $target): void {
    $f=FaBFindUID($uid);$t=FaBIdentityFromMZ($target);if(!$f||!$t||$f['player']!==$p||$f['zone']!=='Arena')return;
    $descriptor=FaBAttackTargetDescriptor($t);if(!FaBResolveAttackTarget($descriptor,$p))return;
    $o=AddStack(CardID:$f['object']->CardID,Controller:$p,Kind:'ATTACK',SourceZone:'Arena',SourceUniqueID:$uid,Params:['attackTarget'=>$descriptor]);
    FaBSetObjectCounter($o,'MON_SOURCE_UID',$uid);FaBSetObjectCounter($o,'MON_ARENA_ATTACK',1);FaBSetObjectCounter($o,'MON_ARENA_POWER',intval(CardPower($o->CardID)));
    FaBIARAllyAttacks($p,$o);FaBIARApplyNext($p,$o);
}
function FaBIARAllyAttacks(int $p,object $o): void {
    $uid=intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0);
    if(in_array($o->CardID,['corrupted_corpse','wind_slicer_blue'],true)||($o->CardID==='blasmophet_the_insatiable_hunger'&&FaBIARCount($p,'BLASMOPHET_ATTACK')))FaBWTRTag($o,'GO_AGAIN');
    if($uid&&intval(FaBARCCard($uid,'iarDanse',-1))===intval(GetTurnNumber())&&FaBARCCard($uid,'iarDanseGo')){FaBWTRTag($o,'GO_AGAIN');FaBARCSetCard($uid,'iarDanseGo',false);}
}
function FaBIARIsAbility(int $uid): bool {$f=FaBFindUID($uid);return $f&&($f['object']->Kind??'')==='ABILITY';}
function FaBIARPreviousAutumn(): bool {return FaBWTRBase((string)(FaBGetState()['previousAttackCardID']??''))==='edge_of_autumn';}
function FaBIARBind(int $p,int $uid,string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||$f['zone']!=='Arena'||!FaBHasType($f['object'],'Ally')){FaBMONDestroy($uid);return;}
    FaBARCSetCard($uid,'iarBound',intval($f['object']->UniqueID));
}
function FaBIARAlternativeAvailable(int $p,array $f): bool {
    return FaBIARAlternativeChoices($p,intval($f['object']->UniqueID))!=='';
}
function FaBIARAlternativeChoices(int $p,int $uid): string {
    $f=FaBFindUID($uid);if(!$f)return '';
    $b=FaBWTRBase($f['object']->CardID);if(!in_array($b,['darkest_hour','skeletal_puppetry'],true))return '';
    $cost=max(0,FaBCardCost($f['object'],$p)-intval(CardCost($f['object']->CardID)));
    $choices=[];
    foreach(FaBChoiceRefs($p,'Hand',$b==='skeletal_puppetry'?['type'=>'Ally']:[]) as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->UniqueID)===intval($f['object']->UniqueID))continue;
        if(FaBAvailablePitch($p,intval($f['object']->UniqueID))-FaBMONPitchValue($p,$o->CardID)>=$cost)$choices[]=$r;
    }return implode('&',$choices);
}
// A mandatory card cost must leave enough pitch to finish the announced play.
function FaBIARAdditionalCosts(int $p,int $uid): string {
    $f=FaBFindUID($uid);if(!$f)return '';
    $b=FaBWTRBase($f['object']->CardID);
    $cost=intval(FaBGetState()['pendingPayment']['uid']??0)===$uid?intval(FaBGetState()['pendingPayment']['cost']):FaBCardCost($f['object'],$p);
    $refs=$b==='tome_of_necrosis'?explode('&',FaBIARAllyCosts($p)):FaBChoiceRefs($p,'Hand',$b==='favorable_winds'?['base'=>'goldfin_harpoon']:[]);
    return implode('&',array_filter($refs,function($r)use($p,$uid,$cost){
        $f=FaBIdentityFromMZ($r);if(!$f||intval($f['object']->UniqueID)===$uid)return false;
        return $f['zone']!=='Hand'||FaBAvailablePitch($p,$uid)-FaBMONPitchValue($p,$f['object']->CardID)>=$cost;
    }));
}
function FaBIAROwnBanish(int $p,int $uid): bool {return intval(FaBARCCard($uid,'iarOrigin',$p))===$p;}
function FaBIARBlasmophetAvailable(int $p,object $o): bool {
    $f=FaBFindUID(intval($o->UniqueID));if(!$f||$f['player']!==$p||$f['zone']!=='Banish'||!empty($o->FaceDown)||!FaBHasType($o,'Action')||!FaBHasKeyword($o,'Blood Debt'))return false;
    foreach(FaBMONArena($p,'blasmophet_the_insatiable_hunger') as $r)if(intval(FaBARCCard(FaBPENUID($r),'iarPlayTurn',-1))!==intval(GetTurnNumber()))return true;
    return false;
}
function FaBIARSpendPermission(int $p,array $f): void {
    $o=$f['object'];FaBARCSetCard(intval($o->UniqueID),'iarOrigin',$f['player']);
    if(!FaBIARBlasmophetAvailable($p,$o))return;
    $declared=FaBARCCard(intval($o->UniqueID),'iarDeclaredPermission',null);
    FaBARCSetCard(intval($o->UniqueID),'iarDeclaredPermission',null);
    if($declared!==null){if(intval($declared)>0)FaBARCSetCard(intval($declared),'iarPlayTurn',intval(GetTurnNumber()));return;}
    $b=FaBWTRBase($o->CardID);
    $other=FaBIARPermission($p,$o,'Banish')||!empty($o->PlayableFromBanish)||FaBMONBanishPlayable($p,$o)||FaBDTDBanishPlayable($p,$o)||in_array($b,['abyssal_bite','abyssal_force','abyssal_rush','enshrine_sin'],true)||str_ends_with($b,'_gloomblade')||($b==='usurp_the_shadow_throne'&&FaBIARCount($p,'USURPED'));
    if($other)return;
    foreach(FaBMONArena($p,'blasmophet_the_insatiable_hunger') as $r)if(intval(FaBARCCard(FaBPENUID($r),'iarPlayTurn',-1))!==intval(GetTurnNumber())){FaBARCSetCard(FaBPENUID($r),'iarPlayTurn',intval(GetTurnNumber()));return;}
}
function FaBIARPermissionOptions(int $p,object $o): string {
    if(!FaBIARBlasmophetAvailable($p,$o))return '';
    return implode('&',array_filter(FaBMONArena($p,'blasmophet_the_insatiable_hunger'),fn($r)=>intval(FaBARCCard(FaBPENUID($r),'iarPlayTurn',-1))!==intval(GetTurnNumber())));
}
function FaBIARChoosePermission(int $p,array $f): bool {
    $o=$f['object'];$uid=intval($o->UniqueID);
    if(FaBARCCard($uid,'iarDeclaredPermission',null)!==null||FaBIARPermissionOptions($p,$o)==='')return false;
    FaBRunSourceMacro('ResolveAbility',$p,'blasmophet_the_insatiable_hunger',['mzID'=>$f['mzID'],'rosEvent'=>'iarPermission']);return true;
}
function FaBIAROtherPermission(int $p,object $o): bool {
    $b=FaBWTRBase($o->CardID);
    return FaBIARPermission($p,$o,'Banish')||!empty($o->PlayableFromBanish)||FaBMONBanishPlayable($p,$o)||FaBDTDBanishPlayable($p,$o)||in_array($b,['abyssal_bite','abyssal_force','abyssal_rush','enshrine_sin'],true)||str_ends_with($b,'_gloomblade')||($b==='usurp_the_shadow_throne'&&FaBIARCount($p,'USURPED'))||(FaBHasType($o,'Aura')&&str_contains(CardName($o->CardID),'Runechant')&&FaBIARCount($p,'EMBRACE'));
}
function FaBIARClose(): void {
    $banish=false;foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if($o->CardID==='reach_of_the_abyss'&&!HasNoAbilities($o)&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))$banish=true;}
    if($banish)foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))FaBMoveUID(intval($o->UniqueID),'Banish',intval($o->Owner));}
    foreach(FaBLiveSeats() as $p){
        FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>!(($e['type']??'')==='IAR_NEXT'&&($e['tag']??'')==='IAR_CHAIN_ONLY'))));
        foreach(FaBChoiceRefs($p,'Weapons',['base'=>'wind_slicer']) as $r)if(FaBARCCard(FaBPENUID($r),'iarWindUsed',false))FaBMONDestroy(FaBPENUID($r));
    }
}
function FaBIAREnd(int $p): void {
    foreach(FaBLiveSeats() as $seat){
        foreach(FaBMONArena($seat,'blasmophet_the_insatiable_hunger') as $r)FaBROSQueue($seat,'blasmophet_the_insatiable_hunger',FaBPENUID($r),['rosEvent'=>'iarEnd']);
        if(FaBIARCount($seat,'GATE'))foreach(GetHero($seat) as $hero)if($hero->CardID==='viserai_usurper'&&!HasNoAbilities($hero))FaBROSQueue($seat,$hero->CardID,intval($hero->UniqueID),['rosEvent'=>'iarEnd']);
    }
    foreach(FaBChoiceRefs($p,'Arena',['type'=>'Ally']) as $r){$o=FaBIdentityFromMZ($r)['object'];
        if(intval(FaBARCCard(intval($o->UniqueID),'iarDanse',-1))===intval(GetTurnNumber())){FaBMONDestroy(intval($o->UniqueID));continue;}
        if(!FaBHasKeyword($o,'Decay'))continue;
        FaBSetObjectCounter($o,'UPR_LOST_HP',intval(FaBObjectCounters($o)['UPR_LOST_HP']??0)+1);
        if(intval($o->Damage)>=FaBUPRHealth($o))FaBMONDestroy(intval($o->UniqueID));
    }
}

function FaBIARShadowResistRefs(int $p,int $source): array {
    $f=FaBFindUID($source);$actor=$f?intval($f['object']->Controller??$f['player']):intval(FaBGetState()['attacker']);
    if(!$actor||!isset(GetHero($actor)[0])||!FaBHasType(GetHero($actor)[0],'Shadow'))return [];
    $refs=[];foreach(['Equipment','CombatChain','Arena'] as $zone)foreach(FaBChoiceRefs($p,$zone) as $r){$o=FaBIdentityFromMZ($r)['object'];if(HasNoAbilities($o))continue;if(FaBHasKeyword($o,'Shadow Resist 1'))$refs[]=$r;}return $refs;
}
function FaBIARShadowResistPay(int $p,string $r,int $source): int {
    if(!in_array($r,FaBIARShadowResistRefs($p,$source),true))return 0;FaBMONDestroy(FaBPENUID($r));return 1;
}
function FaBIARWindLock(int $p): void {
    $s=FaBGetState();$s['iarWind'][$p]=['after'=>intval(GetTurnNumber()),'active'=>-1];FaBSetState($s);
}
function FaBIARWindPhase(int $p): void {
    $s=FaBGetState();$lock=$s['iarWind'][$p]??null;if(!$lock||intval(GetTurnNumber())<=intval($lock['after']))return;
    foreach(GetHero($p) as $o)if(is_object($o)&&empty($o->removed))FaBWTRTag($o,'IAR_WIND_LOCK');
    $s['iarWind'][$p]['active']=intval(GetTurnNumber());FaBSetState($s);
}
function FaBIARWindEnd(int $p): void {
    $s=FaBGetState();if(intval($s['iarWind'][$p]['active']??-1)!==intval(GetTurnNumber()))return;
    foreach(GetHero($p) as $o)if(is_object($o))$o->TurnEffects=array_values(array_filter((array)$o->TurnEffects,fn($t)=>$t!=='IAR_WIND_LOCK'));
    unset($s['iarWind'][$p]);FaBSetState($s);
}
