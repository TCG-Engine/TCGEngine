# ReturnsAnEnemyUnit_ThenShieldsYourUnit
#// IC27_166 I've Got A Bad Feeling — Event, cost 4, [Cunning][Heroism], Innate.
#// "Return a non-leader unit to its owner's hand. Give a Shield token to a friendly unit."
#// Two INDEPENDENT mandatory clauses, resolved in order. Neither gates the other (no "If you do"), so an
#// empty or refused first clause still gives the Shield, and an empty second clause does not undo the
#// return. Clause 1 is unqualified ("a non-leader unit") — any side, any arena, a teammate's too; clause 2
#// is "a friendly unit" — yours or your teammate's, leader units included. The Shield pool is built only
#// AFTER the return resolves, so a unit you just returned is never offered it.
#// Fixture: CommonSetup yyw = Cunning base + Cunning/Heroism leader, so the card costs its printed 4.
#//
#// COVERAGE: offer=ReturnPool_EveryNonLeaderUnit_BothSidesBothArenas_NoLeaders (clause 1)
#//                 + ReturnYourOwnUnit_ShieldPoolIsBuiltAfterTheReturn (clause 2: friendly only, post-return)
#//           decline=N/A (both clauses mandatory — no "you may", no "up to")
#//           boundary=N/A (no numeric threshold; the only counts are 0-vs-1 legal targets, covered by the
#//                    fizzle sections NoNonLeaderUnit_… and NoFriendlyUnit_…)
#//           control=StolenUnit_GoesToItsOwnersHand_NotYours (owner's hand, not the controller's)
#//           reqboundary=RequestBoundary_BetweenBothPicks
#//           modes=2P,TwinSuns ("a non-leader unit" is unqualified: TwinSuns_ReturnsAFarSeatUnit…),
#//                 TeamSuns ("a friendly unit": TeamSuns_ShieldCanGoToYourTeammatesUnit + the return-pool
#//                 and teammate-return sections)

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# ReturnPool_EveryNonLeaderUnit_BothSidesBothArenas_NoLeaders
#// Clause 1's pool: your unit, the enemy's ground AND space units — and neither deployed leader
#// (deployed leaders sort after the plain units, so they sit at ground index 1 on each side).

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0

---

# ReturnYourOwnUnit_ShieldPoolIsBuiltAfterTheReturn
#// Clause 2's pool, left pending. Return your own Battlefield Marine first: the Shield is offered to your
#// two REMAINING units only (the Consular Security Force has slid down to ground index 0) — not to the
#// Marine that just left, and not to the enemy's Dark Trooper. A pool built before the return would read
#// myGroundArena-0&myGroundArena-1&mySpaceArena-0.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# ReturnYourOwnUnit_ThenShieldAnotherFriendly
#// The same board, both picks answered: the Shield lands on the chosen friendly unit only.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1HANDCOUNT:1
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# NoNonLeaderUnit_ReturnFizzles_ShieldGoesOnYourLeaderUnit
#// Only leader units on the table: clause 1 has no target and does nothing, clause 2 still resolves —
#// and "a friendly unit" has no non-leader restriction, so your deployed leader takes the Shield.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166;myLeaderDeployed:true}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# NoFriendlyUnit_ReturnStillResolves_ShieldFizzles
#// Only an enemy unit: it is returned (a lone mandatory target resolves without a prompt), then there is
#// no friendly unit, so the Shield does nothing and nothing is left pending.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION

---

# YourOnlyUnit_IsReturned_ThenNothingToShield
#// The return is mandatory: with your own unit the only non-leader unit on the table, it goes back to
#// your hand — and then the Shield has no friendly unit left to go to.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# EmptyBoard_BothClausesFizzleCleanly
## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# ReturnsATokenUnit_ItCeasesInsteadOfGoingToHand
#// A token unit is a legal non-leader unit, but a returned token ceases to exist — it never reaches a hand.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# StolenUnit_GoesToItsOwnersHand_NotYours
#// "Its OWNER's hand": you control the opponent's Dark Trooper (controlled units sort after your own,
#// so it is ground index 1). Returning it puts it in THEIR hand, not yours; your Marine takes the Shield.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2HANDCOUNT:1
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# ChewbaccaRefusesTheReturn_ShieldStillResolves
#// JTL_103 Chewbacca can't be returned to hand by ENEMY card abilities. Choosing him returns nothing —
#// and because the Shield clause is not "If you do", it still resolves.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:JTL_103
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# RequestBoundary_BetweenBothPicks
#// ReturnYourOwnUnit_ThenShieldAnotherFriendly with a fresh request before EACH answer. Nothing is held
#// in memory: the Shield pool is built by a queued step when it runs, not captured at play time.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1HANDCOUNT:1
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# TeamSuns_ShieldCanGoToYourTeammatesUnit
#// "A friendly unit" spans the team. You control no unit; your teammate (seat 3) has a Marine. Return the
#// seat-2 opponent's unit, and the Shield — with the teammate's unit as the only friendly one — lands on it.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0

## EXPECT
SEATCOUNT:4
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P3GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# TeamSuns_ReturnPoolSpansTheWholeTable
#// "A non-leader unit" is unqualified: yours, both opponents' and your teammate's are all offered.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myGroundArena-0&p2GroundArena-0&p3GroundArena-0&p4GroundArena-0

---

# TeamSuns_ReturningYourTeammatesUnit_GoesToTheirHand
#// Return the teammate's unit: it goes to ITS OWNER's (seat 3's) hand, and the Shield then goes to the
#// only friendly unit left — your own Marine.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P3GROUNDARENACOUNT:0
P3HANDCOUNT:1
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# TwinSuns_ReturnsAFarSeatUnitToItsOwnersHand_AndAFarSeatUnitIsNotFriendly
#// Three seats, no teams. Return seat 3's Consular Security Force: it goes to seat 3's hand. Seat 3's other
#// unit is NOT friendly outside a team game, so with no unit of your own the Shield has nowhere to go.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myhandCardIds:IC27_166}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P3HANDCOUNT:1
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:SOR_095
P3GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION
