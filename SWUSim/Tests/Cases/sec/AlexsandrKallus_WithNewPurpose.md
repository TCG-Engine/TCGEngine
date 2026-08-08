# RaidPassive_UniqueWhileInitiative
#// SEC_155 Alexsandr Kallus — "While you have the initiative, each OTHER friendly unique unit gains Raid 2."
#//   With Kallus in play and P1 holding initiative, the unique SEC_065 attacks the base for 4 + Raid 2 = 6.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_155:1:0
WithP1GroundArena: SEC_065:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:6
P1NODECISION

---

# WhenPlayed_Deal2EachOf3
#// SEC_155 Alexsandr Kallus (Unit, cost 7) — When Played: deal 2 to each of up to 3 ground units.

## GIVEN
CommonSetup: rrw/rrk/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_155

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:2:DAMAGE:2
P1NODECISION

---

# Constant_NotRaidToSelf
#// SEC_155 Alexsandr Kallus — the Raid 2 grant is "each OTHER friendly unique unit", so Kallus never buffs
#//   himself. With initiative, Kallus (6 power) attacks the base → 6 (no self Raid).

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_155:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1NODECISION

---

# Constant_NotRaidToNonUnique
#// SEC_155 Alexsandr Kallus — the grant only applies to UNIQUE units. With Kallus in play and initiative held,
#//   the non-unique SOR_095 Battlefield Marine (3 power) attacks the base → 3 (no Raid).

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_155:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION

---

# Constant_NoRaidWithoutInitiative
#// SEC_155 Alexsandr Kallus — the grant only applies "while you have the initiative". P1 starts with
#//   initiative; the non-unique Marine attacks the base for 3, then P2 claims the initiative. Now the unique
#//   SOR_045 Yoda (2 power) attacks the base and gets NO Raid → +2, base totals 3+2 = 5.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1GroundArena: SEC_155:1:0
WithP1GroundArena: SOR_045:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:2:BASE
- P2>Claim
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:5

---

# Constant_NotRaidToEnemyUnique
#// SEC_155 Alexsandr Kallus — the grant is "friendly" only; enemy unique units never benefit. P2 holds the
#//   initiative and its unique SOR_109 Colonel Yularen (2 power) attacks P1's base → 2 (no Raid from Kallus).

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_155:1:0
WithP2GroundArena: SOR_109:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2

---

# WhenPlayed_Deal2_ChooseFewer
#// SEC_155 Alexsandr Kallus — "up to 3" lets the controller hit fewer. Three enemy SOR_046 in play; choose
#//   only two → those two take 2 each, the third is untouched.

## GIVEN
CommonSetup: rrw/rrk/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_155

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:2:DAMAGE:0
P1NODECISION

---

# WhenPlayed_Deal2_ChooseNothing
#// SEC_155 Alexsandr Kallus — "up to 3" also allows choosing zero targets; no unit is damaged.

## GIVEN
CommonSetup: rrw/rrk/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_155

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Constant_NoRaidToAnEnemyNonUniqueEvenWhenPilotedAndWeHoldInitiative
#// SEC_155 Alexsandr Kallus — the grant is "each OTHER FRIENDLY unique unit", so both halves have to
#// fail for an enemy non-unique: it is neither friendly nor unique. Attaching a Pilot upgrade to it does
#// not change either. With P1 holding initiative, P2's SOR_095 wearing JTL_046 Paige Tico still has no
#// Raid, while P1's own unique SEC_065 does.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SEC_155:1:0
WithP1GroundArena: SEC_065:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:JTL_046

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
