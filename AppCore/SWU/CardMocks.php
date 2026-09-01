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
  'HMW_005' => 
  array (
    'title' => 'Jar Jar Binks',
    'subtitle' => 'Bombad General',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 4,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Gungan',
    ),
    'text' => 'Action [1 resource, Exhaust]: If you gave a token upgrade to a unit this phase, deal 1 damage to a unit and heal 1 damage from a base.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'Shielded (When you play this leader, give a Shield token to him.)
On Attack: If you gave a token upgrade to a unit this phase, you may deal 1 damage to a unit and heal 1 damage from a base.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/005.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/005-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
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
  'HMW_010' => 
  array (
    'title' => 'Tarfful',
    'subtitle' => 'Fighting from the Shadowlands',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 3,
    'hp' => 7,
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
    'text' => 'Action [2 resources, Exhaust, discard a card from your hand]: Create a Beast token.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
On Attack: You may pay [1 resource]. If you do, create a Beast token.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/010.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/010-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_011' => 
  array (
    'title' => 'Darth Sidious',
    'subtitle' => 'There is No Mercy',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 4,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Sith',
    ),
    'text' => 'When you deal 4 or more damage to a unit or a base: You may exhaust this leader. If you do, deal 1 damage to a different unit or base.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'Hidden (This unit can\'t be attacked if it was played this phase.)
When you deal 4 or more damage to a unit or a base: You may deal 1 damage to a different unit or base.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/011.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/011-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_013' => 
  array (
    'title' => 'Cham Syndulla',
    'subtitle' => 'Hammer of Ryloth',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 3,
    'hp' => 8,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Twi\'lek',
    ),
    'text' => 'When non-combat damage is dealt to a friendly unit or base: You may exhaust this leader. If you do, deal 1 damage to an enemy unit or base.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'When non-combat damage is dealt to a friendly unit or base: You may deal 1 damage to an enemy unit or base.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/013.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/013-back.png',
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
  'HMW_016' => 
  array (
    'title' => 'Maul',
    'subtitle' => 'Old Master',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 7,
    'power' => 5,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Fringe',
    ),
    'text' => 'Action [Exhaust]: Play a unit from your hand. It costs [1 resource] less. Then, defeat it. (When Played abilities resolve after the unit is defeated.)',
    'epicAction' => 'Epic Action: If you control 7 or more resources, deploy this leader.',
    'deployText' => 'Shielded
When Deployed: You may play a unit that was defeated this phase from your discard pile. It costs [5 resources] less.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/016.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/016-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_017' => 
  array (
    'title' => 'Osha',
    'subtitle' => 'Haunted by her Past',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 5,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
    ),
    'text' => 'Action [Exhaust]: If a friendly Heroism unit was defeated this phase, play a Villainy unit from your resources, ignoring its Villainy aspect penalties. If you do so, you may resource a card from your hand.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'Saboteur
Action: Play a Villainy unit from your resources, ignoring its Villainy aspect penalties. If you do, you may resource a card from your hand.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/017.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/017-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'HMW_018' => 
  array (
    'title' => 'The Warrior',
    'subtitle' => 'Deft Duelist',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 3,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Tusken',
    ),
    'text' => 'Action [1 resource, Exhaust]: Play a unit with 3 or less power from your hand (paying its cost) and give it Ambush for this phase.',
    'epicAction' => 'Epic Action: If you control 5 or more resources, deploy this leader.',
    'deployText' => 'Ambush (When you play this leader, she may immediately attack an enemy unit.)
Raid 1 (This unit gets +1/+0 while attacking.)',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/018.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/018-back.png',
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
  'HMW_035' => 
  array (
    'title' => 'Hunter',
    'subtitle' => 'Everyone Get to Cover!',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 4,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Vigilance',
      2 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Clone',
    ),
    'text' => 'When Played: Choose two. You may choose the same option more than once:

• Give a Shield token to a unit.

• Attack with a unit, even if it\'s exhausted. It can\'t attack bases for this attack.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/035.png',
    'imageUrlBack' => '',
  ),
  'HMW_036' => 
  array (
    'title' => 'Kelnacca',
    'subtitle' => 'Solitary Master',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 4,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
      2 => 'Wookiee',
    ),
    'text' => 'Restore 2

When Played: You may pay any number of resources. For every 3 resources paid this way, deal damage equal to this unit\'s power to an enemy unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/036.png',
    'imageUrlBack' => '',
  ),
  'HMW_037' => 
  array (
    'title' => 'Bacta Tank',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 1,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify
When Played: Heal up to 3 damage from a non-Vehicle unit.
Action [defeat this upgrade]: Put a non-Vehicle unit from your discard pile on top of your deck.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/037.png',
    'imageUrlBack' => '',
  ),
  'HMW_038' => 
  array (
    'title' => 'Bestial Bond',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 3,
    'upgradePower' => 2,
    'upgradeHp' => 2,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Innate',
    ),
    'text' => 'When Played: If attached unit is a Creature or a Force unit, create a Beast token.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/038.png',
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
  'HMW_045' => 
  array (
    'title' => 'Logray',
    'subtitle' => 'Bright Tree Shaman',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 1,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Ewok',
    ),
    'text' => 'When another friendly unit that costs 3 or less is dealt damage: You may deal 1 damage to an enemy unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/045.png',
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
  'HMW_051' => 
  array (
    'title' => 'Third Sister',
    'subtitle' => 'Cycle of Vengeance',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 6,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Cunning',
      2 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Imperial',
      2 => 'Inquisitor',
    ),
    'text' => 'Overwhelm
