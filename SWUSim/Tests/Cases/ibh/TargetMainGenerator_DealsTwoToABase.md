# IBH_059 Target the Main Generator (Event, cost 2, Aggression) — Deal 2 damage to a base. Player
#   chooses the enemy base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_059

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1NODECISION
