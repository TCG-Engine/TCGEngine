<?php

/* Welcome to Rathe semantic rules layer.
 * Card families add typed effects; combat/cost/turn hooks consume them. This keeps
 * the individual cards declarative and lets later replacement/listener macros
 * interact with the same events instead of duplicating whole card resolutions.
 */

function FaBWTRBase(string $cardID): string {
    return preg_replace('/_(red|yellow|blue)$/', '', strtolower($cardID));
}

function FaBWTRPitchValue(string $cardID, array $values): int {
    if (str_ends_with($cardID, '_red')) return intval($values[0] ?? 0);
    if (str_ends_with($cardID, '_yellow')) return intval($values[1] ?? 0);
    return intval($values[2] ?? 0);
}

function FaBWTRAddEffect(int $player, string $type, int $amount = 0, array $data = [], bool $nextTurn = false): void {
    $state = FaBGetState(); $bucket = $nextTurn ? 'nextTurnEffects' : 'turnEffects'; $key = (string)$player;
    if (!isset($state[$bucket][$key]) || !is_array($state[$bucket][$key])) $state[$bucket][$key] = [];
    $state[$bucket][$key][] = array_merge(['type' => $type, 'amount' => $amount, 'uses' => 1], $data);
    FaBSetState($state);
}

function FaBWTREffects(int $player): array {
    $state = FaBGetState(); $effects = $state['turnEffects'][(string)$player] ?? [];
    return array_values(array_filter($effects, function ($effect) {
        if (empty($effect['persistentUID'])) return true;
        $source=FaBFindUID(intval($effect['persistentUID']));
        return $source !== null && $source['zone'] === 'Arena';
    }));
}

function FaBWTRMayGoAgain(int $player): bool {
    foreach(FaBWTREffects($player) as $effect) if (($effect['type']??'')==='NO_GO_AGAIN') return false;
    return true;
}

function FaBWTRHeroActive(int $player): bool {
    foreach(FaBWTREffects($player) as $effect) if (($effect['type']??'')==='NO_HERO_ABILITY') return false;
    return true;
}

function FaBWTRSetEffects(int $player, array $effects): void {
    $state = FaBGetState(); $state['turnEffects'][(string)$player] = array_values($effects); FaBSetState($state);
}

function FaBWTRTag(object $obj, string $tag): void {
    $effects = is_array($obj->TurnEffects ?? null) ? $obj->TurnEffects : [];
    $effects[] = $tag; $obj->TurnEffects = array_values($effects);
}

function FaBWTRTagValue(object $obj, string $prefix): int {
    foreach ((array)($obj->TurnEffects ?? []) as $effect) if (str_starts_with((string)$effect, $prefix . ':')) return intval(substr((string)$effect, strlen($prefix) + 1));
    return 0;
}

function FaBWTRIsWeapon(object $obj): bool { return FaBHasType($obj, 'Weapon'); }
function FaBWTRIsAttackAction(object $obj): bool { return FaBHasType($obj, 'Attack') && FaBHasType($obj, 'Action'); }
function FaBWTRDefendedFromHand(array $state): bool { return !empty($state['handBlockUIDs']); }

function FaBWTRCanPlay(int $player, array $found, array $state): bool {
    $id = (string)$found['object']->CardID; $base = FaBWTRBase($id);
    $mandatoryDiscard = ['alpha_rampage','bloodrush_bellow','breakneck_battery','primeval_bellow','reckless_swing','savage_feast','savage_swing','wrecker_romp'];
    $otherHand = FaBHandCount($player) - ($found['zone'] === 'Hand' ? 1 : 0);
    if (in_array($base, $mandatoryDiscard, true) && $otherHand < 1) return false;
    if (FaBWTRNeedsDiscard($id)) {
        $pitch=[];foreach(GetHand($player)as$obj)if(is_object($obj)&&empty($obj->removed)&&intval($obj->UniqueID)!==intval($found['object']->UniqueID))$pitch[]=max(0,intval(CardPitch($obj->CardID)));
        if(!$pitch || FaBAvailablePitch($player,intval($found['object']->UniqueID))-min($pitch)<FaBCardCost($found['object'],$player))return false;
    }
    if ($base === 'demolition_crew') {
        foreach (GetHand($player) as $obj) if (is_object($obj) && intval($obj->UniqueID) !== intval($found['object']->UniqueID) && intval(CardCost($obj->CardID)) >= 2) return true;
        return false;
    }
    if ($base === 'flock_of_the_feather_walkers') {
        foreach (GetHand($player) as $obj) if (is_object($obj) && empty($obj->removed) && intval($obj->UniqueID) !== intval($found['object']->UniqueID) && is_numeric(CardCost($obj->CardID)) && intval(CardCost($obj->CardID)) <= 1) return true;
        return false;
    }
    if ($id === 'enlightened_strike_red' && $otherHand < 1) return false;
    if (FaBHasType($id, 'Attack Reaction') && $state['window'] === 'REACTION') {
        $attack = FaBFindUID(intval($state['attackUID']));
        if ($attack === null || intval($state['attacker']) !== $player) return false;
        $obj = $attack['object'];
        if ($base === 'ancestral_empowerment') return FaBWTRIsAttackAction($obj) && FaBHasType($obj, 'Ninja');
        if ($base === 'pummel') return (FaBWTRIsWeapon($obj) && (FaBHasType($obj,'Club') || FaBHasType($obj,'Hammer'))) || (FaBWTRIsAttackAction($obj) && intval(CardCost($obj->CardID)) >= 2);
        if ($base === 'razor_reflex') return (FaBWTRIsWeapon($obj) && (FaBHasType($obj,'Dagger') || FaBHasType($obj,'Sword'))) || (FaBWTRIsAttackAction($obj) && intval(CardCost($obj->CardID)) <= 1);
        if ($base === 'ironsong_response' && !FaBWTRDefendedFromHand($state)) return false;
        return FaBWTRIsWeapon($obj);
    }
    if (FaBHasType($id, 'Defense Reaction') && $found['zone'] === 'Hand' && FaBCurrentAttackHasKeyword($state,'Dominate') && FaBHandDefendingCount($state,$player) >= 1) return false;
    if (FaBHasType($id, 'Defense Reaction') && $state['window'] === 'REACTION') return FaBIsDefendingHero($player,$state);
    return true;
}

