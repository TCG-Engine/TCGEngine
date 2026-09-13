# PreventsExactlyFiveDamageThenDefeatsItselfAndDraws
#// HMW_081 — "Fortify. If attached base would be dealt 5 or more damage, prevent that damage. If you do,
#// defeat this upgrade and draw a card." The generator sits on P2's base and P1 attacks it with ASH_061
#// (a vanilla 5/5), so 5 is the inclusive edge of the threshold. "Draw a card" belongs to the upgrade's
#// controller — P2 — and the defeated non-token upgrade goes to P2's discard.

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
P2BASE:UPGRADECOUNT:0
P2DISCARDCOUNT:1
P2HANDCOUNT:1
P2DECKCOUNT:1

---

# PreventsMoreThanFiveDamageToo
#// "5 or MORE" — ASH_061 carrying SOR_120 Academy Training (+2/+2) attacks for 7. Guards a threshold
#// written as == 5 instead of >= 5.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: ASH_061:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2BASE:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# FourDamageIsBelowTheThresholdAndLands
#// Just under the threshold: LAW_124 (4/7) deals its 4 in full, the generator stays attached, no draw.
#// Nothing is consumed — this is a conditional prevention, not a one-shot shield.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: LAW_124:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2BASE:UPGRADECOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:2

---

# IndirectDamageIsUnpreventableAndIsNotConsumed
#// A PREVENTION must skip unpreventable damage. JTL_234 deals 5 indirect damage to a player; P2 controls
#// no units, so all 5 auto-assign to their base. It lands in full, the generator is NOT defeated, and no
#// card is drawn — so a later preventable 5 would still be stopped.
#// ("Deal N indirect damage to a player" first asks WHICH player — the You&Opponent OPTIONCHOOSE.)

## GIVEN
CommonSetup: yyk/yyk/{myResources:3;myhandCardIds:JTL_234}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5
P2BASE:UPGRADECOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:2

---

# RegroupDeckOut_TwoUndrawnCardsAreONE_SixDamageEvent
#// ⚠ USER RULING 2026-09-07: the damage for failing to draw is ONE event of 3 per undrawn card, not one
#// event PER card. Drawing 2 from an empty deck is a single instance of 6 — so a 5-or-more prevention
#// sees 6 and stops it. Two separate 3-damage events would slip under every threshold in the game.
#// The distinction is per DRAW INSTRUCTION, not per card: three separate "draw a card" instructions on
#// an empty deck are still three events of 3 (LAW_222 Tobias Beckett's deployed side relies on exactly
#// that, and is unaffected).
#//
#// THE REPORTED BOARD. P1's base is on 25 damage of 30 with an empty deck and an Alliance Shield
#// Generator attached. The regroup draw asks for 2 cards, deals its single 6, and the generator
#// prevents all of it — then defeats itself and draws a card, which on an empty deck deals 3 that
#// nothing is left to prevent. P1 ends the regroup on 28 of 30, alive, with no generator.
#// Under the old per-card split neither 3 reached the threshold, the generator never fired, and P1's
#// base took 6 and sat on 31 — dead.
## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:25}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_081
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
## EXPECT
P1BASEDMG:28
P1BASE:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# RegroupDeckOut_WithoutTheGenerator_TheFullSixLands
#// The baseline for the section above, on the identical board minus the generator: the same single
#// 6-damage event lands in full, taking P1 from 25 to 31 on a 30-HP base. It also pins that the TOTAL
#// is unchanged by the ruling — only its shape as one event rather than two.
## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:20}
SkipPreGame: true
P1OnlyActions: true
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
## EXPECT
P1BASEDMG:26

---

# SeparateDrawInstructions_StayASeparateThreeEach
#// THE CONTROL for the ruling's scope. The change is per DRAW INSTRUCTION, so it must NOT merge two
#// independent draws into one event. SHD_137 Punishing One's ready-and-draw and the regroup draw are
#// different instructions; here the simpler proof is a single "draw a card" instruction on an empty
#// deck, which stays a 3 and therefore stays UNDER the generator's threshold — the generator survives
#// and the 3 lands.
#// Without this, merging every draw in a phase into one event would pass both sections above.
## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:2}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_081
WithP1GroundArena: SHD_095:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1BASE:UPGRADECOUNT:1

---

# OverwhelmExcess_OfFive_IsPrevented_TheDefenderStillDies
#// Overwhelm's excess is its own damage instance to the base. LAW_177 Son-tuul Berserkers (8/5, Overwhelm)
#// attacks SOR_095 (3/3): 3 kills the Marine and 5 spills onto P2's base — exactly the threshold, so it is
#// prevented; the generator is defeated and P2 draws. The defender still takes its share and dies.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: LAW_177:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P2BASE:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# OverwhelmExcess_BelowFive_Lands
#// The same Berserkers into SOR_164 Wampa (4/5): 3 excess reaches the base — under the threshold, so it
#// lands and the generator stays.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: LAW_177:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P2BASE:UPGRADECOUNT:1
P2HANDCOUNT:0

---

# OverwhelmDirect_DefenderDiesBeforeCombatDamage_AllOfItIsPrevented
#// SOR_135 Emperor Palpatine (Overwhelm, a Force unit) carries SOR_137 Fallen Lightsaber: "On Attack: Deal 1
#// damage to each ground unit the defending player controls." That kills the 1-HP SOR_128 defender before
#// combat damage, so ALL of Palpatine's combat damage goes to the base as one instance — prevented.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: SOR_135:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SOR_128:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P2BASE:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# TwoSubFiveInstancesInOneAttack_TotalFive_NotPrevented
#// The threshold is PER INSTANCE. ASH_253 Yellow Aces Bomber with an Experience token (3/5) attacks P2's
#// base; its On Attack ("If this unit is upgraded, deal 2 damage to a base") pings P2's base for 2 and the
#// combat damage is 3 — 5 in total, but no single instance reaches 5. All of it lands; the generator stays.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1SpaceArena: ASH_253:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5
P2BASE:UPGRADECOUNT:1
P2HANDCOUNT:0

---

# EmptyDeck_TheDrawFails_ThreeDamageLands
#// "If you do, defeat this upgrade and draw a card." The prevention and the self-defeat happen; the draw
#// on an EMPTY deck deals 3 to P2's base instead — a separate, smaller instance, and the generator is
#// already gone, so it lands.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP2BaseUpgrade: HMW_081
WithP1GroundArena: ASH_061:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2BASE:UPGRADECOUNT:0
P2DISCARDCOUNT:1
P2HANDCOUNT:0
