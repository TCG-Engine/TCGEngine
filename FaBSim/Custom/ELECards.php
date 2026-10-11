<?php
// Tales of Aria: shared rules use live seat IDs; interactive effects are saved macros.
function FaBELECount(int $p,string $key): int {return FaBARCEffect($p,'ELE_'.$key);}
function FaBELEAdd(int $p,string $key,int $n=1): void {FaBWTRAddEffect($p,'ELE_'.$key,$n);}
function FaBELEElement(object $o,string $elements): bool {foreach(explode('|',$elements) as $e)if(FaBHasType($o,$e))return true;return false;}
function FaBELESelect(int $p,string $zone,string $elements='',string $kind='',int $max=999): string {
    return implode('&',array_filter(FaBChoiceRefs($p,$zone),function($r)use($elements,$kind,$max,$zone){$o=FaBIdentityFromMZ($r)['object'];
        if($zone==='Graveyard'&&$r===(string)DecisionQueueController::GetVariable('mzID'))return false;
        return ($elements===''||FaBELEElement($o,$elements))&&($kind===''||($kind==='ActionOrInstant'?(FaBHasType($o,'Action')||FaBHasType($o,'Instant')):($kind==='AA'?FaBWTRIsAttackAction($o):($kind==='NAA'?FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o):FaBHasType($o,$kind)))))&&($max===999||(is_numeric(CardCost($o->CardID))&&intval(CardCost($o->CardID))<=$max));
    }));
}
function FaBELEFused(int $uid,string $element=''): bool {return $element===''?(bool)FaBARCCard($uid,'eleFused'):(bool)FaBARCCard($uid,'eleFuse'.$element);}
function FaBELEFinishFusion(int $p,int $uid,array $refs,array $elements): void {
    $reveal=[];$all=true;if(FaBUPRBleak())return;
    foreach($elements as $i=>$element){$f=FaBIdentityFromMZ($refs[$i]??'');$ok=$f&&$f['player']===$p&&$f['zone']==='Hand'&&FaBHasType($f['object'],$element);
        FaBARCSetCard($uid,'eleFuse'.$element,$ok);if($ok){$reveal[]=$refs[$i];FaBELEAdd($p,'FUSED_'.$element);}else $all=false;
    }
    if(FaBELEFused($uid,'Ice'))FaBUPRFused($p);
    FaBARCSetCard($uid,'eleFused',$all);if($reveal){FaBELEAdd($p,'FUSED');FaBRevealChoices($p,implode('&',array_unique($reveal)));}
}
function FaBELEFrost(int $p,int $n=1): void {if(FaBSeatIsLive($p))for($i=0;$i<$n;++$i)FaBWTRCreateArena($p,'frostbite');}
function FaBELETax(int $p): int {
    $n=count(FaBMONArena($p,'frostbite'));
    foreach(FaBOpponents($p) as $other)$n+=count(FaBMONArena($other,'channel_lake_frigid'))+FaBELECount($other,'TAX');return $n;
}
function FaBELECanPitch(int $p,object $o): bool {return !FaBELECount($p,'NO_ZERO')||!is_numeric(CardCost($o->CardID))||intval(CardCost($o->CardID))!==0;}
function FaBELEArsenalSpace(int $p): bool {
    $refs=FaBChoiceRefs($p,'Arsenal');$capacity=1;
    if(FaBCRUEquipment($p,'new_horizon'))foreach($refs as $r)if(intval(FaBIdentityFromMZ($r)['object']->FaceDown??1)===0){$capacity=2;break;}
    return count($refs)<$capacity;
}
function FaBELEAsInstant(int $p,object $o): bool {
    return (FaBWTRBase($o->CardID)==='rejuvenate'&&FaBELECount($p,'FUSED'))||(FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o)&&(FaBELECount($p,'NEXT_INSTANT')||in_array('ELE_INSTANT',(array)($o->TurnEffects??[]),true)));
}
function FaBELECanPlay(int $p,array $f): bool {
    $o=$f['object'];if(!FaBELECanPitch($p,$o))return false;
    if(FaBWTRBase($o->CardID)==='lightning_press'&&FaBELECombatChoices('AA','',1)==='')return false;
    if(FaBWTRBase($o->CardID)==='summerwood_shelter'&&FaBELECombatChoices('DEFENSE','Earth|Elemental')==='')return false;
    if($o->CardID==='blizzard_blue'&&FaBFindUID(intval(FaBGetState()['attackUID']))===null)return false;
    if($o->CardID==='tome_of_harvests_blue'&&!FaBChoiceRefs($p,'Arsenal'))return false;
    return true;
}
function FaBELEPower(int $p,object $o): int {
    $n=0;$b=FaBWTRBase($o->CardID);$aa=FaBWTRIsAttackAction($o);
    if($aa){
        $n+=FaBPENPowerGain($o,3)*count(FaBMONArena($p,'channel_mount_heroic'));
        foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='ELE_AA_POWER')$n+=FaBPENPowerGain($o,intval($e['amount']??0));
    }
    foreach(FaBWTREffects($p) as $e)if(($e['type']??'')==='ELE_CHAIN_POWER')$n+=FaBPENPowerGain($o,intval($e['amount']??0));
    if($b==='stir_the_wildwood'&&FaBCRUArcane($p,true))$n+=2;
    if($b==='titans_fist')foreach(FaBChoiceRefs($p,'Pitch',['minCost'=>3]) as $r){++$n;break;}
    if($b==='duskblade'){$f=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??$o->UniqueID));if($f)$n+=intval(FaBObjectCounters($f['object'])['POWER']??0);}
    return $n;
}
function FaBELEDefense(int $p,object $o): int {
    $n=0;if(FaBHasType($o,'Action')&&!FaBWTRIsAttackAction($o))$n+=count(FaBMONArena($p,'embodiment_of_earth'));
    if(FaBWTRIsAttackAction($o))$n+=FaBELECount($p,'AA_DEFENSE');
    if(FaBHasType($o,'Action')&&FaBELEElement($o,'Earth|Ice|Elemental'))$n+=FaBELECount($p,'PULSE_DEFENSE');
    if(FaBWTRBase($o->CardID)==='sigil_of_suffering'&&FaBCRUArcane($p))++$n;return $n;
}
function FaBELEGoAgain(int $p,object $o): bool {
    $b=FaBWTRBase($o->CardID);if($b==='rites_of_lightning'&&FaBCRUArcane($p))return true;
    if($b==='boltn_shot'&&FaBAttackPower(FaBGetState())>intval(CardPower($o->CardID)))return true;
    return FaBWTRIsAttackAction($o)&&FaBELECount($p,'AA_GO')>0;
}
function FaBELEClear(int $p,string $key): void {FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='ELE_'.$key)));}
function FaBELEPlayed(int $p,object $o,string $from): void {
    if(!FaBWTRIsWeapon($o))foreach(FaBMONArena($p,'frostbite') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
    if(!FaBHasType($o,'Action')&&!FaBWTRIsWeapon($o))return;
    $b=FaBWTRBase($o->CardID);$uid=intval($o->UniqueID);$aa=FaBWTRIsAttackAction($o);$attack=$aa||FaBWTRIsWeapon($o);
    if(FaBHasType($o,'Action')){if(FaBELECount($p,'NEXT_ACTION_GO')){FaBWTRTag($o,'GO_AGAIN');FaBELEClear($p,'NEXT_ACTION_GO');}
        if(!$aa)FaBELEClear($p,'NEXT_INSTANT');
        foreach(FaBMONArena($p,'channel_thunder_steppe') as $r)FaBRunSourceMacro('ResolveAbility',$p,'channel_thunder_steppe_yellow',['mzID'=>$r,'eleActionUID'=>$uid]);
    }
    if($aa){foreach(FaBMONArena($p,'embodiment_of_lightning') as $r){FaBWTRTag($o,'GO_AGAIN');FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));}}
    if(FaBWTRHeroActive($p)&&in_array((GetHero($p)[0]->CardID??''),['briar','briar_warden_of_thorns'],true)&&!$aa&&FaBHasType($o,'Action')&&count(FaBARCPlayed($p,true))===2)FaBWTRCreateArena($p,'embodiment_of_lightning');
    $left=[];foreach(FaBWTREffects($p) as $e){$k=$e['type']??'';$use=false;$v=intval($e['amount']??0);
        if($attack&&$k==='ELE_NEXT_ATTACK')$use=true;
        if($aa&&$k==='ELE_NEXT_AA')$use=true;
        if($attack&&$k==='ELE_NEXT_ELEMENT'&&FaBELEElement($o,'Earth|Ice|Lightning|Elemental'))$use=true;
        if($attack&&$k==='ELE_NEXT_GUARDIAN'&&FaBHasType($o,'Guardian'))$use=true;
        if($attack&&$k==='ELE_NEXT_ARROW'&&FaBHasType($o,'Arrow'))$use=true;
        if($attack&&$k==='ELE_NEXT_FUSED'&&FaBELEFused($uid))$use=true;
        if($aa&&str_starts_with($k,'ELE_WEAVE_')&&FaBELEElement($o,substr($k,10).'|Elemental')){$use=true;if(FaBELEFused($uid)){if($k==='ELE_WEAVE_Earth')++$v;if($k==='ELE_WEAVE_Ice')FaBWTRTag($o,'DOMINATE');if($k==='ELE_WEAVE_Lightning')FaBWTRTag($o,'GO_AGAIN');}}
        if(!$use){$left[]=$e;continue;}if($v)FaBWTRTag($o,'WTR_POWER:'.$v);foreach($e['tags']??[] as $tag)FaBWTRTag($o,$tag);
    }FaBWTRSetEffects($p,$left);
    if($from==='Arsenal'){if($b==='burgeoning')FaBWTRTag($o,'WTR_POWER:1');if($b==='lightning_surge')FaBWTRTag($o,'GO_AGAIN');if($b==='evergreen')FaBWTRTag($o,'ELE_BOTTOM_CLOSE');}
    if($attack&&FaBELECount($p,'ENTANGLE')){FaBWTRTag($o,'WTR_POWER:-2');FaBELEClear($p,'ENTANGLE');}
}
function FaBELEDamageBonus(int $p,string $ref,int $n,string $type): int {
    $f=FaBIdentityFromMZ($ref);if(!$f||$n<=0)return $n;$o=$f['object'];if($type==='ARCANE'&&FaBSEAArcaneCapped(intval($o->UniqueID)))return $n;
    if(FaBHasType($o,'Action')&&FaBELEElement($o,'Lightning|Elemental'))foreach(FaBLiveSeats() as $seat)$n+=FaBELECount($seat,'BALL');
    if(FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o))$n+=FaBELECount($p,'FRAZZLE');
    if($type==='ARCANE'&&FaBHasType($o,'Action'))$n+=FaBELECount($p,'FLICKER')+FaBEVRCount($p,'WILDFIRE');return $n;
}
function FaBELEDamaged(int $p,int $target,int $n,string $ref): void {
    if($n<=0)return;FaBELEAdd($target,'DAMAGED',$n);$f=FaBIdentityFromMZ($ref);if(!$f)return;$o=$f['object'];$uid=intval($o->UniqueID);$b=FaBWTRBase($o->CardID);
    if(FaBWTRIsAttackAction($o)&&in_array($target,FaBOpponents($p),true)&&FaBWTRHeroActive($p)&&in_array((GetHero($p)[0]->CardID??''),['briar','briar_warden_of_thorns'],true)&&!FaBELECount($p,'BRIAR')){FaBELEAdd($p,'BRIAR');FaBWTRCreateArena($p,'embodiment_of_earth');}
    if($b==='explosive_growth'&&FaBELEFused($uid))FaBELEAdd($p,'CHAIN_POWER');
    if($b==='light_it_up'&&$n>=count(FaBChoiceRefs($target,'Equipment')))FaBWTRAddEffect($target,'ELE_NO_EQUIPMENT',1,['expiresAfterTurnOf'=>$target]);
    if(FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)){
        FaBELEFrost($target,FaBELECount($p,'BLIZZARD'));if(in_array('ELE_ICE_STORM',(array)$o->TurnEffects,true))FaBELEFrost($target,$n);
        if(FaBELECount($p,'ICEVEIN'))FaBRunSourceMacro('ResolveAbility',$p,'chilling_icevein_red',['mzID'=>$ref,'eleTarget'=>$target,'eleRepeats'=>FaBELECount($p,'ICEVEIN')]);
    }
    if($b==='blossoming_spellblade'&&FaBELEFused($uid)&&in_array($target,FaBOpponents($p),true))FaBRunSourceMacro('ResolveAbility',$p,$o->CardID,['mzID'=>$ref]);
}
function FaBELEStart(int $p): void {
    foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);
        if(in_array($b,['embodiment_of_earth','embolden','emerging_avalanche','strength_of_sequoia'],true)){
            if($b!=='embodiment_of_earth')FaBWTRAddEffect($p,$b==='embolden'?'NEXT_GUARDIAN':'NEXT_ATTACK',FaBWTRPitchValue($o->CardID,$b==='embolden'?[5,4,3]:[3,2,1]));
            FaBMONDestroy(intval($o->UniqueID));
        }
    }
}
function FaBELEEnd(int $p): void {
    if(intval(FaBGetState()['eleKorshemActiveTurn']??-1)!==intval(GetTurnNumber()))foreach(FaBLiveSeats() as $seat)foreach(FaBMONArena($seat,'korshem_crossroad_of_elements') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));
    foreach(FaBChoiceRefs($p,'Arena') as $r){$o=FaBIdentityFromMZ($r)['object'];$b=FaBWTRBase($o->CardID);
        if(in_array($b,['frostbite','sting_of_sorcery'],true))FaBMONDestroy(intval($o->UniqueID));
        if(str_starts_with($b,'channel_'))FaBRunSourceMacro('StartTurn',$p,$o->CardID,['mzID'=>$r]);
    }
    foreach(FaBCRUEquipment($p,'spellbound_creepers') as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBCRUArcane($p)<intval(FaBObjectCounters($o)['BIND']??0))FaBMONDestroy(intval($o->UniqueID));}
    foreach(FaBChoiceRefs($p,'Weapons',['base'=>'duskblade']) as $r)if(!FaBELEBothActions($p))FaBSetObjectCounter(FaBIdentityFromMZ($r)['object'],'POWER',0);
    if(FaBELECount($p,'SEEK_DESTROY')){FaBMoveChoices($p,implode('&',FaBChoiceRefs($p,'Hand')),'Hand','Graveyard');foreach(FaBChoiceRefs($p,'Arsenal') as $r)FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));}
}
function FaBELEBothActions(int $p): bool {return count(FaBARCPlayed($p,true))>0&&count(array_filter(FaBARCPlayed($p),fn($id)=>FaBWTRIsAttackAction((object)['CardID'=>$id])))>0;}
function FaBELERandomHand(int $p,int $n): array {
    $refs=FaBChoiceRefs($p,'Hand');$chosen=[];
    while($n-->0&&$refs){$i=EngineRandomInt(0,count($refs)-1);$chosen[]=$refs[$i];array_splice($refs,$i,1);}
    return FaBELEStage($p,implode('&',$chosen));
}
function FaBELECombatChoices(string $kind,string $elements='',int $max=999): string {
    $out=[];$s=FaBGetState();
    foreach(FaBLiveSeats() as $p)foreach(['CombatChain','Stack'] as $zone){if($zone==='Stack'&&$p!==FaBLiveSeats()[0])continue;
        foreach(FaBChoiceRefs($p,$zone) as $r){$f=FaBIdentityFromMZ($r);$o=$f['object'];
            if($zone==='CombatChain'&&intval($o->ChainLink)!==intval($s['chainLink']))continue;
            if($kind==='Attack'&&(($o->Role??'')!=='ATTACK'&&($o->Kind??'')!=='ATTACK'))continue;
            if($kind==='AA'&&((($o->Role??'')!=='ATTACK'&&($o->Kind??'')!=='ATTACK')||!FaBWTRIsAttackAction($o)))continue;
            if($kind==='DEFENSE'&&(!in_array($o->Role??'',['DEFENSE','DEFENSE_REACTION'],true)||!FaBHasType($o,'Action')))continue;
            if($kind==='Action'&&!FaBHasType($o,'Action'))continue;
            if($elements!==''&&!FaBELEElement($o,$elements))continue;
            if($max!==999&&(!is_numeric(CardCost($o->CardID))||intval(CardCost($o->CardID))>$max))continue;$out[]=$r;
        }
    }return implode('&',$out);
}
function FaBELEOtherAura(int $p,int $uid): bool {
    foreach(FaBChoiceRefs($p,'Arena',['type'=>'Aura']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->UniqueID)!==$uid&&!FaBHasType($o,'Token'))return true;}return false;
}
function FaBELEAwakening(int $p,int $uid,int $other=0): int {
    $difference=in_array($other,FaBOpponents($p),true)?max(0,intval(GetHealth($other))-intval(GetHealth($p))):0;
    $n=$difference*(FaBELEFused($uid)?2:1);for($i=0;$i<$n;++$i)FaBWTRCreateArena($p,'seismic_surge');return count(FaBMONArena($p,'seismic_surge'));
}
function FaBELEFlow(int $uid): int {
    $f=FaBFindUID($uid);if(!$f)return 0;$n=intval(FaBObjectCounters($f['object'])['FLOW']??0)+1;FaBSetObjectCounter($f['object'],'FLOW',$n);return $n;
}
function FaBELEChannelPay(int $p,int $uid,string $choices,int $n): void {
    $refs=array_values(array_filter(explode('&',$choices)));if(count($refs)!==$n){FaBMONDestroy($uid);return;}
    FaBMoveChoices($p,$choices,'Pitch','Deck');
}
function FaBELELexiReveal(int $p,int $uid,string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f)return;$f['object']->FaceDown=0;
    foreach(['Ice','Lightning'] as $e)FaBARCSetCard($uid,'eleAbility'.$e,FaBHasType($f['object'],$e));
    FaBRevealChoices($p,$ref);FaBARCArsenalFaceUp($p,$f['object']);
}
function FaBELESowChoices(int $p,int $min): string {return implode('&',array_filter(explode('&',FaBELESelect($p,'Graveyard','Earth|Elemental','Action')),function($r)use($min){$f=FaBIdentityFromMZ($r);return $f&&is_numeric(CardCost($f['object']->CardID))&&intval(CardCost($f['object']->CardID))>=$min;}));}
function FaBELESpellblade(int $p,string $ref): void {
    $o=FaBMoveChoice($p,$ref,'Graveyard','Banish');if(!$o)return;$o->PlayableFromBanish=1;FaBWTRTag($o,'ELE_INSTANT');FaBWTRTag($o,'ELE_BANISH_REPLACE');
}
function FaBELEEquipmentTargets(int $p,bool $zero=false): string {
    $out=[];foreach($zero?[$p]:FaBLiveSeats() as $seat)foreach(['Equipment','CombatChain'] as $z)foreach(FaBChoiceRefs($seat,$z,['type'=>'Equipment']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(!$zero||FaBCurrentDefense($o,$seat)===0)$out[]=$r;}return implode('&',$out);
}
function FaBELEKorshemReveal(int $p): void {
    foreach(FaBLiveSeats() as $seat)foreach(FaBMONArena($seat,'korshem_crossroad_of_elements') as $r)FaBRunSourceMacro('ResolveAbility',$p,'korshem_crossroad_of_elements',['mzID'=>$r]);
}
function FaBELEKorshemBonus(int $p,string $mode): void {
    if($mode==='0')AddResources($p,intval(GetResources($p))+1);
    if($mode==='1')FaBCRUGainLife($p,1);
    if($mode==='2')FaBWTRAddEffect($p,'NEXT_ATTACK',1);
    if($mode==='3')FaBELEAdd($p,'NEXT_DEFENSE');
    FaBELEAdd($p,'KORSHEM_ACTIVE');
}
function FaBELEClose(): void {foreach(FaBLiveSeats() as $p){FaBELEClear($p,'CHAIN_POWER');FaBELEClear($p,'BALL');}}
function FaBELEKorshemActivity(): void {$s=FaBGetState();$s['eleKorshemActiveTurn']=intval(GetTurnNumber());FaBSetState($s);}
function FaBELEBeforeResources($p,$value,$source=null): bool {if($source!=='PITCH'&&intval($value)>intval(GetResources($p)))FaBELEKorshemActivity();return true;}
function FaBELEBeforeHealth($p,$value,$source=null): bool {if(intval($value)>intval(GetHealth($p)))FaBELEKorshemActivity();return true;}
function FaBELELandmark(int $uid): void {foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'Arena',['type'=>'Landmark']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->UniqueID)!==$uid)FaBMONDestroy(intval($o->UniqueID));}}

function FaBELEStage(int $p,string $refs): array {
    $uids=[];foreach(explode('&',$refs) as $r){$f=FaBIdentityFromMZ($r);if($f&&$f['player']===$p&&in_array($f['zone'],['Hand','Graveyard'],true))$uids[]=intval($f['object']->UniqueID);}
    foreach($uids as $uid)FaBMoveUID($uid,'Temp',$p);return $uids;
}

function FaBELEFlowCounters($o): int {return intval(FaBObjectCounters($o)['FLOW']??0);}
function FaBELEBindCounters($o): int {return intval(FaBObjectCounters($o)['BIND']??0);}

function FaBELEAwakeningHeroes(int $p): string {
    $refs=[];foreach(FaBOpponents($p) as $other)if(FaBPENLifeMore($other,$p))$refs=array_merge($refs,FaBChoiceRefs($other,'Hero'));return implode('&',$refs);
}
