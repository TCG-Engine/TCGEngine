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
