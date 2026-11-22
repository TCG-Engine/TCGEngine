<?php

/** Audited declaration profiles; no text parsing or automatic card enrollment. */
function FaBDeclaredReactionDefinition(string $id): ?array {
    if ($id === 'lightning_press_red') return ['kind'=>'INSTANT','modal'=>false,'tip'=>'Choose_attack_action','modes'=>[
        'attackAction'=>['selector'=>'ANY_CHEAP_ATTACK_ACTION','effects'=>['power'=>3]]]];
    if ($id === 'nip_at_the_heels_blue') return ['modal'=>false,'tip'=>'Choose_attack','modes'=>[
        'attack'=>['selector'=>'HNT_SMALL','effects'=>['power'=>1]]]];
    if (in_array($id,['shred_red','shred_yellow','shred_blue'],true)) return ['modal'=>false,'tip'=>'Choose_defending_card','modes'=>[
        'defender'=>['selector'=>'DYN_ASSASSIN_DEFENDER','effects'=>['defense'=>-(5-intval(CardPitch($id)))]]]];
    if ($id === 'razor_reflex_red') return ['modal'=>true,'tip'=>'Choose_attack','modes'=>[
        'weapon'=>['label'=>'Dagger_or_sword_weapon_attack','selector'=>'BLADE_WEAPON','effects'=>['power'=>3]],
        'attackAction'=>['label'=>'Attack_action_cost_1_or_less','selector'=>'CHEAP_ATTACK_ACTION','effects'=>['power'=>3,'hitGoAgain'=>true]]]];
    return null;
}

function FaBDeclaredReactionSelectorRefs(int $player, string $selector): string {
    if ($selector === 'HNT_SMALL') return FaBHNTAffectsAttack($player,'SMALL');
    if ($selector === 'DYN_ASSASSIN_DEFENDER') return FaBDYNDefenders($player,'ASSASSIN');
    $state=FaBGetState(); $target=FaBFindUID(intval($state['attackUID']??0));
    if ($target===null || $target['zone']!=='CombatChain'
        || ($selector!=='ANY_CHEAP_ATTACK_ACTION' && intval($state['attacker']??0)!==$player)
        || ($target['object']->Role??'')!=='ATTACK'
        || intval($target['object']->ChainLink??-1)!==intval($state['chainLink']??-2)) return '';
    $o=$target['object'];
    $legal=$selector==='BLADE_WEAPON'
        ? FaBWTRIsWeapon($o)&&(FaBHasType($o,'Dagger')||FaBHasType($o,'Sword'))
        : (in_array($selector,['CHEAP_ATTACK_ACTION','ANY_CHEAP_ATTACK_ACTION'],true)&&FaBWTRIsAttackAction($o)&&intval(CardCost($o->CardID))<=1);
    return $legal ? (string)$target['mzID'] : '';
}

function FaBDeclaredReactionModeKey(object $source, array $definition): string {
    if (empty($definition['modal'])) return (string)array_key_first($definition['modes']);
    $mode=$source->Params['declaredReactionMode']??null;
    return is_string($mode)&&isset($definition['modes'][$mode]) ? $mode : '';
}

/** Snapshot offered identities after mode selection, still before payment. */
function FaBQueueDeclaredReactionTarget(int $player, object $source): void {
    $uid=intval($source->UniqueID??0); if(FaBDeclaredReactionSource($player,$uid)===null)return;
    $definition=FaBDeclaredReactionDefinition((string)$source->CardID);
    $mode=FaBDeclaredReactionModeKey($source,$definition);
    if($mode===''){FaBReverseDeclaredReaction($player,$source);return;}
    $refs=FaBDeclaredReactionTargetRefs($player,(string)$source->CardID,$mode);$options=[];
    foreach(array_filter(explode('&',$refs))as$ref){
        $found=FaBIdentityFromMZ($ref);
        if($found!==null&&$found['zone']==='CombatChain')$options[(string)intval($found['object']->UniqueID)]=intval($found['object']->ChainLink??-1);
    }
    $source->Params['declaredReactionOptions']=$options;
    if(!$options){FaBReverseDeclaredReaction($player,$source);return;}
    DecisionQueueController::AddDecision($player,'MZCHOOSE',$refs,1,$definition['tip']);
    DecisionQueueController::AddDecision($player,'CUSTOM','FAB_DECLARE_REACTION_TARGET|'.$uid,1);
}

function FaBContinueDeclaredReactionMode(int $player, array $parts, string $answer): void {
    if(count($parts)!==1||DecisionQueueController::ExecutingSeat()!==$player)return;
    $uid=intval($parts[0]);$found=FaBDeclaredReactionSource($player,$uid);if($uid<=0||$found===null)return;
    $source=$found['object'];$definition=FaBDeclaredReactionDefinition((string)$source->CardID);
    $options=(array)($source->Params['declaredReactionModeOptions']??[]);
    if(empty($definition['modal'])||!$options)return;
    $index=preg_match('/^(0|[1-9][0-9]*)$/D',$answer)?intval($answer):-1;$mode=$options[$index]??'';
    if(!is_string($mode)||!isset($definition['modes'][$mode])||FaBDeclaredReactionTargetRefs($player,(string)$source->CardID,$mode)===''){
        FaBReverseDeclaredReaction($player,$source);return;
    }
    $source->Params['declaredReactionMode']=$mode;unset($source->Params['declaredReactionModeOptions']);
    FaBQueueDeclaredReactionTarget($player,$source);
}

/** Only audited declaration profiles enter this compatibility seam. */
function FaBUsesDeclaredReactionTarget(string $cardID): bool {
    return FaBDeclaredReactionDefinition($cardID) !== null;
}

/** Preserve the existing current-attack/current-defender target universe; bind its objects earlier. */
function FaBDeclaredReactionTargetRefs(int $player, string $cardID, string $mode = ''): string {
    $definition=FaBDeclaredReactionDefinition($cardID);if($definition===null)return '';
    $modes=$definition['modes'];if($mode!==''){if(!isset($modes[$mode]))return '';$modes=[$modes[$mode]];}
    $refs=[];foreach($modes as$spec)foreach(array_filter(explode('&',FaBDeclaredReactionSelectorRefs($player,$spec['selector'])))as$ref)$refs[$ref]=true;
    return implode('&',array_keys($refs));
}

function FaBDeclaredReactionSource(int $player, int $uid): ?array {
    $source = FaBFindUID($uid); $pending = FaBGetState()['pendingPayment'] ?? null;
    return $source !== null && $source['zone'] === 'Stack'
        && intval($source['object']->Controller ?? 0) === $player
        && FaBUsesDeclaredReactionTarget((string)$source['object']->CardID)
        && is_array($pending) && intval($pending['player'] ?? 0) === $player
        && intval($pending['uid'] ?? 0) === $uid
        && ($pending['kind'] ?? '') === (FaBDeclaredReactionDefinition((string)$source['object']->CardID)['kind'] ?? 'ATTACK_REACTION')
        && (($pending['kind'] ?? '') !== 'INSTANT' || ($source['object']->Kind ?? '') === 'INSTANT')
        ? $source : null;
}

/** An invalid declaration must not spend costs or become a completed play. */
function FaBReverseDeclaredReaction(int $player, object $source): void {
    $expected = (string)($source->Params['declaredReactionUndoHash'] ?? '');
    $versions = GetVersions($player); $version = $versions[0] ?? null;
    if ($expected !== '' && is_object($version) && hash_equals($expected, hash('sha256', (string)$version->Version))) {
        LoadVersion($player, 0);
        return;
    }
    // Do not turn a stale/missing snapshot into a free or targetless play.
    if (function_exists('SetFlashMessage')) SetFlashMessage('Reaction declaration stopped: preannouncement snapshot unavailable.');
}

/** Runs after announce and before asset payment/played listeners/opponent priority. */
function FaBPrepareDeclaredReaction(int $player, object $source): void {
    $uid=intval($source->UniqueID??0);if(FaBDeclaredReactionSource($player,$uid)===null)return;
    if(HasNoAbilities($source)){FaBFinishPreparedCard($uid);return;}
    $versions=GetVersions($player);$version=$versions[0]??null;
    $source->Params['declaredReactionUndoHash']=is_object($version)?hash('sha256',(string)$version->Version):'';
    $definition=FaBDeclaredReactionDefinition((string)$source->CardID);
    if(empty($definition['modal'])){FaBQueueDeclaredReactionTarget($player,$source);return;}
    $modes=[];$labels=[];
    foreach($definition['modes']as$key=>$spec){
        if(FaBDeclaredReactionTargetRefs($player,(string)$source->CardID,$key)==='')continue;
        $modes[]=$key;$labels[]=$spec['label'];
    }
    if(!$modes){FaBReverseDeclaredReaction($player,$source);return;}
    $source->Params['declaredReactionModeOptions']=$modes;
    DecisionQueueController::AddDecision($player,'MZMODAL','1|1|'.implode('&',$labels),1,'Choose_reaction_mode');
    DecisionQueueController::AddDecision($player,'CUSTOM','FAB_DECLARE_REACTION_MODE|'.$uid,1);
}

