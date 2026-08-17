<?php
// Mock (preview) card definitions — TRACKED SOURCE, merged into the generated card
// dictionaries at generation time by AppCore/SWU/MockCardMerge.php.
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
  'HMW_003' => 
  array (
    'title' => 'Doctor Hemlock',
    'subtitle' => 'Emotion Has No Place Here',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 3,
    'hp' => 6,
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
    'text' => 'Action [1 resource, Exhaust]: Give a Weakness token to a unit without a Weakness token on it.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'On Attack: You may give a Weakness token to a unit.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/003.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/003-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
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
  'HMW_007' => 
  array (
    'title' => 'Darth Vader',
    'subtitle' => 'Might of the Empire',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 5,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Imperial',
      2 => 'Sith',
    ),
    'text' => 'Friendly units that cost 3 or more gain Raid 1.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'Raid 1

Other friendly units that cost 3 or more gain Raid 1.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/007.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/007-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
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
  'HMW_014' => 
  array (
    'title' => 'Wicket',
    'subtitle' => 'Few Greater Battles to Fight',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 2,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'When a friendly unit attacks a unit that costs more than it: You may exhaust this leader. If you do, draw a card.',
    'epicAction' => 'Epic Action: If you control 4 or more resources, deploy this leader.',
    'deployText' => 'On Attack: If you control a unit that costs 3 or less, draw a card.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/014.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/014-back.png',
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
  'HMW_020' => 
  array (
    'title' => 'Great Grass Plains',
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
      0 => 'Naboo',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/020.png',
    'imageUrlBack' => '',
  ),
  'HMW_021' => 
  array (
    'title' => 'Kashirho',
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
      0 => 'Kashyyyk',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/021.png',
    'imageUrlBack' => '',
  ),
  'HMW_023' => 
  array (
    'title' => 'Bright Tree Village',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Endor',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/023.png',
    'imageUrlBack' => '',
  ),
  'HMW_024' => 
  array (
    'title' => 'Origin Tree',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Kashyyyk',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/024.png',
    'imageUrlBack' => '',
  ),
  'HMW_026' => 
  array (
    'title' => 'Tusken Camp',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Tatooine',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/026.png',
    'imageUrlBack' => '',
  ),
  'HMW_027' => 
  array (
    'title' => 'Bioweapons Lab',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Naboo',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/027.png',
    'imageUrlBack' => '',
  ),
  'HMW_028' => 
  array (
    'title' => 'Jundland Wastes',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Tatooine',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/028.png',
    'imageUrlBack' => '',
  ),
  'HMW_029' => 
  array (
    'title' => 'Dendroid Wilds',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Endor',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/029.png',
    'imageUrlBack' => '',
  ),
  'HMW_030' => 
  array (
    'title' => 'Shadowlands',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Kashyyyk',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/030.png',
    'imageUrlBack' => '',
  ),
  'HMW_031' => 
  array (
    'title' => 'Kyyyalstaad Swamp',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Kashyyyk',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/031.png',
    'imageUrlBack' => '',
  ),
  'HMW_033' => 
  array (
    'title' => 'Otoh Gunga',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Naboo',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/033.png',
    'imageUrlBack' => '',
  ),
  'HMW_034' => 
  array (
    'title' => 'Research Station 9',
    'subtitle' => '',
    'type' => 'Base',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'hp' => 30,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Endor',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/034.png',
    'imageUrlBack' => '',
  ),
  'HMW_043' => 
  array (
    'title' => 'Darth Vader',
    'subtitle' => 'Any Methods Necessary',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 9,
    'power' => 9,
    'hp' => 8,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Command',
      2 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Imperial',
      2 => 'Sith',
    ),
    'text' => 'Saboteur
When Played: Search the top 8 cards of your deck for up to 2 units that each cost 4 or less, play them for free, and deal 2 damage to each of them.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/043.png',
    'imageUrlBack' => '',
  ),
  'HMW_048' => 
  array (
    'title' => 'Vernestra Rwoh',
    'subtitle' => 'We Should Handle This Ourselves',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 5,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
    ),
    'text' => 'Sentinel
As an additional cost to play this unit, put up to 2 units that each cost 5 or less from your discard pile on the bottom of your deck. This unit gains those units\' "When Played" abilities for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/048.png',
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
  'HMW_062' => 
  array (
    'title' => 'Nuvo Vindi',
    'subtitle' => 'Blue Shadow Perfected',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 1,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
    ),
    'text' => 'When Played: You may give a Weakness token to a unit.
When an enemy unit with a Weakness token on it is defeated: You may give a Weakness token to a unit. Use this ability only once each round.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/062.png',
    'imageUrlBack' => '',
  ),
  'HMW_064' => 
  array (
    'title' => 'Scorch',
    'subtitle' => 'Imperial Commando',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 3,
    'hp' => 5,
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
    'text' => 'On Attack: You may deal 1 damage to an upgraded unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/064.png',
    'imageUrlBack' => '',
  ),
  'HMW_070' => 
  array (
    'title' => 'Dark Sanctum',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 3,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify (Attack this to your base, not a unit.)
Attached base gains: "When the regroup phase starts: Draw a card and deal 2 damage to this base."',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/070.png',
    'imageUrlBack' => '',
  ),
  'HMW_077' => 
  array (
    'title' => 'Boss Nass',
    'subtitle' => 'Otoh Gunga Boss',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 4,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Gungan',
      1 => 'Official',
    ),
    'text' => 'When Played/On Attack: You may defeat a Shield token on a friendly Gungan unit. If you do, create a Beast token and give a Shield token to it.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/077.png',
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
  'HMW_084' => 
  array (
    'title' => 'Gunga City Guard',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 2,
    'hp' => 1,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Gungan',
    ),
    'text' => 'Restore 1
