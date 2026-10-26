# El’s Invitation — The First Courtyard

Persistent overworld adventure for DFGSim, replacing roguelike progression as the current gameplay direction. Open `http://localhost/TCGEngine/DFGSim/adventure/` on the existing PHP server. The original duel, tutorial, family study, animation galleries, and archived roguelike remain accessible. There is no build step or runtime generation service.

## First playable chapter

A generic human challenger receives a divine tournament advertisement in a stone mailbox. El is a warm, grandfatherly presence heard through the invitation; he does not appear at the beginning. The Sumerian region contains a sanctuary, clay market, canal garden and bridge, cedar grove, training terrace, and temple court. The sanctuary door opens a walkable interior with Aya the keeper, a bed, furniture collision, and a return doorway. The map fills the browser viewport with compact overlay menus, a location plaque, and the current objective. Interaction prompts appear only beside usable objects or characters. Move with WASD/arrows, click/tap destinations or people, or use the mobile directional pad. E/Space and the interaction button talk to adjacent people and examine objects. Overview shows the entire current map; the journal highlights task destinations (or the exit when a task lies outdoors).

The three lesser champions are Iddin (a fictional human disciple), Imdugud, and Huwawa. The Bull of Heaven is the greater champion. Every opponent stands visibly on the map, has a public moveset, and chooses moves secretly during the original simultaneous combat. Defeated opponents remain visibly kneeling with respectful character-specific rematch dialogue. Sparring restores both sides and grants no duplicate unlocks.

The Bull cannot be challenged until the garden has been repaired, Amat has given the Garden Witness, Huwawa and Imdugud have been defeated, and Puzur has witnessed the challenge. This is an authoritative permission gate, not a misleading difficulty wall. Huwawa also refuses a challenge before the canal is repaired. The canal task requires conversations, finding two objects, repairing the sluice, and returning to Amat. Missing requirements are stated explicitly.

Champion victories permanently unlock six signature techniques each (one per color) and a piece of equipment. These are strategic variants drawn from the existing mechanics rather than level/stat upgrades. The starting deck is the exact 36-card PvP repertoire, six per color. Twelve cards (two per color) are marked as shared baseline foundations; their PvP rules are unchanged. Each opponent also uses 36 cards: the shared repertoire with six character variations replacing their source moves. See [CARD-DESIGN.md](CARD-DESIGN.md) for the baseline and signature design. The library permits one to six distinct equipped techniques per color, with free editing outside fights. One per color is retained to keep every color playable; there are no duplicate-card copies. After all four champions, each color has ten choices for six slots. Unlocks do not silently overwrite the equipped deck. Head, Chest, Arms and Legs are separate equipment slots.

Every bout starts at full health with restored match burns, advantages, and armor charges. Defeat/concession returns the challenger inside the sanctuary and preserves all exploration, unlocks, equipment, and seals. No run reset, levels, XP, or increasing base stats. The exact existing fight page, card renderer, previews, burn animations, enemy clips, reaction timing and KO holds are reused through `index.php?adventure=1` and a shared campaign adapter.

Defeating the Bull completes the first region and opens the travel board. The board describes future freely selectable regions and the eventual all-regions tournament requirement. **Only Sumer is implemented in this chapter**; other regions and the divine tournament are visibly marked future content rather than fake playable destinations.

## Music

The lower-right Music control enables eleven ElevenLabs instrumental themes, with saved volume and on/off preference. Seven area themes follow exploration; four champion themes accompany fights. The persistent audio transport crossfades themes and overlaps loop boundaries. Combat uses the same fight page inside the overworld shell so entering or leaving the arena does not destroy the audio player. The shared sound menu also supplies independently controlled effects for card actions, burns, impacts, advantages, armor, damage marks, and outcomes. The 23-effect pack and its trigger rules are documented in `audio/effects/README.md`. Preview the full set at `soundtrack.php`; see `audio/README.md` for generation provenance and behavior.

## Files and persistence