/** CUSTOM only binds the already offered legal object and completes normal payment. */
function FaBContinueDeclaredReaction(int $player, array $parts, string $answer): void {
    if (count($parts) !== 1 || DecisionQueueController::ExecutingSeat() !== $player) return;
    $uid = intval($parts[0]); $source = FaBDeclaredReactionSource($player, $uid);
    if ($uid <= 0 || $source === null) return;
    $object = $source['object']; $target = FaBIdentityFromMZ($answer);
    $options = (array)($object->Params['declaredReactionOptions'] ?? []);
    $targetUID = $target === null ? 0 : intval($target['object']->UniqueID ?? 0);
    $definition = FaBDeclaredReactionDefinition((string)$object->CardID);
    $mode = FaBDeclaredReactionModeKey($object, $definition);
    $refs = explode('&', FaBDeclaredReactionTargetRefs($player, (string)$object->CardID, $mode));
    if ($mode === '' || $target === null || $target['zone'] !== 'CombatChain' || !in_array($answer, $refs, true)
        || !array_key_exists((string)$targetUID, $options)
        || intval($target['object']->ChainLink ?? -1) !== intval($options[(string)$targetUID])) {
        FaBReverseDeclaredReaction($player, $object);
        return;
    }
    $object->Params['declaredReactionTarget'] = ['uid'=>$targetUID, 'chainLink'=>intval($target['object']->ChainLink)];
    unset($object->Params['declaredReactionOptions'], $object->Params['declaredReactionUndoHash']);
    FaBFinishPreparedCard($uid);
}

/** Resolution never asks for a new target; an invalid original target makes the effect fail. */
function FaBResolveDeclaredReaction(int $player, object $source): void {
    $definition=FaBDeclaredReactionDefinition((string)$source->CardID);
    if($definition===null||HasNoAbilities($source))return;
    $mode=FaBDeclaredReactionModeKey($source,$definition);if($mode==='')return;
    $bound=(array)($source->Params['declaredReactionTarget']??[]);$target=FaBFindUID(intval($bound['uid']??0));
    if($target===null||$target['zone']!=='CombatChain'
        ||intval($target['object']->ChainLink??-1)!==intval($bound['chainLink']??-2)
        ||!in_array($target['mzID'],explode('&',FaBDeclaredReactionTargetRefs($player,(string)$source->CardID,$mode)),true))return;
    $effects=$definition['modes'][$mode]['effects'];$uid=intval($bound['uid']);
    if(!empty($effects['power']))FaBTagUID($uid,'WTR_POWER:'.intval($effects['power']));
    if(!empty($effects['defense']))FaBTagUID($uid,'DYN_DEFENSE:'.intval($effects['defense']));
    if(!empty($effects['hitGoAgain']))FaBTagUID($uid,'WTR_HIT_GO_AGAIN');
}

/** Writer-only projection of the exact bound offered UIDs through tombstone compaction. */
function FaBDeclaredReactionChoicesForSave(array $zone, int $player): array {
    $choice = $zone[0] ?? null; $resume = $zone[1] ?? null;
    if (!is_object($choice) || ($choice->Type ?? '') !== 'MZCHOOSE'
        || !is_object($resume) || ($resume->Type ?? '') !== 'CUSTOM') return $zone;
    $parts = explode('|', (string)($resume->Param ?? ''));
    if (count($parts) !== 2 || $parts[0] !== 'FAB_DECLARE_REACTION_TARGET') return $zone;
    $source = FaBDeclaredReactionSource($player, intval($parts[1]));
    if ($source === null) return $zone;
    $options = (array)($source['object']->Params['declaredReactionOptions'] ?? []);
    $listed = [];
    foreach (array_filter(explode('&', (string)$choice->Param)) as $ref) {
        $found = FaBIdentityFromMZ($ref);
        $uid = $found === null ? 0 : intval($found['object']->UniqueID ?? 0);
        if ($found === null || $found['zone'] !== 'CombatChain' || !array_key_exists((string)$uid, $options)
            || intval($found['object']->ChainLink ?? -1) !== intval($options[(string)$uid])) return $zone;
        $listed[$uid] = true;
    }
    $refs = [];
    foreach (FaBLiveSeats() as $seat) {
        $next = 0;
        foreach (GetCombatChain($seat) as $object) {
            if ($object == null || $object->Removed()) continue;
            if (isset($listed[intval($object->UniqueID ?? 0)])) $refs[] = 'p'.$seat.'CombatChain-'.$next;
            ++$next;
        }
    }
    $copy = clone $choice; $copy->Param = implode('&', $refs); $zone[0] = $copy;
    return $zone;
}

/** Stable rendered references for a player's currently live objects. */
function FaBChoiceRefs(int $player, string $zone, array $filter = []): array {
    $refs = [];
    foreach (FaBZoneGet($zone, $player) as $index => $obj) {
        if (!is_object($obj) || !empty($obj->removed)) continue;
        if ($zone==='Graveyard' && !empty($obj->FaceDown) && empty($filter['includeFaceDown'])) continue;
        if(isset($filter['pitch'])&&FaBMSTObjectColor($player,$obj)!==intval($filter['pitch']))continue;
        if (isset($filter['type']) && !FaBHasType($obj, $filter['type'])) continue;
        if (!empty($filter['attackAction']) && !FaBWTRIsAttackAction($obj)) continue;
        if (isset($filter['keyword']) && !FaBHasKeyword($obj, $filter['keyword'])) continue;
        if(!empty($filter['specialization'])&&stripos(implode('|',FaBKeywords($obj)),'Specialization')===false)continue;
        if ((isset($filter['cost']) || isset($filter['minCost']) || isset($filter['maxCost'])) && !is_numeric(CardCost($obj->CardID))) continue;
        if (isset($filter['cost']) && intval(CardCost($obj->CardID)) !== intval($filter['cost'])) continue;
        if (isset($filter['minCost']) && intval(CardCost($obj->CardID)) < intval($filter['minCost'])) continue;
        if (isset($filter['maxCost']) && intval(CardCost($obj->CardID)) > intval($filter['maxCost'])) continue;
        if (isset($filter['bases']) && !in_array(FaBWTRBase($obj->CardID), $filter['bases'], true)) continue;
        if (isset($filter['base']) && FaBWTRBase($obj->CardID) !== $filter['base']) continue;
        $refs[] = ($zone === 'Stack' ? '' : 'p' . $player) . $zone . '-' . $index;
    }
    return $refs;
}

function FaBMoveChoice(int $player, string $choice, string $fromZone, string $toZone): ?object {
    $found = FaBIdentityFromMZ($choice);
    if ($found === null || $found['player'] !== $player || $found['zone'] !== $fromZone) return null;
    return FaBMoveUID(intval($found['object']->UniqueID), $toZone, $player);
}

function FaBShuffleDeck(int $player): void {
    $deck = &GetDeck($player);
    $deck = array_values(array_filter($deck, fn($obj) => is_object($obj) && empty($obj->removed)));
    EngineShuffle($deck, true);
}

/** Searches expose only matching cards, and only to the searching seat. */
function FaBStageSearch(int $player, array $filter = []): string {
    if(FaBUPRBleak())return '';
    $refs = FaBChoiceRefs($player, 'Deck', $filter);
    $uids = [];
    foreach ($refs as $ref) $uids[] = intval(FaBIdentityFromMZ($ref)['object']->UniqueID);
    foreach ($uids as $uid) FaBMoveUID($uid, 'Temp', $player, false);
    return implode('&', FaBChoiceRefs($player, 'Temp'));
}

function FaBFinishSearch(int $player): void {
    foreach (GetTemp($player) as $obj) if (is_object($obj) && empty($obj->removed)) FaBMoveUID(intval($obj->UniqueID), 'Deck', $player, false);
    FaBShuffleDeck($player);
}