While you control another Gungan unit or Naboo base, this unit gains Shielded.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/084.png',
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
  'HMW_107' => 
  array (
    'title' => 'Stormtrooper Patrol',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 2,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Trooper',
    ),
    'text' => 'Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
While you control another unit that costs 3 or more, this unit gets +2/+0.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/107.png',
    'imageUrlBack' => '',
  ),
  'HMW_110' => 
  array (
    'title' => 'Emperor Palpatine',
    'subtitle' => 'Consolidating Power',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 3,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Imperial',
      2 => 'Sith',
      3 => 'Official',
    ),
    'text' => 'When Played: You may take control of an enemy non-leader unit that costs 3 or less. If you do, give 2 Weakness tokens to it.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/110.png',
    'imageUrlBack' => '',
  ),
  'HMW_113' => 
  array (
    'title' => 'Sinister War Memorial',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Special',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify (Attach this to your base, not a unit.)
Attached base gains "When a friendly unit is defeated: Heal 1 damage from this base."',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/113.png',
    'imageUrlBack' => '',
  ),
  'HMW_114' => 
  array (
    'title' => 'Breach',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Tactic',
    ),
    'text' => 'A friendly unit deals damage equal to its power to an enemy unit in its arena. If the friendly unit has Overwhelm, deal deal excess damage to an enemy base.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/114.png',
    'imageUrlBack' => '',
  ),
  'HMW_115' => 
  array (
    'title' => 'Leia Organa',
    'subtitle' => 'These Are My Friends',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 2,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Official',
    ),
    'text' => 'When you play another unit that costs 3 or less: Heal 1 damage from your base.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/115.png',
    'imageUrlBack' => '',
  ),
  'HMW_116' => 
  array (
    'title' => 'Ewok Brigade',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 2,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/116.png',
    'imageUrlBack' => '',
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
  'HMW_124' => 
  array (
    'title' => 'Luminara Unduli',
    'subtitle' => 'Besieged General',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 7,
    'power' => 7,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
      2 => 'Republic',
    ),
    'text' => 'When you play a unit (including this one): You may attack with a unit. It gets +2/+0 for this attack.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/124.png',
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
    'text' => 'Attach to a non-Vehicle unit.
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
  'HMW_136' => 
  array (
    'title' => 'Lifetree Caravan',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 2,
    'hp' => 1,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'When Played: If you control 3 or more units (including this one), you may resource the top card of your deck.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/136.png',
    'imageUrlBack' => '',
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
  'HMW_147' => 
  array (
    'title' => 'Beast Lair',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify (Attach this to your base, not a unit.)
Attached base gains: "When the action phase starts: You discard a card from your hand. If you do, create a Beast token."',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/147.png',
    'imageUrlBack' => '',
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
  'HMW_162' => 
  array (
    'title' => 'Teebo',
    'subtitle' => 'Striped Hunter',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 3,
    'hp' => 1,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'Hidden (This unit can\'t be attacked if it was played this phase.)
Other friendly Ewok units gain Hidden.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/162.png',
    'imageUrlBack' => '',
  ),
  'HMW_164' => 
  array (
    'title' => 'Chief Chirpa',
    'subtitle' => 'Defiant Elder',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 1,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'This unit gets +1/+0 for each other friendly Ewok unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/164.png',
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
  'HMW_176' => 
  array (
    'title' => 'Village Troublemaker',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 2,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'While you control and Endor base, this unit gains Hidden and Saboteur.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/176.png',
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
  'HMW_188' => 
  array (
    'title' => 'Giant Gorax',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 7,
    'power' => 7,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Creature',
    ),
    'text' => 'Overwhelm
On Attack/When Defeated: If you control an Endor base, each opponent chooses one:
<bullet>You deal 3 damage to a unit or base they control.
They discard a card from their hand and defeat a resource they control.</bullet>',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/188.png',
    'imageUrlBack' => '',
  ),
  'HMW_193' => 
  array (
    'title' => 'Nightfall',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Disaster',
    ),
    'text' => 'Deal 1 damage to an enemy unit.
If you control an Endor base , you may attack with a unit. It gets +2/+0 for this attack.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/193.png',
    'imageUrlBack' => '',
  ),
  'HMW_200' => 
  array (
    'title' => 'Rish Loo',
    'subtitle' => 'Traitorous Minister',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 3,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
      1 => 'Gungan',
      2 => 'Official',
    ),
    'text' => 'Hidden
When Played: Take control of an enemy non-leader unit with a Weakness token on it. At the start of the next regroup phase, its owner takes control of it.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/200.png',
    'imageUrlBack' => '',
  ),
  'HMW_202' => 
  array (
    'title' => 'Inferno Squad',
    'subtitle' => 'We Can Grieve Later',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 3,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Trooper',
    ),
    'text' => 'When Played/When Defeated: You may deal 1 damage to a unit and give a Weakness token to it.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/202.png',
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
  'IC27_001' => 
  array (
    'title' => 'Darth Vader',
    'subtitle' => 'No One to Stop Us',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Special',
    'set' => 'IC27',
    'cost' => 7,
    'power' => 5,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Imperial',
      2 => 'Sith',
    ),
    'text' => 'Action [1 resource, Exhaust, defeat a friendly unit]: Draw a card and heal 2 damage from your base.',
    'epicAction' => 'Epic Action: If you control 7 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)',
    'deployText' => 'On Attack: You may defeat another friendly unit. If you do, draw a card and heal 2 damage from your base.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/001.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/001-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'IC27_008' => 
  array (
    'title' => 'Princess Leia',
    'subtitle' => 'On a Diplomatic Mission',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Special',
    'set' => 'IC27',
    'cost' => 6,
    'power' => 4,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Official',
    ),
    'text' => 'Action [1 resource, Exhaust]: Draw a card, then put a card from your hand on the top or bottom of your deck.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader. (Flip her, ready her, and move her to the ground arena.)',
    'deployText' => 'On Attack: Draw a card, then put a card from your hand on the top or bottom of your deck.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/008.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/008-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'IC27_022' => 
  array (
    'title' => 'Moff Gideon',
    'subtitle' => 'Cold Calling',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 3,
    'hp' => 6,
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
    'text' => 'If a friendly unit was defeated this phase, this unit costs [2 resources] less to play.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/022.png',
    'imageUrlBack' => '',
  ),
  'IC27_024' => 
  array (
    'title' => 'Grand Admiral Thrawn',
    'subtitle' => 'Listen to Me Carefully',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 6,
    'power' => 4,
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
    'text' => 'When Played / On Attack / When Defeated: You may give an Experience token to a friendly unit. It gains Sentinel for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/024.png',
    'imageUrlBack' => '',
  ),
  'IC27_026' => 
  array (
    'title' => 'Darth Sidious',
    'subtitle' => 'Move Against the Jedi',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 7,
    'power' => 5,
    'hp' => 8,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Separatist',
      2 => 'Sith',
    ),
    'text' => 'Restore 3 (When this unit attacks, heal 3 damage from your base.)
When you heal damage from your base: Deal that much damage to an enemy unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/026.png',
    'imageUrlBack' => '',
  ),
  'IC27_067' => 
  array (
    'title' => 'Darth Vader',
    'subtitle' => 'Useless to Resist',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'IC27',
    'cost' => 8,
    'power' => 8,
    'hp' => 8,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Imperial',
      2 => 'Sith',
    ),
    'text' => 'Ambush
Each other friendly unit gains Ambush.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/067.png',
    'imageUrlBack' => '',
  ),
  'IC27_071' => 
  array (
    'title' => 'Avar Kriss',
    'subtitle' => 'For Light and Life',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 0,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
      2 => 'Republic',
    ),
    'text' => 'Raid 1 (This unit gets +1/+0 while attacking.)
