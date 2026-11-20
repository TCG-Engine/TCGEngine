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
WithP3Base: SOR_019:24
WithP4Base: SOR_019:24

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
WithP3Base: SOR_019:24

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
WithP3Base: SOR_019:24

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

---

# TyYorrick_Sabine_BothDeclined_StillDraws
#// HMW_185 Ty Yorrick offers "+1 instead" on EACH base the Sabine Action damages. The offers are questions, so the damage
#// waits on them — but it is still ONE event: both bases reach 0 together and the game is a draw (was: the first answer
#// dealt P1's 1 on its own, P1's base died alone, and P2 was scored the winner).
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:29;theirBaseDamage:29}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO
- P1>AnswerDecision:NO

## EXPECT
DRAW
P1BASEDMG:30
P2BASEDMG:30

---

# TyYorrick_Sabine_BothAccepted_From28_Draws
#// Both +1s taken: 1 + 1 = 2 to each base, 28 → 30 on both, in one event — a draw.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:28;theirBaseDamage:28}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
DRAW
P1BASEDMG:30
P2BASEDMG:30

---

# TyYorrick_Sabine_OnlyEnemyBaseLethal_IsAWin
#// NEGATIVE: P1 at 28 declines on its own base (29) and takes the +1 on P2's (28 → 30). Only P2's base reaches 0 — P1 wins.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:28;theirBaseDamage:28}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO
- P1>AnswerDecision:YES

## EXPECT
P1WIN
P1BASEDMG:29
P2BASEDMG:30

---

# TyYorrick_Sabine_FirstAnswer_DealsNothingYet
#// Request boundary: after the FIRST of the two answers no damage has landed — the event resolves only once every +1
#// question is answered.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:29;theirBaseDamage:29}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO

## EXPECT
NOWINNER
P1BASEDMG:29
P2BASEDMG:29

---

# RegroupDeckOut_TyYorrickInPlay_NoOffer_Draws
#// Deck-out damage is the RULE's (CR 5.4.b), not an ability, so Ty Yorrick offers no +1 on it — and both bases still die
#// in one step: a draw (was: Ty's question deferred one base's damage, so the other base died alone).
## GIVEN
CommonSetup: yyk/yyk/{myBaseDamage:27;theirBaseDamage:27}
P1OnlyActions: true
WithP1GroundArena: HMW_185:1:0
WithP2GroundArena: HMW_185:1:0

## WHEN
- P1>Pass

## EXPECT
DRAW
P1BASEDMG:33
P2BASEDMG:33

---

# TyYorrick_RecklessGunslinger_OwnBaseIsOfferedToo_Draws
#// SHD_160 hits the caster's OWN base too, and that hit is still a friendly ability's damage, so Ty offers +1 on it as
#// well (was: the own-base hit was credited to the opponent and dealt at once, never offered). Both taken, 28 → 30 on
#// both bases in one event: a draw.
## GIVEN
CommonSetup: rrw/rrw/{myResources:1;myBaseDamage:28;theirBaseDamage:28}
P1OnlyActions: true
WithP1Hand: SHD_160
WithP1GroundArena: HMW_185:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
DRAW
P1BASEDMG:30
P2BASEDMG:30

---

# FFA_EveryLiveSeatEliminatedTogether_NoGameWillEndWarning
#// The shared victory is decided before the eliminations run, so their log lines must not say "the game will end at the
#// end of this phase" — it already has. The elimination lines themselves still appear.
## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:SHD_160;myBaseDamage:29;theirBaseDamage:29}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:24

## WHEN
- P1>PlayHand:0

## EXPECT
GAMEWINNERS:1,2,3
LOGCONTAINS:Player 2 has been eliminated!
LOGCOUNT:0:The game will end at the end of this phase

---

# FFA_LastSurvivorScoredAtOnce_NoGameWillEndWarning
#// Two of three bases fall together and the survivor is scored on the spot — there is no "end of this phase" to wait for.
## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:SHD_160;theirBaseDamage:29}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:24

## WHEN
- P1>PlayHand:0

## EXPECT
GAMEWINNERS:1
LOGCONTAINS:Player 3 has been eliminated!
LOGCOUNT:0:The game will end at the end of this phase

---

# FFA_OneOfThreeEliminated_StillWarns
#// CONTROL: one base of three falls and two players remain — the game really does end at the end of this phase, so the
#// warning stays.
## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:SHD_160;theirBaseDamage:29}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P1>PlayHand:0

## EXPECT
NOGAMEWINNER
LOGCOUNT:1:The game will end at the end of this phase

---

# Draw_GameDetailEndReasonIsDraw
#// What the match layer stores per game: a drawn game's end reason is 'draw' (was 'win', with no winner to go with it).
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:29;theirBaseDamage:29}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
DRAW
GAMEDETAIL:endReason:draw

---

# Win_GameDetailEndReasonStaysWin
#// CONTROL: a base defeat that is a normal win still records 'win'.
## GIVEN
CommonSetup: grw/grw/{myResources:2;myBaseDamage:29;theirBaseDamage:28}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2WIN
GAMEDETAIL:endReason:win

---

# ColemanTrebor_FFA_BothEnemyBasesFallTogether_OneEvent
#// TS26_19 Coleman Trebor — "Deal 1 damage to each enemy base" is ONE event. Both enemy bases fall to it, so P1 is the
#// last player standing and wins on the spot — no "the game will end at the end of this phase" line in between (was: the
#// first base fell while the second was still untouched, so its elimination announced a phase-end ending that never came).
## GIVEN
CommonSetup: bgw/rrk/{myBaseDamage:3;theirBaseDamage:29}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:24
WithP1Resources: 3
WithP1Hand: TS26_19

## WHEN
- P1>PlayHand:0

## EXPECT
GAMEWINNERS:1
LOGCONTAINS:Player 2 has been eliminated!
LOGCONTAINS:Player 3 has been eliminated!
LOGCOUNT:0:The game will end at the end of this phase

---

# ArenaAcklay_FFA_BothEnemyBasesFallTogether_OneEvent
#// HMW_156 Arena Acklay — "Deal 2 damage to each enemy base" is ONE event. P2 Open Fires P1's Acklay (4 of its 6 HP: it
#// survives), and the 2 to each of P2's and P3's bases (P2 28 → 30, P3's 25-HP base 23 → 25) defeats both together: P1 wins on the spot, no phase-end
#// warning in between.
## GIVEN
CommonSetup: rrw/rrk/{theirResources:4;theirhandCardIds:SOR_172;theirBaseDamage:28}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithP3Base: SOR_019:23
WithP1GroundArena: HMW_156:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P2>Drain
- P3>Drain

## EXPECT
LOGCONTAINS:Player 2 has been eliminated!
LOGCONTAINS:Player 3 has been eliminated!
GAMEWINNERS:1
LOGCOUNT:0:The game will end at the end of this phase