function FaBMoveChoices(int $player, string $choices, string $from, string $to): int {
    $uids = [];
    foreach (explode('&', $choices) as $ref) {
        $found = FaBIdentityFromMZ($ref);
        if ($found !== null && $found['player'] === $player && $found['zone'] === $from) $uids[] = intval($found['object']->UniqueID);
    }
    foreach (array_unique($uids) as $uid) FaBMoveUID($uid, $to, $player);
    return count(array_unique($uids));
}

function FaBActionGraveChoices(int $player, int $excludedUID): string {
    return implode('&', array_filter(FaBChoiceRefs($player, 'Graveyard', ['type'=>'Action']),
        fn($ref) => intval(FaBIdentityFromMZ($ref)['object']->UniqueID) !== $excludedUID));
}

function FaBRevealChoices(int $player, string $choices): void {
    $pending = FaBGetState()['pendingPayment'];
    $flockCost = is_array($pending) && ($pending['wtrFlockStage'] ?? '') === 'REVEAL'
        && intval($pending['player'] ?? 0) === $player && empty($pending['wtrFlockRevealPaid']);
    if ($flockCost && !FaBWTRFlockRevealAnswerIsValid($player, $choices)) return;
    if(FaBUPRBleak())return;
    $revealed=false;
    foreach (explode('&',$choices) as $ref) {
        $found = FaBIdentityFromMZ($ref);
        // Publish through the shared Events feed without starting a decision or
        // replacing the source parameters of the card currently resolving.
        if ($found !== null) {$revealed=true;IncrementMacroGameIndexCard('RevealCard', $player, $found['object']->CardID);}
    }
    if ($revealed && $flockCost) {
        $state = FaBGetState();
        if (intval($state['pendingPayment']['uid'] ?? 0) === intval($pending['uid'])) {
            $state['pendingPayment']['wtrFlockRevealPaid'] = true;
            FaBSetState($state);
        }
    }
    if($revealed)FaBELEKorshemReveal($player);
}

function FaBSetPreparedMode(int $uid, int $mode): void {
    $source = FaBFindUID($uid);
    if ($source === null) return;
    $source['object']->Params['estrikeMode'] = $mode;
    if ($mode === 1) FaBCRUSelfTag($source['object'],'WTR_POWER:2');
    if ($mode === 2) FaBWTRTag($source['object'],'GO_AGAIN');
}

function FaBFinishPreparedCard(int $uid, int $extraCost = 0): void {
    $state = FaBGetState();
    if (intval($state['pendingPayment']['uid'] ?? 0) !== $uid) return;
    if (isset($state['pendingPayment']['wtrFlockStage'])) {
        $pending = $state['pendingPayment'];
        if (($pending['wtrFlockStage'] ?? '') !== 'REVEAL' || empty($pending['wtrFlockAssetsPaid'])
            || empty($pending['wtrFlockRevealPaid']) || $extraCost !== 0 || FaBWTRFlockSource($pending) === null) {
            FaBWTRRollbackFlockPayment($pending, 'mandatory reveal was not completed correctly');
            return;
        }
        $state['pendingPayment']['wtrFlockStage'] = 'COMPLETE';
        FaBSetState($state);
        FaBTryCompletePayment();
        return;
    }
    $state['pendingPayment']['cost'] += max(0,$extraCost); FaBSetState($state);
    FaBTryCompletePayment();
}

function FaBTagUID(int $uid, string $tag): void {
    $found=FaBFindUID($uid); if ($found === null) return;
    $source=FaBIdentityFromMZ((string)DecisionQueueController::GetVariable('mzID'));
    if($source!==null&&FaBHasType($source['object'],'Attack Reaction'))FaBCRUSelfTag($found['object'],$tag);
    else FaBWTRTag($found['object'],$tag);
    if(in_array($tag,['WTR_POWER:1','CRU_REACTION_POWER:1'],true))FaBCRUNotifyFeatherfoot($uid);
}

function FaBPreviousAttackBase(): string {
    $state=FaBGetState(); $attack=FaBFindUID(intval($state['attackUID']));
    return !empty($state['combatOpen']) ? FaBWTRBase($attack['object']->CardID ?? $state['previousAttackCardID']) : '';
}

function FaBGrantBanishPlay(int $player, string $chosen, int $chainLink = 0): void {
    FaBRevealChoices($player,$chosen);
    $moved=FaBMoveChoice($player,$chosen,'Temp','Banish');
    if($moved!==null){$moved->PlayableFromBanish=1;$moved->PlayableChainLink=$chainLink;$moved->FaceDown=0;}
}

/** Shared mandatory discard flow; whole-zone refs survive writer compaction. */
function FaBRequestDiscard(int $player): void {
    if (!FaBSeatIsLive($player) || !FaBChoiceRefs($player, 'Hand')) return;
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', 'p'.$player.'Hand', 1, 'Choose_a_card_to_discard');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'FAB_DISCARD_CHOICE|'.$player, 1);
}

/** Scope answer validation to the exact native request/apply pair. */
function FaBIsDiscardRequestDecision(int $player): bool {
    $queue = GetDecisionQueue($player); $choice = $queue[0] ?? null; $apply = $queue[1] ?? null;
    return is_object($choice) && is_object($apply)
        && ($choice->Type ?? '') === 'MZCHOOSE' && ($choice->Param ?? '') === 'p'.$player.'Hand'
        && intval($choice->Block ?? 0) === 1 && intval($apply->Block ?? 0) === 1
        && ($apply->Type ?? '') === 'CUSTOM' && ($apply->Param ?? '') === 'FAB_DISCARD_CHOICE|'.$player;
}

function FaBDiscardRequestAnswerIsValid(int $player, string $answer): bool {
    if (!FaBSeatIsLive($player) || !preg_match('/^(?:p[1-4]|my|their)Hand-[0-9]+$/D', $answer)) return false;
    $found = FaBIdentityFromMZ($answer);
    return $found !== null && $found['player'] === $player && $found['zone'] === 'Hand';
}

/** CUSTOM is non-interactive; EngineActionRunner validates before popping the choice. */
function FaBApplyDiscardRequest(int $player, string $answer): void {
    if (FaBDiscardRequestAnswerIsValid($player, $answer)) FaBDiscardChoice($player, $answer);
}

function FaBDiscardChoice(int $player, string $chosen): bool {
    $found=FaBIdentityFromMZ($chosen);
    if($found===null || $found['player']!==$player || $found['zone']!=='Hand')return false;
    $id=$found['object']->CardID;$discardSource=clone $found['object'];
    FaBMoveUID(intval($found['object']->UniqueID),'Graveyard',$player);
    FaBWTRCardDiscarded($player,$id,intval($discardSource->UniqueID),$discardSource);
    return true;
}

function FaBHitIsAttackAction(string $mzID): bool {
    $f=FaBIdentityFromMZ($mzID);return $f!==null && FaBWTRIsAttackAction($f['object']);
}

function FaBHitIsWeapon(string $mzID): bool {
    $f=FaBIdentityFromMZ($mzID);return $f!==null && FaBWTRIsWeapon($f['object']);
}

function FaBDefendingChoices(int $defender, bool $equipment = false): string {
    $state=FaBGetState();$refs=[];
    foreach(GetCombatChain($defender)as$i=>$obj)if(is_object($obj)&&empty($obj->removed)&&intval($obj->ChainLink)===intval($state['chainLink'])&&in_array($obj->Role,['DEFENSE','DEFENSE_REACTION'],true)&&($equipment||$obj->FromZone!=='Equipment'))$refs[]='p'.$defender.'CombatChain-'.$i;
    return implode('&',$refs);
}

function FaBPlaceChosenOnDeck(int $player, string $choice, bool $top): void {
    $moved=FaBMoveChoice($player,$choice,'Hand','Deck');
    if($moved!==null&&$top&&!FaBPENTopsy()){$deck=&GetDeck($player);$last=array_pop($deck);array_unshift($deck,$last);}
}

function FaBEquipmentChoices(int $player): string {
    $refs=FaBChoiceRefs($player,'Equipment');
    foreach(GetCombatChain($player)as$i=>$obj)if(is_object($obj)&&empty($obj->removed)&&$obj->FromZone==='Equipment')$refs[]='p'.$player.'CombatChain-'.$i;
    return implode('&',$refs);
}

function FaBWeakenEquipment(string $chosen): void {
    $found=FaBIdentityFromMZ($chosen);
    if($found!==null&&FaBHasType($found['object'],'Equipment'))FaBSetObjectCounter($found['object'],'DEFENSE',intval(FaBObjectCounters($found['object'])['DEFENSE']??0)+1);
}

