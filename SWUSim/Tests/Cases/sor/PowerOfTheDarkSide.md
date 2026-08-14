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

---

# OnlyLeaderUnit_ForcedToDefeatIt
#// SOR_041 Power of the Dark Side — "a unit they control" has NO non-leader restriction, so a deployed
#// LEADER unit is a legal (and here the only) choice: with Sabine SOR_014 deployed as P2's sole unit,
#// the forced defeat undeploys her. A defeated leader returns to its leader zone rather than a discard
#// pile, so P2's discard stays empty.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021;
  theirLeader:SOR_014:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_041
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2LEADER:NOTDEPLOYED
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:0

---

# LeaderUnitOfferedAlongsideRegularUnit
#// SOR_041 Power of the Dark Side — the offer contains the deployed leader unit AS WELL AS a regular
#// unit (no non-leader restriction), and the choosing player is the OPPONENT. Offer left pending.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021;
  theirLeader:SOR_014:1:1:1
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_041
WithP1Resources: 3
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
