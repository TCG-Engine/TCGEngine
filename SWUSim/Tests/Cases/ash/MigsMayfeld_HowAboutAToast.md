# NotUpgraded_Deal1ToDefendingUnit
#// ASH_168 Migs Mayfeld (2/3) — "On Attack: Deal 1 damage to the defending unit. If this unit is
#// upgraded, deal 2 instead." Not upgraded: Migs attacks a 3/7 wall (SOR_046) → defender takes 1
#// (on-attack) + 2 (Migs combat power) = 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: ASH_168:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Upgraded_Deal2ToDefendingUnit
#// Upgraded (SOR_069 Resilient, +0/+3 — HP-only, so Migs stays 2 power): the On-Attack deals 2 instead
#// of 1 → defender takes 2 (on-attack) + 2 (combat) = 4. The 4-vs-3 delta vs the not-upgraded case
#// proves the "if upgraded, deal 2 instead" branch.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: ASH_168:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NotUpgraded_AttackBase_NoExtra
#// The On-Attack targets "the defending UNIT" — a base is not a unit, so attacking the base deals NO
#// on-attack damage. Not upgraded: the base takes only Migs's 2 combat power (not 3).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: ASH_168:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# Upgraded_AttackBase_NoExtra
#// Even upgraded (SOR_069 +0/+3, Migs still 2 power), a base attack deals no on-attack damage → the
#// base takes only the 2 combat power (not 4). Confirms the rider never hits bases.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: ASH_168:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
