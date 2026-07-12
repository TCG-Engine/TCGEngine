# TWI_146 Steela Gerrera — the SAME ability also fires from the When Defeated window. Steela (4/3)
# attacks SOR_046 (3/7) and dies to the 3 counter-damage; her When Defeated then (option taken) deals 2
# to P1's own base and draws the Tactic (TWI_099) from the top 8.

## GIVEN
CommonSetup: rrw/bbw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: TWI_146:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [TWI_099 SOR_095 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:TWI_099

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2
P1HANDCOUNT:1
P1DECKCOUNT:3
