# FriendlyCapturesEnemySameArena
#// TS26_61 Encircle (Event, cost 5, Command) — costs 1 less per friendly unit; a friendly unit captures
#// an enemy non-leader unit in the same arena. With 1 friendly unit the cost is 4 (only affordable via the
#// discount: 4 resources - 4 = 0). The friendly SEC_080 captures the enemy SOR_095 in the ground arena.
## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_61}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# CostsOneLessPerFRIENDLYUnit
#// TS26_61 Encircle — "costs 1 resource less for each friendly unit", counting the whole board, not just
#// the captor. Three friendly units bring it from 5 down to 2, leaving 3 of the 5 resources, and SEC_080
#// captures the enemy SOR_128.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_61}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:3
P2GROUNDARENACOUNT:0

---

# CapturesInTheSPACEArenaToo
#// TS26_61 Encircle — "in the same arena" works in space as well as on the ground: P1's TIE Fighter token
#// captures the enemy SOR_128 in the space arena and remains the only unit there.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_61}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_T01:1:0
WithP2SpaceArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:1
