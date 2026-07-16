# OnAttackDealPowerIfBountyHunter
#// LAW_064 Zuckuss (3/5, Saboteur) — On Attack: if you control another Bounty Hunter unit, you may deal
#// damage equal to this unit's power to a ground unit. P1 controls LAW_124 (Bounty Hunter); Zuckuss
#// attacks the base and deals 3 to the enemy SOR_046.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
