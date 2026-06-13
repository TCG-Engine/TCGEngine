<?php

$debugMode = true;
$customDQHandlers = [];
$untilBeginTurnEffects = [];
$untilBeginTurnEffects['STONEHAVEN_DEFENDER'] = true;
$untilBeginTurnEffects['SHOCKED'] = true;
$untilBeginTurnEffects['EFFECT_DAMAGE_IMMUNE'] = true;
$untilBeginOpponentTurnEffects = [];
$untilBeginOpponentTurnEffects['FROZEN'] = true;
$untilBeginOpponentTurnEffects['ROOTED'] = true;

// --- Helper Functions ---

function NormalizeFieldOwnership($obj, $player) {
    if(!is_object($obj)) return;

    if(!isset($obj->Owner) || intval($obj->Owner) <= 0) {
        $obj->Owner = intval($player);
    }

    if(!isset($obj->Controller) || intval($obj->Controller) <= 0) {
        $obj->Controller = intval($player);
    }
}

function IsFieldZoneName($zoneName) {
    return $zoneName === 'myGarden' || $zoneName === 'theirGarden' || $zoneName === 'myAlley' || $zoneName === 'theirAlley';
}

function ResolveOwnerFromPerspectiveZone($player, $zoneName) {
    if(!is_string($zoneName) || $zoneName === '') return intval($player);
    if(strpos($zoneName, 'their') === 0) return intval($player) === 1 ? 2 : 1;
    return intval($player);
}

function NormalizeMZForPlayerPerspective($player, $mzID) {
    global $playerID;
    if(!is_string($mzID) || $mzID === '') return $mzID;
    if(intval($player) !== intval($playerID)) {
        return FlipZonePerspective($mzID);
    }
    return $mzID;
}

function SaveActionSnapshot($player) {
    $player = intval($player);
    if($player !== 1 && $player !== 2) return;
    SaveVersion($player);
}

function MZMoveToDeckTop($player, $mzIndex, $toZone = 'myDeck') {
    global $playerID;
    if($player != $playerID) {
        $mzIndex = FlipZonePerspective($mzIndex);
        $toZone = FlipZonePerspective($toZone);
    }

    if($toZone !== 'myDeck' && $toZone !== 'theirDeck') return null;

    $removed = GetZoneObject($mzIndex);
    if($removed === null) return null;
    if(property_exists($removed, 'removed') && $removed->removed === true) return null;

    $removed->_sourceZone = strtok($mzIndex, '-');
    $removed->Remove();

    $deckOwner = ($toZone === 'theirDeck') ? ($player == 1 ? 2 : 1) : intval($player);
    $deck = &GetDeck($deckOwner);
    $zoneObj = new Deck($removed->CardID, 'Deck', $deckOwner, 0);

    $properties = get_object_vars($removed);
    foreach($properties as $prop => $value) {
        if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
            $zoneObj->$prop = $value;
        }
    }

    array_unshift($deck, $zoneObj);
    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
        $deck[$i]->BuildIndex();
    }

    return $zoneObj;
}

function QueueTidalInsightRearrange($player) {
    $tempStart = intval(DecisionQueueController::GetVariable('P' . intval($player) . '_TidalInsightTempStart'));
    $tempZone = &GetTempZone($player);
    $remaining = [];
    for($i = $tempStart; $i < count($tempZone); ++$i) {
        if(isset($tempZone[$i]->removed) && $tempZone[$i]->removed) continue;
        $remaining[] = $tempZone[$i]->CardID ?? '';
    }

    $remaining = array_values(array_filter($remaining, fn($cardID) => is_string($cardID) && $cardID !== ''));
    if(empty($remaining)) return;

    $param = 'Top=' . implode(',', $remaining) . ';Bottom=';
    DecisionQueueController::AddDecision($player, 'MZREARRANGE', $param, 1, 'Tidal_Insight:_Top=return_to_top,_Bottom=put_on_bottom');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'TidalInsightApply', 1);
}

function ParsePerspectiveMzID($perspectivePlayer, $mzID) {
    if(!is_string($mzID) || $mzID === '') return null;

    $parts = explode('-', $mzID);
    $zoneToken = $parts[0] ?? '';
    $index = intval($parts[1] ?? -1);
    if($zoneToken === '' || $index < 0) return null;

    $locationToken = $zoneToken;
    if(strpos($zoneToken, 'my') === 0) {
        $locationToken = substr($zoneToken, 2);
    } elseif(strpos($zoneToken, 'their') === 0) {
        $locationToken = substr($zoneToken, 5);
    }
    if(!is_string($locationToken) || $locationToken === '') return null;

    return [
        'owner' => ResolveOwnerFromPerspectiveZone($perspectivePlayer, $zoneToken),
        'location' => $locationToken,
        'index' => $index
    ];
}

function PendingAttackRefExistsInExpectedZone($attackerPlayer, $pendingMZ, $expectedOwner, $expectedLocation) {
    $abs = ParsePerspectiveMzID($attackerPlayer, $pendingMZ);
    if(!is_array($abs)) return false;

    $owner = intval($abs['owner'] ?? 0);
    $location = strval($abs['location'] ?? '');
    $index = intval($abs['index'] ?? -1);

    if($owner !== intval($expectedOwner)) return false;
    if(strcasecmp($location, strval($expectedLocation)) !== 0) return false;
    if($index < 0) return false;

    if(strcasecmp($location, 'Garden') === 0) {
        $zone = &GetGarden($owner);
    } else if(strcasecmp($location, 'Alley') === 0) {
        $zone = &GetAlley($owner);
    } else {
        return false;
    }

    if($index >= count($zone)) return false;
    if(isset($zone[$index]->removed) && $zone[$index]->removed) return false;

    return true;
}

function IsPendingAttackStateValid() {
    $attackerPlayer = GetPendingAttackAttackerPlayer();
    if($attackerPlayer !== 1 && $attackerPlayer !== 2) return false;

    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    if(!is_string($attackerMZ) || $attackerMZ === '') return false;
    if(!is_string($targetMZ) || $targetMZ === '') return false;

    $defenderPlayer = ($attackerPlayer === 1) ? 2 : 1;

    // Pending attacks must keep the same attacker in Garden and a live Garden target.
    if(!PendingAttackRefExistsInExpectedZone($attackerPlayer, $attackerMZ, $attackerPlayer, 'Garden')) return false;
    if(!PendingAttackRefExistsInExpectedZone($attackerPlayer, $targetMZ, $defenderPlayer, 'Garden')) return false;

    return true;
}

function HasPendingAttackResponse() {
    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    $hasRawPending = is_string($attackerMZ) && $attackerMZ !== '' && is_string($targetMZ) && $targetMZ !== '';
    if(!$hasRawPending) return false;

    if(!IsPendingAttackStateValid()) {
        ClearAttackResponseWindow();
        return false;
    }

    return true;
}

function GetPendingAttackAttackerPlayer() {
    $raw = DecisionQueueController::GetVariable('PendingAttackAttackerPlayer');
    $player = intval($raw);
    return ($player === 1 || $player === 2) ? $player : 0;
}

function GetPendingAttackResponderPlayer() {
    $attacker = GetPendingAttackAttackerPlayer();
    if($attacker !== 1 && $attacker !== 2) return 0;
    return $attacker === 1 ? 2 : 1;
}

function BeginAttackResponseWindow($attackerPlayer, $attackerMZ, $targetMZ) {
    if(!is_string($attackerMZ) || $attackerMZ === '' || !is_string($targetMZ) || $targetMZ === '') return false;
    DecisionQueueController::StoreVariable('PendingAttackAttackerPlayer', strval(intval($attackerPlayer)));
    DecisionQueueController::StoreVariable('PendingAttackAttackerMZ', $attackerMZ);
    DecisionQueueController::StoreVariable('PendingAttackTargetMZ', $targetMZ);
    return true;
}

function ClearAttackResponseWindow() {
    DecisionQueueController::StoreVariable('PendingAttackAttackerPlayer', '');
    DecisionQueueController::StoreVariable('PendingAttackAttackerMZ', '');
    DecisionQueueController::StoreVariable('PendingAttackTargetMZ', '');
}

function ResolveAttackAfterResponses($responderPlayer) {
    if(!HasPendingAttackResponse()) return false;

    $attackerPlayer = GetPendingAttackAttackerPlayer();
    $expectedResponder = GetPendingAttackResponderPlayer();
    if($attackerPlayer === 0 || intval($responderPlayer) !== $expectedResponder) return false;

    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    if(!is_string($attackerMZ) || $attackerMZ === '' || !is_string($targetMZ) || $targetMZ === '') return false;

    if(!IsPendingAttackStateValid()) {
        ClearAttackResponseWindow();
        return true;
    }

    $attackResult = DoAttack($attackerPlayer, $attackerMZ, $targetMZ);
    if(!is_string($attackResult) || $attackResult === '') {
        ClearAttackResponseWindow();
        return true;
    }

    $dqController = new DecisionQueueController();
    $dqController->ExecuteStaticMethods($attackerPlayer, "-");
    ClearAttackResponseWindow();
    return true;
}

function TryRedirectPendingAttack($responderPlayer, $candidateMZ) {
    if(!HasPendingAttackResponse()) return false;
    if(!is_string($candidateMZ) || $candidateMZ === '') return false;

    $expectedResponder = GetPendingAttackResponderPlayer();
    if(intval($responderPlayer) !== intval($expectedResponder)) return false;

    $parts = explode('-', $candidateMZ);
    $zone = $parts[0] ?? '';
    $index = intval($parts[1] ?? -1);
    if($zone !== 'myGarden' || $index < 0) return false;

    $garden = &GetGarden(intval($responderPlayer));
    if($index >= count($garden)) return false;
    if(isset($garden[$index]->removed) && $garden[$index]->removed) return false;

    $candidateObj = &$garden[$index];
    if(CardType($candidateObj->CardID ?? '') !== 'ENTITY') return false;
    if(intval($candidateObj->Status ?? 2) !== 2) return false; // must be untapped so it can tap to redirect
    if(!IsDefenderEntity($candidateObj)) return false;

    $attackerPlayer = GetPendingAttackAttackerPlayer();
    if($attackerPlayer !== 1 && $attackerPlayer !== 2) return false;
    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    if(is_string($attackerMZ) && $attackerMZ !== '') {
        $attackerObj = GetZoneObject($attackerMZ);
        if($attackerObj !== null && !(isset($attackerObj->removed) && $attackerObj->removed)) {
            if(HasTurnEffect($attackerObj, 'INFILTRATE')) return false;
        }
    }

    // Pending target is stored from attacker's perspective.
    $redirectTarget = FlipZonePerspective($candidateMZ);
    if(!is_string($redirectTarget) || $redirectTarget === '') return false;

    $candidateObj->Status = 1; // tap as redirect cost
    DecisionQueueController::StoreVariable('PendingAttackTargetMZ', $redirectTarget);
    SetFlashMessage('Attack redirected to defending entity.');
    return true;
}

function TryKiraSwapForPendingAttack($responderPlayer, $kiraMZ) {
    if(!HasPendingAttackResponse()) return false;
    if(!is_string($kiraMZ) || $kiraMZ === '') return false;
    if(intval($responderPlayer) !== intval(GetPendingAttackResponderPlayer())) return false;

    $kiraParts = explode('-', $kiraMZ);
    $kiraZone = $kiraParts[0] ?? '';
    $kiraIndex = intval($kiraParts[1] ?? -1);
    if($kiraZone !== 'myAlley' || $kiraIndex < 0) return false;

    $alley = &GetAlley(intval($responderPlayer));
    if($kiraIndex >= count($alley)) return false;
    if(isset($alley[$kiraIndex]->removed) && $alley[$kiraIndex]->removed) return false;
    if(($alley[$kiraIndex]->CardID ?? '') !== 'S1-AZK01-034_Kira_E_C_die') return false;

    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    if(!is_string($targetMZ) || $targetMZ === '') return false;
    $defenderTargetMZ = FlipZonePerspective($targetMZ);
    if(!is_string($defenderTargetMZ) || $defenderTargetMZ === '') return false;

    $targetParts = explode('-', $defenderTargetMZ);
    $targetZone = $targetParts[0] ?? '';
    $targetIndex = intval($targetParts[1] ?? -1);
    if($targetZone !== 'myGarden' || $targetIndex < 0) return false;

    $garden = &GetGarden(intval($responderPlayer));
    if($targetIndex >= count($garden)) return false;
    if(isset($garden[$targetIndex]->removed) && $garden[$targetIndex]->removed) return false;
    if(CardType($garden[$targetIndex]->CardID ?? '') !== 'ENTITY') return false;

    $gardenObj = $garden[$targetIndex];
    $alleyObj = $alley[$kiraIndex];

    $garden[$targetIndex] = $alleyObj;
    $alley[$kiraIndex] = $gardenObj;

    $garden[$targetIndex]->Location = 'Garden';
    $garden[$targetIndex]->mzIndex = $targetIndex;
    $garden[$targetIndex]->BuildIndex();
    $alley[$kiraIndex]->Location = 'Alley';
    $alley[$kiraIndex]->mzIndex = $kiraIndex;
    $alley[$kiraIndex]->BuildIndex();

    DecisionQueueController::StoreVariable('PendingAttackTargetMZ', FlipZonePerspective('myGarden-' . $targetIndex));
    SetFlashMessage('Kira swapped in and became the new attack target.');
    return true;
}

function SwapMyGardenAndAlleyEntities($player, $gardenMZ, $alleyMZ, $retargetMessage = 'Swapped entities.') {
    $player = intval($player);
    if(!is_string($gardenMZ) || !is_string($alleyMZ)) return false;

    $gardenParts = explode('-', $gardenMZ);
    $alleyParts = explode('-', $alleyMZ);
    $gardenZone = $gardenParts[0] ?? '';
    $alleyZone = $alleyParts[0] ?? '';
    $gardenIndex = intval($gardenParts[1] ?? -1);
    $alleyIndex = intval($alleyParts[1] ?? -1);
    if($gardenZone !== 'myGarden' || $alleyZone !== 'myAlley') return false;
    if($gardenIndex < 0 || $alleyIndex < 0) return false;

    $garden = &GetGarden($player);
    $alley = &GetAlley($player);
    if($gardenIndex >= count($garden) || $alleyIndex >= count($alley)) return false;
    if(isset($garden[$gardenIndex]->removed) && $garden[$gardenIndex]->removed) return false;
    if(isset($alley[$alleyIndex]->removed) && $alley[$alleyIndex]->removed) return false;
    if(CardType($garden[$gardenIndex]->CardID ?? '') !== 'ENTITY') return false;
    if(CardType($alley[$alleyIndex]->CardID ?? '') !== 'ENTITY') return false;

    $oldGardenObj = $garden[$gardenIndex];
    $oldAlleyObj = $alley[$alleyIndex];
    $garden[$gardenIndex] = $oldAlleyObj;
    $alley[$alleyIndex] = $oldGardenObj;

    $garden[$gardenIndex]->Location = 'Garden';
    $garden[$gardenIndex]->mzIndex = $gardenIndex;
    $garden[$gardenIndex]->BuildIndex();
    $alley[$alleyIndex]->Location = 'Alley';
    $alley[$alleyIndex]->mzIndex = $alleyIndex;
    $alley[$alleyIndex]->BuildIndex();

    if(HasPendingAttackResponse()) {
        $pendingTargetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
        if(is_string($pendingTargetMZ) && $pendingTargetMZ !== '') {
            $defenderTargetMZ = FlipZonePerspective($pendingTargetMZ);
            if($defenderTargetMZ === $gardenMZ) {
                DecisionQueueController::StoreVariable('PendingAttackTargetMZ', FlipZonePerspective('myGarden-' . $gardenIndex));
                SetFlashMessage($retargetMessage);
                return true;
            }
        }
    }

    SetFlashMessage('Swapped Garden and Alley entities.');
    return true;
}

