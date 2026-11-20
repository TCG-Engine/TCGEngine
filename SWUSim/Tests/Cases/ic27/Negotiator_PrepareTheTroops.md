# OnAttack_ResourcesTheTopCard_ThenDecline
#// IC27_084 Negotiator - Prepare the Troops (Unit, Space 9/7, cost 9, [Command,Heroism]) — "Sentinel. On Attack/When
#// Defeated: Resource the top card of your deck. Then, you may return a friendly resource to its owner's hand."
#//
#// COVERAGE: offer=OnAttack_Offer_FriendlyResourcesOnly_NoCreditToken (+ the Team Suns pair) ·
#//   decline=OnAttack_ResourcesTheTopCard_ThenDecline · no-target=OnAttack_EmptyDeck_TheReturnIsStillOffered ("Then" is
#//   unconditional) · control=OnAttack_AStolenResource_GoesToITSOWNERsHand (owner ≠ controller) ·
#//   reqboundary=OnAttack_RequestBoundary_BeforeTheReturn · both windows=On Attack sections + WhenDefeated_* ·
#//   modes=2P,TeamSuns ("a FRIENDLY resource" spans the team — TeamSuns_ATeammatesResourceIsOffered /
#//   TwinSunsControl_AnOpponentsResourceIsNot)
#//
#// The top card (SOR_095) becomes a 3rd resource, EXHAUSTED (rulings errata: "resource" = put into play as a resource);
#// SEC_080 is now on top. Declining the return keeps all three. The 9 still reaches P2's base.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: IC27_084:1:0
WithP1Deck: [SOR_095 SEC_080]
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P1RESCOUNT:3
P1RESAVAILABLE:2
P1DECKCOUNT:1
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:0
P2BASEDMG:9

---

# OnAttack_ReturnTheJustResourcedCard
#// The card resourced a moment ago is itself a friendly resource, so it may go straight back to hand.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: IC27_084:1:0
WithP1Deck: [SOR_095 SEC_080]
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myResources-2
## EXPECT
P1RESCOUNT:2
P1RESAVAILABLE:2
P1HANDCOUNT:1
P1DECKCOUNT:1
P2BASEDMG:9

---

# OnAttack_Offer_FriendlyResourcesOnly_NoCreditToken
#// The return pick is left PENDING. Offered: P1's two resources and the one just resourced. Not offered: P1's Credit token
#// (it sits in the resource zone but is not a resource) and P2's resources (not friendly).
## GIVEN
CommonSetup: ggw/rrk/{myResources:2;theirResources:2}
P1OnlyActions: true
WithP1Credits: 1
WithP1SpaceArena: IC27_084:1:0
WithP1Deck: [SOR_095 SEC_080]
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-3

---

# OnAttack_EmptyDeck_TheReturnIsStillOffered
#// "Then" is unconditional: with nothing to resource, the return is still offered — and taken here.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: IC27_084:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myResources-0
## EXPECT
P1DECKCOUNT:0
P1RESCOUNT:1
P1HANDCOUNT:1

---

# OnAttack_AStolenResource_GoesToITSOWNERsHand
#// "to its OWNER's hand": P1 controls a resource P2 owns. Returning it puts it in P2's hand, not P1's.
## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1ResourceControlled: SOR_046:2
WithP1SpaceArena: IC27_084:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myResources-1
## EXPECT
P1RESCOUNT:1
P1HANDCOUNT:0
P2HANDCOUNT:1

---

# OnAttack_RequestBoundary_BeforeTheReturn
#// The return pick ends the request; the answer lands in the next one.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: IC27_084:1:0
WithP1Deck: [SOR_095 SEC_080]
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myResources-0
## EXPECT
P1RESCOUNT:2
P1HANDCOUNT:1
P2BASEDMG:9

---

# WhenDefeated_ResourcesTheTopCard_AndReturns
#// The When Defeated window. Negotiator (3 damage on 7 HP) attacks P2's 4/7 Munificent Frigate: the On Attack resolves first
#// (decline its return), the trade kills both, and the When Defeated resources the NEXT top card and offers the return again
#// — taken. 2 + 1 + 1 - 1 = 3 resources, one card in hand.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: IC27_084:1:3
WithP2SpaceArena: JTL_069:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128]
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myResources-0
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1RESCOUNT:3
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_128

---

# WhenDefeated_Decline_KeepsBothNewResources
#// The When Defeated's own decline: both triggers resource a card and both returns are refused.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: IC27_084:1:3
WithP2SpaceArena: JTL_069:1:0
WithP1Deck: [SOR_095 SEC_080 SOR_128]
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENACOUNT:0
P1RESCOUNT:4
P1RESAVAILABLE:2
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# NoAttack_NoTrigger
#// The negative: the ability is On Attack / When Defeated only. Sitting in play, Negotiator resources nothing.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: IC27_084:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SEC_080]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1RESCOUNT:2
P1DECKCOUNT:2
P1NODECISION

---

# TeamSuns_ATeammatesResourceIsOffered
#// "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26): seat 3 is P1's teammate, so its resource is offered too,
#// and returning it puts it in SEAT 3's hand (its owner), not P1's.
## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Resources: 1
WithP1SpaceArena: IC27_084:1:0
## WHEN
- P1>AttackSpaceArena:0:p2Base-0
- P1>AnswerDecision:p3Resources-0
## EXPECT
SEATCOUNT:4
P3HANDCOUNT:1
P3RESCOUNT:0
P1HANDCOUNT:0
P1RESCOUNT:1

---

# TwinSunsControl_AnOpponentsResourceIsNot
#// THE CONTROL — the same table without teams: seat 3 is an opponent, so the pool is P1's own resource only.
## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3Resources: 1
WithP1SpaceArena: IC27_084:1:0
## WHEN
- P1>AttackSpaceArena:0:p2Base-0
## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myResources-0
