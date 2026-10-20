<?php

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
    if($tag==='WTR_POWER:1'&&FaBGetState()['window']==='REACTION'&&intval(FaBGetState()['attackUID'])===$uid)foreach(FaBMONArena($found['player'],'talisman_of_featherfoot') as $r){FaBMONDestroy(intval(FaBIdentityFromMZ($r)['object']->UniqueID));FaBWTRTag($found['object'],'GO_AGAIN');}
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
    $id=$found['object']->CardID;
    FaBMoveUID(intval($found['object']->UniqueID),'Graveyard',$player);
    FaBWTRCardDiscarded($player,$id);
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
