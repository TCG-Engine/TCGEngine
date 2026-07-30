<?php
// Mock (preview) card definitions — TRACKED SOURCE, merged into the generated card
// dictionaries at generation time by SWUSim/DevTools/MockCardMerge.php.
//
// Keyed by the ordinary SET_NNN CardID. Card logic, tests and decks use that ID; only the
// IMAGE files carry a "mock_" prefix. Official data always wins: once a card exists in the
// real API response its mock entry is ignored and reported as superseded.
//
// Managed by zzPreviewTool.php?rootName=SWUSim (dev only) — hand-editing is fine too.
// This file must stay inert at runtime: it returns data and registers nothing.
//
// SCAFFOLD-IGNORE: pure DATA, not a card implementation. Tools that infer "is this card implemented?"
// by grepping quoted CardIDs under SWUSim/Custom/ must skip this file, or every card listed here looks
// implemented and gets no stub / is dropped from gap reports. This marker lives in the WRITER's header
// constant on purpose — patching it into the file by hand is lost on the next mock write.
//
// Fields (all optional except title/type/set):
//   title, subtitle, type, arena, cost, power, hp, upgradePower, upgradeHp,
//   aspect[], trait[], text, epicAction, deployText, unique, rarity, set,
//   imageUrl, imageUrlBack,
//   leaderUnitTitle, leaderUnitSubtitle, leaderUnitTrait[], leaderUnitArena, leaderUnitType
return array (
  'HMW_004' => 
  array (
    'title' => 'Grand Moff Tarkin',
    'subtitle' => 'Tyrant of the Outer Rim',
    'type' => 'Leader',
    'arena' => 'Space',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 9,
    'power' => 2,
    'hp' => 12,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Official',
    ),
    'text' => 'Ignore the aspect penalties on upgrades with Fortify you play.',
    'epicAction' => 'Epic Action: If you control 9 or more resources, deploy this leader.',
    'deployText' => 'Ignore the aspect penalties on upgrades with Fortify you play.
When the regroup phase starts: You may defeat a base with 10 or less remaining HP.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/004.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/004-back.png',
    'leaderUnitTitle' => 'The Death Star',
    'leaderUnitSubtitle' => 'Icon of Tyranny',
    'leaderUnitTrait' => 
    array (
      0 => 'Imperial',
      1 => 'Vehicle',
      2 => 'Capital Ship',
    ),
    'leaderUnitArena' => 'Space',
    'leaderUnitType' => 'Unit',
  ),
  'HMW_009' => 
  array (
    'title' => 'Chewbacca',
    'subtitle' => 'Relentless Rebel',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 3,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Wookiee',
    ),
    'text' => 'Action [2 resources, Exhaust]: Attack with a unit, even if it\'s exhausted. It can\'t attack bases for this attack.',
    'epicAction' => 'Epic Action: If you control 5 or more resources, deploy this leader.',
    'deployText' => 'Action: Attack with a unit, even if it\'s exhausted. It can\'t attack bases for this attack. Use this ability only once each round.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/009.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/009-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_019' => 
  array (
    'title' => 'Dune Sea',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Tatooine',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/019.png',
    'imageUrlBack' => '',
  ),
  'HMW_059' => 
  array (
    'title' => 'Clone X Assassin',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 1,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Clone',
      2 => 'Trooper',
    ),
    'text' => 'When Defeated: You may give a Weakness token to a unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/059.png',
    'imageUrlBack' => '',
  ),
  'HMW_060' => 
  array (
    'title' => 'Vice Admiral Rampart',
    'subtitle' => 'A New Era of Safety',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 1,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Official',
    ),
    'text' => 'If an upgrade on your base would be defeated, you may defeat this unit instead.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/060.png',
    'imageUrlBack' => '',
  ),
  'HMW_061' => 
  array (
    'title' => 'Director Krennic',
    'subtitle' => 'The Work Has Stalled',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 3,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Official',
    ),
    'text' => 'On Attack: If your base is upgraded, draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/061.png',
    'imageUrlBack' => '',
  ),
  'HMW_081' => 
  array (
    'title' => 'Alliance Shield Generator',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify (Attach this to your base, not a unit.)
If attached base would be dealt 5 or more damage, prevent that damage. If you do, defeat this upgrade and draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/081.png',
    'imageUrlBack' => '',
  ),
  'HMW_085' => 
  array (
    'title' => 'Remote Scout',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 1,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Trooper',
    ),
    'text' => 'When Played: Search the top 8 cards of your deck for an upgrade, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/085.png',
    'imageUrlBack' => '',
  ),
  'HMW_095' => 
  array (
    'title' => 'Carbonite Chamber',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'cost' => 1,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify (Attach this to your base, not a unit.)
Action [defeat this upgrade]: Choose a non-Vehicle unit. It doesn\'t ready during the next regroup phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/095.png',
    'imageUrlBack' => '',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_121' => 
  array (
    'title' => 'Hijacked AT-ST',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 7,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Vehicle',
      2 => 'Walker',
    ),
    'text' => 'Overwhelm (When attacking an enemy unit, deal excess damage to the opponent\'s base.)
When Played: This unit doesn\'t ready during the next regroup phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/121.png',
    'imageUrlBack' => '',
  ),
  'HMW_127' => 
  array (
    'title' => 'Chewbacca\'s Bowcaster',
    'subtitle' => 'Handcrafted Tradition',
    'type' => 'Upgrade',
    'arena' => '',
    'cost' => 3,
    'upgradePower' => 3,
    'upgradeHp' => 1,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Item',
      1 => 'Weapon',
    ),
    'text' => 'Attach of a non-Vehicle unit.
