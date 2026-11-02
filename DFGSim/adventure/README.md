# El’s Invitation — The First Courtyard

Persistent overworld adventure for DFGSim, replacing roguelike progression as the current gameplay direction. Open `http://localhost/TCGEngine/DFGSim/adventure/` on the existing PHP server. The original duel, tutorial, family study, animation galleries, and archived roguelike remain accessible. There is no build step or runtime generation service.

## First playable chapter

A generic human challenger receives a divine tournament advertisement in a stone mailbox. El is a warm, grandfatherly presence heard through the invitation; he does not appear at the beginning. The Sumerian region contains a sanctuary, clay market, canal garden and bridge, cedar grove, training terrace, and temple court. The sanctuary door opens a walkable interior with Aya the keeper, a bed, furniture collision, and a return doorway. The map fills the browser viewport with compact overlay menus, a location plaque, and the current objective. Interaction prompts appear only beside usable objects or characters. Move with WASD/arrows, click/tap destinations or people, or use the mobile directional pad. E/Space and the interaction button talk to adjacent people and examine objects. Overview shows the entire current map; the journal highlights task destinations (or the exit when a task lies outdoors).

The three lesser champions are Iddin (a fictional human disciple), Imdugud, and Huwawa. The Bull of Heaven is the greater champion. Every opponent stands visibly on the map, has a public moveset, and chooses moves secretly during the original simultaneous combat. Defeated opponents remain visibly kneeling with respectful character-specific rematch dialogue. Sparring restores both sides and grants no duplicate unlocks.

The Bull cannot be challenged until the garden has been repaired, Amat has given the Garden Witness, Huwawa and Imdugud have been defeated, and Puzur has witnessed the challenge. This is an authoritative permission gate, not a misleading difficulty wall. Huwawa also refuses a challenge before the canal is repaired. The canal task requires conversations, finding two objects, repairing the sluice, and returning to Amat. Missing requirements are stated explicitly.

Champion victories permanently unlock six signature techniques each (one per color) and a piece of equipment. These are strategic variants drawn from the existing mechanics rather than level/stat upgrades. The starting deck is the exact 36-card PvP repertoire, six per color. Twelve cards (two per color) are marked as shared baseline foundations; their PvP rules are unchanged. Each opponent also uses 36 cards: the shared repertoire with six character variations replacing their source moves. See [CARD-DESIGN.md](CARD-DESIGN.md) for the baseline and signature design. The library permits one to six distinct equipped techniques per color, with free editing outside fights. One per color is retained to keep every color playable; there are no duplicate-card copies. After all four champions, each color has ten choices for six slots. Unlocks do not silently overwrite the equipped deck. Head, Chest, Arms and Legs are separate equipment slots.

Every bout starts at full health with restored match burns, advantages, and armor charges. Defeat/concession returns the challenger inside the sanctuary and preserves all exploration, unlocks, equipment, and seals. No run reset, levels, XP, or increasing base stats. The exact existing fight page, card renderer, previews, burn animations, enemy clips, reaction timing and KO holds are reused through `index.php?adventure=1` and a shared campaign adapter.

Defeating the Bull completes the first region and opens the travel board. The board describes future freely selectable regions and the eventual all-regions tournament requirement. **Only Sumer is implemented in this chapter**; other regions and the divine tournament are visibly marked future content rather than fake playable destinations.

## Riverside Quarter expansion

The courtyard’s canal bridge now has a path into a second full 24×16 outdoor area. The new district has a pottery yard, an outfitter stall, working docks, a teaching garden, three reflection stones, and a one-time travel-pouch discovery. It is available alongside the champion story and does not add requirements to the greater champion gate.