This unit gains Raid 1 for each other friendly unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/071.png',
    'imageUrlBack' => '',
  ),
  'IC27_078' => 
  array (
    'title' => 'Anakin Skywalker',
    'subtitle' => 'Destined For Darkness',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 7,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
      2 => 'Republic',
    ),
    'text' => 'When Defeated: Search your deck for a card named Darth Vader, reveal it, and draw it.
While this unit is in your discard pile, ignore the aspect penalties on cards you play named Darth Vader.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/078.png',
    'imageUrlBack' => '',
  ),
  'IC27_079' => 
  array (
    'title' => 'Qui-Gon Jinn',
    'subtitle' => 'Unwavering Belief',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 5,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Republic',
      1 => 'Force',
      2 => 'Jedi',
    ),
    'text' => 'Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
When Played: Give another friendly unit +2/+2 for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/079.png',
    'imageUrlBack' => '',
  ),
  'IC27_104' => 
  array (
    'title' => 'The Inquisitor\'s TIE',
    'subtitle' => 'Would Rather Win',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 4,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Vehicle',
      2 => 'Fighter',
      3 => 'Inquisitor',
    ),
    'text' => 'On Attack: Each player with 4 or more cards in their hand discards a card from their hand.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/104.png',
    'imageUrlBack' => '',
  ),
  'IC27_146' => 
  array (
    'title' => 'Boba Fett',
    'subtitle' => 'Compensated If He Dies',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 4,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Underworld',
      1 => 'Bounty Hunter',
    ),
    'text' => 'When Attack Ends: If the defending unit was defeated, you may ready 2 resources.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/146.png',
    'imageUrlBack' => '',
  ),
  'IC27_158' => 
  array (
    'title' => 'Millennium Falcon',
    'subtitle' => 'YA-HOO!',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 4,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Vehicle',
      2 => 'Transport',
    ),
    'text' => 'When Attack Ends: You may pay [1 resource]. If you do, return a friendly unit that costs 3 or less to its owner\'s hand. If it\'s returned to your hand, you may play it for free.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/158.png',
    'imageUrlBack' => '',
  ),
  'IC27_167' => 
  array (
    'title' => 'Lando Calrissian',
    'subtitle' => 'Check This Out',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 4,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Official',
    ),
    'text' => 'When Played: Return 3 friendly resources to their owner\'s hands. Then, you may resource up to 3 cards from your hand.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/167.png',
    'imageUrlBack' => '',
  ),
  'IC27_168' => 
  array (
    'title' => 'Cunning Ploy',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 4,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Trick',
    ),
    'text' => 'Look at an opponent\'s hand. You may discard a card from it. If you do, that player draws a card.
Exhaust an enemy unit.
You may attack with a unit. It gets +3/+0 for this attack.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/168.png',
    'imageUrlBack' => '',
  ),
  'IC27_187' => 
  array (
    'title' => 'Jar Jar Binks',
    'subtitle' => 'Bumbling Representative',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 1,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Republic',
      1 => 'Gungan',
      2 => 'Official',
    ),
    'text' => 'On Attack: Discard a card from your deck. If it costs 6 or more, this unit gets +4/+0 for this attack.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/187.png',
    'imageUrlBack' => '',
  ),
);
