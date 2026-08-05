<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $action = strtoupper(trim((string)$action));
    if(strcasecmp(strval($actionCard), 'Tutorial') === 0 && $action === 'CONTINUE') {
        if(function_exists('HellbreakTutorialContinue')) HellbreakTutorialContinue(intval($playerID));
        return;
    }
    if($action === 'PASS' || $action === 'SLUMBER') HellbreakTakePassLikeAction(intval($playerID), $action);
}

?>
