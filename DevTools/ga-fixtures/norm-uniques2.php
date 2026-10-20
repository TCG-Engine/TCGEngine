<?php
/**
 * Phase 3d (Norm): unique allies, second batch (imbue 3 archangels). The imbue YES/NO precedes payment and the payment is the hand pick of the revealed cards. 'quiet' deck.
 */

if (!function_exists('gaNormImbue3')) {
    // $imbuers: three hand cards that satisfy (or, for the control, fail) the imbue.
    function gaNormImbue3(string $slug, string $card, array $imbuers, callable $observe, array $mechanics, string ...$clauses): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor($card);
        if (str_contains($slug, "haniel") || str_contains($slug, "azrael")) $s->seed("myField", "Delivery Droid", player: 2);
        foreach ($imbuers as $c) $s->seed('myHand', $c);
        $s->seed('myHand', $card);
        $s->play($card)->answer(1, 'YES');
        for ($i = 0; $i < CardCost_reserve(GaScenario::id($card)); ++$i) $s->answer(1, 'myHand-7');
        $observe($s);
        return $s->tested($card)->covers($mechanics, ...$clauses);
    }
}

gaNormImbue3('haniel-imbued-enter-debuffs-an-opposing-ally-buffs-itself-and-recovers-one', 'Haniel, Archangel of Spectra', ['Ghastly Slime', 'Lustrous Slime', 'Ghastly Slime'],
    fn($s) => $s->answer(1, 'theirField-1')->expectProperty('p2Field-1', 'Counters', '{"debuff":1}', 'Imbued: the opposing ally got a debuff counter')->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Haniel got a buff counter'),
    ['imbue', 'on-enter', 'counter', 'recover', 'condition'], '**On Enter:** If CARDNAME is **imbued**, put a **debuff** counter on target ally you don\'t control, put a **buff** counter on CARDNAME, and **recover 1**.')->register();

gaNormImbue3('nuriel-advanced-imbue-three-enter-puts-a-bulwark-and-a-buff-counter-on-it', 'Nuriel, Seraphic Paladin', ['Ghastly Slime', 'Lustrous Slime', 'Fulgurite Coordinator', 'Dungeon Guide'],
    fn($s) => $s->expectProperty('p1Field-1', 'Counters', '{"bulwark":1,"buff":1}', 'Imbued: a bulwark and a buff counter'),
    ['imbue', 'advanced-imbue', 'on-enter', 'counter', 'bulwark'], '**On Enter: ** If CARDNAME is **imbued**, put a **bulwark** and a **buff** counter on it.')->register();

gaNormImbue3('nuriel-without-three-advanced-cards-is-not-imbued-and-gets-no-counters', 'Nuriel, Seraphic Paladin', ['Dungeon Guide', 'Dungeon Guide', 'Ghastly Slime', 'Dungeon Guide'],
    fn($s) => $s->expectProperty('p1Field-1', 'Counters', '[]', 'Only one advanced card: not imbued, no counters'),
    ['imbue', 'advanced-imbue', 'condition'], '**Advanced Imbue 3**')->register();

gaNormImbue3('shangxiang-imbue-three-with-norm-cards-becomes-imbued', 'Shangxiang, Fierce Princess', ['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'],
    fn($s) => $s->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Three norm element cards: imbued'),
    ['imbue'], '**Imbue 3** *(You may reserve all cards revealed as you activate this card. If at least three of them are norm element, this card becomes **imbued**.)*')->register();

gaNormImbue3('azrael-imbued-enter-destroys-another-object-and-its-controller-summons-a-spirit-shard', 'Azrael, Archangel of Materia', ['Elysian Aspirant', 'Elysian Aspirant', 'Throne Sentinel'],
    fn($s) => $s->answer(1, 'theirField-1')->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Exia and neos cards: imbued')->expectCardExists('myGraveyard', 'Delivery Droid', 'The opposing object with reserve cost 1 was destroyed', 2)->expectCardExists('myField', 'Spirit Shard', 'Its controller summoned a Spirit Shard', 2),
    ['imbue', 'on-enter', 'destroy'], '**On Enter:** As a Spell, if CARDNAME is **imbued**, destroy another target non-regalia non-champion object with reserve cost 3 or less.')->register();

gaNormImbue3('ariel-imbued-enter-reveals-the-top-card-into-the-material-deck-preserved', 'Ariel, Archangel of Natura', ['Avatar of Gaia', 'Avatar of Gaia', 'Avatar of Gaia'],
    fn($s) => $s->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Tera cards: imbued'),
    ['imbue', 'on-enter', 'zone-movement'], '**On Enter:** If CARDNAME is **imbued**, reveal the top card of your deck and put it into your material deck **preserved**.')->register();

gaNormImbue3('raziel-imbued-enter-banishes-two-memory-cards-glimpses-three-and-draws-two-into-memory', 'Raziel, Archangel of Libra', ['Fulgurite Coordinator', 'Astromech Attendant', 'Fulgurite Coordinator'],
    fn($s) => $s->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Arcane and astra cards: imbued'),
    ['imbue', 'on-enter', 'glimpse'], '**On Enter:** If CARDNAME is **imbued**, banish two cards at random from your memory. If you do, **glimpse 3** and then put the top two cards of your deck into your memory.')->register();