function OfferKiraSwapOnAttack($player) {
    if(!HasPendingAttackResponse()) return;
    if(intval($player) !== intval(GetPendingAttackResponderPlayer())) return;

    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    if(!is_string($targetMZ) || $targetMZ === '') return;
    $defenderTargetMZ = FlipZonePerspective($targetMZ);
    if(!is_string($defenderTargetMZ) || $defenderTargetMZ === '') return;

    $targetObj = GetZoneObject($defenderTargetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    if(($targetObj->Location ?? '') !== 'Garden') return;
    if(CardType($targetObj->CardID ?? '') !== 'ENTITY') return;

    $alley = &GetAlley(intval($player));
    $kiraChoices = [];
    for($i = 0; $i < count($alley); ++$i) {
        if(isset($alley[$i]->removed) && $alley[$i]->removed) continue;
        if(($alley[$i]->CardID ?? '') !== 'S1-AZK01-034_Kira_E_C_die') continue;
        $kiraChoices[] = 'myAlley-' . $i;
    }
    if(empty($kiraChoices)) return;

    $choiceStr = implode('&', $kiraChoices);
    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', $choiceStr, 1, 'Choose_a_Kira_to_swap_with_the_attacked_entity');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'KIRA_SWAP_PENDING_ATTACK', 1);
}

function GetMacroCardIDCandidates($cardID) {
    $candidates = [];
    if(!is_string($cardID) || $cardID === '') return $candidates;

    $candidates[] = $cardID;

    // Some generated macro keys omit only the trailing variant token (e.g. "..._die" -> "..._").
    $trimmedTrailingToken = preg_replace('/[^_]*$/', '', $cardID);
    if(is_string($trimmedTrailingToken) && $trimmedTrailingToken !== '' && !in_array($trimmedTrailingToken, $candidates, true)) {
        $candidates[] = $trimmedTrailingToken;
    }

    // Also accept keys without the common "_die" suffix.
    $trimmedDieSuffix = preg_replace('/_die$/i', '', $cardID);
    if(is_string($trimmedDieSuffix) && $trimmedDieSuffix !== '' && !in_array($trimmedDieSuffix, $candidates, true)) {
        $candidates[] = $trimmedDieSuffix;
    }

    return $candidates;
}

function GetAttachedWeaponIDs($obj) {
    if(!is_object($obj) || !isset($obj->Subcards) || !is_array($obj->Subcards)) return [];

    $weapons = [];
    foreach($obj->Subcards as $subcardID) {
        if(!is_string($subcardID) || $subcardID === '') continue;
        if(CardType($subcardID) !== 'WEAPON') continue;
        $weapons[] = $subcardID;
    }
    return $weapons;
}

function GetSubcardTurnEffects($obj, $subcardIndex) {
    if(!is_object($obj) || intval($subcardIndex) < 0) return [];
    if(!isset($obj->Counters) || !is_array($obj->Counters)) return [];

    $map = $obj->Counters['_subcardTurnEffects'] ?? null;
    if(!is_array($map)) return [];

    $key = strval(intval($subcardIndex));
    $effects = $map[$key] ?? null;
    return is_array($effects) ? $effects : [];
}

function AddSubcardTurnEffect(&$obj, $subcardIndex, $effectID) {
    if(!is_object($obj) || intval($subcardIndex) < 0 || !is_string($effectID) || $effectID === '') return;
    if(!isset($obj->Counters) || !is_array($obj->Counters)) {
        $obj->Counters = [];
    }
    if(!isset($obj->Counters['_subcardTurnEffects']) || !is_array($obj->Counters['_subcardTurnEffects'])) {
        $obj->Counters['_subcardTurnEffects'] = [];
    }

    $key = strval(intval($subcardIndex));
    if(!isset($obj->Counters['_subcardTurnEffects'][$key]) || !is_array($obj->Counters['_subcardTurnEffects'][$key])) {
        $obj->Counters['_subcardTurnEffects'][$key] = [];
    }

    $obj->Counters['_subcardTurnEffects'][$key][] = $effectID;
}

function CountWeaponSubcardTurnEffects($obj, $weaponCardID, $effectID) {
    if(!is_object($obj) || !isset($obj->Subcards) || !is_array($obj->Subcards)) return 0;

    $count = 0;
    for($i = 0; $i < count($obj->Subcards); ++$i) {
        $subcardID = $obj->Subcards[$i] ?? '';
        if(!is_string($subcardID) || $subcardID !== $weaponCardID) continue;
        $effects = GetSubcardTurnEffects($obj, $i);
        foreach($effects as $effect) {
            if($effect === $effectID) ++$count;
        }
    }
    return $count;
}

function AddSubcardTurnEffectByCardID(&$obj, $weaponCardID, $effectID) {
    if(!is_object($obj) || !isset($obj->Subcards) || !is_array($obj->Subcards)) return false;

    for($i = count($obj->Subcards) - 1; $i >= 0; --$i) {
        $subcardID = $obj->Subcards[$i] ?? '';
        if(!is_string($subcardID) || $subcardID !== $weaponCardID) continue;
        AddSubcardTurnEffect($obj, $i, $effectID);
        return true;
    }

    return false;
}

function ApplyBlackJadeDaggerOnPlayBonus($player) {
    $targetKey = 'P' . intval($player) . '_BlackJadeDaggerTargetMZ';
    $targetMZ = DecisionQueueController::GetVariable($targetKey);
    if(!is_string($targetMZ) || $targetMZ === '') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;

    AddSubcardTurnEffectByCardID($targetObj, 'S1-STT01-013_Black-Jade-Dagger_W_C_die', 'BLACK_JADE_DAGGER_BONUS');
}

function HasEquippedWeapon($obj) {
    return !empty(GetAttachedWeaponIDs($obj));
}

function CountCardsOfTypeInDiscard($player, $cardType) {
    if(!is_string($cardType) || $cardType === '') return 0;

    $discard = &GetDiscard($player);
    $count = 0;
    for($i = 0; $i < count($discard); ++$i) {
        if(isset($discard[$i]->removed) && $discard[$i]->removed) continue;
        if(CardType($discard[$i]->CardID ?? '') !== $cardType) continue;
        ++$count;
    }

    return $count;
}

function CardHasSubtype($cardID, $subtype) {
    if(!is_string($cardID) || $cardID === '' || !is_string($subtype) || $subtype === '') return false;
    $subtypes = CardSubtypes($cardID);
    if(!is_array($subtypes)) return false;
    foreach($subtypes as $candidate) {
        if(is_string($candidate) && strcasecmp($candidate, $subtype) === 0) return true;
    }
    return false;
}

function CardHasSubtypeInZone($cardID, $subtype, $zoneName = '') {
    if(!is_string($cardID) || $cardID === '' || !is_string($subtype) || $subtype === '') return false;
    if(($zoneName === 'myDeck' || $zoneName === 'theirDeck')
        && $cardID === 'S1-AZK01-081_Gurugumi-Mentor_E_C_die') {
        return true;
    }
    return CardHasSubtype($cardID, $subtype);
}

function HasTurnEffect($obj, $effectID) {
    if(!is_object($obj) || !is_string($effectID) || $effectID === '') return false;
    if(!isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return false;
    return in_array($effectID, $obj->TurnEffects, true);
}

function GetTurnEffectValue($obj, $prefix) {
    if(!is_object($obj) || !is_string($prefix) || $prefix === '') return null;
    if(!isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return null;
    foreach($obj->TurnEffects as $effect) {
        if(!is_string($effect)) continue;
        if(strpos($effect, $prefix) !== 0) continue;
        return substr($effect, strlen($prefix));
    }
    return null;
}

function GetCopiedTextCardID($obj) {
    $copiedCardID = GetTurnEffectValue($obj, 'COPY_TEXT:');
    if(!is_string($copiedCardID) || $copiedCardID === '') return null;
    return $copiedCardID;
}

function GetObjectMacroCardIDCandidates($obj) {
    $candidates = [];
    if(!is_object($obj) || !isset($obj->CardID)) return $candidates;

    foreach(GetMacroCardIDCandidates($obj->CardID ?? '') as $candidate) {
        if(!in_array($candidate, $candidates, true)) $candidates[] = $candidate;
    }

    $copiedCardID = GetCopiedTextCardID($obj);
    if(is_string($copiedCardID) && $copiedCardID !== '') {
        foreach(GetMacroCardIDCandidates($copiedCardID) as $candidate) {
            if(!in_array($candidate, $candidates, true)) $candidates[] = $candidate;
        }
    }

    return $candidates;
}

function ObjectHasEffectiveCardIDCandidate($obj, $cardID) {
    if(!is_object($obj) || !is_string($cardID) || $cardID === '') return false;
    return in_array($cardID, GetObjectMacroCardIDCandidates($obj), true);
}

function ObjectHasTimingTag($obj, $tagName) {
    if(!is_object($obj) || !is_string($tagName) || $tagName === '') return false;
    foreach(GetObjectMacroCardIDCandidates($obj) as $candidateCardID) {
        if(CardHasTimingTag($candidateCardID, $tagName)) return true;
    }
    return false;
}

function IsCardEarthEntity($cardID) {
    return CardType($cardID) === 'ENTITY' && CardElement($cardID) === 'Earth';
}

function IsGodmodeEntity($obj) {
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return false;
    $cardID = $obj->CardID ?? '';
    if(CardType($cardID) !== 'ENTITY') return false;
    return CardHasKeyword($cardID, 'Godmode');
}

function IsDefenderEntity($obj) {
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return false;
    $cardID = $obj->CardID ?? '';
    if(CardType($cardID) !== 'ENTITY') return false;
    if(HasTurnEffect($obj, 'STONEHAVEN_DEFENDER')) return true;
    if(HasTurnEffect($obj, 'DEFENDER')) return true;
    if(CardHasKeyword($cardID, 'Defender')) return true;
    if($cardID === 'S1-AZK01-052_Yojin_E_UC_die') {
        $owner = ResolveObjectOwner($obj);
        if($owner === null || intval($owner) <= 0) $owner = GetTurnPlayer();
        $myGarden = &GetGarden(intval($owner));
        $opp = intval($owner) === 1 ? 2 : 1;
        $theirGarden = &GetGarden($opp);
        if(CountActiveEntities($myGarden, true) < CountActiveEntities($theirGarden, true)) {
            return true;
        }
    }
    if($cardID === 'S1-STT03-004_Sloth-Scarecrow_E_C_die') return true;
    if($cardID === 'S1-STT03-009_Warding-Totem_E_UC_die') return true;
    return false;
}

function IsImmuneToCardEffectDamage($obj) {
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return false;
    if(HasTurnEffect($obj, 'EFFECT_DAMAGE_IMMUNE')) return true;
    $cardID = $obj->CardID ?? '';
    if($cardID === 'S1-STT02-006_Foamback-Crab_E_C_die') return true;
    if($cardID === 'S1-STT02-008_Serene-Fist-Misaki_E_UC_die') return true;
    if($cardID === 'S1-AZK01-025_Lighthouse-Keeper_E_UC_die') return true;
    return false;
}

function IsTauntEntity($obj) {
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return false;
    $cardID = $obj->CardID ?? '';
    return $cardID === 'S1-STT03-013_Stone-Masked-Ancient_E_SR_die'
        || $cardID === 'S1-STT03-013A_Stone-Masked-Ancient_E_SR_die';
}

function AddUniqueTurnEffect(&$obj, $effectID) {
    if(!is_object($obj) || !is_string($effectID) || $effectID === '') return;
    if(!isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) {
        $obj->TurnEffects = [];
    }
    if(!in_array($effectID, $obj->TurnEffects, true)) {
        $obj->TurnEffects[] = $effectID;
    }
}

function RemoveTurnEffect(&$obj, $effectID) {
    if(!is_object($obj) || !is_string($effectID) || $effectID === '') return;
    if(!isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return;

    $obj->TurnEffects = array_values(array_filter(
        $obj->TurnEffects,
        function($effect) use ($effectID) {
            return $effect !== $effectID;
        }
    ));
}

function FilterZoneTurnEffects(&$zone, $keepUntilBeginTurnEffects, $keepUntilBeginOpponentTurnEffects) {
    global $untilBeginTurnEffects, $untilBeginOpponentTurnEffects;
    foreach($zone as &$obj) {
        if($obj->removed || !isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) continue;
        $newEffects = [];
        foreach($obj->TurnEffects as $effect) {
            if(isset($untilBeginTurnEffects[$effect])) {
                if($keepUntilBeginTurnEffects) $newEffects[] = $effect;
                continue;
            }
            if(isset($untilBeginOpponentTurnEffects[$effect])) {
                if($keepUntilBeginOpponentTurnEffects) $newEffects[] = $effect;
                continue;
            }
        }
        $obj->TurnEffects = $newEffects;
    }
}

function NormalizeDamageSourceKey($sourceKey) {
    if(!is_string($sourceKey) || $sourceKey === '') return '';
    return preg_replace('/[^A-Za-z0-9:_-]/', '_', $sourceKey);
}

function ResolveDamageSourceKey($player, $explicitSourceKey = null) {
    if(is_string($explicitSourceKey) && $explicitSourceKey !== '') {
        return NormalizeDamageSourceKey($explicitSourceKey);
    }

    $sourceMZ = DecisionQueueController::GetVariable('mzID');
    if(is_string($sourceMZ) && $sourceMZ !== '') {
        return NormalizeDamageSourceKey($sourceMZ);
    }

    return 'P' . intval($player) . '_GENERIC';
}

function RecordDamageSourceOnObject(&$obj, $sourceKey) {
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return;
    $normalized = NormalizeDamageSourceKey($sourceKey);
    if($normalized === '') return;
    AddUniqueTurnEffect($obj, 'DMG_SRC:' . $normalized);
}

function CountDamageSourcesOnObject($obj) {
    if(!is_object($obj) || !isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return 0;
    $count = 0;
    foreach($obj->TurnEffects as $effectID) {
        if(!is_string($effectID)) continue;
        if(strpos($effectID, 'DMG_SRC:') !== 0) continue;
        ++$count;
    }
    return $count;
}

function ParseAttackModifierEffects($obj) {
    if(!is_object($obj) || !isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return 0;
    $delta = 0;
    foreach($obj->TurnEffects as $effectID) {
        if(!is_string($effectID)) continue;
        if(strpos($effectID, 'ATK_MOD:') !== 0) continue;
        $amount = intval(substr($effectID, strlen('ATK_MOD:')));
        $delta += $amount;
    }
    return $delta;
}

function ParseHealthModifierEffects($obj) {
    if(!is_object($obj) || !isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return 0;
    $delta = 0;
    foreach($obj->TurnEffects as $effectID) {
        if(!is_string($effectID)) continue;
        if(strpos($effectID, 'HP_MOD:') !== 0) continue;
        $amount = intval(substr($effectID, strlen('HP_MOD:')));
        $delta += $amount;
    }
    return $delta;
}

function ParseModifierResult($result) {
    $parsed = [
        'delta' => 0,
        'consume' => false,
        'applied' => false,
    ];
    if(is_array($result)) {
        $parsed['delta'] = intval($result['delta'] ?? 0);
        $parsed['consume'] = !empty($result['consume']);
        $parsed['applied'] = array_key_exists('applied', $result)
            ? !empty($result['applied'])
            : ($parsed['delta'] !== 0);
        return $parsed;
    }
    $parsed['delta'] = intval($result);
    $parsed['applied'] = ($parsed['delta'] !== 0);
    return $parsed;
}

function ConsumeModifierSource($sourceObj) {
    if($sourceObj === null) return false;
    if(($sourceObj->_sourceZone ?? null) === "GlobalEffects") {
        $controller = intval($sourceObj->Controller ?? 0);
        if($controller < 1) return false;
        return RemoveGlobalEffect($controller, $sourceObj->CardID ?? '');
    }
    return false;
}

function ApplyGeneratedAttackModifiers($player, $subjectObj, $currentValue) {
    if(!is_object($subjectObj) || !function_exists('EvaluateAttackModifier')) return $currentValue;
    $subjectCardIDs = GetObjectMacroCardIDCandidates($subjectObj);
    if(empty($subjectCardIDs)) return $currentValue;

    foreach($subjectCardIDs as $subjectCardID) {
        $currentValue += EvaluateAttackModifier($subjectCardID, $player, $subjectObj, $currentValue, $subjectObj);
    }

    foreach([1, 2] as $fieldPlayer) {
        foreach([GetGarden($fieldPlayer), GetAlley($fieldPlayer)] as $fieldZone) {
            foreach($fieldZone as $fieldObj) {
                if($fieldObj === null || !empty($fieldObj->removed) || $fieldObj === $subjectObj) continue;
                foreach(GetObjectMacroCardIDCandidates($fieldObj) as $fieldCardID) {
                    $currentValue += EvaluateAttackModifier($fieldCardID, $player, $subjectObj, $currentValue, $fieldObj);
                }
            }
        }
    }

    foreach([1, 2] as $effectPlayer) {
        foreach(GetGlobalEffects($effectPlayer) as $effectObj) {
            if($effectObj === null || !empty($effectObj->removed)) continue;
            $effectCardID = $effectObj->CardID ?? '';
            if(!is_string($effectCardID) || $effectCardID === '') continue;
            $effectSource = clone $effectObj;
            $effectSource->Controller = $effectPlayer;
            $effectSource->_sourceZone = "GlobalEffects";
            $currentValue += EvaluateAttackModifier($effectCardID, $player, $subjectObj, $currentValue, $effectSource);
        }
    }

    return max(0, $currentValue);
}

function ApplyGeneratedHealthModifiers($player, $subjectObj, $currentValue) {
    if(!is_object($subjectObj) || !function_exists('EvaluateHealthModifier')) return $currentValue;
    $subjectCardIDs = GetObjectMacroCardIDCandidates($subjectObj);
    if(empty($subjectCardIDs)) return $currentValue;

    foreach($subjectCardIDs as $subjectCardID) {
        $currentValue += EvaluateHealthModifier($subjectCardID, $player, $subjectObj, $currentValue, $subjectObj);
    }

    foreach([1, 2] as $fieldPlayer) {
        foreach([GetGarden($fieldPlayer), GetAlley($fieldPlayer)] as $fieldZone) {
            foreach($fieldZone as $fieldObj) {
                if($fieldObj === null || !empty($fieldObj->removed) || $fieldObj === $subjectObj) continue;
                foreach(GetObjectMacroCardIDCandidates($fieldObj) as $fieldCardID) {
                    $currentValue += EvaluateHealthModifier($fieldCardID, $player, $subjectObj, $currentValue, $fieldObj);
                }
            }
        }
    }

    foreach([1, 2] as $effectPlayer) {
        foreach(GetGlobalEffects($effectPlayer) as $effectObj) {
            if($effectObj === null || !empty($effectObj->removed)) continue;
            $effectCardID = $effectObj->CardID ?? '';
            if(!is_string($effectCardID) || $effectCardID === '') continue;
            $effectSource = clone $effectObj;
            $effectSource->Controller = $effectPlayer;
            $effectSource->_sourceZone = "GlobalEffects";
            $currentValue += EvaluateHealthModifier($effectCardID, $player, $subjectObj, $currentValue, $effectSource);
        }
    }

    return max(0, $currentValue);
}

function EquippedWeaponAttackBonus($obj, $ownerPlayer = null) {
    $bonus = 0;
    $weaponIDs = GetAttachedWeaponIDs($obj);
    foreach($weaponIDs as $weaponID) {
        $bonus += max(0, intval(CardAttack($weaponID)));
    }

    // Tenraku grants +1 additional attack when its controller has 15+ cards in discard.
    $tenrakuID = 'S1-STT01-015_Tenraku_W_UC_die';
    if($ownerPlayer !== null && intval($ownerPlayer) > 0 && CountCardsOfTypeInDiscard(intval($ownerPlayer), 'WEAPON') >= 15) {
        $tenrakuCount = 0;
        foreach($weaponIDs as $weaponID) {
            if($weaponID === $tenrakuID) ++$tenrakuCount;
        }
        $bonus += $tenrakuCount;
    }

    // Black Jade Dagger can grant an additional +1 attack when its On Play cost is paid.
    $blackJadeDaggerID = 'S1-STT01-013_Black-Jade-Dagger_W_C_die';
    $blackJadeBoostEffect = 'BLACK_JADE_DAGGER_BONUS';
    $bonus += CountWeaponSubcardTurnEffects($obj, $blackJadeDaggerID, $blackJadeBoostEffect);

    return $bonus;
}

function EntityCardAttackBonus($player, $obj) {
    if(!is_object($obj)) return 0;

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    if(empty($cardIDCandidates)) return 0;

    $bonus = 0;

    foreach($cardIDCandidates as $cardID) {
        // Black Jade Crewleader gets +1 attack while equipped with a weapon.
        if($cardID === 'S1-STT01-008_Black-Jade-Crewleader_E_UC_die' && HasEquippedWeapon($obj)) {
            $bonus += 1;
        }

        // Mastersmith Yamada gets +2 attack with 6+ weapons in discard.
        if($cardID === 'S1-STT01-009_Mastersmith-Yamada_E_UC_die' && CountCardsOfTypeInDiscard($player, 'WEAPON') >= 6) {
            $bonus += 2;
        }

        if($cardID === 'S1-STT02-012_Young-Shao_E_UC_die') {
            $myGarden = &GetGarden($player);
            $opp = intval($player) === 1 ? 2 : 1;
            $theirGarden = &GetGarden($opp);
            if(CountActiveEntities($myGarden, true) >= CountActiveEntities($theirGarden, true) + 2) {
                $bonus += 1;
            }
        }

        if($cardID === 'S1-AZK01-073_Top-Beanz_E_C_die') {
            $myGarden = &GetGarden($player);
            $allBeanz = true;
            $hasOtherEntity = false;
            for($i = 0; $i < count($myGarden); ++$i) {
                if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
                $candidateID = $myGarden[$i]->CardID ?? '';
                if(CardType($candidateID) !== 'ENTITY') continue;
                $hasOtherEntity = true;
                if(!CardHasSubtype($candidateID, 'Beanz')) {
                    $allBeanz = false;
                    break;
                }
            }
            if($hasOtherEntity && $allBeanz) {
                $bonus += 1;
            }
        }
    }

    return $bonus;
}

function ResolveEntityAttackValue($player, $obj) {
    if(!is_object($obj)) return 0;
    $base = max(0, intval(CardAttack($obj->CardID ?? '')));
    $value = $base + EquippedWeaponAttackBonus($obj, $player) + EntityCardAttackBonus($player, $obj) + ParseAttackModifierEffects($obj);
    $value = ApplyGeneratedAttackModifiers($player, $obj, $value);
    return max(0, $value);
}

function ResolveEntityHealthValue($player, $obj) {
    if(!is_object($obj)) return 0;
    $base = max(0, intval(CardHealth($obj->CardID ?? '')));

    foreach(GetObjectMacroCardIDCandidates($obj) as $candidateCardID) {
        if($candidateCardID === 'S1-STT02-012_Young-Shao_E_UC_die') {
            $myGarden = &GetGarden($player);
            $opp = intval($player) === 1 ? 2 : 1;
            $theirGarden = &GetGarden($opp);
            if(CountActiveEntities($myGarden, true) >= CountActiveEntities($theirGarden, true) + 2) {
                $base += 1;
            }
        }

        if($candidateCardID === 'S1-AZK01-073_Top-Beanz_E_C_die') {
            $myGarden = &GetGarden($player);
            $allBeanz = true;
            $hasOtherEntity = false;
            for($i = 0; $i < count($myGarden); ++$i) {
                if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
                $candidateID = $myGarden[$i]->CardID ?? '';
                if(CardType($candidateID) !== 'ENTITY') continue;
                $hasOtherEntity = true;
                if(!CardHasSubtype($candidateID, 'Beanz')) {
                    $allBeanz = false;
                    break;
                }
            }
            if($hasOtherEntity && $allBeanz) {
                $base += 1;
            }
        }
    }

    if(!ObjectHasEffectiveCardIDCandidate($obj, 'S1-AZK01-053_Geodust-Smuggler_E_R_die')) {
        $myGarden = &GetGarden($player);
        foreach($myGarden as $ally) {
            if(!is_object($ally) || (isset($ally->removed) && $ally->removed)) continue;
            if(!ObjectHasEffectiveCardIDCandidate($ally, 'S1-AZK01-053_Geodust-Smuggler_E_R_die')) continue;
            $base += 1;
            break;
        }
    }
    $base += ParseHealthModifierEffects($obj);
    $base = ApplyGeneratedHealthModifiers($player, $obj, $base);
    return max(0, $base);
}

function TriggerEquippedWeaponOnAttack($player, $attackerMZ) {
    $attackerObj = &GetZoneObject($attackerMZ);
    if($attackerObj === null || (isset($attackerObj->removed) && $attackerObj->removed)) return;
    if(!isset($attackerObj->Subcards) || !is_array($attackerObj->Subcards)) return;

    $opponent = ($player == 1) ? 2 : 1;
    $hasRaizanSubtype = CardHasSubtype($attackerObj->CardID ?? '', 'Raizan');

    foreach($attackerObj->Subcards as $weaponID) {
        if(!is_string($weaponID) || $weaponID === '') continue;

        if($weaponID === 'S1-STT01-012_Lightning-Shuriken_W_C_die') {
            $deck = &GetDeck($player);
            if(!empty($deck)) {
                $milled = array_shift($deck);
                AddDiscard($player, CardID:$milled->CardID);
            }
        }

        if($weaponID === 'S1-STT01-016_Ikazuchi_W_SR_die' && $hasRaizanSubtype) {
            $theirGarden = &GetGarden($opponent);
            for($i = count($theirGarden) - 1; $i >= 0; --$i) {
                if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
                if(CardType($theirGarden[$i]->CardID ?? '') === 'LEADER') continue;
                DealDamageToGardenTarget($player, 'theirGarden-' . $i, 1);
            }
        }
    }
}

function TriggerEquippedWeaponOnCombatDamage($player, &$attackerObj, $targetZone, $targetIndex, $damageDealt) {
    $damageDealt = intval($damageDealt);
    if($damageDealt <= 0) return;
    if(!is_object($attackerObj) || (isset($attackerObj->removed) && $attackerObj->removed)) return;
    if($targetZone !== 'theirGarden' && $targetZone !== 'theirAlley') return;

    $opponent = ($player == 1) ? 2 : 1;
    $targetField = ($targetZone === 'theirGarden') ? GetGarden($opponent) : GetAlley($opponent);
    if($targetIndex < 0 || $targetIndex >= count($targetField)) return;
    if(isset($targetField[$targetIndex]->removed) && $targetField[$targetIndex]->removed) return;

    foreach(GetAttachedWeaponIDs($attackerObj) as $weaponID) {
        if($weaponID !== 'S1-AZK01-044_Lightning-Kanabo_W_R_die') continue;
        if(CountWeaponSubcardTurnEffects($attackerObj, $weaponID, 'LIGHTNING_KANABO_USED') > 0) continue;
        AddUniqueTurnEffect($targetField[$targetIndex], 'SHOCKED');
        AddSubcardTurnEffectByCardID($attackerObj, $weaponID, 'LIGHTNING_KANABO_USED');
    }
}

function DiscardEquippedWeaponsFromObject($owner, $obj) {
    if(!is_object($obj)) return;
    if(!isset($obj->Subcards) || !is_array($obj->Subcards)) return;

    $remaining = [];
    foreach($obj->Subcards as $subcardID) {
        if(!is_string($subcardID) || $subcardID === '') continue;
        if(CardType($subcardID) === 'WEAPON') {
            AddDiscard($owner, CardID:$subcardID);
            continue;
        }
        $remaining[] = $subcardID;
    }
    $obj->Subcards = $remaining;
}

function HandleFieldCardBeforeLeaving($player, $mzIndex, $toZone) {
    global $playerID;
    if(!is_string($mzIndex) || $mzIndex === '') return;
    if(!is_string($toZone) || $toZone === '') return;

    if(intval($player) !== intval($playerID)) {
        $mzIndex = FlipZonePerspective($mzIndex);
        $toZone = FlipZonePerspective($toZone);
    }

    $parts = explode('-', $mzIndex);
    $sourceZone = $parts[0] ?? '';
    if(!IsFieldZoneName($sourceZone)) return;
    if(IsFieldZoneName($toZone)) return;

    $obj = &GetZoneObject($mzIndex);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return;

    if(IsGodmodeEntity($obj)) return;

    $owner = ResolveObjectOwner($obj);
    if($owner === null || intval($owner) <= 0) {
        $owner = ResolveOwnerFromPerspectiveZone($player, $sourceZone);
    }

    $cardID = $obj->CardID ?? '';
    $destroyedCardVar = 'P' . intval($owner) . '_LastDestroyedCardID';
    if(is_string($cardID) && $cardID !== '') {
        DecisionQueueController::StoreVariable($destroyedCardVar, $cardID);
    }

    DiscardEquippedWeaponsFromObject(intval($owner), $obj);

    if($toZone !== 'myDiscard' && $toZone !== 'theirDiscard') return;

    if(is_string($cardID) && $cardID !== '') {
        $leaveReasonVar = 'P' . intval($owner) . '_LeaveFieldReason';
        $leaveReason = strval(DecisionQueueController::GetVariable($leaveReasonVar) ?? '');
        $resolvedCardID = DecisionQueueController::GetVariable($destroyedCardVar);
        if(!is_string($resolvedCardID) || $resolvedCardID === '') {
            $resolvedCardID = $cardID;
        }
        if($leaveReason === 'SACRIFICE') {
            WhenSacrificed(intval($owner), $resolvedCardID);
        }
        else {
            WhenDestroyed(intval($owner), $resolvedCardID);
        }
    }

    if(IsCardEarthEntity($cardID)) {
        $bobuWardVar = 'P' . intval($owner) . '_BobuWardActive';
        if(DecisionQueueController::GetVariable($bobuWardVar) === '1') {
            DecisionQueueController::StoreVariable($bobuWardVar, '0');
            DecisionQueueController::AddDecision(intval($owner), 'YESNO', '-', 1, 'Bobu:_Heal_1_to_your_leader?');
            DecisionQueueController::AddDecision(intval($owner), 'CUSTOM', 'BOBU_WARD_HEAL', 1);
        }
    }
}

function SafeMZMove($player, $mzIndex, $toZone) {
    $shouldCheckSelis = false;
    $movingToOwner = 0;
    $movingCardIsEntity = false;
    if(is_string($mzIndex) && is_string($toZone) && ($toZone === 'myHand' || $toZone === 'theirHand')) {
        $obj = GetZoneObject($mzIndex);
        if($obj !== null && !(isset($obj->removed) && $obj->removed)) {
            $location = strval($obj->Location ?? '');
            if($location === 'Garden' || $location === 'Alley') {
                $movingCardIsEntity = (CardType($obj->CardID ?? '') === 'ENTITY');
                $movingToOwner = ResolveObjectOwner($obj);
                if(intval($movingToOwner) > 0 && $movingCardIsEntity) {
                    $shouldCheckSelis = true;
                }
            }
        }
    }

    $fieldObj = GetZoneObject($mzIndex);
    if(is_string($mzIndex) && is_string($toZone) && $fieldObj !== null && !(isset($fieldObj->removed) && $fieldObj->removed)) {
        $sourceZone = strval($fieldObj->Location ?? '');
        if(IsFieldZoneName($sourceZone) && !IsFieldZoneName($toZone) && IsGodmodeEntity($fieldObj)) {
            return false;
        }
    }

    HandleFieldCardBeforeLeaving($player, $mzIndex, $toZone);
    $result = MZMove($player, $mzIndex, $toZone);

    if($shouldCheckSelis) {
        for($selisOwner = 1; $selisOwner <= 2; ++$selisOwner) {
            $garden = &GetGarden($selisOwner);
            for($i = 0; $i < count($garden); ++$i) {
                $selis = &$garden[$i];
                if($selis === null || (isset($selis->removed) && $selis->removed)) continue;
                if(($selis->CardID ?? '') !== 'S1-STT02-010_Selis-of-the-Shore_E_R_die') continue;
                if(intval($selis->Status ?? 2) === 1) continue;
                $selis->Status = 1;
                DoDrawCard($selisOwner, 1);
                break;
            }
        }
    }

    return $result;
}

function SacrificeCards($player, $mzCards, $toZone = 'myDiscard') {
    if(is_string($mzCards)) {
        if($mzCards === '') return 0;
        $mzCards = [$mzCards];
    }
    if(!is_array($mzCards) || empty($mzCards)) return 0;

    $sacrificedCount = 0;
    $leaveReasonVars = [];
    $orderedCards = array_values(array_filter($mzCards, function($mzID) {
        return is_string($mzID) && $mzID !== '';
    }));
    usort($orderedCards, function($a, $b) {
        $aParts = explode('-', $a);
        $bParts = explode('-', $b);
        return intval($bParts[1] ?? -1) <=> intval($aParts[1] ?? -1);
    });

    for($i = 0; $i < count($orderedCards); ++$i) {
        $obj = GetZoneObject($orderedCards[$i]);
        if($obj === null || (isset($obj->removed) && $obj->removed)) continue;
        $owner = ResolveObjectOwner($obj);
        if($owner === null || intval($owner) <= 0) {
            $parts = explode('-', $orderedCards[$i]);
            $owner = ResolveOwnerFromPerspectiveZone($player, $parts[0] ?? '');
        }
        $owner = intval($owner);
        if($owner <= 0) continue;
        $leaveReasonVar = 'P' . $owner . '_LeaveFieldReason';
        $leaveReasonVars[$leaveReasonVar] = strval(DecisionQueueController::GetVariable($leaveReasonVar) ?? '');
        DecisionQueueController::StoreVariable($leaveReasonVar, 'SACRIFICE');
        if(SafeMZMove($player, $orderedCards[$i], $toZone)) {
            ++$sacrificedCount;
        }
        DecisionQueueController::StoreVariable($leaveReasonVar, $leaveReasonVars[$leaveReasonVar]);
    }

    if($sacrificedCount > 0) {
        DecisionQueueController::CleanupRemovedCards();
    }

    return $sacrificedCount;
}

function ResolveWeaponEquipTargets($player) {
    $targets = [];
    $garden = &GetGarden($player);
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        $targets[] = 'myGarden-' . $i;
    }
    return $targets;
}

function AttachWeaponCardIDToTarget($targetObj, $weaponCardID) {
    if(!is_object($targetObj) || !is_string($weaponCardID) || $weaponCardID === '') return;
    if(!isset($targetObj->Subcards) || !is_array($targetObj->Subcards)) {
        $targetObj->Subcards = [];
    }
    $targetObj->Subcards[] = $weaponCardID;
}

function TriggerWhenEquippedAbilities(&$targetObj) {
    if(!is_object($targetObj) || (isset($targetObj->removed) && $targetObj->removed)) return;

    $targetCardID = $targetObj->CardID ?? '';
    if($targetCardID === 'S1-AZK01-039_Piko_E_C_die') {
        AddUniqueTurnEffect($targetObj, 'CHARGE');
    }
}

function ResolveWeaponPlayFromHand($player, $mzCard, $targetMZ) {
    $sourceObj = &GetZoneObject($mzCard);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
    if(($sourceObj->Location ?? '') !== 'Hand') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    if(($targetObj->Location ?? '') !== 'Garden') return;

    $weaponCardID = $sourceObj->CardID ?? '';
    if($weaponCardID === '' || CardType($weaponCardID) !== 'WEAPON') return;

    $stack = &GetEffectStack();
    $beforeCount = count($stack);
    SafeMZMove($player, $mzCard, 'EffectStack');
    DecisionQueueController::CleanupRemovedCards();

    $stack = &GetEffectStack();
    $stackIndex = count($stack) - 1;
    if($stackIndex >= $beforeCount) {
        $stackMZ = 'EffectStack-' . $stackIndex;
        OnPlay($player, $stackMZ);

        $stackObj = &GetZoneObject($stackMZ);
        if($stackObj !== null && !(isset($stackObj->removed) && $stackObj->removed)) {
            $stackObj->Remove();
        }
        DecisionQueueController::CleanupRemovedCards();
    }

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    AttachWeaponCardIDToTarget($targetObj, $weaponCardID);
    TriggerWhenEquippedAbilities($targetObj);

    if($weaponCardID === 'S1-STT01-013_Black-Jade-Dagger_W_C_die') {
        $targetKey = 'P' . intval($player) . '_BlackJadeDaggerTargetMZ';
        DecisionQueueController::StoreVariable($targetKey, $targetMZ);
    }
}

function ResolveWeaponPlayFromDiscard($player, $weaponMZ, $targetMZ) {
    $sourceObj = &GetZoneObject($weaponMZ);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
    if(($sourceObj->Location ?? '') !== 'Discard') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    if(($targetObj->Location ?? '') !== 'Garden') return;

    $weaponCardID = $sourceObj->CardID ?? '';
    if($weaponCardID === '' || CardType($weaponCardID) !== 'WEAPON') return;

    $stack = &GetEffectStack();
    $beforeCount = count($stack);
    SafeMZMove($player, $weaponMZ, 'EffectStack');
    DecisionQueueController::CleanupRemovedCards();

    $stack = &GetEffectStack();
    $stackIndex = count($stack) - 1;
    if($stackIndex >= $beforeCount) {
        $stackMZ = 'EffectStack-' . $stackIndex;
        OnPlay($player, $stackMZ);

        $stackObj = &GetZoneObject($stackMZ);
        if($stackObj !== null && !(isset($stackObj->removed) && $stackObj->removed)) {
            $stackObj->Remove();
        }
        DecisionQueueController::CleanupRemovedCards();
    }

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    AttachWeaponCardIDToTarget($targetObj, $weaponCardID);
    TriggerWhenEquippedAbilities($targetObj);

    if($weaponCardID === 'S1-STT01-013_Black-Jade-Dagger_W_C_die') {
        $targetKey = 'P' . intval($player) . '_BlackJadeDaggerTargetMZ';
        DecisionQueueController::StoreVariable($targetKey, $targetMZ);
    }
}

function ReequipAttachedWeapon($player, $sourceMZ, $weaponCardID, $targetMZ) {
    if(!is_string($sourceMZ) || $sourceMZ === '' || !is_string($targetMZ) || $targetMZ === '') return false;
    if(!is_string($weaponCardID) || $weaponCardID === '' || CardType($weaponCardID) !== 'WEAPON') return false;
    if($sourceMZ === $targetMZ) return false;

    $sourceObj = &GetZoneObject($sourceMZ);
    $targetObj = &GetZoneObject($targetMZ);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return false;
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return false;
    if(($sourceObj->Location ?? '') !== 'Garden' || ($targetObj->Location ?? '') !== 'Garden') return false;

    if(!isset($sourceObj->Subcards) || !is_array($sourceObj->Subcards)) return false;
    $removedWeapon = false;
    $remaining = [];
    for($i = 0; $i < count($sourceObj->Subcards); ++$i) {
        $subcardID = $sourceObj->Subcards[$i] ?? '';
        if(!$removedWeapon && $subcardID === $weaponCardID) {
            $removedWeapon = true;
            continue;
        }
        $remaining[] = $subcardID;
    }
    if(!$removedWeapon) return false;

    $sourceObj->Subcards = $remaining;
    AttachWeaponCardIDToTarget($targetObj, $weaponCardID);
    TriggerWhenEquippedAbilities($targetObj);

    if($weaponCardID === 'S1-STT01-013_Black-Jade-Dagger_W_C_die') {
        $targetKey = 'P' . intval($player) . '_BlackJadeDaggerTargetMZ';
        DecisionQueueController::StoreVariable($targetKey, $targetMZ);
    }

    return true;
}

function DiscardAllEquippedWeapons($player) {
    $garden = &GetGarden($player);
    foreach($garden as &$obj) {
        if(isset($obj->removed) && $obj->removed) continue;
        DiscardEquippedWeaponsFromObject($player, $obj);
    }

    $alley = &GetAlley($player);
    foreach($alley as &$obj) {
        if(isset($obj->removed) && $obj->removed) continue;
        DiscardEquippedWeaponsFromObject($player, $obj);
    }
}

function GardenAfterAdd($player, $CardID, $Status, $Owner, $Damage, $Controller, $TurnEffects, $Counters, $Subcards) {
    $garden = &GetGarden($player);
    if(empty($garden)) return;
    $idx = count($garden) - 1;
    if($idx < 0 || !isset($garden[$idx])) return;
    NormalizeFieldOwnership($garden[$idx], $player);
}

function AlleyAfterAdd($player, $CardID, $Status, $Owner, $Damage, $Controller, $TurnEffects, $Counters, $Subcards) {
    $alley = &GetAlley($player);
    if(empty($alley)) return;
    $idx = count($alley) - 1;
    if($idx < 0 || !isset($alley[$idx])) return;
    NormalizeFieldOwnership($alley[$idx], $player);
}

function GateAfterAdd($player, $CardID, $Status, $Owner, $Controller, $TurnEffects, $Counters) {
    $gate = &GetGate($player);
    if(empty($gate)) return;
    $idx = count($gate) - 1;
    if($idx < 0 || !isset($gate[$idx])) return;
    NormalizeFieldOwnership($gate[$idx], $player);
}

// CardAttack(), CardHealth(), CardElement(), CardSubtypes() are provided by GeneratedCardDictionaries.php

function CardCost($cardID) {
    $cost = CardIkzCost($cardID);
    return $cost !== null && $cost >= 0 ? intval($cost) : 0;
}

function HandCardCostReduction($player, $cardID, $obj = null) {
    $player = intval($player);
    if(!is_string($cardID) || $cardID === '') return 0;

    if(is_object($obj)) {
        $location = strval($obj->Location ?? '');
        if($location !== 'Hand') return 0;
    }

    $discount = 0;
    if(PlayerLeaderHasTurnEffect($player, 'BENZAI_SLY_NEXT_PLAY_DISCOUNT')) {
        $discount += 2;
    }

    if($cardID !== 'S1-AZK01-106_Lord-of-Sands-Osunanami_E_SR_die') {
        return max(0, $discount);
    }

    $garden = &GetGarden($player);
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        if(IsDefenderEntity($garden[$i])) ++$discount;
    }

    return max(0, $discount);
}

function EffectivePlayCost($player, $cardID, $obj = null) {
    $baseCost = CardCost($cardID);
    $discount = HandCardCostReduction($player, $cardID, $obj);
    return max(0, intval($baseCost) - intval($discount));
}

function HandCardCostDifference($obj) {
    global $playerID;
    if(!is_object($obj)) return -1;

    $cardID = strval($obj->CardID ?? '');
    if($cardID === '') return -1;

    $baseCost = CardCost($cardID);
    $effectiveCost = EffectivePlayCost(intval($playerID), $cardID, $obj);
    return $effectiveCost !== $baseCost ? $effectiveCost : -1;
}

function FindLeaderIndexInGarden($player) {
    $garden = &GetGarden($player);
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        if(CardType($garden[$i]->CardID ?? '') === 'LEADER') return $i;
    }
    return -1;
}

function PlayerLeaderHasTurnEffect($player, $effectID) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return false;
    return HasTurnEffect($garden[$leaderIndex], $effectID);
}

function RemovePlayerLeaderTurnEffect($player, $effectID) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return;
    RemoveTurnEffect($garden[$leaderIndex], $effectID);
}

function LeaderMaxHealth($player) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex >= 0 && $leaderIndex < count($garden)) {
        $h = CardHealth($garden[$leaderIndex]->CardID ?? '');
        if($h === null || $h < 0) $h = 20; // default for leaders without health data in the API
        return max(1, intval($h));
    }
    return 20;
}

