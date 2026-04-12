# Grand Archive Unimplemented Cards: First Draft Mechanical Grouping

This is a backlog-shaping document, not a final implementation plan. I grouped cards by the mechanic they appear to exercise in engine terms so we can batch work around shared helpers, replacement effects, counters, copy logic, negate windows, and zone movement.

I used two confidence levels:

- `Reviewed text` means I checked the card text directly through the card editor MCP.
- `Likely` means the grouping is a first-pass placement based on card text sampled from the same cycle, naming, set context, or obvious support pattern and should be re-checked before implementation.

## 4. Copy / impersonation / transform / overwritten identity

These cards likely stress copy effects, persistent overrides, inherited text, or object redefinition.

- `Reviewed text`: Clockwork Amalgam, Aetherial Projection, Jubjub Bird, Mimsy Ghast, Rai, Mana Weaver, Tome of Sacred Lightning, Spirit Blade: Ensoul.
- `Likely`: Shadow's Twin, Grim Pastiche, Gate of Alterity, Clarent, Reimagined, Reciprocity, Dorumegia's Call, Labyrinth, Jeweled Opus, The Looking Glass, Cheshire Cat, Impish Grin, Wonderland's Reign.

Implementation notes:

- Clockwork Amalgam and Aetherial Projection are the most important here because they imply "enter as a copy" plus altered text.
- Tome of Sacred Lightning looks like "enter with abilities of banished regalia," which may want similar override storage to Fracturize-style tech.

## 6. Distortion / Alice / omens / suited / bizarre timing rules

This DTR/PTM-adjacent package looks like a good "special framework" batch because several of these cards care about custom sub-engines rather than simple stat changes.

- `Reviewed text`: Wonderland's Reign, Three of Hearts, Chronowarp, The Looking Glass, Mary Ann, Maladroit Maid, Nocturne's Oblivion, Nightmare Coil.
- `Likely`: Lamentation's Toll, Grande Aiguille, Inert Sword, Frostbind, Chained Charge, Beguiling Coup, Profane Bindings, Candlelight Hourglass, Memento Pocketwatch, Diana, Moonpiercer, Radiant Vega, Excalibur, Reflected Edge, Sword of Shadows, Broken Promises.

Implementation notes:

- `Chronowarp` and `The Looking Glass` probably need dedicated global-state work.
- `Mary Ann` suggests keyword inheritance from omen objects.
- `Wonderland's Reign` and `Three of Hearts` share the Suited/Cardistry economy and should probably be implemented close together.

## 7. Weapon packages / load / "allies attack with this weapon" / regalia combat hacks

This batch groups weapons, weapon-adjacent regalia, and cards that let allies or other objects attack using weapons they normally would not use.

- `Reviewed text`: Poisoned Dagger, Huaji of Heaven's Rise, Spirit Blade: Ensoul, Amorphous Missile, Marksman's Charm, Winged Talaria, Galahad, Court Knight, Arthur, Young Heir.
- `Likely`: Scepter of Awakening, Tideholder Claymore, Varuckan Soulknife, Wildheart Lyre, Spellward Scepter, Frostbitten Etui, Grande Aiguille, Sealed Blade, Life Essence Amulet, Executioner's Spear, Caliburn of Silencing, Inert Sword, Sword of Shadows, Excalibur, Reflected Edge, Flute of Taming, Biding Cinquedea, Malignant Athame.

Implementation notes:

- This is a good batch for load/unload support, alternate attackers, and "unit attacks using weapon" permissions.
- Poisoned Dagger also overlaps with combat replacement effects because it modifies future damage taken.

## 9. Champion / lineage / level / ascendant / alternate survival rules

These cards either are champions themselves or look like they bend champion rules hard enough that they deserve a dedicated tracking group.

- `Reviewed text`: Vanitas, Convergent Ruin, Guo Jia, Chosen Disciple, Guo Jia, Blessed Scion, Guo Jia, Heaven's Favored, Arisanna, Lucent Arbiter, Jin, Fate Defiant, Nameless Champion, Prismatic Spirit, Rai, Mana Weaver, Seize Fate.
- `Implemented`: Sacramental Rite, Apotheosis Rite.
- `Likely`: Vanitas, Dominus Rex, Jin, Undying Resolve, Diana, Moonpiercer, Materialize the Soul, Coronal of Rejuvenation, Prismatic Perseverance, Nameless Champion, Camelot, Impenetrable.

Implementation notes:

- `Seize Fate` is probably one of the strangest backlog cards because it rewrites how champion damage is handled for the rest of the game.
- `Nameless Champion` implies a non-standard leveling path via counters instead of lineage.
- `Sacramental Rite` and `Apotheosis Rite` look like champion type mutation and card access.

## 10. Element enabling / global rule rewriting / system-wide text

These are probably safer to batch separately because they can affect engine assumptions globally.

- `Reviewed text`: Imperial Seal, Nullifying Lantern, Tricastles of Lucenia, Chronowarp, Prismatic Perseverance.
- `Implemented`: Gaia's Blessing.
- `Likely`: Gate of Alterity, Aetherial Projection, Prismatic Codex, Coronal of Rejuvenation, The Looking Glass.

Implementation notes:

- `Nullifying Lantern` is the obvious graveyard element override case.
- `Tricastles of Lucenia` and `Chronowarp` both look like "global rules text" rather than ordinary card effects.

## 11. Likely support cards for suppress / return / displacement / state-reset play

These cards seem to sit near suppression, bouncing, or forcing an object through a temporary state change.

- `Reviewed text`: Cyclonic Strike, Winds of Destiny, Dream Fairy.
- `Likely`: Shadow's Twin, Orchestrated Seizure, Regal Expulsion, Counter Interference, Phantom Veil, Flash Freeze, Frozen Dismissal.

Implementation notes:

- Suppress already exists in parts of the engine, so this is probably a "finish the mechanic family" batch rather than net-new work.

## 12. Current "needs closer text review" bucket

These are the cards I would keep in a second-pass review pile before assigning implementation order. They still fit the document because they likely belong to one of the groups above, but I do not want to overstate certainty yet.

- `Likely`: Servile Possessions, Strengthen the Bonds, Materialize the Soul, Luminous Quartz, Kraal, Stonescale Tyrant, Polaris, Twinkling Cauldron, Wildheart Lyre, Clash of Fates, Overpowering Defense, Provoke Obstinance, Grande Aiguille, Shadow's Twin, Frozen Dismissal, Caliburn of Silencing, Frostbitten Etui, Counter Interference, Spirit Blade: Ensoul, Quicksilver Grail, Suffocating Ash, Aetherial Projection, Incapacitate, Phantom Veil, Prismatic Spirit, Flash Freeze, Horticounter, The Constellatory Spire, Devoted Bloomweaver, Dissuading Halt, Seed of Nature, Mortal Ambition, Sink the Mind, Spectral Beacon, Synth Disrupter, Heighten Spellcraft, Dazzling Courtesan.

## 13. Suggested first implementation batches

If we start turning this document into work packets, I would do it in this order:

1. Negate / tax / reaction suite.
2. Quest / Fatestone / Guo Jia counter cards.
3. Charge / age / threshold artifacts.
4. Weapon package and "attack using this weapon" cards.
5. Copy / transform / identity rewrite cards.
6. Global-rule cards like Chronowarp, Nullifying Lantern, and Tricastles.

That order should give us the most engine leverage early while keeping the weirdest global-state cards until we have the surrounding support pieces in place.