- Every journey starts with 12 copper. Existing saves receive the same pouch once, without resetting their deck, battle, seals, equipment or story. Copper and work progress persist through defeat and browser/PHP sessions.
- Belit sells seven permanent equipment pieces for 16–26 copper across all four slots, using the existing break, burst, barrier, confirm, rally, and block mechanics. Purchases do not auto-equip; owned items cannot be purchased twice. Equipment is never consumed.
- Taram’s clay-and-kiln circuit pays 10 copper, Lugal’s cargo circuit pays 12, and Sila’s herb-garden circuit pays 8. Start work with its giver, perform the steps in order, then return for payment. Chore stations have interaction markers and prompts only for the next step in an active errand; inactive and completed stations remain ordinary scenery. Repeat work requires another complete circuit; talking repeatedly cannot produce money.
- Completing one errand makes Taram’s **Kiln-Hardened Palm** (Red 1/3) and Lugal’s **Dockside Footwork** (Yellow 4/3) available as lessons. Sila’s **Patient Current** (Blue 2/4) requires one garden errand and reading all three reflection stones. Ask the teacher to learn; lessons permanently enter the collection without changing the equipped deck.
- These three cards reuse the compiled abilities and monochrome art of Guarded Strike, Glancing Step and Mirror Step respectively, trading one printed attack for one block (or vice versa). They add situational choices rather than levels or unconditional power upgrades.
- The journal separates champion progression from optional work and lessons. Active errands guide the HUD; cross-area targets highlight the real connecting path. The World panel lists the new district. Existing market, grove and canal music follows its subareas through the shared crossfade transport.

`Exploration.php` owns map/collision, stock prices, work circuits, lessons, and the save migration. `assets/RIVERSIDE-ART-PROMPTS.json` records the built-in imagegen prompts and references for the new map and four NPC sprites, exported as WebP. Interaction/transaction legality is authoritative on the server.

Additional checks:

```powershell
php DFGSim/adventure/exploration-tests.php
node DFGSim/adventure/verify-exploration.mjs
```

The 112 exploration checks cover reachability, ordered work and repeat payouts, purchase prices/ownership, lesson gates, deck legality, save migration, cross-area travel and fight restrictions. Four Chromium/WebKit desktop/mobile browser cases cover all three work/lesson chains, the shop, equipment, navigation and persisted economy.

## Ambient map motion study (withdrawn from live gameplay)

The initial twelve-patch canvas-warp prototype was rejected: motion was barely perceptible and several rectangular crops cut through objects. The live adventure is static again. `ambient-study.php` now contains a water-only compositing pilot with four revised context crops across both maps, visible moving ripple strokes, exact editable-mask views, and patch focus controls. These are local procedural previews, not Kling-generated clips or approved production animation.

`background-motion.js` no longer moves any original image pixels. Water masks exclude non-cyan scenery, erode four pixels away from boundaries/objects, and feather the outer context edge. Only procedural ripples inside those masks change. Candidate periods are distinct and phase-offset. Reduced motion defaults the preview off.

Future generated clips must be checked through the reviewed masks rather than treating the context rectangle as an editable region. Animated foliage, awnings and banners require whole moving silhouettes, motion padding, fixed anchors, and a clean background plate with the original moving object removed. The rejected partial-object crops are not generation-ready. The revised study explains these requirements; no video generation has been submitted.

## Music

The lower-right Music control enables eleven ElevenLabs instrumental themes, with saved volume and on/off preference. Seven area themes follow exploration; four champion themes accompany fights. The persistent audio transport crossfades themes and overlaps loop boundaries. Combat uses the same fight page inside the overworld shell so entering or leaving the arena does not destroy the audio player. The shared sound menu also supplies independently controlled effects for card actions, burns, impacts, advantages, armor, damage marks, and outcomes. The 23-effect pack and its trigger rules are documented in `audio/effects/README.md`. Preview the full set at `soundtrack.php`; see `audio/README.md` for generation provenance and behavior.

## Files and persistence

