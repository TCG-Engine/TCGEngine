# LosesAbilitiesGainsGrit
#// SEC_054 Exiled from the Force (upgrade) — Attached unit loses the Force trait and all abilities except
#//   for Grit; attached unit gains Grit. Host SOR_049 (Force/Jedi, innate Sentinel) loses Sentinel and
#//   gains Grit. (Force-trait loss is wired via _SWUUnitHasTrait, mirroring the NO_TRAIT path.)

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_049:1:0
WithP1GroundArenaUpgrade: 0:SEC_054

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# RemovesForceTrait
#// SEC_054 Exiled from the Force — besides "loses all abilities except Grit", the attached unit also
#//   loses the Force trait. Host SOR_049 (Obi-Wan, traits Force,Jedi; innate Sentinel) with SEC_054 no
#//   longer counts as Force, loses Sentinel, and gains Grit.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_049:1:0
WithP1GroundArenaUpgrade: 0:SEC_054

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTTRAIT:Force
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# GainedGritScalesWithDamage
#// SEC_054 Exiled from the Force — the granted Grit gives +1/+0 for each damage on the unit. Host SOR_046
#//   (3/7, no innate keywords) seeded with 3 damage + SEC_054 gains Grit → power 3 + 3 = 6.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:3
WithP1GroundArenaUpgrade: 0:SEC_054

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6

---

# MultipleCopies_StillGrit
#// SEC_054 Exiled from the Force — two copies attached still leave the unit with exactly Grit (each copy
#//   grants Grit; "loses all abilities except Grit" doesn't strip the other copy's Grit). Host SOR_046
#//   (3/7) with 3 damage + 2x SEC_054 → Grit, power 6.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:3
WithP1GroundArenaUpgrade: 0:SEC_054
WithP1GroundArenaUpgrade: 0:SEC_054

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6

---

# KeepsInnateGrit
#// SEC_054 Exiled from the Force — a unit that already has innate Grit keeps it (Grit is the one ability
#//   that survives). Host SOR_032 (Scout Bike Pursuer, innate Grit) with 2 damage + SEC_054 → still Grit,
#//   power 1 + 2 = 3.

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_032:1:2
WithP1GroundArenaUpgrade: 0:SEC_054

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3
