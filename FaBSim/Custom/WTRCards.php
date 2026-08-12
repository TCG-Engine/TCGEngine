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
    $state = FaBGetState(); return is_array($state['turnEffects'][(string)$player] ?? null) ? $state['turnEffects'][(string)$player] : [];
}

function FaBWTRSetEffects(int $player, array $effects): void {
    $state = FaBGetState(); $state['turnEffects'][(string)$player] = array_values($effects); FaBSetState($state);
}

function FaBWTRTag(object $obj, string $tag): void {
    $effects = is_array($obj->TurnEffects ?? null) ? $obj->TurnEffects : [];
    $effects[] = $tag; $obj->TurnEffects = array_values(array_unique($effects));
}

function FaBWTRTagValue(object $obj, string $prefix): int {
    foreach ((array)($obj->TurnEffects ?? []) as $effect) if (str_starts_with((string)$effect, $prefix . ':')) return intval(substr((string)$effect, strlen($prefix) + 1));
    return 0;
}

function FaBWTRIsWeapon(object $obj): bool { return FaBHasType($obj->CardID, 'Weapon'); }
function FaBWTRIsAttackAction(object $obj): bool { return FaBHasType($obj->CardID, 'Attack') && FaBHasType($obj->CardID, 'Action'); }
function FaBWTRDefendedFromHand(array $state): bool { return !empty($state['handBlockUIDs']); }

function FaBWTRCanPlay(int $player, array $found, array $state): bool {
    $id = (string)$found['object']->CardID; $base = FaBWTRBase($id);
    $mandatoryDiscard = ['alpha_rampage','bloodrush_bellow','breakneck_battery','primeval_bellow','reckless_swing','savage_feast','savage_swing','wrecker_romp'];
    if (in_array($base, $mandatoryDiscard, true) && FaBHandCount($player) < 2) return false;
    if ($base === 'demolition_crew') {
        foreach (GetHand($player) as $obj) if (is_object($obj) && intval($obj->UniqueID) !== intval($found['object']->UniqueID) && intval(CardCost($obj->CardID)) >= 2) return true;
        return false;
    }
    if ($base === 'flock_of_the_feather_walkers') {
        foreach (GetHand($player) as $obj) if (is_object($obj) && intval($obj->UniqueID) !== intval($found['object']->UniqueID) && intval(CardCost($obj->CardID)) <= 1) return true;
        return false;
    }
    if ($id === 'enlightened_strike_red' && FaBHandCount($player) < 2) return false;
    if (FaBHasType($id, 'Attack Reaction') && $state['window'] === 'REACTION') return intval($state['attacker']) === $player;
    if (FaBHasType($id, 'Defense Reaction') && $state['window'] === 'REACTION') return intval($state['defender']) === $player;
    return true;
}

function FaBWTRCardDiscarded(int $player, string $cardID): void {
    if (intval(CardPower($cardID)) < 6) return;
    $hero = GetHero($player); $heroID = (string)($hero[0]->CardID ?? '');
    if (in_array($heroID, ['rhinar','rhinar_reckless_rampage'], true) && intval(GetTurnPlayer()) === $player) {
        $defender = FaBDefaultDefender($player); if ($defender > 0) FaBIntimidate($player, $defender, 1);
    }
    foreach (GetWeapons($player) as $weapon) if (is_object($weapon) && $weapon->CardID === 'romping_club') FaBWTRTag($weapon, 'WTR_POWER:1');
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
        $stackObj->Params = ['discardedPower' => intval(CardPower($discard['object']->CardID ?? ''))];
    }
    if ($stackObj->CardID === 'enlightened_strike_red') {
        $uid = FaBRandomHandUID($player); if ($uid > 0) FaBMoveUID($uid, 'Deck', $player, true);
        $stackObj->Params = array_merge((array)$stackObj->Params, ['estrikeMode' => 2]);
    }
    if (in_array($base,['nimble_strike','regurgitating_slog'],true)) { $wanted=$base==='nimble_strike'?'nimblism':'sloggism';foreach(GetGraveyard($player)as$grave)if(is_object($grave)&&FaBWTRBase($grave->CardID)===$wanted){FaBMoveUID(intval($grave->UniqueID),'Banish',$player);FaBWTRTag($stackObj,$base==='nimble_strike'?'WTR_NIMBLE_PAID':'WTR_SLOG_PAID');break;} }
}