- `World.php`: authoritative walking/collision, dialogue, task gates, collection/deck limits, equipment, combat transitions, and rematches.
- `api.php`: CSRF and revision checks, private save cookie, locking and atomic durable saves. The cookie persists for one year; clearing it loses this browser’s association with the local save. There are no accounts or cross-device saves yet.
- `.saves/`: gitignored PHP-wrapped save files, denied by Apache `.htaccess`. The PHP wrapper exits if accessed even when override rules are unavailable. Never expose/save the HttpOnly save capability in client code.
- `world.js`, `world.css`, `index.php`: responsive canvas overworld, pathfinding, accessible HTML menus/dialogue, technique library and equipment.
- `technique-art.php`: public, read-only gallery of all 24 champion techniques with their move illustrations and full-art links; browsing grants no unlocks.
- `assets/techniques/`: 24 individual versioned 3:2 WebP move illustrations, used by the shared card renderer in combat, study, and deck editing. Original PNGs are in `assets/`; `TECHNIQUE-ART-PROMPTS.json` records every exact prompt, character reference, and output. Generated with built-in image_gen, then encoded as WebP without cropping.
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

## Generated ambient pilot

`ambient-study.php#generated-pilot` compares one five-second Kling 3.0 water take with the original Riverside map. The single approved job cost 45 credits. Frame registration and the exact grayscale water mask protect static scenery, with a short end blend for looping. The take has insufficient visible water motion and remains study-only; live backgrounds stay static. Input, raw output, registered output, prompt, provenance and registration/mask measurements are retained in `assets/ambient-pilot/`. `ambient-pilot.js` performs the mask composite locally in the review page.

A second isolated-water pilot (`ambient-study.php#water-v2`) uses Kling 4.0, matching endpoint images, five seconds and 720p for a separately approved 50 credits. Its raw take shows ripple movement. The 62×70 map crop was enlarged without redrawing for input; the review restores its map scale with a five-pixel boundary feather. No loop blending or retiming is applied to v2. This remains a study, with raw output, prompt and frame measurements saved alongside v1.

The current study opens with a coherent below-pier section (`ambient-study.php#riverside-section`), salvaged from the existing wide take without new spending. One connected matte covers two complete reed motion envelopes and their revealed backgrounds. Pier/posts and the lower flashing corner remain original. Play/pause, original comparison, exact mask and whole-map controls support review. Water remains mostly static in this take. `tools/build-riverside-section.py` rebuilds the matte and verifies full reed-motion coverage and zero changes outside it. The live game remains unchanged.

The study now defaults to a new Kling 4.0 full-section take (`riverside-section-v3.json`), generated with matching first/last full-crop images for a separately approved 50 credits (balance 231→181). Reeds move in the upper, middle and lower crop, with water ripples around banks/posts. The registered preview preserves original pier pixels and feathers the context perimeter. No endpoint blend is applied; a slight endpoint mismatch remains for review. The original output and earlier Kling 3 salvage are available for comparison. Live backgrounds remain static. `tools/review-riverside-v3.py` reproduces registration and frame measurements.

## Live ambient scenery

The approved Kling 4 riverside section now renders in map coordinates between the background and actors, using the same registered video and protective matte as the approved study. `ambient-player.js` lazily loads only visible approved sections, limits mask compositing to 24 fps, and pauses videos outside the viewport, while the tab is hidden, during menus/loading, and in fights. Menus retain the paused frame. Reduced-motion preference changes restore the static source art immediately; decoding/autoplay/asset failures also fall back to static art. Future sections have independent playback rates and phases. The canal-garden and sanctuary-altar crops/prompts are prepared in `assets/ambient-sections/prepared.json`, pending a new credit allowance.

Ambient verification: `node DFGSim/adventure/verify-ambient.mjs` checks real travel to Riverside, desktop/mobile visibility, playback, menu suspension, preference changes, and failed-video fallback in isolated saves. Six cases pass: Chromium desktop/mobile, Chromium reduced motion, WebKit animated and reduced motion, and an aborted-media fallback. The 185 adventure and 112 exploration checks also pass. Set `ADVENTURE_AMBIENT_CASE` to run one named browser case.

