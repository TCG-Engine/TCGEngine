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
  'IC27_002' => 
  array (
    'title' => 'Rey',
    'subtitle' => 'In Balance',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 3,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Resistance',
    ),
    'text' => 'Action [1 resource, Exhaust]: Give an Experience token to a damaged unit.',
    'epicAction' => 'Epic Action: If you control 5 or more resources , deploy this leader.',
    'deployText' => 'On Attack: You may give an Experience token to a damaged unit.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/002.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/002-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'IC27_003' => 
  array (
    'title' => 'Kylo Ren',
    'subtitle' => 'Supreme Leader',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 5,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'First Order',
      2 => 'Official',
    ),
    'text' => 'Action [Exhaust, discard a card from your hand]: Give an Experience token to a unit.',
    'epicAction' => 'Epic Action: If you control 5 or more resources, deploy this leader.',
    'deployText' => 'When Deployed: You may return a unit from your discard pile to your hand.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/003.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/003-back.png',
    'leaderUnitTitle' => '',
    'leaderUnitSubtitle' => '',
    'leaderUnitTrait' => 
    array (
    ),
    'leaderUnitArena' => '',
    'leaderUnitType' => '',
  ),
  'IC27_006' => 
  array (
    'title' => 'Luke Skywalker',
    'subtitle' => 'I Have to Face Him',
    'type' => 'Leader',
    'arena' => 'Ground',
    'rarity' => 'Common',
    'set' => 'IC27',
    'cost' => 6,
    'power' => 4,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
      2 => 'Rebel',
    ),
    'text' => 'Draw 2 more cards in your starting hand.',
    'epicAction' => 'Epic Action: If you control 6 or more resources, deploy this leader.',
    'deployText' => 'Overwhelm (When attacking an enemy unit, deal excess damage to the opponent\'s base.)
On Attack: You may discard a card from your hand. If you do, this unit gets +2/+0 for this attack.',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/006.png',
    'imageUrlBack' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/006-back.png',
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
  'IC27_014' => 
  array (
    'title' => 'Dryden Vos',
    'subtitle' => 'Thumb-Spiking the Scale',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 1,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Underworld',
    ),
    'text' => 'On Attack: You may deal 1 damage to a friendly unit. If it survives, give an Experience token to it.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/014.png',
    'imageUrlBack' => '',
  ),
  'IC27_021' => 
  array (
    'title' => 'General Grievous',
    'subtitle' => 'Four-Armed Menace',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 4,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
      1 => 'Official',
    ),
    'text' => 'When you play an upgrade on this unit: Choose one that hasn\'t been chosen this phase:<br/>
• Give an Experience token to him.<br/>
• Heal 3 damage from your base.<br/>
• Draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/021.png',
    'imageUrlBack' => '',
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
  'IC27_031' => 
  array (
    'title' => 'BB-8',
    'subtitle' => 'Frazzled Friend',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 1,
    'power' => 0,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Resistance',
      1 => 'Droid',
    ),
    'text' => 'On Attack: Heal 1 damage to a friendly unit. If you do, deal 1 damage to an enemy unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/031.png',
    'imageUrlBack' => '',
  ),
  'IC27_032' => 
  array (
    'title' => 'Kaydel Connix',
    'subtitle' => 'Keep Going!',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 1,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Resistance',
    ),
    'text' => 'Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/032.png',
    'imageUrlBack' => '',
  ),
  'IC27_034' => 
  array (
    'title' => 'Obi-Wan\'s Interceptor',
    'subtitle' => 'Nothing Too Fancy',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
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
      0 => 'Jedi',
      1 => 'Republic',
      2 => 'Vehicle',
      3 => 'Fighter',
    ),
    'text' => 'Other friendly Republic units get +0/+1.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/034.png',
    'imageUrlBack' => '',
  ),
  'IC27_037' => 
  array (
    'title' => 'Finn',
    'subtitle' => 'Stand and Fight',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 4,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Resistance',
    ),
    'text' => 'Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
