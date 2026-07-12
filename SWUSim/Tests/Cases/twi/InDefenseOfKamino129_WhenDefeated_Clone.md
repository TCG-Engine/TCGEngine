# TWI_129 In Defense of Kamino — the granted "When Defeated: Create a Clone Trooper token" fires when a
# marked Republic unit dies. TWI_109 (3/3 Republic) attacks SOR_046 (3/7) and dies to the 3 counter; its
# granted When Defeated creates a Clone Trooper (TWI_T02) in its place.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:TWI_129}
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENAUNIT:0:DAMAGE:3
