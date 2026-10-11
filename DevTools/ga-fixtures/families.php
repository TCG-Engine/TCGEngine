<?php
/**
 * Fixture families that the generator used to build in loops or from helper closures, written as scenarios.
 */

// --- Shackled Theurgist (vkqzk1jik7): "On Death: Target opponent may sacrifice an ally. If they don't, return Shackled
// Theurgist to the field. It gets +2 LIFE until end of turn and becomes ephemeral." ---
// The Theurgist is killed for real by Drenching Finish (2 power vs the Theurgist's 2 life); the On Death trigger goes on the
// effect stack and the opponent of its controller is asked "Sacrifice an ally?". $ctrl controls the Theurgist (field-1) and
// the OTHER player ($opp) kills it. The controller also has its own Dungeon Guide ally (field-2) -- the opponent must never be
// offered THAT ally to sacrifice. $oppAlly: whether the killer has a Dungeon Guide-like ally of its own (Fluffy Shopkeep) so
// the "Sacrifice an ally?" prompt exists.
function gaTheurgistKill(int $ctrl, bool $oppAlly = true): GaScenario {
    global $gaTheurgistDeck;
    $opp = $ctrl == 1 ? 2 : 1;
    $scenario = gaScenario('')->deck($gaTheurgistDeck)
        ->seed('myField', 'Shackled Theurgist', $ctrl)
        ->seed('myField', 'Dungeon Guide', $ctrl); // the controller's own ally: must NOT be offered for sacrifice
    if ($oppAlly) $scenario->seed('myField', 'Fluffy Shopkeep', $opp); // the opponent's only legal sacrifice
    $scenario->seed('myHand', 'Drenching Finish', $opp);
    // Player 1 attacks on its turn 3, player 2 on its turn 2.
    if ($opp == 1) $scenario->endTurn(1)->endTurn(2); else $scenario->endTurn(1);
    return $scenario->play('Drenching Finish', $opp)->pay(2, $opp)->answer($opp, 'theirField-1')
        ->tested('Shackled Theurgist');
}
foreach ([2, 1] as $gaTheurgistCtrl) {
    $gaTheurgistOpp = $gaTheurgistCtrl == 1 ? 2 : 1;
    $gaTheurgistTag = 'p' . $gaTheurgistCtrl . '-controlled';
    gaTheurgistKill($gaTheurgistCtrl)->answer($gaTheurgistOpp, 'NO')
        ->register("shackled-theurgist-$gaTheurgistTag-opponent-declines-sacrifice-returns-ephemeral");
    gaTheurgistKill($gaTheurgistCtrl)->answer($gaTheurgistOpp, 'YES')
        ->rejects($gaTheurgistOpp, 'theirField-1', "The controller's own Dungeon Guide was never offered: the opponent can only sacrifice ITS OWN ally")
        ->answer($gaTheurgistOpp, 'myField-1') // sacrifice the opponent's own Fluffy Shopkeep
        ->register("shackled-theurgist-$gaTheurgistTag-opponent-sacrifices-own-ally-stays-dead");
    gaTheurgistKill($gaTheurgistCtrl, false)
        ->register("shackled-theurgist-$gaTheurgistTag-opponent-has-no-ally-returns-ephemeral");
}

// --- Bygone Days (wXNDSNQ2x2): "At the beginning of your recollection phase, cascade -- 1: Recover 2. 2: Recover 1.
// 3: Each player chooses an ally they control and returns it to its owner's memory. Sacrifice CARDNAME." ---
// The phantasia is played for real; its cascade then triggers on the controller's 1st/2nd/3rd recollection phase.
// $ctrl controls Bygone Days. Setup: controller champion damaged 5 (Recover 2 -> 3, Recover 1 -> 2); controller ally =
// Dungeon Guide (field-1); opponent allies = Fluffy Shopkeep (field-1) + Dungeon Guide (field-2).
foreach ([1, 2] as $gaBygoneCtrl) {
    $gaBygoneOpp = $gaBygoneCtrl == 1 ? 2 : 1;
    $scenario = gaScenario("bygone-days-p$gaBygoneCtrl-controlled-cascade-recovers-then-each-player-returns-ally")
        ->deck($gaBygoneDeck)
        ->patch('myField-0', ['Damage' => 5], $gaBygoneCtrl)
        ->seed('myField', 'Dungeon Guide', $gaBygoneCtrl)
        ->seed('myField', 'Fluffy Shopkeep', $gaBygoneOpp)
        ->seed('myField', 'Dungeon Guide', $gaBygoneOpp)
        ->seed('myHand', 'Bygone Days', $gaBygoneCtrl);
    // Play + 2 reserve, then pass turns until the controller's 3rd recollection phase.
    if ($gaBygoneCtrl == 2) $scenario->endTurn(1);
    $scenario->play('Bygone Days', $gaBygoneCtrl)->pay(2, $gaBygoneCtrl);
    for ($i = 0; $i < 3; ++$i) $scenario->endTurn($gaBygoneCtrl)->endTurn($gaBygoneOpp);
    $scenario
        ->answer($gaBygoneCtrl, 'myField-1') // the controller returns its own Dungeon Guide to its memory
        ->rejects($gaBygoneOpp, 'myField-0', "The opponent's champion is not an ally (and was never offered): it cannot be chosen")
        // the opponent returns one of ITS OWN two allies (player 1 as opponent: its 2nd ally, player 2 as opponent: its 1st)
        ->answer($gaBygoneOpp, $gaBygoneOpp == 2 ? 'myField-2' : 'myField-1')
        ->tested('Bygone Days')
        ->register();
}

