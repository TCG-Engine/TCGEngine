<?php

function CustomWidgetInput($playerID, $actionCard, $action) {
    if ($action === 'Pass') {
        FaBPassPriority(intval($playerID));
        return;
    }
    if ($action === 'Activate' && function_exists('ActivateAbility')) {
        ActivateAbility($playerID, $actionCard, 0);
    }
}

?>
