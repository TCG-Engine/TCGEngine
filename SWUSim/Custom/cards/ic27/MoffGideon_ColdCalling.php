<?php
// IC27_022
// Cost 5 - Moff Gideon - Cold Calling - [Vigilance,Villainy] - Unit (Ground) 3/6 (unique)
//   Traits: Imperial, Official
// Text: If a friendly unit was defeated this phase, this unit costs [2 resources] less to play.
//
// No registration here — this is a subject-keyed play-cost modifier, and $playCostModifiers is
// initialized in GameLogic.php AFTER cards/_loader.php runs, so a registration made from this file
// would be silently wiped. The closure therefore lives in GameLogic beside SHD_182 Bravado (its
// mirror image, keyed on SWU_ENEMY_DEFEATED) — the same placement LAW_179 and TS26_71 use.
// Tests: SWUSim/Tests/Cases/ic27/MoffGideon_ColdCalling.md
