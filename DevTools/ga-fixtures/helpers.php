<?php
/**
 * Shared scenario helpers for the keyword families, loaded before the per-element fixture files (glob order).
 */

// The ranged keyword family on the 'quiet' deck: the ally attacks from player 2 while distant.
if (!function_exists('gaWindRanged')) {
    function gaWindRanged(string $slug, string $ally, int $damage, string $label, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'ranged', 'combat']): void {
        gaScenario($slug)->behaviorFamily('ranged')
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
        $s = gaScenario($slug)->behaviorFamily('retort')
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
        $s = gaScenario($slug)->behaviorFamily('intercept')
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
        gaScenario($slug)->behaviorFamily('stealth')
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
        gaScenario($slug)->behaviorFamily('true-sight')
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

// These small suites deliberately accept explicit expectations, never card-derived answers.
function gaReadyKeywordCase(string $family, string $slug, string $card, string $deck, array $ready, array $mechanics, string $clause): GaScenario {
    return gaScenario($slug)->behaviorFamily($family)
        ->deckTemplate('quiet', $deck)->readyFor($card, ...$ready)
        ->tested($card)->covers($mechanics, $clause);
}

function gaVigorCase(string $slug, string $card, string $deck, array $ready, array $mechanics, string $clause, string $status, string $label): void {
    gaReadyKeywordCase('vigor', $slug, $card, $deck, $ready, $mechanics, $clause)
        ->seed('myField', $card, properties: ['Status' => 1])->endTurn()
        ->expectProperty('p1Field-1', 'Status', $status, $label)->register();
}

function gaPrideCase(string $slug, string $card, string $deck, array $ready, array $mechanics, string $clause, bool $obeys, string $label): void {
    $s = gaReadyKeywordCase('pride', $slug, $card, $deck, $ready, $mechanics, $clause)
        ->seed('myField', $card)->reachTurnThree();
    if ($obeys) $s->attack('myField-1', 'theirField-0')->pass(2);
    else $s->action(1, 10002, 'myField-1!FSM!');
    $s->expectProperty('p1Field-1', 'Status', $obeys ? '1' : '2', $label)->register();
}

// Hand-played Hindered allies only; materialization has its own timing and costs.
function gaHinderedCase(string $slug, string $card, string $deck, array $ready, array $mechanics, string $clause, int $payment, string $label): void {
    gaReadyKeywordCase('hindered', $slug, $card, $deck, $ready, $mechanics, $clause)
        ->seed('myHand', $card)->play($card)->pay($payment)
        ->expectProperty('p1Field-1', 'Status', '1', $label)->register();
}

function gaClassStatsCase(string $slug, string $card, string $deck, array $ready, array $mechanics, string $clause, int $power, string $powerLabel, int $life, string $lifeLabel): void {
    gaReadyKeywordCase('conditional-stats', $slug, $card, $deck, $ready, $mechanics, $clause)
        ->seed('myField', $card)->observe()
        ->expectPower('p1Field-1', $power, $powerLabel)
        ->expectLife('p1Field-1', $life, $lifeLabel)->register();
}

// Simple hand-played allies with class-gated on-enter recovery only.
// Rows: class bonus, initial damage, expected damage, assertion label.
function gaEnterRecover(string $card, int $payment, string $clause, array $cases): void {
    foreach ($cases as $slug => [$classBonus, $damage, $expectedDamage, $label]) {
        gaReadyKeywordCase('enter-recover', $slug, $card, $card,
            ['classBonus' => $classBonus, 'damage' => $damage],
            $classBonus ? ['class-bonus', 'on-enter', 'recover'] : ['class-bonus', 'condition', 'recover'],
            $clause)
            ->seed('myHand', $card)->play($card)->pay($payment)
            ->expectDamage('p1Field-0', $expectedDamage, $label)
            ->expectCardExists('myField', $card, 'Recover ally entered the field')
            ->expectDqEmpty()->register();
    }
}

// Matrix rows keep independent names and explicit expected results.
function gaVigorMatrix(string $card, string $deck, array $mechanics, string $clause, array $cases): void {
    foreach ($cases as $slug => $row) {
        [$ready, $status, $label] = $row;
        gaVigorCase($slug, $card, $deck, $ready, $row[3] ?? $mechanics, $row[4] ?? $clause, $status, $label);
    }
}

function gaSingleStatMatrix(string $card, string $deck, string $stat, array $mechanics, string $clause, array $cases): void {
    if (!in_array($stat, ['power', 'life'], true)) throw new InvalidArgumentException('Unknown stat: ' . $stat);
    foreach ($cases as $slug => $row) {
        [$ready, $expected, $label] = $row;
        $s = gaReadyKeywordCase('single-stat', $slug, $card, $deck, $ready, $row[3] ?? $mechanics, $clause)
            ->seed('myField', $card)->observe();
        if ($stat === 'power') $s->expectPower('p1Field-1', $expected, $label);
        else $s->expectLife('p1Field-1', $expected, $label);
        $s->register();
    }
}

function gaMaterializedHindered(string $slug, string $card, string $clause, bool $ready = false, array $lineage = []): void {
    $s = gaScenario($slug)->behaviorFamily('materialized-hindered')->deckTemplate('quiet', 'Dungeon Guide');
    if ($ready) $s->readyFor($card);
    if ($lineage) $s->champion(lineage: $lineage);
    $s->seed('myMaterial', $card)->endTurn()->endTurn(2)->target('myMaterial-4')
        ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: it entered rested')
        ->expectCardExists('myField', $card, 'Materialized card entered the field')
        ->tested($card)->covers(['keyword', 'hindered'], $clause)->register();
}

// A controlled deck makes every resulting position assertable, including the bottom.
function gaEnterGlimpse(string $card, int $payment, string $clause, array $revealed, array $cases, string $entryLabel = 'Glimpse ally entered the field'): void {
    $ids = fn(array $names) => implode(',', array_map([GaScenario::class, 'id'], $names));
    foreach ($cases as $slug => [$top, $bottom, $expectedDeck]) {
        $chosen = array_merge($top, $bottom);
        $available = $revealed;
        sort($chosen); sort($available);
        if ($chosen !== $available) throw new InvalidArgumentException('Glimpse choices must partition revealed cards');
        $expectedCards = $expectedDeck;
        $deckCards = array_merge($revealed, ['Fluffy Shopkeep']);
        sort($expectedCards); sort($deckCards);
        if ($expectedCards !== $deckCards) throw new InvalidArgumentException('Expected Glimpse deck must preserve all cards');
        $s = gaReadyKeywordCase('enter-glimpse', $slug, $card, $card, ['classBonus' => true], ['class-bonus', 'on-enter', 'glimpse'], $clause)
            ->emptyZone('myDeck')->deckTop(array_merge($revealed, ['Fluffy Shopkeep']))
            ->seed('myHand', $card)->play($card)->pay($payment)
            ->answer(1, 'Top=' . $ids($top) . ';Bottom=' . $ids($bottom))
            ->expectCardExists('myField', $card, $entryLabel)
            ->expectZoneCount('myDeck', count($expectedDeck), label: 'Glimpse preserves the deck size');
        foreach ($expectedDeck as $index => $name) {
            $s->expectProperty('p1Deck-' . $index, 'CardID', GaScenario::id($name), 'Glimpse deck position ' . $index);
        }
        $s->expectDqEmpty()->register();
    }
}

// Simple class-gated entry Gather. Keep the random Herb identity out of expectations.
function gaEnterGatherCase(string $slug, string $card, int $payment, bool $classBonus, int $fieldCount, string $label): GaScenario {
    return gaScenario($slug)->behaviorFamily('enter-gather')
        ->deckTemplate('quiet', $card)->readyFor($card, classBonus: $classBonus)
        ->seed('myHand', $card)->play($card)->pay($payment)
        ->expectZoneCount('myField', $fieldCount, 1, $label);
}

// Payment is explicitly supplied, not read from card implementation data.
// Quiet decks start with seven hand cards; each selected seed shifts into slot 7.
function gaAdvancedImbueCase(string $slug, string $card, array $revealed, int $payment): GaScenario {
    if ($payment < 1 || $payment > count($revealed)) throw new InvalidArgumentException('Invalid imbue payment');
    $s = gaScenario($slug)->behaviorFamily('advanced-imbue')->deckTemplate('quiet', $card)->readyFor($card);
    foreach ($revealed as $name) $s->seed('myHand', $name);
    $s->seed('myHand', $card)->play($card)->answer(1, 'YES');
    for ($i = 0; $i < $payment; ++$i) $s->answer(1, 'myHand-7');
    return $s;
}

// Configure card-specific targets/material decks before resolving the common cost path.
function gaEfficiencyCase(string $slug, string $card, bool $classBonus, int $level, int $payment, callable $setup, ?string $target = null): GaScenario {
    $s = gaScenario($slug)->behaviorFamily('efficiency');
    $setup($s);
    return $s->readyFor($card, classBonus: $classBonus, level: $level)
        ->seed('myHand', $card)->castSpell($card, pay: $payment, window: 'me', target: $target);
}

// Actual combat destruction triggers On Death. Callers retain the death and effect assertions.
function gaOnDeathCase(string $slug, string $card, string $attacker, array $ready = [], array $properties = [], array $attackerProperties = ['Status' => 2]): GaScenario {
    return gaScenario($slug)->behaviorFamily('on-death')
        ->deckTemplate('quiet', $card)->readyFor($card, ...$ready)
        ->seed('myField', $card, properties: $properties)
        ->seed('myField', $attacker, player: 2, properties: $attackerProperties)
        ->endTurn()->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-');
}
