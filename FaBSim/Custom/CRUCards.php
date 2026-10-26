<?php

// Crucible of War: shared continuous effects and event hooks. Interactive card
// decisions live in the CardEditor snapshot, using serializable UIDs across awaits.
function FaBCRUEquipment(int $p,string $id): array {
    $refs=FaBChoiceRefs($p,'Equipment',['base'=>$id]);
    foreach(FaBChoiceRefs($p,'CombatChain',['base'=>$id]) as $ref)if((FaBIdentityFromMZ($ref)['object']->FromZone??'')==='Equipment')$refs[]=$ref;
    return array_values(array_filter($refs,fn($ref)=>!HasNoAbilities(FaBIdentityFromMZ($ref)['object'])));
}
function FaBCRUHero(int $p, string $id): bool {
    return FaBWTRHeroActive($p) && count(FaBChoiceRefs($p,'Hero',['base'=>$id])) > 0;
}
function FaBCRUArcane(int $p, bool $opposing=false): int {
    $n=0; foreach(FaBGetState()['arcaneDealt'][(string)$p]??[] as $seat=>$amount) if(!$opposing||intval($seat)!==$p)$n+=intval($amount); return $n;
}
function FaBCRUWizardPlayed(int $p, string $exclude=''): bool {
    $cards=FaBARCPlayed($p,true);if($exclude!==''){ $index=array_search($exclude,$cards,true);if($index!==false)unset($cards[$index]); }
    foreach($cards as $id)if(FaBHasType($id,'Wizard'))return true; return false;
}
function FaBCRUCount(int $p,string $kind): int { return FaBARCEffect($p,'CRU_'.$kind); }
function FaBCRUAdd(int $p,string $kind,int $n=1): void { FaBWTRAddEffect($p,'CRU_'.$kind,$n); }
function FaBCRUSelfTag(object $o,string $tag): void {
    FaBWTRTag($o,$tag);
    if(str_starts_with($tag,'WTR_POWER:')&&intval(substr($tag,10))>0)FaBWTRTag($o,'CRU_SUPPRESS_POWER:'.intval(substr($tag,10)));
}
function FaBCRUSelfTagUID(int $uid,string $tag): void { $f=FaBFindUID($uid);if($f)FaBCRUSelfTag($f['object'],$tag); }
function FaBCRUGainLife(int $p,int $n): void {
    if(!FaBSeatIsLive($p)||$n<=0||(FaBMONCount($p,'NO_HEAL')&&$p===intval(GetTurnPlayer())&&GetCurrentPhase()==='MAIN'))return;
    foreach(FaBLiveSeats() as $v)if(FaBSEACount($v,'NO_HEAL')||FaBMPGAuras($v,'parched_terrain'))return;
    $highest=true;foreach(FaBOpponents($p) as $seat)if(!FaBPENLifeMore($p,$seat))$highest=false;
    if($highest)foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'Weapons',['base'=>'reaping_blade']) as $ref)if(!HasNoAbilities(FaBIdentityFromMZ($ref)['object']))return;
    foreach(FaBLiveSeats() as $seat)if(FaBDTDCount($seat,'POISON')){FaBDTDClear($seat,'POISON');FaBARCLoseLife($p,$n,$seat);return;}
    if(FaBPENReplaceGain($p,$n))return;
    AddHealth($p,intval(GetHealth($p))+$n);FaBROSLife($p,$n);
}
function FaBCRUKavdaen(): void {
    $seats=FaBLiveSeats();if(count($seats)<2)return;
    $life=array_map(fn($p)=>intval(GetHealth($p)),$seats);$max=max($life);
    if(count(array_filter($life,fn($h)=>$h===$max))===1){$p=$seats[array_search($max,$life,true)];FaBARCLoseLife($p,1,$p);if(FaBSeatIsLive($p))FaBWTRCreateArena($p,'copper');}
    $seats=FaBLiveSeats();$life=array_map(fn($p)=>intval(GetHealth($p)),$seats);if(count($life)<2)return;$min=min($life);
    if(count(array_filter($life,fn($h)=>$h===$min))===1)FaBCRUGainLife($seats[array_search($min,$life,true)],1);
}
function FaBCRUCoax(string $choice): void {
    $modes=explode(',',$choice);$seats=FaBLiveSeats();$blocked=0;$blade=false;
    foreach($seats as $p)if(FaBChoiceRefs($p,'Weapons',['base'=>'reaping_blade']))$blade=true;
    if($blade){$life=array_map(fn($p)=>intval(GetHealth($p)),$seats);$max=max($life);if(count(array_filter($life,fn($h)=>$h===$max))===1)$blocked=$seats[array_search($max,$life,true)];}
    // All heroes gain life simultaneously; earlier seats gaining life must not
    // change whether Reaping Blade prevents a later seat's gain.
    foreach($seats as $p){
        if(in_array('0',$modes,true))FaBWTRCreateArena($p,'quicken');
        if(in_array('1',$modes,true))DoDrawCard($p,1);
        if(in_array('2',$modes,true)&&$p!==$blocked)AddHealth($p,intval(GetHealth($p))+1);
    }
}
function FaBCRUAfterMove(int $p,object $o,string $from,string $to): void {
    // Physical Saber identity changes only when it crosses the arena boundary.
    // Attack proxies carry WEAPON_UID and must not acquire weapon-instance metadata.
    if($o->CardID==='cintari_saber'&&intval(FaBObjectCounters($o)['WEAPON_UID']??0)===0){
        $arena=['Arena','Equipment','Weapons','Hero','CombatChain'];
        if(in_array($from,$arena,true)!==in_array($to,$arena,true)){
            $uid=intval($o->UniqueID);FaBARCSetCard($uid,'cruSaberArenaEpoch',intval(FaBARCCard($uid,'cruSaberArenaEpoch'))+1);
        }
    }
    if($from==='Deck'&&$to==='Banish'&&FaBCRUHero($p,'data_doll_mkii')&&FaBHasType($o,'Mechanologist')&&FaBHasType($o,'Item')&&intval(CardCost($o->CardID))<=2){
        $item=FaBMoveUID(intval($o->UniqueID),'Arena',$p);if($item)FaBARCEnterItem($p,$item);
    }
    if($to==='Graveyard'&&$from!=='CombatChain'&&$o->CardID==='beast_within_yellow'){
        while(FaBSeatIsLive($p)){
            $ref=FaBChoiceRefs($p,'Deck')[0]??'';$f=FaBIdentityFromMZ($ref);if(!$f){FaBARCLoseLife($p,1,$p);continue;}
            $uid=intval($f['object']->UniqueID);$six=intval(CardPower($f['object']->CardID))>=6;
            FaBMoveUID($uid,'Banish',$p);FaBARCLoseLife($p,1,$p);
            if($six){if(FaBSeatIsLive($p))FaBMoveUID($uid,'Hand',$p);break;}
        }
    }
}
function FaBCRUItemEntered(int $p,object $o): void {
    if($o->CardID==='absorption_dome_yellow'){
        $n=FaBARCEffect($p,'ARC_BOOSTED');FaBSetObjectCounter($o,'STEAM',$n);if(!$n)FaBMoveUID(intval($o->UniqueID),'Graveyard',$p);
    }
}
function FaBCRUBoost(int $p,int $uid): void {
    FaBCRUAdd($p,'CHAIN_BOOST');FaBARCSetCard($uid,'boosted',true);
    $left=[];foreach(FaBWTREffects($p) as $e){
        if(($e['type']??'')==='CRU_COURIER')FaBTagUID($uid,'WTR_POWER:'.intval($e['amount']));
        elseif(($e['type']??'')==='CRU_IMPACT')FaBTagUID($uid,'DOMINATE');
        else $left[]=$e;
    }FaBWTRSetEffects($p,$left);
    if(FaBCRUCount($p,'VIZIER'))FaBRunSourceMacro('CardPlayed',$p,'viziertronic_model_i',['mzID'=>'','cardID'=>'','fromZone'=>'']);
}
function FaBCRUAsInstant(int $p,object $o): bool {
    $b=FaBWTRBase($o->CardID);
    return ($b==='cindering_foresight'&&intval(GetTurnPlayer())!==$p)||($b==='snapback'&&FaBCRUWizardPlayed($p))||($b==='rattle_bones'&&FaBCRUArcane($p,true)>0);
}
function FaBCRUCanPlay(int $p,array $f): bool {
    $o=$f['object'];$b=FaBWTRBase($o->CardID);$s=FaBGetState();
    if($b==='feign_death'&&!FaBCRUCount($p,'DAMAGED'))return false;
    if(FaBHasType($o,'Trap')&&$f['zone']!=='Arsenal')return false;
    if(FaBCRUCount($p,'CRUSH_WEAK')&&GetCurrentPhase()==='MAIN'&&intval(GetTurnPlayer())===$p&&FaBWTRIsAttackAction($o)&&intval(CardPower($o->CardID))<=3)return false;
    if(FaBHasType($o,'Defense Reaction')&&!FaBCRUBlockLegal($p,$f))return false;
    if($b==='aetherize'&&FaBCRUTargets($p,'negate')==='')return false;
    if($b==='reinforce_the_line'&&FaBCRUTargets($p,'defendingAA')==='')return false;
    if($b==='rattle_bones'&&FaBARCSelect($p,'Graveyard','Runeblade','AA')==='')return false;
    return true;
}
function FaBCRUTargets(int $p,string $kind,int $target=0): string {
    $refs=[];$s=FaBGetState();
    $near=array_merge([$p],FaBAdjacentOpponents($p));
    if($kind==='negate')foreach(GetStack() as $o)if(is_object($o)&&empty($o->removed)&&in_array(intval($o->Controller),$near,true)&&$o->Kind==='INSTANT'&&FaBHasType($o,'Instant')&&intval(CardCost($o->CardID))<=1)$refs[]='Stack-'.$o->mzIndex;
    foreach(FaBLiveSeats() as $seat){
        if($kind==='hero'&&$seat!==$p&&in_array($seat,$near,true))$refs=array_merge($refs,FaBChoiceRefs($seat,'Hero'));
        if($kind==='items')foreach(FaBChoiceRefs($seat,'Arena') as $ref)if(FaBHasType(FaBIdentityFromMZ($ref)['object'],'Item'))$refs[]=$ref;
        if($kind==='defendingAA'&&in_array($seat,$near,true))foreach(FaBChoiceRefs($seat,'CombatChain') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&FaBWTRIsAttackAction($o))$refs[]=$ref;}
        if($kind==='damagedEquipment'&&$seat===$target)foreach(['Equipment','CombatChain'] as $zone)foreach(FaBChoiceRefs($seat,$zone) as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(FaBHasType($o,'Equipment')&&intval(FaBObjectCounters($o)['DEFENSE']??0)<0)$refs[]=$ref;}
    }return implode('&',$refs);
}
function FaBCRUBlockLegal(int $p,array $f): bool {
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a||empty($s['combatOpen']))return true;
    $o=$f['object'];$attack=$a['object'];$b=FaBWTRBase($attack->CardID);$tags=(array)$attack->TurnEffects;$aa=FaBWTRIsAttackAction($o);
    if($f['zone']==='Hand'&&FaBCRUHero(intval($s['attacker']),'benji_the_piercing_wind')&&FaBWTRIsAttackAction($attack)&&FaBAttackPower($s)<=2)return false;
    if(in_array('CRU_CRANE',$tags,true)&&$aa&&intval(CardPower($o->CardID))>intval($s['chainLink']))return false;
    if(in_array('CRU_CENTER',$tags,true)&&(!is_numeric(CardCost($o->CardID))||intval(CardCost($o->CardID))<intval($s['chainLink'])))return false;
    if(in_array('CRU_HERON_AA',$tags,true)&&!$aa)return false;
    if(in_array('CRU_HERON_NAA',$tags,true)&&(!FaBHasType($o,'Action')||$aa))return false;
    if(FaBHasType($o,'Defense Reaction')&&(($f['zone']==='Hand'&&in_array('CRU_NO_HAND_DR',$tags,true))||($f['zone']==='Arsenal'&&in_array('CRU_NO_ARSENAL_DR',$tags,true))))return false;
    return true;
}
function FaBCRUMustEquip(int $p): bool {
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a||!(FaBWTRBase($a['object']->CardID)==='palantir_aeronought'||$a['object']->CardID==='meganetic_shockwave_blue'||(FaBWTRBase($a['object']->CardID)==='t_bone'&&FaBCRUCount(intval($s['attacker']),'CHAIN_BOOST')>0)))return false;
    $n=0;foreach(GetCombatChain($p) as $o)if(is_object($o)&&empty($o->removed)&&intval($o->ChainLink)===intval($s['chainLink'])&&($o->FromZone??'')==='Equipment')++$n;
    if($n>=($a['object']->CardID==='meganetic_shockwave_blue'?FaBCRUCount(intval($s['attacker']),'CHAIN_BOOST'):1))return false;
    foreach(FaBChoiceRefs($p,'Equipment') as $ref)if(FaBCanBlock($p,$ref))return true;return false;
}
function FaBCRUCost(int $p,object $o): int {
    $aa=FaBWTRIsAttackAction($o);$n=0;
    if(FaBHasType($o,'Action')&&FaBCRUCount($p,$aa?'SKELETA_AA':'SKELETA_NAA'))$n-=FaBARCRunechants($p);
    if(FaBHasType($o,'Defense Reaction'))$n+=FaBCRUCount($p,'DAUNTLESS');
    return $n;
}
function FaBCRUWeaponCost(int $p,string $id): int {
    return FaBHasType($id,'Sword')? -FaBCRUCount($p,'COURAGE')-(FaBCRUHero($p,'kassai_cintari_sellsword')&&FaBCRUCount($p,'SWORDS')===1?1:0):0;
}
function FaBCRUCardPlayed(int $p,object $o,string $from): void {
    $aa=FaBWTRIsAttackAction($o);$b=FaBWTRBase($o->CardID);$uid=intval($o->UniqueID);$left=[];
    foreach(FaBWTREffects($p) as $e){$t=$e['type']??'';
        if(($t==='CRU_SKELETA_AA'&&$aa)||($t==='CRU_SKELETA_NAA'&&FaBHasType($o,'Action')&&!$aa)||($t==='CRU_DAUNTLESS'&&FaBHasType($o,'Defense Reaction')))continue;
        if($t==='CRU_MAUVRION'&&$aa&&FaBHasType($o,'Runeblade')){FaBWTRTag($o,'GO_AGAIN');FaBWTRTag($o,'CRU_RUNE_HIT:'.intval($e['amount']));continue;}
        if($t==='CRU_TENSION'&&FaBHasType($o,'Arrow')){FaBWTRTag($o,'WTR_POWER:'.intval($e['amount']));FaBWTRTag($o,'CRU_NO_HAND_DR');continue;}
        $left[]=$e;
    }FaBWTRSetEffects($p,$left);
    if(FaBHasType($o,'Action')&&FaBCRUCount($p,'REMORSELESS'))FaBARCLoseLife($p,FaBCRUCount($p,'REMORSELESS'),$p);
    if($b==='promise_of_plenty'&&$from==='Arsenal')FaBWTRTag($o,'GO_AGAIN');
    if(FaBWTRIsWeapon($o)||$aa){FaBCRUAdd($p,'ATTACKS');if(FaBCRUHero($p,'ira_crimson_haze')&&FaBCRUCount($p,'ATTACKS')===2)FaBWTRTag($o,'WTR_POWER:1');}
    if(FaBWTRIsWeapon($o)){FaBCRUAdd($p,'WEAPONS');if(FaBHasType($o,'Sword'))FaBCRUAdd($p,'SWORDS');}
    if($aa&&FaBHasKeyword($o,'Combo')&&FaBCRUCount($p,'BREEZE'))FaBWTRTag($o,'GO_AGAIN');
    if(FaBHasType($o,'Arrow')&&$from==='Arsenal'&&FaBARCCard($uid,'faceUp')&&FaBCRUCount($p,'PERCH'))FaBWTRTag($o,'GO_AGAIN');
    if($b==='springboard_somersault'&&$from==='Arsenal')FaBWTRTag($o,'WTR_DEFENSE:2');
    if($aa&&intval(CardPower($o->CardID))>=6&&FaBCRUHero($p,'kayo_berserker_runt'))FaBRunSourceMacro('CardPlayed',$p,'kayo_berserker_runt',['mzID'=>FaBFindUID($uid)['mzID'],'cardID'=>$o->CardID,'fromZone'=>$from]);
    if(FaBARCIsArcaneCard($o->CardID))foreach(FaBCRUEquipment($p,'metacarpus_node') as $ref)FaBRunSourceMacro('CardPlayed',$p,'metacarpus_node',['mzID'=>FaBFindUID($uid)['mzID'],'cardID'=>$o->CardID,'fromZone'=>$from]);
}
function FaBCRUAttack(int $p,object $o): void {
    $b=FaBWTRBase($o->CardID);$s=FaBGetState();
    $tax=FaBCRUCount($p,'DAUNTLESS_PENDING');if($tax){foreach($s['attackTargets']??[] as $target)if(($target['type']??'HERO')==='HERO')FaBCRUAdd(intval($target['player']),'DAUNTLESS',$tax);FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!=='CRU_DAUNTLESS_PENDING')));}
    $prev=FaBWTRBase($s['previousAttackCardID']);
    if($b==='crane_dance'&&$prev==='soulbead_strike'){FaBCRUSelfTag($o,'WTR_POWER:1');FaBWTRTag($o,'GO_AGAIN');FaBWTRTag($o,'CRU_CRANE');}
    if($b==='find_center'&&$prev==='crane_dance')FaBWTRTag($o,'CRU_CENTER');
    if($b==='rushing_river'&&$prev==='torrent_of_tempo'){FaBCRUSelfTag($o,'WTR_POWER:1');FaBWTRTag($o,'GO_AGAIN');FaBWTRTag($o,'CRU_RIVER');}
    if($b==='flying_kick'&&intval($s['chainLink'])>=3)FaBCRUSelfTag($o,'WTR_POWER:2');
    if(FaBCRUCount($p,'DISCARD_SIX')){
        if($b==='riled_up')FaBCRUSelfTag($o,'WTR_POWER:1');
        if($b==='predatory_assault')FaBWTRTag($o,'DOMINATE');
        if($b==='massacre'){FaBCRUSelfTag($o,'WTR_POWER:2');FaBRequestIntimidate($p);}
    }
    if($b==='consuming_volition'&&FaBCRUArcane($p)>0)FaBWTRTag($o,'CRU_DISCARD_HIT');
    if(FaBHasType($o,'Arrow')&&FaBCRUCount($p,'POISON'))FaBWTRTag($o,'CRU_DISCARD_HIT');
}
function FaBCRUGoAgain(int $p,object $o,array $s): bool {
    $b=FaBWTRBase($o->CardID);
    return (FaBWTRIsAttackAction($o)&&FaBHasKeyword($o,'Combo')&&FaBCRUCount($p,'BREEZE'))||in_array($b,['edge_of_autumn','zephyr_needle'],true)||($b==='mandible_claw'&&FaBCRUCount($p,'DISCARD_SIX'))||($b==='barraging_big_horn'&&FaBWTRNonEquipmentBlockCount($s)<2)||($b==='meat_and_greet'&&FaBCRUArcane($p,true)>0);
}
function FaBCRUBasePower(object $o,int $base): int {
    $roll=intval(FaBARCCard(intval($o->UniqueID),'kayo'));return $roll?($roll<=4?intdiv($base,2):$base*2):$base;
}
function FaBCRUPower(int $p,object $o): int {
    $b=FaBWTRBase($o->CardID);$delta=0;foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'CRU_REACTION_POWER:')&&(!FaBWTRIsAttackAction($o)||!FaBCRUCount($p,'SNAG')))$delta+=intval(substr($tag,19));
    if(FaBHasKeyword($o,'Crush'))$delta+=2*FaBCRUCount($p,'CRATER');
    $snag=FaBWTRIsAttackAction($o)&&FaBCRUCount($p,'SNAG');
    if($snag)foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'CRU_SUPPRESS_POWER:'))$delta-=intval(substr($tag,19));
    if($snag)$delta+=min(FaBCRUOwnPower($p,$o),intval(FaBARCCard(intval($o->UniqueID),'snagRetained')));
    return $delta+($b==='overblast'&&!$snag?FaBCRUCount($p,'CHAIN_BOOST'):($b==='plasma_barrel_shot'?1+FaBCRUCount($p,'CHAIN_BOOST'):0));
}
function FaBCRUDefended(int $p,object $block): void {
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a)return;$o=$a['object'];$uid=intval($o->UniqueID);
    $weapon=FaBFindUID(intval(FaBObjectCounters($o)['WEAPON_UID']??0));
    if($o->CardID==='cintari_saber'&&FaBWTRIsAttackAction($block)&&$weapon
        &&$weapon['object']->CardID==='cintari_saber'
        &&in_array($weapon['zone'],['Arena','Equipment','Weapons','Hero','CombatChain'],true)
        &&intval(FaBObjectCounters($weapon['object'])['WEAPON_UID']??0)===0
        &&!HasNoAbilities($weapon['object'])&&!FaBARCCard($uid,'saber')){
        // OnDefended runs after the complete declaration group. Several AAs still trigger once.
        FaBARCSetCard($uid,'saber',true);
        $weaponUID=intval($weapon['object']->UniqueID);
        $controller=intval($weapon['object']->Controller??0)?:intval($weapon['player']);
        FaBROSQueue($controller,'cintari_saber',$weaponUID,['rosEvent'=>'cruCintariDefended',
            'cruSaberAttackUID'=>$uid,'cruSaberArenaEpoch'=>intval(FaBARCCard($weaponUID,'cruSaberArenaEpoch'))]);
    }
    if($o->CardID==='zephyr_needle'&&FaBCurrentDefense($block,$p)>FaBAttackPower($s)&&$weapon)FaBWTRTag($weapon['object'],'CRU_BREAK_CHAIN');
}
function FaBCRUResolveCintariDefended(int $p,object $trigger): void {
    $uid=intval($trigger->SourceUniqueID??0);$source=FaBFindUID($uid);
    if(!$source||$source['object']->CardID!=='cintari_saber'
        ||!in_array($source['zone'],['Arena','Equipment','Weapons','Hero','CombatChain'],true)
        ||intval(FaBObjectCounters($source['object'])['WEAPON_UID']??0)!==0
        ||intval(FaBARCCard($uid,'cruSaberArenaEpoch'))!==intval($trigger->Params['cruSaberArenaEpoch']??-1))return;
    // The triggered ability is independent of later suppression, control, or attack departure.
    // Tag only the physical weapon: WTRAttackPowerModifier already reads its live tags.
    FaBWTRTag($source['object'],'WTR_POWER:1');
}
function FaBCRUHitSuppressed(object $o,bool $all=false): bool {
    if(!$all&&FaBMPGSuppressed($o))return true;
    foreach(FaBMSTAttackVictims() as $v)if(FaBMSTCount($v,'NO_HIT'))return true;
    if(in_array('CRU_NO_HIT',(array)$o->TurnEffects,true))return true;
    if(!$all&&FaBWTRIsAttackAction($o))foreach(FaBLiveSeats() as $p)if(FaBChoiceRefs($p,'Arena',['base'=>'stamp_authority']))return true;
    return false;
}
function FaBCRUHit(int $p,object $o,int $amount): void {
    if(FaBWTRIsAttackAction($o)&&FaBCRUHero($p,'benji_the_piercing_wind')&&intval(FaBGetState()['attackActionHits'][(string)$p]??0)===1)FaBWTRAddEffect($p,'NEXT_ATTACK',1);
    if(FaBWTRIsWeapon($o))FaBCRUAdd($p,'WEAPON_HITS');
    if(FaBWTRIsWeapon($o)&&FaBCRUCount($p,'SPOILS'))for($i=0;$i<2*FaBCRUCount($p,'SPOILS');++$i)FaBWTRCreateArena($p,'copper');
    // Each granted hit ability is its own layer; creation modifiers apply on resolution.
    if(!HasNoAbilities($o)&&!FaBCRUHitSuppressed($o))foreach((array)$o->TurnEffects as $tag){
        if(!str_starts_with($tag,'CRU_RUNE_HIT:'))continue;
        $r=intval(substr($tag,13));
        if($r>0)FaBROSQueue($p,$o->CardID,intval($o->UniqueID),['rosEvent'=>'cruMauvrionRuneHit','runeCount'=>$r]);
    }
    if(!FaBCRUHitSuppressed($o)&&FaBWTRBase($o->CardID)!=='consuming_volition'&&in_array('CRU_DISCARD_HIT',(array)$o->TurnEffects,true)&&FaBFaiHeroHit())FaBRunSourceMacro('Hit',$p,'consuming_volition_red',['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID'],'amount'=>$amount]);
    if(FaBWTRIsAttackAction($o)&&FaBHasType($o,'Ninja'))foreach(FaBCRUEquipment($p,'breeze_rider_boots') as $ref)FaBRunSourceMacro('Hit',$p,'breeze_rider_boots',['mzID'=>$ref,'amount'=>$amount]);
}
function FaBCRUPrevent(int $p,int $amount,string $type): int {
    if($amount<=0)return $amount;
    $left=[];foreach(FaBWTREffects($p) as $e){$t=$e['type']??'';
        if($t==='CRU_FEIGN'&&$amount>0)$amount=0;
        elseif($t==='CRU_SERENITY'&&$type==='PHYSICAL'&&$amount>0)$amount=max(0,$amount-intval($e['amount']));
        else $left[]=$e;
    }FaBWTRSetEffects($p,$left);
    $amount=max(0,$amount-count(FaBChoiceRefs($p,'Arena',['base'=>'zen_state'])));
    foreach(FaBChoiceRefs($p,'Arena',['base'=>'absorption_dome']) as $ref){$o=FaBIdentityFromMZ($ref)['object'];$used=min($amount,intval(FaBObjectCounters($o)['STEAM']??0));$amount-=$used;FaBARCSteam($o,-$used);}
    if($amount>0&&FaBChoiceRefs($p,'Arena',['base'=>'runeblood_barrier']))foreach(array_slice(FaBChoiceRefs($p,'Arena',['base'=>'runechant']),0,$amount) as $ref){FaBMoveChoice($p,$ref,'Arena','Graveyard');--$amount;}
    return $amount;
}
function FaBCRUStart(int $p): void {
    // Shiyana's copy lasts across opponents' turns and expires at her next turn.
    foreach(FaBChoiceRefs($p,'Hero') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(FaBObjectCounters($o)['CRU_SHIYANA']??false)$o->CardID='shiyana_diamond_gemini';}
    if(FaBWTRHeroActive($p))foreach(FaBChoiceRefs($p,'Hero',['base'=>'shiyana_diamond_gemini']) as $ref)FaBRunSourceMacro('StartTurn',$p,'shiyana_diamond_gemini',['mzID'=>$ref]);
    foreach(FaBChoiceRefs($p,'Arena') as $ref){$o=FaBIdentityFromMZ($ref)['object'];$b=FaBWTRBase($o->CardID);
        if(in_array($b,['emerging_dominance','towering_titan'],true)){FaBWTRAddEffect($p,'NEXT_GUARDIAN',FaBWTRPitchValue($o->CardID,$b==='towering_titan'?[10,9,8]:[3,2,1]),['dominate'=>$b==='emerging_dominance']);FaBMoveUID(intval($o->UniqueID),'Graveyard',$p);}
        if(in_array($b,['runeblood_barrier','stamp_authority'],true))FaBMoveUID(intval($o->UniqueID),'Graveyard',$p);
        if($b==='zen_state'){if(intval(FaBObjectCounters($o)['BALANCE']??0)>0)FaBSetObjectCounter($o,'BALANCE',0);else FaBMoveUID(intval($o->UniqueID),'Graveyard',$p);}
    }
}
function FaBCRUEnd(int $p): void {
    if(!FaBSEAHeroCreationBlocked($p)&&FaBCRUHero($p,'kassai_cintari_sellsword')&&FaBCRUCount($p,'WEAPONS')>=2)for($i=0;$i<FaBCRUCount($p,'WEAPON_HITS');++$i)FaBWTRCreateArena($p,'copper');
    foreach(FaBLiveSeats() as $seat)foreach(['Weapons','Equipment','CombatChain'] as $zone)foreach(FaBChoiceRefs($seat,$zone) as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(($seat===$p&&$o->CardID==='talishar_the_lost_prince'&&intval(FaBObjectCounters($o)['RUST']??0)>=3)||($o->CardID==='metacarpus_node'&&in_array('CRU_NODE_USED',(array)$o->TurnEffects,true)))FaBMoveUID(intval($o->UniqueID),'Graveyard',$seat);}
}
function FaBCRUClose(): void {
    foreach(FaBLiveSeats() as $p){
        FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>!in_array($e['type']??'',['CRU_CHAIN_BOOST','CRU_COURIER','CRU_IMPACT','IRA_CHAIN_TIGER'],true))));
        foreach(FaBChoiceRefs($p,'Weapons') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(in_array('CRU_BREAK_CHAIN',(array)$o->TurnEffects,true))FaBMoveUID(intval($o->UniqueID),'Graveyard',$p);}
        foreach(FaBChoiceRefs($p,'Banish') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(FaBARCCard(intval($o->UniqueID),'untilChainCloses'))$o->PlayableFromBanish=0;}
    }
}