function FaBAddEnergyCounter(int $uid): void {
    $f=FaBFindUID($uid);
    if($f!==null){$n=intval(FaBObjectCounters($f['object'])['ENERGY']??0);if($n<3)FaBSetObjectCounter($f['object'],'ENERGY',$n+1);}
}

function FaBHandDefendingCount(array $state, ?int $defender=null): int {
    $count=0;
    foreach(GetCombatChain($defender??intval($state['defender']))as$obj)if(is_object($obj)&&empty($obj->removed)&&intval($obj->ChainLink)===intval($state['chainLink'])&&$obj->FromZone==='Hand'&&in_array($obj->Role,['DEFENSE','DEFENSE_REACTION'],true))++$count;
    return $count;
}

/** Only the three synchronous Dread Prepare bodies use this additional-cost contract. */
function FaBDreadPreparedCostCard(string $card): bool {
    return in_array($card, ['dread_screamer_red','dread_screamer_yellow','dread_screamer_blue'], true);
}

/** Scoped around the actual generated callback; never inferred from pending alone. */
function FaBDreadPrepareCostContext(string $macro, int $player, string $card, int $index, ?array $ref): ?array {
    if ($macro !== 'PrepareCard' || $index !== 0 || !FaBDreadPreparedCostCard($card) || $ref === null) return null;
    $state = FaBGetState(); $pending = $state['pendingPayment'] ?? null;
    if (($state['window'] ?? '') !== 'PITCH' || !is_array($pending)
        || intval($pending['player'] ?? 0) !== $player || ($pending['preparedEffectCardID'] ?? '') !== $card
        || isset($pending['preparedEffectCost']) || !empty($pending['preparedEffectError'])
        || !empty($pending['isAbility']) || !empty($pending['isWeaponAttack']) || !empty($pending['isArenaAttack'])) return null;
    $source = FaBPreparedEffectCostSource($pending);
    if ($source === null || $source['object'] !== $ref['object'] || $source['mzID'] !== $ref['mzID']) return null;
    return ['player'=>$player, 'uid'=>intval($pending['uid']), 'cardID'=>$card,
        'sourceMZ'=>$source['mzID'], 'pending'=>$pending];
}

/** Declare random quantity, not random identities. The unchanged Prepare body still finishes. */
function FaBDreadDeferRandomCost(int $player, int $quantity): bool {
    $context = $GLOBALS['fabDreadPrepareCostContext'] ?? null;
    if (!is_array($context) || !FaBDreadPreparedCostCard((string)($context['cardID'] ?? ''))) return false;
    $state = FaBGetState(); $pending = $state['pendingPayment'] ?? null;
    $source = is_array($pending) ? FaBPreparedEffectCostSource($pending) : null;
    $valid = $quantity === 3 && intval($context['player'] ?? 0) === $player
        && is_array($pending) && intval($pending['player'] ?? 0) === $player
        && intval($pending['uid'] ?? 0) === intval($context['uid'] ?? 0)
        && ($pending['preparedEffectCardID'] ?? '') === $context['cardID']
        && ($state['window'] ?? '') === 'PITCH' && $source !== null
        && $source['mzID'] === ($context['sourceMZ'] ?? '')
        && (string)DecisionQueueController::GetVariable('mzID') === $source['mzID']
        && !isset($pending['preparedEffectCost']) && empty($pending['preparedEffectError'])
        && count(FaBChoiceRefs($player, 'Graveyard')) >= 3;
    if (!$valid) {
        // Roll back only after the generated body returns, so its monSix write
        // cannot touch the restored preannouncement card. Finish stays held.
        $GLOBALS['fabDreadPrepareCostContext']['error'] = 'Dread random-cost declaration context changed';
        if (is_array($pending) && intval($pending['uid'] ?? 0) === intval($context['uid'] ?? 0)) {
            $state['pendingPayment']['preparedEffectError'] = $GLOBALS['fabDreadPrepareCostContext']['error'];
            FaBSetState($state);
        }
        return true;
    }
    $state['pendingPayment']['preparedEffectCost'] = [
        'type'=>'BANISH_RANDOM_GRAVEYARD_THREE', 'uids'=>[], 'quantity'=>3, 'cardID'=>$context['cardID'],
        'stage'=>'DECLARED', 'assetsPaid'=>false, 'chargedCost'=>null];
    FaBSetState($state);
    return true;
}

// CR5.1.7 asset payment precedes declared additional effect-cost execution.
function FaBPreparedEffectCostSource(array $pending): ?array {
    if (($pending['preparedEffectCardID'] ?? '') === 'bloodtorn_bodice') return FaBBodicePreparedEffectCostSource($pending);
    if (in_array((string)($pending['preparedEffectCardID'] ?? ''), ['prism','prism_sculptor_of_arc_light'], true)) return FaBPrismPreparedEffectCostSource($pending);
    if (in_array((string)($pending['preparedEffectCardID'] ?? ''), ['chane','chane_bound_by_shadow'], true)) return FaBChanePreparedEffectCostSource($pending);
    $player = intval($pending['player'] ?? 0);
    $uid = intval($pending['uid'] ?? 0);
    $found = FaBFindUID($uid);
    if ($player < 1 || !FaBSeatIsLive($player) || $found === null || $found['zone'] !== 'Stack') return null;
    $source = $found['object'];
    $card = (string)($source->CardID ?? '');
    if ((!in_array($card, ['tome_of_harvests_blue','rouse_the_ancients_blue','nimble_strike_red','nimble_strike_yellow','nimble_strike_blue','celestial_cataclysm_yellow','dread_screamer_red','dread_screamer_yellow','dread_screamer_blue'], true) && !FaBHVYUsesBeatChestCost($card))
        || intval($source->Controller ?? 0) !== $player
        || intval($source->SourceUniqueID ?? 0) !== $uid
        || (string)($source->SourceZone ?? '') !== (string)($pending['fromZone'] ?? '')
        || (string)($source->Kind ?? '') !== (string)($pending['kind'] ?? '')) return null;
    if (array_key_exists('preparedEffectUndoHash', $pending)
        && (string)($pending['preparedEffectCardID'] ?? '') !== $card) return null;
    $expectedType = $card === 'tome_of_harvests_blue' ? 'ARSENAL_BOTTOM'
        : ($card === 'rouse_the_ancients_blue' ? 'REVEAL_THIRTEEN'
        : ($card === 'celestial_cataclysm_yellow' ? 'BANISH_THREE_SOUL' : 'BANISH_NIMBLISM'));
    if (FaBHVYUsesBeatChestCost($card)) $expectedType = 'DISCARD_SIX_BEAT';
    if (FaBDreadPreparedCostCard($card)) {
        if (($source->Kind ?? '') !== 'ATTACK' || !empty($pending['isAbility'])
            || !empty($pending['isWeaponAttack']) || !empty($pending['isArenaAttack'])) return null;
        $expectedType = 'BANISH_RANDOM_GRAVEYARD_THREE';
    }
    $declared = $pending['preparedEffectCost'] ?? null;
    if (is_array($declared) && ((string)($declared['cardID'] ?? '') !== $card
        || (string)($declared['type'] ?? '') !== $expectedType)) return null;
    if (is_array($declared) && $expectedType === 'BANISH_RANDOM_GRAVEYARD_THREE'
        && (intval($declared['quantity'] ?? 0) !== 3 || ($declared['uids'] ?? null) !== [])) return null;
    return $found;
}

function FaBPreparedEffectCostRollback(array $pending, string $reason): bool {
    $player = intval($pending['player'] ?? 0);
    $versions = GetVersions($player);
    $version = $versions[0] ?? null;
    $expected = (string)($pending['preparedEffectUndoHash'] ?? '');
    if ($expected !== '' && is_object($version)
        && hash_equals($expected, hash('sha256', (string)$version->Version))) {
        LoadVersion($player, 0);
        if (function_exists('SetFlashMessage')) SetFlashMessage('Additional-cost play reversed: '.$reason);
        return false;
    }
    // Do not turn an interrupted or corrupt payment into an unpaid play.
    $state = FaBGetState();
    if (intval($state['pendingPayment']['uid'] ?? 0) === intval($pending['uid'] ?? 0)) {
        $state['pendingPayment']['preparedEffectError'] = $reason.' (preannouncement snapshot unavailable)';
        FaBSetState($state);
    }
    if (function_exists('SetFlashMessage')) SetFlashMessage('Additional-cost payment stopped: original snapshot unavailable.');
    return false;
}

