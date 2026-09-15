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
    $highest=true;foreach(FaBOpponents($p) as $seat)if(intval(GetHealth($seat))>=intval(GetHealth($p)))$highest=false;
    if($highest)foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'Weapons',['base'=>'reaping_blade']) as $ref)if(!HasNoAbilities(FaBIdentityFromMZ($ref)['object']))return;
    AddHealth($p,intval(GetHealth($p))+$n);
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
    $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if(!$a||!($a['object']->CardID==='meganetic_shockwave_blue'||(FaBWTRBase($a['object']->CardID)==='t_bone'&&FaBCRUCount(intval($s['attacker']),'CHAIN_BOOST')>0)))return false;
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
    if($o->CardID==='cintari_saber'&&FaBWTRIsAttackAction($block)&&!FaBARCCard($uid,'saber')){FaBARCSetCard($uid,'saber',true);if($weapon)FaBWTRTag($weapon['object'],'WTR_POWER:1');}
    if($o->CardID==='zephyr_needle'&&FaBCurrentDefense($block,$p)>FaBAttackPower($s)&&$weapon)FaBWTRTag($weapon['object'],'CRU_BREAK_CHAIN');
}
function FaBCRUHitSuppressed(object $o,bool $all=false): bool {
    if(in_array('CRU_NO_HIT',(array)$o->TurnEffects,true))return true;
    if(!$all&&FaBWTRIsAttackAction($o))foreach(FaBLiveSeats() as $p)if(FaBChoiceRefs($p,'Arena',['base'=>'stamp_authority']))return true;
    return false;
}
function FaBCRUHit(int $p,object $o,int $amount): void {
    if(FaBWTRIsAttackAction($o)&&FaBCRUHero($p,'benji_the_piercing_wind')&&intval(FaBGetState()['attackActionHits'][(string)$p]??0)===1)FaBWTRAddEffect($p,'NEXT_ATTACK',1);
    if(FaBWTRIsWeapon($o))FaBCRUAdd($p,'WEAPON_HITS');
    if(FaBWTRIsWeapon($o)&&FaBCRUCount($p,'SPOILS'))for($i=0;$i<2*FaBCRUCount($p,'SPOILS');++$i)FaBWTRCreateArena($p,'copper');
    $r=0;foreach((array)$o->TurnEffects as $tag)if(str_starts_with($tag,'CRU_RUNE_HIT:'))$r+=intval(substr($tag,13));if($r&&!FaBCRUHitSuppressed($o))FaBARCCreateRunes($p,$r);
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
    if(FaBCRUHero($p,'kassai_cintari_sellsword')&&FaBCRUCount($p,'WEAPONS')>=2)for($i=0;$i<FaBCRUCount($p,'WEAPON_HITS');++$i)FaBWTRCreateArena($p,'copper');
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

function FaBCRUWeaponReady(object $w): bool { return (intval(FaBObjectCounters($w)['EVR_EXTRA_ATTACK_TURN']??-1)===intval(GetTurnNumber())&&intval(FaBObjectCounters($w)['WEAPON_ATTACKS']??0)<2)||intval($w->Status??2)===2||in_array('CRU_EXTRA_ATTACK',(array)$w->TurnEffects,true); }
function FaBCRUUseWeapon(object $w): void {
    $turn=intval(GetTurnNumber());$count=intval(FaBObjectCounters($w)['WEAPON_ATTACK_TURN']??0)===$turn?intval(FaBObjectCounters($w)['WEAPON_ATTACKS']??0):0;
    FaBSetObjectCounter($w,'WEAPON_ATTACK_TURN',$turn);FaBSetObjectCounter($w,'WEAPON_ATTACKS',$count+1);
    if(intval($w->Status??2)!==2){$tags=(array)$w->TurnEffects;$i=array_search('CRU_EXTRA_ATTACK',$tags,true);if($i!==false){unset($tags[$i]);$w->TurnEffects=array_values($tags);}}
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
function FaBCRUApplySnag(): void {
    foreach(FaBLiveSeats() as $p){
        if(!FaBCRUCount($p,'SNAG'))foreach(['CombatChain','Stack'] as $zone)foreach(FaBZoneGet($zone,$p) as $o)if(is_object($o)&&empty($o->removed)&&intval($o->Controller??$o->Owner??0)===$p&&FaBWTRIsAttackAction($o))FaBARCSetCard(intval($o->UniqueID),'snagRetained',FaBCRUOwnPower($p,$o));
        FaBCRUAdd($p,'SNAG');
    }
}
