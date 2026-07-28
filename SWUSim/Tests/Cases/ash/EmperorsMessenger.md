# Support_ReadyResource
#// ASH_189 Emperor's Messenger (Ground, 0/3, Support) — the On Attack "ready a resource" is lent to the
#// Support attacker. Messenger is played from hand; the friendly Wampa (SOR_164) is chosen to attack and
#// gains the lent On Attack, readying one of P1's exhausted resources.
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:ASH_189}
WithP1Resources: 1:SOR_046:1,2:SOR_046:1,3:SOR_046:0
WithP1GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1RESAVAILABLE:3
P2BASEDMG:4
