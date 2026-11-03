# WhenPlayed_TheChosenEnemyGetsMinusThree
#// IC27_019 Soulless One, Soulless Fun — Unit (Space) 3/5, cost 4, [Vigilance][Villainy], Separatist/Vehicle/Fighter,
#// unique.
#// Text: When Played: Choose an enemy ground unit. While this unit is in play, the chosen unit gets -3/-0.
#// COVERAGE: offer=Offer_EnemyGroundUnitsOnly (N+1: friendly ground + enemy space excluded) · decline=N/A — "choose",
#//           no "may" · no target=NoEnemyGroundUnit_NothingToChoose · quantity/boundary=this (4 → 1, the OTHER enemy
#//           untouched) + Floor_ThreeGoesToZero + Floor_ATokenOfOneStaysAtZero (never below 0) ·
#//           combat=this (attacks for 1) + Defending_DealsLessBack · value-class=Target_ADeployedLeaderUnit + the token ·
#//           duration=EndsWhenSoullessOneLeavesPlay · blanked source=Blanked_TheDebuffStays (CR 14.3) ·
#//           control=Control_TheChosenUnitChangesSides_StillMinusThree · reqboundary=RequestBoundary_BeforeTheChoice ·
#//           modes=TeamSuns_EitherEnemySeat_NotTheTeammate ("enemy" is team-relative; the far seat's unit is debuffed).
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Modelled on TWI_110 Huyang ("When Played: Choose
#// another friendly unit. While this unit is in play, the chosen unit gets +2/+2"), whose official rulings apply by
#// analogy: the effect lasts until the source LEAVES PLAY and is unchanged by control changes; a captured-and-rescued
#// source does not resume it. Blanking the source does not end it (CR 14.3 — the When Played already resolved).
#//
#// THE POSITIVE. P2 has Battlefield Marine (3) and Super Battle Droid (4). P1 chooses the Droid: it is 1/3, the Marine
#// keeps its 3, and the Droid's attack on P1's base deals 1.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: [SOR_095:1:0 TWI_230:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>AttackGroundArena:1:BASE
## EXPECT
P1SPACEARENAUNIT:0:CARDID:IC27_019
P2GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENAUNIT:0:POWER:3
P1BASEDMG:1

---

# Offer_EnemyGroundUnitsOnly
#// "an ENEMY GROUND unit": two enemy ground units are offered; P1's own ground unit and P2's space unit are not.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_095:1:0 TWI_230:1:0]
WithP2SpaceArena: HMW_178:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# NoEnemyGroundUnit_NothingToChoose
#// Only an enemy SPACE unit: nothing to choose, no dangling decision, and the space unit keeps its power.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: HMW_178:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1SPACEARENAUNIT:0:CARDID:IC27_019
P2SPACEARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:POWER:3

---

# Floor_ThreeGoesToZero
#// Exactly 3: Battlefield Marine goes to 0 and its attack deals nothing. (The positive's 4 → 1 is the other half.)
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P1BASEDMG:0

---

# Floor_ATokenOfOneStaysAtZero
#// Value class + floor: a 1/1 Battle Droid TOKEN is a legal target and goes to 0, never -2 (CR 8.15.4).
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: TWI_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:TWI_T01
P2GROUNDARENAUNIT:0:POWER:0

---

# Defending_DealsLessBack
#// "-3/-0" is its power everywhere, not only while attacking: P1's Wampa attacks the debuffed Droid (1/3) and takes 1.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_019
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: TWI_230:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# Target_ADeployedLeaderUnit
#// Value class: a deployed enemy LEADER unit is an enemy ground unit. P2's Iden Versio (4/4) sits after the Marine.
## GIVEN
CommonSetup: bbk/bbk/{myResources:4; theirLeaderDeployed:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_002
P2GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENAUNIT:0:POWER:3

---

# EndsWhenSoullessOneLeavesPlay
#// "While this unit is in play": P2's Frenzied Tri-Fighters (5/3) attack Soulless One (5 HP) and defeat it; the Droid
#// is back to 4 at once.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: TWI_230:1:0
WithP2SpaceArena: HMW_249:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:POWER:4

---

# Blanked_TheDebuffStays
#// CR 14.3: the When Played already resolved, and its duration is "while this unit is IN PLAY" — blanking Soulless One
#// is not leaving play. JTL_244 There Is No Escape (2, Villainy) makes Soulless One lose all abilities for the round;
#// the Droid stays at 1. 6 resources: 4 + 2. (Mind Trick can't do it: it only exhausts READY units, and Soulless One
#// enters play exhausted.) Proof the blank landed: SEC_140 Hondo Ohnaka's "each other friendly unit gains Raid 1" no
#// longer reaches Soulless One, which "can't gain abilities".
## GIVEN
CommonSetup: bbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [IC27_019 JTL_244]
WithP1GroundArena: SEC_140:1:0
WithP2GroundArena: TWI_230:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Raid
P2GROUNDARENAUNIT:0:POWER:1

---

# Control_TheChosenUnitChangesSides_StillMinusThree
#// "the CHOSEN unit gets -3/-0" follows the unit, not its side: P1 debuffs P2's Hunter For Hire (4/4), then takes control
#// of it with its own Action (any player may use it; P1 defeats a Credit token). Now friendly to Soulless One, it is
#// still 1 power. A reader that looked for the source only among the target's ENEMIES would lose it here.
#// (Paying for Soulless One first offers the Credit as payment — declined, it is kept for the Action.)
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_019
WithP1Credits: 1
WithP2GroundArena: LAW_156:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirGroundArena-0
- P1>UseUnitAbility:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_156
P1GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENACOUNT:0

---

# RequestBoundary_BeforeTheChoice
#// The source's identity rides the decision, not a PHP global: the choice answered in a later request still links.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: [SOR_095:1:0 TWI_230:1:0]
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENAUNIT:0:POWER:3

---

# TeamSuns_EitherEnemySeat_NotTheTeammate
#// TEAM SUNS (seats 1+3 vs 2+4). "ENEMY" is team-relative: both enemy seats' ground units are offered, the teammate's
#// is not. P1 picks seat 4's Super Battle Droid, which is debuffed on the far seat's board.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_164:1:0
WithP4GroundArena: TWI_230:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:p2GroundArena-0&p4GroundArena-0

---

# TeamSuns_TheFarSeatsUnitIsDebuffed
#// Same board, the choice answered: seat 4's Droid is 1, seat 2's Marine and the teammate's Wampa keep theirs.
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_164:1:0
WithP4GroundArena: TWI_230:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4GroundArena-0
## EXPECT
P4GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:POWER:3
P3GROUNDARENAUNIT:0:POWER:4

---

# TeamSuns_TwoSoullessOnes_Stack
#// Uniqueness is per player, so P1 and teammate P3 may each control a Soulless One. Both choose seat 2's Super Battle
#// Droid: two separate effects, -6/-0 → 0. A yes/no reader would leave it at 1. (Seat 3 has no aspects of its own, so it
#// pays 4 + 4 off-aspect = 8.)
## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Hand: IC27_019
WithP3Hand: IC27_019
WithP3Resources: 8
WithP2GroundArena: TWI_230:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0
- P2>Pass
- P3>PlayHand:0
- P3>AnswerDecision:p2GroundArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:IC27_019
P3SPACEARENAUNIT:0:CARDID:IC27_019
P2GROUNDARENAUNIT:0:POWER:0