/** A prepared source cannot bypass declaration through repeated completion. */
function FaBPreparedEffectCostMayComplete(array $pending): bool {
    if (!array_key_exists('preparedEffectUndoHash', $pending) || isset($pending['preparedEffectCost'])) return true;
    $source = FaBPreparedEffectCostSource($pending);
    if ($source === null) return FaBPreparedEffectCostRollback($pending, 'announced source changed before declaration');
    // Match the existing PrepareCard suppression short-circuit exactly.
    // That path calls FinishPreparedCard without executing any additional-cost ability.
    if (!in_array((string)($pending['preparedEffectCardID'] ?? ''), ['bloodtorn_bodice','prism','prism_sculptor_of_arc_light','chane','chane_bound_by_shadow'], true)
        && (FaBMPGSuppressed($source['object']) || FaBSUPSuppressed($source['object']))) return true;
    return false; // Valid await or missing declaration: keep held, never debit/announce.
}

/** Declaration stores identities only: no zone move, reveal event, power or GA. */
function FaBDeclarePreparedEffectCost(int $player, int $uid, string $type, string $choices): bool {
    $state = FaBGetState();
    $pending = $state['pendingPayment'] ?? null;
    if (!is_array($pending) || intval($pending['player'] ?? 0) !== $player
        || intval($pending['uid'] ?? 0) !== $uid) return false;
    $source = FaBPreparedEffectCostSource($pending);
    if ($source === null || ($source['object']->Kind ?? '') === 'ABILITY') return FaBPreparedEffectCostRollback($pending, 'invalid card-layer additional-cost declaration');
    $wanted = $source['object']->CardID === 'tome_of_harvests_blue' ? 'ARSENAL_BOTTOM'
        : ($source['object']->CardID === 'rouse_the_ancients_blue' ? 'REVEAL_THIRTEEN'
        : ($source['object']->CardID === 'celestial_cataclysm_yellow' ? 'BANISH_THREE_SOUL' : 'BANISH_NIMBLISM'));
    if (FaBHVYUsesBeatChestCost((string)$source['object']->CardID)) $wanted = 'DISCARD_SIX_BEAT';
    if ($type !== $wanted || isset($pending['preparedEffectCost']) || !empty($pending['preparedEffectError'])) {
        return FaBPreparedEffectCostRollback($pending, 'invalid or duplicate declaration');
    }
    $none = in_array(trim($choices), ['', '-', 'PASS'], true);
    $refs = $none ? [] : explode('&', $choices);
    $uids = [];
    foreach ($refs as $ref) {
        $found = FaBIdentityFromMZ($ref);
        $zone = $type === 'ARSENAL_BOTTOM' ? 'Arsenal' : ($type === 'BANISH_NIMBLISM' ? 'Graveyard' : ($type === 'BANISH_THREE_SOUL' ? 'Soul' : 'Hand'));
        if ($found === null || $found['player'] !== $player || $found['zone'] !== $zone
            || !empty($found['object']->removed)
            || ($type === 'REVEAL_THIRTEEN' && !FaBWTRIsAttackAction($found['object']))
            || ($type === 'DISCARD_SIX_BEAT' && !in_array($ref, explode('&', FaBHVYSix($player)), true))
            || ($type === 'BANISH_NIMBLISM' && FaBWTRBase((string)$found['object']->CardID) !== 'nimblism')) {
            return FaBPreparedEffectCostRollback($pending, 'declared cost card is unavailable');
        }
        $chosenUID = intval($found['object']->UniqueID);
        if ($chosenUID <= 0 || in_array($chosenUID, $uids, true)) {
            return FaBPreparedEffectCostRollback($pending, 'duplicate or invalid declared identity');
        }
        $uids[] = $chosenUID;
    }
    if (($type === 'ARSENAL_BOTTOM' && count($uids) !== 1)
        || ($type === 'BANISH_NIMBLISM' && count($uids) > 1)
        || ($type === 'DISCARD_SIX_BEAT' && count($uids) > 1)
        || ($type === 'BANISH_THREE_SOUL' && count($uids) !== 3)
        || ($type === 'REVEAL_THIRTEEN' && $uids && (FaBUPRBleak() || FaBMONRevealPower($choices) < 13))) {
        return FaBPreparedEffectCostRollback($pending, 'declared additional cost cannot be paid');
    }
    if ($type === 'DISCARD_SIX_BEAT' && $uids
        && FaBAvailablePitch($player, $uids[0]) < max(0, intval($pending['cost'] ?? 0))) {
        return FaBPreparedEffectCostRollback($pending, 'reserved Beat Chest card leaves insufficient asset funding');
    }
    $state['pendingPayment']['preparedEffectCost'] = [
        'type'=>$type, 'uids'=>$uids, 'cardID'=>(string)$source['object']->CardID,
        'stage'=>'DECLARED', 'assetsPaid'=>false, 'chargedCost'=>null];
    FaBSetState($state);
    return true;
}

/** Declared reveal identities cannot also be spent as resource funding. */
function FaBPreparedEffectCostCanPitch(array $pending, object $pitched): bool {
    if (!isset($pending['preparedEffectCost'])) return true;
    $cost = $pending['preparedEffectCost'];
    if (!empty($pending['preparedEffectError']) || !empty($cost['assetsPaid'])
        || ($cost['stage'] ?? '') !== 'DECLARED' || FaBPreparedEffectCostSource($pending) === null) return false;
    return !in_array(($cost['type'] ?? ''), ['REVEAL_THIRTEEN','DISCARD_SIX_BEAT'], true)
        || !in_array(intval($pitched->UniqueID), array_map('intval', (array)($cost['uids'] ?? [])), true);
}

/** Mark before the normal resource/AP debit so re-entry cannot debit twice. */
function FaBPreparedEffectCostBeginAssets(array $pending): bool {
    if (!isset($pending['preparedEffectCost'])) return true;
    $cost = $pending['preparedEffectCost'];
    $source = FaBPreparedEffectCostSource($pending);
    $paysAction = in_array((string)$pending['kind'], ['ACTION','ATTACK'], true) || !empty($pending['abilityAction']);
    if ($source === null || ($cost['stage'] ?? '') !== 'DECLARED' || !empty($cost['assetsPaid'])
        || ($paysAction && intval(GetActionPoints(intval($pending['player']))) < 1)) {
        return FaBPreparedEffectCostRollback($pending, 'invalid asset-payment stage');
    }
    if (($cost['type'] ?? '') === 'BANISH_RANDOM_GRAVEYARD_THREE'
        && count(FaBChoiceRefs(intval($pending['player']), 'Graveyard')) < 3) {
        return FaBPreparedEffectCostRollback($pending, 'Dread random cost is unavailable before assets');
    }
    $state = FaBGetState();
    $state['pendingPayment']['preparedEffectCost']['stage'] = 'PAYING_ASSETS';
    FaBSetState($state);
    return true;
}

function FaBPreparedEffectCostAssetsArePaid(array $pending): bool {
    $cost = $pending['preparedEffectCost'] ?? null;
    return is_array($cost) && !empty($cost['assetsPaid'])
        && intval($cost['chargedCost'] ?? -1) === max(0, intval($pending['cost']))
        && FaBPreparedEffectCostSource($pending) !== null;
}

function FaBPreparedEffectCostMarkAssetsPaid(int $actualChargedCost): bool {
    $state = FaBGetState(); $pending = $state['pendingPayment'] ?? null;
    if (!is_array($pending) || !isset($pending['preparedEffectCost'])) return true;
    $cost = $pending['preparedEffectCost'];
    if ($actualChargedCost < 0 || max(0, intval($pending['cost'])) !== $actualChargedCost) {
        return FaBPreparedEffectCostRollback($pending, 'asset cost changed during debit');
    }
    if (!empty($cost['assetsPaid'])) return FaBPreparedEffectCostAssetsArePaid($pending)
        && intval($cost['chargedCost'] ?? -1) === $actualChargedCost;
    // The established Boost asset path may already have performed the same debit.
    $boostPaid = !empty($pending['arcBoostAssetsPaid'])
        && intval($pending['arcBoostChargedCost'] ?? -1) === max(0, intval($pending['cost']));
    if (FaBPreparedEffectCostSource($pending) === null
        || (($cost['stage'] ?? '') !== 'PAYING_ASSETS' && !(($cost['stage'] ?? '') === 'DECLARED' && $boostPaid))) {
        return FaBPreparedEffectCostRollback($pending, 'asset debit was not acknowledged');
    }
    $state['pendingPayment']['preparedEffectCost']['assetsPaid'] = true;
    $state['pendingPayment']['preparedEffectCost']['chargedCost'] = $actualChargedCost;
    $state['pendingPayment']['preparedEffectCost']['stage'] = 'ASSETS_PAID';
    FaBSetState($state);
    return true;
}

