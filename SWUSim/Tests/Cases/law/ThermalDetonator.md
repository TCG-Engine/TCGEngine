# ExhaustedDefeat_NoBlast
#// LAW_201 Thermal Detonator — guard: if the host was EXHAUSTED when defeated, the granted When Defeated
#// does NOT fire. P1's host (SEC_080 + detonator, EXHAUSTED) is killed by SOR_039; no enemy damage, so
#// both P2 ground units survive.

## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2

---

# ReadyDefeatBlastsEnemies
#// LAW_201 Thermal Detonator (Upgrade, +1/+1) — grants "When Defeated: If this unit was ready, deal 2
#// damage to each enemy ground unit." P1's host (SEC_080 + detonator = 4/4, READY) is attacked and killed
#// by P2's SOR_039 (8/8) while still ready (a defender). Its When Defeated deals 2 to each P2 ground unit:
#// SOR_039 (8/8) survives, SOR_128 (3/1) dies → P2 keeps only SOR_039.

## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_039
