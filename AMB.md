# AMB — Unimplemented Cards Analysis

### Hard

Permanent rule replacements, copy mechanics injected into existing intent, per-attack dynamic payment gates, damage-doubling replacement hooks, face-down state in the banishment zone, or injecting runtime On-Enter effects onto specific card instances.

| Card | ID | Effect Summary |
|------|----|----------------|
| Slice and Dice | `3jg01o26b4` | NORM Assassin Dagger Attack 3; Prepare 3; On Hit if prepared: the attacker declares an additional attack and a copy of CARDNAME is placed in that attacker's intent — the copy is non-prepared and gets +3 POWER (requires creating and injecting a modified card copy directly into an in-progress intent) |
| Seize Fate | `l61ubi93jx` | Exia Warrior Skill; Jin Bonus: costs 2 less; for the rest of the game: if your champion would take damage, remove that many damage counters from them instead; if damage counters reach zero this way, banish your champion (permanent replacement of the standard champion damage model — rewrites champion damage resolution for the duration of the game) |
| Oppressive Presence | `j9hjjvkyyr` | Fire Warrior Skill; activate only during an opponent's recollection phase; until EOT: each time a player would declare an attack with an ally, they must first pay (X) where X = the highest POWER among fire element allies you control (dynamic per-attack-declaration payment gate that recomputes X each trigger) |
| Proof of Life | `mes4idoihs` | Exia Warrior Skill; the next time your champion would take damage this turn, double that damage; Damage 40+: (2), banish this from GY → wake up your champion (one-shot damage-doubling replacement hook on the next champion damage event) |
| Orb of Sealing | `mekutzp19y` | NORM Regalia Bauble; REST: put a seal counter on up to two target face-up non-champion non-regalia cards in a single banishment and turn them face down; On Leave: remove all seal counters from all cards in each banishment, turn affected cards face up (introduces face-down objects in the banishment zone) |
| Vengeful Gust | `q4dvnn3zp1` | Wind Mage Spell; Suppress target ally; Level 3+: the next time that specific suppressed card would enter the field this turn, it enters with an injected On-Enter effect: "deal 4 damage to your champion" (requires attaching a runtime On-Enter effect to a specific card instance that persists through the suppression re-entry window) |
| Yudi, Gossamer Jade | `l94wp7qjwb` | Unique Tera Cleric/Mage Phantasia; whenever you empower an amount, if that amount is greater than the current number of root counters on CARDNAME, put a root counter on it (tracks the running maximum single-empower value); Class Bonus: players cannot declare attacks with non-tera element units unless they pay (X) per declaration, where X = root counter count on CARDNAME |
| Desperate Cavalier | `slmer06rku` | Exia Warrior Ally; Class Bonus On Attack: if your influence is four or less, banish the top two cards of your deck; you may activate those specific banished cards, with an additional cost of 2 unpreventable damage to your champion per activation (temporary tagged activation window for two specific banished card objects) |
