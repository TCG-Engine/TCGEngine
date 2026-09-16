<?php
/** Omens events are keyed by turn and seat, independently of combat focus. */
function FaBOMNCount(int $p, string $key): int {
    return intval(FaBGetState()['omn'][intval(GetTurnNumber())][$p][$key] ?? 0);
}
function FaBOMNAdd(int $p, string $key, int $n = 1): void {
    $s = FaBGetState(); $t = intval(GetTurnNumber());
    $s['omn']=[$t=>$s['omn'][$t]??[]];
    $s['omn'][$t][$p][$key] = intval($s['omn'][$t][$p][$key] ?? 0) + $n;
    FaBSetState($s);
}
function FaBOMNHolo(object $o): int { return intval(FaBObjectCounters($o)['HOLO'] ?? 0); }
function FaBOMNFlows(int $p): string { return implode('&', FaBMONArena($p, 'lightning_flow')); }
function FaBOMNInstantLink(int $p): bool { return FaBOMNCount($p, 'INSTANT:'.intval(FaBGetState()['chainLink'])) > 0; }
function FaBOMNAfterMove(int $p, object $o, string $from, string $to, ?object $old = null): void {
    $b = FaBWTRBase($o->CardID); $uid = intval($o->UniqueID);
    if ($to === 'Soul' && $from !== 'Soul') foreach (FaBMONArena($p,'blessing_of_aegis') as $r) FaBCRUGainLife($p,1);
    if ($to === 'Graveyard' && $from !== 'Graveyard' && FaBHasType($o, 'Instant')) {
        FaBOMNAdd($p, 'STARFALL');
        if ($b === 'echoflash') FaBROSQueue($p, $o->CardID, $uid, ['rosEvent'=>'omnGrave']);
    }
    if ($from === 'Arena' && $to !== 'Arena' && $old && !HasNoAbilities($old)) {
        $owner = intval($old->Controller ?: $p);
        $token = ['circular_flowtide'=>'lightning_flow', 'sigil_of_astral_flow'=>'lightning_flow',
            'elliptical_conflux'=>'embodiment_of_lightning', 'nebulus_cycle'=>'ponder'][$b] ?? '';
        if ($token !== '') FaBWTRCreateArena($owner, $token);
        if (in_array($b, ['corrosive_space_dust','flicker_reality'], true))
            FaBROSQueue($owner, $o->CardID, $uid, ['rosEvent'=>'omnLeave']);
    }
    if ($to === 'Arena' && $from !== 'Arena' && FaBHasType($o, 'Aura') && !HasNoAbilities($o)) {
        if (FaBARCCard($uid, 'omnHoloEntry', false)) {
            $o->Counters=['HOLO'=>1]; $o->TurnEffects=[]; $o->Status=2; $o->Damage=0;
            FaBARCSetCard($uid, 'omnHoloEntry', false);
        }
        if (FaBOMNHolo($o)) FaBOMNAdd($p, 'HOLO_ENTER');
        if (in_array($b, ['auric_shards','crackle_from_afar','fleeing_starbreeze','nourishing_glow','haven_veil'], true))
            FaBROSQueue($p, $o->CardID, $uid, ['rosEvent'=>'omnEnter']);
    }
}
function FaBOMNDestroyed(int $p, object $o): void {
    if (FaBHasType($o, 'Aura')) FaBOMNAdd($p, 'AURA_DESTROYED');
    if ($o->CardID === 'lightning_flow') { if(!FaBOMNCount($p,'FLOW_DESTROYED'))foreach(FaBMONArena($p,'channel_stormgarden') as $r)FaBMSTAdd($p,'AMP');FaBOMNAdd($p, 'FLOW_DESTROYED'); }
}
function FaBOMNDefended(object $o): void {
    $owner=intval($o->Controller);
    if(FaBHasType($o,'Action')&&FaBARCEffect($owner,'OMN_RAZOR')){
        FaBWTRTag($o,'OMN_RAZOR_DEFENSE');
        FaBWTRSetEffects($owner,array_values(array_filter(FaBWTREffects($owner),fn($e)=>($e['type']??'')!=='OMN_RAZOR')));
    }
    $s = FaBGetState(); $attack = FaBFindUID(intval($s['attackUID']));
    if (!$attack || HasNoAbilities($attack['object']) || !FaBHasKeyword($attack['object'], 'Fragment')) return;
    if (FaBCurrentDefense($o, intval($o->Controller)) < 2) return;
    $uid = intval($attack['object']->UniqueID); $p = intval($s['attacker']);
    FaBROSQueue($p, $attack['object']->CardID, $uid,
        ['rosEvent'=>'omnFragment', 'omnVictim'=>intval($s['defender'])]);
}
function FaBOMNFragment(int $p, int $uid): void {
    if (!FaBFindUID($uid)) return;
    FaBTagUID($uid, 'WTR_POWER:-2');
    FaBARCSetCard($uid, 'omnFragmented', true); FaBOMNAdd($p, 'FRAGMENT');
}
function FaBOMNBlinkChoices(int $p): string {
    return implode('&', array_filter(FaBChoiceRefs($p, 'Arena', ['type'=>'Aura']), function($r) {
        $o = FaBIdentityFromMZ($r)['object']; return FaBHasType($o, 'Lightning') && !FaBOMNHolo($o);
    }));
}
function FaBOMNBlink(int $p, string $ref): void {
    if (!in_array($ref, explode('&', FaBOMNBlinkChoices($p)), true)) return;
    $uid = FaBPENUID($ref); $o = FaBMoveUID($uid, 'Banish', $p);
    if (!$o || FaBHasType($o, 'Token')) return;
    FaBARCSetCard($uid, 'omnHoloEntry', true);
    FaBMoveUID($uid, 'Arena', $p);
}
function FaBOMNAttackRefs(int $p, string $filter = ''): string {
    $s = FaBGetState(); $f = FaBFindUID(intval($s['attackUID']));
    if (empty($s['combatOpen']) || !$f || !in_array($f['player'], array_merge([$p], FaBAdjacentOpponents($p)), true)) return '';
    $o = $f['object'];
    if ($filter === 'Fragment' && !FaBHasKeyword($o, 'Fragment')) return '';
    if ($filter === 'fragmented' && !FaBARCCard(intval($o->UniqueID), 'omnFragmented', false)) return '';
    if ($filter === 'Action' && !FaBWTRIsAttackAction($o)) return '';
    if ($filter === 'Lightning' && !FaBHasType($o, 'Lightning')) return '';
    return $f['mzID'];
}
function FaBOMNPlayed(int $p, object $o): void {
    FaBARCSetCard(intval($o->UniqueID), 'omnFirstDamage', false);
    if (FaBHasType($o, 'Aura') && FaBARCEffect($p, 'OMN_HOLO')) {
        FaBARCSetCard(intval($o->UniqueID), 'omnHoloEntry', true);
        FaBWTRSetEffects($p, array_values(array_filter(FaBWTREffects($p), fn($e)=>($e['type'] ?? '') !== 'OMN_HOLO')));
    }
    if (FaBHasType($o, 'Instant')) FaBOMNAdd($p, 'INSTANT:'.intval(FaBGetState()['chainLink']));
    FaBOMNApplyNext($p,$o);
    if (FaBHasType($o,'Instant')) {
        $key='DISCOUNT:'.intval(FaBGetState()['chainLink']);
        FaBOMNAdd($p,$key,-FaBOMNCount($p,$key));
        $a=FaBFindUID(intval(FaBGetState()['attackUID']));
        if ($a && $a['player']===$p && FaBWTRBase($a['object']->CardID)==='flowstate_embodiment') FaBROSQueue($p,$a['object']->CardID,intval($a['object']->UniqueID));
    }
    if (FaBHasType($o,'Lightning') && intval(GetTurnPlayer())!==$p && !FaBOMNCount($p,'PLUTONIC')) {
        FaBOMNAdd($p,'PLUTONIC'); foreach (FaBCRUEquipment($p,'plutonic_starplate') as $r) AddResources($p,intval(GetResources($p))+1);
    }
    $color=FaBMSTObjectColor($p,$o); $effect='OMN_CHROMATIC:'.$color;
    if (FaBARCEffect($p,$effect)) {
        FaBARCSetCard(intval($o->UniqueID),'omnChromatic',FaBARCEffect($p,$effect));
        FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>($e['type']??'')!==$effect)));
    }
}
function FaBOMNTapHero(int $p): bool {
    foreach (GetHero($p) as $o) if (is_object($o) && empty($o->removed) && intval($o->Status) === 2) {
        $o->Status = 1; return true;
    }
    return false;
}
function FaBOMNHeroReady(int $p): bool {
    foreach (GetHero($p) as $o) if (is_object($o) && empty($o->removed) && intval($o->Status) === 2) return true;
    return false;
}
function FaBOMNBottom(int $p, string $ref, bool $top = false): void {
    $f = FaBIdentityFromMZ($ref);
    if (!$f || $f['player'] !== $p || !in_array($f['zone'], ['Hand','Graveyard'], true)) return;
    $o = FaBMoveUID(intval($f['object']->UniqueID), 'Deck', $p);
    if ($o && $top) { $deck =& GetDeck($p); $index = array_search($o, $deck, true); if ($index !== false) { array_splice($deck,$index,1); array_unshift($deck,$o); } }
}
function FaBOMNArcanePrevent(int $p, int $amount, string $type): int {
    if ($type !== 'ARCANE') return $amount;
    $n = 0;
    foreach (FaBChoiceRefs($p, 'CombatChain') as $r) {
        $o = FaBIdentityFromMZ($r)['object'];
        if (intval($o->ChainLink) === intval(FaBGetState()['chainLink']) && FaBWTRBase($o->CardID) === 'arcanic_cunning' && !HasNoAbilities($o)) ++$n;
    }
    foreach (GetStack() as $o) if (is_object($o) && empty($o->removed) && intval($o->Controller) === $p && FaBWTRBase($o->CardID) === 'arcanic_cunning' && ($o->Kind ?? '') !== 'ABILITY') ++$n;
    return max(0, $amount - $n);
}
function FaBOMNQuick(object $o): bool {
    // Power-dependent go again must not recursively evaluate Quick Succession.
    static $evaluating=[]; $uid=intval($o->UniqueID);
    if(isset($evaluating[$uid]))return false;
    $evaluating[$uid]=true;
    try{return FaBAttackHasGoAgain(FaBGetState(),$o);}finally{unset($evaluating[$uid]);}
}
function FaBOMNDamaged(int $p, int $victim, ?object $o): void {
    if ($p!==$victim && !FaBOMNCount($victim,'REPROACH')) {
        FaBOMNAdd($victim,'REPROACH');
        foreach (FaBMONArena($victim,'arcanic_reproach') as $r) FaBROSQueue($victim,'arcanic_reproach_blue',FaBPENUID($r),['rosEvent'=>'omnDamage','rosTarget'=>$p]);
    }
    if(!$o)return;
    $b = FaBWTRBase($o->CardID); $uid = intval($o->UniqueID);
    if (HasNoAbilities($o)) return;
    if (in_array($b, ['dashing_flashfoot','electryn_mindmeld','prophetic_quickstep','singeing_flowstride','stunning_swipe','tempestuous_kiss'], true)
        && !FaBARCCard($uid, 'omnFirstDamage', false)) {
        FaBARCSetCard($uid, 'omnFirstDamage', true);
        FaBROSQueue($p, $o->CardID, $uid, ['rosEvent'=>'omnDamage','rosTarget'=>$victim]);
    }
    if ($b === 'caress_of_the_reaper') FaBROSQueue($p, $o->CardID, $uid, ['rosEvent'=>'omnDamage','rosTarget'=>$victim]);
    FaBOMNSourceDamage($p,$victim,$o);
    if ($b==='aphrodias' && $victim!==$p && FaBARCEffect($p,'OMN_STARFIELD')) FaBWTRCreateArena($p,'lightning_flow');
}
function FaBOMNActionPhase(int $p): void {
    foreach (FaBChoiceRefs($p, 'Arena') as $r) {
        $o = FaBIdentityFromMZ($r)['object']; $b = FaBWTRBase($o->CardID);
        if (HasNoAbilities($o)) continue;
        if (in_array($b, ['sigil_of_astral_flow','haven_veil','spellbane_sigil'], true)) FaBMONDestroy(intval($o->UniqueID));
        if (in_array($b, ['core_reaction','chromatic_refinement','arcanic_reproach','thunderous_retort'], true))
            FaBROSQueue($p, $o->CardID, intval($o->UniqueID), ['rosEvent'=>'omnStart']);
    }
}
function FaBOMNPrevention(int $p, int $n, string $token = '', bool $arcane = false): void {
    FaBWTRAddEffect($p, 'OMN_PREVENT', $n, ['token'=>$token, 'arcane'=>$arcane]);
}
function FaBOMNPrevent(int $p, int $amount, string $type): int {
    $effects = []; $tokens = [];
    foreach (FaBWTREffects($p) as $e) {
        if (($e['type'] ?? '') !== 'OMN_PREVENT' || $amount <= 0 || (!empty($e['arcane']) && $type !== 'ARCANE')) { $effects[] = $e; continue; }
        $prevent = min($amount, intval($e['amount'])); $amount -= $prevent; $e['amount'] -= $prevent;
        if ($prevent > 0 && !empty($e['token'])) { $tokens[] = $e['token']; $e['token'] = ''; }
        if ($e['amount'] > 0) $effects[] = $e;
    }
    FaBWTRSetEffects($p, $effects);
    foreach ($tokens as $token) FaBWTRCreateArena($p, $token);
    return $amount;
}
function FaBOMNReady(string $ref): void {
    $f = FaBIdentityFromMZ($ref); if ($f && isset($f['object']->Status)) $f['object']->Status = 2;
}
function FaBOMNDiscardAbility(string $id): bool {
    return in_array(FaBWTRBase($id), ['cosmic_duality','nebula_duality','voltbound_duality','glide_through_starlight'], true);
}
function FaBOMNAbilityRows(): array {
    static $rows=null; if($rows!==null)return $rows; $rows=[];
    $rows['fortitude_of_anvilheim']=[['REACTION',2,true,false,false,0,'Return defending action']];
    $rows['gauntlet_of_sword_and_sorcery']=[['ACTION',2,false,true,false,0,'Tap hero and gauntlet']];
    foreach (['boots_of_astral_sanctuary','gloves_of_astral_sanctuary','helm_of_astral_sanctuary','robe_of_astral_sanctuary','boots_of_omnis_ward','constella_waves','starfield_carapace','starfield_veil'] as $id)
        $rows[$id] = [['INSTANT',0,true,false,false,0,'Use equipment']];
    foreach (['constella_tiara','laced_lightning','starflow_robes','fingers_of_fragmentation'] as $id)
        $rows[$id] = [['INSTANT',2,true,false,false,0,'Use equipment']];
    foreach (['snap_fingers','starfield_touch'] as $id) $rows[$id] = [['INSTANT',1,true,false,false,0,'Use equipment']];
    $rows['volzar_meteor_storm'] = [['INSTANT',0,false,false,false,0,'Tap to amp']];
    $rows['aphrodias'] = [['INSTANT',1,false,false,false,0,'Tap to deal arcane damage']];
    foreach (['aurora_emissary_of_lightning','aurora_legacy_of_tempest','zyggy','zyggy_starlight'] as $id)
        $rows[$id] = [['INSTANT',2,false,false,false,0,'Tap and destroy Lightning Flow']];
    foreach (['oscilio_forked_continuum','oscilio_scion_of_the_third_age','third_eye_of_the_sphinx'] as $id)
        $rows[$id] = [['INSTANT',1,false,false,false,0,'Tap and pay token cost']];
    foreach (['cosmic_duality','nebula_duality','voltbound_duality','glide_through_starlight','flowing_stormstrike','meteoric_rise','voltic_impact','path_of_same_ends','step_between'] as $base)
        foreach (['red','yellow','blue'] as $color) $rows[$base.'_'.$color] = [['INSTANT',1,false,false,false,0,'Use instant ability']];
    return $rows;
}
function FaBOMNNeedsPrepare(string $id): bool {
    return in_array($id, ['aurora_emissary_of_lightning','aurora_legacy_of_tempest','zyggy','zyggy_starlight','oscilio_forked_continuum','oscilio_scion_of_the_third_age','third_eye_of_the_sphinx'], true);
}
function FaBOMNSpecialZone(array $f): bool {
    $b = FaBWTRBase($f['object']->CardID);
    return (FaBOMNDiscardAbility($f['object']->CardID) && $f['zone'] === 'Hand') ||
        (in_array($b, ['flowing_stormstrike','meteoric_rise','voltic_impact','path_of_same_ends','step_between'], true) && $f['zone'] === 'CombatChain' && ($f['object']->Role ?? '') === 'ATTACK');
}
function FaBOMNAbilityLegal(int $p, array $f): bool {
    $o = $f['object']; $id = $o->CardID; $b = FaBWTRBase($id);
    if (!isset(FaBOMNAbilityRows()[$id])) return true;
    if($id==='fortitude_of_anvilheim')return FaBOMNHeroReady($p)&&FaBOMNFortitudeChoices($p)!=='';
    if($id==='gauntlet_of_sword_and_sorcery')return FaBOMNHeroReady($p)&&intval($o->Status)===2;
    if($id==='snap_fingers')return FaBOMNOwnLightningAttack($p);
    if($id==='fingers_of_fragmentation')return FaBOMNAttackRefs($p,'fragmented')!=='';
    if (FaBOMNDiscardAbility($id)) return $f['zone'] === 'Hand';
    if (in_array($b, ['flowing_stormstrike','meteoric_rise','voltic_impact','path_of_same_ends','step_between'], true)) {
        if (!FaBOMNSpecialZone($f) || intval($o->UniqueID) !== intval(FaBGetState()['attackUID'])) return false;
        if ($b === 'step_between') return FaBOMNHeroReady($p);
        return $b === 'path_of_same_ends' || FaBOMNCount($p, 'PUMP:'.intval($o->UniqueID)) < 2;
    }
    if (str_contains($id, 'astral_sanctuary') || in_array($id, ['boots_of_omnis_ward','constella_waves'], true)) return FaBOMNHeroReady($p);
    if (FaBOMNNeedsPrepare($id)) {
        if (intval($o->Status) !== 2) return false;
        if ($id === 'third_eye_of_the_sphinx') return count(FaBMONArena($p,'ponder')) > 0;
        return FaBOMNFlows($p) !== '' && (!str_starts_with($id,'zyggy') || FaBOMNBlinkChoices($p) !== '');
    }
    if ($id === 'aphrodias') return intval($o->Status) === 2 && FaBOMNCount($p, 'HOLO_ENTER') > 0;
    if ($id === 'volzar_meteor_storm') return intval($o->Status) === 2 && FaBOMNCount($p, 'STARFALL') > 0;
    if ($id === 'starfield_veil') { foreach (FaBLiveSeats() as $seat) if (FaBOMNCount($seat,'FRAGMENT')) return true; return false; }
    return true;
}
function FaBOMNPaid(int $p, object $o): void {
    $id = $o->CardID; $b = FaBWTRBase($id);
    if (!isset(FaBOMNAbilityRows()[$id])) return;
    if(in_array($id,['fortitude_of_anvilheim','gauntlet_of_sword_and_sorcery'],true)){FaBOMNTapHero($p);if($id==='gauntlet_of_sword_and_sorcery')$o->Status=1;}
    if (FaBOMNDiscardAbility($id)) { $f=FaBFindUID(intval($o->UniqueID)); if ($f) FaBDiscardChoice($p,$f['mzID']); return; }
    if (str_contains($id,'astral_sanctuary') || in_array($id,['boots_of_omnis_ward','constella_waves','step_between_red'],true)) FaBOMNTapHero($p);
    if (FaBOMNNeedsPrepare($id) || in_array($id,['aphrodias','volzar_meteor_storm'],true)) $o->Status=1;
    if (in_array($b,['flowing_stormstrike','meteoric_rise','voltic_impact'],true)) FaBOMNAdd($p,'PUMP:'.intval($o->UniqueID));
}
function FaBOMNStunChoices(int $p): string {
    $hero = FaBFindUID(FaBUPRHeroUID($p));
    return $hero && FaBHasType($hero['object'],'Lightning') ? implode('&',array_merge(FaBChoiceRefs($p,'Hero'),FaBChoiceRefs($p,'Weapons'))) : '';
}
function FaBOMNPrepareBlink(int $stack, int $p, string $ref): void {
    if (!in_array($ref,explode('&',FaBOMNBlinkChoices($p)),true)) return;
    $uid=FaBPENUID($ref); $o=FaBMoveUID($uid,'Banish',$p);
    if ($o && !FaBHasType($o,'Token')) FaBARCSetCard($stack,'omnBlink',$uid);
}
function FaBOMNReturnPreparedBlink(int $p, int $stack): void {
    $uid=intval(FaBARCCard($stack,'omnBlink')); $f=FaBFindUID($uid);
    if (!$f || $f['zone']!=='Banish') return;
    FaBARCSetCard($uid,'omnHoloEntry',true); FaBMoveUID($uid,'Arena',$p);
}
function FaBOMNDiscardPlayable(int $p, string $ref): void {
    $uid=FaBPENUID($ref); FaBDiscardChoice($p,$ref); $f=FaBFindUID($uid);
    if ($f && $f['zone']==='Graveyard' && FaBHasType($f['object'],'Instant')) FaBARCSetCard($uid,'omnPlayableTurn',intval(GetTurnNumber()));
}
function FaBOMNMillPlayable(int $p): void {
    $refs=FaBChoiceRefs($p,'Deck'); if (!$refs) return;
    $uid=FaBPENUID($refs[0]); $o=FaBMoveUID($uid,'Graveyard',$p);
    if ($o && FaBHasType($o,'Instant')) FaBARCSetCard($uid,'omnPlayableTurn',intval(GetTurnNumber()));
}
function FaBOMNPlayable(array $f): bool {
    return $f['zone']==='Graveyard' && FaBARCCard(intval($f['object']->UniqueID),'omnPlayableTurn',-1)===intval(GetTurnNumber());
}
function FaBOMNPanic(int $color): void {
    $chosen=[];
    foreach (FaBLiveSeats() as $p) {
        $f=FaBFindUID(FaBRandomHandUID($p)); if (!$f) continue;
        $chosen[]=[$p,intval($f['object']->UniqueID)];
        FaBRevealChoices($p,$f['mzID']);
    }
    foreach($chosen as [$p,$uid]){
        $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Hand')continue;
        if (FaBMSTObjectColor($p,$f['object'])===$color) FaBDiscardChoice($p,$f['mzID']);
    }
}
function FaBOMNFiltered(int $p, string $zone, array $types): string {
    return implode('&', array_filter(FaBChoiceRefs($p,$zone), function($r) use($types) {
        $o=FaBIdentityFromMZ($r)['object']; foreach ($types as $t) if (!FaBHasType($o,$t)) return false; return true;
    }));
}
function FaBOMNNext(int $p, string $kind, int $power=0, string $tag='', int $count=1): void {
    FaBWTRAddEffect($p,'OMN_NEXT',$power,['kind'=>$kind,'tag'=>$tag,'count'=>$count]);
}
function FaBOMNNextMatch(object $o, string $kind): bool {
    return match($kind) {
        'RL'=> (FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)) && (FaBHasType($o,'Runeblade')||FaBHasType($o,'Lightning')),
        'RLAA'=> FaBWTRIsAttackAction($o) && (FaBHasType($o,'Runeblade')||FaBHasType($o,'Lightning')),
        'DRACONIC'=> (FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)) && FaBHasType($o,'Draconic'),
        'AA'=>FaBWTRIsAttackAction($o), 'ATTACK'=>FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o),
        default=>false
    };
}
function FaBOMNApplyNext(int $p, object $o): void {
    $effects=[];
    foreach (FaBWTREffects($p) as $e) {
        if(($e['type']??'')==='OMN_DRACO_COST'&&FaBOMNNextMatch($o,'DRACONIC'))continue;
        if (($e['type']??'')!=='OMN_NEXT'||!FaBOMNNextMatch($o,$e['kind'])) { $effects[]=$e; continue; }
        if ($e['amount']) FaBWTRTag($o,'WTR_POWER:'.$e['amount']);
        if (!empty($e['tag'])) FaBWTRTag($o,$e['tag']);
        if (--$e['count']>0) $effects[]=$e;
    }
    FaBWTRSetEffects($p,$effects);
}
function FaBOMNHit(int $p, object $o): void {
    $tags=(array)($o->TurnEffects??[]); $uid=intval($o->UniqueID); $victim=intval(FaBGetState()['defender']);
    if (in_array('OMN_FLOW_HIT',$tags,true)) FaBWTRCreateArena($p,'lightning_flow');
    if (FaBFaiHeroHit() && in_array('OMN_LIVEWIRE',$tags,true)) FaBROSQueue($p,'livewire_press_red',$uid,['rosEvent'=>'omnHit','rosTarget'=>$victim]);
    if (FaBFaiHeroHit() && FaBHasType($o,'Axe') && FaBARCEffect($p,'OMN_AXES')) FaBROSQueue($p,'a_bit_off_the_side_red',$uid,['rosTarget'=>$victim]);
    if (FaBFaiHeroHit() && in_array('OMN_SETTLE',$tags,true)) FaBROSQueue($p,'settle_the_bill_red',$uid,['rosTarget'=>$victim]);
    if(in_array('OMN_BECKON',$tags,true))FaBROSQueue($p,'beckon_steel_blue',$uid,['rosEvent'=>'omnHit']);
}
function FaBOMNSourceDamage(int $p, int $v, object $o): void {
    $uid=intval($o->UniqueID); $tags=(array)($o->TurnEffects??[]);
    foreach (['MEMORY'=>'leech_memory_red','RENOWN'=>'leech_renown_red','VITALITY'=>'leech_vitality_red'] as $k=>$id)
        if (in_array('OMN_'.$k,$tags,true)) FaBROSQueue($p,$id,$uid,['rosTarget'=>$v]);
    if (in_array('OMN_MERCURIAL',$tags,true) && !FaBARCCard($uid,'omnMercurial',false)) {
        FaBARCSetCard($uid,'omnMercurial',true); FaBROSQueue($p,'mercurial_skies_red',$uid,['rosTarget'=>$v]);
    }
}
function FaBOMNStealAura(int $p, string $ref): void {
    $f=FaBIdentityFromMZ($ref); if (!$f||!FaBHasType($f['object'],'Aura')||!FaBHasType($f['object'],'Token')) return;
    $old=$f['player']; $uid=intval($f['object']->UniqueID);
    FaBMPGStealAura($p,$ref); FaBWTRAddEffect($p,'OMN_RETURN',1,['uid'=>$uid,'player'=>$old]);
}
function FaBOMNCopyAura(int $p, string $ref): void {
    $f=FaBIdentityFromMZ($ref); if (!$f||$f['player']!==$p||$f['zone']!=='Arena'||!FaBHasType($f['object'],'Aura')) return;
    $o=FaBWTRCreateArena($p,$f['object']->CardID,true);
    if ($o) FaBSetObjectCounter($o,'EVR_TOKEN',1);
}
function FaBOMNScrap(int $p, int $uid, string $ref): void {
    $f=FaBIdentityFromMZ($ref); FaBARCSetCard($uid,'omnCog',$f && FaBHasType($f['object'],'Cog'));
    FaBEVOScrap($p,$uid,$ref);
}
function FaBOMNStormCost(int $p, int $uid, string $ref): void {
    if (!in_array($ref,explode('&',FaBOMNFlows($p)),true)) return;
    FaBMONDestroy(FaBPENUID($ref));
    $s=FaBGetState(); if (intval($s['pendingPayment']['uid']??0)===$uid) {
        $f=FaBFindUID($uid); $s['pendingPayment']['cost']=max(0,intval($s['pendingPayment']['cost'])-intval(CardCost($f['object']->CardID))); FaBSetState($s);
    }
}
function FaBOMNOwnLightningAttack(int $p): bool {
    $s=FaBGetState(); return intval($s['attacker'])===$p && FaBOMNAttackRefs($p,'Lightning')!=='';
}
function FaBOMNEnd(): void {
    foreach (FaBLiveSeats() as $p) foreach (FaBWTREffects($p) as $e) if (($e['type']??'')==='OMN_RETURN') {
        $f=FaBFindUID(intval($e['uid'])); if ($f && $f['zone']==='Arena' && FaBSeatIsLive(intval($e['player']))) FaBMPGStealAura(intval($e['player']),$f['mzID']);
    }
}
function FaBOMNCost(int $p, object $o): int {
    $n=-FaBARCEffect($p,'OMN_CHROMATIC:'.FaBMSTObjectColor($p,$o));
    if (FaBHasType($o,'Instant')) $n-=FaBOMNCount($p,'DISCOUNT:'.intval(FaBGetState()['chainLink']));
    if (FaBOMNNextMatch($o,'DRACONIC')) $n-=FaBARCEffect($p,'OMN_DRACO_COST');
    return $n;
}
function FaBOMNDamageBonus(object $o, int $n): int {
    $uid=intval($o->UniqueID); $bonus=intval(FaBARCCard($uid,'omnChromatic'));
    if ($bonus>0 && $n>0) { FaBARCSetCard($uid,'omnChromatic',0); return $n+$bonus; }
    return $n;
}
function FaBOMNLocked(int $p): bool {
    $a=FaBFindUID(intval(FaBGetState()['attackUID']));
    if ($a && $a['player']!==$p && FaBWTRBase($a['object']->CardID)==='step_between' && !HasNoAbilities($a['object']) && !empty(FaBGetState()['combatOpen'])) return true;
    foreach (GetStack() as $o) if (is_object($o)&&empty($o->removed)&&intval($o->Controller)!==$p&&$o->CardID==='step_between_red'&&($o->Kind??'')==='ATTACK') return true;
    return false;
}
function FaBOMNStart(int $p): void {
    foreach (FaBMONArena($p,'blessing_of_aegis') as $r) FaBMoveUID(FaBPENUID($r),'Soul',$p);
    $refs=FaBChoiceRefs($p,'Graveyard',['base'=>'draco_fire']);
    foreach ($refs as $r) FaBROSQueue($p,'draco_fire_red',FaBPENUID($r),['rosEvent'=>'omnStart']);
}
function FaBOMNDraco(int $p, string $refs): void {
    $uids=FaBUPRUIDs($refs); if (count($uids)!==2) return;
    foreach ($uids as $uid) { $f=FaBFindUID($uid); if (!$f||$f['player']!==$p||$f['zone']!=='Graveyard'||$f['object']->CardID!=='draco_fire_red') return; }
    foreach ($uids as $uid) FaBMoveUID($uid,'Banish',$p);
    AddResources($p,intval(GetResources($p))+1);
}
function FaBOMNSettle(int $p, string $ref): void {
    $f=FaBIdentityFromMZ($ref); if (!$f||$f['player']!==$p||$f['zone']!=='Hand'||!FaBHasType($f['object'],'Arrow')||FaBChoiceRefs($p,'Arsenal')) return;
    $o=FaBMoveUID(intval($f['object']->UniqueID),'Arsenal',$p);
    if ($o) { $o->FaceDown=0; FaBWTRTag($o,'WTR_POWER:3'); FaBWTRTag($o,'OMN_SETTLE'); }
}
function FaBOMNWeaponSource(object $o): int { return intval(FaBObjectCounters($o)['MON_SOURCE_UID']??FaBObjectCounters($o)['WEAPON_UID']??0); }
function FaBOMNVariableBarrier(string $ref): bool {
    $f=FaBIdentityFromMZ($ref); return $f && $f['object']->CardID==='spellbane_sigil_blue' && !HasNoAbilities($f['object']);
}
function FaBOMNOpposingTargets(int $p): string {
    return implode('&',array_filter(explode('&',FaBPENTargets($p)),fn($r)=>($f=FaBIdentityFromMZ($r))&&$f['player']!==$p));
}
function FaBOMNFortitudeChoices(int $p): string {
    if (!FaBPENWeaponAttack()) return '';
    $refs=[]; foreach (FaBLiveSeats() as $seat) foreach (FaBChoiceRefs($seat,'CombatChain',['type'=>'Action']) as $r) {
        $o=FaBIdentityFromMZ($r)['object']; if (in_array($o->Role,['DEFENSE','DEFENSE_REACTION'],true)&&intval($o->ChainLink)===intval(FaBGetState()['chainLink'])) $refs[]=$r;
    }
    return implode('&',$refs);
}
function FaBOMNReturnDefender(string $ref): void {
    $f=FaBIdentityFromMZ($ref); if ($f && in_array($ref,explode('&',FaBOMNFortitudeChoices(intval(FaBGetState()['attacker']))),true)) FaBMoveUID(intval($f['object']->UniqueID),'Hand',intval($f['object']->Owner));
}
function FaBOMNHarpoon(int $p, string $ref): void {
    $f=FaBIdentityFromMZ($ref); if (!$f||$f['zone']!=='Graveyard'||!FaBHasType($f['object'],'Action')||FaBMSTObjectColor($f['player'],$f['object'])!==1) return;
    $o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$f['player']); if (!$o) return;
    $s=FaBGetState();$s['outInfiltrate'][intval($o->UniqueID)]=['player'=>$p,'turn'=>intval(GetTurnNumber())];FaBSetState($s);
}
function FaBOMNSwordAttack(int $p): string {
    $r=FaBOMNAttackRefs($p); $f=FaBIdentityFromMZ($r);return $f && FaBHasType($f['object'],'Sword') ? $r : '';
}
function FaBOMNBeckon(int $p, int $uid): void {
    $f=FaBFindUID($uid);if(!$f)return;$w=FaBFindUID(FaBOMNWeaponSource($f['object']));if(!$w||!FaBHasType($w['object'],'Sword'))return;
    FaBBoltynSharpen($p,$w['mzID']);
    if(intval(FaBObjectCounters($w['object'])['POWER']??0)>=3)FaBARCSetCard($uid,'omnRepeat',intval($w['object']->UniqueID));
}
function FaBOMNRepeatAttack(int $p, int $uid): void {
    $weapon=intval(FaBARCCard($uid,'omnRepeat'));if(!$weapon)return;FaBARCSetCard($uid,'omnRepeat',0);
    $w=FaBFindUID($weapon);if(!$w)return;
    FaBROSQueue($p,'beckon_steel_blue',$weapon,['rosEvent'=>'omnRepeat']);
}
function FaBOMNFreeSword(int $p, int $uid, string $ref): void {
    $w=FaBFindUID($uid);$t=FaBIdentityFromMZ($ref);if(!$w||!$t)return;
    $target=FaBAttackTargetDescriptor($t);if(!FaBResolveAttackTarget($target,$p))return;
    $o=AddStack(CardID:$w['object']->CardID,Controller:$p,Kind:'ATTACK',SourceZone:'Weapons',SourceUniqueID:$uid,Params:['attackTarget'=>$target]);
    FaBSetObjectCounter($o,'WEAPON_UID',$uid);
    FaBWTRCardPlayed($p,'Stack-'.intval($o->mzIndex),$o->CardID,'Weapons');
}
function FaBOMNAttackTargets(int $p): string {
    return implode('&',array_map('FaBAttackTargetMZ',FaBLegalAttackTargets($p)));
}
function FaBOMNSetup(): void {
    $enabled=false;
    foreach(FaBLiveSeats() as $p)foreach(['Arena','Inventory'] as $zone)if(FaBChoiceRefs($p,$zone,['base'=>'omens_of_arcana']))$enabled=true;
    $s=FaBGetState();if(!$enabled||!empty($s['omnMacro']))return;$s['omnMacro']=true;FaBSetState($s);
    foreach(FaBLiveSeats() as $p)FaBWTRCreateArena($p,'lightning_flow');
}
function FaBOMNClose(): void {
    foreach(FaBLiveSeats() as $p){
        foreach(FaBChoiceRefs($p,'Arena',['type'=>'Shuriken']) as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBARCCard(intval($o->UniqueID),'omnShurikenUsed',false))FaBMONDestroy(intval($o->UniqueID));}
        FaBWTRSetEffects($p,array_values(array_filter(FaBWTREffects($p),fn($e)=>!in_array($e['type']??'',['OMN_RAZOR','OMN_UNPREVENTABLE'],true))));
    }
}
function FaBOMNDeclared(int $p, object $o): void {
    if(in_array('OMN_GAUNTLET',(array)($o->TurnEffects??[]),true))FaBROSQueue($p,'gauntlet_of_sword_and_sorcery',intval($o->UniqueID),['rosEvent'=>'omnAttack']);
}
function FaBOMNBlockLegal(object $o): bool {
    $a=FaBFindUID(intval(FaBGetState()['attackUID']));
    return !$a||$a['object']->CardID!=='evasive_nageboshi_blue'||(!FaBHasType($o,'Equipment')&&!FaBHasType($o,'Defense Reaction')&&!FaBHasType($o,'Attack Reaction'));
}
function FaBOMNFinalPower(object $o, int $power): int {
    return $o->CardID==='lionclaw_maul'&&!HasNoAbilities($o)&&$power>intval(CardPower($o->CardID)) ? $power+1 : $power;
}
function FaBOMNResolvedGoAgain(int $p): void {
    if(!FaBUPRFog()&&FaBWTRMayGoAgain($p)){AddActionPoints($p,intval(GetActionPoints($p))+1);FaBROSGo($p);}
}
