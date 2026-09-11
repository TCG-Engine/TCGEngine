# Game log updates — open questions & follow-ups

Collected during the autonomous game-log gap pass (2026-09-11). Everything below is something I did NOT
decide alone: a design choice, an ambiguity, a possible rules bug outside the log's scope, or a coverage gap.
Suite: 11572 → **11675 passed, 0 failed** (first pass) → **11704** after the leftovers pass → **11752 passed,
0 failed** at the end of the SSOT pass. Integration: 4 red on every run, all pre-existing and outside SWUSim
game logic (GA generated dictionary, two Hellbreak tests, SWUDeck format column); the other 3 of the original
7 now pass. See the regression skill's table. Nothing here touched them.

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
   bail. ⚠ *Correction:* the example first given here — HMW_014 Wicket "still dispatching his FRONT reaction
   after he deploys" — was WRONG: that line comes from his DEPLOYED On Attack and is genuine. Borderline:
   deployed SOR_009 Leia logs it after a solo attack (accurate, but frequent). Regenerate the full list with
   `php -d xdebug.mode=off -d memory_limit=2G SWUSim/DevTools/scan-noeffect.php` (in the container).
   **Triaged in the leftovers pass (all 560 cards) — see "Leftovers pass" below.**
3. ~~**Several undos in a row leave ONE "undid" line.**~~ **RESOLVED — keep undone actions visible (user,
   2026-09-11), DONE.** `SWULogCarryUndone`: before a restore the log is kept; the lines the restore erases
   come back marked "(undone)" (type `UNDONE`) with their ORIGINAL visibility — a private "You drew X" stays
   the drawer's alone — then the undo line. This player's consecutive undos fold into one "P1 undid their
   last 3 actions". A restore that is not a prefix of the old log (a divergent branch) carries nothing;
   bookmark loads carry nothing. Tests: `core/GameLog_Undo.md`.
   ~~*Optional follow-up (UI):* no style for the `UNDONE` type.~~ **DONE (2026-09-11).** Undone lines are dimmed
   (opacity 0.45) and struck through, card links included. The rule is in both `GameLayout.php` and
   `GameLayoutMobile.php`, because mobile doesn't load desktop's CSS. Visual: `Tests/Visual/GameLog_UndoneAndDeckLines.md`;
   3-engine × 2-layout check: `DevTools/ui-harness/swusim-log-styles-xbrowser.mjs`, all pass. Removing the
   rules fails all 12 style checks.
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
   ~~*Coverage gap:* the stale-stamp reconcile has no test driving it.~~ **Covered** —
   `SWUSim/DevTools/tests/gamelog_queued_source_test.php` drives it directly, and on its first run it found a
   real off-by-one: the reconcile counted the just-added decision as already live, so it kept one stale stamp
   too many. Fixed (`_SWULogSrcQueueReconcile` discounts the just-added param).

**All six open questions resolved (2026-09-11).** Suite 11675 passed / 0 failed; integration unchanged
(the 7 pre-existing failures).

## Single-source-of-truth helper candidates

Found while sweeping the engine for this pass: the same operation re-implemented in many places, where a
drifted copy caused a gap or a bug. Ranked by what one helper would have prevented. Counts are live greps.

