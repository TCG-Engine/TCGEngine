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

    public function expectDamage(string $mzId, int $damage, ?string $label = null, ?int $step = null): self {
        return $this->expectProperty($mzId, 'Damage', strval($damage), $label, $step);
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
        ];
        if ($this->baseFixture !== null) $def['baseFixture'] = $this->baseFixture;
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

// Add a built definition to the generator's $fixtures. If an inline definition of the same slug still exists
// (mid-migration), the two must produce the same fixture -- setup, actions, deck and tested cards -- or the run stops.
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
