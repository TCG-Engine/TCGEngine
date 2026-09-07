# WhenPlayed_EwokGate_DealsOneToBaseAndOneToEnemyUnit
#// HMW_177 Adamant Ewoks (3/2, Aggression, cost 2, Ewok) — "When Played: If you control another Ewok unit
#// or an Endor base, you may deal 1 to a base and 1 to an enemy unit." Gate via another Ewok (HMW_257);
#// accept by picking the enemy base, then the lone enemy unit auto-resolves.

## GIVEN
CommonSetup: rrk/bbk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_177
WithP1GroundArena: HMW_257:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenPlayed_EndorBaseGate_Deals
#// The other gate branch: no other Ewok, but you control an Endor base (JTL_020). HMW_177 is Aggression,
#// on-aspect via the Aggression leader (rk); the Vigilance Endor base is the override.

## GIVEN
CommonSetup: brk/bbk/{myBase:JTL_020;myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_177
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenPlayed_NoGate_NoOffer
#// No other Ewok and no Endor base → the whole ability is skipped (no prompt, no damage).

## GIVEN
CommonSetup: rrk/bbk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_177
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_Decline_DoesNeither
#// The "may": declining the base choose does neither half.

## GIVEN
CommonSetup: rrk/bbk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_177
WithP1GroundArena: HMW_257:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_NoEnemyUnit_OnlyBaseHalfResolves
#// With the gate met but the opponent controlling no units, accepting still deals 1 to a base; the
#// enemy-unit half fizzles cleanly (no dangling decision).

## GIVEN
CommonSetup: rrk/bbk/{myResources:2}
P1OnlyActions: true
WithP1Hand: HMW_177
WithP1GroundArena: HMW_257:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1NODECISION
P2BASEDMG:1

---

# WhenPlayed_BaseOfferSpansBOTHBases
#// THE FIRST OFFER. "Deal 1 damage to A BASE" carries no controller word, so BOTH bases are legal —
#// including your own. Every other section here answers the enemy base and asserts the damage, which a
#// pool narrowed to the enemy base alone would satisfy identically.
## GIVEN
CommonSetup: rrk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_177
WithP1GroundArena: HMW_257:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
## EXPECT
P1DECISIONTOOLTIP:Deal_1_damage_to_a_base
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# WhenPlayed_UnitOfferIsENEMYOnly
#// THE SECOND OFFER, and the contrast that makes the first one meaningful: the very next clause of the
#// same sentence says "1 damage to an ENEMY unit", so this pool must NOT span both sides.
#// Two enemy units and two friendly ones (the Ewok gate-keeper and the just-played Adamant Ewoks
#// itself); exactly the two enemies come back.
## GIVEN
CommonSetup: rrk/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_177
WithP1GroundArena: HMW_257:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1DECISIONTOOLTIP:Deal_1_damage_to_an_enemy_unit
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