- `World.php`: authoritative walking/collision, dialogue, task gates, collection/deck limits, equipment, combat transitions, and rematches.
- `api.php`: CSRF and revision checks, private save cookie, locking and atomic durable saves. The cookie persists for one year; clearing it loses this browser’s association with the local save. There are no accounts or cross-device saves yet.
- `.saves/`: gitignored PHP-wrapped save files, denied by Apache `.htaccess`. The PHP wrapper exits if accessed even when override rules are unavailable. Never expose/save the HttpOnly save capability in client code.
- `world.js`, `world.css`, `index.php`: responsive canvas overworld, pathfinding, accessible HTML menus/dialogue, technique library and equipment.
- `technique-art.php`: public, read-only gallery of all 24 champion techniques with their move illustrations and full-art links; browsing grants no unlocks.
- `assets/techniques/`: 24 individual 1536×1024 WebP move illustrations, used by the shared card renderer in combat, study, and deck editing. Original PNGs are in `assets/`; `TECHNIQUE-ART-PROMPTS.json` records every exact prompt, character reference, and output. Generated with built-in image_gen, then encoded as WebP without cropping.
- `assets/`: built-in imagegen map, human/civilian sprites, and four kneeling poses. Exact prompts and references are recorded in JSON. `huwawa-kneeling-v2.png` is the corrected final pose. `MAP-LAYOUT-NOTES.json` records visual placement guidance; `ADMap()` is authoritative.

Older adventure saves gain the full PvP repertoire automatically, retaining exploration, rewards, and equipment. A customized deck keeps its equipped signatures and fills available slots with PvP cards. An active legacy fight finishes with its original cards before migration.

Saves are separate from classic and roguelike sessions. A fight saves after each action and can be resumed from the overworld. Version mismatches preserve files and return an error rather than silently discarding progress.

## Validation

```powershell
php DFGSim/adventure/tests.php
php DFGSim/adventure/simulate.php
node DFGSim/adventure/verify.mjs
```

The PHP suite covers reachable interactions, collision/teleport checks, mandatory exploration and champion gates, first/repeat rewards, permanent unlocks, six-card limits, slot legality, private combat observations, match resets, and defeat recovery. The simulation uses legal public-observation bot decisions for both sides and exercises all four champion matchups. With the current 36-card decks, seeds 514 and 741 beat the three lesser champions, then lose or draw against the Bull; this is a smoke test, not evidence of balance.

The browser suite exercises desktop/mobile movement, mailbox dialogue, canal tasks, blocked Bull challenges, deck/equipment edits, persistence across PHP sessions, CSRF/stale-revision rejection, original fight integration, and sanctuary recovery. The champion gallery additionally checks all 24 distinct rendered art URLs, image responses, and responsive layout. A read-only completed-world fixture checks kneeling artwork and the six-card editor limit without adding production cheats.

Verified: 184 adventure checks, legal simulations through all four champion matchups, four Chromium/WebKit desktop/mobile adventure cases including both sanctuary doors, and the existing 153 core + 128 roguelike checks. The four existing roguelike browser cases also pass with the shared campaign adapter. Firefox could not launch in this local runtime (`spawn UNKNOWN`), so Firefox browser verification remains unavailable.

## Mythological grounding

This is a warm martial-arts fantasy adaptation, not a historical reconstruction. Imdugud’s lion-headed eagle identity is grounded in the [Met’s Sumerian inlay](https://www.metmuseum.org/art/collection/search/326897). Huwawa’s role as cedar guardian and the [Sumerian Huwawa story](https://etcsl.orinst.ox.ac.uk/section1/tr1815.htm) inform his demand for care rather than conquest. The Bull’s pasture and river-water motifs in the [Sumerian Bull of Heaven story](https://etcsl.orinst.ox.ac.uk/section1/tr1812.htm) inform the garden/witness gate. Iddin, the civilians, seals, dialogue, tournament, and permission ritual are original gameplay fiction.