While this unit is upgraded, he gains Restore 2. (When this unit attacks, heal 2 damage from your base.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/037.png',
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
  'IC27_040' => 
  array (
    'title' => 'Rose',
    'subtitle' => 'In the Fight',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 6,
    'power' => 6,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Resistance',
    ),
    'text' => 'Shielded (When you play this unit, give a Shield token to her.)
On Attack: You may give a Shield token to another Resistance unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/040.png',
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
  'IC27_045' => 
  array (
    'title' => 'Qi\'ra',
    'subtitle' => 'Survivor',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 1,
    'hp' => 1,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Underworld',
    ),
    'text' => 'When Played: Give 2 Experience tokens to this unit.
When Defeated: Choose a unit. You may give an Experience token to it for each Experience token that was on this unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/045.png',
    'imageUrlBack' => '',
  ),
  'IC27_046' => 
  array (
    'title' => 'Vigilant Defense',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 4,
    'aspect' => 
    array (
      0 => 'Vigilance',
      1 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Tactic',
    ),
    'text' => 'Give a friendly unit +0/+3 and Sentinel for this phase.
Heal 3 damage from a friendly unit or base.
Defeat an enemy unit with 3 or less remaining HP.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/046.png',
    'imageUrlBack' => '',
  ),
  'IC27_049' => 
  array (
    'title' => 'Dexter Jettster',
    'subtitle' => 'Obi-Wannnn!!!',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 2,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Vigilance',
    ),
    'trait' => 
    array (
      0 => 'Fringe',
    ),
    'text' => 'On Attack: You may pay [1 resource]. If you do, look at the top 3 cards of your deck. Put any number of them on the bottom of your deck and the rest on top in any order.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/049.png',
    'imageUrlBack' => '',
  ),
  'IC27_056' => 
  array (
    'title' => 'Allegiant General Pryde',
    'subtitle' => 'Heartless Efficiency',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 1,
    'power' => 2,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'First Order',
      1 => 'Official',
    ),
    'text' => 'When Defeated: Give an Experience token to the friendly unit with the most power. (If multiple units are tied, choose one.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/056.png',
    'imageUrlBack' => '',
  ),
  'IC27_058' => 
  array (
    'title' => 'General Veers',
    'subtitle' => 'Prepared',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 3,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Official',
    ),
    'text' => 'Overwhelm (When attacking an enemy unit, deal excess damage to the opponent\'s base.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/058.png',
    'imageUrlBack' => '',
  ),
  'IC27_062' => 
  array (
    'title' => 'Admiral Motti',
    'subtitle' => 'Conan In Command',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 2,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Official',
    ),
    'text' => 'Hidden (This unit can\'t be attacked if it was played this phase.)
On Attack: Give an Experience token to each other friendly Imperial unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/062.png',
    'imageUrlBack' => '',
  ),
  'IC27_063' => 
  array (
    'title' => 'Kylo\'s TIE Silencer',
    'subtitle' => 'Follow My Lead',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 4,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'First Order',
      1 => 'Vehicle',
      2 => 'Fighter',
    ),
    'text' => 'Restore 1 (When this unit attacks, heal 1 damage from your base.)
When this unit is discarded from your hand: For this phase, you may play it from your discard pile (paying its cost).',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/063.png',
    'imageUrlBack' => '',
  ),
  'IC27_065' => 
  array (
    'title' => 'Darth Maul',
    'subtitle' => 'Mindful Apprentice',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 6,
    'power' => 4,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Sith',
    ),
    'text' => 'Sentinel
Overwhelm
Raid 3',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/065.png',
    'imageUrlBack' => '',
  ),
  'IC27_066' => 
  array (
    'title' => 'Mother Talzin',
    'subtitle' => 'The Time Has Come',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 7,
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
      1 => 'Night',
    ),
    'text' => 'When Played: You may play a Villainy unit that costs 4 or less from your discard pile for free. If it\'s not a Night or Undead unit, deal damage equal to its cost to this unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/066.png',
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
  'IC27_068' => 
  array (
    'title' => 'Devastator',
    'subtitle' => 'Lord Vader\'s Flagship',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Legendary',
    'set' => 'IC27',
    'cost' => 10,
    'power' => 9,
    'hp' => 9,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Imperial',
      1 => 'Vehicle',
      2 => 'Capital Ship',
    ),
    'text' => 'Overwhelm