When Played: You may deal 2 damage to a unit. If you do, that unit\'s controller may deal 3 damage to a unit. If they do, that unit\'s controller may deal 4 damage to a unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/051.png',
    'imageUrlBack' => '',
  ),
  'HMW_055' => 
  array (
    'title' => 'Mae',
    'subtitle' => 'Kill the Dream',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 2,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Vigilance',
      2 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Sith',
    ),
    'text' => 'Ambush
Shielded
Grit',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/055.png',
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
  'HMW_063' => 
  array (
    'title' => 'Rho Medical Shuttle',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 3,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Vehicle',
      2 => 'Transport',
    ),
    'text' => 'When Played/On Attack: You may heal 1 damage from another unit or base.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/063.png',
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
  'HMW_066' => 
  array (
    'title' => 'Carrion Spike',
    'subtitle' => 'Harbinger of Tyranny',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 5,
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
      1 => 'Vehicle',
      2 => 'Capital Ship',
    ),
    'text' => 'Shielded
For each upgrade on your base, this unit gets +1/+0 and gains Restore 1.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/066.png',
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
  'HMW_071' => 
  array (
    'title' => 'Ravage',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Disaster',
      1 => 'Tactic',
    ),
    'text' => 'Distribute up to 3 Weakness tokens among any number of units.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/071.png',
    'imageUrlBack' => '',
  ),
  'HMW_073' => 
  array (
    'title' => 'Peppi Bow',
    'subtitle' => 'Shaak Herder',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 2,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Gungan',
    ),
    'text' => 'Restore 1 (When this unit attacks, heal 1 damage from your base.)
While this unit is upgraded, she gets +1/+1.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/073.png',
    'imageUrlBack' => '',
  ),
  'HMW_074' => 
  array (
    'title' => 'Yord Fandar',
    'subtitle' => 'Devoutly Disciplined',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 2,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
    ),
    'text' => 'While a base has 15 or more damage on it, this unit gains Sentinel.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/074.png',
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
  'HMW_078' => 
  array (
    'title' => 'Qui-Gon Jinn',
    'subtitle' => 'We\'ll Handle This',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 2,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
      2 => 'Republic',
    ),
    'text' => 'Grit