function FaBCRUCashOptions(int $p): string {
    $opts=['Pay_resources'];foreach(['copper'=>4,'silver'=>2,'gold'=>1] as $id=>$n)if(count(FaBChoiceRefs($p,'Arena',['base'=>$id]))>=$n)$opts[]='Destroy_'.$n.'_'.$id;return implode('&',$opts);
}
function FaBCRUCashPay(int $p,int $uid,string $options,string $choice): void {
    $selected=explode('&',$options)[intval($choice)]??'';
    if(preg_match('/^Destroy_(\d+)_(copper|silver|gold)$/',$selected,$m)){
        $refs=FaBChoiceRefs($p,'Arena',['base'=>$m[2]]);if(count($refs)<intval($m[1]))return;
        foreach(array_slice($refs,0,intval($m[1])) as $ref)FaBMoveChoice($p,$ref,'Arena','Graveyard');FaBARCSetPendingCost($uid,0);
    }
}
function FaBCRUCopyHero(int $p,string $chosen): void {
    $f=FaBIdentityFromMZ($chosen);if(!$f||$f['zone']!=='Hero'||$f['player']===$p)return;
    foreach(FaBChoiceRefs($p,'Hero') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if($o->CardID==='shiyana_diamond_gemini'){
        FaBSetObjectCounter($o,'CRU_SHIYANA',1);$o->CardID=$f['object']->CardID;
    }}
}
function FaBCRUStageOther(int $p,int $target,int $n): array {
    $uids=[];foreach(array_slice(FaBChoiceRefs($target,'Deck'),0,$n) as $ref){$uid=intval(FaBIdentityFromMZ($ref)['object']->UniqueID);FaBMoveUID($uid,'Temp',$p,false);$uids[]=$uid;}return $uids;
}
function FaBCRURestoreOther(int $p,int $target,array $uids,string $order): void {
    // Reorder in the chooser's private zone, then transfer only the inspected UIDs
    // to their owner's deck. No opponent sees the looked-at cards.
    $byID=[];foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f&&$f['zone']==='Temp'&&$f['player']===$p)$byID[$f['object']->CardID][]=$uid;}
    $ordered=[];foreach(explode(',',substr($order,4)) as $id)if(!empty($byID[$id]))$ordered[]=array_shift($byID[$id]);foreach($byID as $remaining)$ordered=array_merge($ordered,$remaining);
    foreach(array_reverse($ordered) as $uid)FaBARCToDeck($target,intval($uid),true);
}

