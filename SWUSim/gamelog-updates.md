# Game log updates — open questions & follow-ups

Collected during the autonomous game-log gap pass (2026-09-11). Everything below is something I did NOT
decide alone: a design choice, an ambiguity, a possible rules bug outside the log's scope, or a coverage gap.
Suite: 11572 → **11675 passed, 0 failed**. Integration: the same 7 pre-existing failures as before the pass
(GA / Hellbreak generated files, SWUDeck format column + allow-list, lobby auth) — none touched here.

## Open questions

1. ~~**On Attack effects (and payments) print ABOVE their own ATTACK / Action line.**~~ **RESOLVED — leave
   it (user, 2026-09-11):** the order is accurate per the CR — On Attack abilities resolve before combat
   damage, and the ATTACK line is the combat-damage summary. No change.
2. ~~**Generic "ability had no effect" line?**~~ **RESOLVED — dispatcher check (user, 2026-09-11), DONE.**
   "P1's [[X]] had no effect" when an ability closure changes NOTHING. The test compares the whole serialized
   gamestate (the undo serializer: zones, decision queues, SWUVars, the log) before/after the CLOSURE —
   not the whole dispatcher, which is reached for cards with no closure of that kind (JTL_221's first-draft
   false line). Wired into OnWhenPlayed / OnWhenDefeated / OnDefenseTrigger / OnAttackEndTrigger /
   OnAttackTrigger / OnAttackFromUpgradeTrigger / OnWhenPlayedAsUpgrade, the event dispatch in ActivateCard,
   and DispatchTrigger's card-keyed reactive cases. Keyword triggers (Shielded/Ambush/Support) excluded.
   Cost: ~0.5 s over the whole suite. Tests: `core/GameLog_NoEffect.md`.
   **Audit:** 891 "had no effect" lines across ~559 cards in the suite. A random 25-card sample was almost
   all genuine ("NoBounty_NoExp", "NoEnemyUnit_NoEffect", "EmptyDeckNoEffect"…). **Known false-positive
   class:** a trigger the engine BAGS even though its trigger condition can't hold, leaving the closure to
   bail — e.g. HMW_014 Wicket's FRONT reaction still dispatches after he deploys ("P1's Wicket had no
   effect" for an ability that shouldn't exist). The line now exposes those over-triggers; each is a per-card
   fix (bag the trigger only when its condition holds). Borderline: deployed SOR_009 Leia logs it after a
   solo attack (accurate, but frequent). Regenerate the full list to triage with
   `php -d xdebug.mode=off -d memory_limit=2G SWUSim/DevTools/scan-noeffect.php` (in the container).
3. ~~**Several undos in a row leave ONE "undid" line.**~~ **RESOLVED — keep undone actions visible (user,
   2026-09-11), DONE.** `SWULogCarryUndone`: before a restore the log is kept; the lines the restore erases
   come back marked "(undone)" (type `UNDONE`) with their ORIGINAL visibility — a private "You drew X" stays
   the drawer's alone — then the undo line. This player's consecutive undos fold into one "P1 undid their
   last 3 actions". A restore that is not a prefix of the old log (a divergent branch) carries nothing;
   bookmark loads carry nothing. Tests: `core/GameLog_Undo.md`.
   *Optional follow-up (UI, needs a Visual test + 3-browser check):* the client renders the `UNDONE` type as
   `swu-log-UNDONE` with no style yet — a strike-through / dimmed style would make undone lines scan better.
4. ~~**"Reveal the top N" peeks are logged as private.**~~ **RESOLVED — name all revealed cards (user,
   2026-09-11), DONE.** `SWULogPeek`: when the resolving card's text says "reveal the top", the peek is
   one public line naming every card ("P1 revealed the top 3 cards of their deck: X, Y, Z (I've Found
   Them)") with no private twin; a "look at" / "search" keeps count + private. Same principle, also done:
   `DoRevealCard` now logs ("P1's [[ISB Agent]] revealed X from P1's hand") — before, a reveal through it
   was only a one-request flash message, so ISB Agent / Queen Soruna / Lieutenant Childsen / Vermillion
   never reached the log. Thrawn and Stormchaser write their own lines (their confirmed tests assert them),
   so they pass `$log = false`. Tests: `core/GameLog_Reveals.md`.