function FaBWTRCardPlayed(int $player, string $mzID, string $cardID, string $fromZone): void {
    $found = FaBIdentityFromMZ($mzID); if ($found === null) return; $obj = $found['object'];
    $state = FaBGetState(); $state['cardsPlayedThisTurn'][(string)$player][] = $cardID; FaBSetState($state);
    $effects = FaBWTREffects($player); $remaining = [];
    foreach ($effects as $effect) {
        $type = (string)($effect['type'] ?? ''); $applies = false;
        if ($type === 'NEXT_ATTACK') $applies = FaBHasType($cardID, 'Attack');
        elseif ($type === 'NEXT_AA_LOW') $applies = FaBWTRIsAttackAction($obj) && intval(CardCost($cardID)) <= 1;
        elseif ($type === 'NEXT_AA_HIGH') $applies = FaBWTRIsAttackAction($obj) && intval(CardCost($cardID)) >= 2;
        elseif ($type === 'NEXT_BRUTE') $applies = FaBHasType($cardID, 'Brute') && FaBHasType($cardID, 'Attack');
        elseif ($type === 'NEXT_GUARDIAN') $applies = FaBHasType($cardID, 'Guardian') && FaBWTRIsAttackAction($obj);
        elseif ($type === 'NEXT_WEAPON') $applies = FaBWTRIsWeapon($obj);
        elseif ($type === 'NEXT_COST') $applies = FaBWTRIsAttackAction($obj);
        elseif ($type === 'FIRST_ACTION_COST') $applies = FaBHasType($cardID,'Action');
        if (!$applies) { $remaining[] = $effect; continue; }
        if ($type === 'FIRST_ACTION_COST') continue;
        if (!empty($effect['conditionalBlocks'])) FaBWTRTag($obj, 'WTR_LESS_THAN_TWO_BLOCKS:' . intval($effect['amount']));
        else if (intval($effect['amount'] ?? 0) !== 0) FaBWTRTag($obj, 'WTR_POWER:' . intval($effect['amount']));
        if (!empty($effect['goAgain'])) FaBWTRTag($obj, 'GO_AGAIN');
        if (!empty($effect['hitGoAgain'])) FaBWTRTag($obj, 'WTR_HIT_GO_AGAIN');
        if (!empty($effect['dominate'])) FaBWTRTag($obj, 'DOMINATE');
        if (!empty($effect['nature'])) FaBWTRTag($obj, 'WTR_NATURE_HIT');
    }
    FaBWTRSetEffects($player, $remaining);

    $base = FaBWTRBase($cardID);
    if ($base === 'scar_for_a_scar') foreach (FaBOpponents($player) as $opponent) if (intval(GetHealth($player)) < intval(GetHealth($opponent))) {
        FaBWTRTag($obj, 'GO_AGAIN'); break;
    }
    if ($cardID === 'last_ditch_effort_blue' && count(GetDeck($player)) === 0) { FaBWTRTag($obj, 'WTR_POWER:4'); FaBWTRTag($obj, 'GO_AGAIN'); }
    if (in_array('WTR_NIMBLE_PAID',(array)($obj->TurnEffects??[]),true)){FaBWTRTag($obj,'WTR_POWER:1');FaBWTRTag($obj,'GO_AGAIN');}
    if (in_array('WTR_SLOG_PAID',(array)($obj->TurnEffects??[]),true))FaBWTRTag($obj,'DOMINATE');
    if ($base === 'unmovable' && $fromZone === 'Arsenal') FaBWTRTag($obj, 'WTR_DEFENSE:1');
}

