<?php
// Live-only controls. Engine answers stay on the server; clients submit opaque
// option IDs. Rebuild this map under the write lock before accepting every input.
function GAUIControlText($text): string {
    return str_replace('_', ' ', trim(html_entity_decode(strip_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}
function GAUIControlOption($token, int $seat, int $index): ?array {
    $ability = DecodeOpportunityAbilityChoice($token);
    $ref = $ability['mzID'] ?? $token;
    if (!preg_match('/^(my|their)(Field|Intent|Hand|Memory|Material|Graveyard|Banish|Deck|TempZone|Mastery)-(\d+)(?:\.u\d+)?$/D', $ref, $m)
        && !preg_match('/^EffectStack-\d+$/D', $ref)) return null;
    $obj = MZResolveObject($ref);
    if ($obj === null || !empty($obj->removed)) return null;
    $owner = ($m[1] ?? '') === 'their' ? 3 - $seat : $seat;
    $zone = $m[2] ?? 'EffectStack';
    if ($zone === 'EffectStack' && in_array(intval($obj->Controller ?? 0),[1,2],true)) $owner=intval($obj->Controller);
    $visible = in_array($zone, ['Field','Intent','Graveyard','EffectStack','Mastery'], true)
        || ($zone === 'Banish' && !GABanishFaceDown($obj))
        || ($owner === $seat && in_array($zone, ['Hand','Memory','Material','TempZone','Deck'], true));
    // A deck identity is disclosed only when explicitly offered for this chooser,
    // never by the ordinary board projection. Opponent private choices stay backs.
    $name = $visible ? (string)CardName($obj->CardID) : 'Face-down card';
    if ($name === '') $name = 'Unknown card';
    $verb = $ability === null ? '' : 'Activate ability ' . ($ability['abilityIndex'] + 1) . ': ';
    $parts = explode('@', $token, 3);
    if ($visible && $ability !== null && !empty($parts[2])) $verb = GAUIControlText($parts[2]) . ': ';
    return ['id' => (string)$index, 'label' => $verb . $name . ' · Player ' . $owner . ' · ' . $zone . (isset($m[3]) ? ' #'.(intval($m[3])+1) : '') . ($zone === 'Field' ? ' · '.(intval($obj->Status ?? 2) === 1 ? 'Rested' : 'Ready').' · '.intval($obj->Damage ?? 0).' damage' : ''),
        'printedText' => $visible ? GAUIPrintedCardText((string)$obj->CardID) : '', '_answer' => $token];
}
function GAUIChoiceOptions(string $specs, int $seat): ?array {
    $options = [];
    $tokens = [];
    foreach (explode('&', $specs) as $raw) {
        if ($raw === '' || $raw === '-') continue;
        $token = explode(':', $raw, 2)[0];
        if (preg_match('/^(my|their)(Field|Intent|Hand|Memory|Material|Graveyard|Banish|Deck|TempZone|Mastery)$/D', $token)) {
            foreach (GetZone($token) as $i=>$obj) if (is_object($obj) && empty($obj->removed)) $tokens[]=$token.'-'.$i;
        } else $tokens[]=$token;
    }
    foreach (array_unique($tokens) as $token) {
        $option = GAUIControlOption($token, $seat, count($options));
        if ($option === null) return null;
        $options[] = $option;
    }
    return $options;
}
function GAUIBuildControls(?int $seat): array {
    // GA queues carry decision ownership; currentPlayer is not updated at every phase.
    // A seat may answer only its own live interactive queue head.
    if ($seat === null) return [];
    $saved = $GLOBALS['playerID'];
    $flash = GetFlashMessage();
    $GLOBALS['playerID'] = $seat;
    try {
        $head = GetDecisionQueue($seat)[0] ?? null;
        if ($head !== null && empty($head->removed)) {
            $type = (string)$head->Type;
            $param = (string)$head->Param;
            $prompt = GAUIControlText(explode('~BUDGET~', (string)($head->Tooltip ?? ''))[0]);
            $control = ['type'=>$type, 'prompt'=>($prompt === '' || $prompt === '-') ? 'Make your choice' : $prompt, 'options'=>[]];
            if (in_array($type, ['MZCHOOSE','MZMAYCHOOSE','MZMULTICHOOSE','MZSPLITASSIGN'], true)) {
                $parts = explode('|', $param, 3);
                $specs = $param;
                if ($type === 'MZMULTICHOOSE') {
                    if (count($parts) !== 3) return [];
                    $control['minimum'] = max(0, intval($parts[0]));
                    $control['maximum'] = max($control['minimum'], intval($parts[1]));
                    $specs = $parts[2];
                } elseif ($type === 'MZSPLITASSIGN') {
                    if (count($parts) < 2) return [];
                    $control['amount'] = max(0, intval($parts[0]));
                    $specs = $parts[1];
                } else {
                    $control['minimum'] = $type === 'MZMAYCHOOSE' ? 0 : 1;
                    $control['maximum'] = 1;
                }
                $control['options'] = GAUIChoiceOptions($specs, $seat);
                if ($control['options'] === null) return [];
                if (isset($control['minimum'])) $control['minimum'] = min($control['minimum'], count($control['options']));
                if ($type === 'MZMULTICHOOSE' && str_contains((string)$head->Tooltip, '~BUDGET~')) {
                    $fields=explode('~',explode('~BUDGET~',(string)$head->Tooltip,2)[1]);
                    $control['budget']=max(0,intval(array_shift($fields)));
                    $control['budgetLabel']=GAUIControlText(array_shift($fields)); $weights=[];
                    foreach($fields as $field) { $pair=explode('=',$field,2); if(count($pair)===2) $weights[$pair[0]]=max(0,intval($pair[1])); }
                    foreach($control['options'] as &$option) $option['weight']=$weights[$option['_answer']] ?? 0;
                    unset($option);
                }
            } elseif ($type === 'YESNO') {
                foreach (['YES','NO'] as $value) $control['options'][] = ['id'=>$value, 'label'=>ucfirst(strtolower($value)), '_answer'=>$value];
            } elseif (in_array($type, ['MZMODAL','OPTIONCHOOSE','CHOOSEZONE','ICONCHOICE'], true)) {
                $labels = $param;
                $control['minimum'] = $control['maximum'] = 1;
                if ($type === 'MZMODAL') {
                    $parts = explode('|', $param, 3);
                    if (count($parts) !== 3) return [];
                    $control['minimum'] = max(0,min(intval($parts[0]), intval($parts[1])));
                    $control['maximum'] = max(intval($parts[0]), intval($parts[1]));
                    $labels = $parts[2];
                } elseif ($type === 'ICONCHOICE') $labels = explode('|', $param)[0];
                foreach (explode('&', $labels) as $i=>$label) {
                    if ($label === '') continue;
                    $control['options'][] = ['id'=>(string)$i, 'label'=>GAUIControlText($label), '_answer'=>$type === 'MZMODAL' ? (string)$i : $label];
                }
            } elseif (in_array($type, ['NUMBERCHOOSE','TWOSIDEDSLIDER'], true)) {
                if (!preg_match('/^\s*(-?\d+)\s*[|\-]\s*(-?\d+)/', $param, $m)) return [];
                $control['minimum'] = min(intval($m[1]),intval($m[2]));
                $control['maximum'] = max(intval($m[1]),intval($m[2]));
                if ($type === 'TWOSIDEDSLIDER') {
                    $sides=explode('|',$param);
                    foreach (['left'=>2,'right'=>3] as $side=>$index) {
                        $parts=explode('~',$sides[$index] ?? '');
                        $control[$side]=GAUIControlText(($parts[0] ?? '')==='card' ? CardName($parts[1] ?? '') : (($parts[0] ?? '')==='cardlabel' ? ($parts[2] ?? '') : ($parts[1] ?? $parts[0] ?? $side)));
                    }
                }
            } elseif ($type === 'NAMECARD') {
                $control['prompt'] .= ' (enter the full card name)';
            } elseif ($type === 'MZREARRANGE') {
                $control['piles'] = [];
                foreach (explode(';', $param) as $pile) {
                    $parts = explode('=', $pile, 2);
                    if (count($parts) !== 2) return [];
                    $pileID = (string)count($control['piles']);
                    $control['piles'][] = ['id'=>$pileID, 'label'=>GAUIControlText($parts[0]), '_answer'=>$parts[0]];
                    foreach (array_filter(explode(',', $parts[1]), fn($v)=>$v !== '') as $cardID) {
                        // Rearrange is an explicit reveal of these offered card identities.
                        $control['options'][] = ['id'=>(string)count($control['options']), 'label'=>(string)CardName($cardID) ?: $cardID,
                            'pile'=>$pileID, '_answer'=>$cardID];
                    }
                }
            } else return [];
            return ['decision'=>$control];
        }
        return [];
    } finally { $GLOBALS['playerID'] = $saved; SetFlashMessage($flash); }
}
function GAUIPublicControls(array $controls): array {
    if (!isset($controls['decision'])) return $controls;
    foreach (['options','piles'] as $key) {
        if (!isset($controls['decision'][$key])) continue;
        foreach ($controls['decision'][$key] as &$option) unset($option['_answer']);
        unset($option);
    }
    return $controls;
}
// Returns null on malformed/out-of-pool values. An empty valid answer remains a string.
function GAUIResolveDecision(array $control, $value): ?string {
    $type = $control['type'];
    if (in_array($type, ['NUMBERCHOOSE','TWOSIDEDSLIDER'], true)) {
        if (!is_string($value) || !preg_match('/^-?(0|[1-9][0-9]*)$/D', $value)) return null;
        return (string)intval($value) === $value && intval($value) >= $control['minimum'] && intval($value) <= $control['maximum'] ? $value : null;
    }
    if ($type === 'NAMECARD') return is_string($value) && strlen(trim($value)) > 0 && strlen($value) <= 200 && !preg_match('/[\x00-\x1f]/', $value) ? trim($value) : null;
    $options = array_column($control['options'], '_answer', 'id');
    if ($type === 'MZSPLITASSIGN') {
        if (!is_array($value)) return null;
        $sum = 0; $answers = [];
        foreach ($value as $id=>$amount) {
            if (!array_key_exists($id,$options) || !is_int($amount) || $amount < 0) return null;
            $sum += $amount;
            if ($amount > 0) $answers[] = $options[$id].':'.$amount;
        }
        return $sum === $control['amount'] ? (implode(',',$answers) ?: '-') : null;
    }
    if ($type === 'MZREARRANGE') {
        if (!is_array($value)) return null;
        $piles = array_column($control['piles'],'_answer','id'); $seen=[]; $answers=[];
        if (count($value) !== count($piles)) return null;
        foreach ($value as $id=>$cards) {
            if (!array_key_exists($id,$piles) || !is_array($cards)) return null;
            $pile=[];
            foreach ($cards as $card) {
                if (!is_string($card) || !array_key_exists($card,$options) || isset($seen[$card])) return null;
                $seen[$card]=true; $pile[]=$options[$card];
            }
            $answers[]=$piles[$id].'='.implode(',',$pile);
        }
        return count($seen) === count($options) ? implode(';',$answers) : null;
    }
    if (!is_array($value) || !array_is_list($value)) return null;
    $answers=[]; $seen=[];
    foreach ($value as $id) {
        if (!is_string($id) || !array_key_exists($id,$options) || isset($seen[$id])) return null;
        $seen[$id]=true; $answers[]=$options[$id];
    }
    $min = $control['minimum'] ?? 1; $max = $control['maximum'] ?? 1;
    if (count($answers) < $min || count($answers) > $max) return null;
    if (isset($control['budget'])) {
        $total=0;
        foreach ($control['options'] as $option) if (isset($seen[$option['id']])) $total += $option['weight'] ?? 0;
        if ($total > $control['budget']) return null;
    }
    if (empty($answers)) return $type === 'MZMAYCHOOSE' ? 'PASS' : '-';
    return implode($type === 'MZMODAL' ? ',' : '&', $answers);
}

function GAUIBuildMainActions(?int $seat): array {
    if ($seat === null || GetCurrentPhase() !== 'MAIN'
        || intval(GetTurnPlayer()) !== $seat || !(new DecisionQueueController())->AllQueuesEmpty()
        || count(GetLiveEffectStackEntries()) > 0) return [];
    $saved = $GLOBALS['playerID']; $flash = GetFlashMessage(); $GLOBALS['playerID'] = $seat;
    $actions = [];
    $add = function($id,$label,$mode,$answer) use (&$actions) {
        $actions[]=['id'=>$id,'label'=>$label,'mode'=>'100','params'=>http_build_query(['command'=>$id]),'_mode'=>$mode,'_answer'=>$answer];
    };
    try {
        foreach (['Hand','Field','Intent','Graveyard','Banish','Material','theirField'] as $zone) {
            $opposing = $zone === 'theirField';
            if ($opposing) $zone = 'Field';
            $prefix = $opposing ? 'their' : 'my';
            foreach (GetZone($prefix.$zone) as $i=>$obj) {
                if ($opposing && !GAHasPublicActivatedAbility($obj)) continue;
                if ($obj === null || !empty($obj->removed) || ($zone === 'Banish' && GABanishFaceDown($obj))) continue;
                $mz=$prefix.$zone.'-'.$i; $id='p'.($opposing ? 3-$seat : $seat).$zone.'-'.$i;
                $name=(string)CardName($obj->CardID);
                $play = false;
                if ($zone === 'Hand') $play = CanActivateCardForSelection($seat,$obj) && !ActivationRefusedBeforeStart($seat,$obj,false,true);
                if (in_array($zone,['Graveyard','Banish','Material'],true)) {
                    $fn=['Graveyard'=>'EphemerateMeta','Banish'=>'BanishSelectionMetadata','Material'=>'MaterialSelectionMetadata'][$zone];
                    $meta=json_decode($fn($obj),true); $play=!empty($meta['color']);
                }
                if ($play) $add('play:'.$id,'Play '.$name,10002,$mz.'!FSM!');
                if (!$opposing && $zone === 'Field' && intval($obj->Status ?? 0) === 2) {
                    $type=EffectiveCardType($obj);
                    if ((PropertyContains($type,'ALLY') || PropertyContains($type,'CHAMPION') || GASwordMayAttackAsAlly($obj))
                        && !IsFirstTurnAttackLocked($seat) && PrideAmount($obj) <= PlayerLevel($seat)
                        && (ObjectCurrentPower($obj) > 0 || AttackHasPositivePowerWithoutWeapon($obj,$seat) || !empty(GetAttackWeaponChoices($seat,$obj)))) {
                        // The engine checks weapon/target requirements when this begins.
                        $add('attack:'.$id,'Attack with '.$name,10002,$mz.'!FSM!');
                    }
                }
                if (!in_array($zone,['Field','Intent','Hand'],true) || HasNoAbilities($obj)) continue;
                GAApplyActivateAbilityCountOverrides();
                $names=$zone==='Hand' ? (function_exists('CardHandActivatedAbilityCountNames') ? CardHandActivatedAbilityCountNames($obj->CardID) : []) : (function_exists('CardActivateAbilityCountNames') ? CardActivateAbilityCountNames($obj->CardID) : []);
                $count=$zone==='Hand' ? CardHandActivatedAbilityCount($obj->CardID) : CardActivateAbilityCount($obj->CardID);
                $abilities=[];
                for($n=0;$n<$count;++$n) $abilities[$n]=$names[$n] ?? ('Ability '.($n+1));
                if ($zone !== 'Hand') foreach (json_decode(GetDynamicAbilities($obj) ?: '[]',true) ?: [] as $ability) {
                    $abilities[intval($ability['index'])]=$ability['name'] ?? 'Ability';
                }
                foreach($abilities as $n=>$label) {
                    $can=$zone==='Hand' ? CanHandActivateAbility($seat,$mz,$n) : CanActivateAbility($seat,$mz,$n);
                    if($can) $add('ability:'.$id.':'.$n,GAUIControlText($label).' · '.$name,10001,$mz.'!CustomInput!Activate:'.$n);
                }
            }
        }
        if (!FerventLancerMustAttack($seat)) $add('end-main','End main phase',10001,'myHealth-0!CustomInput!Pass');
        return $actions;
    } finally { $GLOBALS['playerID']=$saved; SetFlashMessage($flash); }
}
function GAUILiveProjection(array $state, ?int $seat, ?array &$bindings = null): array {
    $bindings = [];
    $projection=GAUIProjectState($state,$seat);
    GAUIEnrichBoard($projection);
    $projection['reveals']=GAUIPublicReveals(GetFlashMessage());
    $winner = intval(DecisionQueueController::GetVariable('GAMEOVER_WINNER'));
    $projection['winner'] = in_array($winner,[1,2],true) ? $winner : null;
    if ($projection['winner'] !== null) { $projection['commands']=[]; $projection['controls']=[]; return $projection; }
    $controls=GAUIBuildControls($seat);
    $bindings['controls']=$controls;
    $projection['controls']=GAUIPublicControls($controls);
    if (isset($controls['decision'])) $projection['commands'][]=['id'=>'answer-decision','label'=>'Confirm choice','mode'=>'100','params'=>'command=answer-decision'];
    $bindings['actions']=GAUIBuildMainActions($seat);
    $bot=BuildBotControllerClientState('GrandArchiveSim',(string)($GLOBALS['gameName'] ?? ''));
    if ($seat !== null && $bot['enabled'] && $bot['pendingPlayer'] > 0) {
        $bindings['actions'][]=['id'=>'bot-step','label'=>'Continue bot turn','mode'=>'100','params'=>'command=bot-step','_mode'=>10017,'_answer'=>'PASS'];
    }
    if ($seat !== null) $bindings['actions'][]=['id'=>'concede','label'=>'Concede game','mode'=>'100','params'=>'command=concede','_mode'=>10006,'_answer'=>'PASS'];
    foreach($bindings['actions'] as $command) {
        unset($command['_answer'],$command['_mode']);
        $projection['commands'][]=$command;
    }
    if ((new DecisionQueueController())->AllQueuesEmpty() && $seat !== null) {
        $projection['attention'] = intval(GetTurnPlayer()) === $seat
            ? ['kind'=>'active','message'=>'Your main phase. Choose a card action or end the phase.']
            : ['kind'=>'waiting','message'=>'Waiting for the other player.'];
    }
    $projection['phase']=GAUIControlText(GetCurrentPhase());
    return $projection;
}

function GAUIEnrichBoard(array &$projection): void {
    foreach ($projection['players'] as &$player) foreach ($player['zones'] as $zone=>&$data) foreach ($data['cards'] as &$card) {
        if ($zone === 'Mastery') {
            $index=intval(substr($card['id'],strrpos($card['id'],'-')+1));
            $mastery=GetMastery($player['seat'])[$index] ?? null;
            if($mastery!==null) {
                $direction=strval($mastery->Direction ?? 'NONE');
                $card['direction']=in_array($direction,['NORTH','SOUTH','EAST','WEST'],true) ? $direction : null;
                $card['counters']=[];
                foreach(['sheen','haunt'] as $type) if(GetCounterCount($mastery,$type)>0) $card['counters'][$type]=GetCounterCount($mastery,$type);
            }
        }
        if ($zone !== 'Field') continue;
        $index=intval(substr($card['id'],strrpos($card['id'],'-')+1));
        $object=GetField($player['seat'])[$index] ?? null;
        if ($object===null) continue;
        $power=intval(ObjectCurrentPowerDisplay($object));
        $card['power']=$power < 0 ? null : $power;
        $hp=intval(ObjectCurrentHPDisplay($object));
        $card['hp']=$hp < 0 ? null : $hp;
        $card['level']=PropertyContains(EffectiveCardType($object),'CHAMPION') ? intval(ObjectCurrentLevel($object)) : null;
        $card['keywords']=[];
        foreach(['Stealth'=>'FieldHasStealth','Spellshroud'=>'FieldHasSpellshroud','Taunt'=>'FieldHasTaunt','Brewed'=>'FieldHasBrewed','Fracturized'=>'FieldHasFracturized'] as $label=>$getter) {
            if($getter($object)) $card['keywords'][]=$label;
        }
        if(in_array('DISTANT',$object->TurnEffects ?? [],true)) $card['keywords'][]='Distant';
        if(HasNoAbilities($object)) $card['keywords'][]='Abilities suppressed';
        $card['lineage']=[];
        foreach ($object->Subcards ?? [] as $subcard) if (is_string($subcard) && $subcard !== '') {
            $card['lineage'][]=['name'=>(string)CardName($subcard),'printedText'=>GAUIPrintedCardText($subcard)];
        }
        $card['counters']=[];
        foreach (['buff','debuff','refinement','enlighten','preparation','durability','bulwark','glimmer','wither','charge','sheen','age','divination','quest','prize','root','gem','static','training','debt','blood','true-sight','vigor'] as $type) {
            $count=GetCounterCount($object,$type);
            if ($count > 0) $card['counters'][$type]=intval($count);
        }
    }
    unset($card,$data,$player);
    foreach ($projection['effectStack'] as &$effect) {
        $index=intval(substr($effect['id'],strlen('EffectStack-')));
        $object=GetEffectStack()[$index] ?? null;
        $effect['targets']=[];
        if ($object===null) continue;
        foreach (GADeclarationData($object) as $group) {
            if(!is_array($group)) continue;
            foreach($group as $identity) {
                if(!is_array($identity) || ($identity['zone'] ?? '')!=='Field') continue;
                $ref=GATargetReference($identity); $target=$ref === null ? null : GetZoneObject($ref);
                if ($target!==null && empty($target->removed)) $effect['targets'][]=(string)CardName($target->CardID).' · Player '.intval($target->Controller).' · Field #'.(intval($target->mzIndex)+1);
            }
        }
        $effect['targets']=array_values(array_unique($effect['targets']));
    }
    unset($effect);
    $projection['combat']=[];
    foreach (['attacker'=>'GetCombatAttackerMZ','target'=>'GetCombatTargetMZ'] as $key=>$getter) {
        $ref=$getter();
        if (!is_string($ref) || !preg_match('/^(my|their)Field-(\d+)$/D',$ref,$m)) continue;
        $owner=$m[1]==='my' ? intval($GLOBALS['playerID']) : 3-intval($GLOBALS['playerID']);
        foreach ($projection['players'] as $player) {
            if($player['seat']!==$owner) continue;
            foreach($player['zones']['Field']['cards'] as $card) if($card['id']==='p'.$owner.'Field-'.$m[2]) $projection['combat'][$key]=['name'=>$card['name'],'owner'=>$owner];
        }
    }
}

// DoRevealCard emits this public event format. No other flash text or runtime
// zone contents are exposed to the opposing player by this helper.
function GAUIPublicReveals($message): array {
    if (!is_string($message) || !preg_match('/^REVEAL:([A-Za-z0-9_-]+(?:\|[A-Za-z0-9_-]+)*)$/D',$message,$m)) return [];
    $cards=[];
    foreach (explode('|',$m[1]) as $id) $cards[]=['name'=>(string)CardName($id),'printedText'=>GAUIPrintedCardText($id)];
    return $cards;
}