/** Called after the existing asset/Boost steps, before pending clear/OnCardPlayed. */
function FaBPayDeclaredPreparedEffectCost(): bool {
    $state = FaBGetState(); $pending = $state['pendingPayment'] ?? null;
    if (!is_array($pending) || !isset($pending['preparedEffectCost'])) return true;
    $cost = $pending['preparedEffectCost'];
    if (!FaBPreparedEffectCostAssetsArePaid($pending)) return FaBPreparedEffectCostRollback($pending, 'assets are unpaid or changed');
    if (($cost['stage'] ?? '') === 'COMPLETE') return true;
    if (($cost['stage'] ?? '') !== 'ASSETS_PAID') return false; // In-progress re-entry stays held.
    $player = intval($pending['player']); $uid = intval($pending['uid']);
    if (in_array($cost['type'], ['BANISH_NIMBLISM','BANISH_THREE_SOUL','DISCARD_SIX_BEAT'], true)) {
        $identities = array_map('intval', (array)($cost['uids'] ?? []));
        if (count(array_unique($identities)) !== count($identities)
            || count(array_filter($identities, fn($id) => $id > 0)) !== count($identities)) {
            return FaBPreparedEffectCostRollback($pending, 'declared cost identities are invalid or duplicated');
        }
    }
    $refs = [];
    foreach ((array)($cost['uids'] ?? []) as $chosenUID) {
        $found = FaBFindUID(intval($chosenUID));
        $zone = $cost['type'] === 'ARSENAL_BOTTOM' ? 'Arsenal' : ($cost['type'] === 'DESTROY_CONTROLLED_AURA' ? 'Arena' : (in_array($cost['type'], ['BANISH_OWN_SOUL','BANISH_THREE_SOUL'], true) ? 'Soul' : ($cost['type'] === 'BANISH_NIMBLISM' ? 'Graveyard' : 'Hand')));
        if ($found === null || $found['player'] !== $player || $found['zone'] !== $zone
            || ($cost['type'] === 'REVEAL_THIRTEEN' && !FaBWTRIsAttackAction($found['object']))
            || ($cost['type'] === 'DISCARD_SIX_BEAT' && (!empty($found['object']->removed)
                || !in_array($found['mzID'], explode('&', FaBHVYSix($player)), true)))
            || ($cost['type'] === 'BANISH_NIMBLISM' && (!empty($found['object']->removed)
                || FaBWTRBase((string)$found['object']->CardID) !== 'nimblism'))
            || ($cost['type'] === 'DESTROY_CONTROLLED_AURA' && !FaBBodiceControlledAura($found, $player))
            || ($cost['type'] === 'BANISH_OWN_SOUL' && (!empty($found['object']->removed)
                || (string)($found['object']->CardID ?? '') !== (string)($cost['soulCardID'] ?? '')))) {
            return FaBPreparedEffectCostRollback($pending, 'declared cost identity left its zone');
        }
        $refs[] = $found['mzID'];
    }
    $reveal = implode('&', $refs);
    if ((in_array($cost['type'], ['ARSENAL_BOTTOM','DESTROY_CONTROLLED_AURA','BANISH_OWN_SOUL'], true) && count($refs) !== 1)
        || ($cost['type'] === 'BANISH_NIMBLISM' && count($refs) > 1)
        || ($cost['type'] === 'DISCARD_SIX_BEAT' && count($refs) > 1)
        || ($cost['type'] === 'BANISH_THREE_SOUL' && count($refs) !== 3)
        || ($cost['type'] === 'REVEAL_THIRTEEN' && $refs && (FaBUPRBleak() || FaBMONRevealPower($reveal) < 13))) {
        return FaBPreparedEffectCostRollback($pending, 'effect cost became unavailable after assets');
    }
    if ($cost['type'] === 'BANISH_RANDOM_GRAVEYARD_THREE'
        && (intval($cost['quantity'] ?? 0) !== 3 || ($cost['uids'] ?? null) !== []
            || count(FaBChoiceRefs($player, 'Graveyard')) < 3)) {
        return FaBPreparedEffectCostRollback($pending, 'Dread random cost is unavailable after assets');
    }
    $state['pendingPayment']['preparedEffectCost']['stage'] = 'PAYING_EFFECTS';
    FaBSetState($state);
    if ($cost['type'] === 'ARSENAL_BOTTOM') {
        // Existing zone replacement/event hooks execute here, after asset payment.
        // CR1.14.4c: a replaced effect-cost can still count as paid.
        FaBMoveUID(intval($cost['uids'][0]), 'Deck', $player);
    } elseif ($cost['type'] === 'DESTROY_CONTROLLED_AURA') {
        // The physical equipment destroys itself in the existing ARC ability-cost path.
        FaBMONDestroy(intval($cost['uids'][0]));
    } elseif ($cost['type'] === 'BANISH_OWN_SOUL') {
        // The real Soul-to-Banish move and its listeners run only after assets.
        FaBMoveUID(intval($cost['uids'][0]), 'Banish', $player);
    } elseif ($cost['type'] === 'BANISH_THREE_SOUL') {
        // Celestial's exact three identities are checked together before any
        // movement; keep Prism's separate one-Soul cost contract unchanged.
        foreach ((array)$cost['uids'] as $soulUID) FaBMoveUID(intval($soulUID), 'Banish', $player);
        FaBARCSetCard($uid, 'monX', count($cost['uids']));
    } elseif ($cost['type'] === 'BANISH_RANDOM_GRAVEYARD_THREE') {
        // Run the existing random selection/movement algorithm after the real debit.
        // Bypass the declaration wrapper even if Finish is still inside Prepare.
        $six = FaBMONBanishRandomImmediate($player, 3);
        FaBARCSetCard($uid, 'monSix', $six);
        if ($six) FaBTagUID($uid, 'GO_AGAIN');
    } elseif ($cost['type'] === 'DISCARD_SIX_BEAT') {
        if ($refs) {
            $origin = FaBPreparedEffectCostSource($pending);
            if ($origin === null) return FaBPreparedEffectCostRollback($pending, 'Beat Chest source context changed');
            $variables = json_decode((string)GetDecisionQueueVariables(), true);
            $hadMZ = is_array($variables) && array_key_exists('mzID', $variables);
            $previousMZ = $variables['mzID'] ?? null;
            $previousEffect = $GLOBALS['fabEffectController'] ?? 0;
            $GLOBALS['fabEffectController'] = $player;
            DecisionQueueController::StoreVariable('mzID', $origin['mzID']);
            try {
                if (!FaBDiscardChoice($player, $refs[0])) return FaBPreparedEffectCostRollback($pending, 'Beat Chest discard failed');
                FaBHVYAdd($player, 'BEAT');
            } finally {
                if ($hadMZ) DecisionQueueController::StoreVariable('mzID', $previousMZ);
                else DecisionQueueController::ClearVariable('mzID');
                $GLOBALS['fabEffectController'] = $previousEffect;
            }
        }
    } elseif ($cost['type'] === 'BANISH_NIMBLISM') {
        if ($refs) {
            // The selected identity is reserved during declaration; move and paid
            // marker occur only after the existing resource/AP debit. A replaced
            // effect-cost still counts as paid (CR1.14.4c), as for Arsenal bottom.
            FaBMoveUID(intval($cost['uids'][0]), 'Banish', $player);
            FaBTagUID($uid, 'WTR_NIMBLE_PAID');
        }
    } elseif ($cost['type'] === 'CREATE_SOUL_SHACKLE') {
        if (FaBSEAHeroCreationBlocked($player) || FaBMPGTokenCount($player, 'soul_shackle', 1) < 1) {
            return FaBPreparedEffectCostRollback($pending, 'Soul Shackle creation cost is unavailable');
        }
        // A Hero ability cost is not an action-card effect. Preserve the real
        // token factory/replacements, with the cost payer as creator and owner.
        $created = FaBHVYToken($player, 'soul_shackle', 1, $player, false, false, $player);
        if ($created === null) return FaBPreparedEffectCostRollback($pending, 'Soul Shackle creation cost failed');
    } elseif ($refs) {
        FaBRevealChoices($player, $reveal);
        FaBCRUSelfTagUID($uid, 'WTR_POWER:7');
        FaBTagUID($uid, 'GO_AGAIN');
    }
    $state = FaBGetState();
    if (intval($state['pendingPayment']['uid'] ?? 0) !== $uid
        || ($state['pendingPayment']['preparedEffectCost']['stage'] ?? '') !== 'PAYING_EFFECTS') return false;
    $state['pendingPayment']['preparedEffectCost']['stage'] = 'COMPLETE';
    FaBSetState($state);
    return true;
}