When Played: You may defeat a unit that attacked your base this phase. If it\'s a leader unit, defeat this unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/078.png',
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
  'HMW_088' => 
  array (
    'title' => 'Numa',
    'subtitle' => 'Still Fighting',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 4,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Twi\'lek',
    ),
    'text' => 'Restore 1 (When this unit attacks, heal 1 damage from your base.)
If this unit would be dealt damage, prevent 1 of that damage',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/088.png',
    'imageUrlBack' => '',
  ),
  'HMW_094' => 
  array (
    'title' => 'Sando Aqua Monster',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 8,
    'power' => 5,
    'hp' => 9,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Creature',
    ),
    'text' => 'Grit
When Played: If you control a Naboo base, you may defeat any number of ground units with combined power equal to or less than this unit\'s power. Deal damage to this unit equal to the combined power of the defeated units.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/094.png',
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
  'HMW_100' => 
  array (
    'title' => 'Torrent',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Disaster',
    ),
    'text' => 'Give a Weakness token to a unit. If you control a Naboo base, give 2 Weakness tokens to that unit instead.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/100.png',
    'imageUrlBack' => '',
  ),
  'HMW_102' => 
  array (
    'title' => 'Dragon\'s Might',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Innate',
    ),
    'text' => 'Defeat a non-leader unit with 4 or less power.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/102.png',
    'imageUrlBack' => '',
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
  'HMW_117' => 
  array (
    'title' => 'Chewbacca',
    'subtitle' => 'Resourceful Wookiee',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 0,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Wookiee',
    ),
    'text' => 'This unit gains Raid 1 for each exhausted resource you control.
While each resource you control is exhausted, this unit gains Overwhelm.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/117.png',
    'imageUrlBack' => '',
  ),
  'HMW_118' => 
  array (
    'title' => 'Ryyk Blademaster',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 5,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Wookiee',
    ),
    'text' => 'While you control 6 or more resources, this unit gains Ambush and Overwhelm.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/118.png',
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
  'HMW_123' => 
  array (
    'title' => 'King Grakchawwaa',
    'subtitle' => 'King of Kashyyyk',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 6,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Wookiee',
      1 => 'Official',
    ),
    'text' => 'When Played: For each other friendly Wookiee unit, resource the top card of your deck. Ready each card resourced this way.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/123.png',
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
  'HMW_125' => 
  array (
    'title' => 'The Marauder',
    'subtitle' => 'A New Home',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 7,
    'power' => 5,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Vehicle',
      1 => 'Transport',
    ),
    'text' => 'While playing this unit, you may choose any number of friendly units. Deal 1 damage to each of them. For each unit chosen this way, this unit costs [1 resource] less.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/125.png',
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
  'HMW_145' => 
  array (
    'title' => 'Origin Tree Shyyyo',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 4,
    'hp' => 8,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Creature',
    ),
    'text' => 'Restore 1

While you control a Kashyyyk base, the first, second, and third units you play each round cost [1 resource] less, [2 resources] less, and [3 resources] less, respectively.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/145.png',
    'imageUrlBack' => '',
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
  'HMW_151' => 
  array (
    'title' => 'Overgrowth',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 5,
    'aspect' => 
    array (
      0 => 'Command',
    ),
    'trait' => 
    array (
      0 => 'Disaster',
    ),
    'text' => 'If you control a Kashyyyk base, a friendly unit deals damage equal to its power to an enemy unit.
Resource this card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/151.png',
    'imageUrlBack' => '',
  ),
  'HMW_152' => 
  array (
    'title' => 'Babwa Venomor',
    'subtitle' => 'Burning Kashyyyk',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 4,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
    ),
    'text' => 'Overwhelm
