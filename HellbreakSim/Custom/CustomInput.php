<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $actionParts = explode('|', trim((string)$action));
    $actionName = strtoupper(trim(strval($actionParts[0] ?? '')));
    if(strcasecmp(strval($actionCard), 'Tutorial') === 0 && $actionName === 'CONTINUE') {
        if(function_exists('HellbreakTutorialContinue')) HellbreakTutorialContinue(intval($playerID));
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
