# IBH_071 Target the Main Generator (reprint of IBH_059) — deal 2 to a base. Confirms the duplicate.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_071

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1NODECISION
