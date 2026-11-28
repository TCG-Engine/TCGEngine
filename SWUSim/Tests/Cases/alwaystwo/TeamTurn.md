#// ALWAYS TWO — the TEAM turn. docs/superpowers/specs/2026-11-20-swusim-always-two-design.md ("Turns").
#// Doubles rules: "Your team gets 1 action per turn, taken by either teammate." So on Red's turn EITHER Red board (1 or 3) may take
#// the one action; whichever does becomes the turn player, and the turn then passes to Blue (2 or 4). An enemy board never acts on
#// our turn. In Team Suns, by contrast, every seat waits for its OWN turn (the control at the bottom).
#// Plays go through ActionMap — the same path as a real click — so these sections exercise the production gate.
# EitherBoardOfTheTeamMayAct
#// Seat 1's turn, but board 3 (Red, seat 1's teammate) plays. Its unit lands and the turn passes to Blue (the next board, 4).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP3Resources: 8
WithP1Hand: SOR_046
WithP3Hand: SOR_046
## WHEN
- P3>PlayHand:0
## EXPECT
P3GROUNDARENACOUNT:1
P3HANDCOUNT:0
TURNPLAYER:4

---

# SecondBoardOfTheTeam_RefusedAfterTheTeamActed
#// One action per TEAM turn: board 1 plays, the turn is Blue's, so board 3's play right after is refused.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP3Resources: 8
WithP1Hand: SOR_046
WithP3Hand: SOR_046
## WHEN
- P1>PlayHand:0
- P3>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P3GROUNDARENACOUNT:0
P3HANDCOUNT:1
TURNPLAYER:2

---

# EnemyBoard_RefusedOnOurTurn
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Resources: 8
WithP4Resources: 8
WithP2Hand: SOR_046
WithP4Hand: SOR_046
## WHEN
- P4>PlayHand:0
## EXPECT
P4GROUNDARENACOUNT:0
P4HANDCOUNT:1
TURNPLAYER:1

---

# OneActionThenTheOtherTeam_EitherBlueBoard
#// After Red's action the turn is Blue's — and Blue may take it with board 4 even though the turn landed on board 2.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 8
WithP4Resources: 8
WithP1Hand: SOR_046
WithP4Hand: SOR_046
## WHEN
- P1>PlayHand:0
- P4>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1
TURNPLAYER:1

---

# EitherBoardMayAttack
#// Attacks are a turn action too: board 3 attacks on seat 1's turn.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_046:1:0
## WHEN
- P3>AttackGroundArena:0:P2B
## EXPECT
P2BASEDMG:3

---

# Control_TeamSuns_TeammateWaitsForItsOwnTurn
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Resources: 8
WithP3Hand: SOR_046
## WHEN
- P3>PlayHand:0
## EXPECT
P3GROUNDARENACOUNT:0
P3HANDCOUNT:1
TURNPLAYER:1

---

# BoardThreeIsOfferedItsActionsOnTheTeamTurn
#// The client draws its "you may act" highlights from SWUComputeActionsData. On seat 1's turn board 3 (same team) is offered its
#// ready unit as an attacker — its own home view must light up, not read "waiting".
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
## EXPECT
P3ATTACKERCOUNT:1
P4ATTACKERCOUNT:0

---

# Control_TeamSuns_TeammateOfferedNothingOffTurn
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
## EXPECT
P1ATTACKERCOUNT:1
P3ATTACKERCOUNT:0
