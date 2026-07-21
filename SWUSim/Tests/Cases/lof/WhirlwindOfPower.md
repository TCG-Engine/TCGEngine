# ForceMinus3
#// LOF_078 Whirlwind of Power — Give a unit -2/-2 for this phase; if you control a Force unit, -3/-3
#// instead. P1 controls Plo Koon (Force), so the enemy SOR_046 (3/7) gets -3/-3 → 0/4.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_078}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:4

---

# NoForceUnit_Minus2
#// LOF_078 Whirlwind of Power — without a Force unit, the base effect is -2/-2. P1 controls only Green
#// Squadron A-Wing (SOR_141, Rebel/Vehicle, NOT Force). Enemy Wampa (SOR_164, 4/5) gets -2/-2 → 2/3. The
#// prompt targets any unit, friendly or enemy.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_078}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:3

---

# SelectableAnyUnit
#// LOF_078 — the target choice offers every unit in play, friendly or enemy. With P1's Green Squadron A-Wing
#// (mySpaceArena-0), enemy Wampa (theirGroundArena-0) and enemy Lurking TIE Phantom (theirSpaceArena-0), all
#// three are selectable. No Force unit → the -2/-2 prompt.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_078}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Give_a_unit_-2/-2_for_this_phase
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# Minus2ExpiresNextPhase
#// LOF_078 — the -2/-2 lasts only "for this phase". P1 (no Force unit) gives Wampa (SOR_164, 4/5) -2/-2 →
#// 2/3, then both players pass to end the action phase; regroup expiry restores Wampa to its printed 4/5.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_078}
WithActivePlayer: 1
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:5

---

# Minus2DefeatsUnit
#// LOF_078 — the -2/-2 can defeat a small unit. P1 (no Force unit) targets the enemy Lurking TIE Phantom
#// (SHD_187, 2/2): -2/-2 drops it to 0 HP and it is defeated.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_078}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0

---

# ForceUpgradeDoesNotCount_Minus2
#// LOF_078 — the -3/-3 upgrade requires a Force UNIT, not a Force UPGRADE. P1's only unit is Green Squadron
#// A-Wing (SOR_141, non-Force) carrying Size Matters Not (LOF_056, a Force upgrade). That does not make P1
#// control a Force unit, so Wampa (SOR_164, 4/5) gets only -2/-2 → 2/3.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_078}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:LOF_056
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:3

---

# DeployedForceLeader_Minus3
#// LOF_078 — a deployed leader that is a Force unit satisfies the "control a Force unit" clause. Kanan Jarrus
#// (LOF_004, Force/Jedi) is deployed as a ground unit, so Whirlwind gives -3/-3: enemy Wampa (SOR_164, 4/5)
#// → 1/2.

## GIVEN
CommonSetup: bbw/ggk/{myLeader:LOF_004;handCardIds:LOF_078;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_004:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:2