function FaBWTRResolveCard(int $player, object $source, ?object $resolved): void {
    $id = (string)$source->CardID; $base = FaBWTRBase($id); $params = (array)($source->Params ?? []);
    $buffs = [
        'awakening_bellow'=>['NEXT_BRUTE',[3,2,1]], 'barraging_beatdown'=>['NEXT_BRUTE',[4,3,2]],
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
        if (in_array($base, ['awakening_bellow','barraging_beatdown'], true)) { $d=FaBDefaultDefender($player); if($d) FaBIntimidate($player,$d); }
        return;
    }
    if ($base === 'sigil_of_solace') { AddHealth($player, intval(GetHealth($player)) + FaBWTRPitchValue($id,[3,2,1])); return; }
    if ($base === 'sink_below' || $base === 'scour_the_battlescape') { $uid=FaBRandomHandUID($player); if($uid){FaBMoveUID($uid,'Deck',$player);DoDrawCard($player,1);} if($base==='scour_the_battlescape' && ($source->SourceZone??'')==='Arsenal' && $resolved) FaBWTRTag($resolved,'GO_AGAIN'); return; }
    if ($id === 'tome_of_fyendal_yellow') { DoDrawCard($player,2); if(($source->SourceZone??'')==='Arsenal') AddHealth($player,intval(GetHealth($player))+FaBHandCount($player)); return; }
    if ($id === 'bloodrush_bellow_yellow') { FaBWTRAddEffect($player,'NEXT_BRUTE',2); if(intval($params['discardedPower']??0)>=6){DoDrawCard($player,2);AddActionPoints($player,intval(GetActionPoints($player))+1);} return; }
    if ($base === 'breakneck_battery' && intval($params['discardedPower']??0)>=6 && $resolved) { FaBWTRTag($resolved,'GO_AGAIN'); return; }
    if ($base === 'savage_feast' && intval($params['discardedPower']??0)>=6) { DoDrawCard($player,1); return; }
    if ($id === 'enlightened_strike_red' && $resolved) { FaBWTRTag($resolved,'GO_AGAIN'); return; }
    if ($id === 'bone_head_barrier_yellow') { FaBWTRAddEffect($player,'PREVENT_DAMAGE',EngineRandomInt(1,6)); return; }
    if ($id === 'remembrance_yellow') { $moved=0;foreach(GetGraveyard($player)as$grave)if(is_object($grave)&&FaBHasType($grave->CardID,'Action')&&$moved<3){FaBMoveUID(intval($grave->UniqueID),'Deck',$player);++$moved;}if($resolved)FaBMoveUID(intval($resolved->UniqueID),'Banish',$player);return; }
    if ($id === 'sand_sketched_plan_blue') { foreach(GetDeck($player)as$card)if(is_object($card)){FaBMoveUID(intval($card->UniqueID),'Hand',$player);break;}$uids=FaBDiscardRandom($player,1);if(!empty($uids)){$f=FaBFindUID($uids[0]);if(intval(CardPower($f['object']->CardID??''))>=6)AddActionPoints($player,intval(GetActionPoints($player))+2);}return; }
    if ($id === 'show_time_blue') { foreach(GetDeck($player)as$card)if(is_object($card)&&FaBHasType($card->CardID,'Guardian')&&FaBWTRIsAttackAction($card)){FaBMoveUID(intval($card->UniqueID),'Hand',$player);break;}return; }
    if ($id === 'steelblade_supremacy_red') { FaBWTRAddEffect($player,'WEAPON_TURN',2,['drawOnHit'=>true,'uses'=>99]); return; }
    if ($id === 'ironsong_determination_yellow') { FaBWTRAddEffect($player,'WEAPON_TURN',1,['dominate'=>true,'uses'=>99]); return; }
    if ($base === 'staunch_response' && $resolved && intval(GetResources($player))>=4) { AddResources($player,intval(GetResources($player))-4);FaBWTRTag($resolved,'WTR_DEFENSE:3'); }
    if ($id === 'lord_of_wind_blue' && $resolved && FaBWTRBase((string)FaBGetState()['previousAttackCardID'])==='mugenshi_release') { $paid=0;$available=intval(GetResources($player));foreach(GetGraveyard($player)as$grave){if(!is_object($grave)||$paid>=$available)break;if(in_array(FaBWTRBase($grave->CardID),['surging_strike','whelming_gustwave','mugenshi_release'],true)){FaBMoveUID(intval($grave->UniqueID),'Deck',$player);++$paid;}}if($paid){AddResources($player,$available-$paid);FaBWTRTag($resolved,'WTR_POWER:'.$paid);} }
    if ($base === 'flic_flak') FaBWTRAddEffect($player,'NEXT_COMBO_DEFENSE',2);
    if ($base === 'stonewall_confidence') FaBWTRAddEffect($player,'DEFENSE_HIGH_COST',FaBWTRPitchValue($id,[4,3,2]),['persistentUID'=>intval($resolved->UniqueID??0),'uses'=>99]);
    if ($id === 'forged_for_war_yellow') FaBWTRAddEffect($player,'EQUIPMENT_DEFENSE',1,['persistentUID'=>intval($resolved->UniqueID??0),'uses'=>99]);
    if ($base === 'pummel' || $base === 'razor_reflex' || in_array($base,['rout','overpower','ironsong_response','biting_blade','stroke_of_foresight','ancestral_empowerment','glint_the_quicksilver'],true)) FaBWTRResolveReaction($player,$source);
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
    if($base==='pummel')FaBWTRTag($attack['object'],'WTR_PUMMEL_HIT');
    if($base==='razor_reflex')FaBWTRTag($attack['object'],'WTR_HIT_GO_AGAIN');
    if($base==='biting_blade'&&FaBWTRDefendedFromHand($state))foreach(GetWeapons($player)as$weapon)if(is_object($weapon))FaBWTRTag($weapon,'WTR_POWER:1');
    if($base==='stroke_of_foresight'&&FaBWTRDefendedFromHand($state)){DoDrawCard($player,1);$uid=FaBRandomHandUID($player);if($uid)FaBMoveUID($uid,'Deck',$player);}
    if($base==='rout'&&FaBWTRDefendedFromHand($state))foreach(GetCombatChain(intval($state['defender']))as$defense)if(is_object($defense)&&($defense->Role??'')==='DEFENSE'&&($defense->FromZone??'')!=='Equipment'){FaBMoveUID(intval($defense->UniqueID),'Hand',intval($defense->Owner??$state['defender']));break;}
    if($source->CardID==='singing_steelblade_yellow'&&FaBWTRDefendedFromHand($state))foreach(GetDeck($player)as$card)if(is_object($card)&&FaBHasType($card->CardID,'Attack Reaction')){$banished=FaBMoveUID(intval($card->UniqueID),'Banish',$player);if($banished){$banished->PlayableFromBanish=1;$banished->ReturnAtEndTurn=1;}break;}
}

