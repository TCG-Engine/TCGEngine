<?php

/** Arcane Rising rules shared by generated card continuations and both formats. */
function FaBARCSetCard(int $uid,string $key,$value): void {
    $s=FaBGetState();$s['arcCards'][(string)$uid][$key]=$value;FaBSetState($s);
}
function FaBARCCard(int $uid,string $key,$default=0) {
    return FaBGetState()['arcCards'][(string)$uid][$key]??$default;
}
function FaBARCPlayed(int $player, bool $nonAttack = false): array {
    $ids = FaBGetState()['cardsPlayedThisTurn'][(string)$player] ?? [];
    return $nonAttack ? array_values(array_filter($ids, fn($id) => FaBHasType($id,'Action') && !FaBHasType($id,'Attack'))) : $ids;
}

function FaBARCEffect(int $player, string $type): int {
    $n=$type==='ARC_ACTIONS'?intval(FaBGetState()['arcActions'][(string)$player]??0):0;
    foreach(FaBWTREffects($player) as $e) if(($e['type']??'')===$type) $n+=intval($e['amount']??0); return $n;
}
function FaBARCRecordAction(int $player): void {
    $s=FaBGetState();$s['arcActions'][(string)$player]=intval($s['arcActions'][(string)$player]??0)+1;FaBSetState($s);
}

function FaBARCRunechants(int $player): int {
    return count(FaBChoiceRefs($player,'Arena',['base'=>'runechant']));
}
function FaBARCCreateRunes(int $player,int $amount): void {
    if($amount<=0||!FaBSeatIsLive($player))return;
    $amount+=FaBARCEffect($player,'ARC_MORDRED');
    FaBHVYToken($player,'runechant',$amount);
}

/** UPF spell targeting is adjacent, independently of the attack's chain focus.
 * Effects explicitly targeting multiple heroes (Forked Lightning) ignore adjacency. */
function FaBARCHeroTargets(int $player,bool $opposing=true,bool $multiple=false): string {
    $seats=FaBSeatCount()>2&&!$multiple ? FaBAdjacentOpponents($player) : FaBLiveSeats();
    if(FaBSeatCount()===2&&!$opposing)$seats=FaBLiveSeats();
    if($opposing)$seats=array_values(array_diff($seats,[$player]));
    $refs=[];foreach($seats as $seat)$refs=array_merge($refs,FaBChoiceRefs($seat,'Hero'));
    return implode('&',$refs);
}
function FaBARCTargetSeat(int $player,string $ref,bool $opposing=true,bool $multiple=false): int {
    if(!in_array($ref,explode('&',FaBARCHeroTargets($player,$opposing,$multiple)),true))return 0;
    return intval(FaBIdentityFromMZ($ref)['player']??0);
}

/** Subset sums matter: a lone Barrier 3 cannot be paid as Barrier 1. */
function FaBARCBarrierPayments(int $player,int $damage): array {
    return array_values(array_unique(array_column(FaBUPRLegacyPreventionPlans($player,$damage),'cost')));
}
function FaBARCBarrierOptions(int $player,int $damage): string {
    return implode('&',array_map('FaBUPRLegacyPreventionLabel',FaBUPRLegacyPreventionPlans($player,$damage)));
}
function FaBARCBarrierCost(int $player,int $damage,string $choice): int {
    $plan=FaBUPRLegacyPreventionPlans($player,$damage)[intval($choice)]??['cost'=>0,'quell'=>[],'energy'=>0];
    foreach($plan['quell'] as $uid){$f=FaBFindUID($uid);if($f)FaBSetObjectCounter($f['object'],'UPR_DESTROY_END',1);}
    if($plan['energy']&&FaBUPRAlluvionReady(abs($plan['energy']),$damage))FaBUPRAlluvion(abs($plan['energy']),$plan['energy']>0);
    return $plan['cost'];
}

