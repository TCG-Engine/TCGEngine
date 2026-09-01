# DeclineReturn
#// SOR_197 Lando Calrissian — "up to 2" means the player may return ZERO. Declining the MZMULTICHOOSE
#// (min 0) returns nothing: resources stay 8, hand stays empty, Lando is in play.
#// COVERAGE: offer=Offer_OnlyFRIENDLYResources_NeverTheOpponents (decision left pending; the pool is
#//           P1's six resources with P2's five excluded) ·
#//           decline=DeclineReturn ("up to 2" includes zero — the MZMULTICHOOSE has min 0) ·
#//           control=ForeignOwnedResource_GoesBackToItsOWNERSHand (owner != controller: a P2-owned
#//           resource in P1's row is friendly to P1 but returns to P2's hand) ·
#//           boundary pair=ReturnOneResource (exactly 1) + Return2Resources (exactly 2, the printed
#//           maximum) + DeclineReturn (zero) ·
#//           reqboundary=ReturnHappensAFTERThePlayCost_ExhaustedResourcesAreStillReturnable — the
#//           resource pool is re-read when the answer arrives, one request after the play that
#//           exhausted every one of them, and the return still resolves against the post-payment row

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESCOUNT:8
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1

---

# Return2Resources
#// SOR_197 Lando Calrissian — "When Played: Return up to 2 friendly resources to their owners'
#// hands." P1 plays Lando (cost 6) with 8 resources, then returns 2 of them to hand: resources
#// 8 → 6, hand gains 2 (started with Lando, played him, +2 returned).

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1RESCOUNT:6
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1

---

# ReturnOneResource
#// SOR_197 Lando Calrissian — "Return up to 2" with the player choosing to return exactly ONE. Plays
#// Lando (cost 6) with 8 resources, returns 1 to hand → resources 8 → 7, hand gains 1.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:7
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1

---

# Saboteur_IgnoresEnemySentinel_ReachesTheBase
#// Intended: "Saboteur (When this unit attacks, ignore Sentinel …)" — Lando's first clause, which none
#// of the resource sections touch. Lando (6/5) declares an attack on P2's base while P2's Echo Base
#// Defender (SOR_098, Sentinel, 4/3) stands in the same arena. The Sentinel is bypassed: the base takes
#// all 6, the Defender is untouched, and Lando takes nothing back. Had Saboteur not applied, the attack
#// would have been forced onto the Defender and the base would read 0.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_197:1:0    # Lando (Saboteur, 6/5)
WithP2GroundArena: SOR_098:1:0    # Echo Base Defender (Sentinel, 4/3)

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Saboteur_DefeatsTheDefendersShieldBeforeCombatDamage
#// Intended: the second half of Saboteur — "…and defeat the defender's Shields." Lando attacks a
#// SHIELDED Industrious Team (LAW_124, 4/7). The Shield is defeated during the attack instead of
#// absorbing the hit, so the full 6 lands and the defender ends on 6 damage with no Shield. A Shield
#// that had done its normal job would leave the defender on 0 damage with the same 0 Shields, so the
#// damage number is what separates the two. Lando survives the 4 counter-damage on his 5 HP.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_197:1:0        # Lando (Saboteur)
WithP2GroundArena: LAW_124:1:0        # Industrious Team 4/7
WithP2GroundArenaUpgrade: 0:SOR_T02   # ...with a Shield

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1

---

# Offer_OnlyFRIENDLYResources_NeverTheOpponents
#// Intended: "return up to 2 FRIENDLY resources" — the pool is exactly P1's own six resources and
#// nothing of P2's, even though P2 has five resources of their own sitting on the table. Answering a
#// target would only prove the branch, so the decision is left PENDING here and the offer itself is
#// asserted; the resolutions live in the sections above and below.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6;theirResources:5;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-2&myResources-3&myResources-4&myResources-5
P1RESCOUNT:6
P2RESCOUNT:5

---

# ReturnHappensAFTERThePlayCost_ExhaustedResourcesAreStillReturnable
#// Intended: When Played resolves after the play has been paid for, so with EXACTLY the 6 resources
#// Lando costs, all six are exhausted by the time the return happens — and exhausted resources are
#// still legal to return. Two go back to hand, leaving four resources, all of them still exhausted.
#// The existing Return2Resources uses 8 resources and so can never tell "paid first" apart from
#// "returned first"; here, a return that resolved BEFORE payment would have left Lando unplayable.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1RESCOUNT:4
P1RESAVAILABLE:0
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1

---

# ForeignOwnedResource_GoesBackToItsOWNERSHand
#// Intended: "return … to their OWNERS' hands" — the destination is the owner's hand, not the
#// controller's. P1's resource row holds a Death Star Stormtrooper that P2 still OWNS (the end state
#// after an enemy card has been resourced into P1's zone). It is friendly to P1, so it is in the pool
#// and P1 may return it — but the card lands in P2's hand, not P1's. P1's own hand stays empty and P1
#// simply loses the resource.
#// (A `Controlled` resource seats after the plain ones, so with six ordinary resources it is index 6.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:6;handCardIds:SOR_197}
P1OnlyActions: true
WithP1ResourceControlled: SOR_128:2    # in P1's resource row, OWNED by P2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-6

## EXPECT
P1RESCOUNT:6
P1HANDCOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SOR_128
P1GROUNDARENACOUNT:1