function FaBWTRAttackDeclared(int $player, object $attack, int $defender): void {
    $id=$attack->CardID;$base=FaBWTRBase($id);$state=FaBGetState();
    if(in_array($base,['pack_hunt','smash_instinct'],true)||$base==='alpha_rampage')FaBIntimidate($player,$defender);
    $combo=[
      'rising_knee_thrust'=>['leg_tap',2,true,false], 'blackout_kick'=>['rising_knee_thrust',3,false,false],
      'open_the_center'=>['head_jab',1,true,true],
      'hurricane_technique'=>['rising_knee_thrust',1,true,false], 'mugenshi_release'=>['whelming_gustwave',1,true,false],
    ];
    if(isset($combo[$base])){[$required,$power,$go,$dom]=$combo[$base];if(FaBWTRBase((string)$state['previousAttackCardID'])===$required){if($power)FaBWTRTag($attack,'WTR_POWER:'.$power);if($go)FaBWTRTag($attack,'GO_AGAIN');if($dom)FaBWTRTag($attack,'DOMINATE');if($base==='hurricane_technique')FaBWTRTag($attack,'WTR_RETURN_HAND');}}
    if($base==='pounding_gale'&&FaBWTRBase((string)$state['previousAttackCardID'])==='open_the_center')FaBWTRTag($attack,'WTR_DOUBLE_DAMAGE');
    foreach(GetArena($player)as$aura)if(is_object($aura)&&$aura->CardID==='quicken'){FaBWTRTag($attack,'GO_AGAIN');FaBMoveUID(intval($aura->UniqueID),'Graveyard',$player);break;}
    if($base==='flock_of_the_feather_walkers')FaBWTRCreateArena($player,'quicken');
    foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='WEAPON_TURN'&&FaBWTRIsWeapon($attack)){FaBWTRTag($attack,'WTR_POWER:'.intval($effect['amount']));if(!empty($effect['dominate']))FaBWTRTag($attack,'DOMINATE');if(!empty($effect['drawOnHit']))FaBWTRTag($attack,'WTR_DRAW_HIT');}
}

function FaBWTRNonEquipmentBlockCount(array $state): int {
    $n=0;foreach(FaBSeatOrder() as $seat)foreach(GetCombatChain($seat) as $obj)if(is_object($obj)&&empty($obj->removed)&&intval($obj->ChainLink??0)===intval($state['chainLink'])&&($obj->Role??'')==='DEFENSE'&&($obj->FromZone??'')!=='Equipment')++$n;return $n;
}

