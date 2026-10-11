<?php
/** Compendium of Rathe helpers. Choices use absolute references and live seats. */
function FaBPENUID(string $ref): int {
    return intval(FaBIdentityFromMZ($ref)['object']->UniqueID ?? 0);
}

function FaBPENTargets(int $player, bool $heroesOnly = false, bool $multiple = false): string {
    $seats = $multiple ? FaBLiveSeats() : array_unique(array_merge([$player], FaBAdjacentOpponents($player)));
    $refs = [];
    foreach ($seats as $seat) {
        if (!FaBSeatIsLive($seat)) continue;
        $refs = array_merge($refs, FaBChoiceRefs($seat, 'Hero'));
        if (!$heroesOnly) foreach (['Arena', 'Equipment'] as $zone) {
            $refs = array_merge($refs, FaBChoiceRefs($seat, $zone, ['type'=>'Ally']));
        }
    }
    return implode('&', $refs);
}

/** Complete all banishes before applying the second sentence's life losses. */
function FaBPENPound(int $seat, string $chosen): int {
    if (!FaBSeatIsLive($seat)) return 0;
    $found = FaBIdentityFromMZ($chosen);
    if (!$found || $found['player'] !== $seat || $found['zone'] !== 'Hand') return $seat;
    $six = FaBHVYOwnedPower($seat, $found['object']) >= 6;
    $moved = FaBMoveUID(intval($found['object']->UniqueID), 'Banish', $seat);
    return $moved && $six ? 0 : $seat;
}

function FaBPENPoundLosses(int $source, array $losses): void {
    // Apply simultaneous losses before checking eliminations and victory.
    $seats = array_values(array_filter(array_unique(array_map('intval', $losses)), 'FaBSeatIsLive'));
    foreach ($seats as $seat) { AddHealth($seat, intval(GetHealth($seat))-1); FaBMONLifeLost($seat,1); }
    foreach ($seats as $seat) if (GetHealth($seat) <= 0) FaBEliminateSeat($seat, $source);
}

function FaBPENOpenChests(int $player): void {
    $yellow = false;
    foreach (FaBLiveSeats() as $seat) foreach (FaBChoiceRefs($seat, 'Arsenal') as $ref) {
        $o = FaBIdentityFromMZ($ref)['object'];
        $o->FaceDown = 0;
        $yellow = $yellow || FaBMSTObjectColor($seat, $o) === 2;
    }
    if ($yellow) FaBHVYToken($player, 'gold', 2, $player);
}

function FaBPENConcoct(int $uid): void {
    $count = 0;
    foreach (FaBLiveSeats() as $seat) {
        $refs = FaBChoiceRefs($seat, 'Deck');
        if (!$refs) continue;
        // Effects can exceed normal end-phase arsenal capacity.
        $o = FaBMoveUID(FaBPENUID($refs[0]), 'Arsenal', $seat);
        if ($o) { $o->FaceDown = 1; ++$count; }
    }
    if ($count >= 2) FaBTagUID($uid, 'GO_AGAIN');
}

function FaBPENTurnGraves(int $player, string $choices): int {
    $n = 0;
    foreach (array_unique(explode('&', $choices)) as $ref) {
        $f = FaBIdentityFromMZ($ref);
        if (!$f || $f['player'] !== $player || $f['zone'] !== 'Graveyard' || !empty($f['object']->FaceDown) || !FaBHasType($f['object'], 'Ally')) continue;
        $f['object']->FaceDown = 1;
        ++$n;
    }
    return $n;
}

function FaBPENWeaponAttack(): bool {
    $f = FaBFindUID(intval(FaBGetState()['attackUID']));
    return $f && FaBWTRIsWeapon($f['object']);
}

function FaBPENMindMeetsMight(int $seat): void {
    $refs = FaBChoiceRefs($seat, 'Hand');
    FaBRevealChoices($seat, implode('&', $refs));
    $n = 0;
    foreach ($refs as $ref) {
        $f = FaBIdentityFromMZ($ref);
        if ($f && FaBHVYOwnedPower($seat, $f['object']) >= 6) {
            FaBDiscardChoice($seat, $ref);
            ++$n;
        }
    }
    DoDrawCard($seat, $n);
}

function FaBPENRipTop(int $player): void {
    DoDrawCard($player, 1);
    $f = FaBFindUID(FaBRandomHandUID($player));
    if (!$f) return;
    $six = FaBHVYOwnedPower($player, $f['object']) >= 6;
    // A forced random pitch is not the voluntary pitch chooser.
    $o = FaBMoveUID(intval($f['object']->UniqueID), 'Pitch', $player);
    if (!$o) return;
    AddResources($player, intval(GetResources($player))+FaBEVRPitch($player, FaBMONPitchValue($player, $o->CardID), true), 'PITCH');
    FaBMSTPitch($player, $o->CardID, FaBMONPitchValue($player, $o->CardID));
    FaBWTRCardPitched($player, $o->CardID);
    FaBRunSourceMacro('CardPitched', $player, $o->CardID, ['mzID'=>FaBDTDSource(intval($o->UniqueID))]);
    if ($six) FaBWTRAddEffect($player, 'NEXT_ATTACK', 3);
}

function FaBPENSigilCount(int $player): int {
    $n = 0;
    foreach (FaBChoiceRefs($player, 'Arena', ['type'=>'Aura']) as $ref) {
        if (str_contains(CardName(FaBIdentityFromMZ($ref)['object']->CardID), 'Sigil')) ++$n;
    }
    return $n;
}

function FaBPENChainCards(): string {
    $refs = [];
    foreach (FaBLiveSeats() as $seat) $refs = array_merge($refs, FaBChoiceRefs($seat, 'CombatChain'));
    return implode('&', $refs);
}

function FaBPENCopyName(int $uid, string $ref): void {
    $f = FaBIdentityFromMZ($ref);
    if (!$f || $f['zone'] !== 'CombatChain') return;
    FaBARCSetCard($uid, 'penNames', FaBOUTNames($f['object']));
}

function FaBPENColor(int $uid, int $color): void {
    if ($color >= 1 && $color <= 3) FaBARCSetCard($uid, 'penColor', $color);
}

function FaBPENSoulBelief(int $player, int $uid): void {
    $ref = FaBChoiceRefs($player, 'Deck')[0] ?? '';
    $f = FaBIdentityFromMZ($ref);
    if (!$f) return;
    FaBRevealChoices($player, $ref);
    if (FaBMSTObjectColor($player, $f['object']) === 2) {
        FaBMoveUID(intval($f['object']->UniqueID), 'Soul', $player);
        FaBCRUSelfTagUID($uid, 'WTR_POWER:1');
    }
}

function FaBPENBannermen(int $player): int {
    return count(array_filter(FaBChoiceRefs($player, 'Graveyard'), fn($r)=>str_contains(CardName(FaBIdentityFromMZ($r)['object']->CardID), 'Phoenix Bannerman')));
}

function FaBPENFifth(int $player, string $ref): bool {
    $f = FaBIdentityFromMZ($ref);
    if (!$f || $f['player'] !== $player || $f['zone'] !== 'Hand') return false;
    $o = FaBMoveUID(intval($f['object']->UniqueID), 'Deck', $player);
    if (!$o) return false;
    $deck = &GetDeck($player);
    $deck = array_values(array_filter($deck, fn($c)=>is_object($c) && empty($c->removed) && intval($c->UniqueID) !== intval($o->UniqueID)));
    array_splice($deck, min(4, count($deck)), 0, [$o]);
    return true;
}

function FaBPENArcaneDealt(int $player): int {
    return array_sum(FaBGetState()['arcaneDealt'][(string)$player] ?? []);
}

function FaBPENSixDefenders(): int {
    $n = 0;
    foreach (FaBSUPDefenders() as $ref) {
        $f = FaBIdentityFromMZ($ref);
        if (FaBMONDefendingPower($f['player'], $f['object']) >= 6) ++$n;
    }
    return $n;
}

function FaBPENStart(int $player): void {
    foreach(FaBChoiceRefs($player,'Hero') as $r){$o=FaBIdentityFromMZ($r)['object'];$original=FaBARCCard(intval($o->UniqueID),'penEmbodyOriginal','');if($original!==''){$o->CardID=$original;FaBARCSetCard(intval($o->UniqueID),'penEmbodyOriginal','');}}
    foreach(FaBLiveSeats() as $p)foreach(FaBMONArena($p,'haboob') as $ref)FaBROSQueue($p,'haboob_red',FaBPENUID($ref));
    foreach(FaBCRUEquipment($player,'kimono_of_layered_lessons') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(!HasNoAbilities($o))FaBMONDestroy(intval($o->UniqueID));}
    foreach(FaBChoiceRefs($player,'Graveyard') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(in_array($o->CardID,['graven_cowl','graven_vestment','graven_gloves','graven_walkers','seeker_kunai_red'],true)&&count(FaBMONArena($player,'silver'))>=2)FaBRunSourceMacro('StartTurn',$player,$o->CardID,['mzID'=>$ref]);}
    foreach(FaBCRUEquipment($player,'havoc_wrap') as $ref){$o=FaBIdentityFromMZ($ref)['object'];if(!empty(FaBObjectCounters($o)['PEN_HAVOC']))FaBMONDestroy(intval($o->UniqueID));}
    foreach (FaBChoiceRefs($player, 'Arena') as $ref) {
        $o = FaBIdentityFromMZ($ref)['object'];
        if (HasNoAbilities($o)) continue;
        if($o->CardID==='assembly_module_blue'){if(intval(FaBObjectCounters($o)['STEAM']??0)>0)FaBARCSteam($o,-1);else FaBMONDestroy(intval($o->UniqueID));}
        if($o->CardID==='channel_galcias_cradle_blue')FaBROSQueue($player,$o->CardID,intval($o->UniqueID));
        if(in_array($o->CardID,['blessing_of_bellona_yellow','blessing_of_themis_yellow'],true)){FaBMoveUID(intval($o->UniqueID),'Soul',$player);continue;}
        if (in_array(FaBWTRBase($o->CardID), ['distant_rumbling', 'rites_of_earthlore'], true)) {
            FaBRunSourceMacro('StartTurn', $player, $o->CardID, ['mzID'=>$ref]);
        }
    }
}

function FaBPENPermanents(int $player, string $type = '', bool $token = false, bool $opposing = false, bool $targeted = true): string {
    $refs = [];
    $seats=$targeted?array_values(array_unique(array_merge([$player],FaBAdjacentOpponents($player)))):FaBLiveSeats();
    if($opposing)$seats=array_values(array_diff($seats,[$player]));
    foreach ($seats as $seat) {
        foreach (['Arena', 'Equipment', 'Weapons'] as $zone) foreach (FaBChoiceRefs($seat, $zone) as $ref) {
            $o = FaBIdentityFromMZ($ref)['object'];
            if (($type === '' || FaBHasType($o, $type)) && (!$token || FaBHasType($o, 'Token'))) $refs[] = $ref;
        }
    }
    return implode('&', $refs);
}

