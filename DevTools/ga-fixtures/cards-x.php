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
gaSpellshroudCase('xukong-shifted-fates-printed-spellshroud-spell-cannot-target-it', ['Xukong, Shifted Fates'], 'Disenchant')
    ->register();