When Played: An opponent creates a Beast token.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/152.png',
    'imageUrlBack' => '',
  ),
  'HMW_154' => 
  array (
    'title' => 'Dooku\'s Solar Sailer',
    'subtitle' => 'Droid Army Portent',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 3,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
      1 => 'Vehicle',
      2 => 'Transport',
    ),
    'text' => 'When Played: If you control a unit that costs 1 or less, each opponent discards a card from their hand.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/154.png',
    'imageUrlBack' => '',
  ),
  'HMW_159' => 
  array (
    'title' => 'General Grievous',
    'subtitle' => 'Scourge of Dathomir',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 7,
    'power' => 8,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
      1 => 'Official',
    ),
    'text' => 'Bases can\'t be healed.
When Played: Deal 4 damage to a base.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/159.png',
    'imageUrlBack' => '',
  ),
  'HMW_161' => 
  array (
    'title' => 'Raze to Ruin',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Disaster',
      1 => 'Plan',
    ),
    'text' => 'Each player discards all but 3 cards from their hand.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/161.png',
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
  'HMW_163' => 
  array (
    'title' => 'Champion of Endor',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 3,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Aggression',
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
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/163.png',
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
  'HMW_168' => 
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
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/168.png',
    'imageUrlBack' => '',
  ),
  'HMW_169' => 
  array (
    'title' => 'Crosshair',
    'subtitle' => 'I\'ve Changed',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 5,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Clone',
    ),
    'text' => 'When this unit is dealt damage and survives: Each player draws a card.
When an opponent draws 1 or more cards during the action phase: Deal 2 damage to their base.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/169.png',
    'imageUrlBack' => '',
  ),
  'HMW_170' => 
  array (
    'title' => 'Han Solo',
    'subtitle' => 'My Team\'s Ready',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 4,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Official',
    ),
    'text' => 'Action [Exhaust]: Ready another unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/170.png',
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
  'HMW_174' => 
  array (
    'title' => 'Maul',
    'subtitle' => 'Only Revenge Remains',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 6,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Underworld',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/174.png',
    'imageUrlBack' => '',
  ),
  'HMW_175' => 
  array (
    'title' => 'Fennec Shand',
    'subtitle' => 'A Ship For a Life',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 0,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Underworld',
    ),
    'text' => 'Raid 2 (This unit gets +2/+0 while attacking.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/175.png',
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
    'text' => 'While you control an Endor base, this unit gains Hidden and Saboteur.',
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
  'HMW_180' => 
  array (
    'title' => 'Stormchaser',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
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
      0 => 'Tusken',
    ),
    'text' => 'When Played: You may reveal a Disaster card from your hand. If you do or if there\'s a Disaster card in your discard pile, draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/180.png',
    'imageUrlBack' => '',
  ),
  'HMW_185' => 
  array (
    'title' => 'Ty Yorrick',
    'subtitle' => 'Monster Hunter',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 4,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Bounty Hunter',
    ),
    'text' => 'If a friendly ability would deal damage, you may have that ability deal that much damage plus 1 instead.
On Attack: You may deal 1 damage to a Creature unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/185.png',
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
  'HMW_196' => 
  array (
    'title' => 'Qimir',
    'subtitle' => 'Everyone Has a Weakness',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 3,
    'hp' => 1,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
    ),
    'text' => 'When Defeated: You may discard the top card of your deck. If it\'s not Villainy, give a Weakness token to an enemy unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/196.png',
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
  'HMW_201' => 
  array (
    'title' => 'Sandtrooper Squad',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 3,
    'hp' => 4,
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
    'text' => 'Ambush (When you play this unit, it may attack an enemy unit.)
Raid 1 (This unit gets +1/+0 while attacking.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/201.png',
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
  'HMW_204' => 
  array (
    'title' => 'Nightbrother',
    'subtitle' => 'Maul\'s Gauntlet',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Legendary',
    'set' => 'HMW',
    'cost' => 7,
    'power' => 6,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Vehicle',
      1 => 'Transport',
    ),
    'text' => 'When Played: You may play a unit from your discard pile. It costs [3 resources] less and enters play ready. At the start of the next regroup phase, defeat it.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/204.png',
    'imageUrlBack' => '',
  ),
  'HMW_205' => 
  array (
    'title' => 'Intelligence Agency',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 1,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Fortification',
    ),
    'text' => 'Fortify (Attach this to your base, not a unit.)
Attached base gains: "You may look at the top card of your deck at any time."
When Played: Look at an opponent\'s hand. You may discard a card from it. If you do, they draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/205.png',
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
  'HMW_208' => 
  array (
    'title' => 'Luke Skywalker',
    'subtitle' => 'Dreaming Farmboy',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 1,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Fringe',
    ),
    'text' => 'Raid 1.
While it\'s the first round of the game, this unit enters play ready.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/208.png',
    'imageUrlBack' => '',
  ),
  'HMW_210' => 
  array (
    'title' => 'Sol',
    'subtitle' => 'Compassionate Guardian',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 2,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
    ),
    'text' => 'Shielded (When you play this unit, give a Shield token to it.)