function FaBPENRemoveEnergy(string $ref): int {
    $f = FaBIdentityFromMZ($ref);
    if (!$f) return 0;
    $n = max(0, intval(FaBObjectCounters($f['object'])['ENERGY'] ?? 0));
    FaBSetObjectCounter($f['object'], 'ENERGY', 0);
    return $n;
}

function FaBPENPitchedSix(int $player): bool {
    return FaBARCEffect($player, 'PEN_PITCHED_SIX') > 0;
}

function FaBPENPitchGrave(int $player, string $ref): void {
    $f = FaBIdentityFromMZ($ref);
    if (!$f || $f['player'] !== $player || $f['zone'] !== 'Graveyard' || FaBMSTObjectColor($player, $f['object']) !== 3) return;
    $o = FaBMoveUID(intval($f['object']->UniqueID), 'Pitch', $player);
    if (!$o) return;
    AddResources($player, intval(GetResources($player))+FaBEVRPitch($player, FaBMONPitchValue($player, $o->CardID), true), 'PITCH');
    FaBMSTPitch($player, $o->CardID, FaBMONPitchValue($player, $o->CardID));
    FaBWTRCardPitched($player, $o->CardID);
    FaBRunSourceMacro('CardPitched', $player, $o->CardID, ['mzID'=>FaBDTDSource(intval($o->UniqueID))]);
}

function FaBPENPrevent(int $player, int $amount): int {
    if ($amount <= 0) return $amount;
    $effects = [];
    foreach (FaBWTREffects($player) as $effect) {
        if (($effect['type'] ?? '') === 'PEN_NEXT_DAMAGE' && $amount > 0) {
            $amount = max(0, $amount-intval($effect['amount']));
        } else $effects[] = $effect;
    }
    FaBWTRSetEffects($player, $effects);
    return $amount;
}

function FaBPENHit(int $player): void {
    $attack=FaBFindUID(intval(FaBGetState()['attackUID']));
    if($attack && FaBFaiHeroHit()) {
        $s=FaBGetState();$victim=intval($s['defender']);$tags=(array)$attack['object']->TurnEffects;
        if(in_array('PEN_SENSE',$tags,true))foreach(FaBChoiceRefs($victim,'Arena',['type'=>'Ally']) as $r)FaBUPRDeal($player,intval($attack['object']->UniqueID),FaBPENUID($r),intval($s['damageDealt']),'PHYSICAL');
        if(in_array('PEN_SMOLDER',$tags,true))DoDamage($player,$attack['mzID'],$victim,1,'PHYSICAL');
        foreach(['concealed_nerve_gas'=>'frailty','concealed_pathogen'=>'bloodrot_pox','concealed_sedative'=>'inertia'] as $id=>$token)foreach(FaBChoiceRefs($victim,'Equipment',['base'=>$id]) as $r){
            $o=FaBIdentityFromMZ($r)['object'];if(!FaBMSTHidden($o))continue;
            $active=match($id){'concealed_nerve_gas'=>FaBPENAttackGoAgain(),'concealed_sedative'=>FaBPENAttackDelta()>0,default=>!empty($s['outReactions'][GetTurnNumber().':'.$s['chainLink'].':'.$player])};
            if($active){FaBMONDestroy(intval($o->UniqueID));foreach(FaBOpponents($victim) as $p)FaBHVYToken($p,$token,1,$victim);}
        }
    }
    if($attack && FaBHasType($attack['object'],'Dagger'))FaBWTRAddEffect($player,'PEN_DAGGER_HIT',1);
    if($attack && FaBFaiHeroHit() && FaBHasType($attack['object'],'Sword'))for($i=0;$i<FaBARCEffect($player,'PEN_REND');++$i)FaBROSQueue($player,'rend_flesh_blue',intval($attack['object']->UniqueID),['rosTarget'=>intval(FaBGetState()['defender'])]);
    $might = 0;
    $effects = [];
    foreach (FaBWTREffects($player) as $e) {
        if (($e['type'] ?? '') === 'PEN_HIT_MIGHT') $might += intval($e['amount']);
        else $effects[] = $e;
    }
    FaBWTRSetEffects($player, $effects);
    if ($might) FaBHVYToken($player, 'might', $might, $player);
}

function FaBPENAfterMove(int $player, object $o, string $from, string $to, ?object $source = null): void {
    if($from==='Banish'&&$to!=='Banish'){
        FaBARCSetCard(intval($o->UniqueID),'penPlayPermission',[]);FaBARCSetCard(intval($o->UniqueID),'penBoostTurn',-1);FaBARCSetCard(intval($o->UniqueID),'penMirageChain',-1);
    }
    if(in_array($from,['Arena','Equipment','Weapons','CombatChain'],true)&&!in_array($to,['Arena','Equipment','Weapons','CombatChain'],true))FaBARCSetCard(intval($o->UniqueID),'penFreezeSources',[]);
    if($from==='Arena'&&$to!=='Arena'&&$o->CardID==='channel_galcias_cradle_blue'){
        foreach(FaBLiveSeats() as $seat)foreach(['Arena','Equipment','Weapons','Arsenal','CombatChain'] as $zone)foreach(FaBChoiceRefs($seat,$zone) as $r){$uid=FaBPENUID($r);$sources=(array)FaBARCCard($uid,'penFreezeSources',[]);if(in_array(intval($o->UniqueID),$sources,true))FaBARCSetCard($uid,'penFreezeSources',array_values(array_diff($sources,[intval($o->UniqueID)])));}
    }
    if($from==='CombatChain' && $to!=='CombatChain'){$original=FaBARCCard(intval($o->UniqueID),'penLunarOriginal','');if($original!==''){$o->CardID=$original;FaBARCSetCard(intval($o->UniqueID),'penLunarOriginal','');}}
    if($to==='Arena' && $from!=='Arena' && $o->CardID==='bubba_lubba_run_aground_yellow')FaBSetObjectCounter($o,'POWER',1);
    if($from==='' && $to==='Arena' && FaBHasType($o,'Aura'))FaBWTRAddEffect($player,'PEN_AURAS',1);
    if($to==='Equipment' && $from!=='Graveyard' && in_array($o->CardID,['graven_cowl','graven_vestment','graven_gloves','graven_walkers'],true))FaBSetObjectCounter($o,'DEFENSE',intval(FaBObjectCounters($o)['DEFENSE']??0)+1);
    if($to==='Arena' && $from!=='Arena' && $o->CardID==='assembly_module_blue')FaBSetObjectCounter($o,'STEAM',1);
    $uid = intval($o->UniqueID);
    if (!in_array($to, ['Stack', 'CombatChain'], true)) {
        // Name and color changes last only for this object on the stack/chain.
        $s = FaBGetState();
        unset($s['arcCards'][(string)$uid]['penNames'], $s['arcCards'][(string)$uid]['penColor']);
        FaBSetState($s);
    }
    if ($to === 'Pitch' && FaBHVYOwnedPower($player, $o) >= 6) FaBWTRAddEffect($player, 'PEN_PITCHED_SIX', 1);
    if ($to === 'Graveyard' && FaBHasType($o, 'Ally')) FaBWTRAddEffect($player, 'PEN_ALLY_GRAVE', 1);
    if ($from === '') {FaBWTRAddEffect($player, 'PEN_CREATED', 1);if($o->CardID==='seismic_surge')FaBWTRAddEffect($player,'PEN_SURGE_CREATED',1);}
    $b = FaBWTRBase($o->CardID);
    if ($to === 'Banish') foreach(FaBLiveSeats() as $seat) foreach(FaBMONArena($seat,'blessing_of_themis') as $r) {
        if(FaBARCCard(FaBPENUID($r),'penThemisName','')===CardName($o->CardID)){$o->FaceDown=1;$o->PlayableFromBanish=0;}
    }
    if ($to === 'Soul') {if(FaBMSTObjectColor($player,$o)===2)FaBWTRAddEffect($player,'PEN_YELLOW_SOUL',1);foreach(FaBMONArena($player,'blessing_of_bellona') as $r)FaBHVYToken($player,'courage',1,$player);}
    if ($to === 'Graveyard' && $from === 'CombatChain' && $source && $o->CardID==='beneath_the_surface_yellow' && in_array($source->Role??'',['DEFENSE','DEFENSE_REACTION'],true))$o->FaceDown=1;
    if ($from === 'Graveyard' && $to !== 'Graveyard' && $source && FaBHasType($source,'Aura')) {
        foreach(FaBMONArena($player,'sigil_of_gravespawning') as $r)FaBROSQueue($player,'sigil_of_gravespawning_blue',FaBPENUID($r));
    }
    if ($to === 'Arena' && $from !== 'Arena' && in_array($b,['sigil_of_voltaris','sigil_of_silphidae'],true) && !HasNoAbilities($o)) FaBROSQueue($player, $o->CardID, $uid);
    if (!$source || !in_array($from, ['Arena', 'Equipment', 'Weapons', 'CombatChain'], true) || in_array($to, ['Arena', 'Equipment', 'Weapons', 'CombatChain'], true) || HasNoAbilities($source)) return;
    $controller = intval($source->Controller ?? $player) ?: $player;
    if ($b === 'toughness') FaBWTRAddEffect($controller, 'PEN_TOUGHNESS_LEFT', 1);
    if (in_array($b, ['sigil_of_fate', 'sigil_of_voltaris', 'sigil_of_silphidae', 'robe_of_resourcefulness', 'shroud_of_the_fate_watcher', 'tempest_dancers', 'gloves_of_erasure'], true)) FaBROSQueue($controller, $o->CardID, $uid);
    if ($b === 'shimmering_specter' && $from === 'CombatChain') FaBHVYToken($controller, 'spectral_shield', 1, $controller);
}

function FaBPENDestroyed(int $player, object $o): void {
    if (!HasNoAbilities($o) && in_array($o->CardID, ['silken_shawl','silken_shroud','silken_symphony','silken_slippers'], true)) {
        FaBROSQueue($player, $o->CardID, intval($o->UniqueID));
    }
}

function FaBPENActionPhase(int $player): void {
    $uid=FaBUPRHeroUID($player);if(FaBARCCard($uid,'penLobotomy',false)){FaBTagUID($uid,'NO_ABILITIES');FaBARCSetCard($uid,'penLobotomy',false);}
    foreach(FaBMONArena($player,'channel_the_skybreaker') as $ref)FaBROSQueue($player,'channel_the_skybreaker_yellow',FaBPENUID($ref));
    foreach (FaBChoiceRefs($player, 'Arena') as $ref) {
        $o = FaBIdentityFromMZ($ref)['object'];
        if (!HasNoAbilities($o) && in_array(FaBWTRBase($o->CardID), ['sigil_of_fate','sigil_of_voltaris','sigil_of_silphidae','sigil_of_gravespawning','by_the_book','leave_em_speechless'], true)) FaBMONDestroy(intval($o->UniqueID));
    }
}