5. ~~**Yoda / Princess Leia word their two choices differently.**~~ **RESOLVED — one style + source (user,
   2026-09-11), DONE.** `SWULogDeckPlacement` now carries the source suffix, and `SWULogToDeck`'s hidden
   (hand-origin) line uses the same numeral style: both halves read "P1 put 1 card on the top/bottom of
   their deck (Yoda)". Face-up cards (discard pile) stay named. Existing assertions were substrings, so no
   confirmed test changed; this session's own deck tests were updated to the wording. Test:
   `GameLog_CardMovesToDeckAndResources.md::ToDeck_YodaSensingDarkness_BottomReadsTheSame`.
6. ~~**Queued choices at regroup start aren't attributed.**~~ **RESOLVED — stamp the source on the queued
   decision (user, 2026-09-11), DONE, engine-wide.** New optional core hook `GameOnDecisionAdded`
   (Core/DecisionQueueController::AddDecision, behind `function_exists` — a no-op for other sims) records
   the log source current when a UNIVERSAL continuation (DEAL_UNIT_DAMAGE, GIVE_ADVANTAGE, …) is queued;
   `GameBeforeCustomHandler` (now also passed the full param) restores it when that continuation runs. The
   stamps live in a per-seat SWUVar FIFO keyed by the exact param, reconciled against the live queue on
   every add (a skipped continuation can't leave a stale stamp). Noxious Refinery / U-Wing now queue with
   their card as the source. Test: `GameLog_PhaseTriggers.md::NoxiousRefinery_…_QueuedDamageIsAttributed`.
   *Coverage gap:* the stale-stamp reconcile (declined "you may", then an identical continuation from
   another source) has no test driving it.

**All six open questions resolved (2026-09-11).** Suite 11675 passed / 0 failed; integration unchanged
(the 7 pre-existing failures).

## Single-source-of-truth helper candidates

Found while sweeping the engine for this pass: the same operation re-implemented in many places, where a
drifted copy caused a gap or a bug. Ranked by what one helper would have prevented. Counts are live greps.

1. **Committing a play — `SWUCommitPlay($player, $obj, $how)`.** 12 separate `SWU_CARDS_PLAYED` commit
   points. The Smuggle path had to hand-copy six of ActivateCard's commit steps (one-shot charges, JTL_260,
   used flags, entry grants, shrink check, telemetry), and three play paths (smuggled unit, own-discard unit,
   Pilot from hand) had NO play line. One helper for the counter + charges + flags + telemetry + play line.
   This is the [[parallel-funnels-skip-the-shared-chain]] family. **Do this one first.**
2. **Putting cards into a deck — `SWUPutCardsOnDeck($owner, $cardIDs, top|bottom|shuffle, $from)`.** 15
   card files + 4 GameLogic sites build `new Deck(...)` and push/unshift by hand; the logging is split
   across `SWULogDeckPlacement` and `SWULogToDeck` (why Yoda / Leia word their two choices differently).
   A move-and-log funnel fixes both.
3. **Refilling a resource slot from the deck — `SWUResourceTopOfDeck($player, $status)`.** The identical
   "first live deck card → AddResources exhausted" loop exists 4× (Smuggle event, Smuggle unit,
   SMUGGLE_ATTACH, `_SWUPlotReplaceSlot`). Its log line had to be added three times, and a single-site
   mutation can't target one of them.
