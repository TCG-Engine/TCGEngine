<?php
/**
 * Scenario builder for GrandArchiveSim regression fixtures.
 *
 * Replaces hand-written setup/actions/assertions rows with a fluent description in card names.
 * build() expands to exactly the array shape DevTools/create-prd-ability-fixtures.php already
 * consumes ('testedCards', 'deck', 'setup', 'actions' and, when expectations were declared,
 * 'assertions'), so the generator, the runner and the fixture files do not change.
 *
 *   gaScenario('ignite-the-soul-damage')
 *     ->standardDeck('Ignite the Soul')
 *     ->seed('myHand', 'Ignite the Soul')
 *     ->play('Ignite the Soul')->pay(1)->passBoth()->target('theirField-0')
 *     ->expectDamage('p2Field-0', 2, 'The targeted opponent champion takes 2 damage')
 *     ->expectDqEmpty()
 *     ->register();
 *
 * Definitions live in DevTools/ga-fixtures/*.php (loaded by the generator). register() refuses to
 * silently shadow an inline definition of the same slug: while a fixture is being migrated both
 * may exist, and register() fails loudly unless they are identical.
 */

require_once __DIR__ . '/decks.php';

// A hand holds this many cards after the opening draw; the first card seeded into myHand is therefore myHand-7.
const GA_SCENARIO_STARTING_HAND = 7;

class GaScenario {
    // The lineage card the fixtures use to unlock each advanced element (readyFor()).
    private const ELEMENT_LINEAGE = [
        'WATER' => 'Spirit of Water', 'WIND' => 'Spirit of Wind', 'TERA' => 'Kongming, Fel Eidolon',
        'ASTRA' => 'Arisanna, Astral Zenith', 'UMBRA' => 'Tristan, Shadowdancer', 'ARCANE' => 'Lorraine, Arclight Saber',
        'LUXEM' => 'Zander, Blinding Steel', 'NEOS' => 'Tonoris, Creation\'s Will', 'EXIA' => 'Dante, Hemomancer',
        'CRUX' => 'Lorraine, Crux Knight',
    ];
    private string $slug;
    private string $deck = '';
    private array $setup = [];
    private array $actions = [];
    private array $assertions = [];
    private array $testedCards = [];
    private ?array $testedOverride = null;
    private array $meta = [];
    private ?string $baseFixture = null;
    // player => [cardName => hand index] for cards seeded into myHand, and the next free hand index.
    private array $handIndex = [];
    private array $nextHandIndex = [];

    public function __construct(string $slug) {
        $this->slug = $slug;
    }

    // ---- card names ------------------------------------------------------------------------

    // A card name, or '#<cardID>' for a card whose name is ambiguous in the card data (more than one printing).
    public static function id(string $cardName): string {
        if (str_starts_with($cardName, '#')) return substr($cardName, 1);
        static $loaded = false;
        if (!$loaded) {
            EngineLoadRootRuntime('GrandArchiveSim');
            $loaded = true;
        }
        $id = CardNameToID($cardName);
        if ($id === null) throw new RuntimeException("GaScenario: unknown card name '{$cardName}'");
        return $id;
    }

    // ---- deck --------------------------------------------------------------------------------

    // The shared 5-card material deck with four copies of $mainCardName and filler (the deck most spell fixtures use).
    public function standardDeck(string $mainCardName): self {
        return $this->deckTemplate('standard', $mainCardName);
    }

    // A named deck from decks.php with $mainCardName in its leading main-deck slot.
    public function deckTemplate(string $template, string $mainCardName): self {
        global $gaDeckTemplates;
        if (!isset($gaDeckTemplates[$template])) throw new RuntimeException("GaScenario[{$this->slug}]: unknown deck template '{$template}'");
        $this->deck = str_replace('{MAIN}', $mainCardName, $gaDeckTemplates[$template]);
        return $this;
    }

    public function deck(string $deckText): self {
        $this->deck = $deckText;
        return $this;
    }

    // ---- setup -------------------------------------------------------------------------------