function FaBPENCanDraw(): bool {
    if (GetCurrentPhase() !== 'MAIN' || !empty(FaBGetState()['dtdStarting']) || !empty(FaBGetState()['endingTurn'])) return true;
    foreach (FaBLiveSeats() as $seat) if (FaBMONArena($seat, 'by_the_book')) return false;
    return true;
}

function FaBPENCanPlay(int $player, array $found): bool {
    if (FaBWTRBase($found['object']->CardID) === 'submerge' && count(FaBChoiceRefs($player, 'Hand')) <= ($found['zone'] === 'Hand' ? 1 : 0)) return false;
    if ($found['zone'] !== 'Hand') return true;
    foreach (FaBLiveSeats() as $seat) foreach (FaBMONArena($seat, 'leave_em_speechless') as $ref) {
        if (FaBARCCard(FaBPENUID($ref), 'penProhibitedName', '') === CardName($found['object']->CardID)) return false;
    }
    return true;
}

function FaBPENAsInstant(int $player, object $o): bool {
    if (HasNoAbilities($o)) return false;
    return match ($o->CardID) {
        'by_the_book_blue'=>FaBSUPAllLife($player, true),
        'leave_em_speechless_blue'=>FaBSUPAllLife($player, false),
        'strike_twice_red'=>array_sum(array_intersect_key(FaBGetState()['arcaneDealt'][(string)$player] ?? [], array_flip(FaBOpponents($player)))) > 0,
        default=>false,
    };
}

function FaBPENInflation(): int {
    $n = 0;
    foreach (FaBLiveSeats() as $seat) $n += FaBARCEffect($seat, 'PEN_INFLATION');
    return $n;
}

function FaBPENPower(int $player, object $o): int {
    $n = FaBWTRIsAttackAction($o) && FaBHasType($o, 'Draconic') ? FaBARCEffect($player, 'PEN_DRACONIC_POWER') : 0;
    if (FaBMSTObjectColor($player,$o) === 3 || FaBHasKeyword($o,'Ephemeral')) $n += FaBARCEffect($player,'PEN_WHISPER');
    if(FaBWTRIsAttackAction($o))$n+=FaBPENCombatPower($player,$o);
    if ((FaBHasType($o,'Lightning') || FaBHasType($o,'Elemental')) && intval($o->UniqueID??0)===intval(FaBGetState()['attackUID']) && FaBAttackHasGoAgain(FaBGetState(),$o)) {
        foreach(FaBLiveSeats() as $seat)$n+=FaBARCEffect($seat,'PEN_ION');
    }
    return $n;
}

function FaBPENAbilityRows(): array {
    return [
        'stormweavers_aegis'=>[['INSTANT',0,true,false,false,0,'Grant instant discard prevention']],
        'bubba_lubba_run_aground_yellow'=>[['ACTION',0,false,true,false,0,'Remove ally power counter to destroy aura token']],
        'topsy_turvy'=>[['INSTANT',0,true,false,false,0,'Put top-deck cards on the bottom instead']],
        'tigrine_reflex_red'=>[['REACTION',0,false,false,false,0,'Discard to empower Ninja attack']],
        'herald_of_victoria_yellow'=>[['INSTANT',0,false,false,false,0,'Discard to weaken opposing attack actions']],
        'rippling_wave'=>[['INSTANT',3,false,false,false,0,'Turn face up to return blue defender']],
        'kimono_of_layered_lessons'=>[['INSTANT',3,false,false,false,0,'Turn face up to gain defense counter']],
        'synapse_sparkcap'=>[['ACTION',0,false,false,false,0,'Banish Evo to create Ponder']],
        'mbrio_base_digits'=>[['INSTANT',0,false,false,false,0,'Tap cog to gain defense']],
        'runebleed_robe'=>[['INSTANT',0,true,false,false,0,'Destroy Runechant to prevent arcane damage']],
        'carrion_crown'=>[['ACTION',0,true,true,false,0,'Discard ally to draw']],
        'dyed_silk_sleeves'=>[['REACTION',1,false,false,false,0,'Destroy dagger to empower Ninja attack']],
        'graven_gaslight'=>[['INSTANT',0,false,false,false,0,'Destroy two Silver to equip']],
        'seeker_kunai_red'=>[['REACTION',1,true,false,false,0,'Empower Assassin attack']],
        'assembly_module_blue'=>[['ACTION',0,false,false,false,0,'Find Hyper Driver']],
        'touch_of_reality'=>[['INSTANT',0,false,false,false,0,'Gain ward X']],
        'beckoning_haunt'=>[['ACTION',1,true,false,false,0,'Return aura with cost X']],
        'wind_cutter'=>[['REACTION',1,false,false,false,0,'Find a Shuriken']],
        'farflight_longbow'=>[['INSTANT',1,false,false,false,0,'Load arrow']],
        'boltn_boots'=>[['REACTION',1,true,false,false,0,'Give empowered arrow go again']],
        'crown_of_everbloom'=>[['INSTANT',0,true,false,false,0,'Cycle arsenal and create Spellbane Aegis']],
        'grimoire_of_fellingsong'=>[['INSTANT',1,true,false,false,0,'Create a Runechant']],
        'reach_beyond_the_grave'=>[['ACTION',0,true,true,false,0,'Return ally then discard']],
        'double_cross_strap'=>[['INSTANT',0,true,false,false,0,'Gain a resource']],
        'predatory_plating'=>[['INSTANT',0,true,false,false,0,'Gain a resource']],
        'two_steps_forward'=>[['INSTANT',0,true,false,false,0,'Create Agility']],
        'voltic_vanguard'=>[['INSTANT',0,true,false,false,0,'Prevent two damage']],
        'templar_spellbane'=>[['INSTANT',0,true,false,false,0,'Prevent arcane damage']],
        'shattering_grasp'=>[['ACTION',0,true,true,false,0,'Destroy frozen ally']],
        'scuttle_toes'=>[['INSTANT',2,true,false,false,0,'Untap ally until end phase']],
        'unflinching_foothold'=>[['INSTANT',0,true,false,false,0,'Remove dominate']],
        'burnished_bunkerplate'=>[['DEFENSE_REACTION',0,true,false,false,0,'Defend with arsenal action']],
        'havoc_wrap'=>[['ACTION',0,false,true,false,0,'Tap to reduce card costs']],
        'myrkhellir_helm'=>[['ACTION',2,true,true,false,0,'Draw twice from next Gold']],
    ];
}

function FaBPENRecordPitch(int $player, object $pitched): void {
    $pending = FaBGetState()['pendingPayment'];
    if (!is_array($pending) || intval($pending['player'] ?? 0) !== $player) return;
    $uid = intval($pending['uid']);
    $types = (array)FaBARCCard($uid,'penPitchTypes',[]);
    FaBARCSetCard($uid,'penPitchTypes',array_values(array_unique(array_merge($types,EffectiveCardType($pitched)))));
    if(FaBHVYOwnedPower($player,$pitched)>=6)FaBARCSetCard($uid,'penPaidSix',true);
}

function FaBPENBond(int $uid, string $element): bool {
    return in_array($element,(array)FaBARCCard($uid,'penPitchTypes',[]),true);
}

function FaBPENElementalStrike(int $player, int $uid, string $ref): void {
    $f=FaBIdentityFromMZ($ref);
    if(!$f || $f['player']!==$player || $f['zone']!=='Hand') return;
    $types=EffectiveCardType($f['object']);
    if(!FaBMoveUID(intval($f['object']->UniqueID),'Banish',$player))return;
    if(in_array('Earth',$types,true))FaBCRUSelfTagUID($uid,'WTR_POWER:2');
    if(in_array('Lightning',$types,true))FaBTagUID($uid,'GO_AGAIN');
    if(in_array('Ice',$types,true))FaBTagUID($uid,'DOMINATE');
}

function FaBPENFreeze(int $player, string $refs): void {
    foreach(array_unique(explode('&',$refs)) as $ref) {
        $f=FaBIdentityFromMZ($ref);
        if($f && FaBHasType($f['object'],'Ally') && in_array($f['zone'],['Arena','Equipment'],true))FaBUPRFreeze($player,$ref);
    }
}

function FaBPENFreezeHero(int $player): void {
    foreach(FaBChoiceRefs($player,'Hero') as $ref) FaBUPRFreeze($player,$ref);
}

function FaBPENFrozen(int $player, string $type): string {
    return implode('&',array_filter(explode('&',FaBPENPermanents($player,$type)),fn($r)=>($f=FaBIdentityFromMZ($r)) && FaBUPRFrozen($f['object'])));
}

function FaBPENDecomposeActions(int $player, string $earth): string {
    return implode('&',array_diff(FaBChoiceRefs($player,'Graveyard',['type'=>'Action']),explode('&',$earth)));
}

function FaBPENFrostSpike(int $player, string $options, string $choice): void {
    $label=explode('&',$options)[intval($choice)]??'';
    if(!in_array($label,explode('&',FaBMPGJarlOptions($player)),true))return;
    FaBMPGJarl($player,$label);
}

function FaBPENWeaponActivated(int $player): bool {
    return FaBARCEffect($player,'PEN_WEAPON_ACTIVATED')>0;
}

