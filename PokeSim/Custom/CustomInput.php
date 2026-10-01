<?php
// Shared engine adapter for direct board actions. The local and CLI entry points
// also call PokeApplyAction, so all clients share legality and decision handling.
function CustomWidgetInput($playerID, $actionCard, $action): void {
    $payload = json_decode((string)$action, true);
    if (!is_array($payload)) throw new InvalidArgumentException('PokeSim expects a JSON action');
    $payload['player'] = (int)$playerID;
    PokeApplyAction($payload);
}