The courtyard canal gardens and sanctuary altar clips are now generated, registered and integrated, using the separately approved 100-credit batch (50 each; balance 181→81). `assets/ambient-sections/prepared.json` records inputs, prompts, job IDs, source URLs, hashes, masks and frame checks. `tools/review-ambient-sections.py` reproduces the processing. Courtyard cropped upper decorations and bridge pixels remain original. Effective loop periods are 5.795 seconds (courtyard, rate .87, phase .19), 6.906 seconds (sanctuary, rate .73, phase .63), and 5.042 seconds (riverside, rate 1, phase .37). There are small endpoint differences in the raw clips; no new end blends are applied. The study retains both new full-section previews and raw files.

Validation after this batch: `verify-ambient-sections.mjs` passes all three live canvas composites in Chromium and WebKit (six cases), verifies their actual playback rates, menu pausing, preference changes and mobile layout using read-only fixtures. The existing six `verify-ambient.mjs` cases also pass, including real Riverside travel and failed-media fallback, alongside all 297 PHP adventure/exploration checks. WebKit requires a tiny in-viewport decoding surface rather than a display:none video for canvas frames; the surface is muted, non-interactive and hidden from accessibility.

## Overworld fighter idles

All four standing champions use their existing transparent idle clips, packed as static WebP frame atlases by `tools/build-overworld-idles.py` and drawn by `overworld-idles.js`. Shared frame bounds preserve the full motion envelope; original portrait proportions and foot anchors preserve map placement. Starting offsets differ per fighter. Clocks freeze during menus, fights, hidden tabs and off-screen periods; reduced motion and missing assets use the original portraits. Defeated champions keep the kneeling poses. No new paid animation generation was needed. `verify-overworld-idles.mjs` checks visible changes for all four, paused frames, reduced-motion loading and kneeling preservation in Chromium and WebKit.

Cedar grove and temple-court crops/prompts are prepared in `assets/ambient-sections/grove-temple-prepared.json`; no jobs have been submitted. The ambient renderer now supports multiple independently timed visible sections per map, ready for those additional courtyard regions.

The cedar grove is now generated and integrated as an additional courtyard overlay, alongside the canal. It uses rate .91, phase .47 and an effective 5.540-second loop. The submitted quote/cap was 50 credits; the observed balance change was 81→46 (35 credits), recorded without assuming a discount explanation. Partial crop-edge palms, shrubs and market context are excluded from its matte. The temple crop remains deferred by the user and was not uploaded or generated. Source, official-download provenance, masks, hashes and frame checks are in `assets/ambient-sections/grove-temple-prepared.json`.

Verified after grove integration: four idle cases (standing/defeated in Chromium and WebKit), six ambient section cases including concurrent grove/canal playback and independently timed overlays, and all 185 adventure + 112 exploration PHP checks.

## Cedar retry and temple court

The user approved two five-second Kling 4 jobs, capped at 50 credits each / 100 total. Both were submitted at displayed quotes of 50. The new cedar raw take still changes tree shapes and invents twigs, so it is retained for comparison and not used directly. `tools/restrain-cedar-motion.py` samples the original artwork through a smoothed optical-flow guide from that retry, attenuated to a two-pixel maximum with endpoint correction. It imports no generated textures. This local derivative now replaces the previous live cedar take; recipe and limits are in `cedar-grove-restrained-v2.json`, with no additional generation cost.

The temple take is now live at rate .79 / phase .71 (6.382-second effective period), with partial terrace and canal-overlap context excluded. Architecture/statues remain aligned; banners, door cloth and palm leaves move. The study shows the live cedar derivative, both raw cedar takes, and the temple source. `grove-temple-retry-v2.json` records the batch, job IDs, hashes and exact prompts. Account balance is abbreviated in the UI, so no exact debit is inferred from it. The processor supports versioned outputs to preserve old takes.

Verified after this update: six Chromium/WebKit live-section cases pass with grove, canal and temple playing together, independent rates, menu suspension and reduced motion; the comparison gallery plays both live clips at the configured rates with no errors or mobile overflow.

## Cedar strength comparison

