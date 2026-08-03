<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
  $cardArr = explode('-', $actionCard);
  $zone = $cardArr[0];

  switch ($zone) {
    case 'myCards':
      $card = GetZoneObject($actionCard);
      if ($card === null) break;
      if ($action === '>') MZAddZone($playerID, 'myMainDeck', $card->CardID);
      elseif ($action === '>>>') {
        for ($i = 0; $i < 4; ++$i) MZAddZone($playerID, 'myMainDeck', $card->CardID);
      } elseif ($action === 'V') MZAddZone($playerID, 'mySideboard', $card->CardID);
      break;

    case 'myMainDeck':
    case 'mySideboard':
      $card = GetZoneObject($actionCard);
      if ($card === null) break;
      if ($action === '<') $card->Remove();
      elseif ($action === '<<<') {
        $cardID = $card->CardID;
        for ($i = 0; $i < 4; ++$i) {
          $match = SearchZoneForCard($actionCard, $cardID, 1);
          if ($match !== null) $match->Remove();
        }
      } elseif ($action === '+') {
        MZAddZone($playerID, $zone === 'myMainDeck' ? 'myMainDeck' : 'mySideboard', $card->CardID);
      } elseif ($action === 'V' && $zone === 'myMainDeck') {
        $card->Remove();
        MZAddZone($playerID, 'mySideboard', $card->CardID);
      } elseif ($action === '^' && $zone === 'mySideboard') {
        $card->Remove();
        MZAddZone($playerID, 'myMainDeck', $card->CardID);
      }
      break;
  }
}

/**
 * Capture an AzukiDeck edit before the engine mutates its zones. Retaining the before-state
 * lets the after hook avoid animating failed additions and other no-op edits.
 */
function GameBeforeEngineAction($action): void {
  $GLOBALS['azukiDeckPendingZoneTransition'] = AzukiDeckCaptureZoneTransition($action);
}

/** Queue semantic zone moves after a successful edit and before the frame is persisted. */
function GameAfterEngineAction($action, $result): void {
  $transition = $GLOBALS['azukiDeckPendingZoneTransition'] ?? null;
  unset($GLOBALS['azukiDeckPendingZoneTransition']);
  if (empty($result['success']) || !is_array($transition)) return;

  $sourceZone = $transition['sourceZone'];
  $destinationZone = $transition['destinationZone'];
  // Leader and Gate swaps update the identity banner directly. Flying their cropped pane
  // cards into that shallow banner is visually noisy, so keep motion for deck-card zones only.
  if (AzukiDeckIsIdentityZone($sourceZone) || AzukiDeckIsIdentityZone($destinationZone)) return;
  $cardID = $transition['cardID'];
  $sourceCount = AzukiDeckActiveCardCount($sourceZone, $cardID);
  $destinationCount = AzukiDeckActiveCardCount($destinationZone, $cardID);
  $operation = $transition['operation'];
  $moveCount = 0;

  if ($operation === 'add') {
    $moveCount = max(0, $destinationCount - $transition['destinationCount']);
  } elseif ($operation === 'remove') {
    $moveCount = max(0, $transition['sourceCount'] - $sourceCount);
  } elseif ($operation === 'move') {
    $removedCount = max(0, $transition['sourceCount'] - $sourceCount);
    $addedCount = max(0, $destinationCount - $transition['destinationCount']);
    $moveCount = min($removedCount, $addedCount);
  } elseif ($operation === 'swap') {
    $oldDestinationCardID = $transition['destinationCardID'];
    $newDestinationCardID = AzukiDeckFirstActiveCardID($destinationZone);
    $moveCount = ($newDestinationCardID === $cardID && $oldDestinationCardID !== $cardID) ? 1 : 0;
  }

  if ($moveCount < 1 || !function_exists('QueueZoneMoveAnimation')) return;
  $destinationMZ = AzukiDeckFirstActiveCardMZ($destinationZone, $cardID);
  if ($destinationMZ === '') $destinationMZ = $destinationZone;

  // Multi-add/remove uses staggered clones of the visible collapsed card, matching the
  // shared CardMotion treatment while keeping the editor's grouped quantity rendering.
  for ($i = 0; $i < $moveCount; ++$i) {
    QueueZoneMoveAnimation(
      $transition['sourceMZ'],
      $destinationMZ,
      380,
      true,
      null,
      null,
      $i * 35
    );
  }
}