/** Writer-only projection of the exact offered Hand identities; no cost/effect change. */
function FaBPreparedDarkestChoicesForSave(array $zone, int $seat): array {
    $pending = FaBGetState()['pendingPayment'] ?? null;
    if (!is_array($pending) || !FaBSeatIsLive($seat) || intval($pending['player'] ?? 0) !== $seat
        || ($pending['kind'] ?? '') !== 'ACTION' || !empty($pending['isAbility'])) return $zone;
    $uid = intval($pending['uid'] ?? 0); $source = $uid > 0 ? FaBFindUID($uid) : null;
    if ($source === null || $source['zone'] !== 'Stack' || $source['object']->CardID !== 'darkest_hour_blue'
        || ($source['object']->Kind ?? '') !== 'ACTION' || intval($source['object']->UniqueID ?? 0) !== $uid
        || intval($source['object']->Controller ?? 0) !== $seat
        || (intval($source['object']->Owner ?? 0) > 0 && intval($source['object']->Owner) !== $seat)) return $zone;
    $live = [];
    foreach ($zone as $index=>$row) if (is_object($row) && !$row->Removed()) $live[] = $index;
    if (count($live) < 2) return $zone;
    $index = $live[0]; $choice = $zone[$index]; $resume = $zone[$live[1]];
    if (($choice->Type ?? '') !== 'MZMULTICHOOSE' || ($choice->Tooltip ?? '') !== 'Pay_alternative_cost'
        || ($resume->Type ?? '') !== 'CUSTOM'
        || intval($choice->Block ?? 0) !== 1 || intval($resume->Block ?? 0) !== 1) return $zone;
    $options = explode('|', (string)($choice->Param ?? ''), 3);
    $parts = explode('|', (string)($resume->Param ?? ''));
    $stem = 'darkest_hour_blue:0:PrepareCard';
    if (count($options) !== 3 || $options[0] !== '0' || $options[1] !== '1' || $options[2] === ''
        || count($parts) !== 4 || $parts[0] !== $stem.'-await' || $parts[3] !== 'chosen'
        || $parts[2] !== '3'
        || preg_match('/^'.preg_quote($stem,'/').'#[1-9][0-9]*$/D', $parts[1]) !== 1) return $zone;
    $frame = DecisionQueueController::GetAwaitFrame($parts[1]);
    if (!is_array($frame) || intval($frame['locals']['uid'] ?? 0) !== $uid
        || (string)($frame['locals']['refs'] ?? '') !== $options[2]) return $zone;
    $map = []; $uids = []; $next = 0;
    foreach (GetHand($seat) as $old=>$card) {
        // Match WriteGamestate's skip predicate, preserving the offered subset/order.
        if ($card == null || $card->Removed()) continue;
        $cardUID = intval($card->UniqueID ?? 0);
        if ($cardUID < 1 || isset($uids[$cardUID])) return $zone;
        $uids[$cardUID] = true;
        $map['p'.$seat.'Hand-'.$old] = ['uid'=>$cardUID,'ref'=>'p'.$seat.'Hand-'.$next++];
    }
    $refs = []; $offered = [];
    foreach (explode('&', $options[2]) as $old) {
        if (!isset($map[$old]) || isset($offered[$map[$old]['uid']])) return $zone;
        $offered[$map[$old]['uid']] = true; $refs[] = $map[$old]['ref'];
    }
    $param = '0|1|'.implode('&',$refs);
    if ($param === $choice->Param) return $zone;
    $copy = clone $choice; $copy->Param = $param; $zone[$index] = $copy;
    return $zone;
}

/** Copied-save remap for this exact Rouse declaration frame; Rise036 is untouched. */
function FaBPreparedRouseChoicesForSave(array $zone, int $seat): array {
    $pending = FaBGetState()['pendingPayment'] ?? null;
    if (!is_array($pending) || intval($pending['player'] ?? 0) !== $seat
        || empty($pending['preparedEffectUndoHash']) || isset($pending['preparedEffectCost'])) return $zone;
    $source = FaBPreparedEffectCostSource($pending);
    if ($source === null || $source['object']->CardID !== 'rouse_the_ancients_blue') return $zone;
    $refs = []; $next = 0;
    foreach (GetHand($seat) as $obj) {
        if ($obj == null || $obj->Removed()) continue;
        if (FaBWTRIsAttackAction($obj)) $refs[] = 'p'.$seat.'Hand-'.$next;
        ++$next;
    }
    $indices = [];
    foreach ($zone as $index=>$row) if (is_object($row) && !$row->Removed()) $indices[] = $index;
    for ($i=0; $i+1<count($indices); ++$i) {
        $index=$indices[$i]; $choice=$zone[$index]; $resume=$zone[$indices[$i+1]];
        if (($choice->Type ?? '') !== 'MZMULTICHOOSE'
            || ($choice->Tooltip ?? '') !== 'Declare_optional_reveal_thirteen_cost'
            || ($resume->Type ?? '') !== 'CUSTOM') continue;
        $parts = explode('|', (string)($resume->Param ?? ''));
        $stem = 'rouse_the_ancients_blue:0:PrepareCard';
        if (count($parts) !== 4 || $parts[0] !== $stem.'-await' || $parts[3] !== 'chosen'
            || !ctype_digit($parts[2]) || intval($parts[2]) < 1
            || preg_match('/^'.preg_quote($stem,'/').'#[1-9][0-9]*$/D', $parts[1]) !== 1) continue;
        $frame = DecisionQueueController::GetAwaitFrame($parts[1]);
        if (!is_array($frame) || intval($frame['locals']['uid'] ?? 0) !== intval($pending['uid'])) continue;
        $copy = clone $choice;
        $copy->Param = '0|'.count($refs).'|'.implode('&',$refs);
        $zone[$index] = $copy;
    }
    return $zone;
}

// Separate activation contract; never use the card-layer self-UID shortcut.
function FaBBodicePreparedEffectCostSource(array $pending): ?array {
    $p=intval($pending['player']??0);$uid=intval($pending['uid']??0);$sourceUID=intval($pending['preparedBodiceSourceUID']??0);
    if(!FaBSeatIsLive($p)||($pending['preparedEffectCardID']??'')!=='bloodtorn_bodice'
        ||($pending['kind']??'')!=='ABILITY'||empty($pending['isAbility'])||empty($pending['abilityAction'])
        ||($pending['fromZone']??'')!=='Equipment'||$sourceUID<1||$sourceUID===$uid)return null;
    $layer=FaBFindUID($uid);$physical=FaBFindUID($sourceUID);
    if($layer===null||$layer['zone']!=='Stack'||$physical===null||$physical['zone']!=='Equipment'
        ||intval($physical['player'])!==$p||intval($physical['object']->Controller??0)!==$p
        ||(string)$physical['object']->CardID!=='bloodtorn_bodice')return null;
    $o=$layer['object'];$spec=$o->Params['arcSpec']??null;
    if((string)$o->CardID!=='bloodtorn_bodice'||($o->Kind??'')!=='ABILITY'||intval($o->Controller??0)!==$p
        ||($o->SourceZone??'')!=='Equipment'||intval($o->SourceUniqueID??0)!==$sourceUID
        ||!is_array($spec)||($spec['timing']??'')!=='ACTION'||empty($spec['destroy'])||empty($spec['goAgain'])
        ||intval($spec['index']??-1)!==0||!empty($o->Params['rosTrigger']))return null;
    $cost=$pending['preparedEffectCost']??null;
    if(is_array($cost)&&(($cost['cardID']??'')!=='bloodtorn_bodice'||($cost['type']??'')!=='DESTROY_CONTROLLED_AURA'
        ||intval($cost['sourceUID']??0)!==$sourceUID))return null;
    return $layer;
}

/** The cost says control, so a foreign-owned controlled Aura is still eligible. */
function FaBBodiceControlledAura(array $found,int $p): bool {
    return ($found['zone']??'')==='Arena'&&intval($found['player']??0)===$p
        &&intval($found['object']->Controller??0)===$p&&empty($found['object']->removed)
        &&FaBHasType($found['object'],'Aura');
}

