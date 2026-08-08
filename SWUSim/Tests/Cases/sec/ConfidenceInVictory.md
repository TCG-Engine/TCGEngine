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