function FaBPENAbilityLegal(int $player, array $f): bool {
    $id=$f['object']->CardID;
    if($id==='bubba_lubba_run_aground_yellow'&&FaBPENPoweredAllies($player)==='')return false;
    if(in_array($id,['tigrine_reflex_red','herald_of_victoria_yellow'],true)&&$f['zone']!=='Hand')return false;
    if($id==='tigrine_reflex_red'&&FaBPENNinjaAttack($player)==='')return false;
    if(in_array($id,['rippling_wave','kimono_of_layered_lessons'],true)&&!FaBMSTHidden($f['object']))return false;
    if(in_array($id,['synapse_sparkcap','mbrio_base_digits','dyed_silk_sleeves','assembly_module_blue','touch_of_reality'],true)&&!FaBSEACanTap($f['object']))return false;
    if(in_array($id,['synapse_sparkcap','mbrio_base_digits','runebleed_robe','carrion_crown','dyed_silk_sleeves'],true)&&FaBPENCostRefs($player,$id)==='')return false;
    if($id==='graven_gaslight'&&($f['zone']!=='Graveyard'||count(FaBMONArena($player,'silver'))<2))return false;
    if($id==='seeker_kunai_red'&&FaBDYNAttacks($player,'ASSASSIN')==='')return false;
    if($id==='dyed_silk_sleeves'&&FaBPENNinjaAttack($player,true)==='')return false;
    if(in_array($f['object']->CardID,['wind_cutter','farflight_longbow'],true) && !FaBSEACanTap($f['object']))return false;
    if($f['object']->CardID==='wind_cutter' && intval(FaBGetState()['chainHits']??0)<2)return false;
    if($f['object']->CardID==='farflight_longbow' && (!FaBELEArsenalSpace($player)||!FaBChoiceRefs($player,'Hand',['type'=>'Arrow'])))return false;
    if($f['object']->CardID==='boltn_boots' && FaBPENPoweredArrow($player)==='')return false;
    $id=$f['object']->CardID;
    if($id==='havoc_wrap')return intval($f['object']->Status)===2 && empty(FaBObjectCounters($f['object'])['PEN_HAVOC']);
    if(in_array($id,['double_cross_strap','two_steps_forward'],true)) {
        $s=FaBGetState();return intval($s['attacker'])===$player && intval($s['chainHits']??0)>=2 && !empty($s['combatOpen']);
    }
    if($id==='voltic_vanguard')return count(array_filter(FaBARCPlayed($player),fn($id)=>FaBHasType($id,'Instant')))>0;
    if($id==='predatory_plating') {
        foreach(['Hero','Equipment','Weapons','Arena','CombatChain'] as $z) foreach(FaBChoiceRefs($player,$z) as $r) {
            $o=FaBIdentityFromMZ($r)['object'];
            $power=($z==='CombatChain' && ($o->Role??'')==='ATTACK' && intval($o->UniqueID)===intval(FaBGetState()['attackUID']))?FaBAttackPower(FaBGetState()):FaBHVYOwnedPower($player,$o)+intval(FaBObjectCounters($o)['POWER']??0);
            if($power>=6)return true;
        }
        return false;
    }
    if($id==='shattering_grasp')return FaBPENFrozen($player,'Ally')!=='';
    if($id==='unflinching_foothold')return FaBFindUID(intval(FaBGetState()['attackUID']))!==null;
    return true;
}

function FaBPENBase(int $player, object $o, int $base): int {
    if(FaBPENShoesActive($player)&&FaBWTRIsAttackAction($o))$base=intval(ceil($base/2));
    if(in_array('PEN_BASE_SIX',(array)($o->TurnEffects??[]),true))$base=6;
    if(($o->Role??'')==='ATTACK')foreach(FaBChoiceRefs($player,'CombatChain',['base'=>'gentle_breeze']) as $r){$b=FaBIdentityFromMZ($r)['object'];if(!HasNoAbilities($b) && ($b->Role??'')==='ATTACK' && intval($b->UniqueID)!==intval($o->UniqueID))$base=1;}
    if(HasNoAbilities($o))return $base;
    if($o->CardID==='tough_as_a_rok_blue')return FaBSUPAllLife($player,true)?6:0;
    if($o->CardID==='rockyard_rodeo_blue') {
        $base=0;
        foreach(['Weapons','CombatChain'] as $z)foreach(FaBChoiceRefs($player,$z) as $r){$w=FaBIdentityFromMZ($r)['object'];if(FaBWTRIsWeapon($w))$base=max($base,intval(CardPower($w->CardID)));}
    }
    return $base;
}

function FaBPENHighTide(int $player): bool {
    return count(FaBChoiceRefs($player,'Pitch',['pitch'=>3]))>=2;
}

function FaBPENSwordCounters(int $player): int {
    $n=0;
    foreach(['Weapons','CombatChain'] as $z)foreach(FaBChoiceRefs($player,$z,['type'=>'Sword']) as $r){$o=FaBIdentityFromMZ($r)['object'];$n+=max(0,intval(FaBObjectCounters($o)['POWER']??0));}
    return $n;
}

function FaBPENBloodloss(int $player, int $victim): void {
    $r=FaBChoiceRefs($victim,'Deck')[0]??'';$f=FaBIdentityFromMZ($r);
    if(!$f)return;
    $red=FaBMSTObjectColor($victim,$f['object'])===1;
    FaBDYNBanishTop($player,$victim,1);
    if($red)FaBDYNBanishTop($player,$victim,1);
}

function FaBPENLightenChoices(int $player): string {
    return implode('&',array_merge(FaBChoiceRefs($player,'Hand'),FaBChoiceRefs($player,'Arena',['type'=>'Item'])));
}

function FaBPENLighten(int $player, string $ref): bool {
    if(!in_array($ref,explode('&',FaBPENLightenChoices($player)),true))return false;
    $f=FaBIdentityFromMZ($ref);
    if($f['zone']==='Hand')FaBDiscardChoice($player,$ref);else FaBMONDestroy(intval($f['object']->UniqueID));
    return true;
}

function FaBPENEmbalm(int $player): string {
    return implode('&',array_filter(FaBChoiceRefs($player,'Graveyard',['attackAction'=>true]),fn($r)=>FaBHasKeyword(FaBIdentityFromMZ($r)['object'],'Blood Debt')));
}

function FaBPENOpposingGraves(int $player, string $kind): string {
    $refs=[];
    foreach(FaBAdjacentOpponents($player) as $seat)$refs=array_merge($refs,FaBChoiceRefs($seat,'Graveyard',$kind==='Yellow'?['pitch'=>2]:['type'=>$kind]));
    return implode('&',$refs);
}

function FaBPENBanishRef(string $ref): void {
    $f=FaBIdentityFromMZ($ref);
    if($f && $f['zone']==='Graveyard')FaBMoveUID(intval($f['object']->UniqueID),'Banish',$f['player']);
}

function FaBPENRemoveCounters(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f)return;
    // Counters JSON also holds permissions, ownership and attachments, not counters.
    foreach(['POWER','DEFENSE','STEAM','ENERGY','FLOW','DOOM','VERSE','BALANCE','SUSPENSE','FROST','RAZE','ENDURANCE','LESSON','AIM','HAUNT','BIND','STORM'] as $key) {
        if(array_key_exists($key,FaBObjectCounters($f['object'])))FaBSetObjectCounter($f['object'],$key,0);
    }
}

function FaBPENSigils(int $player): string {
    return implode('&',array_filter(explode('&',FaBPENPermanents($player,'Aura')),fn($r)=>($f=FaBIdentityFromMZ($r)) && str_contains(CardName($f['object']->CardID),'Sigil')));
}

function FaBPENOtherAuras(int $player, int $uid): string {
    return implode('&',array_filter(FaBChoiceRefs($player,'Graveyard',['type'=>'Aura']),fn($r)=>FaBPENUID($r)!==$uid));
}

function FaBPENThemis(int $uid, string $name): void {
    FaBARCSetCard($uid,'penThemisName',$name);
    foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'Banish') as $r){$o=FaBIdentityFromMZ($r)['object'];if(CardName($o->CardID)===$name){$o->FaceDown=1;$o->PlayableFromBanish=0;}}
}

function FaBPENCreatedCount(int $player, int $count): int {
    if($count<=0)return $count;
    $left=[];
    foreach(FaBWTREffects($player) as $e){if(($e['type']??'')==='PEN_EXTRA_EPHEMERAL')$count+=intval($e['amount']);else $left[]=$e;}
    FaBWTRSetEffects($player,$left);FaBWTRAddEffect($player,'PEN_CREATED',$count);
    return $count;
}

function FaBPENPlayed(int $player, object $o, string $from): void {
    if(FaBHasType($o,'Aura'))foreach(FaBCRUEquipment($player,'magmatic_carapace') as $r){$e=FaBIdentityFromMZ($r)['object'];if(!HasNoAbilities($e))FaBROSQueue($player,'magmatic_carapace',intval($e->UniqueID));}
    if(FaBHasType($o,'Aura'))FaBWTRAddEffect($player,'PEN_AURAS',1);
    if(FaBWTRIsAttackAction($o)&&FaBHasKeyword($o,'Ephemeral'))for($i=0;$i<FaBARCEffect($player,'PEN_SHAPELESS');++$i)FaBROSQueue($player,'shapeless_form_blue',intval($o->UniqueID),['rosTarget'=>intval($o->UniqueID)]);
    if($from==='Weapons')FaBWTRAddEffect($player,'PEN_WEAPON_ACTIVATED',1);
    if(FaBHasType($o,'Instant'))FaBWTRAddEffect($player,'PEN_INSTANT_CHAIN',1);
    $weapon=intval(FaBObjectCounters($o)['WEAPON_UID']??0);
    if($weapon && FaBARCCard($weapon,'penSwordDominateTurn',-1)===intval(GetTurnNumber())){FaBWTRTag($o,'DOMINATE');FaBARCSetCard($weapon,'penSwordDominateTurn',-1);}
    $left=[];
    foreach(FaBWTREffects($player) as $e){
        $type=$e['type']??'';
        if($type==='PEN_CHEAT_NEXT' && FaBWTRIsAttackAction($o)){FaBWTRTag($o,'WTR_POWER:3');FaBWTRTag($o,'PEN_CHEAT_WAGER');}
        elseif($type==='PEN_SENSE' && FaBHasType($o,'Guardian') && (FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o))){FaBWTRTag($o,'WTR_POWER:1');FaBWTRTag($o,'DOMINATE');FaBWTRTag($o,'PEN_SENSE');}
        elseif($type==='PEN_NEXT_COLOR_GO' && FaBHasType($o,'Action') && FaBMSTObjectColor($player,$o)===intval($e['amount']))FaBWTRTag($o,'GO_AGAIN');
        elseif($type==='PEN_NEXT_ATTACK_GO' && (FaBWTRIsAttackAction($o)||FaBWTRIsWeapon($o)))FaBWTRTag($o,'GO_AGAIN');
        elseif($type==='PEN_NEXT_BASE_SIX' && FaBWTRIsAttackAction($o))FaBWTRTag($o,'PEN_BASE_SIX');
        else $left[]=$e;
    }
    FaBWTRSetEffects($player,$left);
    if($from==='Banish' && $o->CardID==='embalm_yellow')FaBWTRTag($o,'GO_AGAIN');
}

function FaBPENPandemonium(int $player): void {
    foreach(FaBLiveSeats() as $seat){
        $ref=FaBChoiceRefs($seat,'Deck')[0]??'';$uid=FaBPENUID($ref);if(!$uid)continue;
        $o=FaBMoveUID($uid,'Banish',$seat);
        if($o && empty($o->FaceDown))FaBARCSetCard($uid,'penPlayPermission',['player'=>$player,'turn'=>intval(GetTurnNumber())]);
    }
}

