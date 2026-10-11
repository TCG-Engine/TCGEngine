# CombatDamage_DiscardToReady
#// COVERAGE: offer=N/A (the hand pick is your own hand; any card is legal) · decline=Decline_StaysExhausted
#//           cannot-pay=EmptyHand_NoOffer · boundary=Defeated_NoOffer ("and survives") and Shielded_NoOffer
#//           (prevented damage is never dealt) · path=AbilityDamage_AlsoTriggers
#//           control=N/A · reqboundary=AcrossTheRequestBoundary · modes=2P only (no player reference)
#//           Grit = keyword-only half, auto-wired
#//
#// HMW_166 Gungi — Unit (Ground) 2/5, cost 3, [Aggression][Heroism], Force/Jedi/Wookiee.
#// "Grit. When this unit is dealt damage and survives: You may discard a card from your hand. If you do,
#//  ready this unit."
#// Gungi (2/5) attacks SOR_095 (3/3): takes 3 and survives.

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_166:1:0
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# Decline_StaysExhausted

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_166:1:0
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:2

---

# EmptyHand_NoOffer

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_166:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# Defeated_NoOffer

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_166:1:4
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1NODECISION

---

# Shielded_NoOffer

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_166:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# AbilityDamage_AlsoTriggers
#// HMW_130 Emerie Karr deals 1 to the exhausted Gungi; discarding readies it.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_166:0:0
WithP1Hand: [HMW_130 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_166:1:0
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