function FaBCRULegacyRoll(int $p,string $id,int $roll): void {
    if($roll===6)FaBDTDAdd($p,'ROLL_SIX');
    if($id==='scabskin_leathers')AddActionPoints($p,intval(GetActionPoints($p))+intdiv($roll,2));
    elseif($id==='barkbone_strapping')AddResources($p,intval(GetResources($p))+intdiv($roll,2));
    elseif($id==='bone_head_barrier_yellow')FaBWTRAddEffect($p,'PREVENT_DAMAGE',$roll);
    elseif($id==='crazy_brew_blue'){
        if($roll<=2)FaBARCLoseLife($p,2,$p);
        elseif($roll<=4)FaBCRUGainLife($p,2);
        else {AddResources($p,intval(GetResources($p))+2);AddActionPoints($p,intval(GetActionPoints($p))+2);FaBWTRAddEffect($p,'NEXT_ATTACK',2);}
        if($roll<=4&&FaBWTRMayGoAgain($p))AddActionPoints($p,intval(GetActionPoints($p))+1);
    }
}

function FaBCRUWeaponReady(object $w): bool { if($w->CardID==='bank_breaker')return FaBAMXReady($w)||in_array('CRU_EXTRA_ATTACK',(array)$w->TurnEffects,true); return (intval(FaBObjectCounters($w)['EVR_EXTRA_ATTACK_TURN']??-1)===intval(GetTurnNumber())&&intval(FaBObjectCounters($w)['WEAPON_ATTACKS']??0)<2)||intval($w->Status??2)===2||in_array('CRU_EXTRA_ATTACK',(array)$w->TurnEffects,true); }
function FaBCRUUseWeapon(object $w): void {
    $turn=intval(GetTurnNumber());$count=intval(FaBObjectCounters($w)['WEAPON_ATTACK_TURN']??0)===$turn?intval(FaBObjectCounters($w)['WEAPON_ATTACKS']??0):0;
    FaBSetObjectCounter($w,'WEAPON_ATTACK_TURN',$turn);FaBSetObjectCounter($w,'WEAPON_ATTACKS',$count+1);
    if(intval($w->Status??2)!==2&&($w->CardID!=='bank_breaker'||$count>=2)){$tags=(array)$w->TurnEffects;$i=array_search('CRU_EXTRA_ATTACK',$tags,true);if($i!==false){unset($tags[$i]);$w->TurnEffects=array_values($tags);}}
    $w->Status=1;
}