When Played: if attached unit is Chewbacca, resource the top card of your deck. (It enters play exhausted.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'rarity' => 'Special',
    'set' => 'HMW',
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/127.png',
    'imageUrlBack' => '',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_142' => 
  array (
    'title' => 'Wookie Rangers',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'cost' => 5,
    'power' => 5,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Wookiee',
    ),
    'text' => 'While you control another Wookiee unit or a Kashyyyk base, this unit gains Sentinel. (Enemy units in this arena must attack a Sentinel when they attack you.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'rarity' => 'Common',
    'set' => 'HMW',
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/142.png',
    'imageUrlBack' => '',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_158' => 
  array (
    'title' => 'Ezra Bridger',
    'subtitle' => 'What Are You Afraid Of?',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 5,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Rebel',
      2 => 'Spectre',
    ),
    'text' => 'When you take the initiative: You may deal 3 damage to your base. If you do, create a Beast token.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/158.png',
    'imageUrlBack' => '',
  ),
  'HMW_171' => 
  array (
    'title' => 'Trap Field',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify (Attach to this base, not a unit.)
When a non-leader ground unit enters play (including token units): You may defeat this upgrade. If you do, deal 3 damage to that unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/171.png',
    'imageUrlBack' => '',
  ),
  'HMW_177' => 
  array (
    'title' => 'Adamant Ewoks',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 3,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'When Played: If you control another Ewok unit or an Endor base, you may deal 1 damage to a base and 1 damage to an enemy unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/177.png',
    'imageUrlBack' => '',
  ),
  'HMW_206' => 
  array (
    'title' => 'The Tarkin Doctrine',
    'subtitle' => 'Protect and Punish',
    'type' => 'Upgrade',
    'arena' => '',
    'cost' => 1,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Law',
    ),
    'text' => 'Fortify (Attach this to your base, not a unit.)
Attached base gains: "When you play a Fortification upgrade: Exhaust an enemy unit."
When Played: If you control Grand Moff Tarkin, give an enemy unit -3/-0 for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'rarity' => 'Special',
    'set' => 'HMW',
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/206.png',
    'imageUrlBack' => '',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_234' => 
  array (
    'title' => 'Ritual Dragon',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 8,
    'power' => 6,
    'hp' => 9,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Creature',
    ),
    'text' => 'Saboteur
While you control a Tatooine base, friendly units enter play ready (including this one).',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/234.png',
    'imageUrlBack' => '',
  ),
  'HMW_255' => 
  array (
    'title' => 'C-3PO',
    'subtitle' => 'Captivaling Storyteller',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 2,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Droid',
    ),
    'text' => 'When Played: You may give an Ewok unit +2/+2 for this phase. You may give a Rebel unit +2/+2 for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/255.png',
    'imageUrlBack' => '',
  ),
  'HMW_257' => 
  array (
    'title' => 'Ewok Archers',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 2,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'While you control another unit that costs 3 or less, this unit gains Ambush. (When you play this unit, it may attack an enemy unit.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/257.png',
    'imageUrlBack' => '',
  ),
  'HMW_T02' => 
  array (
    'title' => 'Weakness',
    'subtitle' => '',
    'type' => 'Token Upgrade',
    'arena' => '',
    'cost' => 0,
    'upgradePower' => -1,
    'upgradeHp' => -1,
    'aspect' => 
    array (
    ),
    'trait' => 
    array (
      0 => 'Condition',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'rarity' => '',
    'set' => 'HMW',
    'imageUrl' => '',
    'imageUrlBack' => '',
  ),
  'HMW_T03' => 
  array (
    'title' => 'Beast',
    'subtitle' => '',
    'type' => 'Token Unit',
    'arena' => 'Ground',
    'cost' => 0,
    'power' => 3,
    'hp' => 3,
    'aspect' => 
    array (
    ),
    'trait' => 
    array (
      0 => 'Creature',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'rarity' => '',
    'set' => 'HMW',
    'imageUrl' => '',
    'imageUrlBack' => '',
  ),
);
