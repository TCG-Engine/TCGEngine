<?php
/** IAR state uses absolute seat IDs and a turn key, including during opponents' UPF turns. */
function FaBIARCount(int $p, string $key): int {
    return intval(FaBGetState()['iar'][intval(GetTurnNumber())][$p][$key] ?? 0);
}
function FaBIARAdd(int $p, string $key, int $n = 1): void {
    $s=FaBGetState(); $t=intval(GetTurnNumber());
    $s['iar']=[$t=>$s['iar'][$t]??[]];
    $s['iar'][$t][$p][$key]=max(0,intval($s['iar'][$t][$p][$key]??0)+$n); FaBSetState($s);
}
function FaBIARRunechants(?int $controller = null): string {
    $refs=[];
    // Usurp is an untargeted cost: neither ownership nor UPF adjacency restricts it.
    foreach ($controller===null?FaBLiveSeats():[$controller] as $p) foreach (FaBChoiceRefs($p,'Arena') as $r) {
        $o=FaBIdentityFromMZ($r)['object'];
        if ($o->CardID==='runechant'||(!HasNoAbilities($o)&&str_starts_with($o->CardID,'runechant_of_'))) $refs[]=$r;
    }
    return implode('&',$refs);
}
function FaBIARUsurp(int $p, int $uid, string $ref): bool {
    if (!in_array($ref,explode('&',FaBIARRunechants()),true)) return false;
    $f=FaBIdentityFromMZ($ref); $o=clone $f['object']; $owner=$f['player'];
    FaBMONDestroy(intval($o->UniqueID));
    $still=FaBFindUID(intval($o->UniqueID)); if ($still&&$still['zone']==='Arena') return false;
    FaBTagUID($uid,'WTR_POWER:2'); FaBIARAdd($p,'USURPED');
    // Usurping triggers when the attack is played, after all costs have been paid.
    if (!HasNoAbilities($o)&&str_starts_with($o->CardID,'runechant_of_'))
        FaBARCSetCard($uid,'iarUsurped',['player'=>$owner,'id'=>$o->CardID,'uid'=>intval($o->UniqueID)]);
    return true;
}
function FaBIARFrom(int $uid): string {
    $f=FaBFindUID($uid); if (!$f) return '';
    return (string)($f['object']->SourceZone??$f['object']->FromZone??'');
}
function FaBIARBanishHand(int $p, string $r): string {
    $f=FaBIdentityFromMZ($r); if (!$f||$f['player']!==$p||$f['zone']!=='Hand') return '';
    $id=$f['object']->CardID; FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p); return $id;
}
function FaBIARHandReward(int $p, string $id): void {
    if (FaBHasType($id,'Runeblade')) FaBARCCreateRunes($p,1);
    if (FaBHasType($id,'Shadow')) FaBWTRCreateArena($p,'gate_to_iarathael');
}
function FaBIARBanishTop(int $p): int {
    $refs=FaBChoiceRefs($p,'Deck'); if (!$refs) return 0;
    $f=FaBIdentityFromMZ($refs[0]); $power=FaBMONBasePower($p,$f['object']);
    FaBMoveUID(intval($f['object']->UniqueID),'Banish',$p); return $power;
}
function FaBIARPull(int $p, int $pitch): void {
    $refs=FaBChoiceRefs($p,'Deck'); if (!$refs) return;
    $o=FaBIdentityFromMZ($refs[0])['object']; $match=intval(CardPitch($o->CardID))===$pitch;
    FaBMoveUID(intval($o->UniqueID),'Banish',$p); if ($match) FaBWTRCreateArena($p,'gate_to_iarathael');
}
function FaBIARRevealTop(int $p, bool $bottomMiss=false): bool {
    $refs=FaBChoiceRefs($p,'Deck'); if (!$refs) return false;
    FaBRevealChoices($p,$refs[0]); $o=FaBIdentityFromMZ($refs[0])['object']; $six=FaBMONBasePower($p,$o)>=6;
    if (!$six&&$bottomMiss) FaBMoveUID(intval($o->UniqueID),'Deck',$p);
    return $six;
}
function FaBIARNext(int $p, int $power=0, string $tag='', string $kind='ANY'): void {
    FaBWTRAddEffect($p,'IAR_NEXT',$power,['tag'=>$tag,'kind'=>$kind]);
}
function FaBIARNextMatches(object $o, string $kind): bool {
    return match($kind) {
        'SHADOW'=>FaBHasType($o,'Shadow'),
        'SHADOW_OR_RUNEBLADE'=>FaBHasType($o,'Shadow')||FaBHasType($o,'Runeblade'),
        'SIX'=>FaBMONBasePower(intval($o->Controller),$o)>=6,
        'ALLY'=>FaBHasType($o,'Ally'),
        default=>true
    };
}
function FaBIARApplyNext(int $p, object $o): void {
    $left=[];
    foreach (FaBWTREffects($p) as $e) {
        if (($e['type']??'')!=='IAR_NEXT'||!FaBIARNextMatches($o,$e['kind']??'ANY')) {$left[]=$e;continue;}
        if (intval($e['amount']??0)) FaBWTRTag($o,'WTR_POWER:'.intval($e['amount']));
        foreach (explode('&',$e['tag']??'') as $tag) if ($tag!=='') FaBWTRTag($o,$tag);
    }
    FaBWTRSetEffects($p,$left);
}
function FaBIARPlayed(int $p, object $o, string $from): void {
    $b=FaBWTRBase($o->CardID); $uid=intval($o->UniqueID); $attack=FaBWTRIsAttackAction($o)||in_array($from,['Weapons','Arena'],true);
    FaBARCSetCard($uid,'iarPlayedFrom',$from);
    if(FaBWTRIsAttackAction($o)){
        $left=[];foreach(FaBWTREffects($p) as $e){if(($e['type']??'')==='IAR_SONATA'){
            FaBWTRTag($o,'WTR_POWER:'.intval($e['amount']));FaBWTRTag($o,'OVERPOWER');FaBWTRTag($o,'CRU_RUNE_HIT:'.intval($e['amount']));
        }else $left[]=$e;}FaBWTRSetEffects($p,$left);
        if(FaBHasType($o,'Guardian')||FaBHasType($o,'Revered')){
            if(!FaBIARCount($p,'CHORUS_ATTACK'))foreach(FaBMONArena($p,'head_banging_chorus') as $r)FaBWTRTag($o,'IAR_CHORUS');
            FaBIARAdd($p,'CHORUS_ATTACK');
        }
    }
    $usurped=FaBARCCard($uid,'iarUsurped',[]);
    if($usurped){FaBARCSetCard($uid,'iarUsurped',[]);FaBROSQueue(intval($usurped['player']),$usurped['id'],intval($usurped['uid']),['rosEvent'=>'iarUsurp','iarAttack'=>$uid]);}
    if(FaBWTRIsAttackAction($o)&&FaBHasKeyword($o,'Blood Debt')){
        if(!FaBIARCount($p,'DEBT_ATTACK')&&FaBMONHero($p,'viserai_usurper'))FaBWTRTag($o,'GO_AGAIN');
        FaBIARAdd($p,'DEBT_ATTACK');
    }
    if ($attack) FaBIARApplyNext($p,$o);
    if (FaBWTRIsAttackAction($o)) {
        foreach (explode('&',FaBIARRunechants($p)) as $ref) {
            $f=FaBIdentityFromMZ($ref); if ($f&&str_starts_with($f['object']->CardID,'runechant_of_')) FaBMONDestroy(intval($f['object']->UniqueID));
        }
        if ($from==='Banish'&&FaBIAROwnBanish($p,$uid)&&FaBIARCount($p,'PROMISE')) {
            $n=FaBIARCount($p,'PROMISE'); FaBIARAdd($p,'PROMISE',-$n); FaBARCCreateRunes($p,2*$n);
        }
    }
    if ($from==='Banish'&&FaBIAROwnBanish($p,$uid)&&$attack) {
        if (FaBMONHero($p,'baalghor_omen_of_the_end')&&FaBWTRIsAttackAction($o)) FaBWTRTag($o,'WTR_POWER:3');
        if (in_array($b,['shadowrealm_harrower','shadowrealm_harvester','shadowrealm_reaper'],true)) {
            FaBWTRTag($o,'WTR_POWER:1');
            if ($b==='shadowrealm_harvester') FaBWTRTag($o,'OVERPOWER');
            if ($b==='shadowrealm_reaper') FaBWTRTag($o,'GO_AGAIN');
        }
        if ($b==='corrupt_and_conquer') FaBWTRTag($o,'IAR_NO_DR');
    }
    if ($from==='Banish'&&FaBIAROwnBanish($p,$uid)&&FaBHasType($o,'Aura')&&str_contains(CardName($o->CardID),'Runechant')&&FaBIARCount($p,'EMBRACE')) FaBIARAdd($p,'EMBRACE',-1);
}
function FaBIARHit(int $p, object $o): void {
    $source=intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0);
    if($source&&FaBFaiHeroHit())FaBIARBoundTriggers($source);
    if(FaBFaiHeroHit()&&FaBHasType($o,'Dagger')&&FaBIARCount($p,'FORGE')){
        FaBIARAdd($p,'FORGE',-FaBIARCount($p,'FORGE'));FaBROSQueue($p,'fresh_from_the_forge_red',intval($o->UniqueID),['rosTarget'=>intval(FaBGetState()['defender'])]);
    }
    if(FaBFaiHeroHit()&&FaBWTRBase($o->CardID)==='wind_slicer')FaBIARWindLock(intval(FaBGetState()['defender']));
    foreach ((array)$o->TurnEffects as $tag) {
        if ($tag==='IAR_CORPSE') FaBIARCorpse($p);
        if ($tag==='IAR_GATE') FaBWTRCreateArena($p,'gate_to_iarathael');
        if ($tag==='IAR_HIT_GO') FaBWTRTag($o,'GO_AGAIN');
        if ($tag==='IAR_EXORCISM'&&FaBFaiHeroHit()) FaBIARTurnDownAll(intval(FaBGetState()['defender']));
        if ($tag==='IAR_CHORUS'&&FaBFaiHeroHit()&&FaBHandCount($p)===0)DoDrawCard($p,1);
    }
}
function FaBIARBanishRefs(int $p, string $type=''): string {
    return implode('&',array_filter(FaBChoiceRefs($p,'Banish'),function($r)use($type){
        $o=FaBIdentityFromMZ($r)['object'];return empty($o->FaceDown)&&($type===''||($type==='DEBT'?FaBHasKeyword($o,'Blood Debt'):FaBHasType($o,$type)));
    }));
}
function FaBIARTurnDown(int $p, string $refs): int {
    $n=0;foreach (explode('&',$refs) as $r) {
        $f=FaBIdentityFromMZ($r);if (!$f||$f['player']!==$p||$f['zone']!=='Banish'||!empty($f['object']->FaceDown))continue;
        $f['object']->FaceDown=1;$f['object']->PlayableFromBanish=0;++$n;
    }return $n;
}
function FaBIARTurnDownAll(int $p): int {return FaBIARTurnDown($p,FaBIARBanishRefs($p));}
function FaBIARHarvest(int $p, string $refs): void {
    $n=0;$valid=array_slice(array_unique(explode('&',$refs)),0,3);
    foreach ($valid as $r) { $f=FaBIdentityFromMZ($r);$shadow=$f&&FaBHasType($f['object'],'Shadow');if (FaBIARTurnDown($p,$r)&&$shadow)++$n; }
    if ($n) FaBARCCreateRunes($p,$n);
}
function FaBIARReturnBanish(int $p, string $ref): bool {
    $f=FaBIdentityFromMZ($ref);if (!$f||$f['player']!==$p||$f['zone']!=='Banish')return false;
    $zombie=empty($f['object']->FaceDown)&&FaBHasType($f['object'],'Zombie');
    FaBMoveUID(intval($f['object']->UniqueID),'Graveyard',$p);return $zombie;
}
function FaBIARCorpse(int $p): void {
    if (!FaBSeatIsLive($p))return;
    $o=AddBanish($p,CardID:'corrupted_corpse',Owner:$p,Controller:$p,FaceDown:0);
    FaBMONAfterMove($p,$o,'','Banish');
}
function FaBIARBlasmophet(int $p): string {
    FaBWTRCreateArena($p,'blasmophet_the_insatiable_hunger');
    $refs=[];foreach(array_merge(FaBChoiceRefs($p,'Arena'),FaBChoiceRefs($p,'Hero')) as $r){
        $o=FaBIdentityFromMZ($r)['object'];if(in_array($o->CardID,['blasmophet_the_insatiable_hunger','blasmophet_levia_consumed'],true))$refs[]=$r;
    }
    return count($refs)>1?implode('&',$refs):'';
}
function FaBIARUniqueClear(int $p,string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$p||!in_array($f['object']->CardID,['blasmophet_the_insatiable_hunger','blasmophet_levia_consumed'],true))return;
    if($f['zone']!=='Hero')FaBIARBoundTriggers(intval($f['object']->UniqueID),true);
    $f['object']->removed=true;
    if($f['zone']==='Hero')FaBEliminateSeat($p);
}
function FaBIARAllyCosts(int $p, bool $zombie=false): string {
    $filter=['type'=>$zombie?'Zombie':'Ally'];
    return implode('&',array_merge(FaBChoiceRefs($p,'Hand',$filter),FaBChoiceRefs($p,'Arena',$filter)));
}
function FaBIARPayAlly(int $p, string $ref): bool {
    if (!in_array($ref,explode('&',FaBIARAllyCosts($p)),true))return false;
    $f=FaBIdentityFromMZ($ref);if ($f['zone']==='Hand')return FaBDiscardChoice($p,$ref)!==false;
    $uid=intval($f['object']->UniqueID);FaBMONDestroy($uid);$after=FaBFindUID($uid);return !$after||$after['zone']!=='Arena';
}
function FaBIARBanishZone(int $p, string $zone): void {
    foreach (FaBUPRUIDs(implode('&',FaBChoiceRefs($p,$zone))) as $uid)FaBMoveUID($uid,'Banish',$p);
}
function FaBIARDoomwake(int $uid): void {
    $s=FaBGetState();$uids=[$uid];foreach(FaBLiveSeats() as $p)foreach(FaBChoiceRefs($p,'CombatChain') as $r){
        $o=FaBIdentityFromMZ($r)['object'];if(intval($o->ChainLink)===intval($s['chainLink'])&&in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true))$uids[]=intval($o->UniqueID);
    }
    foreach($uids as $u){$f=FaBFindUID($u);if($f)FaBMoveUID($u,'Banish',intval($f['object']->Owner));}
}
function FaBIARShadowAttacker(): bool {
    $p=intval(FaBGetState()['attacker']);return $p>0&&isset(GetHero($p)[0])&&FaBHasType(GetHero($p)[0],'Shadow');
}
function FaBIARSearchRune(int $p): string {
    FaBStageSearch($p,['type'=>'Aura']);return implode('&',array_filter(FaBChoiceRefs($p,'Temp'),fn($r)=>str_contains(CardName(FaBIdentityFromMZ($r)['object']->CardID),'Runechant')));
}
function FaBIARBanishPlayable(int $p, object $o): bool {
    if (!empty($o->FaceDown))return false;$b=FaBWTRBase($o->CardID);
    if (FaBIARPermission($p,$o,'Banish'))return true;
    if (HasNoAbilities($o))return false;
    if (in_array($b,['abyssal_bite','abyssal_force','abyssal_rush','enshrine_sin'],true)||str_ends_with($b,'_gloomblade'))return true;
    if ($b==='usurp_the_shadow_throne')return FaBIARCount($p,'USURPED')>0;
    if(FaBIARBlasmophetAvailable($p,$o))return true;
    if (FaBHasType($o,'Aura')&&str_contains(CardName($o->CardID),'Runechant')&&FaBIARCount($p,'EMBRACE'))return true;
    return false;
}
function FaBIARPermission(int $p, object $o, string $zone): bool {
    $grant=FaBARCCard(intval($o->UniqueID),'iarPermission',[]);
    return empty($o->FaceDown)&&($grant['player']??0)===$p&&($grant['turn']??-1)===intval(GetTurnNumber())&&($grant['zone']??'')===$zone;
}
function FaBIARCost(int $p, object $o): int {
    $b=FaBWTRBase($o->CardID);$f=FaBFindUID(intval($o->UniqueID));$n=0;
    if ($b==='enshrine_sin'&&FaBIAROwnBanish($p,intval($o->UniqueID))&&(($f['zone']??'')==='Banish'||($o->SourceZone??'')==='Banish'))++$n;
    if ($b==='arknight_descendancy')$n-=count(array_filter(explode('&',FaBIARRunechants($p))));
    if ($b==='usurp_the_shadow_throne'&&FaBIARCount($p,'USURPED'))$n-=6;
    if(FaBWTRIsAttackAction($o))$n-=FaBARCEffect($p,'IAR_SONATA');
    return $n;
}
function FaBIARPower(int $p, object $o): int {
    $uid=intval(FaBObjectCounters($o)['MON_SOURCE_UID']??$o->UniqueID);
    $n=count(FaBIARBoundMarks($uid));if(HasNoAbilities($o))return $n;$b=FaBWTRBase($o->CardID);
    if($b==='feasting_shadowbeast'&&FaBMONCount($p,'BANISHED_SIX'))$n+=2;
    if($b==='tremor_of_iarathael'&&FaBMONCount($p,'BANISHED'))$n+=2;
    return $n;
}
function FaBIARGoAgain(int $p, object $o): bool {
    if(HasNoAbilities($o))return false;$b=FaBWTRBase($o->CardID);
    if($b==='feeding_frenzy')return FaBMONCount($p,'BANISHED_SIX')>0;
    if($b==='murmur_of_iarathael')return FaBMONCount($p,'BANISHED')>0;
    if($b==='bloodfrenzy_gloomblade')return FaBIARCount($p,'DAMAGE:'.intval(FaBGetState()['defender']))>0;
    return false;
}
function FaBIARDestroyed(int $p, object $o): void {
    if(!HasNoAbilities($o)&&str_starts_with($o->CardID,'runechant_of_'))FaBROSQueue($p,$o->CardID,intval($o->UniqueID),['rosEvent'=>'iarDestroy']);
}
function FaBIARPitched(int $p, int $uid): void {
    if(FaBMONHero($p,'baalghor_omen_of_the_end'))FaBROSQueue($p,'baalghor_omen_of_the_end',$uid,['rosEvent'=>'iarPitch']);
}
