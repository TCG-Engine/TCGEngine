<?php

/**
 * Deterministic Milestone 1 fixtures.
 *
 * These lists are deliberately small engine fixtures, not authoritative starter
 * deck lists. Structured values are sufficient to exercise universal rules as
 * those services are added. Production card data will replace these overrides.
 */

function HellbreakFixtureCards(): array
{
    static $cards = [
        'DOT_001' => [
            'name' => 'Dracula, Transylvanian Terror', 'type' => 'Monster',
            'cost' => 0, 'combat' => 0, 'health' => 16,
            'loyalty' => [], 'resources' => ['blood' => 0, 'malice' => 1, 'draw' => 0, 'aspects' => ['Cursed' => 1]],
            'scheme' => [], 'traits' => ['Undead', 'Vampire'], 'unique' => true, 'side' => 'LURKING',
        ],
        'DOT_006' => [
            'name' => 'Jaws, Scourge of Amity Island', 'type' => 'Monster',
            'cost' => 0, 'combat' => 4, 'health' => 16,
            'loyalty' => [], 'resources' => ['blood' => 0, 'malice' => 0, 'draw' => 0, 'aspects' => ['Feral' => 1]],
            'scheme' => [], 'traits' => ['Creature', 'Shark'], 'unique' => true, 'side' => 'LURKING',
        ],
        'DOT_015' => [
            'name' => 'Carfax Abbey', 'type' => 'Location', 'cost' => 0,
            'combat' => 0, 'health' => 0, 'loyalty' => [],
            'resources' => ['blood' => 1, 'malice' => 0, 'draw' => 1, 'aspects' => []],
            'scheme' => [], 'traits' => ['Building'], 'unique' => false, 'threshold' => 4,
        ],
        'DOT_016' => [
            'name' => 'Whitby Abbey', 'type' => 'Location', 'cost' => 0,
            'combat' => 0, 'health' => 0, 'loyalty' => [],
            'resources' => ['blood' => 0, 'malice' => 1, 'draw' => 0, 'aspects' => []],
            'scheme' => [], 'traits' => ['Ruins'], 'unique' => false, 'threshold' => 3,
        ],
        'DOT_013' => [
            'name' => 'Amity Harbor', 'type' => 'Location', 'cost' => 0,
            'combat' => 0, 'health' => 0, 'loyalty' => [],
            'resources' => ['blood' => 0, 'malice' => 1, 'draw' => 0, 'aspects' => []],
            'scheme' => [], 'traits' => ['Water'], 'unique' => false, 'threshold' => 3,
        ],
        'DOT_020' => [
            'name' => 'North Beach', 'type' => 'Location', 'cost' => 0,
            'combat' => 0, 'health' => 0, 'loyalty' => [],
            'resources' => ['blood' => 1, 'malice' => 0, 'draw' => 0, 'aspects' => []],
            'scheme' => [], 'traits' => ['Water'], 'unique' => false, 'threshold' => 3,
        ],
        'DOT_044' => [
            'name' => "Count Alucard, Dracula's Son", 'type' => 'Minion',
            'cost' => 9, 'combat' => 4, 'health' => 7, 'loyalty' => ['Cursed' => 2],
            'resources' => ['blood' => 0, 'malice' => 1, 'draw' => 0, 'aspects' => ['Cursed' => 1]],
            'scheme' => [['type' => 'HAUNT', 'value' => 2]],
            'traits' => ['Undead', 'Vampire'], 'unique' => true,
        ],
        'DOT_032' => [
            'name' => "Mina Seward, The Count's Obsession", 'type' => 'Minion',
            'cost' => 3, 'combat' => 1, 'health' => 3, 'loyalty' => ['Cursed' => 1],
            'resources' => ['blood' => 0, 'malice' => 0, 'draw' => 0, 'aspects' => ['Cursed' => 1]],
            'scheme' => [], 'traits' => ['Human'], 'unique' => true,
        ],
        'DOT_049' => [
            'name' => "Vampire's Coffin", 'type' => 'Asset',
            'cost' => 1, 'combat' => 0, 'health' => 0, 'loyalty' => ['Cursed' => 1],
            'resources' => ['blood' => 1, 'malice' => 0, 'draw' => 0, 'aspects' => ['Cursed' => 1]],
            'scheme' => [], 'traits' => ['Item'], 'unique' => false,
        ],
        'DOT_052' => [
            'name' => 'Drain Life', 'type' => 'Event',
            'cost' => 1, 'combat' => 0, 'health' => 0, 'loyalty' => ['Cursed' => 1],
            'resources' => ['blood' => 0, 'malice' => 1, 'draw' => 0, 'aspects' => ['Cursed' => 1]],
            'scheme' => [], 'traits' => ['Spell'], 'unique' => false, 'playSide' => 'LURKING',
        ],
        'DOT_122' => [
            'name' => 'Threat From Below', 'type' => 'Minion',
            'cost' => 5, 'combat' => 4, 'health' => 4, 'loyalty' => ['Feral' => 2],
            'resources' => ['blood' => 0, 'malice' => 0, 'draw' => 0, 'aspects' => ['Feral' => 1]],
            'scheme' => [['type' => 'PROWL', 'value' => 2]],
            'traits' => ['Creature', 'Shark'], 'unique' => false,
        ],
        'DOT_127' => [
            'name' => 'Giant Octopus', 'type' => 'Minion',
            'cost' => 8, 'combat' => 5, 'health' => 8, 'loyalty' => ['Feral' => 2],
            'resources' => ['blood' => 0, 'malice' => 0, 'draw' => 0, 'aspects' => ['Feral' => 1]],
            'scheme' => [['type' => 'FORESEE', 'value' => 2]],
            'traits' => ['Creature'], 'unique' => false,
        ],
        'DOT_092' => [
            'name' => 'Orca, Timeworn Trawler', 'type' => 'Asset',
            'cost' => 1, 'combat' => 0, 'health' => 0, 'loyalty' => ['Deranged' => 1],
            'resources' => ['blood' => 0, 'malice' => 1, 'draw' => 0, 'aspects' => ['Deranged' => 1]],
            'scheme' => [], 'traits' => ['Vehicle'], 'unique' => true,
            'healthAbility' => true, 'healthMaliceCost' => 1,
        ],
        'DOT_106' => [
            'name' => "Man O' War", 'type' => 'Minion',
            'cost' => 2, 'combat' => 2, 'health' => 1, 'loyalty' => ['Feral' => 1],
            'resources' => ['blood' => 1, 'malice' => 0, 'draw' => 0, 'aspects' => ['Feral' => 1]],
            'scheme' => [], 'traits' => ['Creature', 'Fish'], 'unique' => false,
        ],
    ];
    return $cards;
}

