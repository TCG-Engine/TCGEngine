# HellbreakSim Automated Mechanics Plan

This plan implements the universal quick-start rules first. Individual card text and keywords are a later layer built on stable hooks.

## Current baseline and main gap

Milestones 1 through 7 are complete. HellbreakSim has a lobby, game-session authentication, shared assets with HellbreakDeck, an authoritative generated gamestate, saved-deck setup validation/loading, random initial initiative, simultaneous secret location reveal, four-card mulligans, private eight-card health stacks, Feeding, an alternating Horror action loop, universal card play, Pass and Slumber, combat, schemes, contested location control, point-by-point Health damage and response windows, victory, failed-draw damage, the complete Refresh-to-Feeding round loop, and a responsive production table with public match history. Reviewed card content remains for Milestone 8.

The imported cache contains 285 named card identities with collector number, type, rarity, aspect, blood cost, loyalty, franchise/IP provenance, and available image references. The checklist contains 170 additional unnamed set-number placeholders. The workbook itself contains no combat, health, resource-bar, scheme-bar, trait, or rules-text values. A reviewed supplemental source now covers all 147 playable image-backed cards. The review pipeline covers all 148 imported front images: 147 are authoritative transcriptions and one multi-card convention poster is rejected. Authoritative retail starter lists still require review before the fixture decks can be treated as product deck lists.

### Completed Milestone 1 artifacts

- `Schemas/HellbreakSim/GameSchema.txt` is the authoritative state model.
- `Schemas/HellbreakSim/TurnSchema.txt` defines setup, Feeding, Horror, and Refresh states.
- `HellbreakSim/Fixtures/QuickStartFixtures.php` supplies deterministic engine-only decks and structured sample cards.
- `HellbreakSim/CreateGame.php` initializes the fixture foundation without prematurely resolving setup.
- `DevTools/tdd-regression/test_hellbreak_milestone1.php` exercises the generated accessors and controller.

## Architecture decisions

### 1. Model shared locations explicitly

Replace the two per-player `Location` assumptions with two shared location slots. Each location object needs:

- `CardID`
- original owner
- current controller
- malice threshold
- player 1 malice
- player 2 malice
- stable unique ID

Orientation is a presentation of `Controller`, not authoritative state. Location control and malice must be server-owned.

### 2. Use one character collection per player

Represent minions in a field zone with `Location`, `Status`, `Damage`, `Owner`, `Controller`, `UniqueID`, turn markers, and counters. Keep each monster in its own zone with `Side`, `Status`, `Owner`, `Controller`, and unique ID. This avoids duplicating combat logic across left/right zones while the custom layout can still render characters beneath their locations.

Suggested state values:

- `Status`: ready or exhausted.
- `Side`: lurking or unleashed.
- `Location`: first or second shared location.

### 3. Keep health cards private but health public

Keep the identities in `HealthStack` private. Publish remaining monster health and whether the top card is vertical. On the second damage, reveal the top card, offer its Health ability, and move it face up to the crypt.

### 4. Separate player pools from location malice

`Blood` and pool `Malice` belong to a player. Location malice belongs to a player/location pair and must never be paid as pool malice.

### 5. Treat the supply as unbounded rules state

The quick-start guide does not impose a meaningful exhaustion rule for physical counters. The simulator should add and remove numeric counters without tracking a finite shared inventory.

## State machine

Generate a real `TurnSchema.txt` and regenerate `TurnController.php`. Use coarse phases and Decision Queue interactions for substeps.

| State | Active work | Exit condition |
| --- | --- | --- |
| `SETUP_LOCATION` | Both players secretly commit a location | Both commitments exist |
| `SETUP_MULLIGAN` | Both choose zero to four cards | Both mulligans resolve |
| `FEED_COLLECT` | Compute all vault resources | Automatic |
| `FEED_BID` | Both secretly commit a hand card or decline | Both commitments exist |
| `FEED_RESOLVE` | Reveal bids, move bids to vault, determine bid winner, choose initiative holder | Choice resolves |
| `HORROR` | Alternating single actions | Pass followed by Pass or Slumber |
| `REFRESH_READY` | Ready all controlled cards simultaneously | Automatic |
| `REFRESH_FLIP` | Initiative-order optional monster flips | Both choices resolve |
| `REFRESH_HAND` | Exact excess-card discards | Both hands are at most six |

The Decision Queue must block automatic phase movement whenever a required choice remains unresolved.

## Simultaneous hidden choices

Location selection and initiative bidding need a commit/reveal primitive rather than two visibly sequential prompts.

Implement server-side pending commitments keyed by round and player:

1. Each player sees only their own chooser.
2. Submitting stores a stable card ID or pass sentinel without moving or revealing it.
3. Neither commitment is exposed until both are locked.
4. The resolver revalidates both committed objects against current zones.
5. The resolver reveals and applies both atomically.

Do not store hidden choices in public `PhaseParameters` or public logs.

## Horror-phase priority

Track these server values:

- current action player
- previous action was pass-like
- player who Slumbered, if any
- whether Slumber has already been taken this round
- action sequence number

After a normal action, clear the pass-like flag and give priority to the opponent. After Pass, set the flag and give priority to the opponent. If the next action is Pass or Slumber, end Horror. After Slumber, lock that player out of further Horror actions and auto-pass them whenever priority returns. A normal opponent action after the first Pass prevents the phase from ending.

Use the established SWUSim alternating-action pattern as the engine reference, but keep Hellbreak's distinct consecutive-pass and Slumber behavior in Hellbreak custom logic.

## Core rules services

Implement these as game-level helpers under `HellbreakSim/Custom/`, independent of individual card scripts:

- `HellbreakVaultResources(player)` - count blood, malice, draw, and aspect icons.
- `HellbreakCollectResources(player)` - apply a vault snapshot once per Feeding phase.
- `HellbreakCanPayLoyalty(player, cardID)` - compare per-aspect counts, not just one scalar.
- `HellbreakCanPlayCard(player, mzID)` and `HellbreakPlayCard(...)` - legality, costs, destination, side restriction, and Played hook.
- `HellbreakReady/Exhaust(...)` - canonical state changes.
- `HellbreakDealMinionDamage(...)` and `HellbreakCheckLethal(...)` - persistent damage and kill handling.
- `HellbreakDealMonsterDamage(...)` - point-by-point health-stack resolution and Health-ability windows.
- `HellbreakAssignIndirectDamage(...)` - assignment with per-minion remaining-health caps.
- `HellbreakResolveAttack(...)` - attacker, target, optional defender, simultaneous exchange, and monster exception.
- `HellbreakResolveScheme(...)` - ordered Prowl, Foresee, and Haunt icons.
- `HellbreakAddLocationMalice(...)` and `HellbreakTakeLocation(...)` - threshold checks, both-row reset, controller change, reward, and hook.
- `HellbreakDraw(...)` - normal draw or two monster damage from an empty deck.
- `HellbreakEnforceUnique(...)` - choose and kill one duplicate when necessary.
- `HellbreakEndGame(...)` - authoritative winner state and input lock.

All actions must revalidate ownership, controller, readiness, location, costs, and current priority on the server.

## Decision Queue and UI interactions

Reuse supported interactions where their return shape fits:

- Mulligan: `MZMultiChoose`, zero through hand size.
- Minion placement or monster attack/scheme location: `Modal` or a location-card chooser.
- Attacker and target: `MZChoose`.
- Optional defender: `MZMayChoose`.
- Foresee ordering: `Rearrange`.
- Hand-limit cleanup: `MZMultiChoose` for exactly the excess.
- Optional monster flip and optional Health ability: `YesNo` or `Modal`.

Indirect damage resembles `MZSplitAssign`, but the existing control must be verified or extended to support a maximum per target equal to that minion's remaining health. The monster remains a legal sink unless another rule prevents it.

Attack resolution should be one server transaction after all choices are committed. Never apply attacker damage before defender damage, because combat damage is simultaneous.

## Generic action surface

The Horror UI should expose only currently legal actions:

- Playable hand cards.
- Ready characters that have at least one legal attack target.
- Ready characters that can scheme.
- Usable Action abilities.
- Slumber only when no player has used it this round and the viewer is not already locked out.
- Pass whenever the viewer has priority and no mandatory decision is pending.

The server remains authoritative even if the browser shows a stale button.

## Card-rule hook surface

Before card implementation, define stable dispatch points for:

- resources collected
- initiative bid revealed
- initiative won and assigned
- card played / minion entered
- action ability used
- attack declared / target declared / defender declared
- combat damage calculation and completion
- scheme started and each scheme icon
- location control taken
- damage would be dealt / damage dealt
- minion killed / monster health card revealed
- monster flipped
- refresh and round boundaries

Card scripts should modify or respond to these hooks. They should not duplicate phase transitions, core zone moves, or generic damage rules.

## Data enrichment track

Extend the import schema to represent repeated icons rather than ambiguous strings:

- combat and health
- loyalty as per-aspect counts
- resource bar as blood, malice, draw, and aspect-icon counts
- ordered scheme icons with values
- traits as an array
- unique flag
- monster side and side-restricted ability markers
- normalized ability entries and reminder text