4. **Finishing a top-deck search.** TOPDECKSEARCH_FINALIZE has ≥4 custom copies (JTL_089 Invisible Hand,
   ASH_235 Sense Through the Force, TS26_39 Captain Vaughn, IBH I've Found Them) that skip its draw
   observers (e.g. LOF_148 Rey). Parameterise the shared finalize: where the rest go (bottom / discard /
   shuffle) and whether the pick is revealed.
5. **Heal / ready from an OBJECT — `SWUHealUnitObj`, `SWUReadyUnitObj`.** OnHealUnit / OnReadyCard take
   mzIDs, so object-iterating code writes `Damage` / `Status` directly and skips the observers (Qi'ra's
   heal-all, Aurra Sing, ASH_160, Punishing One, Rex). `SWUExhaustUnitObj` (this pass) is the model; the
   siblings would take a skip-observers flag for the deliberate no-recursion cases.
6. **Which seat an object belongs to — `SWUObjSeat($obj, $mz)`.** Bases and leader-zone objects carry no
   Owner / Controller. This pass wrote `strpos($mz, 'Base') ? SWUMzOwner(...)` 4× and cloned a leader to
   stamp a Controller (Darth Traya).
7. **Shield intent — `SWUPreventWithShield()` vs `SWUDefeatShieldToken()`.** `SWUConsumeShieldToken(...,
   $forPrevention = true)` has a default that is wrong for its two defeat-a-Shield callers (the Rose Tico /
   ASH_062 Galen bug below). Two named functions make each call state its intent.
8. **Log visibility — `SWULogPublic($type, $text)` / `SWULogPrivate($seat, $type, $text)`.** The raw
   visibility string is how the int visibilities slipped in (HMW_160, HMW_108). Several cards also hand-write
   the "put a card into play as a resource" wording instead of calling the helper.

Lower priority:
- **"The opponent."** 238 `GetOpponent` / `OtherPlayer` calls. A single "who is the defending / affected
  player for this effect" helper would consolidate the known Twin Suns seat-hardcode family
  ([[determined-defending-seat-bug-family]]).
- **Once-per-round flags.** 24 bespoke per-player `SWU_*_USED` flags, each with its own clear line, instead
  of the per-copy `NumUses` mechanism ([[once-each-round-is-per-copy-not-per-player]]). The four checked
  (Punishing One, Tobias Beckett, Toro Calican, Migs Mayfeld) are unique, so per-player ≈ per-copy; the one
  difference is a copy that leaves play and returns in the same round, which should get a fresh use.

## Possible rules bugs found (NOT log changes — not touched)

- **ASH_062 The Mandalorian and SHD_045 Rose Tico consume a Shield with `$forPrevention = true`.** Per the
  SEC_046 Galen comment on `SWUConsumeShieldToken`, naming "Shield" blanks the token's PREVENTION, not the
  token — an ability that DEFEATS a Shield should pass `false` (as HMW_077 Boss Nass does). Under Galen
  naming Shield, both currently fail to defeat it.
- **Inline readies skip OnReadyCard's checks.** TWI_166 Aurra Sing, ASH_160, SHD_137 Punishing One ready
  with a direct write (now LOGGED via `_SWUReadyInline`), skipping the can't-ready checks (SOR_186, Frozen
  in Carbonite) and the when-readies taxes (JTL_192 / ASH_088). Routing them through OnReadyCard mid-attack
  would queue a tax prompt inside combat, so behaviour is unchanged.
- **SHD_099 Echo** discards with a raw `MZMove`, skipping DoDiscardCard's "when discarded" reactions
  (LAW_206), the hand-discard counters (LAW_179 / LAW_076) and the SEC_016 Padmé / SHD_163 Migs observers.
- **SHD_002 Qi'ra**'s heal-all sets `Damage = 0` directly, skipping OnHealUnit: no "when healed" reactions
  (JTL_062, LAW_047), no healed-this-phase marker (TWI_042 Barriss), no heal animation.
- **JTL_089 / ASH_235 / TS26_39** use custom search finalizers that skip TOPDECKSEARCH_FINALIZE's draw
  observers (e.g. LOF_148 Rey).
- **SOR_197 Lando / LAW_140 Intimidator**: an unset resource Owner falls back to the ACTING player rather
  than the seat named by the mzID (`SWUReturnResourceToHand` does this right) — a Twin Suns teammate's
  resource could land in the actor's hand.
- **LAW_017 Han Solo**'s 1-damage line is unattributed: its after-action is queued before the damage and
  clears the source.

## Deferred / not done

- **Leader "exhaust this leader" costs are not logged** (~17 leaders) — consistent with "an Action's own
  [Exhaust] cost is not an effect line"; the resulting effect is. Say if you want costs logged.