function FaBPENCanPlayBanished(int $player, array $f): bool {
    if($f['zone']!=='Banish'||!empty($f['object']->FaceDown))return false;
    $permission=FaBARCCard(intval($f['object']->UniqueID),'penPlayPermission',[]);
    if($f['player']===$player && $f['object']->CardID==='shimmering_mirage_blue' && !empty(FaBGetState()['combatOpen']) && FaBARCCard(intval($f['object']->UniqueID),'penMirageChain',-1)===intval(GetTurnNumber()))return true;
    if($f['player']===$player && in_array($f['object']->CardID,['ghost_protocol_architect_red','ghost_protocol_mainframe_blue'],true) && FaBARCCard(intval($f['object']->UniqueID),'penBoostTurn',-1)===intval(GetTurnNumber()))return true;
    return intval($permission['player']??0)===$player && intval($permission['turn']??-1)===intval(GetTurnNumber());
}

function FaBPENPreviewRefs(array $uids): string {
    return implode('&',array_filter(array_map(fn($u)=>FaBDTDSource(intval($u)),$uids)));
}

function FaBPENDiscardPreview(int $victim, string $ref, array $previews): void {
    $uid=FaBPENUID($ref);
    if(in_array($uid,$previews,true)){
        $original=FaBFindUID(intval(FaBARCCard($uid,'dynOriginal')));
        if($original && $original['player']===$victim && $original['zone']==='Hand')FaBDiscardChoice($victim,$original['mzID']);
    }
    foreach($previews as $u){$f=FaBFindUID(intval($u));if($f && $f['zone']==='Temp')$f['object']->removed=true;}
}

function FaBPENRedPreviews(array $uids): string {
    return implode('&',array_filter(explode('&',FaBPENPreviewRefs($uids)),fn($r)=>($f=FaBIdentityFromMZ($r)) && FaBMSTObjectColor($f['player'],$f['object'])===1));
}

function FaBPENDriver(int $player): void {
    $o=FaBHVYToken($player,'hyper_driver',1,$player);
    if($o)FaBSetObjectCounter($o,'STEAM',2);
}

function FaBPENBoost(int $player, int $uid, int $banished): void {
    $f=FaBFindUID($banished);
    if($f){FaBARCSetCard($banished,'penBoostTurn',intval(GetTurnNumber()));if(FaBHasType($f['object'],'Evo'))FaBWTRAddEffect($player,'PEN_BOOST_EVO',1);}
    $left=[];$power=0;
    foreach(FaBWTREffects($player) as $e){if(($e['type']??'')==='PEN_BOOST_NEXT')$power+=intval($e['amount']);else $left[]=$e;}
    FaBWTRSetEffects($player,$left);if($power)FaBCRUSelfTagUID($uid,'WTR_POWER:'.$power);
}

function FaBPENSkywarden(int $player, int $uid, string $ref): void {
    $f=FaBIdentityFromMZ($ref);
    if(!$f || $f['player']!==$player || $f['zone']!=='Arena' || !FaBHasType($f['object'],'Item'))return;
    $gold=$f['object']->CardID==='golden_cog';
    FaBMONDestroy(intval($f['object']->UniqueID));FaBTagUID($uid,'WTR_DEFENSE:1');
    if($gold)FaBHVYToken($player,'gold',1,$player);
}

function FaBPENPuppetry(int $player, string $ref): void {
    $f=FaBIdentityFromMZ($ref);
    if(!$f || $f['player']!==$player || $f['zone']!=='Graveyard' || !FaBHasType($f['object'],'Ally'))return;
    $o=FaBMoveUID(intval($f['object']->UniqueID),'Arena',$player);
    if($o)FaBWTRAddEffect($player,'PEN_PUPPETRY',1,['uid'=>intval($o->UniqueID)]);
}

function FaBPENScuttle(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f || !FaBHasType($f['object'],'Ally'))return;
    $f['object']->Status=2;FaBWTRAddEffect($f['player'],'PEN_SCUTTLE',1,['uid'=>intval($f['object']->UniqueID)]);
}

function FaBPENEnd(): void {
    $p=intval(GetTurnPlayer());$s=FaBGetState();unset($s['penShoes'][$p]);FaBSetState($s);
    foreach(FaBLiveSeats() as $p)foreach(['Equipment','CombatChain'] as $z)foreach(FaBChoiceRefs($p,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBARCCard(intval($o->UniqueID),'penTouchTurn',-1)===intval(GetTurnNumber()))FaBMONDestroy(intval($o->UniqueID));}
    foreach(FaBMONArena(intval(GetTurnPlayer()),'doomsaying') as $ref)FaBROSQueue(intval(GetTurnPlayer()),'doomsaying_red',FaBPENUID($ref));
    foreach(FaBLiveSeats() as $seat){
        foreach(['Weapons','CombatChain'] as $z)foreach(FaBChoiceRefs($seat,$z) as $r){$o=FaBIdentityFromMZ($r)['object'];if(FaBARCCard(intval($o->UniqueID),'penSharpenTurn',-1)===intval(GetTurnNumber()))FaBSetObjectCounter($o,'POWER',0);}
        $effects=FaBWTREffects($seat);
        foreach($effects as $e){
            if(!in_array($e['type']??'',['PEN_PUPPETRY','PEN_SCUTTLE'],true))continue;
            $f=FaBFindUID(intval($e['uid']));if($f && $f['zone']==='Arena')FaBMONDestroy(intval($e['uid']));
            if($e['type']==='PEN_PUPPETRY')foreach(FaBChoiceRefs($seat,'Hand') as $r)FaBDiscardChoice($seat,$r);
        }
    }
}

function FaBPENClose(): void {
    foreach(FaBLiveSeats() as $seat)foreach(FaBChoiceRefs($seat,'Banish') as $r)FaBARCSetCard(FaBPENUID($r),'penMirageChain',-1);
    foreach(FaBLiveSeats() as $seat)FaBWTRSetEffects($seat,array_values(array_filter(FaBWTREffects($seat),fn($e)=>!in_array($e['type']??'',['PEN_BOOST_NEXT','PEN_INSTANT_CHAIN','PEN_DAGGER_HIT','PEN_DRACONIC_CHAIN','PEN_ARC_BENDING'],true))));
}

function FaBPENKeywords(object $o): array {
    $player=intval($o->Controller??$o->Owner??0);$result=[];
    $ward=FaBARCCard(intval($o->UniqueID??0),'penWard',[]);if(intval($ward['turn']??-1)===intval(GetTurnNumber()))$result[]='Ward '.intval($ward['amount']);
    if($o->CardID==='boo_resident_spook_yellow' && intval($o->Status??2)===2)$result[]='Spellvoid 2';
    if(FaBWTRBase($o->CardID)==='stadium_security' && ($o->Location??'')==='Arsenal' && FaBHVYCount($player,'CONTROLLED_toughness'))$result[]='Ambush';
    if(($o->CardID==='skera_strapping' && FaBPENPitchedSix($player)) || ($o->CardID==='volcanic_vice' && FaBARCEffect($player,'PEN_SURGE_CREATED')>0))$result[]='Spellvoid 3';
    if($o->CardID==='mask_of_the_swarming_claw') {
        $links=FaBGetState()['departedChainTypes'][(string)$player]??[];
        foreach(FaBChoiceRefs($player,'CombatChain') as $r){$a=FaBIdentityFromMZ($r)['object'];if(($a->Role??'')==='ATTACK')$links[(string)$a->ChainLink]=true;}
        $result[]='Spellvoid '.count($links);
    }
    return $result;
}

function FaBPENAddDefender(int $player, string $ref): bool {
    $f=FaBIdentityFromMZ($ref);if(!$f || $f['player']!==$player || !in_array($f['zone'],['Deck','Arsenal'],true) || !FaBHasType($f['object'],'Action'))return false;
    $from=$f['zone'];$o=FaBMoveUID(intval($f['object']->UniqueID),'CombatChain',$player);if(!$o)return false;
    $o->Role='DEFENSE';$o->FromZone=$from;$o->ChainLink=intval(FaBGetState()['chainLink']);
    OnDefended($player,FaBDTDSource(intval($o->UniqueID)),$player);return true;
}

function FaBPENSafeHaven(int $player): bool {
    $ref=FaBChoiceRefs($player,'Deck')[0]??'';$f=FaBIdentityFromMZ($ref);if(!$f)return false;
    FaBRevealChoices($player,$ref);
    return FaBWTRIsAttackAction($f['object']) && FaBPENAddDefender($player,$ref);
}

function FaBPENDoom(int $uid): int {
    $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Arena')return 0;
    $n=intval(FaBObjectCounters($f['object'])['DOOM']??0)+1;FaBSetObjectCounter($f['object'],'DOOM',$n);return $n;
}

function FaBPENAttackDelta(): int {
    $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));
    return $f?FaBAttackPower($s)-FaBPENBase(intval($s['attacker']),$f['object'],intval(CardPower($f['object']->CardID))):0;
}

function FaBPENAttackGoAgain(): bool {
    $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));return $f && FaBAttackHasGoAgain($s,$f['object']);
}

function FaBPENRainbow(): bool {
    return FaBPENAttackDelta()>0 && FaBPENAttackGoAgain() && FaBCurrentAttackHasKeyword(FaBGetState(),'Dominate');
}

function FaBPENSuppressAttack(): void {
    $uid=intval(FaBGetState()['attackUID']);FaBTagUID($uid,'WTR_POWER:-2');FaBTagUID($uid,'NO_ABILITIES');FaBTagUID($uid,'PEN_BLANK_ATTACK');
}

function FaBPENRemovePower(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if($f)FaBSetObjectCounter($f['object'],'POWER',max(0,intval(FaBObjectCounters($f['object'])['POWER']??0)-1));
}

function FaBPENTap(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if($f)$f['object']->Status=1;
}

function FaBPENBetaCost(int $player, object $o): int {
    if(!FaBHasType($o,'Evo'))return 0;$n=0;
    foreach(FaBProfessorEquipped($player) as $e){
        if(HasNoAbilities($e)||!str_starts_with($e->CardID,'evo_beta_base_'))continue;
        foreach(['Head','Chest','Arms','Legs'] as $slot)if(FaBHasType($e,$slot)&&FaBHasType($o,$slot))--$n;
    }
    return $n;
}

function FaBPENResolvedDefender(int $seat, object $o): void {
    if($o->CardID!=='shimmering_mirage_blue' || !in_array($o->Role??'',['DEFENSE','DEFENSE_REACTION'],true) || HasNoAbilities($o))return;
    $uid=intval($o->UniqueID);$moved=FaBMoveUID($uid,'Banish',$seat);
    if($moved)FaBARCSetCard($uid,'penMirageChain',intval(GetTurnNumber()));
}

