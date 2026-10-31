# Sabine_OwnerTest_From28_TwoUses_Draw
#// CR 6.3: "In a case where both players' bases reach 0 remaining HP at the same time, the game ends in a draw."
#// Owner's test (2026-10-30): two SOR_014 Sabine Wren leaders ("Action [exhaust]: Deal 1 damage to each base"), both
#// bases at 28/30. P1 uses Sabine (29/29, the game goes on), then P2 uses Sabine: both bases reach 30 in the SAME
#// ability, so the game is a draw — not a win for whichever seat the code happened to check first.
#// COVERAGE: draw paths = Sabine front Action, Reckless Gunslinger When Played, The Chaos of War, Final Showdown x2,
#//           regroup deck-out · NEGATIVE = Sabine_OneBaseLethal_IsAWin + RegroupDeckOut_OneBaseLethal_IsAWin (only one
#//           base at 0 = a normal win) ·
#//           undo = Draw_UndoBehavesLikeAWin
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:28;theirBaseDamage:28}

## WHEN
- P1>UseLeaderAbility
- P2>UseLeaderAbility

## EXPECT
DRAW
P1BASEDMG:30
P2BASEDMG:30

---

# Sabine_OwnerTest_FirstUse_GameGoesOn
#// The owner's test, first half: from 28/28 one Sabine Action leaves both bases at 29 and the game live.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:28;theirBaseDamage:28}

## WHEN
- P1>UseLeaderAbility

## EXPECT
NOWINNER
P1BASEDMG:29
P2BASEDMG:29

---

# Sabine_BothAt29_OneUseDraws
#// The draw on its own: both bases at 29, one Sabine Action takes both to 30 together.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:29;theirBaseDamage:29}

## WHEN
- P1>UseLeaderAbility

## EXPECT
DRAW

---

# Sabine_OneBaseLethal_IsAWin
#// NEGATIVE: only the caster's own base reaches 0 — P1 at 29, P2 at 28. Sabine takes P1 to 30 and P2 to 29, so P1
#// loses: a draw needs BOTH bases.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:29;theirBaseDamage:28}

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2WIN
P2BASEDMG:29

---

# RecklessGunslinger_BothAt29_Draws
#// SHD_160 Reckless Gunslinger — "When Played: Deal 1 damage to each base." One ability, both bases lethal = draw.
## GIVEN
CommonSetup: rrw/rrw/{myResources:1;myBaseDamage:29;theirBaseDamage:29}
P1OnlyActions: true
WithP1Hand: SHD_160

## WHEN
- P1>PlayHand:0

## EXPECT
DRAW

---

# ChaosOfWar_BothBasesLethal_Draws
#// SHD_159 The Chaos of War — "Deal damage to each player's base equal to the number of cards in that player's hand."
#// One event: P1 holds 1 card after casting (base 29 → 30), P2 holds 2 (base 28 → 30). Both at 0 together = draw.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;myBaseDamage:29;theirBaseDamage:28}
P1OnlyActions: true
WithP1Hand: [SHD_159 SOR_095]
WithP2Hand: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
DRAW

---

# FinalShowdown_BothPlayers_Draws
#// SHD_208 Final Showdown — "At the start of the regroup phase, you lose the game." Both players played one, so both
#// lose at the same timing point: a draw.
## GIVEN
CommonSetup: yyk/yyk
WithP1Resources: 6
WithP2Resources: 6
WithP1Hand: SHD_208
WithP2Hand: SHD_208

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
DRAW

---

# RegroupDeckOut_BothBasesLethal_Draws
#// CR 5.4.b "each player draws 2 cards" is ONE instruction. Both decks are empty, so each player takes 3 x 2 = 6 to their
#// base (one event per player, user ruling 2026-09-07), from 27 → 33 on 30-HP bases. Both bases die in the same step:
#// a draw.
## GIVEN
CommonSetup: yyk/yyk/{myBaseDamage:27;theirBaseDamage:27}
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
DRAW

---

# RegroupDeckOut_OneBaseLethal_IsAWin
#// NEGATIVE for the regroup bracket: only P2's base can die (P1 at 0 damage). P2 loses.
## GIVEN
CommonSetup: yyk/yyk/{theirBaseDamage:27}
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
P1WIN

---

# Draw_UndoBehavesLikeAWin
#// A draw is a game-over state exactly like a win. Today an Undo pressed after a WIN leaves the game decided (probed
#// 2026-10-30: base stays at 30, winner stays set), so after a DRAW it must do the same — no half-undone state where
#// the bases rewind but the result survives, or vice versa.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:29;theirBaseDamage:29}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility
- P1>Undo

## EXPECT
DRAW
P1BASEDMG:30
P2BASEDMG:30

---

# TeamSuns_BothTeamsWipedTogether_Draws
#// Team Suns: one "each base" event defeats all four bases at once, so both teams are wiped out together. Nobody is left
#// to win — a DRAW for all four (was recorded as every seat winning).
## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:SHD_160;myBaseDamage:29;theirBaseDamage:29}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:29
WithP4Base: SOR_019:29

## WHEN
- P1>PlayHand:0

## EXPECT
DRAW
NOGAMEWINNER

---

# FFA_EveryLiveSeatEliminatedTogether_ShareTheVictory
#// Twin Suns free-for-all: one "each base" event defeats every live base at once. CR 12.7.3 — players tied for the most
#// remaining HP share the victory — so all three share it. (Was: eliminating seats one by one left a lone "survivor"
#// who was scored the winner before its own base, defeated by the same event, was processed.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:SHD_160;myBaseDamage:29;theirBaseDamage:29}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:29

## WHEN
- P1>PlayHand:0

## EXPECT
GAMEWINNERS:1,2,3

---

# FFA_TwoOfThreeEliminatedTogether_SurvivorWins
#// NEGATIVE: the same event defeats two of the three bases; P1 (no damage) survives and wins as the last player standing.
## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:SHD_160;theirBaseDamage:29}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:29

## WHEN
- P1>PlayHand:0

## EXPECT
GAMEWINNERS:1

---

# ConfidenceInVictory_BothPlayersQualify_Draws
#// SEC_145 Confidence in Victory — "At the start of the regroup phase, if you are the only player who controls units in
#// that arena, you win the game." Both players cast it: P1 is alone in the Ground arena, P2 alone in Space. Both win
#// conditions are met at the same timing point, so neither player alone won — a draw (was: seat 1 always won, because
#// the check visited seat 1 first).
## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1Resources: 10
WithP2Resources: 10
WithP1Hand: SEC_145
WithP2Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P2>PlayHand:0
- P2>AnswerDecision:Space
- P1>Pass
- P2>Pass

## EXPECT
DRAW

---

# ConfidenceInVictory_OneQualifies_StillWins
#// NEGATIVE: both cast it, but only P1's condition holds (P2 chose Space and has no space units) — P1 wins as before.
## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1Resources: 10
WithP2Resources: 10
WithP1Hand: SEC_145
WithP2Hand: SEC_145
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P2>PlayHand:0
- P2>AnswerDecision:Space
- P1>Pass
- P2>Pass

## EXPECT
P1WIN