function FaBWTRCardDiscarded(int $player, string $cardID): void {
    if (intval(CardPower($cardID)) < 6) return;
    $hero = GetHero($player); $heroID = (string)($hero[0]->CardID ?? '');
    if (FaBWTRHeroActive($player) && in_array($heroID, ['rhinar','rhinar_reckless_rampage'], true) && intval(GetTurnPlayer()) === $player && GetCurrentPhase()==='MAIN') {
        FaBRequestIntimidate($player);
    }
    foreach (GetWeapons($player) as $weapon) if (is_object($weapon) && empty($weapon->removed) && $weapon->CardID === 'romping_club' && intval(FaBObjectCounters($weapon)['DISCARD_TURN']??0)!==intval(GetTurnNumber())) {
        FaBWTRTag($weapon, 'WTR_POWER:1'); FaBSetObjectCounter($weapon,'DISCARD_TURN',intval(GetTurnNumber()));
    }
}

function FaBWTRCardPitched(int $player, string $cardID): void {
    if ($cardID !== 'heart_of_fyendal_blue') return;
    foreach (FaBOpponents($player) as $opponent) if (intval(GetHealth($player)) < intval(GetHealth($opponent))) { AddHealth($player, intval(GetHealth($player)) + 1); break; }
}

function FaBWTRPreventDamage(int $player, int $amount, string $damageType): int {
    $remaining=[];foreach(FaBWTREffects($player)as$effect){if(($effect['type']??'')!=='PREVENT_DAMAGE'||$amount<=0){$remaining[]=$effect;continue;}$prevent=min($amount,max(0,intval($effect['amount']??0)));$amount-=$prevent;$left=intval($effect['amount']??0)-$prevent;if($left>0){$effect['amount']=$left;$remaining[]=$effect;}}
    FaBWTRSetEffects($player,$remaining);return $amount;
}

function FaBWTRPayAdditionalCosts(int $player, object $stackObj): void {
    $base = FaBWTRBase($stackObj->CardID); $mandatory = ['alpha_rampage','bloodrush_bellow','breakneck_battery','primeval_bellow','reckless_swing','savage_feast','savage_swing','wrecker_romp'];
    if (in_array($base, $mandatory, true)) {
        $uids = FaBDiscardRandom($player, 1); $discard = empty($uids) ? null : FaBFindUID($uids[0]);
        $stackObj->Params = array_merge((array)$stackObj->Params, ['discardedPower' => intval(CardPower($discard['object']->CardID ?? ''))]);
    }
}

