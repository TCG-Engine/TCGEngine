<?php
// Core/Match/QueueTypes.php — game-agnostic Bo1/Bo3 queue-type registry.
function MatchQueueTypeDefinitions() {
    return [
        'bo1' => ['displayName' => 'Best of 1', 'bestOf' => 1, 'sideboard' => false],
        'bo3' => ['displayName' => 'Best of 3', 'bestOf' => 3, 'sideboard' => true],
    ];
}
function MatchGetQueueType($id) {
    return MatchQueueTypeDefinitions()[strtolower(trim((string)$id))] ?? null;
}
