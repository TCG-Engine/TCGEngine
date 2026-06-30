# JTL_103 Chewbacca (Pilot) — "Attached unit gains: 'This unit can't be defeated ... by enemy card
# abilities.'" P2's SOR_237 carries the Chewbacca pilot. P1 plays Direct Hit (JTL_078: defeat a
# non-leader Vehicle) at SOR_237; it fizzles because the host has the Chewbacca-granted defeat immunity.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:JTL_078}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
