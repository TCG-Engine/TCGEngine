# LOF_172 Sorcerous Blast — "Use the Force (lose your Force token). If you do, deal 3 damage to a unit."
# Without the Force: P1 doesn't control a Force token, so they can't Use the Force; the "If you do" rider
# fails and the event fizzles — NO damage is dealt (and no target decision is offered). The event is
# still played and goes to P1's discard.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LOF_172

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
P1NODECISION