On Attack: This unit gains Sentinel for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/210.png',
    'imageUrlBack' => '',
  ),
  'HMW_211' => 
  array (
    'title' => 'Tech',
    'subtitle' => 'I Thought It Was Obvious',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 3,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Clone',
    ),
    'text' => 'When this unit is dealt damage and survives: You may exhaust a unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/211.png',
    'imageUrlBack' => '',
  ),
  'HMW_212' => 
  array (
    'title' => 'The Chieftain',
    'subtitle' => 'Here Since the Oceans Dried',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 2,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Tusken',
    ),
    'text' => 'This unit gains Raid 1 for each other friendly Tusken unit.
While a friendly Tusken unit is defending, it gets +1/+0 for each Raid it has.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/212.png',
    'imageUrlBack' => '',
  ),
  'HMW_214' => 
  array (
    'title' => 'Phee Genoa',
    'subtitle' => 'Liberator of Ancient Wonders',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 5,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Underworld',
    ),
    'text' => 'Hidden
When an enemy leader deploys: Its controller may pay [2 resources]. If they don\'t, exhaust that leader.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/214.png',
    'imageUrlBack' => '',
  ),
  'HMW_217' => 
  array (
    'title' => 'Don\'t Touch Anything',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Trick',
    ),
    'text' => 'Deal 3 damage to a random enemy unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/217.png',
    'imageUrlBack' => '',
  ),
  'HMW_221' => 
  array (
    'title' => 'Teeka',
    'subtitle' => 'You\'re In Luck',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 2,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Jawa',
    ),
    'text' => 'When Played: Choose one:

• Give a unit Sentinel for this phase.

• A unit loses Sentinel for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/221.png',
    'imageUrlBack' => '',
  ),
  'HMW_222' => 
  array (
    'title' => 'Sandcrawler Sales Team',
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
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Jawa',
    ),
    'text' => 'Saboteur (When this unit attacks, ignore Sentinel and defeat the defender\'s Shields.)
When Played: If you control a Tatooine base, you may return an upgrade that costs 3 or less to its owner\'s hand.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/222.png',
    'imageUrlBack' => '',
  ),
  'HMW_223' => 
  array (
    'title' => 'Therm Scissorpunch',
    'subtitle' => 'Boastful Gambler',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 5,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Underworld',
    ),
    'text' => 'When the action phase starts: Reveal the top card of your deck and an opponent\'s deck. For each card that cost 3 or more revealed this way, this unit gets -2/-2 for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/223.png',
    'imageUrlBack' => '',
  ),
  'HMW_225' => 
  array (
    'title' => 'Boba Fett',
    'subtitle' => 'Family Found',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 3,
    'power' => 1,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Tusken',
    ),
    'text' => 'Ambush
When a friendly unit with Ambush enters plays (including this one): Give it Raid 1 and Saboteur for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/225.png',
    'imageUrlBack' => '',
  ),
  'HMW_230' => 
  array (
    'title' => 'Raiding Party',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 5,
    'power' => 0,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Tusken',
    ),
    'text' => 'Raid 6 (This unit gets +6/+0 while attacking.)
