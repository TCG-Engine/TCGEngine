# DamagedNoSentinel
#// SEC_063 — the Sentinel grant is conditional on being undamaged. A damaged SEC_063 does NOT have
#//   Sentinel.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_063:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# UndamagedSentinel
#// SEC_063 Rotunda Senate Guards (Ground, 4/5) — "While this unit is undamaged, it gains Sentinel." An
#//   undamaged SEC_063 has Sentinel.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_063:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