function FaBCRUOwnPower(int $p,object $o): int {
    if(!FaBWTRIsAttackAction($o))return 0;
    $base=FaBWTRBase($o->CardID);$s=FaBGetState();$n=max(0,intval(EvaluateAttackPowerModifier($o->CardID,$p,$o,intval(CardPower($o->CardID)),$o)));
    foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'CRU_SUPPRESS_POWER:')||str_starts_with($tag,'CRU_REACTION_POWER:'))$n+=max(0,intval(substr($tag,19)));
    if($base==='overblast')$n+=FaBCRUCount($p,'CHAIN_BOOST');
    if($base==='barraging_brawnhide'&&FaBWTRNonEquipmentBlockCount($s)<2)++$n;
    if($base==='fluster_fist'&&FaBWTRBase($s['previousAttackCardID'])==='open_the_center')$n+=intval($s['chainHits']??0);
    return $n+max(0,FaBProfessorPower($p,$o));
}

// Lunging Press (blue): declare its printed target before the normal play/cost
// path. UIDs survive serialization; rendered combat-chain indices do not.
function FaBCRULungingTarget(?array $found): bool {
    if ($found === null || $found['zone'] !== 'CombatChain') return false;
    $object = $found['object'];
    return empty($object->removed) && FaBWTRIsAttackAction($object)
        && empty(FaBObjectCounters($object)['WEAPON_UID'])
        && empty(FaBObjectCounters($object)['MON_ARENA_ATTACK']);
}