Do not infer authoritative rules solely from card images in production. Use image transcription as a review aid, then store reviewed structured values with provenance. The starter decks and their two-location selections also need authoritative deck lists.

## Delivery sequence

### Milestone 1: schema and deterministic fixtures

- [x] Add the shared location, character, monster, health, phase, commitment, and winner state.
- [x] Regenerate engine files.
- [x] Add small deterministic Dracula/Jaws fixtures with enough structured values to exercise every universal mechanic.
- [x] Add a generated-code regression covering initialization and phase transitions.

### Milestone 2: setup

- [x] Deck validation and loading.
- [x] Secret location commit/reveal.
- [x] Random initial initiative.
- [x] Four-card hands, simultaneous mulligans, and eight-card health stacks.

### Milestone 3: Feeding

- [x] Vault resource calculation.
- [x] Simultaneous initiative bids.
- [x] Tie rule, winner's initiative-holder choice, and bid-to-vault movement.

### Milestone 4: Horror action loop

- [x] Alternating priority.
- [x] Play minion, asset, and event.
- [x] Pass and Slumber.
- [x] Server-generated legal-action metadata.

### Milestone 5: combat, scheme, and locations

- [x] Attacker, location, target, and optional defender flow.
- [x] Simultaneous combat damage and lethal cleanup.
- [x] Prowl assignment, Foresee rearrangement, and Haunt.
- [x] Location thresholds, control changes, rewards, and repeated control.

### Milestone 6: health and Refresh

- [x] Point-by-point monster damage and resumable Health windows.
- [x] Empty-stack victory after the final Health response.
- [x] Simultaneous ready, initiative-order flips, and exact hand-limit discard.
- [x] Empty-deck draw replacement with two monster damage per failed draw.
- [x] Focused regression coverage for damage continuations and the new-round loop.

### Milestone 7: production UI

- [x] Shared two-location battlefield with persistent rendered zone slots.
- [x] Correct ready/exhausted rotation, damage/location counters, and monster-side display.
- [x] Private hand, Health, and Vault information with public pile and resource counts.
- [x] Responsive table chrome, phase/priority status, choice prompts, public history, and end-game state.
- [x] Desktop live-match verification and focused UI regression coverage.
- [x] Authored Learn to Play launch with deterministic setup, contextual phase guidance, and an explicitly passive tutorial opponent.
- [x] Board-first Horror controls: click hand cards to play, click ready characters for local attack/scheme/ability actions, and reserve the compact toolbar for Pass and Slumber.

### Milestone 8: card content