function HellbreakReviewedCard(string $cardID): ?array
{
    static $cards = null;
    if($cards === null) {
        $path = __DIR__ . '/../CardData/ReviewedCardFaces.json';
        $payload = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
        $cards = is_array($payload) && is_array($payload['cards'] ?? null) ? $payload['cards'] : [];
    }
    $reviewed = $cards[$cardID] ?? null;
    if(!is_array($reviewed)) return null;
    if(isset($reviewed['faces']['lurking']) && is_array($reviewed['faces']['lurking'])) {
        $reviewed = array_replace($reviewed, $reviewed['faces']['lurking']);
    }
    return $reviewed;
}

function HellbreakFixtureCard(string $cardID): ?array
{
    $cards = HellbreakFixtureCards();
    $fixture = $cards[$cardID] ?? [];
    $reviewed = HellbreakReviewedCard($cardID);
    // Existing deterministic fixture overrides remain intentionally stable for engine tests;
    // reviewed values fill every card/field that the fixture does not override.
    if($reviewed !== null) return array_replace($reviewed, $fixture);
    return $fixture ?: null;
}

/**
 * Forty-card GAMA retailer demo lists supplied from the physical deck contents.
 * Together with one monster and two locations, each side contains 43 cards.
 *
 * Source-name normalization against the current imported card identities:
 * - "Bloodsucking Vampire" -> Bloodsucking Bat (DOT_025)
 * - "Mina Harker" -> Mina Seward, The Count's Obsession (DOT_032)
 * - "Shark!" -> Shark in the Pond (DOT_136)
 */