function FaBWTRCardPlayed(int $player, string $mzID, string $cardID, string $fromZone): void {
    $found = FaBIdentityFromMZ($mzID); if ($found === null) return; $obj = $found['object'];
    $state = FaBGetState(); $state['cardsPlayedThisTurn'][(string)$player][] = $cardID; FaBSetState($state);
    $effects = FaBWTREffects($player); $remaining = [];
    foreach ($effects as $effect) {
        $type = (string)($effect['type'] ?? ''); $applies = false;
        if ($type === 'NEXT_ATTACK') $applies = FaBHasType($cardID, 'Attack') || FaBWTRIsWeapon($obj);
        elseif ($type === 'NEXT_AA_LOW') $applies = FaBWTRIsAttackAction($obj) && intval(CardCost($cardID)) <= 1;
        elseif ($type === 'NEXT_AA_HIGH') $applies = FaBWTRIsAttackAction($obj) && intval(CardCost($cardID)) >= 2;
        elseif ($type === 'NEXT_BRUTE') $applies = FaBHasType($cardID, 'Brute') && (FaBHasType($cardID, 'Attack') || FaBWTRIsWeapon($obj));
        elseif ($type === 'NEXT_BRUTE_ACTION') $applies = FaBHasType($cardID,'Brute') && FaBWTRIsAttackAction($obj);
        elseif ($type === 'NEXT_GUARDIAN') $applies = FaBHasType($cardID, 'Guardian') && FaBWTRIsAttackAction($obj);
        elseif ($type === 'NEXT_WEAPON') $applies = FaBWTRIsWeapon($obj);
        elseif ($type === 'NEXT_COST') $applies = FaBWTRIsAttackAction($obj);
        elseif ($type === 'NEXT_GUARDIAN_COST') $applies = FaBWTRIsAttackAction($obj) && FaBHasType($cardID,'Guardian');
        elseif ($type === 'FIRST_ACTION_COST') $applies = FaBHasType($cardID,'Action') || FaBWTRIsWeapon($obj);
        if (!$applies) { $remaining[] = $effect; continue; }
        if (in_array($type,['FIRST_ACTION_COST','NEXT_COST','NEXT_GUARDIAN_COST'],true)) continue;
        if (!empty($effect['conditionalBlocks'])) FaBWTRTag($obj, 'WTR_LESS_THAN_TWO_BLOCKS:' . intval($effect['amount']));
        else if (intval($effect['amount'] ?? 0) !== 0) FaBWTRTag($obj, 'WTR_POWER:' . intval($effect['amount']));
        if (!empty($effect['goAgain'])) FaBWTRTag($obj, 'GO_AGAIN');
        if (!empty($effect['hitGoAgain'])) FaBWTRTag($obj, 'WTR_HIT_GO_AGAIN');
        if (!empty($effect['dominate'])) FaBWTRTag($obj, 'DOMINATE');
        if (!empty($effect['nature'])) FaBWTRTag($obj, 'WTR_NATURE_HIT');
    }
    FaBWTRSetEffects($player, $remaining);

    FaBFaiCardPlayed($player,$obj);
    FaBARCCardPlayed($player,$obj,$fromZone);

    if (FaBWTRIsAttackAction($obj) || FaBWTRIsWeapon($obj)) {
        foreach(GetArena($player)as$aura)if(is_object($aura)&&empty($aura->removed)&&$aura->CardID==='quicken'){
            FaBWTRTag($obj,'GO_AGAIN'); FaBMoveUID(intval($aura->UniqueID),'Graveyard',$player);
        }
    }
    if(FaBWTRIsAttackAction($obj)&&intval(CardCost($cardID))>=3)foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='BRAVO_DOMINATE')FaBWTRTag($obj,'DOMINATE');
    if(FaBWTRIsWeapon($obj)){
        $weapon=FaBFindUID(intval(FaBObjectCounters($obj)['WEAPON_UID']??0));
        if($weapon!==null)foreach((array)$weapon['object']->TurnEffects as$tag)if(in_array($tag,['DOMINATE','WTR_DRAW_HIT'],true))FaBWTRTag($obj,$tag);
    }

    $base = FaBWTRBase($cardID);
    if ($base === 'scar_for_a_scar') foreach (FaBOpponents($player) as $opponent) if (intval(GetHealth($player)) < intval(GetHealth($opponent))) {
        FaBWTRTag($obj, 'GO_AGAIN'); break;
    }
    if ($base === 'wounded_bull') foreach (FaBOpponents($player) as $opponent) if (intval(GetHealth($player)) < intval(GetHealth($opponent))) {
        FaBWTRTag($obj, 'WTR_WOUNDED_BULL'); break;
    }
    if ($cardID === 'last_ditch_effort_blue' && count(FaBChoiceRefs($player,'Deck')) === 0) { FaBWTRTag($obj, 'WTR_POWER:4'); FaBWTRTag($obj, 'GO_AGAIN'); }
    if (in_array('WTR_NIMBLE_PAID',(array)($obj->TurnEffects??[]),true)){FaBWTRTag($obj,'WTR_POWER:1');FaBWTRTag($obj,'GO_AGAIN');}
    if (in_array('WTR_SLOG_PAID',(array)($obj->TurnEffects??[]),true))FaBWTRTag($obj,'DOMINATE');
    if ($base === 'unmovable' && $fromZone === 'Arsenal') FaBWTRTag($obj, 'WTR_DEFENSE:1');
}