function LeaderAttack($player) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex >= 0 && $leaderIndex < count($garden)) {
        $leaderObj = $garden[$leaderIndex];
        return ResolveEntityAttackValue($player, $leaderObj);
    }
    return 0;
}

function LeaderCombatDamageReduction($player) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return 0;

    $leaderObj = $garden[$leaderIndex];
    if($leaderObj === null || (isset($leaderObj->removed) && $leaderObj->removed)) return 0;

    $reduction = 0;
    foreach(GetAttachedWeaponIDs($leaderObj) as $weaponID) {
        if($weaponID === 'S1-AZK01-018_Monk-Staff-of-Warding_W_C_die') {
            $reduction += 1;
        }
    }
    return $reduction;
}

function EntityDamageReduction($obj) {
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return 0;

    $reduction = 0;
    $cardID = $obj->CardID ?? '';
    $effectText = CardCardText($cardID);
    if(CardHasKeyword($cardID, 'Carapace') && preg_match('/Carapace\s+(\d+)/i', strval($effectText), $matches)) {
        $reduction = max($reduction, intval($matches[1] ?? 0));
    }

    if(HasTurnEffect($obj, 'CARAPACE_1')) {
        $reduction = max($reduction, 1);
    }

    return max(0, $reduction);
}

function LeaderCurrentHealth($player) {
    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return 0;

    $leaderObj = &$garden[$leaderIndex];
    if($leaderObj === null || (isset($leaderObj->removed) && $leaderObj->removed)) return 0;

    $maxHealth = LeaderMaxHealth($player);
    $damage = intval($leaderObj->Damage ?? 0);
    return max(0, $maxHealth - $damage);
}

function TriggerGameOver($loserPlayer) {
    $loserPlayer = intval($loserPlayer);
    if($loserPlayer !== 1 && $loserPlayer !== 2) return;

    $existingWinner = DecisionQueueController::GetVariable('GAMEOVER_WINNER');
    if(is_string($existingWinner) && $existingWinner !== '') return;

    $winner = ($loserPlayer === 1) ? 2 : 1;
    DecisionQueueController::StoreVariable('GAMEOVER_WINNER', strval($winner));
}

function CardHasKeyword($cardID, $keyword) {
    $abilities = CardAbilities($cardID);
    if(!is_array($abilities)) return false;
    foreach($abilities as $ability) {
        if(is_string($ability) && strcasecmp($ability, $keyword) === 0) return true;
    }
    return false;
}

function CountActiveEntities($zone, $ignoreLeaders = true) {
    if(!is_array($zone)) return 0;
    $count = 0;
    for($i = 0; $i < count($zone); ++$i) {
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        $cardID = $zone[$i]->CardID ?? '';
        if($ignoreLeaders && CardType($cardID) === 'LEADER') continue;
        ++$count;
    }
    return $count;
}

function CanPayIKZCost($player, $cost) {
    $cost = max(0, intval($cost));
    if($cost === 0) return true;
    
    $ikzArea = GetIKZArea($player);
    $ikzToken = GetAccessibleIKZTokenCount($player);
    
    // Count untapped IKZ in the area
    $availableIKZ = 0;
    if(is_array($ikzArea)) {
        foreach($ikzArea as $ikz) {
            if(!isset($ikz->removed) || !$ikz->removed) {
                // IKZ is untapped (Status=2) or tapped (Status=1), both count as available
                // (A tapped IKZ could have been tapped earlier this turn)
                if(!isset($ikz->Status) || $ikz->Status == 2) {
                    $availableIKZ++;
                }
            }
        }
    }
    
    $garden = GetGarden($player);
    if(is_array($garden)) {
        foreach($garden as $entity) {
            if(isset($entity->removed) && $entity->removed) continue;
            if(intval($entity->Status ?? 2) !== 2) continue;
            if(($entity->CardID ?? '') !== 'S1-STT03-007_Koyama-Farm-Caretaker_E_R_die') continue;
            $availableIKZ++;
        }
    }

    return ($availableIKZ + $ikzToken) >= $cost;
}

function GetAccessibleIKZTokenCount($player) {
    $token = intval(GetIKZToken($player));
    if($token <= 0) return 0;
    if(intval($player) !== 2) return $token;
    return DecisionQueueController::GetVariable('P2_StartingIKZTokenPending') === '1' ? 0 : $token;
}

function GrantSecondPlayerStartingIKZTokenIfPending($player) {
    if(intval($player) !== 2) return;
    if(DecisionQueueController::GetVariable('P2_StartingIKZTokenPending') !== '1') return;
    $ikzToken = &GetIKZToken($player);
    if(intval($ikzToken) <= 0) {
        $ikzToken = 1;
    }
    DecisionQueueController::StoreVariable('P2_StartingIKZTokenPending', '0');
}

function PayIKZCost($player, $cost) {
    $cost = max(0, intval($cost));
    if($cost === 0) return true;
    if(!CanPayIKZCost($player, $cost)) return false;

    $ikzArea = &GetIKZArea($player);
    $ikzToken = &GetIKZToken($player);

    $remaining = $cost;
    
    // First, tap untapped IKZ in the area
    if(is_array($ikzArea)) {
        foreach($ikzArea as &$ikz) {
            if($remaining <= 0) break;
            if(isset($ikz->removed) && $ikz->removed) continue;
            if(!isset($ikz->Status)) $ikz->Status = 2;
            
            // Only tap if currently untapped
            if($ikz->Status == 2) {
                $ikz->Status = 1; // Tap it
                $remaining--;
            }
        }
    }

    if($remaining > 0) {
        $garden = &GetGarden($player);
        if(is_array($garden)) {
            foreach($garden as &$entity) {
                if($remaining <= 0) break;
                if(isset($entity->removed) && $entity->removed) continue;
                if(intval($entity->Status ?? 2) !== 2) continue;
                if(($entity->CardID ?? '') !== 'S1-STT03-007_Koyama-Farm-Caretaker_E_R_die') continue;
                $entity->Status = 1;
                $remaining--;
            }
        }
    }

    // If still need to pay, use the token
    if($remaining > 0) {
        $token = intval($ikzToken);
        $accessibleToken = GetAccessibleIKZTokenCount($player);
        $fromToken = min($accessibleToken, $remaining);
        $ikzToken = max(0, $token - $fromToken);
        $remaining -= $fromToken;
    }

    return $remaining <= 0;
}

function CountAvailableIKZ($player) {
    $ikzArea = GetIKZArea($player);
    $count = 0;
    if(is_array($ikzArea)) {
        foreach($ikzArea as $ikz) {
            if((!isset($ikz->removed) || !$ikz->removed) && (!isset($ikz->Status) || $ikz->Status == 2)) {
                $count++;
            }
        }
    }
    return $count + GetAccessibleIKZTokenCount($player);
}

function FindReplaceableIndex($zone) {
    if(!is_array($zone)) return -1;
    for($i = 0; $i < count($zone); ++$i) {
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        if(CardType($zone[$i]->CardID ?? '') === 'LEADER') continue;
        // Prefer replacing non-Godmode entities, but allow Godmode as a fallback.
        if(!CardHasKeyword($zone[$i]->CardID ?? '', 'Godmode')) return $i;
    }
    for($i = 0; $i < count($zone); ++$i) {
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        if(CardType($zone[$i]->CardID ?? '') === 'LEADER') continue;
        return $i;
    }
    return -1;
}

