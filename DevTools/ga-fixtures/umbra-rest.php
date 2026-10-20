<?php
/**
 * Phase 3e (Umbra), the card left over after umbra.php: Tristan, Shadowreaver. A second Tristan, Shadowreaver is seeded as a champion so its On Enter runs (a starting champion's printed On Enter
 * is resolved in the pregame instead).
 */

// ---- Tristan, Shadowreaver ----

gaScenario('tristan-shadowreaver-on-enter-banishes-the-top-four-cards-of-the-opponents-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->deckTop(['Dormouse Informant', 'Cunning Broker', 'Lurking Assailant', 'Corrosive Juggler'], player: 2)
    ->seed('myField', 'Tristan, Shadowreaver')
    ->observe()
    ->expectZoneCount('theirBanish', 4, 1, 'Four of the opponent\'s cards are banished')
    ->expectCardExists('theirBanish', 'Dormouse Informant', 'The top card of the opponent\'s deck is among them')
    ->tested('Tristan, Shadowreaver')
    ->covers(['on-enter', 'banish', 'face-down'], '**On Enter:** Banish the top four cards of target opponent’s deck face down.')
    ->register();

gaScenario('tristan-shadowreaver-may-play-a-card-it-banished-from-the-opponents-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->deckTop(['Dormouse Informant', 'Cunning Broker', 'Lurking Assailant', 'Corrosive Juggler'], player: 2)
    ->seed('myField', 'Tristan, Shadowreaver')
    ->action(1, 10002, 'theirBanish-0!FSM!')->pay(3)
    ->expectCardExists('myField', 'Dormouse Informant', 'Player 1 activated the opponent\'s banished Dormouse Informant')
    ->expectZoneCount('theirBanish', 3, 1, 'It left the opponent\'s banishment')
    ->tested('Tristan, Shadowreaver')
    ->covers(['play-from-banish', 'ignore-element'], 'As long as they’re banished, you may play those cards, ignoring their elemental requirements.')
    ->register();