    // Patch a player's starting champion. $lineage names lineage cards (unlocks an advanced element), $level
    // sets its level, $damage its damage, $classes an overridden class list ("MAGE"). Only what is passed is set.
    public function champion(array $lineage = [], ?int $level = null, ?int $damage = null, ?string $classes = null, int $player = 1, ?string $cardName = null): self {
        $props = [];
        if ($cardName !== null) $props['CardID'] = self::id($cardName);
        if (!empty($lineage)) $props['Subcards'] = array_map([self::class, 'id'], $lineage);
        $counters = [];
        if ($level !== null) $counters['level'] = $level;
        if ($classes !== null) $counters['_overrides'] = ['classes' => $classes];
        if (!empty($counters)) $props['Counters'] = $counters;
        if ($damage !== null) $props['Damage'] = $damage;
        $this->setup[] = ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => $props];
        return $this;
    }

    // Element access and class bonus derived from the card under test, as one champion patch: a champion whose lineage
    // unlocks the card's element (none needed for FIRE/NORM, the starting champion's), optionally with the card's own
    // class so its [Class Bonus] applies. $level / $damage are passed through (a second champion() patch would clobber
    // the Counters this one writes).
    public function readyFor(string $cardName, bool $classBonus = false, ?int $level = null, ?int $damage = null, int $player = 1): self {
        $id = self::id($cardName);
        $lineage = [];
        $elements = explode(',', CardElement($id));
        foreach ($elements as $element) {
            if (isset(self::ELEMENT_LINEAGE[$element])) { $lineage[] = self::ELEMENT_LINEAGE[$element]; break; }
        }
        // Exalted cards need another ADVANCED element enabled (water, wind and fire are basic).
        if (empty($lineage) && in_array('EXALTED', $elements, true)) $lineage[] = self::ELEMENT_LINEAGE['TERA'];
        $classes = $classBonus ? CardClasses($id) : null;
        if (empty($lineage) && $level === null && $damage === null && $classes === null) return $this;
        return $this->champion(lineage: $lineage, level: $level, damage: $damage, classes: $classes, player: $player);
    }

    // Shorthand for a champion patch that only unlocks an element through its lineage cards.
    public function lineage(string ...$lineageCardNames): self {
        return $this->champion(lineage: $lineageCardNames);
    }

    // Put a named card into a zone ('myHand', 'theirField', ...) via the same primitive the generator uses.
    // $properties are set on the seeded object (for example ['Damage' => 2] for an already-damaged ally).
    public function seed(string $zone, string $cardName, int $player = 1, array $properties = []): self {
        $row = ['player' => $player, 'zone' => $zone, 'cardID' => self::id($cardName)];
        if (!empty($properties)) $row['setProperties'] = $properties;
        $this->setup[] = $row;
        if ($zone === 'myHand') {
            $next = $this->nextHandIndex[$player] ?? GA_SCENARIO_STARTING_HAND;
            $this->handIndex[$player][$cardName] = $next;
            $this->nextHandIndex[$player] = $next + 1;
        }
        return $this;
    }

    // Set properties on an object already on the board (for example another champion or a field card).
    public function patch(string $mzId, array $properties, int $player = 1): self {
        $this->setup[] = ['player' => $player, 'patchMzId' => $mzId, 'setProperties' => $properties];
        return $this;
    }

    // Place the named cards on TOP of a player's deck (the first listed becomes the top card).
    public function deckTop(array $cardNames, int $player = 1): self {
        $this->setup[] = ['player' => $player, 'deckTop' => array_map([self::class, 'id'], $cardNames)];
        return $this;
    }

    // Move every live object out of a zone (default destination myBanish) to reach e.g. an empty-hand precondition.
    public function emptyZone(string $zone, int $player = 1, ?string $destZone = null): self {
        $row = ['player' => $player, 'emptyZone' => $zone];
        if ($destZone !== null) $row['destZone'] = $destZone;
        $this->setup[] = $row;
        return $this;
    }

    // Set a per-player global effect flag that is normally only reachable through a real game event.
    public function globalEffect(string $effect, int $player = 1): self {
        $this->setup[] = ['player' => $player, 'globalEffect' => $effect];
        return $this;
    }

    // Store decision-queue variables (combat state and the like) that an ability reads.
    public function dqVariables(array $variables, int $player = 1): self {
        $this->setup[] = ['player' => $player, 'dqVariables' => $variables];
        return $this;
    }

    // Escape hatch for setup the builder has no verb for.
    public function rawSetup(array $row): self {
        $this->setup[] = $row;
        return $this;
    }

    // ---- actions -----------------------------------------------------------------------------

    private function act(int $player, int $mode, string $card): self {
        $this->actions[] = ['playerID' => $player, 'mode' => $mode, 'buttonInput' => '', 'cardID' => $card, 'chkInput' => [], 'inputText' => ''];
        return $this;
    }

    // Play a card that was seeded into the player's hand (FSM click on its hand slot).
    public function play(string $cardName, int $player = 1): self {
        if (!isset($this->handIndex[$player][$cardName])) {
            throw new RuntimeException("GaScenario[{$this->slug}]: play('{$cardName}') but it was never seeded into player {$player}'s hand");
        }
        $this->testedCards[self::id($cardName)] = true;
        return $this->act($player, 10002, 'myHand-' . $this->handIndex[$player][$cardName] . '!FSM!');
    }

    // The whole "cast a seeded spell" sequence in one statement: play it, pay $pay reserve, pass the priority
    // window the engine opens before the effect resolves, then answer the target prompt.
    //   $window: ''     no window (the effect resolves straight after the cost)
    //            'both' caster passes, then opponent passes
    //            'me'   only the caster passes   'opp' only the opponent passes
    //   $target: mzID (or other literal answer) for the pending target/choice, or null when there is none.
    public function castSpell(string $cardName, int $pay = 0, string $window = '', ?string $target = null, int $player = 1): self {
        $other = $player === 1 ? 2 : 1;
        $this->play($cardName, $player);
        if ($pay > 0) $this->pay($pay, $player);
        switch ($window) {
            case '': break;
            case 'both': $this->pass($player)->pass($other); break;
            case 'me': $this->pass($player); break;
            case 'opp': $this->pass($other); break;
            default: throw new RuntimeException("GaScenario[{$this->slug}]: castSpell window '{$window}' must be '', 'both', 'me' or 'opp'");
        }
        if ($target !== null) $this->answer($player, $target);
        return $this;
    }

    // Click a hand slot directly (for a card that came from the opening hand rather than a seed).
    public function playSlot(int $slot, int $player = 1): self {
        return $this->act($player, 10002, 'myHand-' . $slot . '!FSM!');
    }

    // A harmless action (answering with the champion when nothing is pending) for fixtures that only inspect seeded
    // state, such as a static ability's computed POWER / LIFE: the generator needs at least one action to record.
    public function observe(int $player = 1): self {
        return $this->answer($player, 'myField-0');
    }

    // Player 1's first turn cannot attack (rule 1.h): both players end a turn, then player 1 declines the materialize offer
    // of their own turn 3 and is back in the main phase with the board unchanged.
    public function reachTurnThree(): self {
        return $this->endTurn(1)->endTurn(2)->pass(1);
    }

    // Declare an attack with the unit in $attackerMz (e.g. 'myField-1') against $targetMz (e.g. 'theirField-0').
    public function attack(string $attackerMz, string $targetMz, int $player = 1): self {
        return $this->act($player, 10002, $attackerMz . '!FSM!')->answer($player, $targetMz);
    }

    // Any other action row: $mode is the engine input mode (100 answer, 10001 turn buttons, 10002 FSM click, ...).
    public function action(int $player, int $mode, string $card): self {
        return $this->act($player, $mode, $card);
    }

    // Pay $n reserve costs (each answer banishes the first hand card into memory).
    public function pay(int $n, int $player = 1): self {
        for ($i = 0; $i < $n; ++$i) $this->act($player, 100, 'myHand-0');
        return $this;
    }

    public function pass(int $player): self {
        return $this->act($player, 100, 'PASS');
    }

    // Both players pass a response window (the active player first).
    public function passBoth(): self {
        return $this->pass(1)->pass(2);
    }

    // Answer the pending decision with an mzID or other literal answer.
    public function answer(int $player, string $answer): self {
        return $this->act($player, 100, $answer);
    }

    // Choose a target for player 1's pending choice.
    public function target(string $mzId, int $player = 1): self {
        return $this->answer($player, $mzId);
    }

    public function endTurn(int $player = 1): self {
        return $this->act($player, 10001, 'myHealth-0!CustomInput!Pass');
    }

    // A deliberately illegal answer: the engine must reject it (and the run continues with the legal actions
    // that follow). Counts as semantic evidence for $label, e.g. "Rejects a champion: the effect targets allies only".
    public function rejects(int $player, string $answer, string $label, int $mode = 100): self {
        $this->actions[] = ['playerID' => $player, 'mode' => $mode, 'buttonInput' => '', 'cardID' => $answer, 'chkInput' => [], 'inputText' => '', 'expectFailure' => true, 'semantic' => true, 'label' => $label];
        return $this;
    }

    // Escape hatch for an action row the builder has no verb for.
    public function rawAction(array $row): self {
        $this->actions[] = $row;
        return $this;
    }

    // ---- expectations ------------------------------------------------------------------------
    // $step defaults to the last action (the state after the scenario finished).

    private function expect(array $row, ?string $label, ?int $step): self {
        if ($label !== null) {
            $row['semantic'] = true;
            $row['label'] = $label;
        }
        $this->assertions[] = ['step' => $step] + $row;
        return $this;
    }

    public function expectProperty(string $mzId, string $property, string $value, ?string $label = null, ?int $step = null): self {
        return $this->expect(['type' => 'card_property_equals', 'mzId' => $mzId, 'property' => $property, 'value' => $value], $label, $step);
    }

    // A per-player global effect id (card id of an "until end of turn" effect) is registered $count times.
    public function expectGlobalEffect(int $player, string $effect, ?string $label = null, int $count = 1, ?int $step = null): self {
        return $this->expect(['type' => 'global_effect_present', 'player' => $player, 'effect' => $effect, 'value' => $count], $label, $step);
    }

    public function expectDamage(string $mzId, int $damage, ?string $label = null, ?int $step = null): self {
        return $this->expectProperty($mzId, 'Damage', strval($damage), $label, $step);
    }

    // Computed (modifier-aware) POWER / LIFE of a unit, e.g. after "+2 POWER until end of turn".
    public function expectPower(string $mzId, int $power, ?string $label = null, ?int $step = null): self {
        return $this->expect(['type' => 'computed_power_equals', 'mzId' => $mzId, 'value' => $power], $label, $step);
    }

    public function expectLife(string $mzId, int $life, ?string $label = null, ?int $step = null): self {
        return $this->expect(['type' => 'computed_life_equals', 'mzId' => $mzId, 'value' => $life], $label, $step);
    }

    // A card (by name, or '#<id>') is present in a zone such as 'myField' or 'theirGraveyard'.
    public function expectCardExists(string $zone, string $cardName, ?string $label = null, int $viewer = 1, ?int $step = null): self {
        return $this->expect(['type' => 'card_exists', 'zone' => $zone, 'cardID' => self::id($cardName), 'viewerPlayerID' => $viewer], $label, $step);
    }

    public function expectDqEmpty(?int $step = null): self {
        return $this->expect(['type' => 'decision_queue_empty', 'player' => 'all'], null, $step);
    }

    public function expectZoneCount(string $zone, int $count, int $viewer = 1, ?string $label = null, ?int $step = null): self {
        return $this->expect(['type' => 'zone_count', 'zone' => $zone, 'viewerPlayerID' => $viewer, 'value' => $count], $label, $step);
    }

    // Zone sizes after an ability's printed cost has been paid in the ability sandbox (see gaAbilitySandbox).
    // Labels are the generic ones the family has always used.
    public function expectCostFootprint(int $hand, int $memory, int $banish, int $graveyard, int $field): self {
        return $this
            ->expectZoneCount('myHand', $hand, 1, 'Hand after paying the printed (N) reserve cost (and any draws the ability makes)')
            ->expectZoneCount('myMemory', $memory, 1, 'Memory after the reserve payments / draws into memory')
            ->expectZoneCount('myBanish', $banish, 1, 'Banishment after the printed banish costs')
            ->expectZoneCount('myGraveyard', $graveyard, 1, 'Graveyard after the printed sacrifice / banish costs')
            ->expectZoneCount('myField', $field, 1, 'Field after the printed Banish / Sacrifice / Return CARDNAME cost');
    }

    // Override the tested cards (by '#<id>' or name) when they are not simply the cards the scenario plays.
    public function tested(string ...$cards): self {
        $this->testedOverride = array_map([self::class, 'id'], $cards);
        return $this;
    }

    // Derive this fixture from another one's recorded initial state (written to meta.json as baseFixture).
    public function base(string $baseFixtureSlug): self {
        $this->baseFixture = $baseFixtureSlug;
        return $this;
    }

    // ---- meta.json ---------------------------------------------------------------------------

    public function notes(string $notes): self {
        $this->meta['notes'] = $notes;
        return $this;
    }

    // Semantic coverage record: concise mechanic names and the printed rules clauses this scenario proves.
    // testedCards is filled in from the cards the scenario plays.
    public function covers(array $mechanics, string ...$rulesClauses): self {
        $this->meta['semanticCoverage'] = ['mechanics' => $mechanics, 'rulesClauses' => array_values($rulesClauses)];
        return $this;
    }

    // ---- output ------------------------------------------------------------------------------

    public function build(): array {
        if ($this->deck === '') throw new RuntimeException("GaScenario[{$this->slug}]: no deck");
        $def = [
            'testedCards' => $this->testedOverride ?? array_map('strval', array_keys($this->testedCards)),
            'deck' => $this->deck,
            'setup' => $this->setup,
            'actions' => $this->actions,
            // The generator stops replaying at the first action the engine refuses and records only the actions before it, so a wrong step silently
            // truncated a scenario (its later assertions then passed vacuously). A scenario step that fails is an error unless it was declared with rejects().
            'strictActions' => true,
        ];
        if ($this->baseFixture !== null) $def['baseFixture'] = $this->baseFixture;
        if (isset($this->meta['semanticCoverage']) && empty($def['testedCards'])) {
            throw new RuntimeException("GaScenario[{$this->slug}]: covers() but no tested cards -- add ->tested('Card Name') (a scenario that only seeds the card never plays it)");
        }
        if (!empty($this->meta)) {
            $meta = $this->meta;
            if (isset($meta['semanticCoverage'])) {
                $meta['semanticCoverage'] = ['testedCards' => $def['testedCards']] + $meta['semanticCoverage'];
            }
            $def['meta'] = $meta;
        }
        if (!empty($this->assertions)) {
            $last = count($this->actions);
            $def['assertions'] = array_map(function ($a) use ($last) {
                if ($a['step'] === null) $a['step'] = $last;
                return $a;
            }, $this->assertions);
        }
        return $def;
    }

    public function register(?string $slug = null): void {
        // DevTools/ga-scaffold.php skeletons start with 'todo-': finish the scenario and give it a slug that describes the behaviour.
        if (str_starts_with($slug ?? $this->slug, 'todo-')) {
            throw new RuntimeException("GaScenario[{$this->slug}]: unfinished scaffold -- replace the todo- slug with one that describes the behaviour");
        }
        gaRegisterFixture($slug ?? $this->slug, $this->build());
    }
}