function FaBWTRResolveCard(int $player, object $source, ?object $resolved): void {
    $id = (string)$source->CardID; $base = FaBWTRBase($id); $params = (array)($source->Params ?? []);
    $buffs = [
        'awakening_bellow'=>['NEXT_BRUTE_ACTION',[3,2,1]], 'barraging_beatdown'=>['NEXT_BRUTE',[4,3,2]],
        'primeval_bellow'=>['NEXT_BRUTE',[5,4,3]], 'nimblism'=>['NEXT_AA_LOW',[3,2,1]],
        'sloggism'=>['NEXT_AA_HIGH',[6,5,4]], 'sharpen_steel'=>['NEXT_WEAPON',[3,2,1]],
        'driving_blade'=>['NEXT_WEAPON',[3,2,1]], 'warriors_valor'=>['NEXT_WEAPON',[3,2,1]],
        'natures_path_pilgrimage'=>['NEXT_WEAPON',[3,2,1]],
    ];
    if (isset($buffs[$base])) {
        [$type,$values] = $buffs[$base]; $data = [];
        if ($base === 'barraging_beatdown') $data['conditionalBlocks'] = true;
        if ($base === 'driving_blade') $data['goAgain'] = true;
        if ($base === 'warriors_valor') $data['hitGoAgain'] = true;
        if ($base === 'natures_path_pilgrimage') $data['nature'] = true;
        FaBWTRAddEffect($player, $type, FaBWTRPitchValue($id, $values), $data);
        if (in_array($base, ['awakening_bellow','barraging_beatdown'], true)) FaBRequestIntimidate($player);
        return;
    }
    if ($base === 'sigil_of_solace') { AddHealth($player, intval(GetHealth($player)) + FaBWTRPitchValue($id,[3,2,1])); return; }
    if ($id === 'tome_of_fyendal_yellow') { DoDrawCard($player,2); if(($source->SourceZone??'')==='Arsenal') AddHealth($player,intval(GetHealth($player))+FaBHandCount($player)); return; }
    if ($id === 'bloodrush_bellow_yellow') { FaBWTRAddEffect($player,'BRUTE_TURN',2); if(intval($params['discardedPower']??0)>=6){DoDrawCard($player,2);if(FaBWTRMayGoAgain($player))AddActionPoints($player,intval(GetActionPoints($player))+1);} return; }
    if ($base === 'breakneck_battery' && intval($params['discardedPower']??0)>=6 && $resolved) { FaBWTRTag($resolved,'GO_AGAIN'); return; }
    if ($base === 'savage_feast' && intval($params['discardedPower']??0)>=6) { DoDrawCard($player,1); return; }
    if ($id === 'enlightened_strike_red' && $resolved) { if(intval($params['estrikeMode']??-1)===0)DoDrawCard($player,1); return; }
    if ($id === 'bone_head_barrier_yellow') { FaBWTRAddEffect($player,'PREVENT_DAMAGE',EngineRandomInt(1,6)); return; }
    if ($id === 'reckless_swing_blue' && intval($params['discardedPower']??0)>=6) DoDamage($player,'',intval(FaBGetState()['attacker']),2,'PHYSICAL');
    if ($base === 'blessing_of_deliverance') foreach (GetPitch($player) as $pitched) if (is_object($pitched) && empty($pitched->removed) && intval(CardCost($pitched->CardID))>=3) { DoDrawCard($player,1); break; }
    if ($base === 'flic_flak') FaBWTRAddEffect($player,'NEXT_COMBO_DEFENSE',2);
    if ($base === 'stonewall_confidence') FaBWTRAddEffect($player,'DEFENSE_HIGH_COST',FaBWTRPitchValue($id,[4,3,2]),['persistentUID'=>intval($resolved->UniqueID??0),'uses'=>99]);
    if ($id === 'forged_for_war_yellow') FaBWTRAddEffect($player,'EQUIPMENT_DEFENSE',1,['persistentUID'=>intval($resolved->UniqueID??0),'uses'=>99]);
    if ($base === 'pummel' || $base === 'razor_reflex' || in_array($base,['rout','overpower','ironsong_response','biting_blade','stroke_of_foresight','ancestral_empowerment','glint_the_quicksilver','singing_steelblade'],true)) FaBWTRResolveReaction($player,$source);
}

