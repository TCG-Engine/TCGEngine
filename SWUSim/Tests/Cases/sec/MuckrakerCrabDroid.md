# ExhaustedCanBeAttacked
#// SEC_135 — the protection applies only while READY. An EXHAUSTED SEC_135 (3 HP) can be attacked: P2's
#//   SOR_046 (3 power) kills it.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_135:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0

---

# ReadyCantBeAttacked
#// SEC_135 Muckraker Crab Droid (Ground, 4/3) — "While this unit is ready, it can't be attacked." P2's
#//   SOR_046 (3 power) tries to attack the READY SEC_135 (3 HP); the attack is blocked, so SEC_135
#//   survives undamaged (would have died if attackable).

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