function FaBPENCloseCard(int $seat, object $o): bool {
    if($o->CardID==='deep_recesses_of_existence_blue'&&!HasNoAbilities($o))FaBROSQueue($seat,$o->CardID,intval($o->UniqueID));
    if($o->CardID==='solforge_gauntlet' && !HasNoAbilities($o) && in_array($o->Role??'',['DEFENSE','DEFENSE_REACTION'],true)) {
        FaBMoveUID(intval($o->UniqueID),'Soul',$seat);return true;
    }
    return false;
}

function FaBPENCradleTargets(int $player): string {
    return implode('&',array_filter(explode('&',FaBPENPermanents($player)),fn($r)=>($f=FaBIdentityFromMZ($r)) && (FaBHasType($f['object'],'Ally')||FaBHasType($f['object'],'Aura')||FaBHasType($f['object'],'Item')||FaBHasType($f['object'],'Equipment'))));
}

function FaBPENCradle(int $uid, string $ref): void {
    $f=FaBIdentityFromMZ($ref);$source=FaBFindUID($uid);if(!$f||!$source||$source['zone']!=='Arena')return;
    $sources=(array)FaBARCCard(intval($f['object']->UniqueID),'penFreezeSources',[]);$sources[]=$uid;
    FaBARCSetCard(intval($f['object']->UniqueID),'penFreezeSources',array_values(array_unique($sources)));
}

function FaBPENFrozenObject(object $o): bool {
    foreach((array)FaBARCCard(intval($o->UniqueID??0),'penFreezeSources',[]) as $uid){$f=FaBFindUID(intval($uid));if($f && $f['zone']==='Arena')return true;}
    if(($o->Location??'')!=='Arsenal')return false;
    $player=intval($o->Controller??$o->Owner??0);
    $glaze=false;foreach(FaBOpponents($player) as $seat)if(FaBMONArena($seat,'channel_iceloch_glaze'))$glaze=true;
    if(!$glaze)return false;
    if(FaBMONArena($player,'frostbite'))return true;
    foreach(['Hero','Equipment','Weapons','Arena'] as $z)foreach(FaBChoiceRefs($player,$z) as $ref){$p=FaBIdentityFromMZ($ref)['object'];if(intval(FaBObjectCounters($p)['UPR_FROZEN_BY']??0)>0||FaBPENFrozenObject($p))return true;}
    return false;
}

function FaBPENSwords(int $player): string {
    return implode('&',FaBChoiceRefs($player,'Weapons',['type'=>'Sword']));
}

function FaBPENSharpen(int $player, string $ref, int $threshold): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||$f['player']!==$player||$f['zone']!=='Weapons'||!FaBHasType($f['object'],'Sword'))return;
    $uid=intval($f['object']->UniqueID);$n=intval(FaBObjectCounters($f['object'])['POWER']??0)+1;
    FaBSetObjectCounter($f['object'],'POWER',$n);FaBARCSetCard($uid,'penSharpenTurn',intval(GetTurnNumber()));
    if($n>=$threshold)FaBARCSetCard($uid,'penSwordDominateTurn',intval(GetTurnNumber()));
}

function FaBPENCraftsmanship(string $ref, int $power): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||!FaBWTRIsWeapon($f['object']))return;
    FaBCRUSelfTagUID(intval($f['object']->UniqueID),'WTR_POWER:'.$power);
    $uid=intval(FaBObjectCounters($f['object'])['WEAPON_UID']??0);$w=FaBFindUID($uid);
    if($w && FaBARCCard($uid,'penSharpenTurn',-1)===intval(GetTurnNumber()))FaBSetObjectCounter($w['object'],'POWER',intval(FaBObjectCounters($w['object'])['POWER']??0)+1);
}

function FaBPENRendReady(int $attackUID): bool {
    $a=FaBFindUID($attackUID);$w=$a?FaBFindUID(intval(FaBObjectCounters($a['object'])['WEAPON_UID']??0)):null;
    return $w && intval(FaBObjectCounters($w['object'])['POWER']??0)>0;
}

function FaBPENRend(int $player, int $attackUID, int $victim): void {
    if(!FaBPENRendReady($attackUID)||!FaBSeatIsLive($victim))return;
    $a=FaBFindUID($attackUID);$w=FaBFindUID(intval(FaBObjectCounters($a['object'])['WEAPON_UID']));
    FaBSetObjectCounter($w['object'],'POWER',intval(FaBObjectCounters($w['object'])['POWER'])-1);FaBARCLoseLife($victim,2,$player);
}

function FaBPENHavocCost(): int {
    $n=0;foreach(FaBLiveSeats() as $p)foreach(FaBCRUEquipment($p,'havoc_wrap') as $r){$o=FaBIdentityFromMZ($r)['object'];if(intval($o->Status)===1&&!HasNoAbilities($o))--$n;}return $n;
}

function FaBPENAbilityPaid(object $o): void {
    if(FaBWTRIsWeapon($o))FaBWTRAddEffect(intval($o->Controller??$o->Owner??0),'PEN_WEAPON_ACTIVATED',1);
    if(in_array($o->CardID,['tigrine_reflex_red','herald_of_victoria_yellow'],true)){ $f=FaBFindUID(intval($o->UniqueID));if($f)FaBDiscardChoice($f['player'],$f['mzID']); }
    if(in_array($o->CardID,['rippling_wave','kimono_of_layered_lessons'],true))FaBMSTFlip(intval($o->UniqueID));
    if(in_array($o->CardID,['synapse_sparkcap','mbrio_base_digits','dyed_silk_sleeves','assembly_module_blue','touch_of_reality'],true))$o->Status=1;
    if(in_array($o->CardID,['wind_cutter','farflight_longbow'],true))$o->Status=1;
    if($o->CardID==='havoc_wrap'){$o->Status=1;FaBSetObjectCounter($o,'PEN_HAVOC',1);}
}

function FaBPENArcDamage(string $sourceMZ, int $amount): int {
    $f=FaBIdentityFromMZ($sourceMZ);
    if(!$f||$f['zone']!=='CombatChain'||($f['object']->Role??'')!=='ATTACK'||(!FaBHasType($f['object'],'Lightning')&&!FaBHasType($f['object'],'Elemental')))return $amount;
    foreach(FaBLiveSeats() as $seat)$amount+=FaBARCEffect($seat,'PEN_ARC_BENDING');return $amount;
}

function FaBPENSowSearch(int $player): string {
    $life=FaBROSCount($player,'LIFE');$refs=FaBChoiceRefs($player,'Deck',['type'=>'Aura','maxCost'=>$life-1]);
    $refs=array_filter($refs,fn($r)=>FaBHasType(FaBIdentityFromMZ($r)['object'],'Earth'));
    $uids=FaBARCStageRefs($player,implode('&',$refs));return FaBPENPreviewRefs($uids);
}

function FaBPENGoldDraw(int $player, int $amount): int {
    if(($GLOBALS['seaDrawSource']??'')!=='gold'||$amount<=0||FaBARCEffect($player,'PEN_GOLD_DRAW')<=0)return $amount;
    FaBWTRSetEffects($player,array_values(array_filter(FaBWTREffects($player),fn($e)=>($e['type']??'')!=='PEN_GOLD_DRAW')));
    return $amount+1;
}

function FaBPENPoweredArrow(int $player): string {
    $refs=FaBDYNAttacks($player,'ARROW');
    return FaBPENAttackDelta()>0?$refs:'';
}

function FaBPENInnerChi(int $player): string {
    return implode('&',FaBChoiceRefs($player,'Deck',['base'=>'inner_chi']));
}

function FaBPENBanishChi(int $player, int $victim, string $refs): void {
    foreach(FaBUPRUIDs($refs) as $uid){
        $f=FaBFindUID($uid);
        if(!$f||$f['player']!==$victim||$f['zone']!=='Deck'||FaBWTRBase($f['object']->CardID)!=='inner_chi')continue;
        $o=FaBMoveUID($uid,'Banish',$victim);
        if($o)FaBDYNBanished($victim,$o,$player);
    }
    FaBShuffleDeck($victim);
}

function FaBPENWobbleTargets(int $player): string {
    $n=FaBPENSigilCount($player);$refs=[];
    $seats=array_merge([$player],FaBAdjacentOpponents($player));
    foreach(GetStack() as $o)if(is_object($o)&&empty($o->removed)&&in_array(intval($o->Controller),$seats,true)&&$o->Kind!=='ABILITY'&&FaBHasType($o,'Action')&&!FaBHasType($o,'Attack')&&is_numeric(CardCost($o->CardID))&&intval(CardCost($o->CardID))<$n)$refs[]='Stack-'.$o->mzIndex;
    return implode('&',$refs);
}

function FaBPENWobble(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||$f['zone']!=='Stack')return;
    $p=intval($f['object']->Controller);
    FaBMoveStackUID(intval($f['object']->UniqueID),'Graveyard',$p);
    AddActionPoints($p,intval(GetActionPoints($p))+1);
}

function FaBPENConquerTargets(int $victim): string {
    $refs=[];
    foreach(['Arsenal','Equipment','Weapons','Arena'] as $z)foreach(FaBChoiceRefs($victim,$z) as $r)if(FaBUPRFrozen(FaBIdentityFromMZ($r)['object']))$refs[]=$r;
    return implode('&',$refs);
}

function FaBPENWaxTargets(int $player, bool $ward): string {
    return implode('&',array_filter(FaBChoiceRefs($player,'Arena',['type'=>'Aura']),function($r)use($player,$ward){
        $o=FaBIdentityFromMZ($r)['object'];return $ward?preg_match('/\bWard\b/i',implode(' ',FaBKeywords($o))):FaBMSTObjectColor($player,$o)===3;
    }));
}

function FaBPENPowerCounter(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if($f)FaBSetObjectCounter($f['object'],'POWER',intval(FaBObjectCounters($f['object'])['POWER']??0)+1);
}

function FaBPENSetX(int $uid, int $amount): void {
    $s=FaBGetState();if(!is_array($s['pendingPayment'])||intval($s['pendingPayment']['uid'])!==$uid)return;
    $amount=max(0,$amount);$s['pendingPayment']['cost']+= $amount;FaBSetState($s);FaBARCSetCard($uid,'penX',$amount);
}

function FaBPENLandmarks(int $player, int $cost): string {
    return implode('&',array_filter(explode('&',FaBPENPermanents($player,'Landmark')),fn($r)=>($f=FaBIdentityFromMZ($r))&&intval(CardCost($f['object']->CardID))===$cost));
}

function FaBPENFarflight(int $player, object $o): bool {
    if(!FaBHasType($o,'Arrow'))return false;
    foreach(FaBChoiceRefs($player,'Weapons',['base'=>'farflight_longbow']) as $r)if(!HasNoAbilities(FaBIdentityFromMZ($r)['object']))return true;
    return false;
}