When Played: Search the top 5 cards of your deck for a Villainy unit that costs 12 or less and play it for free. (Put the other cards on the bottom of your deck in a random order.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/068.png',
    'imageUrlBack' => '',
  ),
  'IC27_070' => 
  array (
    'title' => 'C-3PO',
    'subtitle' => 'Impulse Buy',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 1,
    'power' => 1,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Droid',
    ),
    'text' => 'Action [Exhaust]: Give an Experience token to another unit. Then, if it has 6 or more power, draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/070.png',
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
  'IC27_075' => 
  array (
    'title' => 'Red Leader',
    'subtitle' => 'Wait For My Signal',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 2,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Command',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
      1 => 'Vehicle',
      2 => 'Fighter',
    ),
    'text' => 'Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
Restore 2 (When this unit attacks, heal 2 damage from your base.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/075.png',
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
  'IC27_098' => 
  array (
    'title' => 'Commander Pyre',
    'subtitle' => 'Golden Menace',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 3,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'First Order',
      1 => 'Trooper',
    ),
    'text' => 'Saboteur (When this unit attacks, ignore Sentinel and defeat the defender\'s Shields.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/098.png',
    'imageUrlBack' => '',
  ),
  'IC27_099' => 
  array (
    'title' => 'Eighth Brother',
    'subtitle' => 'Ill-Fated Escape',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 3,
    'hp' => 1,
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
    'text' => 'When Defeated: You may ready an Imperial unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/099.png',
    'imageUrlBack' => '',
  ),
  'IC27_101' => 
  array (
    'title' => 'General Hux',
    'subtitle' => 'Fire Ventral Cannons!',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 3,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'First Order',
      1 => 'Official',
    ),
    'text' => 'Overwhelm (When attacking an enemy unit, deal excess damage to the opponent\'s base.)
Each other friendly {first order} unit gets +1/+0 and gains Overwhelm.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/101.png',
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
  'IC27_107' => 
  array (
    'title' => 'Aurra Sing',
    'subtitle' => 'Getting Even',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 6,
    'power' => 1,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Underworld',
      1 => 'Bounty Hunter',
    ),
    'text' => 'Hidden (This unit can\'t be attacked if she was played this phase.)
Action [Exhaust]: Deal 4 damage to a unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/107.png',
    'imageUrlBack' => '',
  ),
  'IC27_110' => 
  array (
    'title' => 'Supremacy',
    'subtitle' => 'Destroy the Resistance',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Legendary',
    'set' => 'IC27',
    'cost' => 10,
    'power' => 10,
    'hp' => 10,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'First Order',
      1 => 'Vehicle',
      2 => 'Capital Ship',
    ),
    'text' => 'When Played: Deal damage to a base equal to half its remaining HP, rounded down.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/110.png',
    'imageUrlBack' => '',
  ),
  'IC27_115' => 
  array (
    'title' => 'Poe Dameron',
    'subtitle' => 'Hero of the Resistance',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 2,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Resistance',
    ),
    'text' => 'When you play another Resistance card: This unit gets +1/+0 for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/115.png',
    'imageUrlBack' => '',
  ),
  'IC27_116' => 
  array (
    'title' => 'Black One',
    'subtitle' => 'Draw Fire, Twice',
    'type' => 'Unit',
    'arena' => 'Space',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 2,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Resistance',
      1 => 'Vehicle',
      2 => 'Fighter',
    ),
    'text' => 'When Played/When Defeated: Draw a card.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/116.png',
    'imageUrlBack' => '',
  ),
  'IC27_121' => 
  array (
    'title' => 'Kanan Jarrus',
    'subtitle' => 'Sometimes I Hate Being Right',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 6,
    'power' => 8,
    'hp' => 7,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Force',
      1 => 'Jedi',
      2 => 'Rebel',
      3 => 'Spectre',
    ),
    'text' => '',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/121.png',
    'imageUrlBack' => '',
  ),
  'IC27_126' => 
  array (
    'title' => 'Saw Gerrera',
    'subtitle' => 'For the Cause',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 6,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Rebel',
    ),
    'text' => 'On Attack: You may double another friendly unit\'s power for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/126.png',
    'imageUrlBack' => '',
  ),
  'IC27_127' => 
  array (
    'title' => 'Act of Aggression',
    'subtitle' => '',
    'type' => 'Event',
    'arena' => '',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 4,
    'aspect' => 
    array (
      0 => 'Aggression',
      1 => 'Aggression',
    ),
    'trait' => 
    array (
      0 => 'Innate',
    ),
    'text' => 'Draw a card.
You may deal 3 damage to a unit.
Give a friendly unit +3/+0 and Overwhelm for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => false,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/127.png',
    'imageUrlBack' => '',
  ),
  'IC27_142' => 
  array (
    'title' => 'Greedo',
    'subtitle' => 'Big-Timer',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 5,
    'hp' => 3,
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
    'text' => 'Ambush (When you play this unit, he may attack an enemy unit.)
While attacking or defending, this unit deals combat damage after the other attacking or defending unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/142.png',
    'imageUrlBack' => '',
  ),
  'IC27_143' => 
  array (
    'title' => 'Wat Tambor',
    'subtitle' => 'Warped Warbling',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 3,
    'power' => 4,
    'hp' => 2,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
      1 => 'Official',
    ),
    'text' => 'Sentinel (Enemy units in this arena must attack a Sentinel when they attack you.)