function gaScenario(string $slug): GaScenario {
    return new GaScenario($slug);
}

// The "ability sandbox" behind the `…-ability-N-pays-its-printed-cost` family: a crowded, fully-stocked board so any
// printed cost (reserve, banish, sacrifice, return, discard) has something to pay with. The card under test is the
// first myField seed (myField-1, behind the patched champion at myField-0); `$cardPatch` patches it (Status, training
// counters...). `$champion` / `$classes` set the champion card and its class override. The board holds allies and a
// Powercell, an opposing ally, fire / floating-memory / water cards in the graveyard, a Bow weapon, a non-Human Slime
// ally, and reserve fuel in hand.
function gaAbilitySandbox(string $slug, string $cardName, string $classes = 'WARRIOR', array $cardPatch = [], string $champion = 'enxi6tshtu'): GaScenario {
    $s = gaScenario($slug)
        ->deck("# Material\n1 Spirit of Fire\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n")
        ->patch('myField-0', ['CardID' => $champion, 'Counters' => ['_overrides' => ['classes' => $classes], 'preparation' => 2, 'quest' => 9, 'enlighten' => 3, 'charge' => 3]])
        ->seed('myField', $cardName);
    if ($cardPatch) $s->patch('myField-1', $cardPatch);
    foreach ([['myField', 'Dungeon Guide'], ['myField', 'Fluffy Shopkeep'], ['myField', 'Powercell'], ['theirField', 'Dungeon Guide'],
              ['myGraveyard', 'Baby Red Slime'], ['myGraveyard', 'Fast Cure'], ['myGraveyard', 'Baby Blue Slime'],
              ['myMemory', 'Dungeon Guide'], ['myMemory', 'Fluffy Shopkeep'], ['myField', 'Sojourner\'s Hunt'], ['myField', 'Baby Blue Slime']] as [$zone, $name]) {
        $s->seed($zone, $name);
    }
    for ($i = 0; $i < 6; ++$i) $s->seed('myHand', 'Dungeon Guide');
    return $s;
}

