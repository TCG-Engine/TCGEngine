# NoUnits_NoOp
#// SOR_041 Power of the Dark Side — when the opponent controls no units the event fizzles cleanly: it
#// resolves to P1's discard and nothing is defeated (no dangling decision).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_041
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0

---

# OpponentChoosesUnit
#// SOR_041 Power of the Dark Side (event, cost 3) — "An opponent chooses a unit they control. Defeat
#// that unit." Any unit (no non-leader restriction). The opponent controls two units (SEC_080 ground,
#// SOR_225 space) and chooses the space one to defeat. The event then goes to P1's discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_041
WithP1Resources: 3
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:mySpaceArena-0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2SPACEARENACOUNT:0

---

# SingleUnit_ForcedDefeat
#// SOR_041 Power of the Dark Side — when the opponent controls exactly ONE unit the choice is forced,
#// so it is defeated directly with no decision queued (a fragile cross-player auto-resolve is avoided).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_041
WithP1Resources: 3
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