`ambient-study.php#cedar-strengths` offers 4-, 7- and 10-pixel local motion variants, created without new generation credits by `tools/build-cedar-strength-study.py`. Branch edges receive stronger movement than broad foliage interiors; fixed trunk bases and crop-edge objects are protected. First and last frames equal the original before encoding. The gallery has synchronized restart, pause, original comparison, and a full-map preview for selecting the appropriate strength at game scale. These variants are study-only; the live two-pixel clip is unchanged. Motion limits and protected-trunk checks are recorded in `cedar-strength-study-v3.json`.

`verify-cedar-strengths.mjs` verifies the three comparison videos, synchronized restart/pause, actual temporal changes in the map canvas, unchanged surroundings, strength selection and mobile layout in Chromium and WebKit. The new comparisons use explicit play/pause buttons. This local WebKit runtime still emits a known native-media-control Temporal.Duration error from older gallery players; the verifier reports that baseline separately and rejects all other page errors.

The cedar strength comparison now uses `cedar-strength-study-v4.json`: locally authored overlapping periodic wind fields replace the single Kling gust timeline. Spatially staggered branch phases maintain movement through an eight-second loop. All imagery comes from the original art, fixed trunk bases/cropped objects are protected, and endpoints match before encoding. The generator checks every half-second window across the entire loop (including the seam), both overall foliage and four canopy regions; the browser verifier checks these minimum-activity results as well as actual map playback. This remains study-only, costs no additional credits, and leaves the live two-pixel cedar and approved temple unchanged. The v3 assets preserve the earlier single-gust comparison.

The v4 local continuous-wind experiment was rejected by visual review for psychedelic deformation. Its metadata is marked rejected and the gallery now restores v3 only as an explicitly unapproved reference (single gust / long pause remains). No live assets changed. `cedar-grove-sustained-wind-v5-prompt.txt` prepares a whole-section generation emphasizing wind already in progress, overlapping coherent branch sway, and no settling/frozen ending; no v5 job has been submitted or paid for. Numerical motion checks did not establish visual quality.

The built-in browser reconnected after resetting its session. One newly approved 50-credit Kling 4.0 cedar job was submitted at a verified 50-credit price, using matching grove keyframes. The actual output is available in `ambient-study.php#cedar-sustained`, with source and registered clips, prompt, provenance (`cedar-sustained-v5.json`) and per-half-second canopy activity compared with v2. The model still eases in and settles near the ends, so the result is study-only and not accepted as a solution. No local branch deformation, retiming or loop crossfade was added. The live grove and approved temple remain unchanged. The balance was abbreviated in the UI, so exact debit is not inferred.

At the user's request, the cedar study now loops source frames 42-87 of registered v5 (1.75-3.67 seconds), forward only at a default 40% speed. This removes the long quiet intro/outro and yields a 4.79-second effective cycle. A 30/40/50% speed selector allows pacing review. `tools/trim-cedar-active-loop.py` reproduces the trim and seam measurements; `cedar-active-loop-v6.json` records them. The seam mismatch is smaller than the largest normal source frame step but is not zero; no image warp, reversed footage, interpolation or dissolve is added. No credits spent, and the live grove remains unchanged.

The user rejected the v6 trimmed/slowed loop as well. Its metadata and gallery now clearly mark it as a rejected reference: retiming does not correct the underlying unnatural tree bending and foliage deformation. Do not integrate v4/v5/v6 or continue salvaging this take without a new direction. No live assets changed.

`cedar-rig-study.php` is a standalone one-tree pilot using new built-in image_gen cutout artwork based on the front-left grove tree. A second edit removes an unwanted trunk from one foliage piece. `assets/cedar-rig-v1/prompt.txt` records both prompts, and `parts-source.png` retains the selected generated sheet. The four components are mechanically cropped/registered and saved as lossless WebP with the png-to-webp skill. One fixed trunk and three rigid foliage layers rotate with staggered periodic angles; there is no per-pixel displacement, scale change, new pose generation, or Kling footage. The eight-second preview has play/pause, still comparison, strength, frame scrubbing and small/enlarged views. `tools/build-cedar-rig-study.py` exports 120 transparent PNG frames at 15fps, an animated WebP and contact sheet, with root-stability and periodic-endpoint checks. This redraw is a motion prototype only; it has not replaced original map art.
