# NoForce_Fizzles
#// LOF_172 Sorcerous Blast — "Use the Force (lose your Force token). If you do, deal 3 damage to a unit."
#// Without the Force: P1 doesn't control a Force token, so they can't Use the Force; the "If you do" rider
#// fails and the event fizzles — NO damage is dealt (and no target decision is offered). The event is
#// still played and goes to P1's discard.

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

---

# WithForce_Deals3
#// LOF_172 Sorcerous Blast — with the Force: P1 controls their Force token, so they Use the Force (the
#// token is defeated → P1NOFORCE afterward) and the "If you do" resolves: deal 3 damage to a unit. The
#// only unit in play is P2's SOR_046 (3/7), so the target auto-resolves and it takes 3 damage (survives).

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
