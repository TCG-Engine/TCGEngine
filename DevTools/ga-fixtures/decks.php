<?php
/**
 * Named deck templates for the scenario builder. {MAIN} is replaced with the card under test (the first
 * main-deck line). Used through GaScenario::deckTemplate() / standardDeck().
 */
global $gaDeckTemplates;
$gaDeckTemplates = [
    'standard' => "# Material\n1 Spirit of Fire\n1 Lorraine, Wandering Warrior\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n4 Dungeon Guide\n4 Fairy Whispers\n4 Fluffy Shopkeep",
    // The standard deck with the fast Fairy Whispers swapped for Dungeon Guide: with no fast card in either opening hand no priority window opens at
    // phase changes, so turn handovers need no extra passes (see reachTurnThree).
    'quiet' => "# Material\n1 Spirit of Fire\n1 Lorraine, Wandering Warrior\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n8 Dungeon Guide\n4 Fluffy Shopkeep",
    'lorraine-windslice' => "# Material\n1 Spirit of Fire\n1 Lorraine, Wandering Warrior\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n4 Fairy Whispers\n4 Fluffy Shopkeep\n4 Windslice",
    'clarent-windslice' => "# Material\n1 Spirit of Fire\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n4 Fairy Whispers\n4 Fluffy Shopkeep\n4 Windslice",
    'rai-windslice' => "# Material\n1 Spirit of Fire\n1 Rai, Archmage\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n4 Fairy Whispers\n4 Fluffy Shopkeep\n4 Windslice",
    'lorraine-fluffy-shopkeep' => "# Material\n1 Spirit of Wind\n1 Lorraine, Wandering Warrior\n1 Lorraine, Blademaster\n1 Lorraine, Spirit Ruler\n1 Charm of Anticipation\n1 Drawn Blade\n1 Equinox Hour\n1 Safeguard Amulet\n1 Sword of Seeking\n1 Tariff Ring\n1 Prismatic Edge\n1 Quietus Blade\n# Main\n4 {MAIN}\n4 Fairy Whispers\n4 Fluffy Shopkeep",
    'lorraine-fluffy-shopkeep-2' => "# Material\n1 Spirit of Fire\n1 Lorraine, Wandering Warrior\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n4 Fairy Whispers\n4 Fluffy Shopkeep",
    'clarent-fluffy-shopkeep' => "# Material\n1 Spirit of Fire\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n4 Fluffy Shopkeep\n4 Dungeon Guide\n4 Fluffy Shopkeep",
    'diao-awakened-deacon' => "# Material\n1 Spirit of Water\n1 Diao Chan, Enchantress\n1 Backup Charger\n1 Cleric Robes\n1 Scepter of Fascination\n# Main\n4 {MAIN}\n4 Dungeon Guide\n4 Fluffy Shopkeep\n4 Frostbinder Apostle\n4 Awakened Deacon",
    'spirit-dungeon-guide' => "# Material\n1 Spirit of Water\n# Main\n4 {MAIN}\n10 Dungeon Guide",
    'silvie-windslice' => "# Material\n1 Spirit of Fire\n1 Silvie, With the Pack\n1 Clarent, Sword of Peace\n1 Backup Charger\n1 Purifying Thurible\n# Main\n4 {MAIN}\n4 Fairy Whispers\n4 Fluffy Shopkeep\n4 Windslice",
];
