# Cloudbreak Stair

This earlier progression prototype is retained for reference. The current gameplay direction is
the persistent overworld adventure at [`../adventure/`](../adventure/), which reuses its equipment
hooks and the shared fight interface. Existing run saves and animation galleries remain intact.

A playable, single-act DFG roguelike. Open `/TCGEngine/DFGSim/roguelike/` on the existing Apache server, or serve the repository with PHP 8.1+ and visit `DFGSim/roguelike/`. No build, database, generated engine files, or external API is needed at runtime.

## The run

Start at 40 life with 24 jade and eighteen basic techniques: three in each of six color hands. Choose one chest piece, then climb a seven-tier branching map: three ordinary fights, with three intervening camp/shop/shrine rooms, then the dragon boss. Connected rooms are in the same or adjacent lane; the final boss is reachable from every lane. The initial map layout is fixed; loot and opponent decisions use a server-held run seed.

Combat retains simultaneous hidden commitments, optional bonus burns, alternating reactions, advantage tokens, and hidden switches. Movesets and remaining cards are public. The enemy commits before the player and uses the same legality, burn, advantage, equipment, and damage rules. Both sides lose on zero life, exhausting their inventory, or switching to an empty hand. A simultaneous loss ends the run.

Life persists between fights. Match burns return; advantages, relocated hands, temporary modifiers, armor charges, and marks reset. Normal victories award 18 jade, a choice of three techniques (or skip), and a choice of three armor pieces (or skip). Cards enter their printed color. The reward pool contains the 36 current core cards plus six confirmed-damage techniques. Unique ownership avoids duplicate card IDs.

Camps recover 12 life or remove a technique; shrines recover 6 or remove one. Shops sell techniques for 20 jade, priced armor, and removal for 16. Permanent removal must leave one card in every color. Death fully resets progression when beginning a new pilgrimage. There are no upgrades carried across runs.

## Armor and damage

Thirteen obtainable pieces occupy Head, Chest, Arms, and Legs slots. New pieces auto-equip; replacements remain in the pack. Equipment changes are allowed on the map or inside service rooms, never during combat.

- Break checkpoints stop a damaging exchange at a specific life value, once per fight. Reaching the checkpoint consumes it, even with no overflow.
- Burst segments require their entire width in one exchange to cross. Damage above the segment applies normally; insufficient remainder stops at the upper edge. Successfully crossed segments stay broken for the fight.
- Barriers absorb damage before life. Other pieces grant conditional attack, block, recovery, victory healing, or confirmed-damage marks.

Armor resolves after normal combat and retaliation. Combat miss/retaliation checks use pre-armor combat damage; on-hit effects, exact-damage conditions, and objectives use final life damage. An unused checkpoint/segment above current life stays inactive until healing reaches it. Consumed protection does not regenerate during a fight. After-damage healing cannot revive a defeated fighter.

A confirmed-damage hit leaves a mark for the following round. A follow-up must connect through block; its damage receives the old mark before armor. A miss expires the mark. New marks replace old ones and cap at four. Echo Bindings and six reward techniques provide this mechanic.

## Opponents and presentation

Huo the Ember Fox uses fast strikes and marks; Shan the Jade Tortoise uses guard and a break checkpoint; Sora the Crane Tengu uses more aggressive palm techniques and shifting routes. Ao the Azure Dragon has stronger techniques and a burst segment. Each has eighteen named moves.

Fights use the existing `DFGSim/index.php?roguelike=1` page, `app.js`, card renderer, arena styles, previews, hand orbs, timeline, card motion, damage effects, and original blue player animation clips. `fight.js` adapts the run API to that interface and adds armor markers, enemy identity, and the return to rewards. The separate run-page combat renderer has been removed. Returning to the run overview preserves an ongoing fight.

The four enemies have new transparent cartoon sprites generated with the built-in image tool, using the existing red fighter as a direct style reference: thick dark outlines, simple shapes, flat color, and minimal detail. The earlier painterly atlas is retired. Each creature now has matching Kling-generated idle, block, light/medium/heavy hit, and knockout animations, converted to transparent WebP with the player pipeline. Knockouts stay down, reduced motion uses stills, and the original player keeps its existing animation clips. See [animation production notes](assets/animations/README.md). See [assets/PROMPTS.md](assets/PROMPTS.md) for each asset and its exact prompt. Starter technique thumbnails reuse existing card art; enemy thumbnails use their creature sprite.

## Implementation and saves

`Content.php` compiles a request-local catalog through the existing DFG parser. `Run.php` owns progression and adds equipment context, stat, damage, and recovery callbacks to the shared combat engine. Shared hooks are optional and do not activate in classic duel requests. The family gallery remains a separate study.

`api.php` is authoritative and stores one run in a separate HttpOnly PHP session. Reload resumes it while that session lasts; clearing cookies or server session expiry loses it. There is no account save/export yet. Hidden commitments and random seeds are removed from responses. Mutation requests require a session token plus matching run ID and revision, preventing stale tabs from overwriting a newer run. Failed actions do not mutate the saved state.

## Verification

From the repository root:

```sh
php DFGSim/roguelike/tests.php
php DFGSim/roguelike/simulate.php
php DevTools/DFGSim/tests.php
node --check DFGSim/roguelike/rogue.js
node DFGSim/roguelike/verify.mjs
node DFGSim/roguelike/verify-animations.mjs
```

The browser verifier uses Playwright installed under `DevTools/ui-harness`; set `PLAYWRIGHT_BROWSERS_PATH` if needed. `ROGUE_URL` overrides the Apache URL; `ROGUE_SCREENSHOTS` saves screenshots. It checks desktop/mobile layout, actual API flow, enemy secrecy, ownership validation, invalid tokens, stale revisions, dialogs, and reload/resume. There are 128 roguelike checks; the existing classic suite has 153 passing checks. Chromium and WebKit pass at 1440 and 390 pixels; Firefox cannot launch in this local runtime (`spawn UNKNOWN`), so Firefox behavior remains unverified here.

The complete-run CLI smoke test drives legal choices using the normal bot policy without fixture wins. Current seeds 514, 741, and 1203 all reach the boss; seed 741 wins with 8 life, and the other two die there. This verifies progression and a reachable victory, not balance quality. The CLI safety caps do not impose a round limit on actual play.