- [x] Populate reviewed structured data for all 147 playable image-backed cards.
- [ ] Replace deterministic fixture lists with reviewed retail starter lists when authoritative lists are available.
- [x] Establish the generator-backed card vocabulary: named lifecycle macros, zone-active listeners, and scalar modifiers for combat, health, schemes, play cost, location thresholds, and damage.
- [x] Route card play through a queued `Played` continuation so interactive card macros finish before priority passes.
- [x] Prove emergent card interaction with Bloodsucking Bat (`Played`), Countess Zaleska (`PlayCostModifier`), and Kennelmaster (a `Played` listener observing allied Dogs).
- [x] Expose generated `ActivateAbility` macros through the Horror action menu with card prereqs and continuation-safe interactive choices.
- [x] Add shared local-minion targeting, remaining-health, exhaust, kill, and `MinionKilled` dispatch helpers; prove the path with Martin Brody's Action ability.
- [x] Complete Martin Brody with a trait-aware local `Played` macro and suppress the universal malice-ready prompt when card text already readied the entering minion.
- [x] Enforce duplicate unique characters and assets through an immediate, continuation-safe kill choice, including canonical identity across alternate printings.
- [x] Normalize `DamageDealt` payloads across attacks, retaliation, effects, and monster damage with stable cross-player source identity.
- [x] Prove combat listener composition with Renfield's Creature-damage blood gain and the lethal-damage abilities of Deathstalker Scorpion and Roughtail Stingray.
- [x] Add a two-player combat continuation barrier around attack, target, defender, damage, kill, and completion macro timing.
- [x] Prove interactive `AttackDeclared` card automation with Jaws, Ravager from the Deep while keeping its unconfirmed Bloodlust keyword explicitly incomplete.
- [x] Add a two-player scheme continuation barrier around `SchemeStarted`, each `SchemeIcon`, and `LocationTaken`, including opposing-player interactive listeners.
- [x] Prove interactive location automation with Lucy Weston's optional local-minion damage trigger; keep her unconfirmed Fearsome keyword explicitly incomplete.
- [x] Implement Amity Harbor's `LocationTaken` monster choice and route its damage through the resumable Health-stack pipeline before scheme completion.
- [x] Add a two-player Feeding/Refresh continuation barrier around resource collection, bid reveal, initiative assignment, ready triggers, and round end.
- [x] Implement Carfax Abbey's optional initiative-win indirect damage and Underground Tomb's five/ten-card Refresh rewards through generated location listeners.
- [x] Add two-player continuation barriers for `MonsterFlipped`, `MonsterHealthRevealed`, and `HealthAbilityUsed`, including excess-damage resumption after interactive abilities.
- [x] Implement Vampire's Coffin's optional monster-flip blood trigger; keep its separate Jumpscare mode explicitly incomplete.
- [x] Derive per-card Jumpscare eligibility and malice costs from reviewed rules text while retaining the deterministic Orca fixture as a compatibility test.
- [x] Route generated `DamageModifier` deltas through minion and monster damage before `DamageDealt` and Health-stack processing.
- [x] Add phase-scoped, consumable next-monster-damage prevention for confirmed prevention effects.
- [x] Implement Deputy Hendricks' live-board combat modifier and Barracuda's optional flip-triggered character damage.
- [x] Implement the conservative quick-start Jumpscare window: only the newly revealed Health card is eligible, its listed malice is paid, it resolves before normal discard/excess damage, and explicit destination text overrides discard.
- [x] Implement generated destination Jumpscares for all reviewed "add this card to your hand" printings and the confirmed asset "play this card for 0 blood" cases (Vampire's Coffin and Scarecrow).
- [x] Add the supplied GAMA Dracula and Jaws demo lists as selectable, exact-count starter decks, while retaining the authored tutorial fixtures separately.
- [x] Implement the first GAMA automation pass through generated macros: Ancient Wisdom, Swarm of Rats, Aleera, Verona, Count Alucard's confirmed text, North Beach, Castle Dracula, Dracula, and Jaws.
- [x] Implement the second GAMA automation pass: Drain Life and Narrow Escape (including play-from-Health Jumpscares), Coven Feast, Mina Harker, Carpathian Wildcat's confirmed Jumpscare, Ferocious Wolfpack's confirmed trigger, Carriage Driver, Shark Spotter, Veteran Harpooner, Larry Vaughn, Ravenous Predator, Rogue Shark and Threat From Below combat bonuses, A Panic on Our Hands, Giant Octopus, and Killer Whale's confirmed text.
- [x] Implement the supplied keyword definitions for Bloodlust, Fearsome, Fierce, First Strike, Guardian, Malicious, Overkill, Stealth, and Terrify as shared rules mechanics, including side-aware monster keywords and mixed blood/malice payment choice.
- [ ] Resolve the missing Marishka source image/rules and finish GAMA abilities that need additional multi-player sequencing, notably Shark in the Pond's opponent "prank" response and Orca's nested attack action.
- [ ] Extend Jumpscares with response/cancellation ordering only when comprehensive rules or an official ruling defines that timing.
- [ ] Connect other remaining lifecycle macros to card-specific resolution points as their cards are implemented.
- [x] Implement confirmed keyword definitions against the macro surface and universal play/combat hooks.
- [ ] Implement the remaining card abilities and add card-by-card regression fixtures.

## Required automated tests

At minimum, cover:

- Mulligan returns to four without shuffling bottomed cards back into the deck.
- Resource collection counts every vault card exactly once.
- Initiative: higher bid, tied bid, no-bid tie, winner assigning either player, and both bid cards entering vaults.
- Loyalty checks repeated icons independently for all five aspects.
- Minion enters exhausted and may pay exactly one malice to ready.
- Side-restricted events/actions reject the wrong monster side.
- Pass, action, pass does not end Horror; Pass then Pass does; Pass then Slumber does.
- Slumber is globally limited to one player per round and locks that player out.
- Minion attacks cannot cross locations; monsters can use either location.
- Defender is optional, must be ready and local, and may equal the target.
- Defender combat is simultaneous; attacking monsters ignore defender retaliation.
- Indirect assignment obeys minion remaining-health caps.
- Foresee preserves card count and the submitted top/bottom order.
- Haunt triggers control immediately, clears both rows, and grants rewards once.
- Monster damage rotates, reveals, offers the Health window, discards, and continues point by point.
- Empty health stack ends the game; empty deck replaces each failed draw with two monster damage.
- Refresh order is ready, optional flips in initiative order, then hand-limit cleanup.
- Duplicate unique name/subtitle forces one controlled copy to be killed.

## Definition of a rules-complete core

The core is ready for card implementation when two fixture decks can play from setup to victory with no manual gamestate edits, every universal action is server-validated, both players see only permitted hidden information, refresh loops into the next round, and the universal-rules tests pass.
