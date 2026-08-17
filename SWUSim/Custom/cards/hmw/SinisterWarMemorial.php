<?php
// HMW_113
// Cost 2 - Sinister War Memorial - [Command,Villainy] - Upgrade - Trait: Fortification
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains "When a friendly unit is defeated: Heal 1 damage from this base."

// No handler here. Fortify itself needs no code (the keyword makes SWUGetUpgradeValidTargets return
// myBase-0). The granted base ability is a non-interactive observer, so it is wired directly into the
// friendly-defeat collection in GameLogic.php beside LAW_119 Rogue One: if the DEFEATED unit's
// controller has Memorials on their base, heal 1 per copy. Same placement pattern as HMW_206 The
// Tarkin Doctrine, whose granted base ability hooks the play-an-upgrade collection instead.