function AzukiDeckIsIdentityZone($zoneName) {
  return in_array(strtolower(trim(strval($zoneName))), [
    'leader', 'leaders', 'gate', 'gates',
    'myleader', 'myleaders', 'mygate', 'mygates',
    'theirleader', 'theirleaders', 'theirgate', 'theirgates',
  ], true);
}

function AzukiDeckCaptureZoneTransition($action) {
  if (!is_array($action)) return null;
  $mode = intval($action['mode'] ?? 0);
  $payload = strval($action['cardID'] ?? '');
  $parts = explode('!', $payload);
  $sourceMZ = strval($parts[0] ?? '');
  $source = $sourceMZ !== '' ? GetZoneObject($sourceMZ) : null;
  if (!is_object($source) || !isset($source->CardID)) return null;

  $sourceZone = strtok($sourceMZ, '-');
  $destinationZone = '';
  $operation = '';

  if ($mode === 10001 && strval($parts[1] ?? '') === 'CustomInput') {
    $widgetAction = strval($parts[2] ?? '');
    if ($widgetAction === '>' || $widgetAction === '>>>') {
      $destinationZone = 'myMainDeck';
      $operation = 'add';
    } elseif ($widgetAction === 'V') {
      $destinationZone = 'mySideboard';
      $operation = $sourceZone === 'myCards' ? 'add' : 'move';
    } elseif ($widgetAction === '^') {
      $destinationZone = 'myMainDeck';
      $operation = 'move';
    } elseif ($widgetAction === '<' || $widgetAction === '<<<') {
      $destinationZone = 'myCards';
      $operation = 'remove';
    }
  } elseif ($mode === 10002) {
    $clickAction = strval($parts[1] ?? '');
    $parameters = explode(',', strval($parts[2] ?? ''));
    if ($clickAction === 'Add') {
      $destinationZone = strval($parameters[0] ?? '');
      $operation = 'add';
    } elseif ($clickAction === 'Move') {
      $destinationZone = strval($parameters[0] ?? '');
      $operation = 'move';
    } elseif ($clickAction === 'Remove') {
      // Deck-editor removals return to the searchable card library conceptually. The
      // generated Sideboard click parameter names its source zone, so do not trust it.
      $destinationZone = 'myCards';
      $operation = 'remove';
    } elseif ($clickAction === 'Swap') {
      $destinationZone = strval($parameters[0] ?? '');
      $operation = 'swap';
    }
  }

  if ($operation === '' || $destinationZone === '' || $sourceZone === '') return null;
  $cardID = strval($source->CardID);
  return [
    'operation' => $operation,
    'sourceMZ' => $sourceMZ,
    'sourceZone' => $sourceZone,
    'destinationZone' => $destinationZone,
    'cardID' => $cardID,
    'sourceCount' => AzukiDeckActiveCardCount($sourceZone, $cardID),
    'destinationCount' => AzukiDeckActiveCardCount($destinationZone, $cardID),
    'destinationCardID' => AzukiDeckFirstActiveCardID($destinationZone),
  ];
}

function AzukiDeckActiveCardCount($zoneName, $cardID) {
  if ($zoneName === '' || $cardID === '') return 0;
  $zone = &GetZone($zoneName);
  if (!is_array($zone)) return 0;
  $count = 0;
  foreach ($zone as $card) {
    if (!is_object($card) || !empty($card->removed)) continue;
    if (strval($card->CardID ?? '') === $cardID) ++$count;
  }
  return $count;
}

function AzukiDeckFirstActiveCardID($zoneName) {
  if ($zoneName === '') return '';
  $zone = &GetZone($zoneName);
  if (!is_array($zone)) return '';
  foreach ($zone as $card) {
    if (!is_object($card) || !empty($card->removed)) continue;
    return strval($card->CardID ?? '');
  }
  return '';
}

function AzukiDeckFirstActiveCardMZ($zoneName, $cardID) {
  if ($zoneName === '' || $cardID === '') return '';
  $zone = &GetZone($zoneName);
  if (!is_array($zone)) return '';
  foreach ($zone as $index => $card) {
    if (!is_object($card) || !empty($card->removed)) continue;
    if (strval($card->CardID ?? '') !== $cardID) continue;
    $mzIndex = isset($card->mzIndex) ? intval($card->mzIndex) : intval($index);
    return $zoneName . '-' . $mzIndex;
  }
  return '';
}

require_once __DIR__ . '/AssetVersioning.php';

?>