function FaBARCPitchChoices(int $player): string {
    return implode('&',array_filter(FaBChoiceRefs($player,'Hand'),fn($ref)=>intval(CardPitch(FaBIdentityFromMZ($ref)['object']->CardID))>0&&!FaBARCNamedProhibited(FaBIdentityFromMZ($ref)['object']->CardID)&&!FaBHNTNamed(FaBIdentityFromMZ($ref)['object']->CardID)&&FaBOUTCanPitch($player,FaBIdentityFromMZ($ref)['object'])&&FaBELECanPitch($player,FaBIdentityFromMZ($ref)['object'])));
}
function FaBARCPitchForEffect(int $player,string $ref): bool {
    if(!in_array($ref,explode('&',FaBARCPitchChoices($player)),true))return false;
    $id=FaBIdentityFromMZ($ref)['object']->CardID;
    FaBMoveChoice($player,$ref,'Hand','Pitch');AddResources($player,intval(GetResources($player))+FaBEVRPitch($player,FaBMONPitchValue($player,$id),true),'PITCH');
    FaBMSTPitch($player,$id,FaBMONPitchValue($player,$id));FaBWTRCardPitched($player,$id);
    $pitch=FaBChoiceRefs($player,'Pitch');$last=end($pitch);
    FaBRunSourceMacro('CardPitched',$player,$id,['mzID'=>$last]);return true;
}
function FaBARCDealArcane(int $player,int $target,int $amount,int $payment=0): int {
    if(!FaBSeatIsLive($target))return 0;
    if(FaBEVRUnpreventable($player,$target))$payment=0;
    $payment=max(0,min($payment,intval(GetResources($target))));
    AddResources($target,intval(GetResources($target))-$payment);
    $dealt=DoDamage($player,(string)DecisionQueueController::GetVariable('mzID'),$target,max(0,$amount-$payment),'ARCANE');
    $s=FaBGetState();$s['arcaneDealt'][(string)$player][(string)$target]=intval($s['arcaneDealt'][(string)$player][(string)$target]??0)+$dealt;FaBSetState($s);
    return $dealt;
}
function FaBARCArcaneAmount(int $player,int $uid,int $base,int $target=0): int {
    $f=FaBFindUID($uid);$id=$f['object']->CardID??'';
    if($id==='blazing_aether_red')$base=intval(FaBGetState()['arcaneDealt'][(string)$player][(string)$target]??0);
    return max(0,$base+FaBMSTAmp($player,$uid)+FaBROSChorus($player,$uid)+FaBDYNArcaneBonus($uid)+intval(FaBARCCard($uid,'arcaneBonus'))+($f&&FaBHasType($f['object'],'Action')?FaBEVRCount($player,'WILDFIRE'):0));
}

function FaBARCLowerLife(int $player): bool {
    foreach(FaBOpponents($player) as $seat)if(intval(GetHealth($player))<intval(GetHealth($seat)))return true;return false;
}

