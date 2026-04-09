<?php

include_once __DIR__ . '/RegressionTestFramework.php';

function EngineLoadRootRuntime($folderPath) {
  $repoRoot = RegressionRepoRoot();
  $localVarNames = array_keys(get_defined_vars());

  include_once $repoRoot . '/Core/CoreZoneModifiers.php';
  include_once $repoRoot . '/Core/NetworkingLibraries.php';
  include_once $repoRoot . '/Core/HTTPLibraries.php';
  include_once $repoRoot . '/AccountFiles/AccountSessionAPI.php';
  include_once $repoRoot . '/AccountFiles/AccountDatabaseAPI.php';
  include_once $repoRoot . '/Database/ConnectionManager.php';

  include_once $repoRoot . '/' . $folderPath . '/GeneratedCode/GeneratedCardDictionaries.php';
  include_once $repoRoot . '/' . $folderPath . '/GamestateParser.php';
  include_once $repoRoot . '/' . $folderPath . '/ZoneAccessors.php';
  include_once $repoRoot . '/' . $folderPath . '/ZoneClasses.php';

  // Root runtime files define important registries at top level. When they are
  // included from inside this function, those variables land in local scope
  // unless we explicitly promote them back into the global runtime.
  foreach (get_defined_vars() as $name => $value) {
    if ($name === 'GLOBALS' || in_array($name, $localVarNames, true)) continue;
    $GLOBALS[$name] = $value;
  }
}

function EngineNormalizeActionPayload($action) {
  return RegressionNormalizeAction($action);
}

function EngineActionCardExists($mzid) {
  $mzArr = explode('-', $mzid);
  if (count($mzArr) < 2) return false;
  $zone = GetZone($mzArr[0]);
  if (!is_array($zone)) return false;
  return intval($mzArr[1]) < count($zone);
}

