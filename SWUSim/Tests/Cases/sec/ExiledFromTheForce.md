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


---

# NoGrit_WhenASecondEffectAlsoBlanksTheUnit
#// SEC_054 Exiled from the Force — "loses all abilities EXCEPT for Grit" spares Grit from ITS OWN
#// blanking. But a SECOND full-blanking effect has no such exception, so Grit must go too. P1's SOR_049 Obi-Wan Kenobi (innate Sentinel) wears Exiled and has Grit; P2's Galen Erso (SEC_046) then names
#// "Obi-Wan Kenobi" — SOR_049's actual title — blanking every ability of a card P2's opponent owns. The host ends with NEITHER
#// Sentinel NOR Grit.
#// REGRESSION GUARD: the Grit exception used to be unconditional ("never suppress GRIT while Exiled is
#// attached"), so it could not tell "blanked BY Exiled" from "blanked by Exiled AND something else".
## GIVEN
CommonSetup: bbk/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 4
WithP2Hand: SEC_046
WithP1GroundArena: SOR_049:1:0
WithP1GroundArenaUpgrade: 0:SEC_054
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Obi-Wan Kenobi
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# RemovesConstantAbilities_OwnAndGranted
#// SEC_054 Exiled from the Force — "loses ... all abilities except for Grit" strips CONSTANT abilities,
#// both the unit's own and any granted to it by another upgrade. LOF_063 Oggdo Bogdo normally "can't
#// attack unless it's damaged" and here carries TWI_220 Shadowed Intentions ("can't be captured,
#// defeated, or returned to hand by enemy card abilities"). With Exiled attached, both are gone: the
#// undamaged Oggdo Bogdo attacks freely for 5, and P2's SOR_078 Vanquish defeats it outright.

## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 1
WithP2Resources: 5
WithP1GroundArena: LOF_063:1:0
WithP1GroundArenaUpgrade: 0:SEC_054
WithP1GroundArenaUpgrade: 0:TWI_220
WithP2Hand: SOR_078

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0

---

# GrantedGritCanItselfBeRemovedByAKeywordStrip
#// SEC_054 Exiled from the Force — the Grit it hands back is an ordinary keyword, so an effect that
#// removes keywords takes it away too. LOF_049 Jedi Guardian carries 4 damage and Exiled, so its Grit
#// makes it 4 + 4 = 8 power. P2's SEC_185 Screeching TIE Fighter attacks and strips its keywords for
#// the phase: Grit goes, and the Guardian attacks for just its printed 4.

## GIVEN
CommonSetup: bbw/yyk
WithActivePlayer: 2
WithP1GroundArena: LOF_049:1:4
WithP1GroundArenaUpgrade: 0:SEC_054
WithP2SpaceArena: SEC_185:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:4
