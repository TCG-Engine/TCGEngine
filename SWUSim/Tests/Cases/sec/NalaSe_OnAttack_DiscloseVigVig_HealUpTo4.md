# SEC_065 Nala Se (Ground, 4/7, Vigilance) — On Attack: you may disclose VigilanceVigilance
#   (reveal cards from your hand with these icons). If you do, heal up to 4 damage from among
#   OTHER units. Disclosed cards stay in hand (reveal only).
#
# Nala Se (idx 0) attacks P2's base (only target → auto-resolves, 4 power). On Attack fires:
# disclose SEC_054 (Vigilance,Vigilance → one card covers the requirement) → heal up to 4 across
# other units; assign all 4 to the damaged SOR_046 (3/7, 4 damage) → DAMAGE 0. Nala Se is NOT a
# valid heal target ("other units"), so the only damaged other unit is SOR_046.

## GIVEN
CommonSetup: bbk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_065:1:0
WithP1GroundArena: SOR_046:1:4
WithP1Hand: SEC_054

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-1:4

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SEC_065
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:0
P1HANDCOUNT:1
P1NODECISION