When Played: If you control another Tusken unit or a Tatooine base, you may exhaust a ground unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/230.png',
    'imageUrlBack' => '',
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
  'HMW_237' => 
  array (
    'title' => 'Easy Prey',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 1,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Innate',
    ),
    'text' => 'Create a Beast token.
An opponent creates a Beast token. Give a Weakness token to it.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/237.png',
    'imageUrlBack' => '',
  ),
  'HMW_238' => 
  array (
    'title' => 'Exploit Confidence',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 2,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Tactic',
    ),
    'text' => 'Return a non-leader unit with 6 or more power to it\'s owner\'s hand.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/238.png',
    'imageUrlBack' => '',
  ),
  'HMW_240' => 
  array (
    'title' => 'Sandstorm',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 3,
    'aspect' => 
    array (
      0 => 'Cunning',
    ),
    'trait' => 
    array (
      0 => 'Disaster',
    ),
    'text' => 'While you control a Tatooine base, this event costs [1 resource] less to play.
Choose an arena, Give a Weakness token to each exhausted enemy unit in that arena.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/240.png',
    'imageUrlBack' => '',
  ),
  'HMW_243' => 
  array (
    'title' => 'Sun Fac',
    'subtitle' => 'Poggle\'s Second',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 2,
    'power' => 2,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
    ),
    'text' => 'When Played: Give a unit Grit for this phase. (It gets +1/+0 for each damage on it.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/243.png',
    'imageUrlBack' => '',
  ),
  'HMW_254' => 
  array (
    'title' => 'Captain Tarpals',
    'subtitle' => 'Grand Army Captain',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 0,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Gungan',
      1 => 'Trooper',
    ),
    'text' => 'Shielded (When you play this unit, give a Shield token to him.)
Raid 2 (This unit gets +2/+0 while attacking.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/254.png',
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
  'HMW_260' => 
  array (
    'title' => 'Queen Amidala',
    'subtitle' => 'Retaking Theed',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'power' => 4,
    'hp' => 4,
    'aspect' => 
    array (
    ),
    'trait' => 
    array (
      0 => 'Naboo',
      1 => 'Official',
    ),
    'text' => 'If you control an upgraded base, this unit costs [2 resources] less to play.
Restore 2',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/260.png',
    'imageUrlBack' => '',
  ),
  'HMW_263' => 
  array (
    'title' => 'Wrecker',
    'subtitle' => 'Wrecking the Empire',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'HMW',
    'cost' => 6,
    'power' => 6,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Clone',
    ),
    'text' => 'When Played: Each player chooses a unit they control. Deal 3 damage to each chosen unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/263.png',
    'imageUrlBack' => '',
  ),
  'HMW_265' => 
  array (
    'title' => 'Twi\'lek Kalikori',
    'subtitle' => '',
    'type' => 'Upgrade',
    'arena' => '',
    'rarity' => 'Rare',
    'set' => 'HMW',
    'cost' => 4,
    'upgradePower' => 2,
    'upgradeHp' => 2,
    'aspect' => 
    array (
      0 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Item',
    ),
    'text' => 'When Played: If attached unit is a Twi\'lek, search the top 8 cards of your deck for any number of Twi\'lek units with a combined costs 5 or less and play each of them for free.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/265.png',
    'imageUrlBack' => '',
  ),
  'HMW_268' => 
  array (
    'title' => 'Offworld Jawa',
    'subtitle' => '',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 1,
    'power' => 2,
    'hp' => 1,
    'aspect' => 
    array (
    ),
    'trait' => 
    array (
      0 => 'Jawa',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/268.png',
    'imageUrlBack' => '',
  ),
  'HMW_272' => 
  array (
    'title' => 'Growth',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Common',
    'set' => 'HMW',
    'cost' => 5,
    'aspect' => 
    array (
    ),
    'trait' => 
    array (
      0 => 'Innate',
    ),
    'text' => 'Create a Beast token.
Heal 3 damage from your base.
Draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/HMW/272.png',
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
