<?php
/**
 * The one table of GrandArchiveSim card-text mechanics that fixture tooling knows about.
 *
 * ga-scaffold.php reads it to decide, for each printed clause of a card, what the fixture must cover and what to tell the author.
 * A clause that matches NO row is a NEW EFFECT: the scaffold prints UNMATCHED for it and `--list` counts it. Promotion rule: write the
 * first one or two such cards by hand; the third card with the same clause shape gets a row here (and a Layer 2 helper in Scenario.php if
 * the assertion shape repeats).
 *
 * Row keys:
 *   re        regex over the card text / a single clause (required)
 *   tag       the covers() mechanic tag a fixture for this clause declares (required)
 *   hint      one line telling the author what the fixture must do ('' = nothing special)
 *   snippet   optional scenario lines pasted into the skeleton notes as a starting shape
 *   exemplar  true = list already-covered cards sharing this mechanic as copy-from examples
 *   shape     false = ignore this key when `--list` groups cards by shape (frequent, low-information rows)
 *   boiler    true = the clause needs no mechanic of its own (keyword reminder text); matching it only marks the clause understood
 */
return [
    'class-bonus'     => ['re' => '/\[Class Bonus\]|Class Bonus:/i', 'tag' => 'class-bonus', 'hint' => 'Write the bonus twin AND the no-bonus twin (readyFor(card, classBonus: true) / without).', 'exemplar' => false],
    'level'           => ['re' => '/\[Level (\d+)\+\]/', 'tag' => 'level-gate', 'hint' => 'readyFor(card, level: N) at the gate and one below it.', 'exemplar' => false],
    'floating-memory' => ['re' => '/Floating Memory/i', 'tag' => 'floating-memory', 'hint' => 'Pay a memory cost with the card in the graveyard (see gaFloatingPays in water-allies2.php).', 'exemplar' => true],
    'imbue'           => ['re' => '/\bImbue (\d+)/i', 'tag' => 'imbue', 'hint' => 'YESNO imbue prompt comes BEFORE payment: answer YES, then reserve the cards; test imbued and not-imbued.', 'exemplar' => true],
    'suppress'        => ['re' => '/\bsuppress\b/i', 'tag' => 'suppress', 'hint' => 'Banishes now, returns at the next end phase: assert banish after the effect, field after endTurn.', 'exemplar' => true],
    'glimpse'         => ['re' => '/\bGlimpse (\d+)/i', 'tag' => 'glimpse', 'hint' => 'Top/bottom prompts per card; assert the deck order.', 'exemplar' => true],
    'scavenge'        => ['re' => '/\bScavenge (\d+)/i', 'tag' => 'scavenge', 'hint' => 'Stack the deck with deckTop() so the scavenged card is in range and out of range.', 'exemplar' => true],
    'efficiency'      => ['re' => '/Efficiency/i', 'tag' => 'cost-reduction', 'hint' => 'Cost = printed - level; raise the level with readyFor(level:) and check the memory footprint.', 'exemplar' => true],
    'cost-reduction'  => ['re' => '/costs? (\d+) less/i', 'tag' => 'cost-reduction', 'hint' => 'Pay the reduced cost; the twin pays the printed cost and is refused with rejects().', 'exemplar' => true],
    'load'            => ['re' => '/\bload (?:CARDNAME|[A-Z][\w\' ]+) into an (?:Aetherwing|\w+) weapon/i', 'tag' => 'load', 'hint' => 'Needs a loadable weapon on the field; Gun/Bow loading is not exercised yet -- check ENGINE-FINDINGS first.', 'exemplar' => true],
    'ephemerate'      => ['re' => '/Ephemerate/i', 'tag' => 'ephemerate', 'hint' => 'Ephemerate cost is a second activation path; test both.', 'exemplar' => true],
    'delevel'         => ['re' => '/delevel/i', 'tag' => 'delevel-cost', 'hint' => 'Additional cost: needs a levelled champion (see Innervate Knowledge in water-actions2.php).', 'exemplar' => true],
    'additional-cost' => ['re' => '/As an additional cost/i', 'tag' => 'additional-cost', 'hint' => 'Seed what the cost needs, and test the refusal without it.', 'exemplar' => true],
    'alt-cost'        => ['re' => '/rather than pay/i', 'tag' => 'alternate-cost', 'hint' => 'Orphan risk: check a YESNO offer is queued (Song of Frost / Ravishing Finale were never offered).', 'exemplar' => true],
    'sacrifice'       => ['re' => '/\bsacrifice\b/i', 'tag' => 'sacrifice', 'hint' => '', 'shape' => false],
    'counter'         => ['re' => '/\b\*{0,2}(?:buff|enlighten|preparation|sheen|age|omen|foster|refinement)\*{0,2} counter/i', 'tag' => 'counter', 'hint' => '', 'shape' => false],
    'token'           => ['re' => '/\b(?:Summon|summon)\b.*token/', 'tag' => 'token', 'hint' => '', 'shape' => false],
    'distant'         => ['re' => '/distant/i', 'tag' => 'distant', 'hint' => '', 'shape' => false],
    'draw'            => ['re' => '/\bdraw\b/i', 'tag' => 'draw-discard', 'hint' => '', 'shape' => false],
    'damage'          => ['re' => '/deal[s]? [^.]*damage|damage/i', 'tag' => 'damage', 'hint' => '', 'shape' => false],
    'prevent'         => ['re' => '/\bprevent\b/i', 'tag' => 'prevent-damage', 'hint' => '', 'shape' => false],
    'wake-rest'       => ['re' => '/\b(?:wake up|rest)\b/i', 'tag' => 'status', 'hint' => '', 'shape' => false],
    'stat'            => ['re' => '/[+\-]\d+\s*\[(?:POWER|LIFE)\]/i', 'tag' => 'stat-change', 'hint' => '', 'shape' => false],
    'on-enter'        => ['re' => '/On Enter/i', 'tag' => 'on-enter', 'hint' => '', 'shape' => false],
    'on-attack'       => ['re' => '/On Attack/i', 'tag' => 'on-attack', 'hint' => '', 'shape' => false],
    'on-death'        => ['re' => '/On Death|dies/i', 'tag' => 'on-death', 'hint' => '', 'shape' => false],
    'deluge'          => ['re' => '/Deluge/i', 'tag' => 'deluge', 'hint' => '', 'shape' => false, 'exemplar' => true],
    'negate'          => ['re' => '/\bnegate\b/i', 'tag' => 'negate', 'hint' => 'Needs an opposing activation to negate (see water-negates*.php).', 'exemplar' => true],
    'omen'            => ['re' => '/\bomen\b/i', 'tag' => 'omen', 'hint' => '', 'shape' => false, 'exemplar' => true],
    'melody'          => ['re' => '/Melody|Harmonize|Harmony/i', 'tag' => 'melody', 'hint' => '', 'exemplar' => true],
    'bonus-champion'  => ['re' => '/\[(?:Merlin|Ciel|Alice|Nico|Kongming|Vanitas|Diao Chan|Guo Jia|Cao Cao) Bonus\]/i', 'tag' => 'champion-bonus', 'hint' => 'Needs that specific champion: not yet exercised, check ENGINE-FINDINGS.', 'exemplar' => true],
    // Keywords (promoted after the Wind unmatched report): each needs the keyword's observable behaviour, not just a play.
    'fast-activation' => ['re' => '/Fast Activation/i', 'tag' => 'fast-activation', 'hint' => 'Fast: the opponent gets a priority window after the play (pass both players).', 'shape' => false],
    'ranged'          => ['re' => '/\bRanged \d+/i', 'tag' => 'ranged', 'hint' => 'Assert the ranged attack is legal at distance and the Ranged N rule is what permits it.', 'shape' => false, 'exemplar' => true],
    'stealth'         => ['re' => '/\bstealth\b/i', 'tag' => 'stealth', 'hint' => 'Attack the stealth unit from the other side: untargetable without true sight.', 'shape' => false, 'exemplar' => true],
    'intercept'       => ['re' => '/\bintercept\b/i', 'tag' => 'intercept', 'hint' => 'The champion is attacked while this ally is awake: redirect prompt; rested ally cannot.', 'shape' => false, 'exemplar' => true],
    'taunt'           => ['re' => '/\btaunt\b/i', 'tag' => 'taunt', 'hint' => 'Another unit of yours cannot be targeted while this one is awake.', 'shape' => false, 'exemplar' => true],
    'true-sight'      => ['re' => '/true sight/i', 'tag' => 'true-sight', 'hint' => '', 'shape' => false],
    'pride'           => ['re' => '/\bPride \d+/i', 'tag' => 'pride', 'hint' => 'Disobeys below the champion level: test at and below the gate.', 'shape' => false, 'exemplar' => true],
    'hindered'        => ['re' => '/\bHindered\b/i', 'tag' => 'enters-rested', 'hint' => 'Enters the field rested.', 'shape' => false],
    'vigor'           => ['re' => '/\bvigor\b/i', 'tag' => 'vigor', 'hint' => '', 'shape' => false],
    'ally-link'       => ['re' => '/Ally Link/i', 'tag' => 'link', 'hint' => 'Link needs an ally to attach to; linked unit gets the clause. Test linked and unlinked.', 'shape' => false],
    'renewable'       => ['re' => '/\bRenewable\b/i', 'tag' => 'renewable', 'hint' => '', 'shape' => false],
    'lineage'         => ['re' => '/\bLineage\b/i', 'tag' => 'lineage', 'hint' => '', 'shape' => false],
    'prepare'         => ['re' => '/\bPrepare \d+/i', 'tag' => 'prepare', 'hint' => 'Remove a preparation counter from the champion as the activation cost.', 'shape' => false],
    'brew'            => ['re' => '/\bBrew\b/i', 'tag' => 'brew', 'hint' => 'Brew needs the herbs; test with and without them.', 'shape' => false],
    'command-chessman'=> ['re' => '/Command Chessman/i', 'tag' => 'command-chessman', 'hint' => 'Needs a Chessman ally to perform the attack.', 'shape' => false],
    // Zone effects and choices (promoted after the Wind unmatched report).
    'return-to-zone'  => ['re' => '/\breturn\b[^.]*\b(?:hand|hands|memory|memories|owner|deck)\b/i', 'tag' => 'zone-movement', 'hint' => 'Assert the zone of the moved card AND that a card outside the target set did not move.', 'shape' => false],
    'materialize'     => ['re' => '/\bMaterialize\b/i', 'tag' => 'materialize', 'hint' => 'Needs a matching card in the material deck; assert it arrives and what is paid.', 'shape' => false],
    'modal-choice'    => ['re' => '/\bChoose (?:one|two)\b/i', 'tag' => 'modal-choice', 'hint' => 'MZMODAL answers are 0,1,2: one fixture per mode, plus the imbued double-choice if printed.', 'shape' => false],
    'reveal-look'     => ['re' => '/\b(?:Reveal|Look at)\b/i', 'tag' => 'look', 'hint' => '', 'shape' => false],
    'banish-all'      => ['re' => '/\bBanish (?:all|target)\b/i', 'tag' => 'banish', 'hint' => '', 'shape' => false],
    'destroy'         => ['re' => '/\bDestroy\b/i', 'tag' => 'destroy', 'hint' => '', 'shape' => false],
];