function FaBCRULungingTargets(): array {
    $targets = [];
    foreach (FaBLiveSeats() as $seat) {
        foreach (FaBChoiceRefs($seat, 'CombatChain') as $ref) {
            $found = FaBIdentityFromMZ($ref);
            if (FaBCRULungingTarget($found)) $targets[intval($found['object']->UniqueID)] = $found;
        }
    }
    return $targets;
}

// A later-link return keeps the physical UID but is a different declared target.
// Same-link departure/re-entry is not distinguished by this bounded identity pair.
function FaBCRULungingTargetMatches(?array $found, int $declaredLink): bool {
    return FaBCRULungingTarget($found) && $declaredLink >= 0
        && intval($found['object']->ChainLink ?? -1) === $declaredLink;
}

/** Validate the source and unchanged pre-play window without consuming a choice. */
function FaBCRULungingPendingSource(int $player, array $pending): ?array {
    $state = FaBGetState();
    if (intval($pending['player'] ?? 0) !== $player || !FaBSeatIsLive($player)
        || intval(GetPriorityPlayer()) !== $player || GetCurrentPhase() !== 'MAIN'
        || intval(GetTurnNumber()) !== intval($pending['turnNumber'] ?? -1)
        || intval(GetTurnPlayer()) !== intval($pending['turnPlayer'] ?? 0)
        || empty($state['combatOpen']) || ($state['combatStep'] ?? '') !== 'REACTION'
        || ($state['window'] ?? '') !== ($pending['window'] ?? '')
        || !in_array($state['window'] ?? '', ['REACTION', 'PRIORITY'], true)
        || intval($state['attacker'] ?? 0) !== $player
        || intval($state['attackUID'] ?? 0) !== intval($pending['attackUID'] ?? 0)
        || intval($state['chainLink'] ?? 0) !== intval($pending['chainLink'] ?? 0)
        || ($state['pendingPayment'] ?? null) !== null
        || ($state['pendingAttackTarget'] ?? null) !== null
        || FaBReactionResolutionIsPending()) return null;
    $active = FaBFindUID(intval($pending['attackUID'] ?? 0));
    if ($active === null || $active['zone'] !== 'CombatChain'
        || ($active['object']->Role ?? '') !== 'ATTACK'
        || intval($active['object']->Controller ?? 0) !== $player
        || intval($active['object']->ChainLink ?? 0) !== intval($pending['chainLink'] ?? 0)) return null;
    $source = FaBFindUID(intval($pending['sourceUID'] ?? 0));
    if ($source === null || $source['object']->CardID !== 'lunging_press_blue'
        || $source['zone'] !== ($pending['sourceZone'] ?? '')
        || intval($source['player']) !== intval($pending['sourcePlayer'] ?? 0)
        || intval($source['object']->Owner ?? 0) !== intval($pending['sourceOwner'] ?? 0)
        || intval($source['object']->Controller ?? 0) !== intval($pending['sourceController'] ?? 0)
        || HasNoAbilities($source['object'])) return null;
    return $source;
}