function FaBWTRAttackPowerModifier(int $player, object $attack, array $state): int {
    $delta=0;foreach((array)($attack->TurnEffects??[]) as $effect){if(str_starts_with((string)$effect,'WTR_POWER:'))$delta+=intval(substr($effect,10));if(str_starts_with((string)$effect,'WTR_LESS_THAN_TWO_BLOCKS:')&&FaBWTRNonEquipmentBlockCount($state)<2)$delta+=intval(substr($effect,25));}
    $base=FaBWTRBase($attack->CardID);
    if($base==='barraging_brawnhide'&&FaBWTRNonEquipmentBlockCount($state)<2)$delta++;
    if($base==='fluster_fist'&&FaBWTRBase((string)$state['previousAttackCardID'])==='open_the_center')$delta+=count($state['hitsThisTurn'][(string)$player]??[]);
    if($attack->CardID==='anothos'){ $high=0;foreach(GetPitch($player)as$o)if(is_object($o)&&intval(CardCost($o->CardID))>=3)++$high;if($high>=2)$delta+=2; }
    return $delta;
}

function FaBWTRAttackHasGoAgain(array $state, object $attack): bool {
    foreach(FaBWTREffects(intval($state['attacker']))as$effect)if(($effect['type']??'')==='NO_GO_AGAIN')return false;
    if(in_array('WTR_HIT_GO_AGAIN',(array)($attack->TurnEffects??[]),true)&&!empty($state['attackHit']))return true;
    if($attack->CardID==='harmonized_kodachi')foreach(GetPitch(intval($state['attacker']))as$o)if(is_object($o)&&intval(CardCost($o->CardID))===0)return true;
    return false;
}

function FaBWTRMoveReplacement(object $obj,string $destination,int $owner):bool{
    if($destination!=='Graveyard')return false;if(FaBWTRBase($obj->CardID)==='drone_of_brutality'){FaBMoveUID(intval($obj->UniqueID),'Deck',$owner);return true;}if(in_array('WTR_RETURN_HAND',(array)($obj->TurnEffects??[]),true)&&!empty(FaBGetState()['attackHit'])){FaBMoveUID(intval($obj->UniqueID),'Hand',$owner);return true;}return false;
}

function FaBWTRDefenseModifier(int $player, object $obj): int {
    $delta=FaBWTRTagValue($obj,'WTR_DEFENSE');foreach(FaBWTREffects($player)as$e){$type=$e['type']??'';if($type==='NEXT_COMBO_DEFENSE'&&FaBHasKeyword($obj->CardID,'Combo'))$delta+=intval($e['amount']);if($type==='DEFENSE_HIGH_COST'&&intval(CardCost($obj->CardID))>=3)$delta+=intval($e['amount']);if($type==='EQUIPMENT_DEFENSE'&&FaBHasType($obj->CardID,'Equipment'))$delta+=intval($e['amount']);}return $delta;
}

function FaBWTRCostModifier(int $player, object $obj): int { foreach(FaBWTREffects($player)as$e){if(($e['type']??'')==='NEXT_COST'&&FaBWTRIsAttackAction($obj))return-intval($e['amount']);if(($e['type']??'')==='FIRST_ACTION_COST'&&FaBHasType($obj->CardID,'Action'))return intval($e['amount']);}return 0; }

