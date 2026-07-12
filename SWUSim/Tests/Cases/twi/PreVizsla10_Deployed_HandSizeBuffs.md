# TWI_010 Pre Vizsla (Leader, deployed) — "While you have 3+ cards in your hand, this unit gains Saboteur.
# While you have 6+ cards, this unit gets +2/+0." With 6 cards in hand, deployed Pre Vizsla (4 power) is 6/?
# and has Saboteur.
## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:TWI_010}
P1OnlyActions: true
WithP1Hand: [SOR_046 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_010
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
