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
  'IC27_038' => 
  array (
    'title' => 'Admiral Holdo',
    'subtitle' => 'We Are The Spark',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 3,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Resistance',
      1 => 'Official',
    ),
    'text' => 'Draw 1 more card during the regroup phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/038.png',
    'imageUrlBack' => '',
  ),
  'IC27_041' => 
  array (
    'title' => 'Captain Rex',
    'subtitle' => 'Staunch Advocate',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 7,
    'power' => 7,
    'hp' => 5,
    'aspect' => 
    array (
    ),
    'trait' => 
    array (
      0 => 'Republic',
      1 => 'Clone',
      2 => 'Trooper',
    ),
    'text' => 'Sentinel
Shielded
Restore 3',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/041.png',
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
  'IC27_103' => 
  array (
    'title' => 'Grand Inquisitor',
    'subtitle' => 'How The Mighty Will Fall',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 3,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Imperial',
      2 => 'Inquisitor',
    ),
    'text' => 'While this unit is damaged, he gains Raid 3 and Saboteur.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/103.png',
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
  'IC27_166' => 
  array (
    'title' => 'I\'ve Got A Bad Feeling',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 4,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Innate',
    ),
    'text' => 'Return a non-leader unit to its owner\'s hand.
Give a Shield token to a friendly unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/166.png',
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
