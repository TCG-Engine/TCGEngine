# Restore2_OnUnitAttack_HealsBase
#// SOR_045 Yoda — Restore 2 fires "When this unit attacks" on ANY attack, not just base attacks
#// (regression guard for the Restore fix). Yoda attacks a UNIT (SOR_063, 2/4) and survives; P1's base
#// heals 2 (3 damage → 1). SOR_063 takes Yoda's 2 combat damage.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenDefeated_Both_DrawBoth
#// SOR_045 Yoda — "When Defeated: choose any number of players, they each draw." Choosing "Both" →
#// both P1 and P2 draw a card.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Both

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1

---

# WhenDefeated_Opponent_DrawOpp
#// SOR_045 Yoda — choosing "Opponent" → only P2 draws a card; P1 draws nothing.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Opponent

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1

---

# WhenDefeated_You_DrawSelf
#// SOR_045 Yoda — "When Defeated: Choose any number of players. They each draw a card." Yoda attacks
#// LAW_124 (4/7) and dies (2/4 takes 4). On defeat, choosing "You" → only P1 (Yoda's controller) draws.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:You

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:0