function FaBWTRResolveReaction(int $player, object $source): void {
    $state=FaBGetState(); $attack=FaBFindUID(intval($state['attackUID']??0)); if($attack===null)return;
    $id=$source->CardID; $base=FaBWTRBase($id); $amount=0;
    $maps=['pummel'=>[4,3,2],'razor_reflex'=>[3,2,1],'overpower'=>[4,3,2],'ironsong_response'=>[3,2,1],'biting_blade'=>[3,2,1],'stroke_of_foresight'=>[3,2,1]];
    if(isset($maps[$base])) $amount=FaBWTRPitchValue($id,$maps[$base]);
    if($base==='overpower' && FaBWTRDefendedFromHand($state)) $amount+=2;
    if($base==='ironsong_response' && !FaBWTRDefendedFromHand($state)) $amount=0;
    if($base==='rout')$amount=3; if($base==='ancestral_empowerment')$amount=1; if($base==='glint_the_quicksilver')FaBWTRTag($attack['object'],'GO_AGAIN');
    if($amount)FaBWTRTag($attack['object'],'WTR_POWER:'.$amount);
    if($base==='ancestral_empowerment')DoDrawCard($player,1);
    if($base==='glint_the_quicksilver' && FaBWTRDefendedFromHand($state))DoDrawCard($player,1);
    if($base==='pummel'&&FaBWTRIsAttackAction($attack['object']))FaBWTRTag($attack['object'],'WTR_PUMMEL_HIT');
    if($base==='razor_reflex'&&FaBWTRIsAttackAction($attack['object']))FaBWTRTag($attack['object'],'WTR_HIT_GO_AGAIN');
    if($base==='biting_blade'&&FaBWTRDefendedFromHand($state))foreach(GetWeapons($player)as$weapon)if(is_object($weapon))FaBWTRTag($weapon,'WTR_POWER:1');
}

function FaBWTRAttackDeclared(int $player, object $attack, int $defender): void {
    $id=$attack->CardID;$base=FaBWTRBase($id);$state=FaBGetState();
    if(in_array($base,['pack_hunt','smash_instinct'],true)||$base==='alpha_rampage')FaBRequestIntimidate($player);
    $combo=[
      'rising_knee_thrust'=>['leg_tap',2,true,false], 'blackout_kick'=>['rising_knee_thrust',3,false,false],
      'open_the_center'=>['head_jab',1,true,true],
      'hurricane_technique'=>['rising_knee_thrust',1,true,false], 'mugenshi_release'=>['whelming_gustwave',1,true,false],
    ];
    if(isset($combo[$base])){[$required,$power,$go,$dom]=$combo[$base];if(FaBWTRBase((string)$state['previousAttackCardID'])===$required){if($power)FaBWTRTag($attack,'WTR_POWER:'.$power);if($go)FaBWTRTag($attack,'GO_AGAIN');if($dom)FaBWTRTag($attack,'DOMINATE');if($base==='hurricane_technique')FaBWTRTag($attack,'WTR_RETURN_HAND');}}
    if($base==='pounding_gale'&&FaBWTRBase((string)$state['previousAttackCardID'])==='open_the_center')FaBWTRTag($attack,'WTR_DOUBLE_DAMAGE');
    if($base==='flock_of_the_feather_walkers')FaBWTRCreateArena($player,'quicken');
    foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='WEAPON_TURN'&&FaBWTRIsWeapon($attack)){FaBWTRTag($attack,'WTR_POWER:'.intval($effect['amount']));if(!empty($effect['dominate']))FaBWTRTag($attack,'DOMINATE');if(!empty($effect['drawOnHit']))FaBWTRTag($attack,'WTR_DRAW_HIT');}
}

function FaBWTRNonEquipmentBlockCount(array $state): int {
    $n=0;foreach(FaBSeatOrder() as $seat)foreach(GetCombatChain($seat) as $obj)if(is_object($obj)&&empty($obj->removed)&&intval($obj->ChainLink??0)===intval($state['chainLink'])&&in_array($obj->Role??'',['DEFENSE','DEFENSE_REACTION'],true)&&($obj->FromZone??'')!=='Equipment')++$n;return $n;
}

function FaBWTRAttackPowerModifier(int $player, object $attack, array $state): int {
    $delta=0;foreach((array)($attack->TurnEffects??[]) as $effect){if(str_starts_with((string)$effect,'WTR_POWER:'))$delta+=intval(substr($effect,10));if(str_starts_with((string)$effect,'WTR_LESS_THAN_TWO_BLOCKS:')&&FaBWTRNonEquipmentBlockCount($state)<2)$delta+=intval(substr($effect,25));}
    $delta+=FaBFaiPower($player,$attack);
    $base=FaBWTRBase($attack->CardID);
    if($base==='barraging_brawnhide'&&FaBWTRNonEquipmentBlockCount($state)<2)$delta++;
    if($base==='fluster_fist'&&FaBWTRBase((string)$state['previousAttackCardID'])==='open_the_center')$delta+=intval($state['chainHits']??0);
    foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='BRUTE_TURN'&&FaBHasType($attack,'Brute'))$delta+=intval($effect['amount']);
    if(FaBWTRIsWeapon($attack)){
        $source=FaBFindUID(intval(FaBObjectCounters($attack)['WEAPON_UID']??0));
        if($source!==null){$delta+=intval(FaBObjectCounters($source['object'])['POWER']??0);foreach((array)$source['object']->TurnEffects as$tag)if(str_starts_with($tag,'WTR_POWER:'))$delta+=intval(substr($tag,10));}
    }
    if($attack->CardID==='anothos'){ $high=0;foreach(GetPitch($player)as$o)if(is_object($o)&&intval(CardCost($o->CardID))>=3)++$high;if($high>=2)$delta+=2; }
    return $delta;
}