function ResolveEntityPlayFromHand($player, $mzCard, $destination) {
    if($destination !== 'myGarden' && $destination !== 'myAlley') {
        return;
    }

    if($destination === 'myGarden') {
        $garden = &GetGarden($player);
        if(CountActiveEntities($garden, true) >= 5) {
            $replaceIndex = FindReplaceableIndex($garden);
            if($replaceIndex >= 0) {
                SafeMZMove($player, 'myGarden-' . $replaceIndex, 'myDiscard');
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    }
    else {
        $alley = &GetAlley($player);
        if(CountActiveEntities($alley, true) >= 5) {
            $replaceIndex = FindReplaceableIndex($alley);
            if($replaceIndex >= 0) {
                SafeMZMove($player, 'myAlley-' . $replaceIndex, 'myDiscard');
                DecisionQueueController::CleanupRemovedCards();
            }
        }
    }

    SafeMZMove($player, $mzCard, $destination);
    DecisionQueueController::CleanupRemovedCards();

    $placedZone = ($destination === 'myGarden') ? GetGarden($player) : GetAlley($player);
    $placedIndex = count($placedZone) - 1;
    if($placedIndex < 0) {
        return;
    }

    $newMZ = $destination . '-' . $placedIndex;
    $newObj = &GetZoneObject($newMZ);
    if($newObj === null || (isset($newObj->removed) && $newObj->removed)) {
        return;
    }

    NormalizeFieldOwnership($newObj, $player);

    if($destination === 'myGarden') {
        if(!isset($newObj->TurnEffects) || !is_array($newObj->TurnEffects)) {
            $newObj->TurnEffects = [];
        }
        if(!in_array('COOLDOWN', $newObj->TurnEffects)) {
            $newObj->TurnEffects[] = 'COOLDOWN';
        }
    }

    Enter($player, $newMZ);
    if($destination === 'myGarden') {
        EnterGarden($player, $newMZ);
    }
    OnPlay($player, $newMZ);

    $entityPlays = intval(DecisionQueueController::GetVariable('P' . intval($player) . '_EntitiesPlayedThisTurn'));
    DecisionQueueController::StoreVariable('P' . intval($player) . '_EntitiesPlayedThisTurn', strval($entityPlays + 1));
}

function QueueLeaderDamageAnimation($player, $amount) {
    if(intval($amount) <= 0) return;

    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex >= 0) {
        QueueDamageAnimation('p' . $player . 'Garden-' . $leaderIndex, intval($amount), 500, true);
        return;
    }

    QueueDamageAnimation(intval($player) === 1 ? 'P1BASE' : 'P2BASE', intval($amount), 500, true);
}

function DealDamageToLeader($player, $amount, $sourceKey = null) {
    $amount = max(0, intval($amount));
    if($amount <= 0) return;

    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return;

    $leaderObj = &$garden[$leaderIndex];
    if($leaderObj === null || (isset($leaderObj->removed) && $leaderObj->removed)) return;

    $leaderObj->Damage = intval($leaderObj->Damage ?? 0) + $amount;
    RecordDamageSourceOnObject($leaderObj, ResolveDamageSourceKey($player, $sourceKey));
    QueueLeaderDamageAnimation($player, $amount);

    if(LeaderCurrentHealth($player) <= 0) {
        TriggerGameOver($player);
    }
}

function HealLeader($player, $amount) {
    $amount = max(0, intval($amount));
    if($amount <= 0) return;

    $garden = &GetGarden($player);
    $leaderIndex = FindLeaderIndexInGarden($player);
    if($leaderIndex < 0 || $leaderIndex >= count($garden)) return;

    $leaderObj = &$garden[$leaderIndex];
    if($leaderObj === null || (isset($leaderObj->removed) && $leaderObj->removed)) return;

    $maxHealth = LeaderMaxHealth($player);
    $currentDamage = max(0, intval($leaderObj->Damage ?? 0));
    $newDamage = max(0, $currentDamage - $amount);
    $leaderObj->Damage = min($maxHealth, $newDamage);
}

function ClearPekiroPendingDamageVars() {
    DecisionQueueController::ClearVariable('PekiroPendingSourcePlayer');
    DecisionQueueController::ClearVariable('PekiroPendingOriginalTargetMZ');
    DecisionQueueController::ClearVariable('PekiroPendingAmount');
    DecisionQueueController::ClearVariable('PekiroPendingSourceKey');
}

function QueuePekiroDamageReplacement($sourcePlayer, $targetMZ, $amount, $sourceKey) {
    $sourcePlayer = intval($sourcePlayer);
    $amount = max(0, intval($amount));
    if($sourcePlayer <= 0 || $amount <= 0) return false;
    if(!is_string($targetMZ) || $targetMZ === '') return false;

    $parts = explode('-', $targetMZ);
    $zone = $parts[0] ?? '';
    if($zone !== 'myGarden' && $zone !== 'theirGarden' && $zone !== 'myAlley' && $zone !== 'theirAlley') return false;

    $targetPlayer = (strpos($zone, 'my') === 0) ? $sourcePlayer : ($sourcePlayer === 1 ? 2 : 1);
    $resolvedTargetMZ = NormalizeMZForPlayerPerspective($targetPlayer, $targetMZ);
    $targetObj = GetZoneObject($resolvedTargetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return false;
    if(($targetObj->CardID ?? '') !== 'S1-AZK01-062_Pekiro_E_R_die') return false;
    if(HasTurnEffect($targetObj, 'PEKIRO_USED')) return false;

    $targets = [];
    $myGarden = &GetGarden($targetPlayer);
    for($i = 0; $i < count($myGarden); ++$i) {
        if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
        if(CardType($myGarden[$i]->CardID ?? '') !== 'ENTITY') continue;
        if($resolvedTargetMZ === 'myGarden-' . $i) continue;
        $targets[] = 'myGarden-' . $i;
    }

    $opponent = ($targetPlayer === 1) ? 2 : 1;
    $theirGarden = &GetGarden($opponent);
    for($i = 0; $i < count($theirGarden); ++$i) {
        if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
        if(CardType($theirGarden[$i]->CardID ?? '') !== 'ENTITY') continue;
        $targets[] = 'theirGarden-' . $i;
    }

    if(empty($targets)) return false;

    AddUniqueTurnEffect($targetObj, 'PEKIRO_USED');
    ClearPekiroPendingDamageVars();
    DecisionQueueController::StoreVariable('PekiroPendingSourcePlayer', strval($sourcePlayer));
    DecisionQueueController::StoreVariable('PekiroPendingOriginalTargetMZ', $targetMZ);
    DecisionQueueController::StoreVariable('PekiroPendingAmount', strval($amount));
    DecisionQueueController::StoreVariable('PekiroPendingSourceKey', strval($sourceKey));
    DecisionQueueController::AddDecision($targetPlayer, 'MZMAYCHOOSE', implode('&', $targets), 1, 'Choose_another_entity_in_any_Garden_to_redirect_the_damage_to');
    DecisionQueueController::AddDecision($targetPlayer, 'CUSTOM', 'PEKIRO_REDIRECT_DAMAGE', 1);
    return true;
}

function TryHandlePreDamageReplacement($player, $targetMZ, $amount, $sourceKey, $isCardEffect, $allowReplacement = true) {
    if(!$allowReplacement || !$isCardEffect) return false;
    $amount = max(0, intval($amount));
    if($amount <= 0) return false;
    return QueuePekiroDamageReplacement($player, $targetMZ, $amount, $sourceKey);
}

function TriggerZeroStarterDamageReactions($player, $targetMZ, $amount, $isCardEffect = false) {
    $amount = max(0, intval($amount));
    if($amount <= 0) return;
    if(!is_string($targetMZ) || $targetMZ === '') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;

    $targetCardID = $targetObj->CardID ?? '';
    if(!is_string($targetCardID) || $targetCardID === '') return;

    // Enraged Howler: once/turn when this takes damage, +1 attack this turn.
    if($targetCardID === 'S1-STT04-007_Enraged-Howler_E_C_die') {
        if(!HasTurnEffect($targetObj, 'STT04_HOWLER_USED')) {
            AddUniqueTurnEffect($targetObj, 'STT04_HOWLER_USED');
            AddUniqueTurnEffect($targetObj, 'ATK_MOD:1');
        }
    }

    // Spiteful Raider: once/turn when this takes damage, deal 1 to a leader or entity.
    if($targetCardID === 'S1-STT04-012_Spiteful-Raider_E_UC_die') {
        if(!HasTurnEffect($targetObj, 'STT04_RAIDER_USED')) {
            AddUniqueTurnEffect($targetObj, 'STT04_RAIDER_USED');
            $opponent = ($player == 1) ? 2 : 1;
            DealDamageToLeader($opponent, 1);
        }
    }

    // Cinderwake Ritualist: once/turn when damaged by card effects, reflect that damage (max 2).
    if($targetCardID === 'S1-STT04-009_Cinderwake-Ritualist_E_R_die' && $isCardEffect) {
        if(!HasTurnEffect($targetObj, 'STT04_RITUALIST_USED')) {
            AddUniqueTurnEffect($targetObj, 'STT04_RITUALIST_USED');
            $reflect = min(2, $amount);
            if($reflect > 0) {
                $targetParts = explode('-', $targetMZ);
                $targetZone = $targetParts[0] ?? '';
                $targetIndex = intval($targetParts[1] ?? -1);
                $opponent = ($player == 1) ? 2 : 1;
                $theirGarden = &GetGarden($opponent);
                $picked = '';
                for($i = 0; $i < count($theirGarden); ++$i) {
                    if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
                    $candidateID = $theirGarden[$i]->CardID ?? '';
                    if($targetZone === 'theirGarden' && $i === $targetIndex) continue;
                    if(CardType($candidateID) !== 'ENTITY') continue;
                    $picked = 'theirGarden-' . $i;
                    break;
                }
                if($picked !== '') {
                    DealDamageToGardenTarget($player, $picked, $reflect);
                } else {
                    DealDamageToLeader($opponent, $reflect);
                }
            }
        }
    }
}

function TriggerKuraiUntapFromEnemyGardenDestroy($actingPlayer, $destroyedOwner, $destroyedCardID) {
    if(CardType($destroyedCardID) !== 'ENTITY') return;
    $opponentOfActing = ($actingPlayer == 1) ? 2 : 1;
    if(intval($destroyedOwner) !== intval($opponentOfActing)) return;

    $myGarden = &GetGarden($actingPlayer);
    for($i = 0; $i < count($myGarden); ++$i) {
        if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
        if(($myGarden[$i]->CardID ?? '') !== 'S1-STT04-013_Kurai-the-Volcano_E_SR_die') continue;
        if(HasTurnEffect($myGarden[$i], 'STT04_KURAI_USED')) continue;
        AddUniqueTurnEffect($myGarden[$i], 'STT04_KURAI_USED');
        $myGarden[$i]->Status = 2;
    }
}

function DealDamageToFieldTargetInternal($player, $targetMZ, $amount, $isCardEffect = true, $sourceKey = null, $allowReplacement = true) {
    $amount = max(0, intval($amount));
    if($amount <= 0) return;
    if(!is_string($targetMZ) || $targetMZ === '') return;

    $parts = explode('-', $targetMZ);
    $zone = $parts[0] ?? '';
    $index = intval($parts[1] ?? -1);
    if($zone === 'myGarden' || $zone === 'theirGarden') {
        $targetPlayer = ($zone === 'myGarden') ? intval($player) : (intval($player) === 1 ? 2 : 1);
        $targetOwnerMZ = ($zone === 'myGarden') ? $targetMZ : FlipZonePerspective($targetMZ);
        $resolvedSourceKey = ResolveDamageSourceKey($player, $sourceKey);
        $garden = &GetGarden($targetPlayer);

        if($index < 0 || $index >= count($garden)) return;
        if(isset($garden[$index]->removed) && $garden[$index]->removed) return;

        $targetCardID = $garden[$index]->CardID ?? '';
        if(CardType($targetCardID) === 'LEADER') {
            DealDamageToLeader($targetPlayer, $amount, $resolvedSourceKey);
            return;
        }

        if(HasTurnEffect($garden[$index], 'FROZEN')) return;
        if($isCardEffect && IsImmuneToCardEffectDamage($garden[$index])) return;
        $amount = max(0, $amount - EntityDamageReduction($garden[$index]));
        if($amount <= 0) return;

        if(TryHandlePreDamageReplacement($player, $targetMZ, $amount, $resolvedSourceKey, $isCardEffect, $allowReplacement)) return;

        $garden[$index]->Damage = intval($garden[$index]->Damage ?? 0) + $amount;
        QueueDamageAnimation('p' . $targetPlayer . 'Garden-' . $index, $amount, 500, true);
        TriggerZeroStarterDamageReactions($player, $targetMZ, $amount, $isCardEffect);
        RecordDamageSourceOnObject($garden[$index], $resolvedSourceKey);
        if(is_string($targetOwnerMZ) && $targetOwnerMZ !== '') {
            DamageTaken($targetPlayer, $targetOwnerMZ, $amount);
        }

        $targetHealth = ResolveEntityHealthValue($targetPlayer, $garden[$index]);
        $targetDamage = intval($garden[$index]->Damage ?? 0);
        if($targetHealth > 0 && $targetDamage >= $targetHealth) {
            TriggerKuraiUntapFromEnemyGardenDestroy($player, $targetPlayer, $targetCardID);
            SafeMZMove($player, $zone . '-' . $index, ($zone === 'myGarden' ? 'myDiscard' : 'theirDiscard'));
            DecisionQueueController::CleanupRemovedCards();
        }
        return;
    }

    if($zone !== 'myAlley' && $zone !== 'theirAlley') return;

    $targetPlayer = ($zone === 'myAlley') ? intval($player) : (intval($player) === 1 ? 2 : 1);
    $targetOwnerMZ = ($zone === 'myAlley') ? $targetMZ : FlipZonePerspective($targetMZ);
    $resolvedSourceKey = ResolveDamageSourceKey($player, $sourceKey);
    $alley = &GetAlley($targetPlayer);
    if($index < 0 || $index >= count($alley)) return;
    if(isset($alley[$index]->removed) && $alley[$index]->removed) return;

    $targetCardID = $alley[$index]->CardID ?? '';
    if(HasTurnEffect($alley[$index], 'FROZEN')) return;
    if($isCardEffect && IsImmuneToCardEffectDamage($alley[$index])) return;
    $amount = max(0, $amount - EntityDamageReduction($alley[$index]));
    if($amount <= 0) return;

    if(TryHandlePreDamageReplacement($player, $targetMZ, $amount, $resolvedSourceKey, $isCardEffect, $allowReplacement)) return;

    $alley[$index]->Damage = intval($alley[$index]->Damage ?? 0) + $amount;
    QueueDamageAnimation('p' . $targetPlayer . 'Alley-' . $index, $amount, 500, true);
    TriggerZeroStarterDamageReactions($player, $targetMZ, $amount, $isCardEffect);
    RecordDamageSourceOnObject($alley[$index], $resolvedSourceKey);
    if(is_string($targetOwnerMZ) && $targetOwnerMZ !== '') {
        DamageTaken($targetPlayer, $targetOwnerMZ, $amount);
    }

    $targetHealth = ResolveEntityHealthValue($targetPlayer, $alley[$index]);
    $targetDamage = intval($alley[$index]->Damage ?? 0);
    if($targetHealth > 0 && $targetDamage >= $targetHealth) {
        TriggerKuraiUntapFromEnemyGardenDestroy($player, $targetPlayer, $targetCardID);
        SafeMZMove($player, $zone . '-' . $index, ($zone === 'myAlley' ? 'myDiscard' : 'theirDiscard'));
        DecisionQueueController::CleanupRemovedCards();
    }
}

function DealDamageToGardenTarget($player, $targetMZ, $amount, $sourceKey = null) {
    DealDamageToFieldTargetInternal($player, $targetMZ, $amount, true, $sourceKey, true);
}

function DealDamageToEntityTarget($player, $targetMZ, $amount, $isCardEffect = true, $sourceKey = null) {
    DealDamageToFieldTargetInternal($player, $targetMZ, $amount, $isCardEffect, $sourceKey, true);
}

function GetPortalCandidates($player) {
    $alley = &GetAlley($player);
    $candidates = [];

    for($i = 0; $i < count($alley); ++$i) {
        $obj = &$alley[$i];
        if(isset($obj->removed) && $obj->removed) continue;
        $status = intval($obj->Status ?? 2);
        if($status == 1) continue; // must be untapped
        $candidates[] = 'myAlley-' . $i;
    }

    return $candidates;
}

function CanUseGateRuntime($player, $gateMZ = null, $entityMZ = null) {
    $gateZone = &GetGate($player);
    if(empty($gateZone)) return false;
    $gate = &$gateZone[0];
    // Gate can only be used once per turn and must be untapped
    if(!isset($gate->Status)) $gate->Status = 2;
    if($gate->Status == 1) return false; // Tapped

    // Check if gate was already used this turn
    if(isset($gate->TurnEffects) && in_array("GATE_USED_THIS_TURN", $gate->TurnEffects)) {
        return false;
    }

    $candidates = GetPortalCandidates($player);
    if(empty($candidates)) return false;

    if(is_string($entityMZ) && $entityMZ !== '') {
        return in_array($entityMZ, $candidates);
    }

    return true;
}

function ExhaustEntity($player, $mzID) {
    $mzParts = explode("-", $mzID);
    $zone = $mzParts[0];
    $index = intval($mzParts[1] ?? -1);

    if($zone === "myGarden" || $zone === "theirGarden") {
        $field = &GetGarden(($zone === "myGarden") ? $player : 3 - $player);
    } else if($zone === "myAlley" || $zone === "theirAlley") {
        $field = &GetAlley(($zone === "myAlley") ? $player : 3 - $player);
    } else {
        return;
    }

    if($index >= 0 && $index < count($field) && !$field[$index]->removed) {
        $field[$index]->Status = 1; // 1 = tapped/exhausted
    }
}

function WakeEntity($player, $mzID) {
    $mzParts = explode("-", $mzID);
    $zone = $mzParts[0];
    $index = intval($mzParts[1] ?? -1);

    if($zone === "myGarden" || $zone === "theirGarden") {
        $field = &GetGarden(($zone === "myGarden") ? $player : 3 - $player);
    } else if($zone === "myAlley" || $zone === "theirAlley") {
        $field = &GetAlley(($zone === "myAlley") ? $player : 3 - $player);
    } else {
        return;
    }

    if($index >= 0 && $index < count($field) && !$field[$index]->removed) {
        if(($field[$index]->CardID ?? '') === 'S1-AZK01-046_Mina-the-Geomancer_E_UC_die') return;
        $field[$index]->Status = 2; // 2 = ready/untapped
    }
}

function HasCooldown($entity) {
    return isset($entity->TurnEffects) && in_array("COOLDOWN", $entity->TurnEffects);
}

function CanAttackOpponentAlleyUntapped($cardID, $obj = null) {
    if($cardID === 'S1-AZK01-037_Stormcaller-Tenkichi_E_R_die'
        || $cardID === 'S1-AZK01-038_Riven-Flashborne_E_R_die') {
        return true;
    }

    if(is_object($obj) && CardType($cardID) === 'LEADER') {
        $weaponIDs = GetAttachedWeaponIDs($obj);
        return in_array('S1-AZK01-043_Stormglass-Daggers_W_C_die', $weaponIDs, true)
            || in_array('S1-AZK01-095_Stormglass-Katana_W_C_die', $weaponIDs, true);
    }

    return false;
}

function CanAttackWith($player, $mzID) {
    $mzParts = explode("-", $mzID);
    $zone = $mzParts[0];
    $index = intval($mzParts[1] ?? -1);

    // Can only attack from Garden (front row)
    if($zone !== "myGarden") return false;

    $garden = &GetGarden($player);
    if($index < 0 || $index >= count($garden) || $garden[$index]->removed) return false;

    $entity = &$garden[$index];

    if(CardType($entity->CardID ?? '') === 'LEADER' && !HasEquippedWeapon($entity)) {
        return false;
    }

    // Cannot attack if tapped
    if($entity->Status == 1) return false; // Tapped
    if(HasTurnEffect($entity, 'FROZEN')) return false;
    if(HasTurnEffect($entity, 'ROOTED')) return false;
    if(HasCooldown($entity)) {
        $hasCharge = CardHasKeyword($entity->CardID ?? '', 'Charge')
            || (isset($entity->TurnEffects) && is_array($entity->TurnEffects) && in_array('CHARGE', $entity->TurnEffects, true));
        if(!$hasCharge) return false;
    }

    return true;
}

function ResetEntityDamage($player, $zone) {
    if($zone === "myGarden" || $zone === "myAlley") {
        $field = ($zone === "myGarden") ? GetGarden($player) : GetAlley($player);
    } else {
        return;
    }

    foreach($field as &$entity) {
        if(!$entity->removed && isset($entity->Damage)) {
            // Skip LEADER cards: leader damage is permanent
            if(CardType($entity->CardID ?? '') === 'LEADER') continue;
            $entity->Damage = 0; // Reset entity damage at end of turn
        }
    }
}

function WakeAllCards($player) {
    $player = intval($player);
    $garden = &GetGarden($player);
    $alley = &GetAlley($player);
    $gate = &GetGate($player);

    foreach($garden as &$entity) {
        if($entity->removed) continue;
        if(intval(ResolveObjectOwner($entity)) !== $player) continue;
        if(HasTurnEffect($entity, 'SHOCKED')) {
            RemoveTurnEffect($entity, 'SHOCKED');
            continue;
        }
        if(($entity->CardID ?? '') === 'S1-AZK01-046_Mina-the-Geomancer_E_UC_die') continue;
        $entity->Status = 2; // Ready all entities
    }

    foreach($alley as &$entity) {
        if($entity->removed) continue;
        if(intval(ResolveObjectOwner($entity)) !== $player) continue;
        if(HasTurnEffect($entity, 'SHOCKED')) {
            RemoveTurnEffect($entity, 'SHOCKED');
            continue;
        }
        if(($entity->CardID ?? '') === 'S1-AZK01-046_Mina-the-Geomancer_E_UC_die') continue;
        $entity->Status = 2; // Ready all entities
    }

    foreach($gate as &$entity) {
        if($entity->removed) continue;
        if(intval(ResolveObjectOwner($entity)) !== $player) continue;
        $entity->Status = 2; // Ready only the active player's gate cards
    }
}

function GainIKZ($player, $amount, $status=2) {
    $ikzArea = &GetIKZArea($player);
    $ikzPile = &GetIKZPile($player);
    
    if(!is_array($ikzArea)) $ikzArea = [];
    if(!is_array($ikzPile)) $ikzPile = [];
    
    // Count current IKZ in area
    $currentCount = 0;
    foreach($ikzArea as $ikz) {
        if(!isset($ikz->removed) || !$ikz->removed) {
            $currentCount++;
        }
    }
    
    // Don't exceed maximum of 10 in the area
    $canGain = max(0, 10 - $currentCount);
    $toAdd = min($amount, $canGain);
    $toOverflow = $amount - $toAdd;
    
    // Add IKZ to area (untapped, Status=2)
    for($i = 0; $i < $toAdd; ++$i) {
        AddIKZArea($player, "IKZ-001_IKZ!_IKZ_die", $status);
    }
}

function UntapAllIKZ($player) {
    $ikzArea = &GetIKZArea($player);
    $ikzPile = &GetIKZPile($player);
    
    if(is_array($ikzArea)) {
        foreach($ikzArea as &$ikz) {
            if(!isset($ikz->removed) || !$ikz->removed) {
                $ikz->Status = 2; // Untap
            }
        }
    }
    
    if(is_array($ikzPile)) {
        foreach($ikzPile as &$ikz) {
            if(!isset($ikz->removed) || !$ikz->removed) {
                $ikz->Status = 2; // Untap
            }
        }
    }
}

function ResolveObjectOwner($obj) {
    if(!is_object($obj)) return null;

    if(isset($obj->Controller)) {
        $controller = intval($obj->Controller);
        if($controller > 0) return $controller;
    }

    if(isset($obj->PlayerID)) {
        $playerID = intval($obj->PlayerID);
        if($playerID > 0) return $playerID;
    }

    if(isset($obj->Owner)) {
        $owner = intval($obj->Owner);
        if($owner > 0) return $owner;
    }

    return null;
}

function SelectionMetadata($obj) {
    $currentPhase = GetCurrentPhase();
    $turnPlayer = &GetTurnPlayer();

    // Azuki selections are only surfaced during the turn player's main phase.
    if($currentPhase !== "MAIN") {
        return json_encode(['highlight' => false]);
    }

    // Suppress baseline highlights while either player has queued decisions.
    $myQueue = &GetDecisionQueue($turnPlayer);
    $theirQueue = &GetDecisionQueue($turnPlayer == 1 ? 2 : 1);
    if(count($myQueue) > 0 || count($theirQueue) > 0) {
        return json_encode(['highlight' => false]);
    }

    // Hand/temp-zone highlights are for the currently acting player.
    $owner = ResolveObjectOwner($obj);
    $actingPlayer = HasPendingAttackResponse() ? GetPendingAttackResponderPlayer() : intval($turnPlayer);
    if($owner === null || $owner !== intval($actingPlayer)) {
        return json_encode(['highlight' => false]);
    }

    // During a response window only cards with the [Response] timing tag are playable.
    if(HasPendingAttackResponse() && !CardHasTimingTag($obj->CardID, 'Response')) {
        return json_encode(['highlight' => false]);
    }

    $location = strval($obj->Location ?? '');
    if($location === 'Hand') {
        $cardID = $obj->CardID ?? '';
        if($cardID === '' || !CanPlayCardByTiming($actingPlayer, $cardID)) {
            return json_encode(['highlight' => false]);
        }
        if(CardType($cardID) === 'WEAPON' && empty(ResolveWeaponEquipTargets($actingPlayer))) {
            return json_encode(['highlight' => false]);
        }
        if(!CanPayIKZCost($actingPlayer, EffectivePlayCost($actingPlayer, $cardID, $obj))) {
            return json_encode(['highlight' => false]);
        }
    }

    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CardType($cardID) {
    $category = CardCategory($cardID);
    if(is_string($category) && $category !== '') {
        switch(strtoupper(trim($category))) {
            case 'ENTITY': return 'ENTITY';
            case 'SPELL': return 'SPELL';
            case 'WEAPON': return 'WEAPON';
            case 'LEADER': return 'LEADER';
            case 'GATE': return 'GATE';
            case 'IKZ': return 'IKZ';
        }
    }
    // Fallback: infer from card ID naming convention
    if(!is_string($cardID)) return '';
    if(strpos($cardID, '_L_L_') !== false) return 'LEADER';
    if(strpos($cardID, '_G_G_') !== false) return 'GATE';
    return 'ENTITY';
}

function CardHasTimingTag($cardID, $tagName) {
    if(!is_string($cardID) || $cardID === '' || !is_string($tagName) || $tagName === '') return false;
    $text = CardCardText($cardID);
    if(!is_string($text) || $text === '') return false;
    return stripos($text, '[' . $tagName . ']') !== false;
}

function CanPlayCardByTiming($player, $cardID) {
    $isResponseWindow = HasPendingAttackResponse();
    $hasMainTiming = CardHasTimingTag($cardID, 'Main');
    $hasResponseTiming = CardHasTimingTag($cardID, 'Response');
    $cardType = CardType($cardID);

    if($isResponseWindow) {
        if(intval($player) !== GetPendingAttackResponderPlayer()) return false;
        return $hasResponseTiming;
    }

    if($cardType === 'SPELL' && $hasResponseTiming && !$hasMainTiming) return false;
    return true;
}

// CardHealth($cardID) is provided by GeneratedCardDictionaries.php
// CardElement($cardID) is provided by GeneratedCardDictionaries.php
// CardSubtypes($cardID) is provided by GeneratedCardDictionaries.php

function CardPower($cardID) {
    return CardAttack($cardID);
}

function CardClasses($cardID) {
    return '';
}

function ObjectCurrentPowerDisplay($obj) {
    if(!is_object($obj) || !isset($obj->CardID)) return 0;
    $owner = ResolveObjectOwner($obj);
    if($owner === null || intval($owner) <= 0) $owner = GetTurnPlayer();
    return ResolveEntityAttackValue(intval($owner), $obj);
}

function ObjectCurrentHPDisplay($obj) {
    $baseHP = 0;
    if(is_object($obj)) {
        $owner = ResolveObjectOwner($obj);
        if($owner === null || intval($owner) <= 0) $owner = GetTurnPlayer();
        $baseHP = ResolveEntityHealthValue(intval($owner), $obj);
    }
    $damage = isset($obj->Damage) ? intval($obj->Damage) : 0;
    return max(0, $baseHP - $damage);
}

function FieldSelectionMetadata($obj) {
    $currentPhase = GetCurrentPhase();
    if($currentPhase !== 'MAIN') {
        return json_encode(['highlight' => false]);
    }

    $turnPlayer = &GetTurnPlayer();
    $myQueue = &GetDecisionQueue($turnPlayer);
    $theirQueue = &GetDecisionQueue($turnPlayer == 1 ? 2 : 1);
    if(count($myQueue) > 0 || count($theirQueue) > 0) {
        return json_encode(['highlight' => false]);
    }

    $owner = ResolveObjectOwner($obj);
    $actingPlayer = HasPendingAttackResponse() ? GetPendingAttackResponderPlayer() : intval($turnPlayer);
    if($owner === null || $owner !== intval($actingPlayer)) {
        return json_encode(['highlight' => false]);
    }

    return json_encode(['color' => 'rgba(0, 255, 0, 0.95)']);
}

function CombatTargetIndicator($obj) {
    if(!is_object($obj) || !isset($obj->Location)) return '';
    if(!HasPendingAttackResponse()) return '';
    if(!isset($obj->mzIndex)) return '';
    $objOwner = ResolveObjectOwner($obj);
    if($objOwner === null) return '';
    $objLocation = strval($obj->Location);
    $objIndex = intval($obj->mzIndex);
    if($objLocation === '' || $objIndex < 0) return '';

    $attackerPlayer = GetPendingAttackAttackerPlayer();
    if($attackerPlayer !== 1 && $attackerPlayer !== 2) return '';

    $attackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
    $targetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
    $attackerAbs = ParsePerspectiveMzID($attackerPlayer, $attackerMZ);
    $targetAbs = ParsePerspectiveMzID($attackerPlayer, $targetMZ);

    if(is_array($attackerAbs)
        && intval($attackerAbs['owner']) === intval($objOwner)
        && strcasecmp(strval($attackerAbs['location']), $objLocation) === 0
        && intval($attackerAbs['index']) === $objIndex) {
        return 'ATTACKER';
    }
    if(is_array($targetAbs)
        && intval($targetAbs['owner']) === intval($objOwner)
        && strcasecmp(strval($targetAbs['location']), $objLocation) === 0
        && intval($targetAbs['index']) === $objIndex) {
        return 'TARGET';
    }
    return '';
}

function CardCurrentEffects($obj) {
    if(!isset($obj->TurnEffects) || !is_array($obj->TurnEffects)) return '';
    return implode(',', array_values($obj->TurnEffects));
}

function CardDisplayEffects($obj) {
    return CardCurrentEffects($obj);
}

function CardHasAbility($obj) {
    if(!is_object($obj) || (isset($obj->removed) && $obj->removed)) return 0;

    if(GetCurrentPhase() !== 'MAIN') return 0;
    if(HasTurnEffect($obj, 'FROZEN')) return 0;

    $turnPlayer = &GetTurnPlayer();
    $isResponseWindow = HasPendingAttackResponse();
    $actingPlayer = $isResponseWindow ? GetPendingAttackResponderPlayer() : intval($turnPlayer);

    $cardID = $obj->CardID ?? '';
    if($isResponseWindow && !ObjectHasTimingTag($obj, 'Response')) return 0;

    $owner = ResolveObjectOwner($obj);
    if($owner === null || intval($owner) !== intval($actingPlayer)) return 0;

    $myQueue = &GetDecisionQueue($turnPlayer);
    $theirQueue = &GetDecisionQueue($turnPlayer == 1 ? 2 : 1);
    if(count($myQueue) > 0 || count($theirQueue) > 0) return 0;

    $location = isset($obj->Location) ? $obj->Location : '';
    $mzIndex = intval($obj->mzIndex ?? -1);

    // Garden cards surface Activate when they can currently declare an attack.
    if($location === 'Garden' && $mzIndex >= 0) {
        $mzID = 'myGarden-' . $mzIndex;
        if(!$isResponseWindow && CanAttackWith($actingPlayer, $mzID)) return 1;

        if($cardID !== '') {
            $abilityCount = 0;
            foreach(GetObjectMacroCardIDCandidates($obj) as $candidateCardID) {
                $abilityCount = max($abilityCount, intval(CardActivateAbilityCount($candidateCardID)));
            }
            for($abilityIndex = 0; $abilityIndex < $abilityCount; ++$abilityIndex) {
                if(CanActivateAbilityRuntime($actingPlayer, $mzID, $abilityIndex) && CanActivateAbilityWithCopiedText($actingPlayer, $mzID, $abilityIndex)) {
                    return 1;
                }
            }
        }
    }

    // Gate surfaces Activate when it is usable and an untapped Alley unit exists to portal.
    if(!$isResponseWindow && $location === 'Gate' && $mzIndex >= 0 && CanUseGateRuntime($actingPlayer, 'myGate-' . $mzIndex, '')) {
        if(!empty(GetPortalCandidates($actingPlayer))) return 1;
    }

    // Alley cards can expose Activate abilities (e.g. Alpine Prowler sacrifice ability).
    if($location === 'Alley' && $mzIndex >= 0) {
        if($cardID !== '') {
            $abilityCount = 0;
            foreach(GetObjectMacroCardIDCandidates($obj) as $candidateCardID) {
                $abilityCount = max($abilityCount, intval(CardActivateAbilityCount($candidateCardID)));
            }
            for($abilityIndex = 0; $abilityIndex < $abilityCount; ++$abilityIndex) {
                if(CanActivateAbilityRuntime($actingPlayer, 'myAlley-' . $mzIndex, $abilityIndex) && CanActivateAbilityWithCopiedText($actingPlayer, 'myAlley-' . $mzIndex, $abilityIndex)) {
                    return 1;
                }
            }
        }
    }

    return 0;
}

function CanActivateAbilityRuntime($player, $mzID, $abilityIndex = 0) {
    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return false;

    $cardID = $obj->CardID ?? '';
    $location = $obj->Location ?? '';
    if(HasTurnEffect($obj, 'FROZEN')) return false;

    // Alpine Prowler: [In Alley Only Ability][Main]
    if($cardID === 'S1-STT01-005_Alpine-Prowler_E_C_die') {
        if($location !== 'Alley') return false;
        if(GetCurrentPhase() !== 'MAIN') return false;
        if(HasPendingAttackResponse()) return false;
        return intval($player) === intval(GetTurnPlayer());
    }

    return true;
}

function IsAttackTargetLegal($player, $targetMZ) {
    if(!is_string($targetMZ) || $targetMZ === '') return false;

    $opponent = ($player == 1) ? 2 : 1;
    $attackerCardID = '';
    $attackerObj = null;
    $combatTarget = DecisionQueueController::GetVariable('CombatTarget');
    if(is_string($combatTarget) && $combatTarget !== '') {
        $attackerObj = GetZoneObject($combatTarget);
        if($attackerObj !== null && !(isset($attackerObj->removed) && $attackerObj->removed)) {
            $attackerCardID = $attackerObj->CardID ?? '';
        }
    }
    $parts = explode('-', $targetMZ);
    $zone = $parts[0] ?? '';
    $index = intval($parts[1] ?? -1);

    $garden = &GetGarden($opponent);
    $guardTargets = [];
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        if(intval($garden[$i]->Status ?? 2) !== 1) continue;
        if(IsDefenderEntity($garden[$i]) || IsTauntEntity($garden[$i])) {
            $guardTargets[] = $i;
        }
    }

    if($zone === 'theirAlley') {
        if(!CanAttackOpponentAlleyUntapped($attackerCardID, $attackerObj)) return false;
        if(!empty($guardTargets)) return false;

        $alley = &GetAlley($opponent);
        if($index < 0 || $index >= count($alley)) return false;
        if(isset($alley[$index]->removed) && $alley[$index]->removed) return false;
        return CardType($alley[$index]->CardID ?? '') === 'ENTITY';
    }

    if($zone !== 'theirGarden') return false;
    if($index < 0 || $index >= count($garden)) return false;
    if(isset($garden[$index]->removed) && $garden[$index]->removed) return false;
    if($attackerCardID === 'S1-AZK01-077_Stalking-Assassin_E_C_die') {
        return CardType($garden[$index]->CardID ?? '') === 'LEADER';
    }

    $cardID = $garden[$index]->CardID ?? '';
    if(CardType($cardID) === 'LEADER') {
        if(!empty($guardTargets)) return false;
        return LeaderCurrentHealth($opponent) > 0;
    }

    if(intval($garden[$index]->Status ?? 2) != 1) return false;
    if(!empty($guardTargets)) return in_array($index, $guardTargets, true);
    return true;
}

function CanAttackRuntime($player, $mzID, $targetMZ) {
    if(intval(GetTurnPlayer()) !== intval($player)) return false;
    if(GetCurrentPhase() !== 'MAIN') return false;
    if(!CanAttackWith($player, $mzID)) return false;
    return IsAttackTargetLegal($player, $targetMZ);
}

function DoPlayCard($player, $mzCard, $ignoreCost = false) {
    $sourceObject = &GetZoneObject($mzCard);
    if($sourceObject === null || (isset($sourceObject->removed) && $sourceObject->removed)) {
        return '';
    }

    $zoneName = isset($sourceObject->Location) ? $sourceObject->Location : '';
    if($zoneName !== 'Hand') {
        return '';
    }

    $cardID = $sourceObject->CardID ?? '';
    if($cardID === '') {
        return '';
    }

    SaveActionSnapshot($player);

    $cardType = CardType($cardID);
    $cardCost = EffectivePlayCost($player, $cardID, $sourceObject);
    $benzaiDiscountActive = PlayerLeaderHasTurnEffect($player, 'BENZAI_SLY_NEXT_PLAY_DISCOUNT');
    $ignoreTimingRestriction = strval(DecisionQueueController::GetVariable('IgnorePlayTimingRestriction') ?? '') === '1';
    if($ignoreTimingRestriction) {
        DecisionQueueController::StoreVariable('IgnorePlayTimingRestriction', '0');
    }

    if(!$ignoreTimingRestriction && !CanPlayCardByTiming($player, $cardID)) {
        if(HasPendingAttackResponse()) {
            SetFlashMessage('Only [Response] cards can be played by the defending player during this response window.');
        } else {
            SetFlashMessage('This card cannot be played during the main phase.');
        }
        return '';
    }

    if($cardType === 'WEAPON') {
        $targets = ResolveWeaponEquipTargets($player);
        if(empty($targets)) {
            SetFlashMessage('No valid Garden target to equip this weapon.');
            return '';
        }
    }

    if(!$ignoreCost) {
        if(!CanPayIKZCost($player, $cardCost)) {
            SetFlashMessage('Not enough IKZ to play this card.');
            return '';
        }
        if(!PayIKZCost($player, $cardCost)) {
            return '';
        }
    }

    if($benzaiDiscountActive) {
        RemovePlayerLeaderTurnEffect($player, 'BENZAI_SLY_NEXT_PLAY_DISCOUNT');
    }

    if($cardType === 'ENTITY') {
        DecisionQueueController::AddDecision($player, 'CHOOSEZONE', 'myGarden&myAlley', 1, 'Choose_lane_for_entity');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'PLAY_ENTITY_DEST|' . $mzCard, 1);
        return 'PLAY';
    } else if($cardType === 'WEAPON') {
        $targets = ResolveWeaponEquipTargets($player);
        if(empty($targets)) {
            return '';
        }
        $targetStr = implode('&', $targets);
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', $targetStr, 1, 'Select_Garden_target_to_equip');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'PLAY_WEAPON_TARGET|' . $mzCard, 1);
        return 'PLAY';
    } else if($cardType === 'SPELL') {
        $stack = &GetEffectStack();
        $beforeCount = count($stack);
        SafeMZMove($player, $mzCard, 'EffectStack');
        DecisionQueueController::CleanupRemovedCards();

        $stack = &GetEffectStack();
        $stackIndex = count($stack) - 1;
        if($stackIndex >= $beforeCount) {
            $stackMZ = 'EffectStack-' . $stackIndex;
            OnPlay($player, $stackMZ);
            SafeMZMove($player, $stackMZ, 'myDiscard');
        }
    } else {
        // Weapon and unsupported card types: pay cost, then send to discard for now.
        SafeMZMove($player, $mzCard, 'myDiscard');
    }

    DecisionQueueController::CleanupRemovedCards();
    return 'PLAY';
}

function DoDrawCard($player, $amount) {
    $amount = max(0, intval($amount));
    $deck = &GetDeck($player);
    $hand = &GetHand($player);

    for($i = 0; $i < $amount; ++$i) {
        if(empty($deck)) break;
        $card = array_shift($deck);
        array_push($hand, $card);
    }

    return 'DRAW';
}

$customDQHandlers['TidalInsightReveal'] = function($player, $parts, $lastDecision) {
    if(is_string($lastDecision) && $lastDecision !== '' && $lastDecision !== '-') {
        MZMove($player, $lastDecision, 'myHand');
        DecisionQueueController::CleanupRemovedCards();
    }

    QueueTidalInsightRearrange($player);
};

$customDQHandlers['TidalInsightApply'] = function($player, $parts, $lastDecision) {
    $piles = ['Top' => [], 'Bottom' => []];
    foreach(explode(';', strval($lastDecision)) as $pileStr) {
        $eqPos = strpos($pileStr, '=');
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        if(isset($piles[$pileName])) {
            $piles[$pileName] = ($cardsStr !== '') ? explode(',', $cardsStr) : [];
        }
    }

    $tempStart = intval(DecisionQueueController::GetVariable('P' . intval($player) . '_TidalInsightTempStart'));
    $tempZone = &GetTempZone($player);
    $tempObjs = [];
    for($i = $tempStart; $i < count($tempZone); ++$i) {
        if(isset($tempZone[$i]->removed) && $tempZone[$i]->removed) continue;
        $tempObjs[] = $tempZone[$i];
    }

    foreach($tempObjs as $obj) {
        $obj->Remove();
    }
    DecisionQueueController::CleanupRemovedCards();

    $deck = &GetDeck($player);
    $topCards = $piles['Top'];
    for($i = count($topCards) - 1; $i >= 0; --$i) {
        array_unshift($deck, new Deck($topCards[$i], 'Deck', $player));
    }
    foreach($piles['Bottom'] as $cardID) {
        $deck[] = new Deck($cardID, 'Deck', $player);
    }

    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
        $deck[$i]->BuildIndex();
    }
};