function HellbreakGamaDemoDeck(string $archetype): array
{
    $archetype = strtoupper(trim($archetype));
    if($archetype === 'JAWS') {
        $counts = [
            'DOT_105' => 3, // Barracuda
            'DOT_136' => 1, // Shark in the Pond (source: "Shark!")
            'DOT_092' => 2, // Orca, Timeworn Trawler
            'DOT_110' => 3, // Shark Spotter
            'DOT_106' => 3, // Man O' War
            'DOT_109' => 3, // Rogue Shark
            'DOT_098' => 3, // Narrow Escape
            'DOT_068' => 2, // Deputy Hendricks
            'DOT_076' => 3, // Veteran Harpooner
            'DOT_112' => 2, // Larry Vaughn
            'DOT_119' => 3, // Ravenous Predator
            'DOT_080' => 3, // Roughtail Stingray
            'DOT_104' => 2, // A Panic on Our Hands
            'DOT_122' => 3, // Threat From Below
            'DOT_127' => 3, // Giant Octopus
            'DOT_128' => 1, // Killer Whale
        ];
        return [
            'name' => 'GAMA Demo - Jaws',
            'source' => 'GAMA retailer demo deck',
            'monster' => 'DOT_006',
            'locations' => ['DOT_020', 'DOT_013'],
            'deck' => HellbreakExpandCardCounts($counts),
            'knownMissingImages' => [],
        ];
    }

    $counts = [
        'DOT_052' => 3, // Drain Life
        'DOT_049' => 2, // Vampire's Coffin
        'DOT_025' => 2, // Bloodsucking Bat (source: "Bloodsucking Vampire")
        'DOT_151' => 6, // Swarm of Rats
        'DOT_053' => 2, // Coven Feast
        'DOT_028' => 3, // Transylvanian Wolf
        'DOT_027' => 1, // Renfield, Deranged Solicitor
        'DOT_180' => 2, // Ancient Wisdom
        'DOT_152' => 2, // Aleera, Alluring Bride
        'DOT_032' => 1, // Mina Seward (source: "Mina Harker")
        'DOT_034' => 2, // Carpathian Wildcat
        'DOT_161' => 2, // Marishka, Cunning Bride
        'DOT_159' => 3, // Carriage Driver
        'DOT_165' => 2, // Verona, Bloodthirsty Bride
        'DOT_039' => 3, // Ferocious Wolfpack
        'DOT_040' => 1, // Lucy Weston, Back from the Dead
        'DOT_042' => 2, // Countess Zaleska
        'DOT_044' => 1, // Count Alucard
    ];
    return [
        'name' => 'GAMA Demo - Dracula',
        'source' => 'GAMA retailer demo deck',
        'monster' => 'DOT_001',
        'locations' => ['DOT_016', 'DOT_015'],
        'deck' => HellbreakExpandCardCounts($counts),
        // The checklist identifies the card, but neither its standard nor
        // borderless row currently has a source image in the imported assets.
        'knownMissingImages' => ['DOT_161'],
    ];
}

function HellbreakExpandCardCounts(array $counts): array
{
    $deck = [];
    foreach($counts as $cardID => $quantity) {
        for($copy = 0; $copy < max(0, intval($quantity)); ++$copy) $deck[] = strval($cardID);
    }
    return $deck;
}