function FaBWTRAttackHasGoAgain(array $state, object $attack): bool {
    foreach(FaBWTREffects(intval($state['attacker']))as$effect)if(($effect['type']??'')==='NO_GO_AGAIN')return false;
    if(in_array('WTR_HIT_GO_AGAIN',(array)($attack->TurnEffects??[]),true)&&!empty($state['attackHit']))return true;
    if($attack->CardID==='harmonized_kodachi')foreach(GetPitch(intval($state['attacker']))as$o)if(is_object($o)&&empty($o->removed)&&is_numeric(CardCost($o->CardID))&&intval(CardCost($o->CardID))===0)return true;
    return false;
}

function FaBWTRMoveReplacement(object $obj,string $destination,int $owner):bool{
    if($destination!=='Graveyard')return false;if(FaBWTRBase($obj->CardID)==='drone_of_brutality'){FaBMoveUID(intval($obj->UniqueID),'Deck',$owner);return true;}if(in_array('WTR_RETURN_HAND',(array)($obj->TurnEffects??[]),true)&&!empty(FaBGetState()['attackHit'])){FaBMoveUID(intval($obj->UniqueID),'Hand',$owner);return true;}return false;
}

function FaBWTRApplyNextDefense(int $player, object $obj): void {
    $remaining=[];
    foreach(FaBWTREffects($player)as$effect){
        if(($effect['type']??'')==='NEXT_COMBO_DEFENSE'){
            if(FaBHasKeyword($obj,'Combo'))FaBWTRTag($obj,'WTR_DEFENSE:'.intval($effect['amount']));
        }else $remaining[]=$effect;
    }
    FaBWTRSetEffects($player,$remaining);
}

function FaBWTRDefenseModifier(int $player, object $obj): int {
    $delta=FaBWTRIsAttackAction($obj)?FaBFaiEffect($player,'AOW_STATS'):0;
    foreach((array)($obj->TurnEffects??[])as$tag)if(str_starts_with($tag,'WTR_DEFENSE:'))$delta+=intval(substr($tag,12));
    foreach(FaBWTREffects($player)as$e){
        $type=$e['type']??'';
        if($type==='DEFENSE_HIGH_COST'&&intval(CardCost($obj->CardID))>=3)$delta+=intval($e['amount']);
        if($type==='EQUIPMENT_DEFENSE'&&FaBHasType($obj,'Equipment'))$delta+=intval($e['amount']);
    }
    return $delta;
}

function FaBWTRCostModifier(int $player, object $obj): int {
    $delta=-intval(FaBObjectCounters($obj)['FAI_RESENTMENT']??0);
    foreach(FaBWTREffects($player)as$e){
        $type=$e['type']??'';
        if($type==='NEXT_COST'&&FaBWTRIsAttackAction($obj))$delta-=intval($e['amount']);
        if($type==='NEXT_GUARDIAN_COST'&&FaBWTRIsAttackAction($obj)&&FaBHasType($obj,'Guardian'))$delta-=intval($e['amount']);
        if($type==='FIRST_ACTION_COST'&&FaBHasType($obj,'Action'))$delta+=intval($e['amount']);
    }
    return $delta;
}