function FaBWTRHit(int $player, object $attack, int $amount): void {
    $state=FaBGetState();$state['hitsThisTurn'][(string)$player][]=intval($attack->UniqueID??0);FaBSetState($state);$base=FaBWTRBase($attack->CardID);
    if($base==='snatch')DoDrawCard($player,1);
    if(in_array('WTR_DRAW_HIT',(array)($attack->TurnEffects??[]),true))DoDrawCard($player,1);
    if(in_array('WTR_PUMMEL_HIT',(array)($attack->TurnEffects??[]),true))FaBDiscardRandom(intval($state['defender']),1);
    if(in_array('WTR_DOUBLE_DAMAGE',(array)($attack->TurnEffects??[]),true))DoDamage($player,'',intval($state['defender']),$amount,'PHYSICAL');
    if(in_array('WTR_NATURE_HIT',(array)($attack->TurnEffects??[]),true)&&empty(GetArsenal($player))){$deck=GetDeck($player);$top=$deck[0]??null;if(is_object($top)&&FaBHasType($top->CardID,'Action'))FaBMoveUID(intval($top->UniqueID),'Arsenal',$player);}
    if($amount>=4)FaBWTRCrush($player,intval($state['defender']),$attack);
    $hero=GetHero($player);$heroID=(string)($hero[0]->CardID??'');
    if(in_array($heroID,['dorinthea','dorinthea_ironsong'],true)&&FaBWTRIsWeapon($attack))foreach(GetWeapons($player)as$weapon)if(is_object($weapon)&&$weapon->CardID===$attack->CardID)$weapon->Status=2;
    foreach(GetEquipment($player)as$equipment)if(is_object($equipment)&&$equipment->CardID==='mask_of_momentum'&&count($state['hitsThisTurn'][(string)$player]??[])>=3){$c=FaBObjectCounters($equipment);if(empty($c['DRAWN_TURN'])){DoDrawCard($player,1);FaBSetObjectCounter($equipment,'DRAWN_TURN',1);}}
    if($attack->CardID==='dawnblade'&&count($state['hitsThisTurn'][(string)$player]??[])===2)foreach(GetWeapons($player)as$weapon)if(is_object($weapon)&&$weapon->CardID==='dawnblade')FaBSetObjectCounter($weapon,'POWER',intval(FaBObjectCounters($weapon)['POWER']??0)+1);
    if(in_array($heroID,['katsu','katsu_the_wanderer'],true)&&count($state['hitsThisTurn'][(string)$player]??[])===1&&FaBWTRIsAttackAction($attack))FaBWTRKatsuSearch($player);
    if($base==='mugenshi_release'&&FaBWTRBase((string)$state['previousAttackCardID'])==='whelming_gustwave')foreach(GetDeck($player)as$card)if(is_object($card)&&$card->CardID==='lord_of_wind_blue')FaBMoveUID(intval($card->UniqueID),'Hand',$player);
}

function FaBWTRKatsuSearch(int $player):void{$discard=0;foreach(GetHand($player)as$card)if(is_object($card)&&intval(CardCost($card->CardID))===0){$discard=intval($card->UniqueID);break;}if(!$discard)return;FaBMoveUID($discard,'Graveyard',$player);foreach(GetDeck($player)as$card)if(is_object($card)&&FaBHasKeyword($card->CardID,'Combo')){$moved=FaBMoveUID(intval($card->UniqueID),'Banish',$player);if($moved){$moved->PlayableFromBanish=1;$moved->ReturnAtEndTurn=1;}break;}}
function FaBWTRCanDraw(int $player):bool{foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='NO_DRAW_ACTION_PHASE'&&GetCurrentPhase()==='MAIN')return false;return true;}
function FaBWTRIntellectModifier(int $player):int{foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='INTELLECT')return intval($effect['amount']);return 0;}

function FaBWTRCrush(int $player,int $defender,object $attack):void{
    $base=FaBWTRBase($attack->CardID);
    if($base==='disable'){foreach(GetArsenal($defender)as$o)if(is_object($o)){FaBMoveUID(intval($o->UniqueID),'Deck',$defender);break;}}
    elseif($base==='buckling_blow'){foreach(GetEquipment($defender)as$o)if(is_object($o)){FaBSetObjectCounter($o,'DEFENSE',intval(FaBObjectCounters($o)['DEFENSE']??0)+1);break;}}
    elseif($base==='cartilage_crush')FaBWTRAddEffect($defender,'FIRST_ACTION_COST',1,[],true);
    elseif($base==='debilitate')FaBWTRAddEffect($defender,'NEXT_ATTACK',-2,[],true);
    elseif($base==='cranial_crush')FaBWTRAddEffect($defender,'NO_DRAW_ACTION_PHASE',0,[],true);
    elseif($base==='spinal_crush')FaBWTRAddEffect($defender,'NO_GO_AGAIN',0,[],true);
    elseif($base==='crush_confidence')FaBWTRAddEffect($defender,'NO_HERO_ABILITY',0,[],true);
}

