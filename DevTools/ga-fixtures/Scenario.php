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

// A hand holds this many cards after the opening draw; the first card seeded into myHand is therefore myHand-7.
const GA_SCENARIO_STARTING_HAND = 7;

class GaScenario {
    private string $slug;
    private string $deck = '';
    private array $setup = [];
    private array $actions = [];
    private array $assertions = [];
    private array $testedCards = [];
    private array $meta = [];
    // player => [cardName => hand index] for cards seeded into myHand, and the next free hand index.
    private array $handIndex = [];
    private array $nextHandIndex = [];

    public function __construct(string $slug) {
        $this->slug = $slug;
    }

    // ---- card names ------------------------------------------------------------------------

    public static function id(string $cardName): string {
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
        $this->deck = "# Material\n1 Spirit of Fire\n1 Lorraine, Wandering Warrior\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n"
            . "# Main\n4 {$mainCardName}\n4 Dungeon Guide\n4 Fairy Whispers\n4 Fluffy Shopkeep";
        return $this;
    }

    public function deck(string $deckText): self {
        $this->deck = $deckText;
        return $this;
    }

    // ---- setup -------------------------------------------------------------------------------

    // Patch a player's starting champion. $lineage names lineage cards (unlocks an advanced element), $level
    // sets its level, $damage its damage, $classes an overridden class list ("MAGE"). Only what is passed is set.
    public function champion(array $lineage = [], ?int $level = null, ?int $damage = null, ?string $classes = null, int $player = 1): self {
        $props = [];
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

    // Escape hatch for setup the builder has no verb for (patch, globalEffect, deckTop, dqVariables, ...).
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
            'testedCards' => array_keys($this->testedCards),
            'deck' => $this->deck,
            'setup' => $this->setup,
            'actions' => $this->actions,
        ];
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

    public function register(): void {
        gaRegisterFixture($this->slug, $this->build());
    }
}

function gaScenario(string $slug): GaScenario {
    return new GaScenario($slug);
}

// Add a built definition to the generator's $fixtures. If an inline definition of the same slug still exists
// (mid-migration), the two must produce the same fixture -- setup, actions, deck and tested cards -- or the run stops.
function gaRegisterFixture(string $slug, array $def): void {
    global $fixtures;
    if (isset($fixtures[$slug])) {
        foreach (['testedCards', 'deck', 'setup', 'actions'] as $key) {
            if (($fixtures[$slug][$key] ?? null) !== ($def[$key] ?? null)) {
                fwrite(STDERR, "ga-fixtures: scenario '{$slug}' differs from the inline definition in '{$key}'\n");
                fwrite(STDERR, "  inline:   " . json_encode($fixtures[$slug][$key] ?? null) . "\n");
                fwrite(STDERR, "  scenario: " . json_encode($def[$key] ?? null) . "\n");
                exit(1);
            }
        }
        // Identical: keep the scenario's version (it may carry assertions).
    }
    $fixtures[$slug] = $def;
}
