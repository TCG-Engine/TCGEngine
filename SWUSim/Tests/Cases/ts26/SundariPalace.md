#// COVERAGE: 18 sections · clauses: (a) "for each friendly leader unit" count — TEAM-wide in Team Suns,
#// self-only otherwise, each with its control; (b) the may-resource loop — take AND decline per iteration,
#// empty-hand no-op, and the SECOND iteration's offer asserted (stale re-offer guard); (c) the regroup
#// debt — owed once per SUCCESSFUL resourcing ("if you do"), always OFFERED (a resource is not fungible),
#// cross-seat discard ownership, and same-zone descending defeat order.
#//
#// THE DEBT IS SETTLED ONE BOARD AT A TIME, YOUR OWN FIRST — not as one mixed pool. A single prompt
#// holding `myResources-…` and `p3Resources-…` together cannot be presented in one place, since your own
#// resources render inline on your board while a teammate's live behind their own seat's view. Each stage
#// may contribute at most what its board holds and at least enough that the boards after it can cover the
#// rest; when those bounds meet AND consume the whole pool the stage resolves silently rather than posing
#// a question with one legal answer.
#//
#// Offer asserted in 4 sections (P1SELECTABLEEXACT), including BOTH popups of the same flow:
#//   FirstPopup_IsYourOwnBoardOnly_NotTheOpponentsBiggerPiles · SecondPopup_IsTheTEAMMATESBoardOnly
#// CEILING: MAXIMUM_FourLeaderUnits_ResourceFour_ThenDefeatFour (four leader units, four resourcings,
#// second stage forced so it auto-resolves) and MAXIMUM_TwoPopups_YoursThenYourTEAMMATES (the same
#// ceiling driven end to end through two real popups, teammate resources actually defeated, opponents
#// holding the table's two biggest piles and untouched throughout).
#// ⚠ A TEAMMATE'S BOARD IS OFFERED AS STAGED COPIES IN YOUR OWN TempZone, never as `p{n}Resources-N`.
#// The client only ever draws two seats at a time, so a foreign resource zone has no element on your view
#// to highlight — the raw prompt rendered with a Confirm bar and NOTHING TO CLICK (live bug 2026-08-26).
#// TempZone is `Mode=None` (its own card modal) and `Visibility=Self` (revealed to you alone), which is
#// also the ruling for looking at a teammate's resources. Your OWN board stays inline, as every other
#// own-resource prompt does. A positional map on the CUSTOM param ties each staged pick back to its slot.
#// Mutation-verified 2026-08-26: collapsing the stages back into one mixed pool · widening a stage's pool
#// to every live seat · ascending defeat order within a board · offering a teammate's board raw · a
#// REVERSED positional map · leaving TempZone uncleared — each reds its own sections and nothing else.
#// ⚠ The reversed-map mutation is why StagedPickIdentity picks staged 0 and 2 rather than 0 and 3: a
#// symmetric pick maps to the same SET under reversal and cannot tell a correct map from a backwards one.
#// ⚠ `-` AND `PASS` ARE TWO DIFFERENT DECLINES. Confirming a stage with nothing selected submits the
#// literal "PASS", which goes STICKY and skips every following CUSTOM that is not DontSkipOnPass; `-`
#// does not. Every decline section here answered `-` and stayed green while a Confirm-with-zero silently
#// cancelled the rest of the card. ConfirmingZeroOnYourOwnBoardStillAsksTheTEAMMATE is the guard.

# DefeatsResourcesAtRegroup
#// TS26_12 Sundari Palace — the "resource a card and ready it" clause is paid for at the start of the next
#// regroup phase: defeat that many friendly resources. After resourcing SEC_080 (2 → 3) and passing to
#// regroup, 1 resource is defeated (3 → 2).
#//
#// ⚠ UPDATED 2026-08-26 (user-approved): which resource dies is now the player's PICK, not slot 0 taken
#// silently. The 3 → 2 assertion is unchanged; the added answer is what makes it happen.
## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>Pass
- P1>AnswerDecision:myResources-0
## EXPECT
P1RESCOUNT:2

---

