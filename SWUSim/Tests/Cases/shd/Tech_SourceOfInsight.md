# Tech_GrantsSmuggleToPlainCard
#// COVERAGE: offer=N/A — the grant is a STATIC keyword grant and raises no decision of its own; the only
#//           decision any granted Smuggle play makes is the host pick when the smuggled card is an UPGRADE,
#//           asserted pending in SmuggleUpgradeViaTheGrant_OffersEveryUnitInPlay
#//           decline=N/A — no "you may" anywhere on the card; a resource simply HAS Smuggle or does not
#//           control=StolenEnemyResourceGainsTheGrant ("each FRIENDLY resource" is a CONTROL test, not an
#//           ownership test: a resource P1 controls but P2 owns gains it) + EnemyResourcesDoNotGainTheGrant
#//           boundary=GrantIsOffWhileTechSitsInResources / TechSmugglesHimselfIn_ThenTheGrantIsLive /
#//           GrantDiesWithTech (the grant's three states: not yet in play, in play, gone) and the cheaper-path
#//           pair PrintedSmuggleWinsWhenItIsCheaper vs GrantWinsWhenItIsCheaperThanPrintedSmuggle
#//           reqboundary=TechSmugglesHimselfIn_ThenTheGrantIsLive (the grant is re-derived from live board
#//           state on a SECOND action, after Tech's own play already ended)
#//           seatcount=TeamSuns_TheTEAMMATES_Tech_TurnsYourResourcesOn (an ally's Tech reaches you) with
#//           BOTH negatives — TwinSunsControl_ATeammateSeatsTechDoesNotReachYou (no team, no reach) and
#//           TeamSuns_AnOPPONENTS_Tech_StillDoesNotReachYou (team ≠ table). Mutation-verified 2026-08-26
#//           in both directions: narrowing PlayerHasTechInPlay to your own seat reds only the positive,
#//           widening it to every live seat reds only the two negatives.
#// SHD_248 Tech — "Each friendly resource gains Smuggle. The gained Smuggle cost is that card's cost plus
#// 2 resources and its aspect icons." A plain card with NO printed Smuggle (SOR_046, cost 4, Vigilance/
#// Heroism — both covered) can be played from resources via the granted Smuggle for 4 + 2 = 6. It enters
#// play (exhausted) and is replaced in resources by the top of the deck (net resource count unchanged).

## GIVEN
CommonSetup: bbw/grw
WithP1GroundArena: SHD_248
WithP1Resources: 1:SOR_046:0,6:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESCOUNT:7
P1RESAVAILABLE:0

---

# GrantIsOffWhileTechSitsInResources
#// The grant is worded "each friendly resource", i.e. it only operates while Tech is IN PLAY. With Tech
#// himself still a face-down resource, the plain SOR_046 next to him has no Smuggle path at all and the
#// attempt is rejected outright: nothing enters the arena, no resource is spent and no decision is raised.
#// (A card that read the grant off Tech's mere presence in the ZONE would let this play through for 6.)

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 1:SOR_046:1,6:SOR_095:1,1:SHD_248:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:8
P1RESAVAILABLE:8
P1NODECISION

---

# TechSmugglesHimselfIn_ThenTheGrantIsLive
#// The transition, in one section. Tech has his OWN printed Smuggle [4 resources, Heroism]; his slot pays 1
#// toward it (CR 8.22.e) and 3 more resources cover the rest. Once he is on the board the very next action
#// can smuggle SOR_046 out of the same zone for the granted 4 + 2 = 6 — a card with no printed Smuggle of
#// its own, which GrantIsOffWhileTechSitsInResources just proved was unplayable a moment earlier.
#// Each play consumes its slot and refills it from the deck (exhausted), so the zone stays at 10 while
#// every resource in it ends up spent.

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1Resources: 8:SOR_095:1,1:SHD_248:1,1:SOR_046:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>SmuggleResource:8
- P1>SmuggleResource:8

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SHD_248
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1RESCOUNT:10
P1RESAVAILABLE:0

---

# EnemyResourcesDoNotGainTheGrant
#// "Each FRIENDLY resource" — the grant is scoped to Tech's controller. P1 has Tech on the board; P2's own
#// resource zone is untouched by it, so P2 cannot smuggle the plain SOR_095 sitting there. P2's zone is
#// unchanged and nothing enters P2's arena.

## GIVEN
CommonSetup: bbw/grw
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SHD_248
WithP2Resources: 6:SOR_095:1
WithP2Deck: SOR_095

## WHEN
- P2>SmuggleResource:0

## EXPECT
P2GROUNDARENACOUNT:0
P2RESCOUNT:6
P2RESAVAILABLE:6
P2NODECISION

---

# GrantDiesWithTech
#// The grant is a continuous effect, not a one-time stamp on the zone. P2's AT-AT Suppressor (SOR_039, 8/8)
#// defeats Tech (2/5); with Tech gone, the same SOR_046 that the top-of-file section smuggles for 6 is no
#// longer playable at all, even though P1 still has 6 ready resources — far more than enough had the grant
#// survived. Nothing is spent and nothing enters the arena.

