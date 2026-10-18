<?php
/**
 * Phase E (Exalted): Aenean Repudiation's Aenean Progression cost, Mirrorbound Covenant's cap on the opposing player, Labyrinth's stealth.
 */

const GA_PROGRESSION_TEXT = '**Aenean Progression** *(This card costs (2) more to activate for each time you\'ve resolved a card with **Aenean progression** this game.)*';

gaScenario('aenean-repudiation-second-activation-costs-two-more-from-aenean-progression')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Aenean Repudiation')
    ->seed('myHand', 'Aenean Repudiation')->seed('myHand', 'Aenean Repudiation')
    ->castSpell('Aenean Repudiation', pay: 2, window: 'both')
    ->castSpell('Aenean Repudiation', pay: 4, window: 'both')
    ->expectZoneCount('myGraveyard', 2, 1, 'Both Repudiations resolved: the second one paid 2 + 2')
    ->expectZoneCount('myMemory', 6, 1, 'Two paid for the first and four for the second')
    ->expectDqEmpty()
    ->tested('Aenean Repudiation')
    ->covers(['aenean-progression', 'cost-increase'], GA_PROGRESSION_TEXT)
    ->register();

// ---- Mirrorbound Covenant ----

const GA_MIRRORBOUND_TEXT = 'Each player\'s maximum **influence** is seven. *(As each player\'s end phase resolves, that player discards cards from their hand and/or memory until their **influence** is equal to their maximum influence.)*';

// The Covenant belongs to player 2, but it caps player 1 too: player 1 starts with nine cards and discards two at their own end phase.
gaScenario('mirrorbound-covenant-caps-the-opposing-players-influence-at-their-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Mirrorbound Covenant', player: 2)
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->endTurn()->answer(1, 'myHand-0&myHand-1')
    ->expectZoneCount('myGraveyard', 2, 1, 'Two cards discarded to reach influence seven')
    ->expectZoneCount('myHand', 7, 1, 'Nine cards minus two')
    ->expectDqEmpty()
    ->tested('Mirrorbound Covenant')
    ->covers(['static', 'influence', 'discard', 'opposing-player'], GA_MIRRORBOUND_TEXT)
    ->register();

// Control: with no Covenant on either side the same nine cards stay (the normal hand-size rule is not the influence cap).
gaScenario('mirrorbound-covenant-absent-the-end-phase-does-not-cap-influence-at-seven')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->endTurn()
    ->expectZoneCount('myHand', 9, 1, 'No influence cap without the Covenant')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing discarded')
    ->tested('Mirrorbound Covenant')
    ->covers(['static', 'influence', 'condition'], GA_MIRRORBOUND_TEXT)
    ->register();

// ---- Labyrinth, Jeweled Opus ----

const GA_LABYRINTH_STEALTH_TEXT = '[Sheen 10+] Your champion has **spellshroud** and **stealth** as long as they\'re awake and an opponent with the most cards in their memory has six or less cards in their memory.';

// Player 2's Dungeon Guide attacks: an awake champion with stealth is not a legal attack target, so the attack can only go to player 1's Dungeon Guide.
function gaLabyrinthTarget(string $slug, int $sheen) {
    return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
        ->seed('myField', 'Labyrinth, Jeweled Opus')
        ->seed('myMastery', 'Fractured Memories', properties: ['Counters' => ['sheen' => $sheen]])
        ->seed('myField', 'Dungeon Guide')
        ->seed('myField', 'Dungeon Guide', player: 2)
        ->endTurn()
        ->action(2, 10002, 'myField-1!FSM!');
}

gaLabyrinthTarget('labyrinth-jeweled-opus-sheen-ten-gives-your-champion-stealth-so-it-cannot-be-attacked', 10)
    ->rejects(2, 'theirField-0', 'Stealth: the awake champion is not a legal attack target', 100)
    ->answer(2, 'theirField-2')
    ->expectDamage('p1Field-0', 0, 'The champion was never attacked')
    ->tested('Labyrinth, Jeweled Opus')
    ->covers(['sheen', 'stealth', 'static'], GA_LABYRINTH_STEALTH_TEXT)
    ->register();

gaLabyrinthTarget('labyrinth-jeweled-opus-sheen-nine-gives-no-stealth-so-the-champion-can-be-attacked', 9)
    ->answer(2, 'theirField-0')
    ->expectDamage('p1Field-0', 1, 'Without stealth the champion took the attack')
    ->tested('Labyrinth, Jeweled Opus')
    ->covers(['sheen', 'stealth', 'condition'], GA_LABYRINTH_STEALTH_TEXT)
    ->register();