$customDQHandlers['MIZUKI_SEARCH_REVEAL'] = function($player, $parts, $lastDecision) {
    $chosenMZ = is_string($lastDecision) ? $lastDecision : '';
    if($chosenMZ !== '' && $chosenMZ !== '-') {
        $obj = GetZoneObject($chosenMZ);
        if($obj !== null && !(isset($obj->removed) && $obj->removed)) {
            $chosenCardID = $obj->CardID ?? '';
            MZMove($player, $chosenMZ, 'myHand');
            DecisionQueueController::CleanupRemovedCards();

            $hand = &GetHand($player);
            if(!empty($hand)) {
                $varPrefix = GetMizukiSearchVarPrefix($player);
                DecisionQueueController::StoreVariable($varPrefix . 'ChosenHandMZ', 'myHand-' . (count($hand) - 1));
                DecisionQueueController::StoreVariable($varPrefix . 'ChosenCardID', $chosenCardID);
            }
        }
    }

    QueueMizukiSearchRearrange($player);
};

$customDQHandlers['MIZUKI_SEARCH_REARRANGE'] = function($player, $parts, $lastDecision) {
    $deck = &GetDeck($player);
    $tempZone = &GetTempZone($player);
    $piles = ['Top' => [], 'Bottom' => []];

    foreach(explode(';', strval($lastDecision)) as $pileStr) {
        $eqPos = strpos($pileStr, '=');
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        if(isset($piles[$pileName])) {
            $piles[$pileName] = ($cardsStr !== '') ? explode(',', $cardsStr) : [];
        }
    }

    $tempStart = intval(DecisionQueueController::GetVariable(GetMizukiSearchVarPrefix($player) . 'TempStart'));
    for($i = $tempStart; $i < count($tempZone); ++$i) {
        if(isset($tempZone[$i]->removed) && $tempZone[$i]->removed) continue;
        $tempZone[$i]->Remove();
    }
    DecisionQueueController::CleanupRemovedCards();

    foreach(array_merge($piles['Bottom'], $piles['Top']) as $cardID) {
        if(!is_string($cardID) || $cardID === '') continue;
        $deck[] = new Deck($cardID, 'Deck', $player);
    }

    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
        $deck[$i]->BuildIndex();
    }

    QueueMizukiSearchPlayPrompt($player);
};

$customDQHandlers['MIZUKI_SEARCH_PLAY_ALLEY'] = function($player, $parts, $lastDecision) {
    if($lastDecision !== 'YES') return;

    $varPrefix = GetMizukiSearchVarPrefix($player);
    $chosenHandMZ = strval(DecisionQueueController::GetVariable($varPrefix . 'ChosenHandMZ') ?? '');
    $chosenCardID = strval(DecisionQueueController::GetVariable($varPrefix . 'ChosenCardID') ?? '');
    if($chosenHandMZ === '' || $chosenCardID === '') return;
    if(CardType($chosenCardID) !== 'ENTITY') return;

    $obj = GetZoneObject($chosenHandMZ);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return;
    if(($obj->CardID ?? '') !== $chosenCardID) return;

    ResolveEntityPlayFromHand($player, $chosenHandMZ, 'myAlley');
    DecisionQueueController::CleanupRemovedCards();
};

function ZoneSearchCardMatches($cardID, $matchKind, $matchValue, $excludeCardID = '', $matchZoneName = '') {
    if(!is_string($cardID) || $cardID === '') return false;
    if($excludeCardID !== '' && $cardID === $excludeCardID) return false;

    switch($matchKind) {
        case 'subtype':
            return CardHasSubtypeInZone($cardID, $matchValue, $matchZoneName);
        case 'type':
            return CardType($cardID) === $matchValue;
        default:
            return false;
    }
}

function ZoneSearch($player, $zoneName, $matchKind, $matchValue, $excludeCardID = '', $matchZoneName = '') {
    $player = intval($player);
    if(!is_string($zoneName) || $zoneName === '') return [];
    if(!is_string($matchKind) || $matchKind === '') return [];
    if(!is_string($matchValue) || $matchValue === '') return [];
    if(!is_string($matchZoneName) || $matchZoneName === '') $matchZoneName = $zoneName;

    $zone = null;
    switch($zoneName) {
        case 'myDeck':
            $zone = &GetDeck($player);
            break;
        case 'theirDeck':
            $zone = &GetDeck($player === 1 ? 2 : 1);
            break;
        case 'myHand':
            $zone = &GetHand($player);
            break;
        case 'theirHand':
            $zone = &GetHand($player === 1 ? 2 : 1);
            break;
        case 'myDiscard':
            $zone = &GetDiscard($player);
            break;
        case 'theirDiscard':
            $zone = &GetDiscard($player === 1 ? 2 : 1);
            break;
        case 'myGarden':
            $zone = &GetGarden($player);
            break;
        case 'theirGarden':
            $zone = &GetGarden($player === 1 ? 2 : 1);
            break;
        case 'myAlley':
            $zone = &GetAlley($player);
            break;
        case 'theirAlley':
            $zone = &GetAlley($player === 1 ? 2 : 1);
            break;
        case 'myTempZone':
            $zone = &GetTempZone($player);
            break;
        default:
            return [];
    }

    $matches = [];
    for($i = 0; $i < count($zone); ++$i) {
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        $cardID = $zone[$i]->CardID ?? '';
        if(!ZoneSearchCardMatches($cardID, $matchKind, $matchValue, $excludeCardID, $matchZoneName)) continue;
        $matches[] = $zoneName . '-' . $i;
    }

    return $matches;
}

function GetBottomDeckSearcherVarPrefix($player) {
    return 'P' . intval($player) . '_BottomDeckSearcher_';
}

function BeginBottomDeckSearcher($player, $lookCount, $matchKind, $matchValue, $chooseTooltip = 'Choose_a_card_to_add_to_your_hand', $excludeCardID = '') {
    $deck = &GetDeck($player);
    if(empty($deck)) return;

    $lookCount = min(max(0, intval($lookCount)), count($deck));
    if($lookCount <= 0) return;

    $tempZone = &GetTempZone($player);
    $tempStart = count($tempZone);
    for($i = 0; $i < $lookCount; ++$i) {
        $tempZone[] = array_shift($deck);
    }

    $varPrefix = GetBottomDeckSearcherVarPrefix($player);
    DecisionQueueController::StoreVariable($varPrefix . 'TempStart', strval($tempStart));

    $candidates = [];
    foreach(ZoneSearch($player, 'myTempZone', $matchKind, $matchValue, $excludeCardID, 'myDeck') as $mzID) {
        $parts = explode('-', $mzID);
        $index = intval($parts[1] ?? -1);
        if($index < $tempStart) continue;
        $candidates[] = $mzID;
    }

    if(empty($candidates)) {
        QueueBottomDeckSearcherBottom($player);
        return;
    }

    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', implode('&', $candidates), 1, $chooseTooltip);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'BOTTOM_DECK_SEARCHER_REVEAL', 1);
}

function QueueBottomDeckSearcherBottom($player) {
    $varPrefix = GetBottomDeckSearcherVarPrefix($player);
    $tempStart = intval(DecisionQueueController::GetVariable($varPrefix . 'TempStart'));
    $tempZone = &GetTempZone($player);
    $remaining = [];
    for($i = $tempStart; $i < count($tempZone); ++$i) {
        if(isset($tempZone[$i]->removed) && $tempZone[$i]->removed) continue;
        $cardID = $tempZone[$i]->CardID ?? '';
        if(!is_string($cardID) || $cardID === '') continue;
        $remaining[] = $cardID;
    }
    if(empty($remaining)) return;

    $param = 'Bottom=' . implode(',', $remaining);
    DecisionQueueController::AddDecision($player, 'MZREARRANGE', $param, 1, 'Put_remaining_on_bottom_of_deck_in_any_order');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'BOTTOM_DECK_SEARCHER_BOTTOM', 1);
}

function GetMizukiSearchVarPrefix($player) {
    return 'P' . intval($player) . '_MizukiSearch_';
}

