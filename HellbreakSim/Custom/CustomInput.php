<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $actionParts = explode('|', trim((string)$action));
    $actionName = strtoupper(trim(strval($actionParts[0] ?? '')));
    if(strcasecmp(strval($actionCard), 'Tutorial') === 0) {
        if($actionName === 'CONTINUE' && function_exists('HellbreakTutorialContinue')) {
            HellbreakTutorialContinue(intval($playerID));
        } else if($actionName === 'ACK_LOCATION_CONTROL' && function_exists('HellbreakTutorialAcknowledge')) {
            HellbreakTutorialAcknowledge(intval($playerID), 'LOCATION_CONTROL');
        } else if($actionName === 'ACK_RETAKE_CONTROL' && function_exists('HellbreakTutorialAcknowledge')) {
            HellbreakTutorialAcknowledge(intval($playerID), 'RETAKE_CONTROL');
        }
        return;
    }
    if(strcasecmp(strval($actionCard), 'BoardAction') === 0 && $actionName === 'DIRECT') {
        HellbreakTakeDirectHorrorAction(
            intval($playerID),
            strval($actionParts[1] ?? ''),
            strval($actionParts[2] ?? ''),
            intval($actionParts[3] ?? -1)
        );
        return;
    }
    if($actionName === 'PASS' || $actionName === 'SLUMBER') {
        HellbreakTakeDirectHorrorAction(intval($playerID), $actionName);
    }
}

?>