/** Opt stages only the chooser's cards in their private Temp zone. */
function FaBARCStageTop(int $player,int $amount): array {
    $uids=[];
    foreach(array_slice(FaBChoiceRefs($player,'Deck'),0,max(0,$amount)) as $ref)$uids[]=intval(FaBIdentityFromMZ($ref)['object']->UniqueID);
    foreach($uids as $uid)FaBMoveUID($uid,'Temp',$player,false);
    return $uids;
}
function FaBARCOrderParam(array $uids,string $piles='Top,Bottom'): string {
    $ids=[];foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f!==null)$ids[]=$f['object']->CardID;}
    $names=explode(',',$piles);$out=[];foreach($names as $i=>$name)$out[]=$name.'='.($i===0?implode(',',$ids):'');return implode(';',$out);
}
function FaBARCFinishOrder(int $player,array $uids,string $result): void {
    $byID=[];foreach($uids as $uid){$f=FaBFindUID(intval($uid));if($f!==null&&$f['player']===$player&&$f['zone']==='Temp')$byID[$f['object']->CardID][]=$uid;}
    $top=[];$bottom=[];
    foreach(explode(';',$result) as $pile){$pair=explode('=',$pile,2);if(count($pair)!==2||!in_array($pair[0],['Top','Bottom'],true))continue;
        foreach(explode(',',$pair[1]) as $id)if(!empty($byID[$id])){ $uid=array_shift($byID[$id]);if($pair[0]==='Top')$top[]=$uid;else $bottom[]=$uid;}
    }
    foreach($byID as $remaining)$top=array_merge($top,$remaining);
    foreach($bottom as $uid)FaBMoveUID($uid,'Deck',$player,false);
    foreach(array_reverse($top) as $uid)FaBARCToDeck($player,intval($uid),true);
}
function FaBARCToDeck(int $player,int $uid,bool $top): void {
    FaBARCPreserveAttack($uid);
    if(FaBMoveUID($uid,'Deck',$player,false)!==null&&$top){$deck=&GetDeck($player);$last=array_pop($deck);array_unshift($deck,$last);}
}
function FaBARCPreserveAttack(int $uid): void {
    $found=FaBFindUID($uid);$state=FaBGetState();
    if($found!==null&&$found['zone']==='CombatChain'&&intval($state['attackUID']??0)===$uid){
        $state['attackGoAgain']=FaBAttackHasGoAgain($state,$found['object']);FaBSetState($state);
    }
}
function FaBARCReturnAttack(int $player,string $mzID): void {
    $found=FaBIdentityFromMZ($mzID);if($found===null)return;
    $uid=intval($found['object']->UniqueID);FaBARCPreserveAttack($uid);
    FaBMoveUID($uid,'Hand',intval($found['object']->Owner??$player));
}
function FaBARCRevealPitch(int $player): int {
    $refs=array_slice(FaBChoiceRefs($player,'Deck'),0,1);FaBRevealChoices($player,implode('&',$refs));
    return $refs?intval(CardPitch(FaBIdentityFromMZ($refs[0])['object']->CardID)):0;
}
function FaBARCSelect(int $player,string $zone,string $class='',string $kind='',int $maxCost=999): string {
    if($zone==='Deck'&&FaBUPRBleak())return '';
    $out=[];foreach(FaBChoiceRefs($player,$zone) as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if($class!==''&&!FaBHasType($o,$class))continue;
        if($kind==='AR'&&!FaBHasType($o,'Attack Reaction'))continue;
        if($kind==='AA'&&!FaBWTRIsAttackAction($o))continue;
        if($kind==='NAA'&&(!FaBHasType($o,'Action')||FaBHasType($o,'Attack')))continue;
        if($kind==='Item'&&!FaBHasType($o,'Item'))continue;
        if($kind==='Action'&&!FaBHasType($o,'Action'))continue;
        if($kind==='Arrow'&&!FaBHasType($o,'Arrow'))continue;
        if($kind==='SunKiss'&&FaBWTRBase($o->CardID)!=='sun_kiss')continue;
        if($maxCost<999&&(!is_numeric(CardCost($o->CardID))||intval(CardCost($o->CardID))>$maxCost))continue;
        $out[]=$ref;
    }return implode('&',$out);
}
function FaBARCSetPendingCost(int $uid,int $cost): void {
    $s=FaBGetState();if(intval($s['pendingPayment']['uid']??0)!==$uid)return;$s['pendingPayment']['cost']=max(0,$cost);FaBSetState($s);
}
function FaBARCTome(int $player): void {
    $refs=array_slice(FaBChoiceRefs($player,'Deck'),0,2);FaBRevealChoices($player,implode('&',$refs));
    if(count($refs)!==2)return;$aa=0;$naa=0;$uids=[];
    foreach($refs as $ref){$o=FaBIdentityFromMZ($ref)['object'];$uids[]=intval($o->UniqueID);if(FaBWTRIsAttackAction($o))++$aa;elseif(FaBHasType($o,'Action'))++$naa;}
    if($aa===1&&$naa===1)foreach($uids as $uid)FaBMoveUID($uid,'Hand',$player);
}
function FaBARCDiscardKind(int $player,string $ref): string {
    $f=FaBIdentityFromMZ($ref);if($f===null||$f['player']!==$player||$f['zone']!=='Hand'||!FaBHasType($f['object'],'Action'))return '';
    $kind=FaBWTRIsAttackAction($f['object'])?'NAA':'AA';FaBDiscardChoice($player,$ref);return $kind;
}
function FaBARCName(int $uid,string $name): void {
    $s=FaBGetState();$s['arcNames'][(string)$uid]=trim($name);FaBSetState($s);
}
function FaBARCExactItems(int $player,int $cost): string {
    return implode('&',array_filter(explode('&',FaBARCSelect($player,'Deck','Mechanologist','Item',$cost)),fn($ref)=>$ref!==''&&intval(CardCost(FaBIdentityFromMZ($ref)['object']->CardID))===$cost));
}
function FaBARCPutItem(int $player,string $ref,string $zone,bool $extra=false): void {
    $o=FaBMoveChoice($player,$ref,$zone,'Arena');if($o===null)return;FaBARCEnterItem($player,$o);
    if($extra&&FaBARCEffect($player,'ARC_BOOSTED')>0)FaBARCSteam($o,1);
}
function FaBARCFoundry(int $player): void {
    $refs=array_slice(FaBChoiceRefs($player,'Deck'),0,2);$gain=0;
    foreach($refs as $ref){$f=FaBIdentityFromMZ($ref);if(FaBHasType($f['object'],'Mechanologist'))++$gain;if(FaBWTRBase($f['object']->CardID)==='back_alley_breakline')AddActionPoints($player,intval(GetActionPoints($player))+1);FaBMoveUID(intval($f['object']->UniqueID),'Banish',$player);}
    AddResources($player,intval(GetResources($player))+$gain);
}
function FaBARCAzalea(int $player,string $ref): void {
    if(FaBMoveChoice($player,$ref,'Arsenal','Deck')===null)return;
    $refs=FaBChoiceRefs($player,'Deck');if(!$refs)return;
    $uid=intval(FaBIdentityFromMZ($refs[0])['object']->UniqueID);
    if(FaBARCLoadArsenal($player,$refs[0],true)){$o=FaBFindUID($uid)['object'];if(FaBHasType($o,'Arrow'))FaBWTRTag($o,'DOMINATE');}
}
function FaBARCCharge(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if($f!==null&&intval(FaBObjectCounters($f['object'])['STEAM']??0)===0)FaBARCSteam($f['object'],1);
}
function FaBARCMaintainShield(int $player,int $uid,string $choice): void {
    $f=FaBFindUID($uid);if($f===null||$f['zone']!=='Arena')return;
    if($choice==='0'&&intval(FaBObjectCounters($f['object'])['STEAM']??0)>0)FaBARCSteam($f['object'],-1);
    else FaBMoveUID($uid,'Graveyard',$player);
}
function FaBARCStageRefs(int $player,string $refs): array {
    $uids=[];foreach(explode('&',$refs) as $ref){$f=FaBIdentityFromMZ($ref);if($f!==null&&$f['player']===$player&&$f['zone']==='Deck')$uids[]=intval($f['object']->UniqueID);}
    foreach($uids as $uid)FaBMoveUID($uid,'Temp',$player,false);return $uids;
}
function FaBARCBanishInstant(int $player,string $chosen,string $zone,int $discount=0): void {
    $o=FaBMoveChoice($player,$chosen,$zone,'Banish');if($o===null)return;
    $o->PlayableFromBanish=1;FaBARCSetCard(intval($o->UniqueID),'instantTurn',intval(GetTurnNumber()));FaBARCSetCard(intval($o->UniqueID),'discount',$discount);
}
function FaBARCAsInstant(int $player,object $obj): bool {
    if(FaBROSAsInstant($player,$obj))return true;
    if(FaBWTRBase($obj->CardID)==='cull')foreach(FaBLiveSeats() as $seat)if(FaBDTDCount($seat,'LOST')>0)return true;
    if(FaBMSTAsInstant($player,$obj)||FaBEVOAsInstant($player,$obj)||FaBDTDAsInstant($player,$obj))return true;
    if($obj->CardID==='lumina_ascension_yellow'&&FaBMONArena($player,'spirit_of_eirina'))return true;
    if(FaBELEAsInstant($player,$obj)||FaBEVRAsInstant($player,$obj))return true;
    if(FaBCRUAsInstant($player,$obj))return true;
    if(!FaBHasType($obj,'Action')||FaBHasType($obj,'Attack'))return false;
    if(intval(FaBARCCard(intval($obj->UniqueID),'instantTurn'))===intval(GetTurnNumber()))return true;
    return FaBARCEffect($player,'ARC_NEXT_NAA_INSTANT')>0 || (FaBHasType($obj,'Wizard')&&FaBARCEffect($player,'ARC_NEXT_WIZARD_INSTANT')>0);
}
function FaBARCLoadArsenal(int $player,string $chosen,bool $faceUp,int $power=0): bool {
    if(!FaBELEArsenalSpace($player))return false;
    $f=FaBIdentityFromMZ($chosen);if($f===null||$f['player']!==$player||!in_array($f['zone'],['Hand','Temp','Deck'],true))return false;
    $o=FaBMoveUID(intval($f['object']->UniqueID),'Arsenal',$player);if($o===null)return false;
    $o->FaceDown=$faceUp?0:1;
    if($power)FaBWTRTag($o,'WTR_POWER:'.$power);
    if($faceUp)FaBARCArsenalFaceUp($player,$o,$f['zone']);return true;
}
function FaBARCArsenalFaceUp(int $player,object $o,string $from='Arsenal'): void {
    FaBARCSetCard(intval($o->UniqueID),'faceUp',true);
    FaBOUTFaceUp($player,$o,$from);
    if(in_array($from,['Deck','Temp'],true)&&FaBHasType($o,'Arrow')&&FaBMONWeapon($player,'sandscour_greatbow'))FaBSetObjectCounter($o,'AIM',intval(FaBObjectCounters($o)['AIM']??0)+1);
    if($o->CardID==='remorseless_red'&&$from!=='Arsenal')FaBWTRTag($o,'CRU_NO_ARSENAL_DR');
    $base=FaBWTRBase($o->CardID);
    if($base==='head_shot'&&$from!=='Arsenal')FaBWTRTag($o,'WTR_POWER:2');
    if($base==='ridge_rider_shot'&&$from!=='Arsenal')FaBRunSourceMacro('StartTurn',$player,$o->CardID,['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);
    if($base==='back_alley_breakline'&&$from==='Deck')AddActionPoints($player,intval(GetActionPoints($player))+1);
}
function FaBARCLoseLife(int $target,int $amount,int $source): void {
    if(!FaBSeatIsLive($target))return;AddHealth($target,max(0,intval(GetHealth($target))-$amount));
    FaBMONLifeLost($target,$amount);
    if(intval(GetHealth($target))===0)FaBEliminateSeat($target,$source);
}

function FaBARCNamedProhibited(string $id): bool {
    foreach(FaBLiveSeats() as $seat)foreach(GetArena($seat) as $o)if(is_object($o)&&empty($o->removed)&&$o->CardID==='chains_of_eminence_red'){
        $name=FaBGetState()['arcNames'][(string)$o->UniqueID]??'';
        if($name!==''&&strcasecmp($name,CardName($id))===0)return true;
    }return false;
}
function FaBARCCanPlay(int $player,array $found): bool {
    $obj=$found['object'];$id=$obj->CardID;
    if(FaBARCNamedProhibited($id))return false;
    if(FaBARCEffect($player,'ARC_LEDGER')&&FaBHasType($obj,'Action')&&FaBARCEffect($player,'ARC_ACTIONS')>=1)return false;
    if(FaBHasType($obj,'Arrow')&&$found['zone']!=='Arsenal')return false;
    if(FaBARCEffect($player,'ARC_THREE_KIND')&&$found['zone']!=='Arsenal')return false;
    if($id==='maximum_velocity_red'&&FaBARCEffect($player,'ARC_BOOSTED')<3)return false;
    $s=FaBGetState();$attack=FaBFindUID(intval($s['attackUID']));
    if(FaBHasType($obj,'Defense Reaction')&&$attack!==null&&$attack['object']->CardID==='command_and_conquer_red'&&!empty($s['combatOpen']))return false;
    return true;
}
function FaBARCCostModifier(int $player,object $obj): int {
    $base=FaBWTRBase($obj->CardID);$delta=FaBOUTCost($player,$obj);
    if(in_array($base,['amplify_the_arknight','arknight_ascendancy','drawn_to_the_dark_dimension','ninth_blade_of_the_blood_oath','rune_flash','reduce_to_runechant'],true))$delta-=FaBARCRunechants($player);
    if(FaBWTRIsAttackAction($obj)||FaBWTRIsWeapon($obj))$delta+=FaBARCEffect($player,'ARC_FIRST_ATTACK_COST');
    if(FaBARCCard(intval($obj->UniqueID),'instantTurn')===intval(GetTurnNumber()))$delta-=intval(FaBARCCard(intval($obj->UniqueID),'discount'));
    return $delta;
}

function FaBARCCardPlayed(int $player,object $obj,string $fromZone): void {
    FaBEVRPlayed($player,$obj,$fromZone);
    $base=FaBWTRBase($obj->CardID);$aa=FaBWTRIsAttackAction($obj);$attack=$aa||FaBWTRIsWeapon($obj);
    if(FaBHasType($obj,'Action')||FaBWTRIsWeapon($obj))FaBARCRecordAction($player);
    $effects=[];$bonus=0;
    foreach(FaBWTREffects($player) as $e){$type=$e['type']??'';$applies=false;
        if($type==='ARC_FIRST_ATTACK_COST'&&$attack)continue;
        if($type==='ARC_NEXT_NAA_INSTANT'&&FaBHasType($obj,'Action')&&!$aa)continue;
        if($type==='ARC_NEXT_NAA_GO'&&FaBHasType($obj,'Action')&&!$aa){FaBWTRTag($obj,'GO_AGAIN');continue;}
        if($type==='ARC_NEXT_WIZARD_INSTANT'&&FaBHasType($obj,'Wizard')&&FaBHasType($obj,'Action')&&!$aa){$bonus+=max(0,intval($e['amount'])-1);continue;}
        if($type==='ARC_NEXT_AA')$applies=$aa;
        if($type==='ARC_NEXT_MECH')$applies=$aa&&FaBHasType($obj,'Mechanologist');
        if($type==='ARC_NEXT_RANGER')$applies=$aa&&FaBHasType($obj,'Ranger');
        if($type==='ARC_NEXT_RUNEBLADE')$applies=$attack&&FaBHasType($obj,'Runeblade');
        if($type==='ARC_NEXT_ARCANE'&&FaBARCIsArcaneCard($obj->CardID)){$bonus+=intval($e['amount']);continue;}
        if($type==='ARC_LEAD'&&FaBHasType($obj,'Action')&&intval(CardCost($obj->CardID))>=intval($e['amount'])){AddActionPoints($player,intval(GetActionPoints($player))+1);continue;}
        if(!$applies){$effects[]=$e;continue;}
        if(intval($e['amount']??0))FaBWTRTag($obj,'WTR_POWER:'.intval($e['amount']));
        if(!empty($e['dominate']))FaBWTRTag($obj,'DOMINATE');
    }
    FaBWTRSetEffects($player,$effects);
    if($bonus)FaBARCSetCard(intval($obj->UniqueID),'arcaneBonus',intval(FaBARCCard(intval($obj->UniqueID),'arcaneBonus'))+$bonus);
    if($base==='life_for_a_life'&&FaBARCLowerLife($player))FaBWTRTag($obj,'GO_AGAIN');
    if($base==='fervent_forerunner'&&$fromZone==='Arsenal')FaBWTRTag($obj,'GO_AGAIN');
    if($base==='vigor_rush'&&count(FaBARCPlayed($player,true)))FaBWTRTag($obj,'GO_AGAIN');
    if($base==='push_the_point'&&!empty(FaBGetState()['attackHit']))FaBWTRTag($obj,'WTR_POWER:2');
    if(FaBHasType($obj,'Arrow')&&FaBARCEffect($player,'ARC_RAPID'))FaBWTRTag($obj,'GO_AGAIN');
    if($obj->CardID==='nebula_blade'&&count(FaBARCPlayed($player,true)))FaBWTRTag($obj,'WTR_POWER:3');
    if($attack){
        foreach(FaBChoiceRefs($player,'Arena',['base'=>'runechant']) as $ref){
            FaBMoveChoice($player,$ref,'Arena','Graveyard');
            $rune=AddStack(CardID:'runechant',Controller:$player,Kind:'TRIGGER',SourceZone:'Arena');
            FaBRunSourceMacro('PrepareCard',$player,'runechant',['mzID'=>FaBFindUID(intval($rune->UniqueID))['mzID']]);
        }
    }
    $previous=FaBARCPlayed($player,true);
    if(FaBHasType($obj,'Action')&&!$aa)array_pop($previous);
    if($previous&&FaBHasType($obj,'Runeblade')&&FaBWTRHeroActive($player))foreach(GetHero($player) as $hero)if(is_object($hero)&&in_array($hero->CardID,['viserai','viserai_rune_blood'],true))FaBARCCreateRunes($player,1);
}
function FaBARCAfterHit(int $player,object $attack,int $amount): void {
    $base=FaBWTRBase($attack->CardID);
    if($base==='life_for_a_life')FaBCRUGainLife($player,1);
    if(!FaBWTRIsAttackAction($attack))return;
    $effects=[];$draw=0;
    foreach(FaBWTREffects($player) as $e){if(($e['type']??'')==='ARC_PLUNDER')$draw+=intval($e['amount']);else $effects[]=$e;}
    FaBWTRSetEffects($player,$effects);if($draw)DoDrawCard($player,$draw);
    foreach(FaBChoiceRefs($player,'Arena',['base'=>'bloodspill_invocation']) as $ref){$id=FaBIdentityFromMZ($ref)['object']->CardID;FaBMoveChoice($player,$ref,'Arena','Graveyard');FaBARCCreateRunes($player,FaBWTRPitchValue($id,[3,2,1]));}
    foreach(FaBChoiceRefs($player,'Equipment',['base'=>'vest_of_the_first_fist']) as $ref)FaBRunSourceMacro('Hit',$player,'vest_of_the_first_fist',['mzID'=>$ref,'amount'=>$amount]);
    if(in_array('ARC_BOTTOM_HIT',(array)($attack->TurnEffects??[]),true))FaBARCToDeck($player,intval($attack->UniqueID),false);
}
function FaBARCStartTurn(int $player): void {
    foreach(FaBChoiceRefs($player,'Arena') as $ref){$o=FaBIdentityFromMZ($ref)['object'];
        if($o->CardID==='chains_of_eminence_red')FaBMoveChoice($player,$ref,'Arena','Graveyard');
        if($o->CardID==='teklo_core_blue'){FaBARCSteam($o,-1);AddResources($player,intval(GetResources($player))+2);}
        if($o->CardID==='dissipation_shield_yellow')FaBRunSourceMacro('StartTurn',$player,$o->CardID,['mzID'=>$ref]);
    }
}
function FaBARCEndTurn(int $player): void {
    if(!FaBARCPlayed($player,true))foreach(FaBChoiceRefs($player,'Arena',['base'=>'enchanting_melody']) as $ref)FaBMoveChoice($player,$ref,'Arena','Graveyard');
}
function FaBARCIsArcaneCard(string $id): bool {
    return in_array(FaBWTRBase($id),['aether_wildfire','emeritus_scolding','timekeepers_whim','scour','foreboding_bolt','rousing_aether','snapback','chain_lightning','aether_flare','aether_spindle','blazing_aether','forked_lightning','lesson_in_lava','reverberate','scalding_rain','sonic_boom','voltic_bolt','zap'],true);
}

function FaBARCPreventDamage(int $player,int $amount,string $type): int {
    $remaining=[];
    foreach(FaBWTREffects($player) as $e){
        if(($e['type']??'')==='ARC_PREVENT'&&$type==='ARCANE'&&$amount>0){$used=min($amount,intval($e['amount']));$amount-=$used;$e['amount']-=$used;if($e['amount']>0)$remaining[]=$e;}
        elseif(($e['type']??'')==='ARC_PREVENT_ONCE'&&$amount>0)$amount=max(0,$amount-intval($e['amount']));
        else $remaining[]=$e;
    }FaBWTRSetEffects($player,$remaining);
    foreach(GetArena($player) as $o)if(is_object($o)&&empty($o->removed)&&FaBWTRBase($o->CardID)==='enchanting_melody'&&$amount>0){
        $amount=max(0,$amount-FaBWTRPitchValue($o->CardID,[4,3,2]));FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);
    }
    if($amount>0)foreach(FaBChoiceRefs($player,'Arena',['base'=>'bloodspill_invocation']) as $ref)FaBMoveChoice($player,$ref,'Arena','Graveyard');
    return $amount;
}