function BeginMizukiSearch($player) {
    $deck = &GetDeck($player);
    if(empty($deck)) {
        SetFlashMessage('No Water card with cost 2 or less was found.');
        return;
    }

    $lookCount = min(3, count($deck));
    if($lookCount <= 0) {
        SetFlashMessage('No Water card with cost 2 or less was found.');
        return;
    }

    $tempZone = &GetTempZone($player);
    $tempStart = count($tempZone);
    for($i = 0; $i < $lookCount; ++$i) {
        $tempZone[] = array_shift($deck);
    }

    $varPrefix = GetMizukiSearchVarPrefix($player);
    DecisionQueueController::StoreVariable($varPrefix . 'TempStart', strval($tempStart));
    DecisionQueueController::StoreVariable($varPrefix . 'ChosenHandMZ', '');
    DecisionQueueController::StoreVariable($varPrefix . 'ChosenCardID', '');

    $candidates = [];
    for($i = $tempStart; $i < count($tempZone); ++$i) {
        if(isset($tempZone[$i]->removed) && $tempZone[$i]->removed) continue;
        $cardID = $tempZone[$i]->CardID ?? '';
        if(CardElement($cardID) !== 'Water') continue;
        if(intval(CardCost($cardID)) > 2) continue;
        $candidates[] = 'myTempZone-' . $i;
    }

    if(empty($candidates)) {
        SetFlashMessage('No Water card with cost 2 or less was found.');
        QueueMizukiSearchRearrange($player);
        return;
    }

    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $candidates), 1, 'Choose_a_Water_card_with_cost_2_or_less_to_reveal_and_add_to_your_hand');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'MIZUKI_SEARCH_REVEAL', 1);
}

function QueueMizukiSearchRearrange($player) {
    $varPrefix = GetMizukiSearchVarPrefix($player);
    $tempStart = intval(DecisionQueueController::GetVariable($varPrefix . 'TempStart'));
    $tempZone = &GetTempZone($player);
    $remaining = [];
    for($i = $tempStart; $i < count($tempZone); ++$i) {
        if(isset($tempZone[$i]->removed) && $tempZone[$i]->removed) continue;
        $cardID = $tempZone[$i]->CardID ?? '';
        if(!is_string($cardID) || $cardID === '') continue;
        $remaining[] = $cardID;
    }

    if(empty($remaining)) {
        QueueMizukiSearchPlayPrompt($player);
        return;
    }

    $param = 'Bottom=' . implode(',', $remaining);
    DecisionQueueController::AddDecision($player, 'MZREARRANGE', $param, 1, 'Put_the_rest_on_the_bottom_of_your_deck_in_any_order');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'MIZUKI_SEARCH_REARRANGE', 1);
}

function QueueMizukiSearchPlayPrompt($player) {
    $varPrefix = GetMizukiSearchVarPrefix($player);
    $chosenCardID = strval(DecisionQueueController::GetVariable($varPrefix . 'ChosenCardID') ?? '');
    $chosenHandMZ = strval(DecisionQueueController::GetVariable($varPrefix . 'ChosenHandMZ') ?? '');
    if($chosenCardID === '' || $chosenHandMZ === '') return;
    if(CardType($chosenCardID) !== 'ENTITY') return;

    $obj = GetZoneObject($chosenHandMZ);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return;
    if(($obj->CardID ?? '') !== $chosenCardID) return;

    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, 'Play_the_revealed_entity_to_Alley?');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'MIZUKI_SEARCH_PLAY_ALLEY', 1);
}

function GetOpponentGardenEntityTargetsUpToCost($player, $maxCost, $excludeMZ = '') {
    $player = intval($player);
    $opponent = $player === 1 ? 2 : 1;
    $theirGarden = &GetGarden($opponent);
    $targets = [];
    for($i = 0; $i < count($theirGarden); ++$i) {
        if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
        $targetMZ = 'theirGarden-' . $i;
        if($excludeMZ !== '' && $targetMZ === $excludeMZ) continue;
        $cardID = $theirGarden[$i]->CardID ?? '';
        if(CardType($cardID) !== 'ENTITY') continue;
        if(intval(CardCost($cardID)) > intval($maxCost)) continue;
        $targets[] = $targetMZ;
    }
    return $targets;
}

function QueueMizuryuusTorrentApplyBottomOrder($player, $firstTargetMZ, $secondTargetMZ) {
    $firstObj = GetZoneObject($firstTargetMZ);
    $secondObj = GetZoneObject($secondTargetMZ);
    if($firstObj === null || $secondObj === null) return;
    if((isset($firstObj->removed) && $firstObj->removed) || (isset($secondObj->removed) && $secondObj->removed)) return;

    $firstCardID = $firstObj->CardID ?? '';
    $secondCardID = $secondObj->CardID ?? '';
    if(!is_string($firstCardID) || $firstCardID === '') return;
    if(!is_string($secondCardID) || $secondCardID === '') return;

    DecisionQueueController::StoreVariable('MizuryuusTorrentFirstTargetMZ', $firstTargetMZ);
    DecisionQueueController::StoreVariable('MizuryuusTorrentSecondTargetMZ', $secondTargetMZ);

    $param = 'Bottom=' . $firstCardID . ',' . $secondCardID;
    DecisionQueueController::AddDecision($player, 'MZREARRANGE', $param, 1, 'Choose_the_order_to_put_the_selected_entities_on_the_bottom_of_their_owners_deck');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'MIZURYUUS_TORRENT_APPLY', 1);
}

function CanActivateAbilityWithCopiedText($player, $mzID, $abilityIndex = 0) {
    global $activateAbilityPrereqs;

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return false;

    $abilityIndex = max(0, intval($abilityIndex));
    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $hasCandidateAbility = false;
    foreach($cardIDCandidates as $candidateCardID) {
        if(intval(CardActivateAbilityCount($candidateCardID)) <= $abilityIndex) continue;
        $hasCandidateAbility = true;
        $abilityKey = $candidateCardID . ':' . $abilityIndex;
        if(!isset($activateAbilityPrereqs[$abilityKey])) return true;
        if($activateAbilityPrereqs[$abilityKey]($player, $mzID, $abilityIndex)) return true;
    }

    return !$hasCandidateAbility ? false : false;
}

$customDQHandlers['BOTTOM_DECK_SEARCHER_REVEAL'] = function($player, $parts, $lastDecision) {
    if(is_string($lastDecision) && $lastDecision !== '' && $lastDecision !== '-') {
        MZMove($player, $lastDecision, 'myHand');
        DecisionQueueController::CleanupRemovedCards();
    }

    QueueBottomDeckSearcherBottom($player);
};

$customDQHandlers['BOTTOM_DECK_SEARCHER_BOTTOM'] = function($player, $parts, $lastDecision) {
    $deck = &GetDeck($player);
    $tempZone = &GetTempZone($player);
    $piles = ['Top' => [], 'Bottom' => []];

    foreach(explode(';', strval($lastDecision)) as $pileStr) {
        $eqPos = strpos($pileStr, '=');
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        if(isset($piles[$pileName])) {
            $piles[$pileName] = ($cardsStr !== '') ? explode(',', $cardsStr) : [];
        }
    }

    $tempStart = intval(DecisionQueueController::GetVariable(GetBottomDeckSearcherVarPrefix($player) . 'TempStart'));
    for($i = $tempStart; $i < count($tempZone); ++$i) {
        if(isset($tempZone[$i]->removed) && $tempZone[$i]->removed) continue;
        $tempZone[$i]->Remove();
    }
    DecisionQueueController::CleanupRemovedCards();

    foreach(array_merge($piles['Bottom'], $piles['Top']) as $cardID) {
        if(!is_string($cardID) || $cardID === '') continue;
        $deck[] = new Deck($cardID, 'Deck', $player);
    }

    for($i = 0; $i < count($deck); ++$i) {
        $deck[$i]->mzIndex = $i;
        $deck[$i]->BuildIndex();
    }
};

$customDQHandlers['MIZURYUUS_TORRENT_FIRST'] = function($player, $parts, $lastDecision) {
    $firstTargetMZ = is_string($lastDecision) ? $lastDecision : '';
    if($firstTargetMZ === '' || $firstTargetMZ === '-') return;

    $firstObj = GetZoneObject($firstTargetMZ);
    if($firstObj === null || (isset($firstObj->removed) && $firstObj->removed)) return;
    $firstCardID = $firstObj->CardID ?? '';
    $remainingCost = 5 - intval(CardCost($firstCardID));

    DecisionQueueController::StoreVariable('MizuryuusTorrentFirstTargetMZ', $firstTargetMZ);
    if($remainingCost <= 0) {
        MZMove($player, $firstTargetMZ, 'theirDeck');
        DecisionQueueController::CleanupRemovedCards();
        return;
    }

    $secondTargets = GetOpponentGardenEntityTargetsUpToCost($player, $remainingCost, $firstTargetMZ);
    if(empty($secondTargets)) {
        MZMove($player, $firstTargetMZ, 'theirDeck');
        DecisionQueueController::CleanupRemovedCards();
        return;
    }

    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', implode('&', $secondTargets), 1, 'Choose_a_second_entity_to_put_on_bottom');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'MIZURYUUS_TORRENT_SECOND', 1);
};

$customDQHandlers['MIZURYUUS_TORRENT_SECOND'] = function($player, $parts, $lastDecision) {
    $firstTargetMZ = strval(DecisionQueueController::GetVariable('MizuryuusTorrentFirstTargetMZ') ?? '');
    if($firstTargetMZ === '') return;

    $secondTargetMZ = is_string($lastDecision) ? $lastDecision : '';
    if($secondTargetMZ === '' || $secondTargetMZ === '-') {
        MZMove($player, $firstTargetMZ, 'theirDeck');
        DecisionQueueController::CleanupRemovedCards();
        return;
    }

    QueueMizuryuusTorrentApplyBottomOrder($player, $firstTargetMZ, $secondTargetMZ);
};

$customDQHandlers['MIZURYUUS_TORRENT_APPLY'] = function($player, $parts, $lastDecision) {
    $firstTargetMZ = strval(DecisionQueueController::GetVariable('MizuryuusTorrentFirstTargetMZ') ?? '');
    $secondTargetMZ = strval(DecisionQueueController::GetVariable('MizuryuusTorrentSecondTargetMZ') ?? '');
    if($firstTargetMZ === '' || $secondTargetMZ === '') return;

    $firstParts = explode('-', $firstTargetMZ);
    $secondParts = explode('-', $secondTargetMZ);
    $firstIndex = intval($firstParts[1] ?? -1);
    $secondIndex = intval($secondParts[1] ?? -1);
    if($firstIndex < 0 || $secondIndex < 0) return;

    $selected = [];
    $firstObj = GetZoneObject($firstTargetMZ);
    if($firstObj !== null && !(isset($firstObj->removed) && $firstObj->removed)) {
        $selected[] = ['label' => 'FIRST', 'cardID' => strval($firstObj->CardID ?? ''), 'index' => $firstIndex];
    }
    $secondObj = GetZoneObject($secondTargetMZ);
    if($secondObj !== null && !(isset($secondObj->removed) && $secondObj->removed)) {
        $selected[] = ['label' => 'SECOND', 'cardID' => strval($secondObj->CardID ?? ''), 'index' => $secondIndex];
    }
    if(count($selected) !== 2) return;

    $bottomCards = [];
    foreach(explode(';', strval($lastDecision)) as $pileStr) {
        $eqPos = strpos($pileStr, '=');
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        if($pileName !== 'Bottom') continue;
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        $bottomCards = ($cardsStr !== '') ? explode(',', $cardsStr) : [];
        break;
    }
    if(count($bottomCards) !== 2) return;

    $ordered = [];
    foreach($bottomCards as $cardID) {
        for($i = 0; $i < count($selected); ++$i) {
            if($selected[$i]['cardID'] !== $cardID) continue;
            $ordered[] = $selected[$i];
            array_splice($selected, $i, 1);
            break;
        }
    }
    if(count($ordered) !== 2) return;

    $firstMove = $ordered[0];
    $secondMove = $ordered[1];
    MZMove($player, 'theirGarden-' . $firstMove['index'], 'theirDeck');
    DecisionQueueController::CleanupRemovedCards();

    $secondCurrentIndex = $secondMove['index'];
    if($firstMove['index'] < $secondMove['index']) {
        --$secondCurrentIndex;
    }
    MZMove($player, 'theirGarden-' . $secondCurrentIndex, 'theirDeck');
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers['LOTUS_OF_REFLECTION_CHOOSE'] = function($player, $parts, $lastDecision) {
    $chosenMZ = is_string($lastDecision) ? $lastDecision : '';
    if($chosenMZ === '' || $chosenMZ === '-') {
        QueueBottomDeckSearcherBottom($player);
        return;
    }

    $chosenObj = GetZoneObject($chosenMZ);
    if($chosenObj === null || (isset($chosenObj->removed) && $chosenObj->removed)) {
        QueueBottomDeckSearcherBottom($player);
        return;
    }

    DecisionQueueController::StoreVariable('LotusOfReflectionChosenMZ', $chosenMZ);
    DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|Add_to_hand&Play_it', 1, 'Choose_what_to_do_with_the_revealed_card');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'LOTUS_OF_REFLECTION_MODE', 1);
};

$customDQHandlers['LOTUS_OF_REFLECTION_MODE'] = function($player, $parts, $lastDecision) {
    $chosenMZ = strval(DecisionQueueController::GetVariable('LotusOfReflectionChosenMZ') ?? '');
    if($chosenMZ === '') {
        QueueBottomDeckSearcherBottom($player);
        return;
    }

    $selectedIndex = intval(explode(',', strval($lastDecision))[0] ?? 0);
    MZMove($player, $chosenMZ, 'myHand');
    DecisionQueueController::CleanupRemovedCards();

    if($selectedIndex === 0) {
        QueueBottomDeckSearcherBottom($player);
        return;
    }

    $hand = &GetHand($player);
    if(empty($hand)) {
        QueueBottomDeckSearcherBottom($player);
        return;
    }

    $playedMZ = 'myHand-' . (count($hand) - 1);
    DecisionQueueController::StoreVariable('IgnorePlayTimingRestriction', '1');
    DoPlayCard($player, $playedMZ, true);
    QueueBottomDeckSearcherBottom($player);
};

$customDQHandlers['RAIKOS_WRATH_SHIN_CHOICE'] = function($player, $parts, $lastDecision) {
    $choiceMap = strval(DecisionQueueController::GetVariable('P' . intval($player) . '_RaikosWrathShinChoiceMap') ?? '');
    $mappedChoices = array_values(array_filter(explode(',', $choiceMap), function($value) {
        return $value !== '';
    }));
    if(empty($mappedChoices)) return;

    $selectedIndex = intval(explode(',', strval($lastDecision))[0] ?? 0);
    if($selectedIndex < 0 || $selectedIndex >= count($mappedChoices)) return;

    $chosenMode = $mappedChoices[$selectedIndex];
    if($chosenMode === 'charge') {
        $selfMZ = strval(DecisionQueueController::GetVariable('P' . intval($player) . '_RaikosWrathShinSelfMZ') ?? '');
        if($selfMZ === '') return;
        $selfObj = &GetZoneObject($selfMZ);
        if($selfObj === null || (isset($selfObj->removed) && $selfObj->removed)) return;
        AddUniqueTurnEffect($selfObj, 'CHARGE');
        return;
    }

    if($chosenMode !== 'shock') return;
    $targets = GetOpponentGardenEntityTargetsUpToCost($player, 5);
    if(empty($targets)) return;
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $targets), 1, 'Choose_an_entity_to_become_Shocked');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'RAIKOS_WRATH_SHIN_SHOCK', 1);
};

$customDQHandlers['RAIKOS_WRATH_SHIN_SHOCK'] = function($player, $parts, $lastDecision) {
    $chosen = is_string($lastDecision) ? $lastDecision : '';
    if($chosen === '' || $chosen === '-') return;
    $targetObj = &GetZoneObject($chosen);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    AddUniqueTurnEffect($targetObj, 'SHOCKED');
};

$customDQHandlers['KIRA_SWAP_PENDING_ATTACK'] = function($player, $parts, $lastDecision) {
    if(!is_string($lastDecision) || $lastDecision === '' || $lastDecision === '-') return;
    TryKiraSwapForPendingAttack($player, $lastDecision);
};

$customDQHandlers['MINA_START_TURN_DAMAGE'] = function($player, $parts, $lastDecision) {
    $sourceMZ = $parts[0] ?? '';
    if(!is_string($sourceMZ) || $sourceMZ === '') return;

    $sourceObj = GetZoneObject($sourceMZ);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
    if(($sourceObj->CardID ?? '') !== 'S1-AZK01-046_Mina-the-Geomancer_E_UC_die') return;
    if(($sourceObj->Location ?? '') !== 'Garden') return;

    if(!is_string($lastDecision) || $lastDecision === '' || $lastDecision === '-') return;

    $targetParts = explode('-', $lastDecision);
    $targetZone = $targetParts[0] ?? '';
    if($targetZone !== 'myGarden' && $targetZone !== 'theirGarden') return;

    $targetPlayer = ($targetZone === 'myGarden') ? $player : ($player == 1 ? 2 : 1);
    DealDamageToLeader($targetPlayer, 1);
};

$customDQHandlers['SAEKO_SELF_DAMAGE'] = function($player, $parts, $lastDecision) {
    $sourceMZ = $parts[0] ?? '';
    if(!is_string($sourceMZ) || $sourceMZ === '') return;

    $sourceObj = GetZoneObject($sourceMZ);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
    if(($sourceObj->CardID ?? '') !== 'S1-AZK01-057_Lounge-Siren-Saeko_E_C_die') return;
    if(($sourceObj->Location ?? '') !== 'Garden') return;

    $chosen = is_string($lastDecision) ? $lastDecision : '';
    if($chosen === '' || $chosen === '-') return;
    DealDamageToEntityTarget($player, $chosen, 1, false);

    $opponent = $player == 1 ? 2 : 1;
    $theirTargets = [];
    $theirGarden = &GetGarden($opponent);
    for($i = 0; $i < count($theirGarden); ++$i) {
        if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
        if(CardType($theirGarden[$i]->CardID ?? '') !== 'ENTITY') continue;
        $theirTargets[] = 'theirGarden-' . $i;
    }
    if(empty($theirTargets)) return;

    $theirTargetStr = implode('&', $theirTargets);
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', $theirTargetStr, 1, 'Choose_an_entity_in_your_opponents_Garden_to_deal_1_damage_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SAEKO_OPP_DAMAGE|' . $sourceMZ, 1);
};

$customDQHandlers['SAEKO_OPP_DAMAGE'] = function($player, $parts, $lastDecision) {
    $sourceMZ = $parts[0] ?? '';
    if(!is_string($sourceMZ) || $sourceMZ === '') return;

    $sourceObj = GetZoneObject($sourceMZ);
    if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
    if(($sourceObj->CardID ?? '') !== 'S1-AZK01-057_Lounge-Siren-Saeko_E_C_die') return;
    if(($sourceObj->Location ?? '') !== 'Garden') return;

    $chosen = is_string($lastDecision) ? $lastDecision : '';
    if($chosen === '' || $chosen === '-') return;
    DealDamageToEntityTarget($player, $chosen, 1, false);
};

$customDQHandlers['HOREN_OF_TWO_PATHS_CHOICE'] = function($player, $parts, $lastDecision) {
    $sourcePerspectiveMZ = $parts[0] ?? '';
    $owner = intval($parts[1] ?? 0);
    if(!is_string($sourcePerspectiveMZ) || $sourcePerspectiveMZ === '') return;
    if($owner !== 1 && $owner !== 2) return;

    $selectedIndex = intval(explode(',', strval($lastDecision))[0] ?? 0);
    $sourceMZ = FlipZonePerspective($sourcePerspectiveMZ);
    if(!is_string($sourceMZ) || $sourceMZ === '') return;

    if($selectedIndex === 0) {
        $sourceObj = &GetZoneObject($sourceMZ);
        if($sourceObj === null || (isset($sourceObj->removed) && $sourceObj->removed)) return;
        AddUniqueTurnEffect($sourceObj, 'CHARGE');
        return;
    }

    HealLeader($owner, 2);
};

$customDQHandlers['GIN_AND_TONIKA_CHOICE'] = function($player, $parts, $lastDecision) {
    $owner = intval($parts[0] ?? 0);
    if($owner !== 1 && $owner !== 2) return;

    $selectedIndex = intval(explode(',', strval($lastDecision))[0] ?? 0);
    if($selectedIndex === 0) {
        DoDrawCard($owner, 2);
        return;
    }

    $opponent = $owner == 1 ? 2 : 1;
    DealDamageToLeader($opponent, 3);
};

function OnEnter($player, $mzID) {
    global $enterAbilities, $customDQHandlers;

    $obj = GetZoneObject($mzID);
    if($obj !== null && !(isset($obj->removed) && $obj->removed)) {
        $cardID = $obj->CardID ?? '';
        $location = strval($obj->Location ?? '');
        if($cardID !== '' && isset($enterAbilities) && is_array($enterAbilities)) {
            $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
            $abilityCount = 0;
            if(function_exists('CardEnterCount')) {
                for($i = 0; $i < count($cardIDCandidates); ++$i) {
                    $abilityCount = max($abilityCount, intval(CardEnterCount($cardIDCandidates[$i])));
                }
            }

            if($abilityCount <= 0) {
                for($i = 0; $i < count($cardIDCandidates); ++$i) {
                    $key = $cardIDCandidates[$i] . ':0';
                    if(isset($enterAbilities[$key])) {
                        $enterAbilities[$key]($player);
                        break;
                    }
                }
            }
            else {
                for($i = 0; $i < $abilityCount; ++$i) {
                    for($j = 0; $j < count($cardIDCandidates); ++$j) {
                        $key = $cardIDCandidates[$j] . ':' . $i;
                        if(isset($enterAbilities[$key])) {
                            $enterAbilities[$key]($player);
                            break;
                        }
                    }
                }
            }
        }

        if(isset($customDQHandlers['ON_ENTER']) && is_callable($customDQHandlers['ON_ENTER'])) {
            $customDQHandlers['ON_ENTER']($player, [$mzID, $cardID], null);
        }
    }
    return 'ENTER';
}

function OnEnterGarden($player, $mzID) {
    global $enterGardenAbilities, $customDQHandlers;

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'ENTER_GARDEN';
    }

    if(($obj->Location ?? '') !== 'Garden') {
        return 'ENTER_GARDEN';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID !== '' && isset($enterGardenAbilities) && is_array($enterGardenAbilities)) {
        $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
        $abilityCount = 0;
        if(function_exists('CardEnterGardenCount')) {
            for($i = 0; $i < count($cardIDCandidates); ++$i) {
                $abilityCount = max($abilityCount, intval(CardEnterGardenCount($cardIDCandidates[$i])));
            }
        }

        if($abilityCount <= 0) {
            for($i = 0; $i < count($cardIDCandidates); ++$i) {
                $key = $cardIDCandidates[$i] . ':0';
                if(isset($enterGardenAbilities[$key])) {
                    $enterGardenAbilities[$key]($player);
                    break;
                }
            }
        }
        else {
            for($i = 0; $i < $abilityCount; ++$i) {
                for($j = 0; $j < count($cardIDCandidates); ++$j) {
                    $key = $cardIDCandidates[$j] . ':' . $i;
                    if(isset($enterGardenAbilities[$key])) {
                        $enterGardenAbilities[$key]($player);
                        break;
                    }
                }
            }
        }
    }

    if(isset($customDQHandlers['ON_ENTER_GARDEN']) && is_callable($customDQHandlers['ON_ENTER_GARDEN'])) {
        $customDQHandlers['ON_ENTER_GARDEN']($player, [$mzID, $cardID], null);
    }

    return 'ENTER_GARDEN';
}

function OnCardActivated($player, $mzID) {
    global $customDQHandlers;
    if(isset($customDQHandlers['ON_CARD_ACTIVATED']) && is_callable($customDQHandlers['ON_CARD_ACTIVATED'])) {
        $obj = GetZoneObject($mzID);
        $cardID = ($obj !== null && isset($obj->CardID)) ? $obj->CardID : '';
        $customDQHandlers['ON_CARD_ACTIVATED']($player, [$mzID, $cardID], null);
    }
    return 'CARD_ACTIVATED';
}