// --- Heirloom of Natura / Spectra: the second ability is "(3), Banish CARDNAME: Draw a card into your memory." The (3) was
// never paid (and Spectra's ability never banished the Heirloom, leaving it on the field to be used again). ---
foreach (['natura' => 'Heirloom of Natura', 'spectra' => 'Heirloom of Spectra'] as $gaHeirloomSlug => $gaHeirloomName) {
    gaScenario("heirloom-of-$gaHeirloomSlug-draw-ability-pays-three-and-banishes-itself")
        ->deck($gaSweepDeck('Spirit of Fire'))
        ->seed('myField', $gaHeirloomName)
        ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(3) // (3), banish: draw a card into your memory
        ->tested($gaHeirloomName)
        ->register();
}

// --- Lamentation's Toll (1t3dvor61i): "[Level 2+] Lamentation's Toll gets +X power, where X is the highest power stat among
// your omens." --- Player 2 attacks with Lamentation's Toll while holding two omens (The Majestic Spirit, power 4, and a
// Dungeon Guide, power 1).
foreach ([
    'lamentations-toll-level-two-gets-the-highest-omen-power' => 'Ciel, Omenbringer', // level 2
    'lamentations-toll-level-one-gets-no-omen-power' => 'Ciel, Loyal Valet',          // level 1
] as $gaTollSlug => $gaTollChampion) {
    gaScenario($gaTollSlug)
        ->deck($gaSweepDeck('Spirit of Fire'))
        ->champion(cardName: $gaTollChampion, player: 2)
        ->seed('myField', 'Grande Aiguille', 2) // Sword -> myField-1
        ->seed('myBanish', 'The Majestic Spirit', 2, ['Counters' => ['omen' => 1]])
        ->seed('myBanish', 'Dungeon Guide', 2, ['Counters' => ['omen' => 1]])
        ->seed('myHand', "Lamentation's Toll", 2)
        ->endTurn(1)->play("Lamentation's Toll", 2)->pay(4, 2)->answer(2, 'myField-1')->answer(2, 'theirField-0')
        ->tested("Lamentation's Toll")
        ->register();
}

// --- Seeker's Aetherwing (bf7yzaqes4): "[Class Bonus] Spellshroud. [Class Bonus] True Sight." Player 2 tries to destroy it
// with Ghastly Corrosion (destroy target item or weapon with memory cost 0 or reserve cost 3 or less). ---
foreach ([true, false] as $gaSeekerRanger) {
    $scenario = gaScenario($gaSeekerRanger
        ? 'seekers-aetherwing-class-bonus-spellshroud-cannot-be-targeted-by-a-spell'
        : 'seekers-aetherwing-without-the-class-bonus-can-be-targeted-by-a-spell')
        ->deck($gaSweepDeck('Spirit of Fire'));
    if ($gaSeekerRanger) $scenario->champion(classes: 'RANGER');
    $scenario->seed('myField', "Seeker's Aetherwing")->seed('myHand', 'Ghastly Corrosion', 2)
        ->endTurn(1)->play('Ghastly Corrosion', 2)->pay(4, 2);
    if ($gaSeekerRanger) $scenario->rejects(2, 'theirField-1', "Seeker's Aetherwing has spellshroud (Ranger champion): not a legal Spell target");
    else $scenario->answer(2, 'theirField-1');
    $scenario->tested("Seeker's Aetherwing")->register();
}

// --- Fractal of Insight: On Enter -- decline to rest self, Glimpse does not happen ---
gaScenario('fractal-of-insight-decline-no-glimpse')
    ->base('fractal-of-insight-rest-glimpse-2')
    ->deck(<<<'DECK'
# Material
1 Spirit of Water
1 Diao Chan, Enchantress
1 Backup Charger
1 Cleric Robes
1 Scepter of Fascination
# Main
4 Fractal of Insight
4 Dungeon Guide
4 Fluffy Shopkeep
4 Frostbinder Apostle
4 Awakened Deacon
DECK
    )
    ->champion(cardName: 'Diao Chan, Enchantress')
    ->seed('myHand', 'Fractal of Insight') // seeded to a known hand slot
    ->play('Fractal of Insight')->pay(2)->answer(1, 'NO') // decline to rest Fractal of Insight
    ->tested('Fractal of Insight')
    ->register();
