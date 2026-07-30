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
