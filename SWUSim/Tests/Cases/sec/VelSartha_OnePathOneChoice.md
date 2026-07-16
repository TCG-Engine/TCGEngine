# ExhaustedDefenderMinus2
#// SEC_224 Saw's Renegades (Ground, 4/6) — Raid 2 + "Each exhausted enemy unit gets -2/-0 while
#//   defending." SEC_224 attacks an EXHAUSTED SOR_046 (3/7): the defender is reduced to 1 power, so it
#//   counters for only 1; SEC_224's 4+2(Raid)=6 lands on SOR_046 (survives, 7 HP).

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_224:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# ReadyDefenderNoDebuff
#// SEC_224 — the -2/-0 applies only to EXHAUSTED enemy defenders. A READY SOR_046 (3/7) counters at its
#//   full 3 power, so SEC_224 takes 3 (its 6 lands but SOR_046 survives at 7 HP).

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_224:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:6