function FaBWTRDefended(int $player,object $card):void { if(FaBWTRBase($card->CardID)==='steelblade_shunt'){ $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']??0));if($a!==null&&FaBWTRIsWeapon($a['object']))DoDamage($player,'',intval($s['attacker']),1,'PHYSICAL'); } }

function FaBWTRCreateArena(int $player,string $cardID):?object { return AddArena($player,CardID:$cardID,Owner:$player,Controller:$player,Status:2); }

function FaBWTRCanActivate(int $player,string $mzID):bool{
    if(intval(GetPriorityPlayer())!==$player)return false;$f=FaBIdentityFromMZ($mzID);if($f===null||$f['player']!==$player)return false;$id=$f['object']->CardID;$zone=$f['zone'];foreach(FaBWTREffects($player)as$effect)if(($effect['type']??'')==='NO_HERO_ABILITY'&&$zone==='Hero')return false;
    $attackWeapons=['anothos'=>3,'romping_club'=>2,'dawnblade'=>1,'harmonized_kodachi'=>1];if(isset($attackWeapons[$id]))return$zone==='Weapons'&&intval($f['object']->Status??2)===2&&in_array(FaBGetState()['window'],['ACTION','RESOLUTION'],true)&&intval(GetActionPoints($player))>0&&FaBAvailablePitch($player)>=$attackWeapons[$id];
    if(in_array($id,['breaking_scales','snapdragon_scalers','refraction_bolters'],true)){$s=FaBGetState();return$zone==='Equipment'&&$s['window']==='REACTION'&&intval($s['attacker']??0)===$player&&intval($s['attackUID']??0)>0;}
    $active=['scabskin_leathers','barkbone_strapping','bravo','bravo_showstopper','tectonic_plating','helm_of_isens_peak','braveforge_bracers','fyendals_spring_tunic','heartened_cross_strap','snapdragon_scalers','goliath_gauntlet','crazy_brew_blue','energy_potion_blue','potion_of_strength_blue','timesnap_potion_blue','breaking_scales','hope_merchants_hood','refraction_bolters'];
    return in_array($id,$active,true)&&in_array($zone,['Hero','Equipment','Arena'],true);
}

function FaBWTRActivate(int $player,string $mzID,int $index=0):bool{
    if(!FaBWTRCanActivate($player,$mzID))return false;$f=FaBIdentityFromMZ($mzID);$o=$f['object'];$id=$o->CardID;SaveUndoVersion($player,'Before activating '.(CardName($id)?:$id));
    $weapons=['anothos'=>3,'romping_club'=>2,'dawnblade'=>1,'harmonized_kodachi'=>1];if(isset($weapons[$id])){ $weaponUID=intval($o->UniqueID);$attackTarget=FaBClaimOrRequestAttackTarget($player,$weaponUID,'ACTIVATE');if($attackTarget===null)return true;if($attackTarget===false)return false;SaveUndoVersion($player,'Before attacking with '.(CardName($id)?:$id));$cost=$weapons[$id];$stack=AddStack(CardID:$id,Controller:$player,Kind:'ATTACK',SourceZone:'Weapons',SourceUniqueID:$weaponUID,Params:['attackTarget'=>$attackTarget]);$stack->Kind='ATTACK';$stack->Controller=$player;$stack->SourceZone='Weapons';$stack->SourceUniqueID=$weaponUID;$s=FaBGetState();$s['pendingPayment']=['player'=>$player,'uid'=>intval($stack->UniqueID),'weaponUID'=>$weaponUID,'cost'=>$cost,'fromZone'=>'Weapons','kind'=>'ATTACK','isWeaponAttack'=>true,'returnWindow'=>(string)$s['window'],'returnCombatStep'=>(string)$s['combatStep']];$s['window']='PITCH';FaBSetState($s);SetPriorityPlayer($player);SetConsecutivePasses(0);return FaBTryCompletePayment(); }
    if(in_array($id,['energy_potion_blue','potion_of_strength_blue','timesnap_potion_blue','crazy_brew_blue'],true)){FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);if($id==='energy_potion_blue')AddResources($player,intval(GetResources($player))+2);elseif($id==='timesnap_potion_blue')AddActionPoints($player,max(0,intval(GetActionPoints($player))-1)+2);elseif($id==='potion_of_strength_blue')FaBWTRAddEffect($player,'NEXT_ATTACK',2);else{$roll=EngineRandomInt(1,6);if($roll<=2)AddHealth($player,max(0,intval(GetHealth($player))-2));elseif($roll<=4)AddHealth($player,intval(GetHealth($player))+2);else{AddResources($player,intval(GetResources($player))+2);AddActionPoints($player,max(0,intval(GetActionPoints($player))-1)+2);FaBWTRAddEffect($player,'NEXT_ATTACK',2);}}return true;}
    if($id==='scabskin_leathers'){AddActionPoints($player,max(0,intval(GetActionPoints($player))-1)+intdiv(EngineRandomInt(1,6),2));$o->Status=1;return true;}
    if($id==='barkbone_strapping'){AddResources($player,intval(GetResources($player))+intdiv(EngineRandomInt(1,6),2));FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);return true;}
    if(in_array($id,['bravo','bravo_showstopper'],true)){if(intval(GetResources($player))<2)return false;AddResources($player,intval(GetResources($player))-2);FaBWTRAddEffect($player,'NEXT_GUARDIAN',0,['dominate'=>true]);return true;}
    if($id==='tectonic_plating'){if(intval(GetResources($player))<1)return false;AddResources($player,intval(GetResources($player))-1);FaBWTRCreateArena($player,'seismic_surge');return true;}
    if($id==='heartened_cross_strap'){FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);FaBWTRAddEffect($player,'NEXT_COST',2);return true;}
    if($id==='goliath_gauntlet'){FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);FaBWTRAddEffect($player,'NEXT_AA_HIGH',2);return true;}
    if($id==='helm_of_isens_peak'){if(intval(GetResources($player))<1)return false;AddResources($player,intval(GetResources($player))-1);AddActionPoints($player,max(0,intval(GetActionPoints($player))-1));FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);FaBWTRAddEffect($player,'INTELLECT',1);return true;}
    if($id==='braveforge_bracers'){if(intval(GetResources($player))<1)return false;AddResources($player,intval(GetResources($player))-1);FaBWTRAddEffect($player,'NEXT_WEAPON',1);return true;}
    if($id==='hope_merchants_hood'){$count=FaBHandCount($player);foreach(GetHand($player)as$card)if(is_object($card))FaBMoveUID(intval($card->UniqueID),'Deck',$player);DoDrawCard($player,$count);FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);return true;}
    if($id==='fyendals_spring_tunic'){if(intval(FaBObjectCounters($o)['ENERGY']??0)<3)return false;FaBSetObjectCounter($o,'ENERGY',0);AddResources($player,intval(GetResources($player))+1);return true;}
    if(in_array($id,['breaking_scales','snapdragon_scalers','refraction_bolters'],true)){FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);$s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']??0));if($a){if($id==='breaking_scales')FaBWTRTag($a['object'],'WTR_POWER:1');else FaBWTRTag($a['object'],'GO_AGAIN');}return true;}
    return false;
}