function OnPlayCard($player, $mzID) {
    global $onPlayAbilities;
    if(!isset($onPlayAbilities) || !is_array($onPlayAbilities)) {
        return 'ON_PLAY';
    }

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'ON_PLAY';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'ON_PLAY';
    }

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $abilityCount = 0;
    if(function_exists('CardOnPlayCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardOnPlayCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($onPlayAbilities[$key])) {
                $onPlayAbilities[$key]($player);
                break;
            }
        }
        return 'ON_PLAY';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($onPlayAbilities[$key])) {
                $onPlayAbilities[$key]($player);
                break;
            }
        }
    }

    return 'ON_PLAY';
}

function OnEndTurnAbility($player, $mzID) {
    global $endTurnAbilityAbilities;
    if(!isset($endTurnAbilityAbilities) || !is_array($endTurnAbilityAbilities)) {
        return 'END_TURN_ABILITY';
    }

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'END_TURN_ABILITY';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'END_TURN_ABILITY';
    }

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $abilityCount = 0;
    if(function_exists('CardEndTurnAbilityCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardEndTurnAbilityCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($endTurnAbilityAbilities[$key])) {
                $endTurnAbilityAbilities[$key]($player);
                break;
            }
        }
        return 'END_TURN_ABILITY';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($endTurnAbilityAbilities[$key])) {
                $endTurnAbilityAbilities[$key]($player);
                break;
            }
        }
    }

    return 'END_TURN_ABILITY';
}

function OnWhenAttacked($player, $mzID, $attackerMZ) {
    global $whenAttackedAbilities;
    if(!isset($whenAttackedAbilities) || !is_array($whenAttackedAbilities)) {
        return 'WHEN_ATTACKED';
    }

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'WHEN_ATTACKED';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'WHEN_ATTACKED';
    }

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $abilityCount = 0;
    if(function_exists('CardWhenAttackedCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardWhenAttackedCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($whenAttackedAbilities[$key])) {
                $whenAttackedAbilities[$key]($player);
                break;
            }
        }
        return 'WHEN_ATTACKED';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($whenAttackedAbilities[$key])) {
                $whenAttackedAbilities[$key]($player);
                break;
            }
        }
    }

    OfferKiraSwapOnAttack($player);

    return 'WHEN_ATTACKED';
}

function TriggerEndTurnAbilitiesForZone($player, &$zone) {
    for($i = count($zone) - 1; $i >= 0; --$i) {
        if(!is_object($zone[$i])) continue;
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;

        $mzID = '';
        $mzID = $zone[$i]->GetMzID();
        if($mzID === '') continue;

        EndTurnAbility($player, $mzID);
    }
}

function SacrificeRushfireMarkedEntities($player, &$zone) {
    for($i = count($zone) - 1; $i >= 0; --$i) {
        if(!is_object($zone[$i])) continue;
        if(isset($zone[$i]->removed) && $zone[$i]->removed) continue;
        if(!HasTurnEffect($zone[$i], 'RUSHFIRE_SAC_EOT')) continue;

        $mzID = $zone[$i]->GetMzID();
        if($mzID === '') continue;

        SafeMZMove($player, $mzID, 'myDiscard');
        DecisionQueueController::CleanupRemovedCards();
    }
}

function OnUseGateCard($player, $gateMZ) {
    global $useGateAbilities;
    if(!isset($useGateAbilities) || !is_array($useGateAbilities)) {
        return 'USE_GATE';
    }

    $obj = GetZoneObject($gateMZ);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'USE_GATE';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'USE_GATE';
    }

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $abilityCount = 0;
    if(function_exists('CardUseGateCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardUseGateCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($useGateAbilities[$key])) {
                $useGateAbilities[$key]($player);
                break;
            }
        }
        return 'USE_GATE';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($useGateAbilities[$key])) {
                $useGateAbilities[$key]($player);
                break;
            }
        }
    }

    return 'USE_GATE';
}

function OnAttackWithCard($player, $mzID, $targetMZ) {
    global $attackWithAbilities;
    if(!isset($attackWithAbilities) || !is_array($attackWithAbilities)) {
        return 'ATTACK_WITH';
    }

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'ATTACK_WITH';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'ATTACK_WITH';
    }

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $abilityCount = 0;
    if(function_exists('CardAttackWithCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardAttackWithCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($attackWithAbilities[$key])) {
                $attackWithAbilities[$key]($player);
                break;
            }
        }
        return 'ATTACK_WITH';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($attackWithAbilities[$key])) {
                $attackWithAbilities[$key]($player);
                break;
            }
        }
    }

    return 'ATTACK_WITH';
}

function OnAfterAttacking($player, $mzID, $targetMZ) {
    global $afterAttackingAbilities;
    if(!isset($afterAttackingAbilities) || !is_array($afterAttackingAbilities)) {
        return 'AFTER_ATTACKING';
    }

    $obj = GetZoneObject($mzID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'AFTER_ATTACKING';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'AFTER_ATTACKING';
    }

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $abilityCount = 0;
    if(function_exists('CardAfterAttackingCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardAfterAttackingCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($afterAttackingAbilities[$key])) {
                $afterAttackingAbilities[$key]($player);
                break;
            }
        }
        return 'AFTER_ATTACKING';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($afterAttackingAbilities[$key])) {
                $afterAttackingAbilities[$key]($player);
                break;
            }
        }
    }

    return 'AFTER_ATTACKING';
}

function OnDamageTaken($player, $mzID, $amount) {
    global $damageTakenAbilities;
    if(!isset($damageTakenAbilities) || !is_array($damageTakenAbilities)) {
        return 'DAMAGE_TAKEN';
    }

    $resolvedMZID = NormalizeMZForPlayerPerspective($player, $mzID);
    $obj = GetZoneObject($resolvedMZID);
    if($obj === null || (isset($obj->removed) && $obj->removed)) {
        return 'DAMAGE_TAKEN';
    }

    if(intval($amount) <= 0) {
        return 'DAMAGE_TAKEN';
    }

    $cardID = $obj->CardID ?? '';
    if($cardID === '') {
        return 'DAMAGE_TAKEN';
    }

    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    $abilityCount = 0;
    if(function_exists('CardDamageTakenCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardDamageTakenCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($damageTakenAbilities[$key])) {
                $damageTakenAbilities[$key]($player);
                break;
            }
        }
        return 'DAMAGE_TAKEN';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($damageTakenAbilities[$key])) {
                $damageTakenAbilities[$key]($player);
                break;
            }
        }
    }

    return 'DAMAGE_TAKEN';
}

function OnWhenDestroyed($player, $cardID) {
    global $whenDestroyedAbilities;
    if(!isset($whenDestroyedAbilities) || !is_array($whenDestroyedAbilities)) {
        return 'WHEN_DESTROYED';
    }

    if(!is_string($cardID) || $cardID === '') {
        return 'WHEN_DESTROYED';
    }

    $cardIDCandidates = GetMacroCardIDCandidates($cardID);
    $abilityCount = 0;
    if(function_exists('CardWhenDestroyedCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardWhenDestroyedCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($whenDestroyedAbilities[$key])) {
                $whenDestroyedAbilities[$key]($player);
                break;
            }
        }
        return 'WHEN_DESTROYED';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($whenDestroyedAbilities[$key])) {
                $whenDestroyedAbilities[$key]($player);
                break;
            }
        }
    }

    return 'WHEN_DESTROYED';
}

function OnWhenSacrificed($player, $cardID) {
    global $whenSacrificedAbilities;
    if(!isset($whenSacrificedAbilities) || !is_array($whenSacrificedAbilities)) {
        return 'WHEN_SACRIFICED';
    }

    if(!is_string($cardID) || $cardID === '') {
        return 'WHEN_SACRIFICED';
    }

    $cardIDCandidates = GetMacroCardIDCandidates($cardID);
    $abilityCount = 0;
    if(function_exists('CardWhenSacrificedCount')) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $abilityCount = max($abilityCount, intval(CardWhenSacrificedCount($cardIDCandidates[$i])));
        }
    }

    if($abilityCount <= 0) {
        for($i = 0; $i < count($cardIDCandidates); ++$i) {
            $key = $cardIDCandidates[$i] . ':0';
            if(isset($whenSacrificedAbilities[$key])) {
                $whenSacrificedAbilities[$key]($player);
                break;
            }
        }
        return 'WHEN_SACRIFICED';
    }

    for($i = 0; $i < $abilityCount; ++$i) {
        for($j = 0; $j < count($cardIDCandidates); ++$j) {
            $key = $cardIDCandidates[$j] . ':' . $i;
            if(isset($whenSacrificedAbilities[$key])) {
                $whenSacrificedAbilities[$key]($player);
                break;
            }
        }
    }

    return 'WHEN_SACRIFICED';
}

function QueueMinaLeaderChoice($player, $sourceMZ) {
    $leaders = [];
    $myGarden = &GetGarden($player);
    for($i = 0; $i < count($myGarden); ++$i) {
        if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
        if(CardType($myGarden[$i]->CardID ?? '') !== 'LEADER') continue;
        $leaders[] = 'myGarden-' . $i;
    }

    $opponent = $player == 1 ? 2 : 1;
    $theirGarden = &GetGarden($opponent);
    for($i = 0; $i < count($theirGarden); ++$i) {
        if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
        if(CardType($theirGarden[$i]->CardID ?? '') !== 'LEADER') continue;
        $leaders[] = 'theirGarden-' . $i;
    }

    if(empty($leaders)) return;

    $leaderStr = implode('&', $leaders);
    DecisionQueueController::AddDecision($player, 'MZMAYCHOOSE', $leaderStr, 1, 'Choose_a_leader_to_deal_1_damage_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'MINA_START_TURN_DAMAGE|' . $sourceMZ, 1);
}

function TriggerMinaStartTurnAbilities($player) {
    $garden = &GetGarden($player);
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        if(($garden[$i]->CardID ?? '') !== 'S1-AZK01-046_Mina-the-Geomancer_E_UC_die') continue;
        QueueMinaLeaderChoice($player, 'myGarden-' . $i);
    }
}

function QueueSaekoStartTurnDamage($player, $sourceMZ) {
    $myTargets = [];
    $myGarden = &GetGarden($player);
    for($i = 0; $i < count($myGarden); ++$i) {
        if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
        if(CardType($myGarden[$i]->CardID ?? '') !== 'ENTITY') continue;
        $myTargets[] = 'myGarden-' . $i;
    }

    $opponent = $player == 1 ? 2 : 1;
    $theirTargets = [];
    $theirGarden = &GetGarden($opponent);
    for($i = 0; $i < count($theirGarden); ++$i) {
        if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
        if(CardType($theirGarden[$i]->CardID ?? '') !== 'ENTITY') continue;
        $theirTargets[] = 'theirGarden-' . $i;
    }

    if(empty($myTargets) || empty($theirTargets)) return;

    $myTargetStr = implode('&', $myTargets);
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', $myTargetStr, 1, 'Choose_an_entity_in_your_Garden_to_deal_1_damage_to');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SAEKO_SELF_DAMAGE|' . $sourceMZ, 1);
}

function TriggerSaekoStartTurnAbilities($player) {
    $garden = &GetGarden($player);
    for($i = 0; $i < count($garden); ++$i) {
        if(isset($garden[$i]->removed) && $garden[$i]->removed) continue;
        if(($garden[$i]->CardID ?? '') !== 'S1-AZK01-057_Lounge-Siren-Saeko_E_C_die') continue;
        QueueSaekoStartTurnDamage($player, 'myGarden-' . $i);
    }
}

function ResolveAttackCombat($player, $mzCard, $targetMZ) {
    $opponent = ($player == 1) ? 2 : 1;
    $attackerParts = explode('-', $mzCard);
    $attackerZone = $attackerParts[0] ?? '';
    $attackerIndex = intval($attackerParts[1] ?? -1);

    if($attackerZone !== 'myGarden') {
        DecisionQueueController::CleanupRemovedCards();
        return 'ATTACK';
    }

    $myGarden = &GetGarden($player);
    if($attackerIndex < 0 || $attackerIndex >= count($myGarden)
        || (isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed)) {
        DecisionQueueController::CleanupRemovedCards();
        return 'ATTACK';
    }

    $attackerObj = &$myGarden[$attackerIndex];
    $attackerAttack = ResolveEntityAttackValue($player, $attackerObj);
    $attackerIsLeader = (CardType($attackerObj->CardID ?? '') === 'LEADER');

    $targetParts = explode('-', $targetMZ);
    $targetZone = $targetParts[0] ?? '';
    $targetIndex = intval($targetParts[1] ?? -1);
    if($targetZone !== 'theirGarden' && $targetZone !== 'theirAlley') {
        DecisionQueueController::CleanupRemovedCards();
        return 'ATTACK';
    }

    $targetField = ($targetZone === 'theirGarden') ? GetGarden($opponent) : GetAlley($opponent);
    if($targetIndex < 0 || $targetIndex >= count($targetField)
        || (isset($targetField[$targetIndex]->removed) && $targetField[$targetIndex]->removed)) {
        DecisionQueueController::CleanupRemovedCards();
        return 'ATTACK';
    }

    $defenderAttack = 0;
    $defenderHealth = 0;
    $targetIsLeader = false;
    $targetCardID = $targetField[$targetIndex]->CardID ?? '';
    if(CardType($targetCardID) === 'LEADER') {
        $targetIsLeader = true;
        $defenderAttack = max(0, LeaderAttack($opponent));
        $defenderHealth = max(0, LeaderCurrentHealth($opponent));
    } else {
        $targetObj = &$targetField[$targetIndex];
        $defenderAttack = ResolveEntityAttackValue($opponent, $targetObj);
        $defenderHealth = ResolveEntityHealthValue($opponent, $targetObj);
    }

    if($attackerAttack > 0) {
        if($targetIsLeader) {
            $combatDamage = max(0, $attackerAttack - LeaderCombatDamageReduction($opponent));
            if($combatDamage > 0) {
                DealDamageToLeader($opponent, $combatDamage);
                TriggerEquippedWeaponOnCombatDamage($player, $attackerObj, $targetZone, $targetIndex, $combatDamage);
            }
        } else {
            $targetField = ($targetZone === 'theirGarden') ? GetGarden($opponent) : GetAlley($opponent);
            if(isset($targetField[$targetIndex]) && !(isset($targetField[$targetIndex]->removed) && $targetField[$targetIndex]->removed)) {
                if(!HasTurnEffect($targetField[$targetIndex], 'FROZEN')) {
                    $damageDealt = max(0, $attackerAttack - EntityDamageReduction($targetField[$targetIndex]));
                    if($damageDealt > 0) {
                        $targetField[$targetIndex]->Damage = intval($targetField[$targetIndex]->Damage ?? 0) + $damageDealt;
                        $animTarget = ($targetZone === 'theirGarden' ? 'Garden' : 'Alley');
                        QueueDamageAnimation('p' . $opponent . $animTarget . '-' . $targetIndex, $damageDealt, 500, true);
                        TriggerZeroStarterDamageReactions($player, $targetZone . '-' . $targetIndex, $damageDealt, false);
                        $targetOwnerMZ = FlipZonePerspective($targetZone . '-' . $targetIndex);
                        RecordDamageSourceOnObject($targetField[$targetIndex], 'COMBAT:' . NormalizeDamageSourceKey($mzCard));
                        if(is_string($targetOwnerMZ) && $targetOwnerMZ !== '') {
                            DamageTaken($opponent, $targetOwnerMZ, $damageDealt);
                        }
                        TriggerEquippedWeaponOnCombatDamage($player, $attackerObj, $targetZone, $targetIndex, $damageDealt);
                    }
                }
            }
        }
    }

    if($defenderAttack > 0) {
        if($attackerIsLeader) {
            $combatDamage = max(0, $defenderAttack - LeaderCombatDamageReduction($player));
            if($combatDamage > 0) {
                DealDamageToLeader($player, $combatDamage);
            }
        } else {
            $myGarden = &GetGarden($player);
            if(isset($myGarden[$attackerIndex]) && !(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed)) {
                if(!HasTurnEffect($myGarden[$attackerIndex], 'FROZEN')) {
                    $damageDealt = max(0, $defenderAttack - EntityDamageReduction($myGarden[$attackerIndex]));
                    if($damageDealt > 0) {
                        $myGarden[$attackerIndex]->Damage = intval($myGarden[$attackerIndex]->Damage ?? 0) + $damageDealt;
                        QueueDamageAnimation('p' . $player . 'Garden-' . $attackerIndex, $damageDealt, 500, true);
                        TriggerZeroStarterDamageReactions($opponent, 'myGarden-' . $attackerIndex, $damageDealt, false);
                        RecordDamageSourceOnObject($myGarden[$attackerIndex], 'COMBAT:' . NormalizeDamageSourceKey($targetZone . '-' . $targetIndex));
                        DamageTaken($player, 'myGarden-' . $attackerIndex, $damageDealt);
                    }
                }
            }
        }
    }

    if(!$targetIsLeader) {
        $targetField = ($targetZone === 'theirGarden') ? GetGarden($opponent) : GetAlley($opponent);
        if(isset($targetField[$targetIndex]) && !(isset($targetField[$targetIndex]->removed) && $targetField[$targetIndex]->removed)) {
            $targetDamage = intval($targetField[$targetIndex]->Damage ?? 0);
            if($defenderHealth > 0 && $targetDamage >= $defenderHealth) {
                $attackerCardID = $attackerObj->CardID ?? '';
                if($attackerCardID === 'S1-STT03-010_Shroommancer_E_C_die' && !HasTurnEffect($attackerObj, 'SHROOMMANCER_USED')) {
                    HealLeader($player, 1);
                    AddUniqueTurnEffect($attackerObj, 'SHROOMMANCER_USED');
                }
                if($targetZone === 'theirGarden') {
                    TriggerKuraiUntapFromEnemyGardenDestroy($player, $opponent, $targetField[$targetIndex]->CardID ?? '');
                    SafeMZMove($player, 'theirGarden-' . $targetIndex, 'theirDiscard');
                } else {
                    SafeMZMove($player, 'theirAlley-' . $targetIndex, 'theirDiscard');
                }
            }
        }
    }

    $myGarden = &GetGarden($player);
    if(isset($myGarden[$attackerIndex]) && !(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed)) {
        $attackerHealth = ResolveEntityHealthValue($player, $myGarden[$attackerIndex]);
        $attackerDamage = intval($myGarden[$attackerIndex]->Damage ?? 0);
        if($attackerHealth > 0 && CardType($myGarden[$attackerIndex]->CardID ?? '') !== 'LEADER' && $attackerDamage >= $attackerHealth) {
            TriggerKuraiUntapFromEnemyGardenDestroy($opponent, $player, $myGarden[$attackerIndex]->CardID ?? '');
            SafeMZMove($player, 'myGarden-' . $attackerIndex, 'myDiscard');
        }
    }

    AfterAttacking($player, $mzCard, $targetMZ);
    DecisionQueueController::CleanupRemovedCards();
    return 'ATTACK';
}

function DoAttack($player, $mzCard, $targetMZ) {
    $isPendingResolution = false;
    if(HasPendingAttackResponse()) {
        $pendingAttackerPlayer = GetPendingAttackAttackerPlayer();
        $pendingAttackerMZ = DecisionQueueController::GetVariable('PendingAttackAttackerMZ');
        $pendingTargetMZ = DecisionQueueController::GetVariable('PendingAttackTargetMZ');
        $isPendingResolution = intval($pendingAttackerPlayer) === intval($player)
            && is_string($pendingAttackerMZ) && $pendingAttackerMZ === $mzCard
            && is_string($pendingTargetMZ) && $pendingTargetMZ === $targetMZ;
    }

    if(!$isPendingResolution && !CanAttackRuntime($player, $mzCard, $targetMZ)) return '';

    $attackerParts = explode('-', $mzCard);
    $attackerZone = $attackerParts[0] ?? '';
    $attackerIndex = intval($attackerParts[1] ?? -1);

    if($attackerZone !== 'myGarden') return '';
    $myGarden = &GetGarden($player);
    if($attackerIndex < 0 || $attackerIndex >= count($myGarden)) return '';
    if(isset($myGarden[$attackerIndex]->removed) && $myGarden[$attackerIndex]->removed) return '';

    if(!$isPendingResolution) {
        SaveActionSnapshot($player);
    }

    DecisionQueueController::AddDecision($player, 'CUSTOM', 'RESOLVE_ATTACK_COMBAT|' . $mzCard . '|' . $targetMZ, 1);

    return 'ATTACK';
}

function DoActivatedAbility($player, $mzCard, $abilityIndex = 0) {
    global $activateAbilityAbilities;

    $obj = GetZoneObject($mzCard);
    if($obj === null || (isset($obj->removed) && $obj->removed)) return '';

    $cardID = $obj->CardID ?? '';
    if(!is_string($cardID) || $cardID === '') return '';

    $abilityIndex = max(0, intval($abilityIndex));
    $cardIDCandidates = GetObjectMacroCardIDCandidates($obj);
    for($i = 0; $i < count($cardIDCandidates); ++$i) {
        $abilityKey = $cardIDCandidates[$i] . ':' . $abilityIndex;
        if(isset($activateAbilityAbilities[$abilityKey]) && is_callable($activateAbilityAbilities[$abilityKey])) {
            SaveActionSnapshot($player);
            $activateAbilityAbilities[$abilityKey]($player);
            CardActivated($player, $mzCard);
            return 'ACTIVATE_ABILITY';
        }
    }

    return '';
}

function DoUseGate($player, $gateMZ, $entityMZ) {
    $gateObj = &GetZoneObject($gateMZ);
    if($gateObj === null || (isset($gateObj->removed) && $gateObj->removed)) {
        return '';
    }

    if(!CanUseGateRuntime($player, $gateMZ, $entityMZ)) {
        return '';
    }

    if(!isset($gateObj->Status)) {
        $gateObj->Status = 2;
    }
    $gateObj->Status = 1;
    if(!isset($gateObj->TurnEffects) || !is_array($gateObj->TurnEffects)) {
        $gateObj->TurnEffects = [];
    }
    if(!in_array('GATE_USED_THIS_TURN', $gateObj->TurnEffects)) {
        $gateObj->TurnEffects[] = 'GATE_USED_THIS_TURN';
    }

    $portalSucceeded = false;
    if(is_string($entityMZ) && $entityMZ !== '') {
        $entityObj = &GetZoneObject($entityMZ);
        if($entityObj !== null && !(isset($entityObj->removed) && $entityObj->removed) && isset($entityObj->Location) && $entityObj->Location === 'Alley' && intval($entityObj->Status ?? 2) !== 1) {
            $entityCardID = $entityObj->CardID ?? '';
            if($entityCardID !== '') {
                DecisionQueueController::StoreVariable('entityMZCardID', $entityCardID);
            }

            $garden = &GetGarden($player);
            if(CountActiveEntities($garden, true) >= 5) {
                $replaceIndex = FindReplaceableIndex($garden);
                if($replaceIndex >= 0) {
                    SafeMZMove($player, 'myGarden-' . $replaceIndex, 'myDiscard');
                    DecisionQueueController::CleanupRemovedCards();
                }
            }

            SafeMZMove($player, $entityMZ, 'myGarden');
            DecisionQueueController::CleanupRemovedCards();

            $garden = &GetGarden($player);
            $addedIndex = count($garden) - 1;
            if($addedIndex >= 0) {
                $addedObj = &$garden[$addedIndex];
                if($addedObj !== null && !(isset($addedObj->removed) && $addedObj->removed)) {
                    NormalizeFieldOwnership($addedObj, $player);
                    if(!isset($addedObj->TurnEffects) || !is_array($addedObj->TurnEffects)) {
                        $addedObj->TurnEffects = [];
                    }
                    if(!in_array('COOLDOWN', $addedObj->TurnEffects)) {
                        $addedObj->TurnEffects[] = 'COOLDOWN';
                    }
                }
            }

            $portalSucceeded = true;
        }
    }

    if($portalSucceeded) {
        OnUseGateCard($player, $gateMZ);
    }

    return 'GATE';
}

