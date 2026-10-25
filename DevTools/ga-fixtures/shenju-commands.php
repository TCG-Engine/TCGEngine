<?php
// Skills can target spellshrouded Beasts belonging to either player.
foreach ([1 => 'friendly', 2 => 'opposing'] as $owner => $side) {
    $target = $owner === 1 ? 'myField-1' : 'theirField-1';
    gaScenario('genbus-command-targets-' . $side . '-spellshrouded-shenju')
        ->standardDeck("Genbu's Command")
        ->readyFor("Genbu's Command", damage: 6)
        ->seed('myField', 'Genbu, Black Tortoise', player: $owner)
        ->seed('myHand', "Genbu's Command")
        ->castSpell("Genbu's Command", pay: 2, window: 'both', target: $target)
        ->expectLife('p' . $owner . 'Field-1', 12, 'Spellshroud does not prevent this Skill granting +3 LIFE')
        ->expectDamage('p1Field-0', 0, 'The activating champion recovers 6 for a Shenju target')
        ->expectDqEmpty()
        ->tested("Genbu's Command")
        ->covers(['targeting', 'life', 'recover'], 'Target Beast ally gets +3 [LIFE] until end of turn. If that ally is a Shenju, recover 6.')
        ->register();

    gaScenario('suzakus-command-targets-' . $side . '-spellshrouded-shenju')
        ->standardDeck("Suzaku's Command")
        ->readyFor("Suzaku's Command")
        ->seed('myField', 'Suzaku, Vermillion Phoenix', player: $owner)
        ->seed('myHand', "Suzaku's Command")
        ->castSpell("Suzaku's Command", pay: 2, window: 'both', target: $target)
        ->expectProperty('p' . $owner . 'Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","5v598k3m1w-SHENJU"]', 'The Skill grants the Shenju next-attack effect through spellshroud')
        ->expectDqEmpty()
        ->tested("Suzaku's Command")
        ->covers(['targeting', 'shenju'], "Target Beast ally's next attack this turn gains **unblockable** and gets +2 [POWER].")
        ->register();
}

// Turn two gives the seeded Beast a legal attack without entered-this-turn restrictions.
foreach (['Direwolf' => [2, 4], 'Suzaku, Vermillion Phoenix' => [4, 8]] as $beast => [$basePower, $attackPower]) {
    $kind = $basePower === 4 ? 'shenju' : 'ordinary-beast';
    gaScenario('suzakus-command-' . $kind . '-bonus-ends-with-combat')
        ->standardDeck('Red Slime')
        ->readyFor("Suzaku's Command", player: 2)
        ->seed('myField', $beast, player: 2, properties: ['Status' => 2])
        ->seed('myField', 'Genbu, Black Tortoise', player: 1)
        ->seed('myHand', "Suzaku's Command", player: 2)
        ->endTurn()->pass(2)->pass(2)
        ->castSpell("Suzaku's Command", pay: 2, target: 'myField-1', player: 2)
        ->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
        ->expectPower('p2Field-1', $basePower, 'Pending next-attack bonus does not change the Beast POWER', step: 7)
        ->expectDamage('p1Field-0', $attackPower, 'The attack bypasses Genbu taunt and deals the enhanced POWER')
        ->expectPower('p2Field-1', $basePower, 'The consumed bonus is gone after combat')
        ->expectDqEmpty()
        ->tested("Suzaku's Command")
        ->covers(['next-attack', 'power', 'unblockable', 'expiration'], "Target Beast ally's next attack this turn gains **unblockable** and gets +2 [POWER].")
        ->register();
}

gaScenario('suzakus-command-unused-bonus-expires')
    ->deckTemplate("quiet", "Red Slime")
    ->readyFor("Suzaku's Command")
    ->seed('myField', 'Suzaku, Vermillion Phoenix')
    ->seed('myHand', "Suzaku's Command")
    ->castSpell("Suzaku's Command", pay: 2, target: 'myField-1')
    ->endTurn()
    ->expectProperty('p1Field-1', 'TurnEffects', '[]', 'The unused next-attack effect expires at end of turn')
    ->expectPower('p1Field-1', 4, 'No bonus carries into the following turn')
    ->tested("Suzaku's Command")
    ->covers(['next-attack', 'expiration'], "Target Beast ally's next attack this turn gains **unblockable** and gets +2 [POWER].")
    ->register();

foreach (['Direwolf' => 3, 'Suzaku, Vermillion Phoenix' => 8] as $beast => $damage) {
    gaScenario('suzakus-command-' . ($damage === 8 ? 'shenju-bypasses' : 'ordinary-beast-respects') . '-prevention')
        ->standardDeck('Red Slime')
        ->readyFor("Suzaku's Command", player: 2)
        ->seed('myField', $beast, player: 2, properties: ['Status' => 2])
        ->seed('myField', 'Protective Fractal', player: 2, properties: ['Status' => 2])
        ->seed('myHand', "Suzaku's Command", player: 2)
        ->endTurn()->answer(2, '-')->answer(2, '-')
        ->castSpell("Suzaku's Command", pay: 2, window: 'me', target: 'myField-1', player: 2)
        ->action(2, 10001, 'myField-2!CustomInput!Activate:0')->answer(2, 'theirField-0')->answer(2, '-')
        ->attack('myField-1', 'theirField-0', player: 2)->answer(2, '-')->answer(1, '-')
        ->expectDamage('p1Field-0', $damage, 'Only the Shenju bonus bypasses the one-point prevention shield')
        ->expectPower('p2Field-1', $damage === 8 ? 4 : 2, 'The attack bonus ends with combat')
        ->expectDqEmpty()
        ->tested("Suzaku's Command")
        ->covers(['shenju', 'unpreventable'], 'If that ally is a Shenju, that attack gets an additional +2 [POWER] and its combat damage is unpreventable.')
        ->register();
}