## GIVEN
CommonSetup: bbw/grw
WithActivePlayer: 1
WithP1GroundArena: SHD_248:1:0
WithP2GroundArena: SOR_039:1:0
WithP1Resources: 1:SOR_046:1,6:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1RESAVAILABLE:7
P1NODECISION

---

# PrintedSmuggleWinsWhenItIsCheaper
#// A card that already has its own Smuggle keeps the cheaper of the two. SHD_111 Collections Starhopper is
#// cost 2 with printed Smuggle [3 resources, Command]; the granted price would be 2 + 2 = 4. With Command
#// covered the printed 3 wins, so the Starhopper's own slot pays 1 and only TWO other resources exhaust —
#// three of the five stay ready. Taking the granted path instead would leave only two ready.

## GIVEN
CommonSetup: ggw/grw
P1OnlyActions: true
WithP1GroundArena: SHD_248
WithP1Resources: 1:SHD_111:1,5:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111
P1RESCOUNT:6
P1RESAVAILABLE:3

---

# GrantWinsWhenItIsCheaperThanPrintedSmuggle
#// The mirror. SHD_113 Privateer Crew is cost 2 with a printed Smuggle of [6 resources, Command]; the
#// granted price is 2 + 2 = 4. P1 holds only 4 other resources, so the printed 6 is flatly unaffordable
#// (its own slot pays 1, leaving 5 to find) — the play can only have gone through on the granted 4.
#// It is still a Smuggle play, so Privateer Crew's own "When played using Smuggle: give 3 Experience
#// tokens to this unit" fires and the printed 2/2 arrives as a 5/5.

## GIVEN
CommonSetup: ggw/grw
P1OnlyActions: true
WithP1GroundArena: SHD_248
WithP1Resources: 1:SHD_113:1,4:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SHD_113
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1RESCOUNT:5
P1RESAVAILABLE:1

---

# StolenEnemyResourceGainsTheGrant
#// "Friendly" is about CONTROL, not ownership. SHD_178 Daring Raid sits in P1's resource zone but is OWNED
#// by P2 (the end state after a take-control effect); Tech grants it Smuggle all the same, and P1 plays it
#// for the granted 1 + 2 = 3, dealing its 2 damage to P2's base — the controller gets the card's effect
#// even though the opponent still owns the card.

## GIVEN
CommonSetup: rrw/grw
P1OnlyActions: true
WithP1GroundArena: SHD_248
WithP1ResourceControlled: SHD_178:2
WithP1Resources: 4:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1RESCOUNT:5
P1RESAVAILABLE:2

---

# SmuggleResupplyViaTheGrant_AddsAnotherCardToResources
#// The grant reaches EVENTS too, and an event smuggled out of the zone still runs its full ceremony.
#// SOR_126 Resupply is cost 3 with no printed Smuggle, so the granted 3 + 2 = 5 is the only price. Its own
#// slot is spent and refilled from the deck (CR 8.22.g, before the ability resolves), and then Resupply's
#// own text puts the event itself back in as a resource — so a 6-card zone ends at 7 and the event never
#// rests in the discard pile.

## GIVEN
CommonSetup: ggw/grw
P1OnlyActions: true
WithP1GroundArena: SHD_248
WithP1Resources: 1:SOR_126:1,5:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1RESCOUNT:7
P1RESAVAILABLE:1
P1DISCARDCOUNT:0
P1DECKCOUNT:0

---

# SmuggleUpgradeViaTheGrant_OffersEveryUnitInPlay
#// An UPGRADE in the resource zone gains the grant as well, and a smuggled upgrade has to pick a host.
#// SOR_069 Resilient is cost 1, so the granted price is 1 + 2 = 3. Two friendly units are on the board, so
#// the host pick stays interactive and is read here while it is still PENDING — the only decision any
#// granted Smuggle play raises.

## GIVEN
CommonSetup: bbw/grw
P1OnlyActions: true
WithP1GroundArena: [SHD_248:1:0 SOR_095:1:0]
WithP1Resources: 1:SOR_069:1,4:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1


---

# TeamSuns_TheTEAMMATES_Tech_TurnsYourResourcesOn
#// ⚠ USER RULING (friendly = the TEAM). Tech reads "each FRIENDLY resource gains Smuggle", so in Team Suns
#// your ally's Tech turns YOUR resources on. Seat 1 owns no Tech at all here — the only copy on the table
#// belongs to the seat-3 teammate — so the smuggle play below is impossible unless the grant crosses the
#// team. Same board and same numbers as Tech_GrantsSmuggleToPlainCard (SOR_046 for 4 + 2 = 6); only the
#// Tech's SEAT moved.

## GIVEN
CommonSetup: bbw/grw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3GroundArena: SHD_248:1:0
WithP1Resources: 1:SOR_046:0,6:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESCOUNT:7
P1RESAVAILABLE:0

---

