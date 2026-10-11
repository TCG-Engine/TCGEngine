# NotFirstAction_Blocked
#// SEC_145 Confidence in Victory — "Play only as your first action in the action phase." P1 attacks first
#// (its first action), then tries to play Confidence in Victory as a second action — the play is blocked,
#// so the card stays in hand.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2BASEDMG:3

---

# OpponentAlsoHasUnits_NoWin
#// SEC_145 Confidence in Victory — no win when the opponent ALSO controls units in the chosen arena.
#// P1 plays it (Ground) but P2 also has a ground unit, so at regroup P1 is NOT the only player with units
#// there → no win. The game continues: after passing into the next action phase, P1's attack still lands
#// (proving the game did not end).

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# SoleArenaControl_Wins
#// SEC_145 Confidence in Victory (Event, cost 10, Aggression/Villainy, Gambit)
#//   "Play only as your first action in the action phase. Choose an arena. At the start of the regroup
#//    phase, if you are the only player who controls units in that arena, you win the game."
#// P1 plays it as its first action and chooses Ground. P1 controls a ground unit (SOR_095); P2 controls
#// none. Both pass to the regroup phase, where the win check fires: P1 is the only player with units in
#// the ground arena, so P1 wins.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass

## EXPECT
P1WIN

---

# SoleSpaceControl_Wins
#// SEC_145 Confidence in Victory — same win check for the Space arena. P1 controls a space unit (SOR_066),
#//   P2 none. At the start of regroup P1 is the only player with units in Space → P1 wins.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1SpaceArena: SOR_066:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>Pass

## EXPECT
P1WIN

---

# NoUnitsInChosenArena_NoWin
#// SEC_145 Confidence in Victory — the caster must control units in the chosen arena. P1 chooses Space
#//   but has NO space units (only a ground unit); neither player has space units. At regroup the win
#//   check fails (caster count 0), so the game continues — proven by P1's attack landing next phase.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# OnlyOpponentControlsArena_NoWin
#// SEC_145 Confidence in Victory — no win when the OPPONENT is the only one with units in the chosen
#//   arena. P1 chooses Space, has no space units (ground only); P2 has a space unit. At regroup the win
#//   check fails, the game continues, and P1's attack lands next phase.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_066:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# CanPlayAfterOpponentActed
#// SEC_145 Confidence in Victory — "Play only as your first action in the action phase." The gate is
#//   per-player: the opponent taking an action first does NOT consume P1's first action. P2 attacks P1's
#//   base first; the turn returns to P1, who can still play Confidence as its own first action.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_145
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
TURNPLAYER:2

---

