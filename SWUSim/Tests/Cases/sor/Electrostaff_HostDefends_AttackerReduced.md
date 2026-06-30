# SOR_071 Electrostaff (Vigilance upgrade, cost 2, +2/+2, non-Vehicle) — "While attached unit is
# defending, the attacker gets -1/-0." P2's SOR_046 (3/7) carries Electrostaff (→ 5/9). P1's SOR_095
# (3 power) attacks it: the attacker's power is reduced to 2, so the host takes DAMAGE:2 (not 3). The
# host's 5-power counter kills SOR_095.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_071

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:0
