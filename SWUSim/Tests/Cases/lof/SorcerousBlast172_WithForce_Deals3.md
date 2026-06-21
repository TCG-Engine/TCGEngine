# LOF_172 Sorcerous Blast — with the Force: P1 controls their Force token, so they Use the Force (the
# token is defeated → P1NOFORCE afterward) and the "If you do" resolves: deal 3 damage to a unit. The
# only unit in play is P2's SOR_046 (3/7), so the target auto-resolves and it takes 3 damage (survives).

## GIVEN
CommonSetup: rrw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LOF_172

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:1
P1NODECISION
