# SOR_222 Waylay — upgrades on a bounced unit are defeated (CR 9.3)
# Non-token upgrade (LOF_215) goes to the upgrade owner's discard

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:1
