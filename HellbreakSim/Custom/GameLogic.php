<?php

$debugMode = true;
$customDQHandlers = [];

function IsDecisionQueueEnabled() { return 1; }
function CardHasAbility($cardID, $from, $index = -1) { return false; }
function ActionMap($cardID, $action, $from = '') { return ''; }
function SelectionMetadata($cardID, $from = '') { return ''; }
function CardCurrentEffects($cardID, $from = '') { return []; }

?>
