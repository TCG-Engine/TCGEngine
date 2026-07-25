# EachPlayerDefeatsOwn
#// LAW_099 Governor's Shuttle (2/4) — When Played: each player chooses a unit they control. Defeat those
#// units. P1 picks its SEC_080 (keeps the Shuttle); P2 picks its SOR_046.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_099
P2GROUNDARENACOUNT:0

---

# MustChooseItselfWhenNoOtherUnits
#// LAW_099 Governor's Shuttle — "each player chooses a unit they control." With no other units in play, P1
#// controls only the newly-played Shuttle and must choose it; P2 controls nothing. The Shuttle is defeated.

## GIVEN
CommonSetup: brk/bgw/{myResources:5}
WithActivePlayer: 1
WithP1Hand: LAW_099

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1

---

# CanChooseFriendlyLeaderUnit
#// LAW_099 Governor's Shuttle — the chosen unit may be a leader unit; a defeated leader unit returns to
#// base rather than the discard. P1 chooses its deployed leader (returns to base, Shuttle survives); P2's
#// lone SOR_046 is auto-chosen and defeated.

## GIVEN
CommonSetup: brk/bgw/{myLeader:LAW_003:1:1:1;myResources:9}
WithActivePlayer: 1
WithP1Hand: LAW_099
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:NOTDEPLOYED
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_099
P2GROUNDARENACOUNT:0