/** A target UID, null while a choice is pending, or false for invalid targets. */
function FaBCRULungingClaimOrRequestTarget(int $player, array $source) {
    $state = FaBGetState(); $pending = $state['pendingLungingTarget'] ?? null;
    if (is_array($pending)) {
        if (FaBCRULungingPendingSource($player, $pending) === null
            || intval($pending['sourceUID'] ?? 0) !== intval($source['object']->UniqueID)) return false;
        $targetUID = intval($pending['selectedUID'] ?? 0);
        if ($targetUID <= 0) return null;
        $valid = in_array($targetUID, (array)($pending['eligibleUIDs'] ?? []), true)
            && FaBCRULungingTargetMatches(FaBFindUID($targetUID), intval($pending['eligibleLinks'][(string)$targetUID] ?? -1));
        unset($state['pendingLungingTarget']); FaBSetState($state);
        return $valid ? $targetUID : false;
    }
    $targets = FaBCRULungingTargets();
    if (count($targets) === 0) return false;
    if (count($targets) === 1) return intval(array_key_first($targets));
    $eligibleLinks = [];
    foreach ($targets as $uid => $target) $eligibleLinks[(string)$uid] = intval($target['object']->ChainLink ?? -1);
    $state['pendingLungingTarget'] = [
        'player'=>$player, 'sourceUID'=>intval($source['object']->UniqueID),
        'sourceZone'=>$source['zone'], 'sourcePlayer'=>intval($source['player']),
        'sourceOwner'=>intval($source['object']->Owner ?? 0),
        'sourceController'=>intval($source['object']->Controller ?? 0),
        'turnNumber'=>intval(GetTurnNumber()), 'turnPlayer'=>intval(GetTurnPlayer()),
        'window'=>(string)$state['window'], 'attackUID'=>intval($state['attackUID']),
        'chainLink'=>intval($state['chainLink']), 'eligibleUIDs'=>array_keys($targets), 'eligibleLinks'=>$eligibleLinks,
    ];
    FaBSetState($state);
    DecisionQueueController::AddDecision($player, 'MZCHOOSE',
        implode('&', array_column($targets, 'mzID')), 1, 'Choose_Lunging_Press_target');
    DecisionQueueController::AddDecision($player, 'CUSTOM',
        'FAB_LUNGING_TARGET|' . intval($source['object']->UniqueID), 1);
    return null;
}

