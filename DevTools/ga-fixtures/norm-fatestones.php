<?php
/**
 * Phase 3d (Norm): Fatestones ([Guo Jia Bonus]). The starting champion is patched into Guo Jia, Chosen Disciple. 'quiet' deck.
 */

// ---- Idle Fatestone ----

if (!function_exists('gaIdleFatestone')) {
    // $top: the deck's top card (known reserve cost) revealed by the ability.
    function gaIdleFatestone(string $slug, string $top): GaScenario {
        return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
            ->champion(cardName: 'Guo Jia, Chosen Disciple')
            ->deckTop([$top])
            ->seed('myField', 'Idle Fatestone')->answer(1, 'Top=' . GaScenario::id($top) . ',px60u5n1do,em6eEh9q8y,em6eEh9q8y;Bottom=');
    }
}

$gaIdleClause = '[Guo Jia Bonus] [REST], *(2)*: Reveal the top card of your deck. If that card\'s reserve cost is even, put a **buff** counter on Idle Fatestone. Otherwise, transform Idle Fatestone.';

gaIdleFatestone('idle-fatestone-guo-jia-bonus-an-even-reserve-cost-puts-a-buff-counter-on-it', 'Weiss Bishop')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The revealed Weiss Bishop costs 2 (even): a buff counter')
    ->expectProperty('p1Field-1', 'CardID', 'qiv63tpshe', 'It stays an Idle Fatestone')
    ->tested('Idle Fatestone')->covers(['guo-jia-bonus', 'activated-ability', 'rest', 'reveal', 'counter', 'condition'], $gaIdleClause)->register();

gaIdleFatestone('idle-fatestone-guo-jia-bonus-an-odd-reserve-cost-transforms-it', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->expectProperty('p1Field-1', 'CardID', '0v8zzzb83i', 'The revealed Dungeon Guide costs 3 (odd): Idle Fatestone transformed into Bolstered Boar')
    ->tested('Idle Fatestone')->covers(['guo-jia-bonus', 'activated-ability', 'reveal', 'transform', 'condition'], $gaIdleClause)->register();

gaScenario('idle-fatestone-without-the-guo-jia-bonus-cannot-be-activated')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Idle Fatestone')->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Status', '2', 'No Guo Jia champion: the ability was refused and the Fatestone stays awake')
    ->tested('Idle Fatestone')->covers(['guo-jia-bonus', 'condition'], '[Guo Jia Bonus] [REST], *(2)*')->register();

// ---- Fatestone of Balance ----

if (!function_exists('gaFatestoneBalance')) {
    // Player 2 holds $memory cards in memory and activates Aenean Ward (1) on its turn; the Fatestone belongs to a Guo Jia player 1.
    function gaFatestoneBalance(string $slug, int $memory, string $expected, string $label): void {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
            ->champion(cardName: 'Guo Jia, Chosen Disciple')
            ->seed('myField', 'Fatestone of Balance')
            ->seed('myHand', 'Aenean Ward', player: 2);
        for ($i = 0; $i < $memory; ++$i) $s->seed('myMemory', 'Dungeon Guide', player: 2);
        $s->endTurn()->answer(2, 'myHand-7')->pay(1, 2)->target('theirField-0', 2)
            ->expectProperty('p1Field-1', 'CardID', $expected, $label)
            ->tested('Fatestone of Balance')
            ->covers(['guo-jia-bonus', 'trigger', 'transform', 'condition'], '[Guo Jia Bonus] Whenever an opponent activates a card, if that opponent has exactly three cards in their memory, transform Fatestone of Balance.')
            ->register();
    }
}

gaFatestoneBalance('fatestone-of-balance-guo-jia-bonus-an-opponent-activating-with-exactly-three-memory-cards-transforms-it', 2, '0qmwwihhn2', 'Three cards in memory once the 1-cost is paid: it became Woodland Shoats');
gaFatestoneBalance('fatestone-of-balance-guo-jia-bonus-an-opponent-with-a-different-memory-count-leaves-it', 0, 'v4gtq1ibth', 'Only one card in memory: no transform');
gaFatestoneBalance('fatestone-of-balance-guo-jia-bonus-an-opponent-with-four-memory-cards-leaves-it', 3, 'v4gtq1ibth', 'Four cards in memory once the 1-cost is paid: no transform');