function FaBWTRHit(int $player, object $attack, int $amount): void {
    $state=FaBGetState();
    $state['hitsThisTurn'][(string)$player][]=intval($attack->UniqueID);
    $state['chainHits']=intval($state['chainHits']??0)+1;
    $state['consecutiveHits']=intval($state['consecutiveHits']??0)+1;
    if(FaBWTRIsAttackAction($attack))$state['attackActionHits'][(string)$player]=intval($state['attackActionHits'][(string)$player]??0)+1;
    $weaponUID=intval(FaBObjectCounters($attack)['WEAPON_UID']??0);
    if($weaponUID>0)$state['weaponHits'][(string)$weaponUID]=intval($state['weaponHits'][(string)$weaponUID]??0)+1;
    FaBSetState($state);
    $base=FaBWTRBase($attack->CardID);
    if($base==='snatch')DoDrawCard($player,1);
    if(in_array('WTR_DRAW_HIT',(array)$attack->TurnEffects,true))DoDrawCard($player,1);
    if(in_array('WTR_PUMMEL_HIT',(array)$attack->TurnEffects,true))FaBRunSourceMacro('Hit',$player,'pummel_red',['mzID'=>FaBFindUID(intval($attack->UniqueID))['mzID'],'amount'=>$amount]);
    if(in_array('WTR_NATURE_HIT',(array)$attack->TurnEffects,true)&&empty(FaBChoiceRefs($player,'Arsenal'))){
        $topRefs=FaBChoiceRefs($player,'Deck');$topRef=$topRefs[0]??'';
        FaBRevealChoices($player,$topRef);
        $top=FaBIdentityFromMZ($topRef);
        if($top!==null&&FaBHasType($top['object'],'Action'))FaBMoveUID(intval($top['object']->UniqueID),'Arsenal',$player);
    }
    if($amount>=4)FaBWTRCrush($player,intval($state['defender']),$attack);
    $hero=GetHero($player);$heroObj=$hero[0]??null;$heroID=$heroObj->CardID??'';
    if(FaBWTRHeroActive($player)&&in_array($heroID,['dorinthea','dorinthea_ironsong'],true)&&$weaponUID>0&&intval(FaBObjectCounters($heroObj)['USED_TURN']??0)!==intval(GetTurnNumber())){
        $weapon=FaBFindUID($weaponUID);
        if($weapon!==null)$weapon['object']->Status=2;
        FaBSetObjectCounter($heroObj,'USED_TURN',intval(GetTurnNumber()));
    }
    foreach(GetEquipment($player)as$equipment)if(is_object($equipment)&&empty($equipment->removed)&&$equipment->CardID==='mask_of_momentum'&&FaBWTRIsAttackAction($attack)&&intval($state['consecutiveHits'])>=3&&intval(FaBObjectCounters($equipment)['DRAWN_TURN']??0)!==intval(GetTurnNumber())){
        DoDrawCard($player,1);FaBSetObjectCounter($equipment,'DRAWN_TURN',intval(GetTurnNumber()));
    }
    if($attack->CardID==='dawnblade'&&$weaponUID>0&&intval($state['weaponHits'][(string)$weaponUID]??0)===2){
        $weapon=FaBFindUID($weaponUID);
        if($weapon!==null)FaBSetObjectCounter($weapon['object'],'POWER',intval(FaBObjectCounters($weapon['object'])['POWER']??0)+1);
    }
}

function FaBWTRNeedsDiscard(string $id): bool {
    return in_array(FaBWTRBase($id),['alpha_rampage','bloodrush_bellow','breakneck_battery','primeval_bellow','reckless_swing','savage_feast','savage_swing','wrecker_romp'],true);
}
function FaBWTRCanDraw(int $player):bool{foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='NO_DRAW_ACTION_PHASE'&&GetCurrentPhase()==='MAIN')return false;return true;}
function FaBWTRIntellectModifier(int $player):int{foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='INTELLECT')return intval($effect['amount']);return 0;}

function FaBWTRCrush(int $player,int $defender,object $attack):void{
    $base=FaBWTRBase($attack->CardID);
    if($base==='disable'){foreach(GetArsenal($defender)as$o)if(is_object($o)){FaBMoveUID(intval($o->UniqueID),'Deck',$defender);break;}}
    elseif($base==='cartilage_crush')FaBWTRAddEffect($defender,'FIRST_ACTION_COST',1,[],true);
    elseif($base==='debilitate')FaBWTRAddEffect($defender,'NEXT_ATTACK',-2,[],true);
    elseif($base==='cranial_crush')FaBWTRAddEffect($defender,'NO_DRAW_ACTION_PHASE',0,[],true);
    elseif($base==='spinal_crush')FaBWTRAddEffect($defender,'NO_GO_AGAIN',0,[],true);
    elseif($base==='crush_confidence')FaBWTRAddEffect($defender,'NO_HERO_ABILITY',0,['expiresAfterTurnOf'=>$defender]);
}

