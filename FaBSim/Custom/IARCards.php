<?php
/** IAR state uses absolute seat IDs and a turn key, including during opponents' UPF turns. */
function FaBIARCount(int $p, string $key): int {
    if ($key === 'BLASMOPHET_ATTACK') {
        return max(0, FaBARCEffect($p, 'IAR_BLASMOPHET_ATTACK') + intval(FaBGetState()['iar'][intval(GetTurnNumber())][$p][$key] ?? 0));
    }
    return intval(FaBGetState()['iar'][intval(GetTurnNumber())][$p][$key] ?? 0);
}
function FaBIARAdd(int $p, string $key, int $n = 1): void {
    if ($key === 'BLASMOPHET_ATTACK') {
        $state = FaBGetState(); $turn = intval(GetTurnNumber());
        $delta = max(0, FaBIARCount($p, $key) + $n) - FaBARCEffect($p, 'IAR_BLASMOPHET_ATTACK');
        if (isset($state['iar'][$turn][$p][$key])) {
            unset($state['iar'][$turn][$p][$key]); FaBSetState($state);
        }
        if ($delta !== 0) FaBWTRAddEffect($p, 'IAR_BLASMOPHET_ATTACK', $delta);
        return;
    }
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
function FaBIARQueueWindHit(int $p,object $attack): void {
    $s=FaBGetState();$target=$s['attackTarget']??null;
    $source=intval(FaBObjectCounters($attack)['MON_SOURCE_UID']??FaBObjectCounters($attack)['WEAPON_UID']??0);
    $victim=is_array($target)?intval($target['player']??0):0;
    if(!FaBSeatIsLive($p)||($attack->CardID??'')!=='wind_slicer_blue'||($attack->Role??'')!=='ATTACK'
        ||intval($attack->UniqueID??0)<1||intval($attack->Controller??0)!==$p||HasNoAbilities($attack)||$source<1
        ||intval($s['attackUID']??0)!==intval($attack->UniqueID)||!is_array($target)||($target['type']??'')!=='HERO'
        ||intval($target['uid']??0)<1||intval($s['defender']??0)!==$victim||$victim===$p||!FaBSeatIsLive($victim))return;
    FaBROSQueue($p,'wind_slicer_blue',$source,['rosEvent'=>'iarWindHeroHit','iarWindVictim'=>$victim]);
}
function FaBIARResolveWindHit(int $p,object $layer): void {
    $params=is_array($layer->Params??null)?$layer->Params:[];$victim=$params['iarWindVictim']??null;
    if(($layer->CardID??'')!=='wind_slicer_blue'||($layer->Kind??'')!=='ABILITY'
        ||intval($layer->Controller??0)!==$p||!FaBSeatIsLive($p)||intval($layer->SourceUniqueID??0)<1
        ||($params['rosTrigger']??false)!==true||($params['rosEvent']??'')!=='iarWindHeroHit'
        ||!is_int($victim)||$victim===$p||!FaBSeatIsLive($victim))return;
    // The earned hit ability is independent of the later physical Item or attack.
    FaBIARWindLock($victim);
}
function FaBIARHit(int $p, object $o): void {
    $source=intval(FaBObjectCounters($o)['MON_SOURCE_UID']??0);
    if($source&&FaBFaiHeroHit())FaBIARBoundTriggers($source);
    if(FaBFaiHeroHit()&&FaBHasType($o,'Dagger')&&FaBIARCount($p,'FORGE')){
        FaBIARAdd($p,'FORGE',-FaBIARCount($p,'FORGE'));FaBROSQueue($p,'fresh_from_the_forge_red',intval($o->UniqueID),['rosTarget'=>intval(FaBGetState()['defender'])]);
    }
    if(FaBFaiHeroHit()&&FaBWTRBase($o->CardID)==='wind_slicer')FaBIARQueueWindHit($p,$o);
    foreach ((array)$o->TurnEffects as $tag) {
        if ($tag==='IAR_CORPSE') FaBIARCorpse($p);
        if ($tag==='IAR_GATE') FaBWTRCreateArena($p,'gate_to_iarathael');
        if ($tag==='IAR_HIT_GO') FaBWTRTag($o,'GO_AGAIN');
        if ($tag==='IAR_EXORCISM'&&FaBFaiHeroHit()) FaBIARTurnDownAll(intval(FaBGetState()['defender']));
        if ($tag==='IAR_CHORUS')FaBIARQueueChorusHit($p,$o);
    }
}
function FaBIARQueueChorusHit(int $p,object $attack): void {
    $controller=intval($attack->Controller??$p);if($controller<1)$controller=$p;
    if(!FaBSeatIsLive($controller)||!FaBFaiHeroHit()||intval($attack->UniqueID??0)<1
        ||($attack->Role??'')!=='ATTACK'||!is_string($attack->CardID??null)||$attack->CardID===''
        ||!in_array('IAR_CHORUS',(array)($attack->TurnEffects??[]),true)
        ||HasNoAbilities($attack)||FaBCRUHitSuppressed($attack)
        ||FaBMONArena($controller,'head_banging_chorus')===[])return;
    // The first eligible attack already received this ability at play; hand is checked only on resolution.
    FaBROSQueue($controller,$attack->CardID,intval($attack->UniqueID),
        ['rosEvent'=>'iarChorusHitDraw','iarChorusGranted'=>true]);
}
function FaBIARResolveChorusHitDraw(int $p,object $layer): void {
    $params=is_array($layer->Params??null)?$layer->Params:[];
    if(($layer->Kind??'')!=='ABILITY'||intval($layer->Controller??0)!==$p||!FaBSeatIsLive($p)
        ||intval($layer->SourceUniqueID??0)<1||!is_string($layer->CardID??null)||$layer->CardID===''
        ||($params['rosTrigger']??false)!==true||($params['rosEvent']??'')!=='iarChorusHitDraw'
        ||($params['iarChorusGranted']??false)!==true||FaBHandCount($p)!==0)return;
    // This earned ability is independent of the attack/Aura's later location, name or abilities.
    $hadController=array_key_exists('fabEffectController',$GLOBALS);
    $previousController=$hadController?$GLOBALS['fabEffectController']:null;
    $GLOBALS['fabEffectController']=$p;
    try{DoDrawCard($p,1);}
    finally{if($hadController)$GLOBALS['fabEffectController']=$previousController;else unset($GLOBALS['fabEffectController']);}
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
/** Only the printed three Hellbound Assault identities; copied legacy macros stay separate. */
function FaBIARHellboundCard(string $id): bool {
    return in_array($id,['hellbound_assault_red','hellbound_assault_yellow','hellbound_assault_blue'],true);
}

/** Capture the actual attacking instance/link at its positive hit; do not self-banish inline. */
function FaBIARQueueHellboundHit(int $p,object $attack,int $amount): void {
    $s=FaBGetState();$uid=intval($attack->UniqueID??0);$link=intval($attack->ChainLink??0);
    $owner=intval($attack->Owner??0);$found=FaBFindUID($uid);
    if(!FaBIARHellboundCard((string)$attack->CardID)||$uid<1||$link<1||$amount<=0
        ||!FaBSeatIsLive($p)||$owner<1||HasNoAbilities($attack)
        ||intval($attack->Controller??0)!==$p||intval($s['attacker']??0)!==$p
        ||intval($s['attackUID']??0)!==$uid||intval($s['chainLink']??0)!==$link
        ||!$found||$found['zone']!=='CombatChain'||$found['object']!==$attack
        ||($attack->Role??'')!=='ATTACK')return;
    FaBROSQueue($p,(string)$attack->CardID,$uid,['rosEvent'=>'iarHellboundHit',
        'iarHellboundLink'=>$link,'iarHellboundOwner'=>$owner]);
}

/** Resolve only that still-present chain instance. A departed source is never retrieved or resurrected. */
function FaBIARResolveHellboundHit(int $p,object $trigger): void {
    $uid=intval($trigger->SourceUniqueID??0);$link=intval($trigger->Params['iarHellboundLink']??0);
    $owner=intval($trigger->Params['iarHellboundOwner']??0);
    if(($trigger->Params['rosEvent']??'')!=='iarHellboundHit'
        ||!FaBIARHellboundCard((string)$trigger->CardID)||intval($trigger->Controller??0)!==$p
        ||!FaBSeatIsLive($p)||$uid<1||$link<1||$owner<1)return;
    $found=FaBFindUID($uid);if(!$found||$found['zone']!=='CombatChain')return;$source=$found['object'];
    if((string)$source->CardID!==(string)$trigger->CardID||($source->Role??'')!=='ATTACK'
        ||intval($source->ChainLink??0)!==$link||intval($source->Owner??0)!==$owner)return;
    // The trigger is already earned: later loss of source abilities does not cancel it.
    FaBMoveUID($uid,'Banish',$owner);
}