function FaBWTRStartTurn(int $player):void{
    foreach(GetEquipment($player)as$o)if(is_object($o)){if($o->CardID==='fyendals_spring_tunic'){ $n=intval(FaBObjectCounters($o)['ENERGY']??0);if($n<3)FaBSetObjectCounter($o,'ENERGY',$n+1);}if($o->CardID==='mask_of_momentum')FaBSetObjectCounter($o,'DRAWN_TURN',0);}
    foreach(GetArena($player)as$o){if(!is_object($o)||!empty($o->removed))continue;$id=$o->CardID;$base=FaBWTRBase($id);if($id==='seismic_surge'){FaBWTRAddEffect($player,'NEXT_COST',1);FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);}elseif($base==='emerging_power'){FaBWTRAddEffect($player,'NEXT_GUARDIAN',FaBWTRPitchValue($id,[3,2,1]));FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);}elseif($base==='blessing_of_deliverance'){$count=FaBWTRPitchValue($id,[3,2,1]);$gain=0;foreach(array_slice(GetDeck($player),0,$count)as$c)if(is_object($c)&&intval(CardCost($c->CardID))>=3)++$gain;AddHealth($player,intval(GetHealth($player))+$gain);FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);}elseif(in_array($base,['stonewall_confidence','forged_for_war'],true))FaBMoveUID(intval($o->UniqueID),'Graveyard',$player);}
}

function FaBWTREndTurn(int $player):void{
    foreach(GetWeapons($player)as$o)if(is_object($o)&&$o->CardID==='dawnblade'){ $hits=0;foreach(FaBGetState()['hitsThisTurn'][(string)$player]??[]as$uid){$f=FaBFindUID(intval($uid));if(($f['object']->CardID??'')==='dawnblade')++$hits;}if($hits===0)FaBSetObjectCounter($o,'POWER',0);}
}

?>
