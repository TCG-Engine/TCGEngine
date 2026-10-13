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

// A defender retaliating against player 2's Dungeon Guide (1 power, 3 life): $damage is the damage the attacker takes back.
if (!function_exists('gaNormRetort')) {
    function gaNormRetort(string $slug, string $ally, int $damage, string $label, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'retort', 'combat'], array $properties = [], array $extraSeeds = []): void {
        $s = gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus)
            ->seed('myField', $ally, properties: $properties);
        foreach ($extraSeeds as [$zone, $card]) $s->seed($zone, $card);
        $s->seed('myField', 'Dungeon Guide', player: 2)
            ->endTurn()
            ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
            ->expectDamage('p2Field-1', $damage, $label)
            ->tested($ally)
            ->covers($mechanics, $clause)
            ->register();
    }
}

// Intercept: player 2's Dungeon Guide attacks the champion and the awake ally takes the attack instead.
if (!function_exists('gaNormIntercept')) {
    function gaNormIntercept(string $slug, string $ally, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'intercept', 'combat'], array $extraSeeds = []): void {
        $s = gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus)
            ->seed('myField', $ally);
        foreach ($extraSeeds as [$zone, $card]) $s->seed($zone, $card);
        $s->seed('myField', 'Dungeon Guide', player: 2)
            ->endTurn()
            ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myField-1')->answer(1, '-')
            ->expectDamage('p1Field-0', 0, 'The champion takes no damage: the attack was redirected')
            ->expectDamage('p1Field-1', 1, 'The interceptor takes the redirected 1 damage')
            ->tested($ally)
            ->covers($mechanics, $clause)
            ->register();
    }
}

if (!function_exists('gaNormStealth')) {
    function gaNormStealth(string $slug, string $ally, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'stealth']): void {
        gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus)
            ->seed('myField', $ally, properties: ['Status' => 2])
            ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
            ->endTurn()->action(2, 10002, 'myField-1!FSM!')
            ->rejects(2, 'theirField-1', 'Stealth: the attack cannot target the ' . $ally)
            ->answer(2, 'theirField-0')
            ->expectDamage('p1Field-1', 0, 'The stealth ally took no damage')
            ->tested($ally)->covers($mechanics, $clause)->register();
    }
}

if (!function_exists('gaNormTrueSight')) {
    function gaNormTrueSight(string $slug, string $ally, string $clause, bool $classBonus = false, ?int $level = null, array $mechanics = ['keyword', 'true-sight', 'stealth', 'combat']): void {
        gaScenario($slug)
            ->deckTemplate('quiet', $ally)
            ->readyFor($ally, classBonus: $classBonus, level: $level)
            ->seed('myField', $ally)
            ->seed('myField', 'Haze Droid', player: 2)
            ->reachTurnThree()
            ->attack('myField-1', 'theirField-1')->pass(2)
            ->expectDamage('p2Field-1', 2, 'True Sight: the ally could attack the stealth Haze Droid')
            ->tested($ally)->covers($mechanics, $clause)->register();
    }
}
