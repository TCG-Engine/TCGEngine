#// COVERAGE: 13 sections · clauses: (a) "for each friendly leader unit" count — TEAM-wide in Team
#// Suns, self-only otherwise, each with its control; (b) the may-resource loop — take AND decline per
#// iteration, empty-hand no-op, and the SECOND iteration's offer asserted (stale re-offer guard);
#// (c) the regroup debt — owed once per SUCCESSFUL resourcing ("if you do"), a picker over friendly
#// resources (the pick is ALWAYS offered — user ruling: a resource is not fungible), scoped to your own
#// board when there is no teammate, cross-seat discard ownership, and same-zone
#// descending defeat order. Offer asserted in 3 sections (P1SELECTABLEEXACT). The pool-exactly-consumed
#// branch skips the picker by design and is covered as code, not as a section.
#// Mutation-verified 2026-08-26: teams gate · friendly pool · defeat ordering · debt gate · stale-hand
#// filter — 5 for 5, each reddening its own section and nothing else.

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
- P1>AnswerDecision:p3Resources-0

## EXPECT
SEATCOUNT:4
P1RESCOUNT:3
P3RESCOUNT:1
P3DISCARDCOUNT:1
P1DISCARDCOUNT:0

---

# TeamSuns_TheOfferSpansBOTHSeatsResources
#// The pool itself, asserted exactly — answering a target proves the branch, never the pool. Seat 1 holds
#// 3 resources after the Epic Action and seat 3 holds 2, so all five must be offered and neither
#// OPPONENT's resources (seats 2 and 4) may appear. Without this the section above would pass for a build
#// that offered only the teammate's, or only the actor's plus one.

## GIVEN
CommonSetup: yyk/rrk/{myBase:TS26_12;myLeaderDeployed:true}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP2Resources: 2
WithP3Resources: 2
WithP4Resources: 2
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
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-2&p3Resources-0&p3Resources-1

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
