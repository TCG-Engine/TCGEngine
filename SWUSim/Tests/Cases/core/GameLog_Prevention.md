# ShieldPreventsAbilityDamage_HasALine
#// Game-log follow-up (2026-09-11). Damage a Shield token prevented left no line at all: the ability's
#// damage line is skipped (0 dealt) and nothing said why. LOF_172 Sorcerous Blast: "Use the Force. If you
#// do, deal 3 damage to a unit."

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_172
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
LOGCONTAINS:P2's [[SOR_095|Battlefield Marine]]'s Shield token prevented the damage ([[LOF_172|Sorcerous Blast]])
LOGCOUNT:0:dealt 3 damage

---

# ShieldPreventsCombatDamage_NotedOnTheAttackLine
#// In combat the ATTACK summary carries the numbers ("dealt 0, took 3"); the Shield is a note on that line.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
LOGCONTAINS:dealt 0, took 3 — P2's [[SEC_080|Imperial Dark Trooper]]'s Shield token prevented the damage
#// Only the ATTACK-line note — no separate Shield line printed above it.
LOGCOUNT:1:Shield token prevented the damage

---

# ShieldOnTheAttacker_NotedToo
#// The DEFENDER's counter-damage into a shielded attacker (a separate branch in the combat resolver).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
LOGCONTAINS:P1's [[SOR_046|Consular Security Force]]'s Shield token prevented the damage

---

# RoseTico_DefeatsAShield_NotAPrevention
#// SHD_045 Rose Tico: "On Attack: You may defeat a Shield token on a friendly unit. If you do, give 2
#// Experience tokens to that unit." Defeating a Shield outright is its own line (not "prevented").

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
LOGCONTAINS:P1's [[SHD_045|Rose Tico]] defeated a Shield token on itself
LOGCOUNT:0:prevented the damage

---

# ImmuneToAbilityDamage_Refusal
#// SHD_187 Lurking TIE Phantom: "can't be captured, damaged, or defeated by enemy card abilities." The damage
#// is refused at resolution (the Phantom stays in the pool) — and now says so. (Fixture from
#// shd/LurkingTiePhantom.md.) SHD_178 Daring Raid: "Deal 2 damage to a unit or base."

## GIVEN
CommonSetup: yyk/rrk/{theirResources:1}
WithActivePlayer: 2
WithP1SpaceArena: SHD_187:1:0
WithP2Hand: SHD_178

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
LOGCONTAINS:P2's [[SHD_178|Daring Raid]] couldn't deal damage to P1's [[SHD_187|Lurking TIE Phantom]]

---

# ShieldOnTheAttacker_ShootFirst_DefenderSurvives
#// Under Shoot First the combat resolver applies a SURVIVING defender's counter-damage in its own branch,
#// with its own inline Shield check (not _SWUShieldOrReduceCombat). SOR_217 Shoot First: "Attack with a unit.
#// It gets +1/+0 for this attack and deals its combat damage before the defender." The Marine (4 power) hits
#// SOR_046 Consular Security Force (3/7), which survives and strikes back 3 into the Marine's Shield.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_217}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:4
LOGCONTAINS:dealt 4, took 0 — P1's [[SOR_095|Battlefield Marine]]'s Shield token prevented the damage

---

# Amidala_AbilityDamage_PreventedAndTheSacrificeIsHers
#// SEC_101 Queen Amidala: "If damage would be dealt to this unit, you may defeat a friendly unit that shares
#// a Trait with her. If you do, prevent that damage." Before: only the animation — and the sacrifice was
#// credited to the ENEMY card ("P1's Contempt for Culture defeated P2's …"), since that was still the
#// source. (Fixture from sec/QueenAmidala_ChampioningHerPeople.md.)

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:SEC_246}
P1OnlyActions: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
LOGCONTAINS:P2's [[SEC_101|Queen Amidala]] defeated P2's [[SEC_118|
LOGCONTAINS:Damage to P2's [[SEC_101|Queen Amidala]] was prevented ([[SEC_101|Queen Amidala]])
LOGCOUNT:0:[[SEC_246|Contempt for Culture]] defeated

---

# Amidala_CombatCounterDamage_NotedOnTheAttackLine
#// The combat leg: the prevention is consumed while the ATTACK line's numbers are being built, so it is a
#// note there ("took 0 — damage to P1's Queen Amidala was prevented (Queen Amidala)").

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SEC_101:1:0
WithP1GroundArena: SEC_118:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
LOGCONTAINS:damage to P1's [[SEC_101|Queen Amidala]] was prevented ([[SEC_101|Queen Amidala]])
LOGCONTAINS:P1's [[SEC_101|Queen Amidala]] defeated P1's [[SEC_118|

---

# BasePrevention_CloseTheShieldGate_NotedOnTheAttackLine
#// JTL_074 Close the Shield Gate: "the next time damage would be dealt to this base this phase, prevent it."
#// It only flashed a message on ONE client — the opponent saw "attacked P2's base for 0 damage" with no
#// reason. (Fixture from jtl/CloseTheShieldGate.md.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:JTL_074}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:1:BASE

## EXPECT
P2BASEDMG:2
LOGCONTAINS:for 0 damage — 2 damage to P2's base was prevented ([[JTL_074|Close the Shield Gate]])
LOGCOUNT:1:was prevented

---

# BasePrevention_AtAttin_CapsAtFour
#// ASH_070 At Attin Safety Droid: "If your base would be dealt more than 4 damage, prevent all but 4."
#// (Fixture from ash/AtAttinSafetyDroid.md.)

## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_070:1:0
WithP2GroundArena: SOR_038:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
LOGCONTAINS:for 4 damage — 1 damage to P1's base was prevented ([[ASH_070|At Attin Safety Droid]])

---

# BasePrevention_AllianceShieldGenerator_ItsOwnDefeatAndDraw
#// HMW_081 Alliance Shield Generator: "If attached base would be dealt 5 or more damage, prevent that
#// damage. If you do, defeat this upgrade and draw a card." Its defeat and draw are ITS lines — not the
#// attacker's (there is no attacking "ability" to credit) — and it defeats "itself", on P2's base.
#// (Fixture from hmw/AllianceShieldGenerator.md.)

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: ASH_061:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
LOGCONTAINS:5 damage to P2's base was prevented ([[HMW_081|Alliance Shield Generator]])
LOGCONTAINS:P2's [[HMW_081|Alliance Shield Generator]] defeated itself (on P2's base)
LOGCONTAINS:P2 drew 1 card ([[HMW_081|Alliance Shield Generator]])