function FaBCRULungingAnswerIsValid(int $player, string $answer): bool {
    $pending = FaBGetState()['pendingLungingTarget'] ?? null;
    if (!is_array($pending) || FaBCRULungingPendingSource($player, $pending) === null) return false;
    $queue = GetDecisionQueue($player); $choice = $queue[0] ?? null; $resume = $queue[1] ?? null;
    if (!is_object($choice) || ($choice->Type ?? '') !== 'MZCHOOSE'
        || ($choice->Tooltip ?? '') !== 'Choose_Lunging_Press_target'
        || !is_object($resume) || ($resume->Type ?? '') !== 'CUSTOM'
        || ($resume->Param ?? '') !== 'FAB_LUNGING_TARGET|' . intval($pending['sourceUID'])
        || !in_array($answer, explode('&', (string)$choice->Param), true)) return false;
    $found = FaBIdentityFromMZ($answer);
    return FaBCRULungingTarget($found)
        && in_array(intval($found['object']->UniqueID), (array)$pending['eligibleUIDs'], true)
        && FaBCRULungingTargetMatches($found, intval($pending['eligibleLinks'][(string)intval($found['object']->UniqueID)] ?? -1));
}

/** Only the generated outer PlayCard bookkeeping may precede this resume. */
function FaBCRULungingHasOwnPlayTails(int $player): bool {
    if (DecisionQueueController::ExecutingSeat() !== $player) return false;
    $queue = GetDecisionQueue($player);
    if (count($queue) !== 2) return false;
    foreach ([['PASSPARAMETER', '1'], ['SYSTEM', 'PlayCard_AfterAction']] as $i => $expected) {
        $row = $queue[$i] ?? null;
        if (!is_object($row) || ($row->Type ?? '') !== $expected[0]
            || (string)($row->Param ?? '') !== $expected[1]
            || intval($row->Block ?? -1) !== 99
            || (string)($row->Tooltip ?? '') !== ''
            || intval($row->DontSkipOnPass ?? 0) !== 0 || !empty($row->removed)) return false;
    }
    foreach (FaBSeatOrder() as $seat) {
        if (intval($seat) !== $player && count(GetDecisionQueue($seat)) > 0) return false;
    }
    $variables = json_decode((string)GetDecisionQueueVariables(), true);
    return is_array($variables) && empty($variables['__awaitFrames']);
}

