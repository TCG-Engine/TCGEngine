# Decline_NoReady
#// SEC_190 Soulless One — decline the On Attack disclose → no resources readied.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_190:1:0
WithP1Resources: 4:SOR_095:0
WithP1Hand: SEC_220
WithP1Hand: SEC_230
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:0
P1NODECISION

---

# OnAttack_Disclose_ReadyTwoResources
#// SEC_190 Soulless One (Ground, 3/3, Cunning/Villainy) — On Attack: you may disclose CunningCunningVillainy
#//   → ready 2 resources.
#// 4 exhausted resources. Soulless One attacks the base; On Attack: disclose SEC_220 + SEC_230 (Cunning)
#// + SEC_133 (Villainy) → cover CunningCunningVillainy → ready 2 of the exhausted resources.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_190:1:0
WithP1Resources: 4:SOR_095:0
WithP1Hand: SEC_220
WithP1Hand: SEC_230
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P2BASEDMG:3
P1RESAVAILABLE:2
P1NODECISION