# EpicResourceReady
#// TS26_12 Sundari Palace (Base, Cunning) — Epic Action: for each friendly leader unit, you may resource
#// a card from your hand and ready it. With one deployed leader unit, resource SEC_080 (2 resources → 3),
#// emptying the hand.
## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080
## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1RESCOUNT:3
P1HANDCOUNT:0
P1BASE:EPICUSED

---

# NoFriendlyLeaderUnits_NothingHappens
#// TS26_12 Sundari Palace — "FOR EACH friendly LEADER UNIT". With the leader undeployed there are none,
#// so the Epic Action offers nothing: the hand keeps its card, the resource count is unchanged, and no
#// decision is left pending (and so nothing is queued to be defeated at regroup either).

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility

## EXPECT
P1RESCOUNT:2
P1HANDCOUNT:1
P1NODECISION

---

# NoCardsInHand_NothingHappens
#// TS26_12 Sundari Palace — the other empty input: a deployed leader unit is present but there is no card
#// to resource, so the resource count stays put and no decision opens.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2

## WHEN
- P1>UseBaseAbility

## EXPECT
P1RESCOUNT:2
P1NODECISION

---

# ChoosingNoCards_NothingIsResourcedAndNothingIsDefeatedAtRegroup
#// TS26_12 Sundari Palace — "you MAY resource a card". Declining leaves the card in hand and the resource
#// count at 2; passing on into the regroup phase then defeats nothing, since the delayed cost is owed only
#// for cards actually resourced.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:-
- P1>Pass

## EXPECT
P1RESCOUNT:2
P1HANDCOUNT:1

---

# TeamSuns_TheTEAMMATESLeaderUnitGrantsAnOffer
#// ⚠ USER RULING 2026-08-26: "For each FRIENDLY leader unit" spans the TEAM, so a teammate's deployed
#// leader earns you an offer.
#//
#// ⚠ Missed by that day's friendly audit for the same reason as its sibling Dooku's Palace: the sweep
#// matched an `// EpicAction:` header and this clause sits on `// Epic Action:` — with a space. Exactly
#// two cards hid behind that typo.
#//
#// Seat 1 has NO leader unit of its own, so without the ruling the count is 0 and the Epic Action offers
#// nothing at all. Seat 3's deployed leader is the ONLY source of the single offer, which makes the
#// resourced card itself the assertion — it cannot happen by accident.
#// ⚠ `:1:1` on the far-seat leader = ready + deployed; a plain unit is not a LEADER unit.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP1Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P1RESCOUNT:3
P1HANDCOUNT:0

---

# TwinSunsControl_TeammateSeatLeaderDoesNotCount
#// THE CONTROL — identical board with WithTeams removed. Seat 3 is then an opponent, its leader unit is
#// not friendly, seat 1 has no leader unit of its own, so the count is 0 and NOTHING is offered.
#// Without this pair the section above would pass for a build that counted every leader unit on the table.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP1Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility

## EXPECT
SEATCOUNT:4
P1NODECISION
P1RESCOUNT:2
P1HANDCOUNT:1

---

# TeamSuns_YouPickWhichFriendlyResourceDies_AndItMayBeTheTEAMMATES
#// ⚠ USER RULING 2026-08-26. The rider — "defeat that many FRIENDLY resources at the start of the regroup
#// phase" — spans the TEAM in Team Suns. Unlike the ready/exhaust split (which only flips Status, so which
#// resource is meaningless and a count suffices), a DEFEAT moves a specific card to a specific discard
#// pile. Identity matters, so the Sundari controller PICKS, and a teammate's resource is a legal pick.
#//
#// The timing is the awkward part: this fires at REGROUP START, when nobody is taking an action. The
#// picker is therefore queued from RegroupPhaseStart (same shape as HMW_004's regroup base defeat).
#//
#// The assertion is deliberately CROSS-SEAT: the defeated card must land in SEAT 3's discard, not seat 1's.
#// SWUDefeatResource resolves the owner from the mzID's seat, so a frame slip here would silently put a
#// teammate's card in YOUR discard — and P1RESCOUNT alone would never notice.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP3Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
#// ⚠ FOUR passes, not one. At 4 seats the action phase only ends once EVERY live seat has passed
#// consecutively, and `P1OnlyActions` cannot stand in for that here — it claims initiative and the phase
#// never reaches regroup, so the rider silently never fires (measured: all three of these sections
#// failed that way first, which reads exactly like the picker being unimplemented).
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
#// TWO POPUPS now, one per board. The first offers YOUR resources 0-1 (your own board could cover the
#// whole debt, so taking none is legal); declining it pushes the debt onto the second popup.
- P1>AnswerDecision:-
#// ⚠ `myTempZone-0`, NOT `p3Resources-0`. A teammate's board is offered as STAGED COPIES in your own
#// TempZone — the client only draws two seats at a time, so a foreign resource zone has no element on
#// your view to light up (live bug: the prompt rendered with a Confirm bar and nothing to click). The
#// staged index maps positionally back to p3Resources-0.
- P1>AnswerDecision:myTempZone-0