function FaBCRULungingContinue(int $player, array $parts, string $answer): void {
    $state = FaBGetState(); $pending = $state['pendingLungingTarget'] ?? null;
    if (!is_array($pending) || count($parts) !== 1
        || (string)$parts[0] !== (string)intval($pending['sourceUID'])
        || intval($pending['selectedUID'] ?? 0) > 0
        || FaBCRULungingPendingSource($player, $pending) === null
        || !FaBCRULungingHasOwnPlayTails($player)) return;
    $target = FaBIdentityFromMZ($answer);
    if (!FaBCRULungingTarget($target)
        || !in_array(intval($target['object']->UniqueID), (array)$pending['eligibleUIDs'], true)
        || !FaBCRULungingTargetMatches($target, intval($pending['eligibleLinks'][(string)intval($target['object']->UniqueID)] ?? -1))) return;
    $uid = intval($target['object']->UniqueID);
    $link = intval($pending['eligibleLinks'][(string)$uid]);
    $state['pendingLungingTarget']['selectedUID'] = $uid;
    FaBSetState($state);
    // Reaction timing must see all work drained. The outer macro's two Block99
    // tails still exist during this CUSTOM, so resume behind them without
    // weakening FaBReactionResolutionHasWork or bypassing normal CanPlayCard.
    DecisionQueueController::AddDecision($player, 'CUSTOM',
        'FAB_LUNGING_PLAY|' . intval($pending['sourceUID']) . '|' . $uid . '|' . $link, 100);
}

/** Revalidate the bound selection after its outer macro has completely drained. */
function FaBCRULungingPlaySelected(int $player, array $parts): void {
    $pending = FaBGetState()['pendingLungingTarget'] ?? null;
    if (!is_array($pending) || count($parts) !== 3
        || (string)$parts[0] !== (string)intval($pending['sourceUID'])
        || (string)$parts[1] !== (string)intval($pending['selectedUID'] ?? 0)
        || intval($parts[1]) <= 0
        || (string)$parts[2] !== (string)intval($pending['eligibleLinks'][(string)intval($parts[1])] ?? -1)) return;
    try {
        $source = FaBCRULungingPendingSource($player, $pending);
        $target = FaBFindUID(intval($parts[1]));
        if (DecisionQueueController::ExecutingSeat() !== $player || $source === null
            || FaBReactionResolutionHasWork()
            || !in_array(intval($parts[1]), (array)$pending['eligibleUIDs'], true)
            || !FaBCRULungingTargetMatches($target, intval($parts[2]))) return;
        DoPlayCard($player, $source['mzID']);
    } finally {
        // Read fresh state: normal play, payment and listeners may have changed it.
        $state = FaBGetState();
        if (intval($state['pendingLungingTarget']['sourceUID'] ?? 0) === intval($pending['sourceUID'])) {
            unset($state['pendingLungingTarget']); FaBSetState($state);
        }
    }
}

/** Writer-only index projection, retaining only the originally listed live UIDs. */
function FaBCRULungingChoicesForSave(array $zone, int $player): array {
    $pending = FaBGetState()['pendingLungingTarget'] ?? null;
    if (!is_array($pending) || FaBCRULungingPendingSource($player, $pending) === null) return $zone;
    $choice = $zone[0] ?? null; $resume = $zone[1] ?? null;
    if (!is_object($choice) || ($choice->Type ?? '') !== 'MZCHOOSE'
        || ($choice->Tooltip ?? '') !== 'Choose_Lunging_Press_target'
        || !is_object($resume) || ($resume->Type ?? '') !== 'CUSTOM'
        || ($resume->Param ?? '') !== 'FAB_LUNGING_TARGET|' . intval($pending['sourceUID'])) return $zone;
    $bound = array_fill_keys(array_map('intval', (array)$pending['eligibleUIDs']), true);
    $listed = [];
    foreach (explode('&', (string)$choice->Param) as $ref) {
        $found = FaBIdentityFromMZ($ref);
        // Mixed or stale original coordinates are not safe to reinterpret.
        if (!FaBCRULungingTarget($found) || !isset($bound[intval($found['object']->UniqueID)])
            || !FaBCRULungingTargetMatches($found, intval($pending['eligibleLinks'][(string)intval($found['object']->UniqueID)] ?? -1))) return $zone;
        $listed[intval($found['object']->UniqueID)] = true;
    }
    $refs = [];
    foreach (FaBLiveSeats() as $seat) {
        $next = 0;
        foreach (GetCombatChain($seat) as $object) {
            if ($object == null || $object->Removed()) continue;
            if (isset($listed[intval($object->UniqueID ?? 0)])) $refs[] = 'p' . $seat . 'CombatChain-' . $next;
            ++$next;
        }
    }
    $copy = clone $choice; $copy->Param = implode('&', $refs); $zone[0] = $copy;
    return $zone;
}

function FaBCRUResolveLunging(int $player, object $source): void {
    if ($source->CardID !== 'lunging_press_blue' || HasNoAbilities($source)) return;
    $targetUID = intval($source->Params['cruLungingTargetUID'] ?? 0);
    $targetLink = intval($source->Params['cruLungingTargetChainLink'] ?? -1);
    if (!FaBCRULungingTargetMatches(FaBFindUID($targetUID), $targetLink)) return;
    // Preserve the native tag/CRU/Snag pipeline and the wrapper's source actor.
    FaBTagUID($targetUID, 'CRU_REACTION_POWER:1');
}

/** Count public graveyard copies while the resolving Tome is still a stack layer. */
function FaBCRUResolveGorganian(int $player, object $source): void {
    if ($source->CardID !== 'gorganian_tome' || HasNoAbilities($source)) return;
    $amount = 1;
    foreach (FaBLiveSeats() as $seat) {
        $amount += count(FaBChoiceRefs($seat, 'Graveyard', ['base'=>'gorganian_tome']));
    }
    DoDrawCard($player, $amount);
}

function FaBCRUApplySnag(): void {
    foreach(FaBLiveSeats() as $p){
        if(!FaBCRUCount($p,'SNAG'))foreach(['CombatChain','Stack'] as $zone)foreach(FaBZoneGet($zone,$p) as $o)if(is_object($o)&&empty($o->removed)&&intval($o->Controller??$o->Owner??0)===$p&&FaBWTRIsAttackAction($o))FaBARCSetCard(intval($o->UniqueID),'snagRetained',FaBCRUOwnPower($p,$o));
        FaBCRUAdd($p,'SNAG');
    }
}
