#// ALWAYS TWO — bases at 0 HP, and how a TEAM loses. docs/superpowers/specs/2026-11-20-swusim-always-two-design.md.
#// • A board whose base reaches 0 remaining HP is NOT eliminated: it keeps its cards, keeps acting, its base stays on the table and
#//   can still be attacked and healed. Damage keeps counting past 0 (owner ruling), so a heal smaller than the overshoot leaves it at 0.
#// • A team loses when BOTH its bases are at 0 at the same time; all four at once is a draw (CR 6.3).
#// • Final Showdown, Confidence in Victory and conceding act for the whole TEAM (owner rulings).
#// Boards 1-2 have 30 HP bases (damage via the setup's {myBaseDamage;theirBaseDamage}); boards 3-4 use SOR_019 (25 HP).
# BaseAtZero_NotEliminated
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:29}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:P1B
## EXPECT
P1BASEMAXHP:30
P1BASEDMG:32
SEATLIVE:1:true
NOWINNER

---

# BaseAtZero_BoardStillActs
#// Board 1's base is already at 0; board 1 still plays a card on its team's turn.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:30}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP1Hand: SOR_046
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
SEATLIVE:1:true
NOWINNER

---

# DamageCountsPastZero_HealStillZero
#// 33 damage on a 30 HP base (3 past 0). SOR_243 (Restore 2) attacks: heals 2 → 31, still at 0 remaining.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:33}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_243:1:0
## WHEN
- P1>AttackGroundArena:0:P2B
## EXPECT
P1BASEDMG:31
SEATLIVE:1:true
NOWINNER

---

# BaseAtZero_CanStillBeAttacked
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:30}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:P1B
## EXPECT
P1BASEDMG:33
NOWINNER

---

# BothBasesOfATeam_TeamLoses
#// Board 1 is already at 0; Blue takes board 3's base to 0 → both Red bases at 0 → Blue wins.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:30}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP3Base: SOR_019:24
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:P3B
## EXPECT
GAMEWINNERS:2,4

---

# AllFourAtOnce_Draw
#// SHD_160 Reckless Gunslinger: "deal 1 damage to each base" — one event takes all four bases to 0 together.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:29;theirBaseDamage:29}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:24
WithP4Base: SOR_019:24
WithP1Resources: 10
WithP1Hand: SHD_160
## WHEN
- P1>PlayHand:0
## EXPECT
DRAW

---

# HealedBackAboveZero_NoLossWhenTheOtherFalls
#// Board 1 is at 0 (31/30) but heals 2 (Restore) back to 29 = 1 remaining. Then board 3 reaches 0 — only ONE Red base is at 0.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:31}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:24
WithP1GroundArena: SOR_243:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:P2B
- P2>AttackGroundArena:0:P3B
## EXPECT
P1BASEDMG:29
P3BASEDMG:27
NOWINNER

---

# FinalShowdown_LosesForTheTeam
#// SHD_208 "At the start of the regroup phase, you lose the game" — played by board 3, it loses for Red.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Resources: 10
WithP3Hand: SHD_208
## WHEN
- P3>PlayHand:0
- P1>RunRegroupStart
## EXPECT
GAMEWINNERS:2,4

---

# ConfidenceInVictory_WinsForTheTeam
#// SEC_145 "if you are the only player who controls units in that arena, you win the game" — board 3 is alone in the ground arena,
#// so Red wins (a "you win" acts for the team, as Final Showdown's "you lose" does).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Resources: 14   # cost 10 + 2 per missing aspect (Aggression, Villainy)
WithP3Hand: SEC_145
WithP3GroundArena: SOR_095:1:0
## WHEN
- P3>PlayHand:0
- P3>AnswerDecision:Ground
- P1>RunRegroupStart
## EXPECT
GAMEWINNERS:1,3

---

# ConcedeFromBoardThree_TeamLoses
#// Conceding (or leaving) concedes for the whole team (owner ruling).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P3>Concede
## EXPECT
GAMEWINNERS:2,4

---

# DeckOutOnOneBoard_NoElimination
#// CommonSetup decks are empty: at regroup every board draws 2 from an empty deck and takes 3 x 2 = 6. Nobody is eliminated and
#// nobody wins — each base just takes it. (Red claims, Blue's one pass ends the action phase.)
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Claim
- P2>Pass
## EXPECT
P3BASEDMG:6
SEATLIVE:3:true
NOWINNER

---

# RegroupDeckOut_BothRedBases_TeamLoses
#// Not combat: the regroup deck-out (one bracketed event) takes BOTH Red bases to 0 — board 1 27+6 = 33/30, board 3 22+6 = 28/25 —
#// while Blue's take 6 and live. Decided by the state-based check, not the combat path.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:27}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:22
WithP4Base: SOR_019:0
## WHEN
- P1>Claim
- P2>Pass
## EXPECT
GAMEWINNERS:2,4

---

# RegroupDeckOut_OnlyBoardOneAtZero_NoWinner
#// The same non-combat event takes ONLY board 1 to 0 (27+6 = 33/30); board 3 is fine. One Red base at 0 decides nothing —
#// the two-board reading ("P1's base was defeated → P2 wins") must not leak in.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:27}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
## WHEN
- P1>Claim
- P2>Pass
## EXPECT
P1BASEDMG:33
SEATLIVE:1:true
NOWINNER

---

# Control_TeamSuns_BaseDefeatStillEliminates
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBaseDamage:29}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:P1B
## EXPECT
SEATLIVE:1:false

---

# ConfidenceInVictory_TwoTeammatesWin_IsATeamWinNotADraw
#// Two Red casters meet SEC_145's condition at the same timing point — board 1 alone in the ground arena, board 3 alone in space.
#// Both winners are on one team, so Red wins (winners on BOTH teams would be the draw). Final review finding, 2026-11-20.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 14
WithP3Resources: 14
WithP1Hand: SEC_145
WithP3Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP3SpaceArena: SOR_225:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P2>Claim
- P3>PlayHand:0
- P3>AnswerDecision:Space
- P1>RunRegroupStart
## EXPECT
GAMEWINNERS:1,3