When Played: You may attack with a unit.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/143.png',
    'imageUrlBack' => '',
  ),
  'IC27_144' => 
  array (
    'title' => 'Jango Fett',
    'subtitle' => 'Straight Shooter',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 4,
    'hp' => 4,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
      1 => 'Bounty Hunter',
    ),
    'text' => 'Ambush
When Defeated: You may discard a card from your hand. If you do, return an event from your discard pile to your hand.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/144.png',
    'imageUrlBack' => '',
  ),
  'IC27_145' => 
  array (
    'title' => 'Poggle the Lesser',
    'subtitle' => 'Secret Designs',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 3,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Villainy',
    ),
    'trait' => 
    array (
      0 => 'Separatist',
      1 => 'Official',
    ),
    'text' => 'On Attack: You may pay [1 resource] and discard a card from your hand. If you do, the next card you play this phase costs [3 resources] less.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/145.png',
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
  'IC27_153' => 
  array (
    'title' => 'Greef Karga',
    'subtitle' => 'I Celebrate Your Success',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Uncommon',
    'set' => 'IC27',
    'cost' => 2,
    'power' => 0,
    'hp' => 5,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Official',
    ),
    'text' => 'Raid 3 (This unit gets +3/+0 while attacking.)',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/153.png',
    'imageUrlBack' => '',
  ),
  'IC27_157' => 
  array (
    'title' => 'The Mandalorian',
    'subtitle' => 'From the Shadows',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 4,
    'power' => 5,
    'hp' => 3,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Mandalorian',
      1 => 'Bounty Hunter',
    ),
    'text' => 'Shielded (When you play this unit, give a Shield token to him.)
On Attack: If the defending unit is exhausted, it gets -4/-0 for this attack.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/157.png',
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
  'IC27_159' => 
  array (
    'title' => 'Princess Leia',
    'subtitle' => 'Calculated Distraction',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Legendary',
    'set' => 'IC27',
    'cost' => 5,
    'power' => 5,
    'hp' => 5,
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
    'text' => 'When Played/On Attack: Exhaust up to 2 units in the same arena with combined cost 4 or less.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/159.png',
    'imageUrlBack' => '',
  ),
  'IC27_163' => 
  array (
    'title' => 'Bo-Katan Kryze',
    'subtitle' => 'Right Place, Right Time',
    'type' => 'Unit',
    'arena' => 'Ground',
    'rarity' => 'Rare',
    'set' => 'IC27',
    'cost' => 7,
    'power' => 7,
    'hp' => 6,
    'aspect' => 
    array (
      0 => 'Cunning',
      1 => 'Heroism',
    ),
    'trait' => 
    array (
      0 => 'Mandalorian',
    ),
    'text' => 'Shielded (When you play this unit, give a Shield token to her.)
When Played: Give each enemy unit -7/-0 for this phase.',
    'epicAction' => '',
    'deployText' => '',
    'unique' => true,
    'imageUrl' => 'https://swudb.com/cdn-cgi/image/quality=95/images/cards/IC27/163.png',
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
