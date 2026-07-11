# SHD_229 Ma Klounkee (1-cost event, Cunning) — "Return a friendly non-leader Underworld unit to its
# owner's hand. If you do, deal 3 damage to a unit." The friendly LAW_124 (Underworld) is returned to P1's
# hand, then P1 deals 3 to the enemy SOR_046 (7 HP → 3 damage).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