function FaBDeclareBodiceAuraCost(int $p,int $uid,string $chosen): bool {
    $s=FaBGetState();$pending=$s['pendingPayment']??null;
    if(!is_array($pending)||intval($pending['player']??0)!==$p||intval($pending['uid']??0)!==$uid)return false;
    if(FaBBodicePreparedEffectCostSource($pending)===null||isset($pending['preparedEffectCost']))return FaBPreparedEffectCostRollback($pending,'Bodice source changed or duplicate declaration');
    $aura=FaBIdentityFromMZ($chosen);
    if($aura===null||!FaBBodiceControlledAura($aura,$p)||intval($aura['object']->UniqueID??0)<1)return FaBPreparedEffectCostRollback($pending,'Bodice controlled Aura cost unavailable');
    $s['pendingPayment']['preparedEffectCost']=['type'=>'DESTROY_CONTROLLED_AURA','uids'=>[intval($aura['object']->UniqueID)],
        'cardID'=>'bloodtorn_bodice','sourceUID'=>intval($pending['preparedBodiceSourceUID']),
        'stage'=>'DECLARED','assetsPaid'=>false,'chargedCost'=>null];
    FaBSetState($s);return true;
}


/** Prism's hero activation binds its physical hero separately from the virtual layer. */
function FaBPrismPreparedEffectCostSource(array $pending): ?array {
    $p = intval($pending['player'] ?? 0);
    $uid = intval($pending['uid'] ?? 0);
    $heroUID = intval($pending['preparedPrismSourceUID'] ?? 0);
    $card = (string)($pending['preparedEffectCardID'] ?? '');
    if (!in_array($card, ['prism','prism_sculptor_of_arc_light'], true)
        || !FaBSeatIsLive($p) || ($pending['kind'] ?? '') !== 'ABILITY'
        || empty($pending['isAbility']) || !empty($pending['abilityAction'])
        || ($pending['fromZone'] ?? '') !== 'Hero' || $heroUID < 1 || $uid < 1 || $heroUID === $uid) return null;
    $layer = FaBFindUID($uid); $hero = FaBFindUID($heroUID);
    if ($layer === null || $layer['zone'] !== 'Stack' || $hero === null || $hero['zone'] !== 'Hero'
        || intval($hero['player']) !== $p || intval($hero['object']->Owner ?? 0) !== $p
        || intval($hero['object']->Controller ?? 0) !== $p || (string)$hero['object']->CardID !== $card) return null;
    $o = $layer['object']; $spec = $o->Params['arcSpec'] ?? null;
    if ((string)$o->CardID !== $card || ($o->Kind ?? '') !== 'ABILITY' || intval($o->Controller ?? 0) !== $p
        || ($o->SourceZone ?? '') !== 'Hero' || intval($o->SourceUniqueID ?? 0) !== $heroUID
        || !is_array($spec) || ($spec['cardID'] ?? '') !== $card || ($spec['timing'] ?? '') !== 'INSTANT'
        || intval($spec['index'] ?? -1) !== 0 || intval($spec['cost'] ?? -1) !== 2
        || !empty($spec['destroy']) || !empty($spec['goAgain']) || empty($spec['once'])
        || intval($spec['steam'] ?? -1) !== 0 || !empty($o->Params['rosTrigger'])) return null;
    $cost = $pending['preparedEffectCost'] ?? null;
    if (is_array($cost) && (($cost['cardID'] ?? '') !== $card || ($cost['type'] ?? '') !== 'BANISH_OWN_SOUL'
        || intval($cost['sourceUID'] ?? 0) !== $heroUID)) return null;
    return $layer;
}

/** Soul has implicit seat ownership; bind its real UID without unsupported Owner fields. */
function FaBDeclarePrismSoulCost(int $p, int $uid, string $chosen): bool {
    $s = FaBGetState(); $pending = $s['pendingPayment'] ?? null;
    if (!is_array($pending) || intval($pending['player'] ?? 0) !== $p || intval($pending['uid'] ?? 0) !== $uid) return false;
    if (FaBPrismPreparedEffectCostSource($pending) === null || isset($pending['preparedEffectCost'])
        || !empty($pending['preparedEffectError'])) return FaBPreparedEffectCostRollback($pending, 'Prism source changed or duplicate Soul declaration');
    $soul = FaBIdentityFromMZ($chosen);
    if ($soul === null || $soul['zone'] !== 'Soul' || intval($soul['player']) !== $p
        || !empty($soul['object']->removed) || intval($soul['object']->UniqueID ?? 0) < 1) {
        return FaBPreparedEffectCostRollback($pending, 'Prism Soul cost unavailable');
    }
    $s['pendingPayment']['preparedEffectCost'] = [
        'type'=>'BANISH_OWN_SOUL', 'uids'=>[intval($soul['object']->UniqueID)],
        'cardID'=>(string)$pending['preparedEffectCardID'], 'sourceUID'=>intval($pending['preparedPrismSourceUID']),
        'soulCardID'=>(string)$soul['object']->CardID, 'stage'=>'DECLARED', 'assetsPaid'=>false, 'chargedCost'=>null];
    FaBSetState($s);
    return true;
}


/** Chane pays an effect-cost from its physical Hero through a separate ability layer. */
function FaBChanePreparedEffectCostSource(array $pending): ?array {
    $p = intval($pending['player'] ?? 0); $uid = intval($pending['uid'] ?? 0);
    $heroUID = intval($pending['preparedChaneSourceUID'] ?? 0);
    $card = (string)($pending['preparedEffectCardID'] ?? '');
    if (!in_array($card, ['chane','chane_bound_by_shadow'], true) || !FaBSeatIsLive($p)
        || ($pending['kind'] ?? '') !== 'ABILITY' || empty($pending['isAbility']) || empty($pending['abilityAction'])
        || ($pending['fromZone'] ?? '') !== 'Hero' || $heroUID < 1 || $uid < 1 || $heroUID === $uid) return null;
    $layer = FaBFindUID($uid); $hero = FaBFindUID($heroUID);
    if ($layer === null || $layer['zone'] !== 'Stack' || $hero === null || $hero['zone'] !== 'Hero'
        || intval($hero['player']) !== $p || intval($hero['object']->Owner ?? 0) !== $p
        || intval($hero['object']->Controller ?? 0) !== $p || (string)$hero['object']->CardID !== $card) return null;
    $o = $layer['object']; $spec = $o->Params['arcSpec'] ?? null;
    if ((string)$o->CardID !== $card || ($o->Kind ?? '') !== 'ABILITY' || intval($o->Controller ?? 0) !== $p
        || ($o->SourceZone ?? '') !== 'Hero' || intval($o->SourceUniqueID ?? 0) !== $heroUID
        || !is_array($spec) || ($spec['cardID'] ?? '') !== $card || ($spec['timing'] ?? '') !== 'ACTION'
        || intval($spec['index'] ?? -1) !== 0 || intval($spec['cost'] ?? -1) !== 0
        || !empty($spec['destroy']) || empty($spec['goAgain']) || empty($spec['once'])
        || intval($spec['steam'] ?? -1) !== 0 || !empty($o->Params['rosTrigger'])) return null;
    $cost = $pending['preparedEffectCost'] ?? null;
    if (is_array($cost) && (($cost['cardID'] ?? '') !== $card || ($cost['type'] ?? '') !== 'CREATE_SOUL_SHACKLE'
        || intval($cost['sourceUID'] ?? 0) !== $heroUID || ($cost['uids'] ?? null) !== []
        || ($cost['tokenCardID'] ?? '') !== 'soul_shackle' || intval($cost['amount'] ?? 0) !== 1)) return null;
    return $layer;
}

/** No choice is needed: declaration records the required creation, without creating it. */
function FaBDeclareChaneShackleCost(int $p, int $uid): bool {
    $s = FaBGetState(); $pending = $s['pendingPayment'] ?? null;
    if (!is_array($pending) || intval($pending['player'] ?? 0) !== $p || intval($pending['uid'] ?? 0) !== $uid) return false;
    if (FaBChanePreparedEffectCostSource($pending) === null || isset($pending['preparedEffectCost'])
        || !empty($pending['preparedEffectError']) || FaBSEAHeroCreationBlocked($p)
        || FaBMPGTokenCount($p, 'soul_shackle', 1) < 1) {
        return FaBPreparedEffectCostRollback($pending, 'Chane source changed or creation cost unavailable');
    }
    $s['pendingPayment']['preparedEffectCost'] = [
        'type'=>'CREATE_SOUL_SHACKLE', 'uids'=>[], 'cardID'=>(string)$pending['preparedEffectCardID'],
        'sourceUID'=>intval($pending['preparedChaneSourceUID']), 'tokenCardID'=>'soul_shackle', 'amount'=>1,
        'stage'=>'DECLARED', 'assetsPaid'=>false, 'chargedCost'=>null];
    FaBSetState($s);
    return true;
}