// The `…-spell-cannot-target-it` family: player 2 controls one or more shrouded $holders plus a legal decoy; player 1
// casts a targeting spell, every holder is refused as an answer (the engine must reject it), and the decoy is then chosen
// and resolves. $spell picks the kit: 'Liturgy of Corruption' (buff counter on an ally, decoy Dungeon Guide), 'Fracturize'
// (item/weapon becomes a phantasia, decoy Mirrordepth's Blade) or 'Disenchant' (decoy Ominous Shadow; the refusals are
// the only evidence). $holderCounters is each holder's Counters JSON (default none); $word is the keyword in the labels
// ('Spellshroud' or 'Omnishroud'); $oppChampion replaces player 2's champion (class-dependent shroud).
function gaSpellshroudCase(string $slug, array $holders, string $spell, array $holderCounters = [], string $word = 'Spellshroud', ?string $oppChampion = null): GaScenario {
    global $mordredDeck;
    $kits = [
        'Liturgy of Corruption' => ['decoy' => 'Dungeon Guide', 'pre' => '[]', 'post' => '{"buff":1}', 'grave' => 'BZMKfFZ22T',
            'postLabel' => 'The only legal target, Dungeon Guide, received the buff counter'],
        'Fracturize' => ['decoy' => 'Mirrordepth\'s Blade', 'pre' => '{"durability":2}', 'grave' => 'cpvn96659y',
            'post' => '{"durability":2,"_overrides":{"type":"PHANTASIA","subtypes":"CLERIC,FRACTAL","classes":"CLERIC","NO_ABILITIES":true,"granted_keywords":["Reservable"],"FRACTURIZED":true}}',
            'postLabel' => 'The only legal target, Mirrordepth\'s Blade, became a Cleric Fractal phantasia (Fracturize override)'],
        'Disenchant' => ['decoy' => 'Ominous Shadow'],
    ];
    if (!isset($kits[$spell])) throw new RuntimeException("gaSpellshroudCase[{$slug}]: unknown spell kit '{$spell}'");
    $kit = $kits[$spell];
    $n = count($holders);
    $s = gaScenario($slug)->deck($mordredDeck);
    if ($oppChampion !== null) $s->champion(cardName: $oppChampion, player: 2);
    foreach ($holders as $h) $s->seed('myField', $h, player: 2);
    $s->seed('myField', $kit['decoy'], player: 2)
      ->seed('myHand', $spell)
      ->castSpell($spell, pay: 2);
    $names = [];
    foreach ($holders as $i => $h) {
        $id = GaScenario::id($h);
        $names[$i] = CardName($id) . " ({$id})";
        $s->rejects(1, 'theirField-' . ($i + 1), "{$names[$i]} has spellshroud: not a legal target for the opposing {$spell}");
    }
    $step = $n + 3;
    $s->target('theirField-' . ($n + 1))->tested(...$holders);
    if (!isset($kit['pre'])) return $s;
    $hc = fn($i) => $holderCounters[$i] ?? '[]';
    foreach ($holders as $i => $h) {
        $s->expectProperty('p2Field-' . ($i + 1), 'Counters', $hc($i), "The refused answers left {$names[$i]} untouched: {$word} stops the opposing {$spell} from targeting it", step: $step);
    }
    $s->expectProperty('p2Field-' . ($n + 1), 'Counters', $kit['pre'], "The refused answers did not apply {$spell} to the {$kit['decoy']} either (prompt still pending)", step: $step);
    foreach ($holders as $i => $h) {
        $s->expectProperty('p2Field-' . ($i + 1), 'Counters', $hc($i), "{$names[$i]} is unaffected by the resolved {$spell}");
    }
    return $s->expectProperty('p2Field-' . ($n + 1), 'Counters', $kit['post'], $kit['postLabel'])
        ->expectProperty('p1Graveyard-0', 'CardID', $kit['grave'], "{$spell} resolved into player 1's graveyard")
        ->expectDqEmpty();
}

