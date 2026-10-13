<?php
/**
 * Shared scenario helpers for the keyword families, loaded before the per-element fixture files (glob order).
 */

// The taunt keyword family on the 'quiet' deck (the standard deck's fast cards open a window that swallows the attack click): player 1 holds the taunter and
// player 2's Dungeon Guide has to hit it, not the champion.
if (!function_exists('gaWindTaunt')) {
    function gaWindTaunt(string $slug, string $ally, string $clause, bool $classBonus = false): void {
        gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus)
            ->seed('myField', $ally, properties: ['Status' => 2])
            ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
            ->endTurn()->action(2, 10002, 'myField-1!FSM!')
            ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while the taunting ally is awake')
            ->tested($ally)->covers(['taunt', 'attack-declaration'], $clause)->register();
    }
}

// The ranged keyword family on the 'quiet' deck: the ally attacks from player 2 while distant.
if (!function_exists('gaWindRanged')) {
    function gaWindRanged(string $slug, string $ally, int $damage, string $label, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'ranged', 'combat']): void {
        gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus, player: 2)
            ->seed('myField', $ally, player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
            ->endTurn()
            ->attack('myField-1', 'theirField-0', player: 2)
            ->expectDamage('p1Field-0', $damage, $label)
            ->tested($ally)
            ->covers($mechanics, $clause)
            ->register();
    }
}

// A scenario on the 'quiet' deck whose starting champion is patched to the named champion (a champion-bonus card needs its champion).
if (!function_exists('gaBonus')) {
    function gaBonus(string $slug, string $champion): GaScenario {
        return gaScenario($slug)
            ->deckTemplate('quiet', 'Dungeon Guide')
            ->champion(cardName: $champion, lineage: ['Spirit of Wind']);
    }
}
