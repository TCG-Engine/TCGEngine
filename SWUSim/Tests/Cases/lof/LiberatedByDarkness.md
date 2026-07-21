# TakeControl
#// LOF_189 Liberated by Darkness — Use the Force; if you do, take control of a non-leader unit (its owner
#// takes control back at regroup). P1 uses the Force and steals SOR_046 into its own arena.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:0
P1NOFORCE

---

# StealEnemy_RevertsAtRegroup
#// LOF_189 — P1 uses the Force to steal the enemy SOR_046 (arena flips to P1). Then both players pass into
#// the regroup phase, where the delayed effect returns the unit to its owner (P2). Arena flips back to P2.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046,SOR_046,SOR_046
WithP2Deck: SOR_046,SOR_046,SOR_046

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:0
P1NOFORCE

---

# NoForce_NothingHappens
#// LOF_189 — with no Force token, the event fizzles: no unit is taken. It resolves to the discard and the
#// enemy unit stays in P2's arena.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