// Add a built definition to the generator's $fixtures. If an inline definition of the same slug still exists
// (mid-migration), the two must produce the same fixture -- setup, actions, deck and tested cards -- or the run stops.
// $card sits in the graveyard with no memory seeded; levelling up banishes it as the memory payment. $classes overrides the champion class.
// Layer 2 shape helper (moved here from water-allies2.php so every element file can use it): $card sits in the graveyard with no
// memory seeded; levelling up banishes it as the memory payment. $classes overrides the champion class; $pays=false is the no-bonus twin.
function gaFloatingPays(string $slug, string $card, string $clause, ?string $classes = null, bool $pays = true, string $label = ''): void {
    $s = gaScenario($slug)->deckTemplate('lorraine-windslice', 'Dungeon Guide');
    if ($classes !== null) $s->champion(classes: $classes);
    $s->seed('myGraveyard', $card)->endTurn()->endTurn(2)->target('myMaterial-0');
    if ($pays) {
        $s->target('myGraveyard-0')
          ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion levelled up, so the 1-memory cost was paid')
          ->expectZoneCount('myGraveyard', 0, 1, $label !== '' ? $label : "$card was banished from the graveyard as the floating memory payment");
    } else {
        $s->expectZoneCount('myGraveyard', 1, 1, $label !== '' ? $label : "$card stays in the graveyard: its Floating Memory needs the class bonus")
          ->expectZoneCount('myMaterial', 4, 1, 'No memory could be paid, so no champion was materialized');
    }
    $s->tested($card)->covers(['floating-memory', 'memory-cost', 'banish'] + ($classes !== null || !$pays ? [3 => 'class-bonus'] : []), $clause)->register();
}

