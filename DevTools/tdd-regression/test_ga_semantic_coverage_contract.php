<?php
// READ-ONLY guard for GrandArchive semantic-coverage contract classification.
//
// A drained decision queue is useful cleanup evidence, but it does not prove a
// card resolved correctly. Positive contracts require an observable state
// assertion. Negative contracts may instead prove that an illegal action was
// explicitly rejected.

chdir(dirname(dirname(__DIR__)));
require_once './DevTools/GaSemanticCoverage.php';

$failures = 0;
$check = function (bool $condition, string $label) use (&$failures): void {
    echo ($condition ? 'PASS' : 'FAIL') . ": {$label}\n";
    if (!$condition) ++$failures;
};

$meta = [
    'semanticCoverage' => [
        'testedCards' => ['test-card'],
        'mechanics' => ['zone-movement'],
        'rulesClauses' => ['Move the tested card to another zone.'],
    ],
];

$queueOnly = [[
    'type' => 'decision_queue_empty',
    'player' => 1,
    'semantic' => true,
]];
$observable = [[
    'type' => 'card_property_equals',
    'mzId' => 'p1Hand-0',
    'property' => 'CardID',
    'value' => 'test-card',
    'semantic' => true,
]];
$rejectedAction = [[
    'playerID' => 1,
    'mode' => 100,
    'cardID' => 'illegal-target',
    'expectFailure' => true,
    'semantic' => true,
]];

$check(
    !GaSemanticContractIsComplete($meta, $queueOnly),
    'queue-empty evidence alone does not complete a positive semantic contract'
);
$check(
    GaSemanticContractIsComplete($meta, $observable),
    'an observable card-state assertion completes a positive semantic contract'
);
$check(
    GaSemanticContractIsComplete($meta, [], $rejectedAction),
    'an expected semantic rejection completes a negative semantic contract'
);
$check(
    !GaSemanticContractIsComplete($meta, [], [[
        'expectFailure' => true,
        'semantic' => false,
    ]]),
    'an unmarked rejection does not count as semantic evidence'
);

echo $failures === 0 ? "\nALL PASS\n" : "\n{$failures} FAILED\n";
exit($failures === 0 ? 0 : 1);
