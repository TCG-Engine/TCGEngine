# TS26_075 Jango Fett — "While an enemy unit has attacked your base this phase, this unit gains Ambush."
# After P2's SEC_080 attacks P1's base, Jango gains Ambush.
## GIVEN
CommonSetup: yyk/rrk
WithP1GroundArena: TS26_075:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