function gaRegisterFixture(string $slug, array $def): void {
    global $fixtures;
    if (isset($fixtures[$slug])) {
        foreach (['testedCards', 'deck', 'setup', 'actions', 'baseFixture'] as $key) {
            $inlineValue = $fixtures[$slug][$key] ?? ($key === 'setup' ? [] : null); // a definition without 'setup' means no setup rows
            if (gaCanonical($inlineValue) !== gaCanonical($def[$key] ?? null)) {
                fwrite(STDERR, "ga-fixtures: scenario '{$slug}' differs from the inline definition in '{$key}'\n");
                fwrite(STDERR, "  inline:   " . json_encode($fixtures[$slug][$key] ?? null) . "\n");
                fwrite(STDERR, "  scenario: " . json_encode($def[$key] ?? null) . "\n");
                // GA_SCENARIO_COLLECT=1 (migration tooling) reports every mismatch and keeps the inline definition.
                if (getenv('GA_SCENARIO_COLLECT')) return;
                exit(1);
            }
        }
        // Identical: keep the scenario's version (it may carry assertions).
    }
    $fixtures[$slug] = $def;
}

// Key order is not significant in a setup/action row: sort associative arrays so two equal rows compare equal.
function gaCanonical($value) {
    if (!is_array($value)) return $value;
    $isList = array_is_list($value);
    $out = array_map('gaCanonical', $value);
    if (!$isList) ksort($out);
    return $out;
}