function HellbreakFixtureDeck(string $archetype): array
{
    $archetype = strtoupper(trim($archetype));
    if ($archetype === 'JAWS') {
        return [
            'name' => 'Jaws engine fixture',
            'monster' => 'DOT_006',
            'locations' => ['DOT_020', 'DOT_013'],
            'deck' => [
                'DOT_092', 'DOT_105', 'DOT_106', 'DOT_107', 'DOT_108', 'DOT_109',
                'DOT_110', 'DOT_111', 'DOT_112', 'DOT_113', 'DOT_117', 'DOT_118',
                'DOT_119', 'DOT_121', 'DOT_122', 'DOT_124', 'DOT_125', 'DOT_127',
                'DOT_130', 'DOT_132', 'DOT_136', 'DOT_137', 'DOT_140', 'DOT_144',
            ],
        ];
    }

    return [
        'name' => 'Dracula engine fixture',
        'monster' => 'DOT_001',
        'locations' => ['DOT_015', 'DOT_016'],
        'deck' => [
            'DOT_025', 'DOT_028', 'DOT_030', 'DOT_032', 'DOT_034', 'DOT_044',
            'DOT_049', 'DOT_052', 'DOT_060', 'DOT_068', 'DOT_053', 'DOT_055',
            'DOT_025', 'DOT_028', 'DOT_030', 'DOT_032', 'DOT_034', 'DOT_044',
            'DOT_049', 'DOT_052', 'DOT_060', 'DOT_068', 'DOT_053', 'DOT_055',
        ],
    ];
}

function HellbreakLoadFixturePlayer(int $player, string $archetype): array
{
    $fixture = HellbreakFixtureDeck($archetype);
    $monsterData = HellbreakFixtureCard($fixture['monster']) ?? [];
    AddMonster(
        $player,
        $fixture['monster'],
        2,
        (string)($monsterData['side'] ?? 'LURKING'),
        $player,
        $player,
        [],
        []
    );

    foreach ($fixture['locations'] as $cardID) AddLocationDeck($player, $cardID);
    foreach ($fixture['deck'] as $cardID) AddDeck($player, $cardID);

    $health = &HealthValue($player);
    $health = 0;
    $topHealth = &TopHealthRemainingValue($player);
    $topHealth = 0;
    $blood = &BloodValue($player);
    $blood = 0;
    $malice = &MaliceValue($player);
    $malice = 0;
    $locationCommitment = &LocationCommitmentValue($player);
    $locationCommitment = '-';
    $bidCommitment = &BidCommitmentValue($player);
    $bidCommitment = '-';
    $mulliganCommitted = &MulliganCommittedValue($player);
    $mulliganCommitted = false;

    return $fixture;
}

function HellbreakLoadGamaDemoPlayer(int $player, string $archetype): array
{
    $deck = HellbreakGamaDemoDeck($archetype);
    $monsterData = HellbreakFixtureCard($deck['monster']) ?? [];
    AddMonster($player, $deck['monster'], 2, (string)($monsterData['side'] ?? 'LURKING'), $player, $player, [], []);
    foreach($deck['locations'] as $cardID) AddLocationDeck($player, $cardID);
    foreach($deck['deck'] as $cardID) AddDeck($player, $cardID);

    $health = &HealthValue($player); $health = 0;
    $topHealth = &TopHealthRemainingValue($player); $topHealth = 0;
    $blood = &BloodValue($player); $blood = 0;
    $malice = &MaliceValue($player); $malice = 0;
    $locationCommitment = &LocationCommitmentValue($player); $locationCommitment = '-';
    $bidCommitment = &BidCommitmentValue($player); $bidCommitment = '-';
    $mulliganCommitted = &MulliganCommittedValue($player); $mulliganCommitted = false;
    return $deck;
}

function HellbreakInitializeFixtureGame(): array
{
    $players = [
        1 => HellbreakLoadFixturePlayer(1, 'DRACULA'),
        2 => HellbreakLoadFixturePlayer(2, 'JAWS'),
    ];

    SetTurnNumber(0);
    SetFirstPlayer(1);
    SetInitiativePlayer(1);
    SetTurnPlayer(1);
    SetCurrentPhase('SETUP_LOCATION');
    SetPhaseParameters('-');
    SetPreviousActionPassLike(false);
    SetSlumberPlayer(0);
    SetSlumberUsed(false);
    SetActionSequence(0);
    SetWinner(0);
    SetFixtureMode(true);

    return $players;
}

?>