function FaBWTRDefended(int $player,object $card):void { if(FaBWTRBase($card->CardID)==='steelblade_shunt'){ $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']??0));if($a!==null&&FaBWTRIsWeapon($a['object']))DoDamage($player,'',intval($s['attacker']),1,'PHYSICAL'); } }

function FaBWTRCreateArena(int $player,string $cardID):?object { return AddArena($player,CardID:$cardID,Owner:$player,Controller:$player,Status:2); }

function FaBWTRCanActivate(int $player,string $mzID):bool {
    if(!FaBSeatIsLive($player)||intval(GetWinner())!==0||intval(GetPriorityPlayer())!==$player||FaBHasPendingDecision())return false;
    $f=FaBIdentityFromMZ($mzID);if($f===null||$f['player']!==$player)return false;
    $state=FaBGetState();if($state['pendingPayment']!==null)return false;
    $id=$f['object']->CardID;
    if(FaBARCAbilityActions($player,$f))return true;
    $weapons=['anothos'=>3,'romping_club'=>2,'dawnblade'=>1,'harmonized_kodachi'=>1,'nebula_blade'=>2,'teklo_plasma_pistol'=>0,'teklo_blaster'=>FaBTekloBlasterCost($player)];
    if(isset($weapons[$id])){
        if(FaBARCEffect($player,'ARC_LEDGER')&&FaBARCEffect($player,'ARC_ACTIONS')>=1)return false;
        $spec=['timing'=>'ACTION','cost'=>$weapons[$id]+FaBARCEffect($player,'ARC_FIRST_ATTACK_COST')];
        return $f['zone']==='Weapons'&&intval(GetTurnPlayer())===$player&&($id==='teklo_plasma_pistol'?intval(FaBObjectCounters($f['object'])['STEAM']??0)>0:intval($f['object']->Status??2)===2)&&in_array($state['window'],['ACTION','RESOLUTION'],true)&&intval(GetActionPoints($player))>0&&FaBAvailablePitch($player)>=FaBWTRAbilityCost($player,$spec);
    }
    $spec=FaBWTRAbilitySpec($id);
    return $spec!==null&&FaBWTRAbilityLegal($player,$f,$spec);
}

function FaBWTRActivate(int $player,string $mzID,int $index=0):bool {
    if(!FaBWTRCanActivate($player,$mzID))return false;
    $f=FaBIdentityFromMZ($mzID);$o=$f['object'];$id=$o->CardID;
    if(isset(FaBARCAbilityActions($player,$f)[$index]))return FaBARCActivate($player,$f,$index);
    SaveUndoVersion($player,'Before activating '.(CardName($id)?:$id));
    $weapons=['anothos'=>3,'romping_club'=>2,'dawnblade'=>1,'harmonized_kodachi'=>1,'nebula_blade'=>2,'teklo_plasma_pistol'=>0,'teklo_blaster'=>FaBTekloBlasterCost($player)];
    if(isset($weapons[$id])){
        $weaponUID=intval($o->UniqueID);
        $target=FaBClaimOrRequestAttackTarget($player,$weaponUID,'ACTIVATE');
        if($target===null)return true;
        if($target===false)return false;
        $stack=AddStack(CardID:$id,Controller:$player,Kind:'ATTACK',SourceZone:'Weapons',SourceUniqueID:$weaponUID,Params:['attackTarget'=>$target]);
        $cost=FaBWTRAbilityCost($player,['timing'=>'ACTION','cost'=>$weapons[$id]+FaBARCEffect($player,'ARC_FIRST_ATTACK_COST')]);
        $s=FaBGetState();
        $s['pendingPayment']=['player'=>$player,'uid'=>intval($stack->UniqueID),'weaponUID'=>$weaponUID,'cost'=>$cost,'fromZone'=>'Weapons','kind'=>'ATTACK','isWeaponAttack'=>true,'returnWindow'=>$s['window'],'returnCombatStep'=>$s['combatStep']];
        $s['window']='PITCH';FaBSetState($s);SetConsecutivePasses(0);
        return FaBTryCompletePayment();
    }
    return FaBWTRAnnounceAbility($player,$f,FaBWTRAbilitySpec($id));
}

function FaBWTRStartTurn(int $player):void {
    foreach(GetEquipment($player)as$o)if(is_object($o)&&empty($o->removed)&&$o->CardID==='fyendals_spring_tunic'&&intval(FaBObjectCounters($o)['ENERGY']??0)<3){
        FaBRunSourceMacro('StartTurn',$player,$o->CardID,['mzID'=>FaBFindUID(intval($o->UniqueID))['mzID']]);
    }
    foreach(GetArena($player)as$o){
        if(!is_object($o)||!empty($o->removed))continue;
        $id=$o->CardID;$base=FaBWTRBase($id);
        if($id==='seismic_surge'){FaBWTRAddEffect($player,'NEXT_GUARDIAN_COST',1);FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);}
        elseif($base==='emerging_power'){FaBWTRAddEffect($player,'NEXT_GUARDIAN',FaBWTRPitchValue($id,[3,2,1]));FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);}
        elseif($base==='blessing_of_deliverance'){
            FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);
            $refs=array_slice(FaBChoiceRefs($player,'Deck'),0,FaBWTRPitchValue($id,[3,2,1]));$gain=0;
            FaBRevealChoices($player,implode('&',$refs));
            foreach($refs as$ref){$card=FaBIdentityFromMZ($ref);if(intval(CardCost($card['object']->CardID))>=3)++$gain;}
            AddHealth($player,intval(GetHealth($player))+$gain);
        }
        elseif($base==='show_time'){FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);DoDrawCard($player,1);}
        elseif(in_array($base,['stonewall_confidence','forged_for_war'],true))FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);
    }
}

function FaBWTREndTurn(int $player):void {
    $hits=FaBGetState()['weaponHits']??[];
    foreach(GetWeapons($player)as$o)if(is_object($o)&&empty($o->removed)&&$o->CardID==='dawnblade'&&empty($hits[(string)$o->UniqueID]))FaBSetObjectCounter($o,'POWER',0);
}