1. ~~**Committing a play — `SWUCommitPlay`.**~~ **DONE (leftovers pass).**
   `SWUCommitPlay($player, $cardID, $logSuffix, $as, $chargeObj)` in GameLogic.php now owns the play line,
   telemetry, the `SWU_CARDS_PLAYED` counter, the one-shot charge consume (only on a path that applied the
   charges) and the "first unit / non-unit / Clone / Gambit you play" flags. It is the ONLY place the counter
   is bumped (12 sites → 1). Writing it exposed **three real counter bugs**, read by Vanguard Ace, Lothal
   Insurgent, Coordinate's "exactly the 2nd card", Tribunal and Talzin's Shuttle:
   - a **smuggled EVENT counted twice**: Smuggle committed, then delegated to ActivateCard, which committed
     again and re-ran the charge consume;
   - **any card played from an opponent's discard counted twice** (both the direct path and the Unit answer
     of its Unit-vs-Pilot fork bumped the counter before handing off to ActivateCard);
   - a **Pilot played from HAND never counted** (both the Unit-vs-Pilot answer and the pilot-only branch).
   The own-discard / foreign-discard pilot paths, own-discard units, Vermillion and Tear This Ship Apart also
   gain telemetry and the unit flags they skipped. Tests: `core/PlayCommit_CountsEachPlayOnce.md` (4
   sections, each mutation-verified).
   ~~*Follow-up:* `_SWUOwnDiscardPlayAsUnit` skips ActivateCard's unit-ENTRY flags.~~ **DONE (2026-09-11).**
   ActivateCard's per-play entry bookkeeping is now `_SWUPlayedUnitEntry`, called by all three paths that put
   a PLAYED unit into play: ActivateCard, the own-discard play and the Smuggle play. It covers:
   - the "next unit you play" charges (LOF_180 Ambush, LOF_010 Hidden);
   - the played flags (`SWU_PLAYED_UNIT_<uid>`, `SWU_UNITS_PLAYED_ROUND`, Villainy / First Order / Force /
     Bounty Hunter / Pilot);
   - the play-source grants, the paid stamp, and pending entry effects.
   The Smuggle placement was a third inline copy with the same gap. The own-discard path also gained the
   continuous-shrink check: a unit replayed under SHD_037 Snoke survived at -1 HP. Tests:
   `core/UnitEntry_EveryPlayPath.md` (3; each site's mutation fails only its own section).
2. ~~**Putting cards into a deck.**~~ **DONE (SSOT pass, 2026-09-11).** Three entry points, in GameLogic.php:
   - `SWUPutCardsOnDeck($owner, $cardIDs, 'top'|'bottom'|'bottomRandom'|'shuffle', $from)` for cards already
     out of their zone. `$from` sets the log wording: hand/deck = hidden count; discard/play/revealed =
     named; `''` = no line (internal staging).
   - `SWUMoveCardToDeck($player, $mzID, $where)` takes a hand, discard or deck card to its OWNER's deck.
   - `SWUMoveChosenHandCardToDeck($player, $idx, $cid, $where)` is the Yoda / Leia "then put a card from
     your hand on top or bottom".
   ~20 hand-built `new Deck(...)` sites converted: 12 real placements, `_topDeckPutRemainingToBottom`, the
   unit-to-deck path, and 6 "stage a search pick on top to play or resource it" sites. The search rest now
   logs through `SWULogToDeck`; `SWULogDeckPlacement` is left only for scry's combined "bottom and kept on
   top" line.
   **Fixed on the way:**
   - LOF_103 Following the Path's top placement logged nothing; its revealed picks are now named.
   - **IC27_008 Princess Leia** (Yoda's Action verbatim) re-found the chosen card by CardID and never
     reported the removal to the waiting draw trigger, so a drawn LOF_148 Rey lost her trigger whenever
     any card before her was put back.
   Tests: `core/PutCardsOnDeck_OneFunnel.md`, `lof/Rey_DrawnByLeia_TheDrawnCopyTriggers.md` (3); the 10
   per-site log sections in `GameLog_CardMovesToDeckAndResources.md` each fail when their site is
   neutralised.
3. ~~**Refilling a resource slot from the deck.**~~ **DONE (SSOT pass, 2026-09-11).**
   `SWUResourceTopOfDeck($player, $ready = false, $reason = '')` in GameLogic.php, over one move shared with
   the ramp helpers (`_SWURampResource`). It wasn't 4 copies but ~20:
   - **8 raw loops:** the Smuggle unit / event / upgrade and Plot refills, Hunter, Frontier Trader, Bail
     Organa, Citadel Research Center. They had no slide animation and used three different log lines.
   - **12 ramp-helper callers that located the top card themselves:** Resupply Carrier, Outlaw Corona's
     bounty, Galactic Escalation, Tear This Ship Apart, Scanning Officer, Chancellor Valorum, Elia Kane, Long
     Live the Empire, Han Solo, Lifetree Caravan, Ima-Gun Di, Chewbacca's Bowcaster, Jendirian Valley, When
     Has Become Now. Several read `'myDeck-0'`, which is a dead slot after an uncleaned removal — the reason
     Outlaw Corona's bounty once silently did nothing.
   Every one now reads "P1 resourced the top card of their deck (<source>)", with a rule reason for the
   refills ("Smuggle slot refill" / "Plot slot refill"). Tear This Ship Apart's refill is now credited to
   LAW_066 instead of the stolen card that resolved in between. Tests: `core/ResourceTopOfDeck_OneFunnel.md`
   — one section per site, and each site's own mutation fails only its own section, which also closes the
   old "a single-site mutation can't target one Smuggle refill" gap. The 8 raw paths gained the deck →
   resources slide animation: `Tests/Visual/ResourceTopOfDeck_SmuggleRefillSlides.md`. Browser-checked in Chromium,
   Firefox and WebKit (`DevTools/ui-harness/swusim-refill-slide-xbrowser.mjs`): the ZONE_MOVE is sent scoped to
   seat 1 and a card flies; removing the slide fails all three engines.
4. ~~**Finishing a top-deck search.**~~ **DONE (SSOT pass, 2026-09-11).** Two helpers in GameLogic.php:
   - `_SWUAfterCardsDrawn($player, $drawnMz)` — everything that follows a DRAW: telemetry, undo consent, the
     drawn-this-phase counter `SWU_DREW_PHASE` (LAW_051 Beilert Valance), `_SWUOnPlayerDrew` (ASH_169 Axe
     Woves, JTL_111, SHD_184) and `_SWUOnDrawLof148` (Rey). Before, only DoDrawCard had all of it.
   - `SWUFinishTopDeckSearch($player, $peekedIDs, $pick, 'bottom'|'discard', $revealed)` — resolve, draw,
     log ("revealed and drew" / hidden), place the rest, then the above. Returns `[cardID, handMz]` pairs
     for the card's rider.
   **Rules bug fixed:** 8 draw paths skipped the observers and the counter — The Invisible Hand, Sense
   Through the Force, Captain Vaughn, Bounty Posting and I've Found Them (custom finalizers);
   Reinforcement Walker and C-3PO (`SWUDrawTopCardFront`); Admiral Trench's deployed draw (TempZone). Even
   the shared finalize never counted its draw. Bounty Posting's line now reads "revealed and drew" like
   every other search, and C-3PO's gets the source suffix. Tests: `core/SearchAndDraw_FiresDrawObservers.md`
   (9 sections; mutation: dropping the helper call fails all 9, dropping the counter also fails 4 Beilert
   Valance tests). Searches that PLAY their pick or put it into hand (Triple Dark Raid, Reforge, …) are not
   draws and are untouched.
   **Retro follow-up — ★ USER RULING + CR 7.6.8 (2026-09-11, Yoda + Rey):** leader abilities and events must
   fully resolve first — a draw trigger raised inside an EVENT or an ACTION ability (leader, unit, base) waits
   until that action is complete, and belongs to the DRAWN copy. TWI_004 Yoda: "draw a card, then put a card
   from your hand on the top or bottom" — LOF_148 Rey's reveal resolves only after the put-back; putting THAT
   Rey on the deck cancels her trigger even with another Rey in hand, keeping her allows exactly one reveal.
   Generic, serialized bracket: opened by `SWUBeginActionAbility` (all 8 Action sites) and the event branch of
   ActivateCard, released in `SWUAfterAction` (the close re-queued behind the released triggers, on the queue
   they landed on). **Nested (user, 2026-09-11: "Rey triggers, but won't resolve til after"):** a draw inside a
   TRIGGERED ability (Captain Vaughn's When Defeated) is a nested ability (CR 7.6.11) — it waits for that ability
   (after Vaughn's put-on-top) and then resolves before its siblings: a release marker at block 2 on the queue
   that is running (new read-only Core accessor `DecisionQueueController::ExecutingSeat()`; on the caster's
   queue it stalled Watto's / Cikatro's attacks, whose draw runs on the opponent's queue). Yoda's
   put-back now moves the exact chosen copy (it re-found it by CardID — the wrong Rey). Tests:
   `lof/Rey_DrawnByYoda_WaitsForTheLeaderAbility.md` (7), `lof/Rey_DrawnByVaughn_NestedWaitsForTheAbility.md` (3),
   `core/DrawTriggers_WaitForTheAction.md` (3 cross-seat), `core/SearchAndDraw_FiresDrawObservers.md::BountyPosting_DrawTriggerWaitsForTheEvent`.
5. ~~**Heal / ready from an OBJECT.**~~ **DONE (2026-09-12).** Swept by verb, not by the md's list: every raw
   "Damage lowered" and "Status = 1" write on a unit. **No raw heal remains**: every heal goes through
   OnHealUnit; Qi'ra was fixed earlier. Every effect ready goes through OnReadyCard, or `_SWUReadyInline` for
   an object. The combat `Status = 1` writes only undo an aborted attack's exhaust, and the ~60 others are
   leftover Grand Archive code. So no object-level heal helper was written; nothing would call it.
   **The real remainder was THREE copies of the ready-tax queue.** The shared `_SWUQueueReadyTaxes` plus two
   regroup clones, and the clones taxed EVERY unit carrying the upgrade. CR 5.1.e: a ready card chosen for a
   readying effect "is not considered to have been readied", and regroup readies only exhausted cards. So
   JTL_192 In Debt to Crimson Dawn and ASH_088 The Conflict Within asked a unit that was already ready (and one
   under Frozen in Carbonite) to pay. The regroup step now records the units that actually ready and taxes
   exactly those through the shared queue; the clones are deleted. Tests:
   `core/ReadyTaxes_OnlyWhenTheUnitReadies.md`.
6. ~~**Which seat an object belongs to.**~~ **DONE (SSOT pass, 2026-09-11).** `SWUObjSeat($obj, $owner = false)`
   in GameLogic.php returns the field if it is > 0, else the seat whose zone holds the object, found by object
   identity (no frame needed). `SWULogObjRef` asks it, so the 4 hand-derived base seats and Darth Traya's
   leader clone are gone.
   A sweep classified all 172 `->Owner/Controller ?? <player>` / two-seat-formula fallbacks by object kind:
   158 safe (arena units always carry both fields), 9 low, and **2 real bugs**, both wrong even at two seats:
   - **HMW_060 Vice Admiral Rampart:** when an OPPONENT defeated your base upgrade, the engine asked whether
     the defeating player controls Rampart, so your Rampart was never offered. Fixing that exposed a
     **second bug** in the same path: the deferred offer stored the base's mzID in the defeater's frame and
     resolved it in yours, so it found nothing and silently dropped the offer.
   - **TWI_040 A Fine Addition:** an upgrade played from an opponent's discard became the caster's, because
     discard objects carry no Owner. When it later left play it went to the wrong discard.
   Tests: `core/ObjSeat_ZoneObjectsWithoutOwner.md`. The three fixes each fail their own section when
   reverted, and disabling the identity scan fails 6 game-log sections.
   Found in the same sweep: `ZoneClasses.php` Discard / Hand / Deck / Leader / Base carry no Owner or
   Controller, and Resources default both to **-1**.
7. ~~**Shield intent.**~~ **DONE (2026-09-12).** `SWUPreventWithShield($unit)` (a Shield absorbing a hit, which
   Galen naming "Shield" blanks) and `SWUDefeatShieldToken($unit)` (an effect or cost defeating one, which Galen
   doesn't stop). The core is now private, `_SWUConsumeShieldToken($unit, $forPrevention)`, with no default;
   all 7 callers state their intent. **Bug fixed on the way:** Saboteur's "defeat the defender's Shields" had
   its own loop and skipped the "a friendly upgrade was defeated" observers (ASH_039 Baylan's flag, ASH_161
   Zeb's deal-1); both paths now share `_SWUShieldDefeatedObservers`. Tests:
   `core/ShieldIntent_TwoNamedFunctions.md`, plus the Galen Rose Tico / Mandalorian sections (which fail if
   the defeat function uses the prevention path).
8. ~~**Log visibility.**~~ **DONE (SSOT pass, 2026-09-11).**
   - **Restricted lines:** `SWULogPrivate($seat, …)` / `SWULogSeats($seats, …)` in GameLogEvents.php are the
     only way to write one. They build and de-duplicate the tag and refuse to write a line nobody could see
     (seat 0 used to give 'P0'). A public line is plain `AddGameLogEntry($type, $text)`, which defaults to
     'ALL', so no `SWULogPublic` was needed.
   - **Converted:** the 7 hand-built visibilities (the draw / search / peek lines, the private reveal,
     Thrawn's regroup peek, Reanimated Night Trooper).
   - **Guard:** `gamelog_visibility_arg_test.php` now also fails any computed visibility outside the
     helpers (allow-list: `SWULogPrivate`, `SWULogSeats`, `SWULogEffect`, `SWULogCarryUndone`).
   - **One resource wording:** the ramp helpers' generic "P1 put a card into play as a (ready) resource"
     (~12 callers, no source, no origin) now goes through `SWULogResourced`: "P1 resourced a card from
     their hand, ready (Han Solo)" (hidden), "P1 resourced [[X]] from their discard pile (…)" (named).
     Arquitens Assault Cruiser's hand-written line is converted too.
   - **Tests:** `core/GameLog_RampHelperLines.md` (3) and `DevTools/tests/gamelog_private_helpers_test.php`.
     Mutation: removing the seat filter fails the helper test; putting a hand-built visibility back fails
     the guard.
   - Reanimated Night Trooper: the look is now public and the card private (user decision, see the end of
     this file).

Lower priority:
- ✅ (2026-09-12) **"The opponent."** 238 `GetOpponent` / `OtherPlayer` calls. A single "who is the defending / affected
  player for this effect" helper would consolidate the known Twin Suns seat-hardcode family
  ([[determined-defending-seat-bug-family]]).
  - **Outcome: no new helper needed.** The Twin Suns sweep (closed 2026-08-27) already built the helpers:
    `SWUQueueChooseOpponent`, `SWUQueueChoosePlayer`, `OpponentsOf`, `GetLiveSeatsArray`, `SWUAllBaseMzIDs`.
    Most remaining calls are gated two-seat paths or unreachable fallbacks.
  - A re-scan of every card file (22 live hits, each read) found **2 live bugs** the sweep's clause index
    missed: `SOR_134` Ruthless Raider (the "an enemy base" damage always hit seat 2, or nothing from seat
    3/4) and `LAW_159` Expendable Mercenary (a far-seat owner's discard was never searched). It also found
    one dead seat param, `LAW_101`. All three are fixed and pinned at four seats.
  - Nothing new has been introduced since the sweep. Details are in
    `SWUSim/docs/twinsuns-opponent-choice-plan.md` → "POST-SWEEP RE-SCAN".
- ✅ (2026-09-12) **Once-per-round flags.** 24 bespoke per-player `SWU_*_USED` flags, each with its own clear line, instead
  of the per-copy `NumUses` mechanism ([[once-each-round-is-per-copy-not-per-player]]). The four checked
  (Punishing One, Tobias Beckett, Toro Calican, Migs Mayfeld) are unique, so per-player ≈ per-copy; the one
  difference is a copy that leaves play and returns in the same round, which should get a fresh use.
  - **Outcome.** The flags were two families, so there was no single conversion:
    1. **"Use this ability only once each round" on a UNIT → converted to NumUses on the copy (11 cards).**
       - `ASH_032` Rancor Keeper and `TWI_082` MagnaGuard Wing Leader are NOT unique. These were real bugs
         (bug #1031's shape): two copies shared one round.
       - `ASH_160` Kachirho Militia is also not unique; its per-UID flag was simply moved to NumUses.
       - The unique ones: `ASH_047` Gar Saxon, `ASH_128` Bothan-5, `SHD_137` Punishing One, `SHD_163` Migs,
         `SHD_217` Tobias, `SHD_239` Toro, `LAW_094` Hondo, `LAW_176` Sebulba's Podracer. Now a copy that
         leaves play and returns gets a fresh round (CR 8.5.4).
       - New helpers `SWUUnitRoundUseSpent(uid)` / `SWUSpendUnitRoundUse(uid)` (GameLogic) cover a trigger
         whose check and spend fall in different requests. The UID rides the trigger param.
    2. **"The first X you play each round/phase" → correctly PER PLAYER.** That is the player's history, not
       a copy's budget (Krennic, Malakili, JTL_260, SHD_198, LAW_229, ASH_212, SEC_064, ASH_075). But:
       - **RULES BUG (CR 8.8.1: "not the first after an ability becomes active").** `SHD_198` Omega,
         `LAW_229` Codebreaker and `SEC_064` Congress only recorded the first play WHILE their card was in
         play. So a Clone / Gambit / upgrade played earlier left the next one wrongly discounted; Omega's
         OWN play is the first Clone. They now record every qualifying play.
       - **RULES BUG (CR 1.8.3: cost modifiers are cumulative).** Two Congresses, two Pit Droid Teams or
         two Death Star Plans each gave ONE discount. Now one per copy, and Pit Droid's "another friendly
         unit" is judged per copy.
       - Tests: `core/FirstEachRound_HistoryAndStacking.md` (9).
    - **Deliberately left as per-player flags:**
      - `HMW_062` Nuvo Vindi and `LAW_053` Dengar: a copy defeated in the same batch still observes
        (last-known information), and the defeat batch does not record the dead copy's budget. Both are
        unique, so this only matters for leave-and-return.
      - The leaders `SEC_002`, `SHD_010`, `SHD_017`: a leader can't leave and return within a round, and
        under CR 3.4.6 a control change defeats it.
      - The per-PHASE ones, `HMW_172`, `HMW_215` and `SEC_067`, because NumUses refills per round.
    - **Found along the way:**
      - **MagnaGuard's Action was refused with no ready Droid**, against the official ruling ("can be used
        as an action even if you can't attack with a Droid unit"). Fixed; it spends the round.
      - The Twin Suns pools of Rancor Keeper, Coruscanti Spy and Tobias (see item 3 / the plan doc).
      - `LAW_094` Hondo's `ActionOnceEachRound` could not see the limit: the second play was unaffordable
        anyway. A new section keeps it affordable.
      - Gar Saxon's once-per-round section could not see a pending second offer. A new section asserts
        `P1NODECISION`.
    - Mutation-checked per card. The Migs, Gar, Bothan-5 and Podracer checks are layered, so each is pinned
      as a set.
    - ❓ **Needs you — see the bottom of this file.**

## Possible rules bugs found — ALL FIXED (leftovers pass; the search finalizers by SSOT #4)

- ✅ **ASH_062 The Mandalorian and SHD_045 Rose Tico consume a Shield with `$forPrevention = true`.** Per the
  SEC_046 Galen comment on `SWUConsumeShieldToken`, naming "Shield" blanks the token's PREVENTION, not the
  token — an ability that DEFEATS a Shield should pass `false` (as HMW_077 Boss Nass does). Under Galen
  naming Shield, both currently fail to defeat it.
- ✅ **Inline readies skip OnReadyCard's checks.** TWI_166 Aurra Sing, ASH_160, SHD_137 Punishing One ready
  with a direct write (now LOGGED via `_SWUReadyInline`), skipping the can't-ready checks (SOR_186, Frozen
  in Carbonite) and the when-readies taxes (JTL_192 / ASH_088). Routing them through OnReadyCard mid-attack
  would queue a tax prompt inside combat, so behaviour is unchanged.
- ✅ **SHD_099 Echo** discards with a raw `MZMove`, skipping DoDiscardCard's "when discarded" reactions
  (LAW_206), the hand-discard counters (LAW_179 / LAW_076) and the SEC_016 Padmé / SHD_163 Migs observers.
- ✅ **SHD_002 Qi'ra**'s heal-all sets `Damage = 0` directly, skipping OnHealUnit: no "when healed" reactions
  (JTL_062, LAW_047), no healed-this-phase marker (TWI_042 Barriss), no heal animation.
- ✅ (SSOT #4) **JTL_089 / ASH_235 / TS26_39** use custom search finalizers that skip TOPDECKSEARCH_FINALIZE's draw
  observers (e.g. LOF_148 Rey).
- ✅ **SOR_197 Lando / LAW_140 Intimidator**: an unset resource Owner falls back to the ACTING player rather
  than the seat named by the mzID (`SWUReturnResourceToHand` does this right) — a Twin Suns teammate's
  resource could land in the actor's hand.
- ✅ **LAW_017 Han Solo**'s 1-damage line is unattributed: its after-action is queued before the damage and
  clears the source.

## Deferred / not done — resolved in the leftovers pass

- ✅ **Leader "exhaust this leader" costs are not logged** (~17 leaders) — consistent with "an Action's own
  [Exhaust] cost is not an effect line"; the resulting effect is. Say if you want costs logged.
- ✅ (except pregame — the Smuggle refills were closed by SSOT #3) **Coverage gaps** (code changed, no test drives the path): Cal Kestis's DEPLOYED side (LOF_015#1/#3);
  `_SWUBaseCaptureUnit`'s capture refusal; the SEC_101 split-damage prevention path; the opening-hand and
  mulligan lines (the harness DERIVES pregame state, so no schema test can reach `QueuePregameSetup` /
  `MulliganDecision` — verified with a scratch driver instead); the three identical Smuggle slot-refill
  lines (a single-site mutation can't target one).
- ✅ **SHD_225 Jetpack**'s regroup-start Shield defeat and **SHD_203 Zorii Bliss**'s regroup discard are
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

## Leftovers pass (2026-09-11, second half)

User decisions: inline readies → **checks + taxes**; leader exhaust costs → **log them**; no-effect over-triggers
→ **triage now**; SSOT → **do #1 now**. Suite **11704 passed / 0 failed**. Every new guard mutation-verified.

| Piece | What changed | Tests |
|---|---|---|
| Galen-named Shield | Rose Tico (SHD_045) and ASH_062 The Mandalorian DEFEAT the Shield (`SWUConsumeShieldToken(..., false)`) — Galen blanks its prevention, not the token | `sec/GalenErso_NamedShieldCanStillBeDefeated.md` |
| Inline readies | `_SWUReadyInline` (Aurra Sing, ASH_160, Punishing One, Rex's DC-17s) now honours "can't ready" (`_SWUReadyBlocked`, shared with OnReadyCard) and queues the when-readies taxes (`_SWUQueueReadyTaxes`: JTL_192 / ASH_088) | `core/InlineReady_RespectsReadyRules.md` |
| Echo / Qi'ra | SHD_099 Echo discards through `DoDiscardCard`; SHD_002 Qi'ra's heal-all calls `OnHealUnit` per unit | `shd/Echo_DiscardIsARealDiscard.md`, `shd/Qira_HealAllIsARealHeal.md` |
| Lando / Intimidator | an unset resource Owner falls back to the mzID's seat (`SWUMzOwner`), not the actor | `teamsuns/ReturnTeammateResourceToItsOwner.md` |
| Jetpack / Zorii | SHD_225's regroup Shield defeat and SHD_203's regroup discard are attributed | `core/GameLog_PhaseTriggers.md` |
| Leader costs | "P1 exhausted [[Leader]]" for ~17 "you may exhaust this leader" costs (`SWULogLeaderExhaustCost`; + source suffix when another card asks) | `core/GameLog_LeaderCosts.md` |
| Coverage gaps | Cal Kestis deployed, Arrest's base-capture refusal, Amidala's split prevention | `GameLog_ExhaustReady.md`, `GameLog_Refusals.md`, `GameLog_Prevention.md` |
| **No-effect triage** | 560 cards. **Over-triggers gated at collection**: Coordinate On Attacks (TWI_096/147/165/192) and When Playeds (TWI_095/162/213) via `_SWUOnAttackAbilityActive` / `_SWUWhenPlayedAbilityActive`; Force-host upgrades SOR_054/SOR_137/LOF_187; JTL_012 (Fighter host); Poe JTL_100's unit-only When Played no longer fires as a pilot (`_SWUWhenPlayedIsUnitOnly`). **Stubs registered** (`$swuLogEffectAppliedElsewhere`: IBH_010/042 Han Solo, LOF_014, SHD_216) + a static guard that every empty ability closure is registered | `GameLog_NoEffect.md`, `DevTools/tests/gamelog_noeffect_stub_test.php` |
| **SEC_013 Luthen double trigger** | an EFFECT self-defeat of the attacker (SOR_150 Heroic Sacrifice, LAW_205 Flash the Vents, LAW_062 Defiant Hammerhead) fired Luthen TWICE — SWUDefeatUnit's "defeated while attacking" check and HeroicSacrificeDefeatTrigger's own copy. The deployed side offered a 2nd deal-2; the front side hid it ("had no effect"). Duplicate removed | `sec/LuthenRael_DontYouWantToFightForReal.md` (+3 sections) |
| **IC27_024 Thrawn When Defeated** | after a combat death the survivor shifts into Thrawn's positional slot, so the self-exclusion excluded the SURVIVOR → "had no effect". Now excludes the slot only if it really is Thrawn (the SEC_202 Rebel Propagandist idiom) | `ic27/GrandAdmiralThrawn_ListenToMeCarefully.md` (+1 section) |
| **SWUCommitPlay** | SSOT #1 — see above; 3 counter bugs fixed | `core/PlayCommit_CountsEachPlayOnce.md` |
| Queued-source reconcile | off-by-one found by its new test, fixed | `DevTools/tests/gamelog_queued_source_test.php` |

### Needs you — all answered (user, 2026-09-11)

1. ~~Two confirmed tests describe code that changed.~~ **Fixed (user-approved).**
   `jtl/PoeDameron_OneHellOfAPilot.md::PlayAsPilot_NoToken` comments (and the card file's header) now describe
   the unit-only gate; mutating `_SWUWhenPlayedIsUnitOnly` off fails 3 Poe sections.
   `ic27/...::WhenDefeated_GivesExperienceToASurvivor` now answers BOTH prompts (On Attack → index 1, When
   Defeated → index 0 after cleanup) and asserts 2 tokens / power 5 / nothing pending; the old positional
   exclusion fails it.
2. ~~Do LAW_205 / LAW_062 "after completing this attack" defeats count for Luthen?~~ **RULING: yes — they are
   "defeated while attacking."** Current behaviour, pinned by the two new Luthen sections.
3. ~~Thrawn's self-exclusion has no test.~~ **Fine as is (user)** — Thrawn is dead by the time When Defeated
   resolves, so there is nothing for it to exclude; the guard is defensive only.

### Status at the end of the 2026-09-11 session

**Decided by you (2026-09-11):**
- ASH_045 Reanimated Night Trooper: **the look is public, the card private.** "P1 looked at the top card of
  P2's deck (Reanimated Night Trooper)" is shown to everyone; "You saw [[X]]" goes to the trooper's controller
  only (`ash/ReanimatedNightTrooper.md` Log_*; making the card line public fails both sections).
- A search's "put N cards on the bottom" line stays the default off-white (`DECK`), not gold: **approved.**

**Bigger items, deliberately not started:**
- ~~SSOT #5, the rest~~ — done 2026-09-12 (no raw heal/ready left; the regroup ready taxes now fire only for units that actually ready).
- ~~SSOT #7, the rest~~ — done 2026-09-12 (two named Shield functions; Saboteur now fires the observers).
- ~~"Lower priority": one "the opponent / defending player" helper~~ — done 2026-09-12 (no new helper
  needed; the re-scan fixed SOR_134 and LAW_159 — see above).
- ~~Moving the 24 bespoke `SWU_*_USED` once-per-round flags to per-copy `NumUses`~~ — done 2026-09-12 (see
  above), with one question for you below.

### Still open (not started)

- All eight SSOT items are done or explicitly parked (#1–#4, #6, #8 done; #5 and #7 partly addressed — see above).
- ~~`_SWUOwnDiscardPlayAsUnit` skips the unit-entry flags~~ — done (SSOT #1 follow-up).
- ~~The foreign-discard fork's **Unit** answer has no test~~ — covered (2026-09-11): both answers of
  FOREIGN_PILOT_PLAY_CHOICE in `core/PlayCommit_CountsEachPlayOnce.md` (Vanguard Ace gets exactly 1 token);
  re-adding the old bump fails the Unit section (2 tokens), dropping the Pilot commit fails the Pilot one (0).
- ~~UI: a style for the `UNDONE` log type~~ — done, browser-checked in all three engines.

### Needs you (2026-09-12)

1. **Does a unit's "once each round" survive a CONTROL CHANGE?** P1 uses MagnaGuard Wing Leader's Action;
   P2 steals it with Change of Heart. Can P2 use it again this round?
   - **Current behaviour: yes.** The new controller gets a fresh round.
     - This is what `law/SebulbasPodracer_TakingTheLead.md::NewControllerGetsAFreshUsePerRound` (added
       2026-08-13) asserts.
     - CR 8.32.3 words the limit on the player: "they may not choose to resolve that text again that
       round".
     - I kept it, and pinned the same reading for MagnaGuard
       (`twi/MagnaguardWingLeader.md::ControlChange_TheNewControllerGetsAFreshRound`).
   - **Alternative: the round stays spent** (same object, CR 8.5.4 only resets on leaving play).
     - That is a one-line change: `SWUTakeControlOfUnit` carries `NumUses` onto the rebuilt unit.
     - Both sections would flip. The Podracer one is an existing test, so I'd need your OK to change it.