function FaBPENNeedsPrepare(string $id): bool {
    return in_array($id,['synapse_sparkcap','mbrio_base_digits','runebleed_robe','carrion_crown','dyed_silk_sleeves','graven_gaslight','touch_of_reality','beckoning_haunt','bubba_lubba_run_aground_yellow'],true);
}

function FaBPENCostRefs(int $player, string $id): string {
    return match($id){
        'synapse_sparkcap'=>implode('&',FaBChoiceRefs($player,'Hand',['type'=>'Evo'])),
        'mbrio_base_digits'=>FaBSEARefs($player,'readyCog'),
        'runebleed_robe'=>implode('&',FaBMONArena($player,'runechant')),
        'carrion_crown'=>implode('&',FaBChoiceRefs($player,'Hand',['type'=>'Ally'])),
        'dyed_silk_sleeves'=>implode('&',FaBChoiceRefs($player,'Weapons',['type'=>'Dagger'])),
        default=>''
    };
}

function FaBPENCostChoices(int $player, int $stackUID): string {
    $f=FaBFindUID($stackUID);return $f?FaBPENCostRefs($player,$f['object']->CardID):'';
}

function FaBPENPayChosenCost(int $player, int $stackUID, string $ref): void {
    $f=FaBFindUID($stackUID);if(!$f||!in_array($ref,explode('&',FaBPENCostChoices($player,$stackUID)),true))return;
    $id=$f['object']->CardID;
    if($id==='synapse_sparkcap')FaBMoveUID(FaBPENUID($ref),'Banish',$player);
    elseif($id==='carrion_crown')FaBDiscardChoice($player,$ref);
    elseif($id==='mbrio_base_digits')FaBPENTap($ref);
    else FaBMONDestroy(FaBPENUID($ref));
}

function FaBPENNinjaAttack(int $player, bool $actionOnly=false): string {
    $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));
    return $f&&$f['player']===$player&&FaBHasType($f['object'],'Ninja')&&(!$actionOnly||FaBWTRIsAttackAction($f['object']))?$f['mzID']:'';
}

function FaBPENDyed(int $player): void {
    $uid=intval(DecisionQueueController::GetVariable('penAbilitySource'));
    FaBARCSetCard($uid,'penDyedLink',intval(FaBGetState()['chainLink']));
    FaBARCSetCard($uid,'penDyedTurn',intval(GetTurnNumber()));
}

function FaBPENPaySilver(int $player, int $stackUID, string $refs): void {
    $uids=FaBUPRUIDs($refs);if(count($uids)!==2)return;
    foreach($uids as $uid){$f=FaBFindUID($uid);if(!$f||$f['player']!==$player||$f['zone']!=='Arena'||$f['object']->CardID!=='silver')return;}
    foreach($uids as $uid)FaBMONDestroy($uid);
}

function FaBPENEquipGraven(int $player, int $uid): void {
    $f=FaBFindUID($uid);if(!$f||$f['player']!==$player||$f['zone']!=='Graveyard')return;
    if(FaBHasType($f['object'],'Equipment')){
        foreach(FaBProfessorEquipped($player) as $o)foreach(['Head','Chest','Arms','Legs'] as $slot)if(FaBHasType($f['object'],$slot)&&FaBHasType($o,$slot))FaBMONDestroy(intval($o->UniqueID));
        FaBMoveUID($uid,'Equipment',$player);
    }else FaBMoveUID($uid,'Arena',$player);
}

function FaBPENReturnGraven(int $player, int $uid, string $refs): void {
    if(count(FaBUPRUIDs($refs))!==2)return;
    FaBPENPaySilver($player,0,$refs);FaBPENEquipGraven($player,$uid);
}

function FaBPENTouch(int $uid, int $amount): void {
    FaBARCSetCard($uid,'penWard',['turn'=>intval(GetTurnNumber()),'amount'=>$amount]);FaBARCSetCard($uid,'penTouchTurn',intval(GetTurnNumber()));
}

function FaBPENAuraCost(int $player, int $amount): string {
    return implode('&',array_filter(FaBChoiceRefs($player,'Graveyard',['type'=>'Aura']),fn($r)=>intval(CardCost(FaBIdentityFromMZ($r)['object']->CardID))===$amount));
}

function FaBPENStorm(int $uid): int {
    $f=FaBFindUID($uid);if(!$f||$f['zone']!=='Arena')return 0;
    $n=intval(FaBObjectCounters($f['object'])['STORM']??0)+1;FaBSetObjectCounter($f['object'],'STORM',$n);return $n;
}

function FaBPENHaboobUpkeep(int $player, int $uid, string $refs, int $number): void {
    $uids=FaBUPRUIDs($refs);if($number<1||count($uids)!==$number){FaBMONDestroy($uid);return;}
    foreach($uids as $u)FaBMONDestroy($u);
}

function FaBPENBanishDown(int $uid): void {
    $f=FaBFindUID($uid);if(!$f)return;$o=FaBMoveUID($uid,'Banish',intval($f['object']->Owner??$f['player']));if($o)$o->FaceDown=1;
}

function FaBPENOrbitoclast(int $player, string $ref): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||$f['zone']!=='Inventory'||$f['player']!==$player||$f['object']->CardID!=='orbitoclast')return;
    if(FaBHNTOpenHands($player)<1)return;
    FaBMoveUID(intval($f['object']->UniqueID),'Weapons',$player);
}

function FaBPENLobotomy(int $victim): void {FaBARCSetCard(FaBUPRHeroUID($victim),'penLobotomy',true);}

function FaBPENBlueDefenders(): string {
    return implode('&',array_map(fn($f)=>$f['mzID'],array_filter(FaBOUTDefenders(),fn($f)=>FaBWTRIsAttackAction($f['object'])&&FaBMSTObjectColor($f['player'],$f['object'])===3)));
}

function FaBPENReturnOwner(string $ref): void {
    $f=FaBIdentityFromMZ($ref);if($f)FaBMoveUID(intval($f['object']->UniqueID),'Hand',intval($f['object']->Owner??$f['player']));
}

function FaBPENKimono(int $uid): void {
    $f=FaBFindUID($uid);if($f)FaBSetObjectCounter($f['object'],'DEFENSE',intval(FaBObjectCounters($f['object'])['DEFENSE']??0)-1);
}

function FaBPENCloaked(int $player): string {
    return implode('&',array_filter(FaBChoiceRefs($player,'Equipment'),fn($r)=>FaBHasKeyword(FaBIdentityFromMZ($r)['object']->CardID,'Cloaked')));
}

function FaBPENFaceDown(string $refs): void {foreach(FaBUPRUIDs($refs) as $uid){$f=FaBFindUID($uid);if($f)$f['object']->FaceDown=1;}}

function FaBPENPeekTwo(int $player, int $victim): array {
    $uids=[];foreach(array_slice(FaBChoiceRefs($victim,'Deck'),0,2) as $r){$uid=FaBPENUID($r);FaBMoveUID($uid,'Temp',$player);$uids[]=$uid;}return $uids;
}

function FaBPENBanishPeek(int $player, int $victim, string $ref): void {
    $f=FaBIdentityFromMZ($ref);if($f&&$f['zone']==='Temp'){ $o=FaBMoveUID(intval($f['object']->UniqueID),'Banish',$victim);if($o)FaBDYNBanished($victim,$o,$player); }
}

function FaBPENLinkResolved(array $state): void {
    foreach(FaBLiveSeats() as $p)foreach(FaBCRUEquipment($p,'dyed_silk_sleeves') as $r){$uid=FaBPENUID($r);if(FaBARCCard($uid,'penDyedTurn',-1)===intval(GetTurnNumber())&&FaBARCCard($uid,'penDyedLink',-1)===intval($state['chainLink'])&&empty($state['attackHit']))FaBMONDestroy($uid);}
}

function FaBPENMagmaticReady(int $player, int $uid): bool {
    $f=FaBFindUID($uid);return $f&&$f['player']===$player&&in_array($f['zone'],['Equipment','CombatChain'],true)&&!HasNoAbilities($f['object'])&&FaBSEACanTap($f['object'])&&FaBAvailablePitch($player)>=1;
}

function FaBPENReadySurges(int $player): string {
    return implode('&',array_filter(FaBMONArena($player,'seismic_surge'),fn($r)=>FaBSEACanTap(FaBIdentityFromMZ($r)['object'])));
}

function FaBPENShiftCost(int $uid, string $refs): void {
    $uids=FaBUPRUIDs($refs);foreach($uids as $u)FaBPENTap(FaBDTDSource($u));FaBARCSetCard($uid,'penShift',count($uids));
}

function FaBPENPoweredAllies(int $player): string {
    return implode('&',array_filter(FaBChoiceRefs($player,'Arena',['type'=>'Ally']),fn($r)=>intval(FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['POWER']??0)>0));
}

function FaBPENPreventionEquipment(int $player, string $id, array $used): int {
    foreach(FaBCRUEquipment($player,$id) as $r){$o=FaBIdentityFromMZ($r)['object'];$uid=intval($o->UniqueID);if(!HasNoAbilities($o)&&!in_array($uid,$used,true))return $uid;}return 0;
}

function FaBPENPreventionChoices(int $player, string $type, array $used): array {
    $refs=[];
    if(FaBPENPreventionEquipment($player,'solray_plating',$used))$refs=FaBChoiceRefs($player,'Soul');
    if($type==='ARCANE'&&FaBPENPreventionEquipment($player,'mbrio_base_vizier',$used))foreach(FaBMONArena($player,'hyper_driver') as $r)if(intval(FaBObjectCounters(FaBIdentityFromMZ($r)['object'])['STEAM']??0)>0)$refs[]=$r;
    return $refs;
}

function FaBPENPreventionPay(int $player, string $ref, array &$used): int {
    $f=FaBIdentityFromMZ($ref);if(!$f)return 0;
    if($f['zone']==='Soul'&&($uid=FaBPENPreventionEquipment($player,'solray_plating',$used))){
        $used[]=$uid;FaBMoveUID(intval($f['object']->UniqueID),'Banish',$player);FaBARCSetCard($uid,'penTouchTurn',intval(GetTurnNumber()));return 1;
    }
    if($f['zone']==='Arena'&&FaBWTRBase($f['object']->CardID)==='hyper_driver'&&intval(FaBObjectCounters($f['object'])['STEAM']??0)>0&&($uid=FaBPENPreventionEquipment($player,'mbrio_base_vizier',$used))){$used[]=$uid;FaBARCSteam($f['object'],-1);return 1;}
    return 0;
}

function FaBPENTopsy(): bool {foreach(FaBLiveSeats() as $p)if(FaBARCEffect($p,'PEN_TOPSY'))return true;return false;}

function FaBPENProtectFealty(object $o, int $controller): bool {
    return $o->CardID==='fealty'&&$controller>0&&$controller!==intval($o->Controller??$o->Owner??0)&&count(FaBCRUEquipment(intval($o->Controller??$o->Owner??0),'dynastic_diadem'))>0;
}

