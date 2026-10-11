<?php
/** Durable public totals live in the serialized DecisionQueue variables, not the bounded log. */
function PokeDamageStartTurn(int $player): void {
    $rows = PokeVar('damageTurns', []);
    $previous = array_filter($rows, fn($row) => $row['player'] === $player);
    $ownTurn = max(GetPlayerTurns($player), $previous ? max(array_column($previous, 'turn')) + 1 : 1);
    $rows[] = ['player'=>$player, 'turn'=>$ownTurn, 'gameTurn'=>GetTurnNumber(),
        'order'=>$player === PokeVar('statsFirstPlayer', GetFirstPlayer()) ? 'first' : 'second',
        'deck'=>PokeVar('deckKey:'.$player, 'custom'), 'damage'=>0, 'complete'=>false];
    PokeSetVar('damageTurns', $rows);
}
function PokeRecordDamage(int $player, int $targetPlayer, int $amount): void {
    if ($player === $targetPlayer || $amount <= 0 || GetCurrentPhase() !== 'MAIN' || GetTurnPlayer() !== $player) return;
    $rows = PokeVar('damageTurns', []);
    $i = count($rows) - 1;
    if ($i < 0 || $rows[$i]['player'] !== $player || $rows[$i]['complete']) return;
    $rows[$i]['damage'] += $amount;
    PokeSetVar('damageTurns', $rows);
}
function PokeDamageFinishTurn(): void {
    $rows = PokeVar('damageTurns', []);
    if (!$rows) return;
    $rows[count($rows)-1]['complete'] = true;
    PokeSetVar('damageTurns', $rows);
}