# TwinSunsControl_ATeammateSeatsTechDoesNotReachYou
#// THE CONTROL — byte-identical board with WithTeams removed. In a free-for-all Twin Suns game seat 3 is
#// an opponent, its Tech is not friendly, and seat 1 has no Smuggle path at all: the play is REJECTED
#// outright, nothing enters the arena, and not one resource is spent.
#// Without this pair the section above would pass for a build that simply granted Smuggle to everybody.

## GIVEN
CommonSetup: bbw/grw
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3GroundArena: SHD_248:1:0
WithP1Resources: 1:SOR_046:0,6:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1RESAVAILABLE:6
P1NODECISION

---

# TeamSuns_AnOPPONENTS_Tech_StillDoesNotReachYou
#// ⚠ THE NEGATIVE THAT KEEPS "FRIENDLY" MEANING FRIENDLY. Same Team Suns game, but the only Tech belongs
#// to seat 2 — an OPPONENT, not the ally. Widening the grant to the team must not widen it to the table,
#// so seat 1 still has no Smuggle path and the play is rejected exactly as in the control above.
#//
#// This is the section that discriminates a team lookup from a live-seats lookup: a build that scanned
#// every seat's ground arena would pass both this file's positive sections and fail only here.

## GIVEN
CommonSetup: bbw/grw
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP2GroundArena: SHD_248:1:0
WithP1Resources: 1:SOR_046:0,6:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1RESAVAILABLE:6
P1NODECISION

---

# SmuggledUnit_ExploitForkIsOffered_CONTROL_FromHandItIs
#// THE PASSING CONTROL for the section below. TWI_037 Droideka Security has Exploit 2, and playing it
#// from HAND raises "Defeat_up_to_2_units_(Exploit)" before the cost is paid. Without this control the
#// RED section below would only prove "no prompt appeared", which is equally consistent with the fixture
#// being wrong about Exploit existing at all.
## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: [TWI_037]
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_up_to_2_units_(Exploit)

---

# SmuggledUnit_ExploitForkIsOffered
#// ⚠⚠ KNOWN ENGINE BUG — THIS SECTION IS EXPECTED TO BE RED. Restored 2026-09-01 from the SHD worklist,
#// where it had existed only as PROSE since 2026-08-15 because the old practice deleted failing sections
#// to keep the file green. It asserts the CORRECT behaviour.
#//
#// SHD_248 Tech grants Smuggle to every friendly resource ("the gained Smuggle cost is that card's cost
#// plus 2 resources and its aspect icons"), which is what makes this reachable at all — no printed card
#// carries both Smuggle and Exploit. TWI_037 Droideka Security is cost 6, so its Tech-granted Smuggle
#// cost is 8, and both its aspects are covered by this board.
#//
#// EXPECTED: smuggling an Exploit unit offers the same Exploit fork the hand path offers (the control
#// above) — Exploit is part of PLAYING the card, not of playing it from hand.
#// ACTUAL:   no decision is raised at all; the unit simply enters play at full price.
#// ROOT CAUSE: SWUSmuggleResource places units INLINE via AddGroundArena/AddSpaceArena and only
#// DELEGATES for the Upgrade and Event branches. The Exploit fork lives in the
#// SWUBeginPlayCard/ActivateCard hand path, which the unit branch never enters.
#// SHARES ONE ROOT with Clone.md::SmuggledClone_CopyForkIsOffered — fixing the delegation fixes both.
## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10:SOR_046:1,1:TWI_037:1
WithP1GroundArena: SHD_248:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>SmuggleResource:10
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Defeat_up_to_2_units_(Exploit)

---

# SmuggledUnit_StillGetsPassiveEntryGrants
#// A unit played via Smuggle is a unit you PLAY, so a grant that fires as a unit ENTERS must reach it.
#// Found 2026-09-01 while refactoring the smuggle placement: SWUApplyPassiveEntryGrants is called by
#// ActivateCard's unit branch and by the own-discard play path, and by NEITHER smuggle branch.
#//
#// ⚠ THIS SECTION WAS RE-TARGETED. Its first form used SOR_100 Wedge ("each friendly Vehicle gains
#// Ambush") and PASSED WITHOUT THE FIX — because Wedge is a CONTINUOUS AURA read through the keyword
#// layer, so a smuggled Vehicle gets Ambush whether or not the entry grant ran. The entry-grant call
#// only adds a provenance token there. A section that cannot fail is documentation, so it was pointed at
#// the one consumer that genuinely depends on the call:
#// ASH_006 Sabine Wren — "the next unit you play this phase gains Shielded" — which CONSUMES a one-shot
#// flag and gives the Shield at entry. Nothing else grants it, so the Shield is present only if the
#// smuggle placement runs the entry grants.
#// The flag is armed directly here rather than by driving Sabine's leader Action, because that Action
#// requires an opponent to accept 2 Advantage tokens first and that cross-player branch is not what this
#// section is about.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1,1:SOR_237:1
WithP1GroundArena: SHD_248:1:0
WithP1GlobalEffect: SWU_ASH006_SHIELDED_NEXT

## WHEN
- P1>SmuggleResource:6

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
