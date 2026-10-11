<?php
/**
 * Fixtures for cards starting with 'X', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Xukong, Shifted Fates: printed Spellshroud -- an opposing spell cannot target it ---
// Xukong, Shifted Fates (Epmh7VncaR, UNIQUE,PHANTASIA): "Spellshroud" -- the Spellshroud is the card's own,
// UNCONDITIONAL keyword (rules: .claude/GrandArchiveSim/refs/comprehensive-rules.md, Keywords > Spellshroud 1:
// "This can't be targeted by spells"). It was never implemented: HasSpellshroud() (Custom/GameLogic.php)
// recognised printed Spellshroud only through a hard-coded card-ID list and HasKeyword_Spellshroud(), which is
// never generated (Data/ProcessKeywordsGA.php has no Spellshroud keyword, so function_exists is false);
// Epmh7VncaR was in neither, so FilterSpellshroudTargets() kept the card in the target list. Fixed: it is now
// in HasSpellshroud()'s printed-Spellshroud table (after the HasNoAbilities / NO_SPELLSHROUD gates).
// Player 2 controls the holder plus a Ominous Shadow; player 1 plays Disenchant ("Destroy target phantasia", a
// Spell): the target prompt offers ONLY the Ominous Shadow, answering with the holder is refused
// (expectFailure) and the legal answer then resolves.
// Notes carried over from the original definition:
//   - Xukong, Shifted Fates -> p2 field-1
//   - Ominous Shadow -> p2 field-2
//   - Disenchant -> p1 myHand-7
//   - player 1 plays Disenchant
//   - the Ominous Shadow is the legal target
gaScenario('xukong-shifted-fates-printed-spellshroud-spell-cannot-target-it')
    ->deck($mordredDeck)
    ->seed('myField', 'Xukong, Shifted Fates', player: 2)
    ->seed('myField', 'Ominous Shadow', player: 2)
    ->seed('myHand', 'Disenchant')
    ->castSpell('Disenchant', pay: 2)
    ->rejects(1, 'theirField-1', 'Xukong, Shifted Fates (Epmh7VncaR) has spellshroud: not a legal target for the opposing Disenchant')
    ->target('theirField-2')
    ->tested('Xukong, Shifted Fates')
    ->register();