function ActionMap($actionCard) {
    global $playerID;

    $turnPlayer = &GetTurnPlayer();
    $currentPhase = GetCurrentPhase();

    if(!is_string($actionCard) || $actionCard === '') {
        return '';
    }

    $cardArr = explode('-', $actionCard);
    $cardZone = $cardArr[0] ?? '';

    if($cardZone === 'myGarden' && $currentPhase === 'MAIN' && HasPendingAttackResponse() && intval($playerID) === GetPendingAttackResponderPlayer()) {
        if(TryRedirectPendingAttack($playerID, $actionCard)) {
            return 'REDIRECT_ATTACK';
        }
        return '';
    }

    // Ignore FSM clicks while decisions are pending; the UI can surface them again after the queue clears.
    $dqController = new DecisionQueueController();
    if(!$dqController->AllQueuesEmpty()) {
        return '';
    }

    if($cardZone === 'myHand' && $currentPhase === 'MAIN' && HasPendingAttackResponse() && intval($playerID) === GetPendingAttackResponderPlayer()) {
        if(function_exists('PlayCard')) {
            PlayCard($playerID, $actionCard);
            return 'PLAY_RESPONSE';
        }
    }

    if($cardZone === 'myHand' && $currentPhase === 'MAIN' && !HasPendingAttackResponse() && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('PlayCard')) {
            PlayCard($playerID, $actionCard);
            return 'PLAY';
        }
    }

    // Fallback: allow direct card click on Garden cards to initiate attack setup.
    if($cardZone === 'myGarden' && $currentPhase === 'MAIN' && !HasPendingAttackResponse() && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('HandleAttackSetup')) {
            HandleAttackSetup($playerID, $actionCard);
            return 'ATTACK_SETUP';
        }
    }

    // Fallback: allow direct gate click to start portal flow.
    if($cardZone === 'myGate' && $currentPhase === 'MAIN' && !HasPendingAttackResponse() && intval($playerID) === intval($turnPlayer)) {
        if(function_exists('HandleGateUsage')) {
            HandleGateUsage($playerID);
            return 'GATE_SETUP';
        }
    }

    return '';
}

// --- Phase Handlers ---

function OnStartOfTurn($player) {
    DecisionQueueController::StoreVariable('P' . intval($player) . '_EntitiesPlayedThisTurn', '0');
    DecisionQueueController::StoreVariable('P' . intval($player) . '_BobuWardActive', '0');
    global $gCurrentPhase;

    // 1. Untap all cards (field and IKZ)
    WakeAllCards($player);
    UntapAllIKZ($player);

    // 2. Player 2's starting token unlocks at the beginning of their first turn.
    GrantSecondPlayerStartingIKZTokenIfPending($player);

    // Clear any effects that were intentionally carried to the start of this turn.
    ExpireTurnEffects($player, false);

    // 3. Start-of-turn triggered abilities that may queue player choices.
    TriggerMinaStartTurnAbilities($player);
    TriggerSaekoStartTurnAbilities($player);
    
    // 4. Gain 1 IKZ (up to a maximum of 10 in area)
    GainIKZ($player, 1);

    // 5. Draw 1 card (except player 1 on turn 1)
    $turnNumber = GetTurnNumber();
    if(!($player === 1 && $turnNumber === 1)) {
        // Resolve draw immediately so SOT can auto-advance into MAIN.
        DoDrawCard($player, 1);
    }

    // 6. Resolve SOT effects.
    $myGarden = &GetGarden($player);
    for($i = count($myGarden) - 1; $i >= 0; --$i) {
        if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
        if(($myGarden[$i]->CardID ?? '') !== 'S1-STT04-003_Cinderwake-Seer_E_UC_die') continue;
        DealDamageToGardenTarget($player, 'myGarden-' . $i, 1);
    }
    $opponent = ($player == 1) ? 2 : 1;
    $theirGarden = &GetGarden($opponent);
    for($i = count($theirGarden) - 1; $i >= 0; --$i) {
        if(isset($theirGarden[$i]->removed) && $theirGarden[$i]->removed) continue;
        if(($theirGarden[$i]->CardID ?? '') !== 'S1-STT04-003_Cinderwake-Seer_E_UC_die') continue;
        DealDamageToGardenTarget($player, 'theirGarden-' . $i, 1);
    }
}

function OnEndOfTurn($player) {
    // 0. Weapons are temporary and are discarded from all equipped cards.
    DiscardAllEquippedWeapons($player);
    DiscardAllEquippedWeapons($player == 1 ? 2 : 1);

    // 1. Run owned end-turn card abilities before end-of-turn cleanup removes temporary state.
    $garden = &GetGarden($player);
    TriggerEndTurnAbilitiesForZone($player, $garden);
    $alley = &GetAlley($player);
    TriggerEndTurnAbilitiesForZone($player, $alley);
    SacrificeRushfireMarkedEntities($player, $garden);
    SacrificeRushfireMarkedEntities($player, $alley);

    // 2. Reset entity damage
    ResetEntityDamage($player, "myGarden");
    ResetEntityDamage($player, "myAlley");

    // 3. Expire turn effects
    ExpireTurnEffects($player);

    // 4. Tap Gate if it was used
    $gateZone = &GetGate($player);
    if(!empty($gateZone)) {
        $gate = &$gateZone[0];
        // Gate usage is tracked via TurnEffects, which are cleared at EOT
        if(isset($gate->TurnEffects) && in_array("GATE_USED_THIS_TURN", $gate->TurnEffects)) {
            // TurnEffects will be cleared, gate can be used again next turn
        }
    }
}

function ExpireTurnEffects($player, $isEndTurn = true) {
    $garden = &GetGarden($player);
    $alley = &GetAlley($player);
    $gate = &GetGate($player);
    $opponent = intval($player) === 1 ? 2 : 1;
    $theirGarden = &GetGarden($opponent);
    $theirAlley = &GetAlley($opponent);
    $theirGate = &GetGate($opponent);

    if($isEndTurn) {
        FilterZoneTurnEffects($garden, true, true);
        FilterZoneTurnEffects($alley, true, true);
        FilterZoneTurnEffects($gate, true, true);
        FilterZoneTurnEffects($theirGarden, true, true);
        FilterZoneTurnEffects($theirAlley, true, true);
        FilterZoneTurnEffects($theirGate, true, true);
        return;
    }

    FilterZoneTurnEffects($garden, false, true);
    FilterZoneTurnEffects($alley, false, true);
    FilterZoneTurnEffects($gate, false, true);
    FilterZoneTurnEffects($theirGarden, true, false);
    FilterZoneTurnEffects($theirAlley, true, false);
    FilterZoneTurnEffects($theirGate, true, false);
}

// --- DQ Handlers ---
$customDQHandlers["DRAW"] = function($player, $params, $lastDecision) {
    $amount = isset($params[0]) ? intval($params[0]) : 1;
    $deck = &GetDeck($player);
    $hand = &GetHand($player);

    for($i = 0; $i < $amount; ++$i) {
        if(empty($deck)) break;
        $card = array_shift($deck);
        array_push($hand, $card);
    }
};

$customDQHandlers["BOBU_WARD_HEAL"] = function($player, $params, $lastDecision) {
    if($lastDecision !== 'YES') return;
    HealLeader(intval($player), 1);
};

$customDQHandlers["PEKIRO_REDIRECT_DAMAGE"] = function($player, $params, $lastDecision) {
    $sourcePlayer = intval(DecisionQueueController::GetVariable('PekiroPendingSourcePlayer'));
    $originalTargetMZ = strval(DecisionQueueController::GetVariable('PekiroPendingOriginalTargetMZ') ?? '');
    $amount = intval(DecisionQueueController::GetVariable('PekiroPendingAmount'));
    $sourceKey = DecisionQueueController::GetVariable('PekiroPendingSourceKey');
    ClearPekiroPendingDamageVars();

    if($sourcePlayer <= 0 || $amount <= 0 || $originalTargetMZ === '') return;

    $chosen = is_string($lastDecision) ? $lastDecision : '';
    if($chosen === '' || $chosen === '-') {
        DealDamageToFieldTargetInternal($sourcePlayer, $originalTargetMZ, $amount, true, $sourceKey, false);
        return;
    }

    $redirectTargetMZ = intval($player) === intval($sourcePlayer) ? $chosen : FlipZonePerspective($chosen);
    if(!is_string($redirectTargetMZ) || $redirectTargetMZ === '') {
        DealDamageToFieldTargetInternal($sourcePlayer, $originalTargetMZ, $amount, true, $sourceKey, false);
        return;
    }

    $targetObj = GetZoneObject($chosen);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) {
        DealDamageToFieldTargetInternal($sourcePlayer, $originalTargetMZ, $amount, true, $sourceKey, false);
        return;
    }

    if(CardType($targetObj->CardID ?? '') !== 'ENTITY') {
        DealDamageToFieldTargetInternal($sourcePlayer, $originalTargetMZ, $amount, true, $sourceKey, false);
        return;
    }

    DealDamageToFieldTargetInternal($sourcePlayer, $redirectTargetMZ, $amount, true, $sourceKey, true);
};

$customDQHandlers["FORGING_TRICKS_ORDER"] = function($player, $params, $lastDecision) {
    $selected = strval($lastDecision);
    if($selected === '' || $selected === '-') return;

    $chosenCards = array_values(array_filter(explode('&', $selected), function($value) {
        return $value !== '';
    }));
    if(empty($chosenCards)) return;

    $chosenCardIDs = [];
    foreach($chosenCards as $mzID) {
        $obj = GetZoneObject($mzID);
        if($obj === null || (isset($obj->removed) && $obj->removed)) continue;
        $cardID = $obj->CardID ?? '';
        if(!is_string($cardID) || $cardID === '') continue;
        $chosenCardIDs[] = $cardID;
    }
    if(empty($chosenCardIDs)) return;

    $param = 'Bottom=' . implode(',', $chosenCardIDs);
    DecisionQueueController::AddDecision($player, 'MZREARRANGE', $param, 1, 'Put_the_selected_weapon_cards_on_the_bottom_of_your_deck_in_any_order');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'FORGING_TRICKS_APPLY', 1);
};

$customDQHandlers["FORGING_TRICKS_APPLY"] = function($player, $params, $lastDecision) {
    $bottomCards = [];
    foreach(explode(';', strval($lastDecision)) as $pileStr) {
        $eqPos = strpos($pileStr, '=');
        if($eqPos === false) continue;
        $pileName = substr($pileStr, 0, $eqPos);
        if($pileName !== 'Bottom') continue;
        $cardsStr = trim(substr($pileStr, $eqPos + 1));
        $bottomCards = ($cardsStr !== '') ? explode(',', $cardsStr) : [];
        break;
    }
    if(empty($bottomCards)) return;

    $moved = 0;
    foreach($bottomCards as $cardID) {
        if(!is_string($cardID) || $cardID === '') continue;
        $discard = &GetDiscard($player);
        $chosenMZ = '';
        for($i = 0; $i < count($discard); ++$i) {
            if(isset($discard[$i]->removed) && $discard[$i]->removed) continue;
            if(($discard[$i]->CardID ?? '') !== $cardID) continue;
            $chosenMZ = 'myDiscard-' . $i;
            break;
        }
        if($chosenMZ === '') continue;
        MZMove($player, $chosenMZ, 'myDeck');
        DecisionQueueController::CleanupRemovedCards();
        ++$moved;
    }

    if($moved <= 0) return;

    $myGarden = &GetGarden($player);
    for($i = 0; $i < count($myGarden); ++$i) {
        if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
        if(CardType($myGarden[$i]->CardID ?? '') !== 'LEADER') continue;
        AddUniqueTurnEffect($myGarden[$i], 'ATK_MOD:' . $moved);
        break;
    }
};

$customDQHandlers["S1-STT03-017_Sprout-of-Fortune_S_C_die:0:OnPlay-1"] = function($player, $parts, $lastDecision) {
    $selectedIndex = intval(explode(',', strval($lastDecision))[0] ?? 0);
    if($selectedIndex === 0) {
        DoDrawCard(intval($player), 1);
        return;
    }
    GainIKZ($player, 1, status:1);
    HealLeader(intval($player), 1);
};

$customDQHandlers["GOU_ONPLAY_CHOICE"] = function($player, $params, $lastDecision) {
    $choiceMap = strval(DecisionQueueController::GetVariable('P' . intval($player) . '_GouChoiceMap'));
    if($choiceMap === '') return;

    $mappedChoices = array_values(array_filter(explode(',', $choiceMap), function($value) {
        return $value !== '';
    }));
    if(empty($mappedChoices)) return;

    $selectedIndex = intval(explode(',', strval($lastDecision))[0] ?? 0);
    if($selectedIndex < 0 || $selectedIndex >= count($mappedChoices)) return;

    $chosenMode = $mappedChoices[$selectedIndex];
    if($chosenMode === 'sacrifice') {
        $gardenChoices = [];
        $myGarden = &GetGarden($player);
        for($i = 0; $i < count($myGarden); ++$i) {
            if(isset($myGarden[$i]->removed) && $myGarden[$i]->removed) continue;
            if(CardType($myGarden[$i]->CardID ?? '') !== 'ENTITY') continue;
            $gardenChoices[] = 'myGarden-' . $i;
        }
        if(empty($gardenChoices)) return;
        if(count($gardenChoices) === 1) {
            SafeMZMove($player, $gardenChoices[0], 'myDiscard');
            DecisionQueueController::CleanupRemovedCards();
            return;
        }
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $gardenChoices), 1, 'Choose_an_entity_to_sacrifice');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'GOU_SACRIFICE_ENTITY', 1);
        return;
    }

    if($chosenMode === 'discard') {
        $handChoices = [];
        $myHand = &GetHand($player);
        for($i = 0; $i < count($myHand); ++$i) {
            if(isset($myHand[$i]->removed) && $myHand[$i]->removed) continue;
            $handChoices[] = 'myHand-' . $i;
        }
        if(count($handChoices) < 2) return;
        if(count($handChoices) === 2) {
            MZMove($player, $handChoices[1], 'myDiscard');
            MZMove($player, $handChoices[0], 'myDiscard');
            DecisionQueueController::CleanupRemovedCards();
            return;
        }
        DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE', '2|2|' . implode('&', $handChoices), 1, 'Choose_2_cards_to_discard');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'GOU_DISCARD_TWO', 1);
    }
};

$customDQHandlers["GOU_SACRIFICE_ENTITY"] = function($player, $params, $lastDecision) {
    $chosen = strval($lastDecision);
    if($chosen === '' || $chosen === '-' || strtoupper($chosen) === 'PASS') return;
    SafeMZMove($player, $chosen, 'myDiscard');
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["GOU_DISCARD_TWO"] = function($player, $params, $lastDecision) {
    $selected = strval($lastDecision);
    if($selected === '' || $selected === '-') return;
    $cards = array_values(array_filter(explode('&', $selected), function($value) {
        return $value !== '';
    }));
    if(count($cards) < 2) return;

    usort($cards, function($a, $b) {
        $aParts = explode('-', $a);
        $bParts = explode('-', $b);
        return intval($bParts[1] ?? -1) <=> intval($aParts[1] ?? -1);
    });

    for($i = 0; $i < count($cards); ++$i) {
        MZMove($player, $cards[$i], 'myDiscard');
    }
    DecisionQueueController::CleanupRemovedCards();
};

$customDQHandlers["S1-AZK01-014_Trade-Guild-Cavalry_E_R_die:0:AttackWith-1"] = function($player, $params, $lastDecision) {
    $chosenTarget = strval($lastDecision);
    if($chosenTarget === '' || $chosenTarget === '-') return;
    $targetObj = &GetZoneObject($chosenTarget);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    AddUniqueTurnEffect($targetObj, 'ATK_MOD:2');
};

$customDQHandlers["POWER_OF_FRIENDSHIP_ATTACK"] = function($player, $params, $lastDecision) {
    $selected = strval($lastDecision);
    if($selected === '' || $selected === '-') return;
    $cards = array_values(array_filter(explode('&', $selected), function($value) {
        return $value !== '';
    }));
    foreach($cards as $mzID) {
        $targetObj = &GetZoneObject($mzID);
        if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) continue;
        AddUniqueTurnEffect($targetObj, 'ATK_MOD:1');
    }
};

$customDQHandlers["POWER_OF_FRIENDSHIP_HEALTH"] = function($player, $params, $lastDecision) {
    $selected = strval($lastDecision);
    if($selected === '' || $selected === '-') return;
    $cards = array_values(array_filter(explode('&', $selected), function($value) {
        return $value !== '';
    }));
    foreach($cards as $mzID) {
        $targetObj = &GetZoneObject($mzID);
        if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) continue;
        AddUniqueTurnEffect($targetObj, 'HP_MOD:1');
    }
};

$customDQHandlers["PORTAL_FROM_ALLEY"] = function($player, $params, $lastDecision) {
    $gateMZ = isset($params[0]) && $params[0] !== '' ? $params[0] : 'myGate-0';
    $entityMZ = isset($params[1]) && $params[1] !== '' ? $params[1] : $lastDecision;
    if(!is_string($entityMZ) || $entityMZ === '' || strtoupper($entityMZ) === 'PASS') return;
    UseGate($player, $gateMZ, $entityMZ);
};

$customDQHandlers["RESOLVE_ATTACK"] = function($player, $params, $lastDecision) {
    $attackerMZ = isset($params[0]) ? $params[0] : '';
    $chosenTarget = is_string($lastDecision) ? $lastDecision : '';
    if($attackerMZ === '' || $chosenTarget === '' || strtoupper($chosenTarget) === 'PASS') return;
    if(!CanAttackRuntime($player, $attackerMZ, $chosenTarget)) return;
    ExhaustEntity($player, $attackerMZ);
    TriggerEquippedWeaponOnAttack($player, $attackerMZ);
    OnAttackWithCard($player, $attackerMZ, $chosenTarget);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'BEGIN_ATTACK_RESPONSE|' . $attackerMZ . '|' . $chosenTarget, 1);
};

$customDQHandlers["BEGIN_ATTACK_RESPONSE"] = function($player, $params, $lastDecision) {
    $attackerMZ = isset($params[0]) ? $params[0] : '';
    $targetMZ = isset($params[1]) ? $params[1] : '';
    if(!is_string($attackerMZ) || $attackerMZ === '') return;
    if(!is_string($targetMZ) || $targetMZ === '') return;
    if(!BeginAttackResponseWindow($player, $attackerMZ, $targetMZ)) return;

    $responderPlayer = GetPendingAttackResponderPlayer();
    if($responderPlayer !== 1 && $responderPlayer !== 2) return;

    $defenderTargetMZ = FlipZonePerspective($targetMZ);
    $defenderAttackerMZ = FlipZonePerspective($attackerMZ);
    if(!is_string($defenderTargetMZ) || $defenderTargetMZ === '') return;
    if(!is_string($defenderAttackerMZ) || $defenderAttackerMZ === '') return;

    WhenAttacked($responderPlayer, $defenderTargetMZ, $defenderAttackerMZ);
};

$customDQHandlers["RESOLVE_ATTACK_COMBAT"] = function($player, $params, $lastDecision) {
    $attackerMZ = isset($params[0]) ? $params[0] : '';
    $targetMZ = isset($params[1]) ? $params[1] : '';
    if(!is_string($attackerMZ) || $attackerMZ === '') return;
    if(!is_string($targetMZ) || $targetMZ === '') return;
    ResolveAttackCombat($player, $attackerMZ, $targetMZ);
};

$customDQHandlers["PLAY_ENTITY_DEST"] = function($player, $params, $lastDecision) {
    $mzCard = isset($params[0]) ? $params[0] : '';
    if(!is_string($mzCard) || $mzCard === '') return;

    $sourceObject = &GetZoneObject($mzCard);
    if($sourceObject === null || (isset($sourceObject->removed) && $sourceObject->removed)) return;
    if(($sourceObject->Location ?? '') !== 'Hand') return;

    $destination = is_string($lastDecision) ? $lastDecision : '';
    if($destination !== 'myGarden' && $destination !== 'myAlley') {
        return;
    }
    ResolveEntityPlayFromHand($player, $mzCard, $destination);
};

$customDQHandlers["PLAY_WEAPON_TARGET"] = function($player, $params, $lastDecision) {
    $mzCard = isset($params[0]) ? $params[0] : '';
    if(!is_string($mzCard) || $mzCard === '') return;

    $targetMZ = is_string($lastDecision) ? $lastDecision : '';
    if($targetMZ === '' || strtoupper($targetMZ) === 'PASS') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;
    if(($targetObj->Location ?? '') !== 'Garden') return;

    ResolveWeaponPlayFromHand($player, $mzCard, $targetMZ);
};

$customDQHandlers['RaizanActivate:Grant-Charge'] = function($player, $params, $lastDecision) {
    $targetMZ = is_string($lastDecision) ? $lastDecision : '';
    if($targetMZ === '' || $targetMZ === '-' || strtoupper($targetMZ) === 'PASS') return;

    $targetObj = &GetZoneObject($targetMZ);
    if($targetObj === null || (isset($targetObj->removed) && $targetObj->removed)) return;

    if(!CanPayIKZCost($player, 1)) {
        SetFlashMessage('Not enough IKZ available. You need 1 IKZ to activate this ability.');
        return;
    }

    if(!isset($targetObj->TurnEffects) || !is_array($targetObj->TurnEffects)) {
        $targetObj->TurnEffects = [];
    }

    if(!in_array('CHARGE', $targetObj->TurnEffects, true)) {
        $targetObj->TurnEffects[] = 'CHARGE';
    }

    PayIKZCost($player, 1);
};

// --- Phase Handler Wrappers for TurnController ---
function StartOfTurnPhase() {
    $player = intval(GetTurnPlayer());
    DecisionQueueController::SuspendAutoAdvance();
    try {
        OnStartOfTurn($player);
    } finally {
        DecisionQueueController::ResumeAutoAdvance();
    }
}

function MainPhase() {
    // Main phase is player-driven; no auto actions needed here yet.
    // Could add auto-triggers or forced actions if needed in future.
}

function EndOfTurnPhase() {
    $endingPlayer = intval(GetTurnPlayer());
    DecisionQueueController::SuspendAutoAdvance();
    try {
        OnEndOfTurn($endingPlayer);
    } finally {
        DecisionQueueController::ResumeAutoAdvance();
    }
    // Switch turn player and increment turn number
    $turnPlayer = &GetTurnPlayer();
    $turnNumber = &GetTurnNumber();
    $turnPlayer = ($endingPlayer == 1) ? 2 : 1;
    $turnNumber++;
}

/**
 * Flip a zone mzID between player perspectives.
 * e.g. "myField-2" becomes "theirField-2" and vice versa.
 */
function FlipZonePerspective($mzID) {
    if(strpos($mzID, "my") === 0) {
        return "their" . substr($mzID, 2);
    } else if(strpos($mzID, "their") === 0) {
        return "my" . substr($mzID, 5);
    }
    return $mzID; // global zones like EffectStack don't flip
}
