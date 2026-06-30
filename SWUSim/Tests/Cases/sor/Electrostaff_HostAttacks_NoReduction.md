# SOR_071 Electrostaff — the -1/-0 applies ONLY while the host is DEFENDING. When the Electrostaff host
# (SOR_095 + upgrade → 5/5) ATTACKS, it deals its full 5 to the defender (no self-reduction).

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_071
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
