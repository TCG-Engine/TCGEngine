<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    $action = strtoupper(trim((string)$action));
    if($action === 'PASS' || $action === 'SLUMBER') HellbreakTakePassLikeAction(intval($playerID), $action);
}

?>
