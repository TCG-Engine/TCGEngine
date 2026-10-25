<?php
foreach([['discount', 'YES', 0, 'CLERIC', 1], ['full', 'NO', 3, 'CLERIC', 1], ['opposing-banish', 'YES', 0, 'CLERIC', 2], ['no-class', null, 3, 'WARRIOR', 1]] as [$suffix, $mode, $cost, $classes, $banishPlayer]) {
    $scenario = gaScenario('astral-target-cost-' . $suffix)
        ->deck($fillerDeck)->champion(lineage: ['Arisanna, Astral Zenith'], classes: $classes)
        ->seed('myBanish', 'Dungeon Guide', player: $banishPlayer)
        ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Astral Seal')
        ->play('Dungeon Guide')->pay(3)->target('myHand-4');
    if($mode !== null) $scenario->answer(1, $mode);
    $scenario->pay($cost)
        ->answer(1, 'EffectStack-0')
        ->expectZoneCount('myMemory', 3 + $cost, 1, 'Only the declared qualifying category receives the discount')
        ->expectZoneCount('myBanish', $banishPlayer === 1 ? 2 : 1, 1, 'The chosen activation is banished')
        ->expectProperty('p1DecisionQueue-0', 'Param', $mode === 'YES' ? 'EffectStack-0' : 'EffectStack-0&EffectStack-1', 'The paid target category controls the resolution chooser', step: 5 + intval($mode !== null) + $cost)
        ->expectDqEmpty()->tested('Astral Seal')
        ->covers(['cost', 'targeting', 'class-bonus'], 'The discount is bound to banished-name targets before payment.')->register();
}