function FaBPENReplaceGain(int $player, int $amount): bool {
    $refs=[];
    foreach(FaBOpponents($player) as $p)foreach(FaBCRUEquipment($p,'vestige_of_flagellation') as $r){$o=FaBIdentityFromMZ($r)['object'];$uid=intval($o->UniqueID);if(!HasNoAbilities($o)&&FaBARCCard($uid,'penVestigeTurn',-1)!==intval(GetTurnNumber()))$refs[]=$r;}
    if(!$refs)return false;
    if(count($refs)===1)FaBPENVestige($refs[0],$amount);
    else FaBRunSourceMacro('ResolveAbility',$player,'vestige_of_flagellation',['mzID'=>'','penVestiges'=>implode('&',$refs),'penGainAmount'=>$amount]);
    return true;
}

function FaBPENVestige(string $ref, int $amount): void {
    $f=FaBIdentityFromMZ($ref);if(!$f||$amount<1)return;$uid=intval($f['object']->UniqueID);$owner=$f['player'];
    FaBARCSetCard($uid,'penVestigeTurn',intval(GetTurnNumber()));FaBARCLoseLife($owner,$amount,$owner);FaBHVYToken($owner,'vigor',$amount,$owner);
}

function FaBPENDestroy(int $actor, int $uid): void {
    $f=FaBFindUID($uid);if($f&&FaBPENProtectFealty($f['object'],$actor))return;
    $previous=$GLOBALS['fabEffectController']??0;$GLOBALS['fabEffectController']=$actor;
    try{FaBMONDestroy($uid);}finally{$GLOBALS['fabEffectController']=$previous;}
}

function FaBPENLegendCards(): array {
    static $cards=null;
    if($cards===null){$data=json_decode(file_get_contents(__DIR__.'/../GeneratedCode/cardArrayCache.json'),true);$cards=[];foreach($data['cardArray']??[] as $card)if(!empty($card['cc_living_legend'])&&in_array('Hero',$card['types']??[],true))$cards[$card['name']]=$card['id'];}
    return $cards;
}
function FaBPENLivingLegends(): string {return implode('&',array_keys(FaBPENLegendCards()));}
function FaBPENEmbody(int $player, string $name): void {
    $id=FaBPENLegendCards()[$name]??'';if($id==='')return;
    foreach(FaBChoiceRefs($player,'Hero') as $ref){$o=FaBIdentityFromMZ($ref)['object'];$uid=intval($o->UniqueID);if(FaBARCCard($uid,'penEmbodyOriginal','')==='')FaBARCSetCard($uid,'penEmbodyOriginal',$o->CardID);$o->CardID=$id;}
}
function FaBPENShoes(int $victim): void {$s=FaBGetState();$s['penShoes'][$victim]=true;FaBSetState($s);}
function FaBPENShoesActive(int $player): bool {return !empty(FaBGetState()['penShoes'][$player]);}
function FaBPENLunar(object $defender): void {
    $s=FaBGetState();$f=FaBFindUID(intval($s['attackUID']));if(!$f||$f['object']->CardID!=='lunar_mirage_red'||HasNoAbilities($f['object'])||!FaBWTRIsAttackAction($defender)||FaBMONDefendingPower(intval($defender->Controller??0),$defender)<6)return;
    FaBARCSetCard(intval($f['object']->UniqueID),'penLunarOriginal','lunar_mirage_red');$f['object']->CardID=$defender->CardID;
}
function FaBPENFinalPower(int $player, object $o, int $base, int $power): int {
    if($o->CardID==='walk_in_my_shoes_yellow'&&!HasNoAbilities($o)&&$power>$base)++$power;
    if(FaBARCEffect($player,'PEN_TIGER_LOCK')){
        $cap=FaBARCCard(intval($o->UniqueID),'penTigerCap',[]);
        $power=min($power,intval($cap['turn']??-1)===intval(GetTurnNumber())?intval($cap['power']):$base);
    }
    return $power;
}
function FaBPENTigerTrap(int $player): void {
    $attacks=[];$n=0;$s=FaBGetState();
    foreach(FaBChoiceRefs($player,'CombatChain') as $r){$o=FaBIdentityFromMZ($r)['object'];if(($o->Role??'')!=='ATTACK')continue;$test=$s;$test['attackUID']=intval($o->UniqueID);$power=FaBAttackPower($test);$base=FaBPENBase($player,$o,intval(CardPower($o->CardID)));if($power>$base)++$n;$attacks[intval($o->UniqueID)]=$power;}
    if($n<3)return;
    foreach($attacks as $uid=>$power)FaBARCSetCard($uid,'penTigerCap',['turn'=>intval(GetTurnNumber()),'power'=>$power]);FaBWTRAddEffect($player,'PEN_TIGER_LOCK',1);
}
function FaBPENPowerGain(object $o, int $amount): int {
    return $amount>0&&$o->CardID==='doubling_season_red'&&empty($o->FaceDown)&&!HasNoAbilities($o)?$amount+1:$amount;
}
function FaBPENPowerTag(object $o, string $tag): string {
    if(!preg_match('/^(WTR_POWER|CRU_REACTION_POWER):([1-9][0-9]*)$/',$tag,$m))return $tag;
    if(FaBARCEffect(intval($o->Controller??$o->Owner??0),'PEN_TIGER_LOCK')&&($o->Role??'')==='ATTACK')return '';
    return $m[1].':'.FaBPENPowerGain($o,intval($m[2]));
}
function FaBPENFrostReplacements(int $player): string {
    $refs=FaBChoiceRefs($player,'Graveyard',['base'=>'smoldering_steel']);
    foreach(FaBCRUEquipment($player,'smoldering_scales') as $r)if(!HasNoAbilities(FaBIdentityFromMZ($r)['object']))$refs[]=$r;
    return implode('&',$refs);
}
function FaBPENReplaceFrost(int $player, string $id, int $number, int $creator, bool $action, bool $wager): bool {
    if($id!=='frostbite'||$number<1||!empty($GLOBALS['penFrostBypass'])||FaBPENFrostReplacements($player)==='')return false;
    FaBRunSourceMacro('ResolveAbility',$player,'smoldering_scales',['mzID'=>'','penFrostNumber'=>$number,'penFrostCreator'=>$creator,'penFrostAction'=>$action,'penFrostWager'=>$wager]);return true;
}
function FaBPENFinishFrost(int $player, string $ref, int $number, int $creator, bool $action, bool $wager): void {
    if($number<1)return;
    if(in_array($ref,explode('&',FaBPENFrostReplacements($player)),true)&&($f=FaBIdentityFromMZ($ref))){if($f['zone']==='Graveyard')FaBMoveUID(intval($f['object']->UniqueID),'Banish',$player);else FaBMONDestroy(intval($f['object']->UniqueID));return;}
    $GLOBALS['penFrostBypass']=true;try{FaBHVYToken($player,'frostbite',$number,$creator,$action,$wager);}finally{unset($GLOBALS['penFrostBypass']);}
}
function FaBPENLifeMore(int $a, int $b): bool {
    return GetHealth($a)>GetHealth($b)||(GetHealth($a)===GetHealth($b)&&FaBPENLineCrossers($a));
}
function FaBPENLineCrossers(int $player): bool {foreach(FaBCRUEquipment($player,'line_crossers') as $r)if(!HasNoAbilities(FaBIdentityFromMZ($r)['object']))return true;return false;}

function FaBPENWagerResolution(int $attacker, int $uid, array $wagers): bool {
    if(!$wagers)return false;$needed=FaBARCEffect($attacker,'PEN_CHEAT_LOSS')>0;
    foreach($wagers as $w)if(FaBARCEffect(intval($w['victim']),'PEN_CHEAT_LOSS'))$needed=true;
    if(!$needed)return false;$s=FaBGetState();
    foreach($wagers as &$w){$v=intval($w['victim']);$hit=isset($s['targetDamage'][(string)$v])?intval($s['targetDamage'][(string)$v]['damage'])>0:(!empty($s['attackHit'])&&$v===intval($s['defender']));$w['winner']=$hit?$attacker:$v;}unset($w);
    FaBRunSourceMacro('ResolveAbility',$attacker,'cheating_scoundrel_red',['mzID'=>FaBDTDSource($uid),'penWagers'=>$wagers,'penWagerUID'=>$uid,'penWagerAttacker'=>$attacker]);return true;
}
function FaBPENConsumeCheat(int $player): bool {
    if(!FaBARCEffect($player,'PEN_CHEAT_LOSS'))return false;
    FaBWTRSetEffects($player,array_values(array_filter(FaBWTREffects($player),fn($e)=>($e['type']??'')!=='PEN_CHEAT_LOSS')));return true;
}
function FaBPENWagerPrize(int $attacker, int $uid, int $winner, array $wager): void {
    if(!FaBSeatIsLive($winner))return;
    foreach($wager['tokens'] as $token){if($token==='ROS_DRINK'){DoDrawCard($winner,1);FaBROSQueue($winner,'drink_em_under_the_table_red',$uid,['rosTarget'=>$winner===$attacker?intval($wager['victim']):$attacker]);}else FaBHVYToken($winner,$token,1,$attacker,$wager['action'],true);}
    if($winner===$attacker&&FaBHVYHero($attacker,'olympia')&&!FaBARCCard($uid,'penOlympiaPaid',false)){FaBARCSetCard($uid,'penOlympiaPaid',true);FaBHVYToken($attacker,'gold',1,$attacker,false);}
}
function FaBPENDeclared(int $player, object $o): void {
    if(!in_array('PEN_CHEAT_WAGER',(array)$o->TurnEffects,true)||!FaBFaiHeroHit())return;
    foreach((array)$o->TurnEffects as $tag)if($tag==='PEN_CHEAT_WAGER')FaBHVYWager($player,intval($o->UniqueID),intval(FaBGetState()['defender']),['gold']);
}

function FaBPENPrintedKeywords(object $o): array {
    $remove=match($o->CardID){'boo_resident_spook_yellow'=>['Spellvoid 2'],'skera_strapping','volcanic_vice'=>['Spellvoid 3'],'mask_of_the_swarming_claw'=>['Spellvoid X'],'gloves_of_azure_waves'=>['Blade Break'],'touch_of_reality'=>['Ward X'],default=>[]};
    return array_values(array_diff((array)CardCard_keywords($o->CardID),$remove));
}
function FaBPENCombatPower(int $player, object $o): int {
    if(!FaBWTRIsAttackAction($o))return 0;$n=0;
    foreach(FaBLiveSeats() as $p){$n-=count(FaBMONArena($p,'haboob'));if($p!==$player)$n-=FaBARCEffect($p,'PEN_VICTORIA');}return $n;
}
