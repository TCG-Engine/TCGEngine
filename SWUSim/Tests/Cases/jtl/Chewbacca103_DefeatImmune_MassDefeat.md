# JTL_103 Chewbacca — "This unit can't be defeated ... by enemy card abilities." P1 plays Nebula
# Ignition (JTL_080: defeat each unit that isn't upgraded). The enemy SEC_080 is defeated, but Chewbacca
# survives despite being unupgraded — he's immune to defeat by an enemy card ability.

## GIVEN
CommonSetup: bbw/rrk/{myResources:12;handCardIds:JTL_080}
P1OnlyActions: true
WithP2GroundArena: JTL_103:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:JTL_103