## EXPECT
SEATCOUNT:4
P1RESCOUNT:3
P3RESCOUNT:1
P3DISCARDCOUNT:1
P1DISCARDCOUNT:0

---

# TeamSuns_FirstPopup_IsYourOwnBoardOnly_NotTheOpponentsBiggerPiles
#// POPUP 1 OF 2. The debt is settled one BOARD at a time, your own first — not as a single mixed pool.
#// A prompt holding `myResources-…` and `p3Resources-…` together cannot be presented in one place: your
#// own resources render inline on your board while a teammate's live behind their own seat's view.
#//
#// This asserts the first popup EXACTLY, on a board where the opponents hold the biggest piles on the
#// table (6 at seat 2, 5 at seat 4, every one of them ready). Only seat 1's six may appear:
#//   • the teammate's four are OUT of THIS popup — they get their own, asserted below
#//   • the opponents' eleven are out of BOTH, forever
#// The range is 0-4: your own board could cover the whole debt, so contributing nothing is legal.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true;myLeader2:IBH_053:1:1}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP3Leader2: SHD_011:1:1
WithP1Resources: 2
WithP2Resources: 6
WithP3Resources: 4
WithP4Resources: 5
WithP1Hand: [SEC_080 SEC_237 SOR_046 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myHand-2
- P1>AnswerDecision:myHand-3
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P1RESCOUNT:6
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-2&myResources-3&myResources-4&myResources-5

---

# TeamSuns_SecondPopup_IsTheTEAMMATESBoardOnly
#// POPUP 2 OF 2 — the same board, one step further on. Two of the four are taken from seat 1, so a debt
#// of two carries to the teammate's board and the second popup offers THEIR four, exactly.
#//
#// Two things are pinned here that popup 1 cannot show: seat 1's remaining four are NOT re-offered (the
#// debt has moved on), and the range is now a hard 2-2 because no board follows this one. The opponents
#// stay absent, which is the assertion that keeps "friendly" from meaning "everyone" at the last stage —
#// the easiest place for a fan-out to hide, since by then the prompt is already about someone else's board.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true;myLeader2:IBH_053:1:1}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP3Leader2: SHD_011:1:1
WithP1Resources: 2
WithP2Resources: 6
WithP3Resources: 4
WithP4Resources: 5
WithP1Hand: [SEC_080 SEC_237 SOR_046 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myHand-2
- P1>AnswerDecision:myHand-3
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
- P1>AnswerDecision:myResources-2&myResources-5

## EXPECT
SEATCOUNT:4
P1RESCOUNT:4
#// Staged copies, one per teammate resource, in YOUR OWN TempZone — TempZone is `Mode=None`, which routes
#// the choice into its own card modal instead of trying to highlight a board you cannot see.
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1&myTempZone-2&myTempZone-3
P1TEMPZONECOUNT:4

---

# TwinSunsControl_ThePickerIsYourOwnResourcesOnly
#// THE CONTROL for the Team Suns picker — byte-identical board with WithTeams removed.
#//
#// ⚠ REWRITTEN 2026-08-26 (user-approved). This section used to assert that NO picker appeared outside a
#// team game, because the defeat was taken silently from slot 0. That premise is dead: every
#// defeat-a-resource effect now prompts, everywhere. What survives — and what the section is actually
#// for — is the SCOPE of that prompt: with no teammate, "friendly resources" is your own board and
#// nothing else. Seat 3's two resources must NOT be in the offer.
#//
#// This is the assertion that stops the team ruling from leaking: a build that always fanned out to every
#// seat would pass every Team Suns section above and fail only here.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP3Resources: 2
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-2
P1RESCOUNT:3
P3RESCOUNT:2

---

# TeamSuns_TwoDefeatsInOneZoneResolveInDescendingOrder
#// ⚠ THE REINDEXING CELL. SWUDefeatResource compacts the zone it touches, so two picks in the SAME zone
#// must be applied HIGHEST-INDEX-FIRST: defeating myResources-2 first shifts myResources-3 down and the
#// second defeat lands on nothing. That failure is quiet — one card dies instead of two.
#//
#// Two friendly leader units here (seat 1's own AND the seat-3 teammate's, per the ruling above), so the
#// Epic Action resources TWO cards and the rider owes TWO defeats. Both picks are the freshly-resourced
#// cards at the TOP of the zone, which is exactly the pair an ascending walk would get wrong.
#// P1DISCARDCOUNT is the discriminator — a broken order still leaves RESCOUNT plausible-looking at 3.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP1Resources: 2
#// ⚠ TWO DISTINCT card IDs. Seeding `[SEC_080 SEC_080]` collapses the offer to a single candidate, the
#// MAY-choose AUTO-RESOLVES, and the section silently exercises one resourcing instead of two — measured,
#// and it reads exactly like the teammate's leader not being counted.
WithP1Hand: [SEC_080 SEC_237]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
#// ⚠ `myHand-1`, not `myHand-0` again. The second iteration re-offers the LIVE hand, and a card moved out
#// keeps its slot until the end-of-request cleanup — so the survivor is still addressed at its original
#// index. Answering `myHand-0` twice is what exposed the two bugs this section now guards.
- P1>AnswerDecision:myHand-1
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
- P1>AnswerDecision:myResources-2&myResources-3

## EXPECT
SEATCOUNT:4
P1RESCOUNT:2
P1DISCARDCOUNT:2

---

# TeamSuns_DecliningTheSecondOfferOwesOnlyONEResource
#// THE "IF YOU DO" GATE, per iteration. The rider is "you may resource a card ... IF YOU DO, defeat that
#// many friendly resources" — so the regroup debt is owed once per resourcing that ACTUALLY happened,
#// never once per leader unit. Two friendly leader units here, but the second offer is DECLINED, so
#// exactly ONE resource may be defeated at regroup.
#//
#// ⚠ This was measurably wrong before 2026-08-26: the debt was stacked unconditionally, so a second
#// iteration that resourced nothing still charged the player. It only became reachable once a teammate's
#// leader unit could push the count to 2 — every earlier section ran a single iteration and could not
#// see it. The discriminator is the PICKER'S OWN CAP: at a debt of 2 the prompt would demand two picks
#// and `myResources-0` alone would not be a legal answer.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP1Resources: 2
WithP1Hand: [SEC_080 SEC_237]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:-
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
- P1>AnswerDecision:myResources-0

## EXPECT
SEATCOUNT:4
P1RESCOUNT:2
P1DISCARDCOUNT:1

---

# TeamSuns_SecondOfferDoesNotReOfferTheCardYouJustResourced
#// ⚠ THE POOL, asserted on the SECOND iteration. Within a single request a card moved out of the hand
#// keeps its slot until the end-of-request cleanup, so the naive `ZoneSearch("myHand")` re-offered the
#// card that had just become a resource — and picking it resourced nothing while still owing a defeat.
#//
#// This is a pool assertion because answering can never catch it: the sections above answer the SURVIVOR
#// (`myHand-1`), which is legal either way. Only looking at the offer itself shows the ghost.
#// The exact set must be `myHand-1` alone — the survivor at its ORIGINAL index, not re-based to 0.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP1Resources: 2
WithP1Hand: [SEC_080 SEC_237]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myHand-1

---

# TeamSuns_MAXIMUM_FourLeaderUnits_ResourceFour_ThenDefeatFour
#// ⚠ THE CEILING OF THIS CARD, and the only section where every moving part runs at once.
#//
#// Four friendly leader units is the true maximum: Twin Suns gives each seat TWO leaders, and under the
#// friendly=team ruling seat 1's two plus the seat-3 teammate's two all count. So the Epic Action loops
#// FOUR times, resources four cards, and owes FOUR defeats at the regroup phase.
#//
#// Everything this card can get wrong is live simultaneously here, and each has its own failure signature:
#//   • the loop must run 4 distinct iterations and re-read the hand each time — a stale re-offer would let
#//     one pick resource nothing (hand 4 → 0 is the tell)
#//   • the debt must accrue exactly 4, not 4-per-leader-unit and not 1 — the picker's own cap is the tell,
#//     since a wrong count makes this exact 4-mzID answer illegal and the section errors rather than fails
#//   • the pool must span BOTH seats (6 of seat 1's + 2 of seat 3's = 8 candidates for 4 picks)
#//   • the picks must resolve highest-index-first WITHIN each zone — two of them are seat 1's
#//     myResources-2 and myResources-5, which an ascending walk would shift out from under itself
#//   • each defeated card must land in ITS OWN owner's discard — 2 in seat 1's, 2 in seat 3's, never 4 in
#//     the actor's
#//
#// The teammate's resources are wiped to ZERO here on purpose: that is the sharpest form of "a teammate's
#// resource is a legal pick", and it is only reachable because the pool crosses seats.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true;myLeader2:IBH_053:1:1}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP3Leader2: SHD_011:1:1
WithP1Resources: 2
WithP3Resources: 2
#// ⚠ FOUR DISTINCT card IDs. Identical IDs collapse the offer to one candidate, the may-choose
#// auto-resolves, and the loop silently runs fewer iterations than the leader count.
WithP1Hand: [SEC_080 SEC_237 SOR_046 SOR_095]

## WHEN
- P1>UseBaseAbility
#// A card moved out of the hand keeps its slot until the end-of-request cleanup, so the survivors stay at
#// their ORIGINAL indices — 0, then 1, then 2, then 3. Never `myHand-0` four times.
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myHand-2
- P1>AnswerDecision:myHand-3
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
#// Your own board is offered 2-4 (the teammate's two can absorb at most two of the four), so taking
#// exactly two leaves a debt of two against a two-card board — no discretion left, so the second stage
#// resolves silently instead of posing a question with one legal answer. That auto-resolution is the
#// behaviour being asserted: the teammate still loses BOTH, and they still land in THEIR discard.
- P1>AnswerDecision:myResources-2&myResources-5

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P1RESCOUNT:4
P3RESCOUNT:0
P1DISCARDCOUNT:2
P3DISCARDCOUNT:2

---

# TeamSuns_MAXIMUM_TwoPopups_YoursThenYourTEAMMATES
#// ⚠ THE WHOLE FLOW, END TO END, AT THE CARD'S CEILING — and the section that proves you really can
#// defeat a teammate's resources rather than merely being offered them.
#//
#// Four friendly leader units (seat 1's two plus the seat-3 teammate's two) means four resourcings and a
#// four-resource debt. It is then settled across TWO popups, one per board:
#//   • popup 1 — your own six, range 0-4. Two taken: myResources-2 and myResources-5.
#//   • popup 2 — the teammate's four, range 2-2. Two taken: p3Resources-0 and p3Resources-3.
#//
#// Every mechanism in the card is live at once and each fails distinctly: the loop runs four iterations
#// (hand 4 -> 0), the debt accrues exactly four (a wrong count makes these answers illegal and the section
#// ERRORS rather than fails), both popups pick non-adjacent indices so an ascending walk shifts a pick out
#// from under itself, and the four defeated cards split 2/2 into their OWN owners' discards rather than
#// landing four-deep in the actor's.
#//
#// Meanwhile the opponents hold the two biggest piles on the table and are untouched by all of it.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true;myLeader2:IBH_053:1:1}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP3Leader2: SHD_011:1:1
WithP1Resources: 2
WithP2Resources: 6
WithP3Resources: 4
WithP4Resources: 5
WithP1Hand: [SEC_080 SEC_237 SOR_046 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myHand-2
- P1>AnswerDecision:myHand-3
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
- P1>AnswerDecision:myResources-2&myResources-5
#// Popup 2 answers STAGED copies (myTempZone-K), which map positionally back to p3Resources-0 and -3.
#// Non-adjacent on purpose: index 3 must survive index 0 being defeated first.
- P1>AnswerDecision:myTempZone-0&myTempZone-3

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P1RESCOUNT:4
P3RESCOUNT:2
#// The staging is scratch space, not state — it must be empty again once the pick resolves, or the next
#// prompt inherits somebody else's cards.
P1TEMPZONECOUNT:0
P1DISCARDCOUNT:2
P3DISCARDCOUNT:2
P2RESCOUNT:6
P4RESCOUNT:5

---

# TeamSuns_StagedPickIdentity_TheMAPPointsAtTheRightSlots
#// ⚠ THE MAP, asserted by IDENTITY. A teammate's board is offered as staged CardID copies in your own
#// TempZone, so a positional map is the only thing tying `myTempZone-K` back to `p{n}Resources-{i}` —
#// staged copies are bare CardIDs and a team can hold duplicates, so nothing else could re-match them.
#//
#// Every other section here asserts COUNTS, and counts cannot see a scrambled map: any two of the four
#// leave the teammate on 2 resources and 2 discards. So this one gives seat 3 four DISTINCT resources and
#// names the two that must die — staged 0 and 2, defeating SOR_046 and SOR_128.
#//
#// ⚠ THE PICKS MUST BE ASYMMETRIC, and this is the whole reason the section is worded around 0 and 2.
#// The first draft picked 0 and 3, and a mutation that REVERSED the map did not red it — {0,3} maps to
#// {3,0}, the same set, so the assertion could not tell a correct map from a backwards one. {0,2} maps to
#// {3,1}, a different pair of cards. Measured 2026-08-26.
#//
#// Still non-adjacent, so the reindex hazard is covered through the indirection too: staged index 2 must
#// resolve after index 0 has been defeated and the teammate's zone compacted.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true;myLeader2:IBH_053:1:1}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Leader: SHD_014:1:1
WithP3Leader2: SHD_011:1:1
WithP1Resources: 2
WithP3Resources: 1:SOR_046:1,1:SOR_095:1,1:SOR_128:1,1:SEC_080:1
WithP1Hand: [SEC_080 SEC_237 SOR_046 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:myHand-2
- P1>AnswerDecision:myHand-3
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
- P1>AnswerDecision:myResources-2&myResources-5
- P1>AnswerDecision:myTempZone-0&myTempZone-2

## EXPECT
SEATCOUNT:4
P3RESCOUNT:2
P3DISCARDCOUNT:2
#// Highest index first, so SOR_128 (slot 2) lands in the discard before SOR_046 (slot 0).
P3DISCARDUNIT:0:CARDID:SOR_128
P3DISCARDUNIT:1:CARDID:SOR_046
P1TEMPZONECOUNT:0

---

# TeamSuns_ConfirmingZeroOnYourOwnBoardStillAsksTheTEAMMATE
#// ⚠ REGRESSION GUARD for a live bug (2026-08-26). Confirming a stage with NOTHING selected submits the
#// literal "PASS", and a sticky PASS makes ExecuteStaticMethods skip every following CUSTOM that is not
#// flagged DontSkipOnPass. The stage continuation was not flagged, so the second board was never asked
#// and the ENTIRE remaining debt evaporated — the card's whole downside, cancelled by a Confirm.
#//
#// Taking none from your own board is a LEGAL answer, not a cancellation: the lower bound is 0 precisely
#// because your teammate's board can absorb the debt instead. So the pass must advance the chain, never
#// end it.
#//
#// ⚠ WHY THIS ESCAPED THE EXISTING SECTIONS — worth knowing before writing another. The decline sections
#// here answer `-`, which is NOT "PASS" and does not go sticky. Every one of them stayed green against
#// the broken build. `-` and `PASS` are two different declines and only one of them carries this hazard.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP3Resources: 3
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P2>Pass
- P3>Pass
- P4>Pass
- P1>Pass
#// Popup 1 (your own board, range 0-1) confirmed with nothing selected.
- P1>AnswerDecision:PASS
#// Popup 2 must still arrive, staged over the teammate's three resources, and the debt must be intact.
- P1>AnswerDecision:myTempZone-1

## EXPECT
SEATCOUNT:4
P1RESCOUNT:3
P3RESCOUNT:2
P3DISCARDCOUNT:1
P1DISCARDCOUNT:0
P1TEMPZONECOUNT:0