- **Coverage gaps** (code changed, no test drives the path): Cal Kestis's DEPLOYED side (LOF_015#1/#3);
  `_SWUBaseCaptureUnit`'s capture refusal; the SEC_101 split-damage prevention path; the opening-hand and
  mulligan lines (the harness DERIVES pregame state, so no schema test can reach `QueuePregameSetup` /
  `MulliganDecision` — verified with a scratch driver instead); the three identical Smuggle slot-refill
  lines (a single-site mutation can't target one).
- **SHD_225 Jetpack**'s regroup-start Shield defeat and **SHD_203 Zorii Bliss**'s regroup discard are
  still unattributed (queued / tag-matched paths).

## Done this pass

| Piece | What changed | Tests (`SWUSim/Tests/Cases/core/`) |
|---|---|---|
| Reactive triggers | `DispatchTrigger` sets its card as the log source (`SWULogTriggerSource`) — ~120 reactive cases were unattributed or kept the PREVIOUS ability's source; self-effects read "itself" | `GameLog_Triggers.md` |
| Game end | WIN line from `SWUDeclareGameWinner` (every path) and Twin Suns winners; "P3 conceded"; a combat win is moved after its ATTACK line | `GameLog_GameEnd.md` |
| Exhaust / ready | ~20 raw `Status = 0` effect writes → `SWUExhaustUnitObj` — logged, **and now honouring "can't be exhausted by enemy card abilities" (RULES BUG: Force Illusion, Premonition of Doom, Mind Trick, Cal Kestis, Watch This, Superheavy Ion Cannon…)**; card readies → OnReadyCard; inline readies logged; Darth Traya's leader ready | `GameLog_ExhaustReady.md` |
| Plays | `SWULogPlay`: smuggled units/upgrades and own-discard units had NO play line; "using Smuggle" / "using Plot" (Plot wrote two lines); "from their / P2's discard pile"; a Pilot played from HAND had no play line — now "P1 played X as a pilot on Y"; every upgrade names its host | `GameLog_Plays.md` |
| Prevention | Shield prevented (a line, or a note on the ATTACK line); Amidala / ASH_062 prevention — and Amidala's sacrifice is credited to her, not the enemy card; base prevention (Close the Shield Gate, Alliance Shield Generator, At Attin); "defeated itself (on P2's base)" | `GameLog_Prevention.md` |
| Refusals | "P1's X couldn't defeat / capture / take control of / return / exhaust / ready / deal damage to P2's Y" at every immunity check; Willrow / Luke-pilot upgrade refusals | `GameLog_Refusals.md` |
| Tokens / upgrades | Credit tokens defeated by effects; the automatic Credit-payment path now logs like the picker; upgrades returned to hand; Bounty collected (and is the reward's source); deck-out says why and is no longer credited to the card that asked for the draw | `GameLog_TokensAndUpgrades.md` |
| Keywords / rules | uniqueness and Exploit defeats give their reason; Saboteur's Shield defeats; L3-37 / Luke / Rampart defeat replacements; Maul's two-defender ATTACK line carries the numbers (the per-hit lines are gone) | `GameLog_Keywords.md` |
| Undo | undo / approved / denied / bookmark loaded — written AFTER the restore | `GameLog_Undo.md` |
| Mulligan | "P1 drew an opening hand of 6 cards" / "P1 mulliganed and drew a new hand of 6 cards" (one line, cards private) / "P1 kept their hand" | scratch-verified (see gaps) |
| Card moves | ~30 card files (2 parallel agents) + engine sites: searches to hand, discard/resource → hand, hand/discard → deck, resourcing, Smuggle/Plot slot refills, DJ's steal and its return | `GameLog_CardMovesToHand.md`, `GameLog_CardMovesToDeckAndResources.md`, `GameLog_EngineMoves.md` |
| Phase triggers | inline regroup-start triggers name their card (Dark Sanctum, Zillo Beast, Assault Lander, Max Rebo Band, Stygeon Prime, Heightened Awareness, Maz Kanata) | `GameLog_PhaseTriggers.md` |
| **Two lines nobody could see** | HMW_160 Noxious Refinery's reveal (visibility `0`) and HMW_108 The First Legion's named trait (visibility `1`) were logged to no viewer. Fixed, plus a static guard: `SWUSim/DevTools/tests/gamelog_visibility_arg_test.php` | `GameLog_PhaseTriggers.md` |

Every new guard was mutation-checked (the guarded line removed → its section fails, then restored).
Two of my own guards were ineffective on the first try (a `LOGCOUNT:0:DAMAGE|` / `SHIELD|` — LOGCOUNT
matches the entry TEXT, not the type prefix); both rewritten and re-verified.
