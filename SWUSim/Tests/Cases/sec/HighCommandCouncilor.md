# NoRaidAlone
#// SEC_249 — without another Official unit in play, the conditional Raid 2 is off, so it attacks the
#//   base for its base 1 power.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_249:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1

---

# RaidWithAnotherOfficial
#// SEC_249 High Command Councilor (Ground, 1/4) — "While you control another Official unit, this unit
#//   gains Raid 2." With SEC_041 (an Official) also in play, SEC_249 attacks P2's base for 1+2 = 3.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_249:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
