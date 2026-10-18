<?php

// Listing requires no game runtime, so the normal integration runner can discover every case.
function gaBehaviorCases(): array {
    $cases = [];
    foreach (require __DIR__ . '/cases.php' as $slug => $row) {
        if ($row[0] === 'taunt') {
            $cases[$slug] = $row;
        } elseif ($row[0] === 'recover') {
            $cases[$slug] = [...$row, 5];
            $cases[$slug . '-clamps-at-zero'] = [...$row, 1];
            $cases[$slug . '-undamaged'] = [...$row, 0];
        } else {
            throw new RuntimeException("Unknown GA behavior family: {$row[0]}");
        }
    }
    foreach (json_decode(file_get_contents(__DIR__ . '/families.json'), true, 512, JSON_THROW_ON_ERROR) as $slug => $family) {
        if (isset($cases[$slug])) throw new RuntimeException("Duplicate behavior case: $slug");
        $cases[$slug] = [$family, '@shared'];
    }
    return $cases;
}

function gaBehaviorScenario(string $slug, array $row): GaScenario {
    [$family, $card] = $row;
    $s = gaScenario($slug);
    if ($family === 'taunt') {
        [, , $clause, $bonus] = $row;
        return $s->deckTemplate('quiet', $card)
            ->readyFor($card, classBonus: $bonus)
            ->seed('myField', $card, properties: ['Status' => 2])
            ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
            ->endTurn()->action(2, 10002, 'myField-1!FSM!')
            ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while the taunting ally is awake')
            // Also prove that a legal attack can proceed; rejection alone could pass for the wrong reason.
            ->expectDamage('p1Field-0', 0, 'Rejected target leaves the champion undamaged', step: 3)
            ->answer(2, 'theirField-1')
            ->tested($card)->covers(['taunt', 'attack-declaration'], $clause);
    }
    if ($family === 'recover') {
        [, , $amount, $payment, $window, $damage] = $row;
        return $s->standardDeck($card)->readyFor($card, damage: $damage)
            ->expectDamage('p1Field-0', $damage, 'Recovery starts with the specified damage', step: 0)
            ->seed('myHand', $card)->castSpell($card, pay: $payment, window: $window)
            ->expectDamage('p1Field-0', max(0, $damage - $amount), "Recover $amount from $damage damage, bounded at zero")
            ->expectCardExists('myGraveyard', $card, 'The recovery card resolved')
            ->expectDqEmpty()->tested($card)->covers(['recover'], "**Recover $amount**.");
    }
    throw new RuntimeException("Unknown GA behavior family: $family");
}

function gaCheckBehaviorStep(array $definition, string $family, int $step): void {
    [$passed, $message] = RegressionEvaluateAssertionsForStep($definition['assertions'] ?? [], $step);
    if (!$passed) throw new RuntimeException("Step $step assertion failed: $message");
    if (in_array($family, ['taunt', 'stealth'], true) && in_array($step, [3, 4], true)) {
        // Preserve the meaningful part of the former final snapshot: the target prompt must
        // still offer exactly the legal defender after rejection, not an unrelated prompt.
        $queue = array_values(array_filter((array)GetDecisionQueue(2), fn($d) => is_object($d) && empty($d->removed)));
        $choice = $queue[0] ?? null;
        $target = $family === 'taunt' ? 'theirField-1' : 'theirField-0';
        $choosingLegalTarget = $choice !== null && $choice->Type === 'MZCHOOSE' && $choice->Param === $target;
        if ($step === 3 && !$choosingLegalTarget) {
            throw new RuntimeException(ucfirst($family) . ' must leave a target choice offering exactly ' . $target);
        }
        if ($step === 4 && $choosingLegalTarget) throw new RuntimeException('Choosing the legal target must advance the attack');
    }
}

// Semantic inventory tools use the same definitions, including cases without a saved directory.
function gaBehaviorDefinitions(): array {
    require_once dirname(__DIR__, 3) . '/Core/EngineActionRunner.php';
    require_once dirname(__DIR__) . '/Scenario.php';
    $definitions = [];
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 2) . '/create-prd-ability-fixtures.php') . ' --export-shared-families';
    exec($command, $output, $status);
    if ($status !== 0) throw new RuntimeException('Cannot load shared behavior definitions');
    $shared = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
    foreach (gaBehaviorCases() as $slug => $row) {
        if ($row[1] !== '@shared') $definitions[$slug] = gaBehaviorScenario($slug, $row)->build();
        else {
            if (($shared[$slug]['meta']['behaviorFamily'] ?? null) !== $row[0]) throw new RuntimeException("Shared family mismatch: $slug");
            $definitions[$slug] = $shared[$slug];
        }
    }
    // Keep historical coverage contracts and notes retained in meta.json. Live source
    // definitions take precedence when they declare updated metadata.
    foreach ($definitions as $slug => &$definition) {
        $path = dirname(__DIR__, 3) . '/Tests/Integration/GrandArchiveSim/' . $slug . '/meta.json';
        if (is_file($path)) {
            $saved = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $definition['meta'] = array_replace($saved, $definition['meta'] ?? []);
        }
    }
    unset($definition);
    return $definitions;
}