# PlayedAsVeryFirstActionOfPhase
#// SEC_145 Confidence in Victory — the plain positive: played as the FIRST action of the phase with
#// nobody having acted at all. (CanPlayAfterOpponentActed proves the gate is per-player when the OPPONENT
#// moved first; this proves the simplest legal case, which nothing else covered.) The card resolves, the
#// arena is chosen, and it goes to the discard.
## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# FirstActionWasALeaderAbilityThatPlayedACard_Blocked
#// SEC_145 Confidence in Victory — "your FIRST ACTION" counts ANY action, not just a card played from
#// hand. P1's first action is DJ's leader Action (SEC_018, "Choose a friendly unit… play a unit from your
#// hand… the chosen unit captures it"), which plays a card via an ACTION ABILITY. That still consumes
#// P1's first action, so Confidence in Victory is then blocked and stays in hand.
#// Distinct from NotFirstAction_Blocked, where the first action was an attack.
## GIVEN
CommonSetup: rrk/grw/{myLeader:SEC_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: SEC_145
WithP1Hand: SOR_095
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SEC_145

---

# KazudaExtraAction_ConfidenceStillBlocked
#// SEC_145 Confidence in Victory — "Play only as your FIRST action in the action phase." An extra action
#// granted by Kazuda Xiono (JTL_018, "Take an extra action after this one") does NOT reset that: Kazuda's
#// Action was P1's first action, so Confidence played with the extra action is still blocked and stays in
#// hand. Guards against implementing the gate as "actions remaining" rather than "actions taken".
## GIVEN
CommonSetup: rrk/grw/{myLeader:JTL_018}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SEC_145

---

# FirstActionWasATRIGGEREDAbilityThatPlayedACard_Blocked
#// SEC_145 Confidence in Victory — "your FIRST ACTION" is consumed by any action, including one whose
#// card-play came from a TRIGGERED ability rather than an activated one. P1's first action is playing
#// SEC_034 Cad Bane out of the resource row through the Plot window opened by deploying a leader — a
#// triggered play, not an Action ability. Confidence in Victory is then blocked and stays in hand.
#// Companion to FirstActionWasALeaderAbilityThatPlayedACard_Blocked, which covers the Action-ability route.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_034:1,14:SOR_046:1
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>PlayHand:0

## EXPECT
P1LEADER:DEPLOYED
P1HANDCOUNT:1
P1HANDCARD:0:SEC_145
P1DISCARDCOUNT:0

---

# PlayedFromRESOURCESViaSmuggle_IsStillAFirstAction
#// SEC_145 Confidence in Victory — "Play only as your first action in the action phase" restricts WHEN,
#// not from WHERE. Smuggling it out of the resource zone is still that first action, so it is allowed.
#// SHD_248 Tech gives every friendly resource Smuggle at "that card's cost plus 2 and its aspect icons",
#// so Confidence costs 12 (10 + 2, on-aspect under an Aggression/Villainy leader). P1 smuggles it with 15
#// resources and ends on 3 ready; the resource it occupied is replaced from the deck, so the resource
#// count stays 15, and the event lands in P1's discard after the arena is chosen.
#// (The sibling NotFirstAction_Blocked is the discriminator for the timing half of the restriction.)

## GIVEN
CommonSetup: rrk/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_248:1:0
WithP1Resources: 1:SEC_145:1,14:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:Space

## EXPECT
P1DISCARDCOUNT:1
P1RESCOUNT:15
P1RESAVAILABLE:3
P1NODECISION

---

# ThreeSeat_AFarSeatHoldsTheArena_NoWin
#// ⚠ A FALSE WIN. "if you are the ONLY PLAYER who controls units in that arena" — every other player
#// counts, not just one. P1 chooses Ground and holds it; SEAT 2 has nothing there, but SEAT 3 does. P1
#// is therefore NOT the only player controlling ground units and must NOT win.
#//
#// The check read `$opp = OtherPlayer($caster)` — seat 2 alone — so seat 3's units were invisible and
#// P1 WON THE GAME outright. Two seats cannot observe this: there, the one opponent is every opponent,
#// which is why SoleArenaControl_Wins and OpponentAlsoHasUnits_NoWin both pass against the broken read.

## GIVEN
CommonSetup3P: rrk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass
- P2>Pass
- P3>Pass

## EXPECT
SEATCOUNT:3
NOGAMEWINNER

---

# ThreeSeat_NoOtherSeatHoldsTheArena_Wins
#// THE CONTROL for the section above — identical board with seat 3's ground unit REMOVED (it is in SPACE
#// instead, a different arena). Now P1 really is the only player with ground units, so the win still
#// fires. Without this, a check that simply never won would pass the section above.

## GIVEN
CommonSetup3P: rrk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP3SpaceArena: SOR_246:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass
- P2>Pass
- P3>Pass

## EXPECT
SEATCOUNT:3
P1WIN

---

# FourSeat_TheFARTHESTSeatHoldsTheArena_NoWin
#// 4P sibling of the false-win guard. Seats 2 AND 3 are empty in the ground arena; only SEAT 4 holds it.
#// `OtherPlayer(1)` is seat 2, so the old check saw two empty boards and declared the win twice over.
#// With three opponents, "checked one of them" and "checked all of them" are finally different.

## GIVEN
CommonSetup4P: rrk/grw/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 10
WithP1Hand: SEC_145
WithP1GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP4Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>Pass
- P2>Pass
- P3>Pass
- P4>Pass

## EXPECT
SEATCOUNT:4
NOGAMEWINNER