function EngineExecuteLoadedAction($action, $folderPath, $gameName, $options = []) {
  global $updateNumber, $playerID;

  $action = EngineNormalizeActionPayload($action);
  $playerID = $action['playerID'];
  $mode = intval($action['mode']);
  $buttonInput = $action['buttonInput'];
  $cardID = $action['cardID'];
  $chkInput = $action['chkInput'];
  $inputText = $action['inputText'];

  $result = [
    'success' => true,
    'message' => '',
    'writeGamestate' => true,
    'updateCache' => $options['updateCache'] ?? true,
    'recordAction' => !($options['disableRecording'] ?? false),
  ];

  if (function_exists('SetFlashMessage')) SetFlashMessage('');

  switch ($mode) {
    case 100:
      $dqController = new DecisionQueueController();
      $dqController->PopDecision($playerID);
      $dqController->ExecuteStaticMethods($playerID, $cardID);
      break;
    case 10000:
      $macro = $buttonInput;
      $zone = &GetZone($inputText);
      switch ($macro) {
        case 'Shuffle':
          EngineShuffle($zone);
          break;
        default:
          break;
      }
      break;
    case 10001:
      $inpArr = explode('!', $cardID);
      $actionCard = $inpArr[0] ?? '';
      $widgetType = $inpArr[1] ?? '';
      $actionValue = $inpArr[2] ?? '';
      if ($widgetType == 'CustomInput') {
        CustomWidgetInput($playerID, $actionCard, $actionValue);
        break;
      }
      switch ($actionValue) {
        case '-1':
        case '+1':
          $card = &GetZoneObject($actionCard);
          if (is_object($card)) $card->$widgetType += intval($actionValue);
          else $card += intval($actionValue);
          break;
        case 'Notes':
          if (!EngineActionCardExists($actionCard)) break;
          $noteText = str_replace(' ', '_', $inpArr[3] ?? '');
          $card = GetZoneObject($actionCard);
          $widgetCardId = $card->CardID;
          $card = SearchZoneForCard('myCardNotes', $card->CardID, $playerID);
          if ($card != null) {
            $card->Notes = $noteText;
          } else {
            MZAddZone($playerID, 'myCardNotes', $widgetCardId);
            $card = SearchZoneForCard('myCardNotes', $widgetCardId, $playerID);
            $card->Notes = $noteText;
          }
          break;
        default:
          $card = &GetZoneObject($actionCard);
          if (is_object($card)) {
            if ($card->$widgetType == $actionValue) $card->$widgetType = '-';
            else $card->$widgetType = $actionValue;
          } else {
            if ($card == $actionValue) $card = '-';
            else $card = $actionValue;
          }
          break;
      }
      break;
    case 10002:
      $inpArr = explode('!', $cardID);
      $actionCard = $inpArr[0] ?? '';
      $actionValue = $inpArr[1] ?? '';
      $parameterArr = explode(',', $inpArr[2] ?? '');
      if (!EngineActionCardExists($actionCard)) break;
      $card = GetZoneObject($actionCard);
      switch ($actionValue) {
        case 'Move':
          $card->Remove();
          $destination = $parameterArr[0] ?? '';
          MZAddZone($playerID, $destination, $card->CardID);
          break;
        case 'Add':
          $destination = $parameterArr[0] ?? '';
          MZAddZone($playerID, $destination, $card->CardID);
          break;
        case 'Remove':
          $card->Remove();
          break;
        case 'Swap':
          $destination = $parameterArr[0] ?? '';
          MZClearZone($playerID, $destination);
          MZAddZone($playerID, $destination, $card->CardID);
          break;
        case 'FSM':
          ActionMap($actionCard);
          break;
        default:
          break;
      }
      break;
    case 10003:
      $version = $cardID;
      if ($version == 'current') {
        break;
      } elseif ($version == 'new') {
        $versionName = $options['versionName'] ?? $inputText;
        SaveVersion($playerID, $versionName);
      } else {
        if ($folderPath == 'SoulMastersDB') {
          SoulMastersSwitchVersion($version);
          break;
        }
        LoadVersion($playerID, intval($version));
      }
      break;
    case 10004:
      LoadVersion($playerID);
      SetFlashMessage('Player ' . $playerID . ' undid their last action.');
      break;
    case 10005:
      SaveVersion($playerID);
      break;
    case 10014:
      $inpArr = explode('!', $cardID);
      $moveCard = $inpArr[0] ?? '';
      $destination = $inpArr[1] ?? '';
      if (!EngineActionCardExists($moveCard)) break;
      $card = GetZoneObject($moveCard);
      if ($card->DragMode() != 'Clone') $card->Remove();
      MZAddZone($playerID, $destination, $card->CardID);
      break;
    case 11000:
      $createdBy = $options['createdBy'] ?? 'anonymous';
      $recordingResult = RegressionStartRecording($folderPath, $gameName, $playerID, $createdBy);
      $result['success'] = $recordingResult['success'];
      $result['message'] = $recordingResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11001:
      $recordingResult = RegressionStopRecording($folderPath, $gameName);
      $result['success'] = $recordingResult['success'];
      $result['message'] = $recordingResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11002:
      $recordingResult = RegressionAddAssertion($folderPath, $gameName, $playerID, $inputText);
      $result['success'] = $recordingResult['success'];
      $result['message'] = $recordingResult['message'];
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    case 11003:
      $payload = json_decode($inputText, true);
      if (!is_array($payload)) {
        $result['success'] = false;
        $result['message'] = 'Fixture save payload must be valid JSON.';
      } else {
        $recordingResult = RegressionSaveFixture(
          $folderPath,
          $gameName,
          strval($payload['slug'] ?? ''),
          strval($payload['name'] ?? ''),
          strval($payload['notes'] ?? '')
        );
        $result['success'] = $recordingResult['success'];
        $result['message'] = $recordingResult['message'];
      }
      $result['writeGamestate'] = false;
      $result['updateCache'] = false;
      $result['recordAction'] = false;
      break;
    default:
      break;
  }

  if ($result['writeGamestate']) {
    ++$updateNumber;
    WriteGamestate('./' . $folderPath . '/');
    if (is_numeric($gameName)
        && function_exists('TouchOwnershipLastUpdated')
        && function_exists('GetEditAuth') && GetEditAuth() === 'AssetOwner') {
      TouchOwnershipLastUpdated(intval($gameName));
    }
    if ($result['updateCache']) {
      GamestateUpdated($gameName);
    }
    if ($result['recordAction'] && RegressionIsRecordingActive($folderPath, $gameName)) {
      RegressionRecordAction($folderPath, $gameName, $action);
    }
  }

  return $result;
}

function EngineRunAction($action, $folderPath, $gameId, $options = []) {
  EngineLoadRootRuntime($folderPath);
  global $gameName;
  $gameName = strval($gameId);
  ParseGamestate('./' . $folderPath . '/');
  return EngineExecuteLoadedAction($action, $folderPath, $gameName, $options);
}
