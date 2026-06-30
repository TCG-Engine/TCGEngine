# SHD_006 Jabba the Hutt (deployed leader unit) — "Action [Exhaust]: Choose a unit. For this phase it
# gains 'Bounty - The next unit you play this phase costs 2 resources less.'" The deployed Jabba unit
# uses its Action and bounties the enemy Battlefield Marine, which gains the Bounty keyword; Jabba exhausts.
# (Same grant mechanism as the front side; the deployed reward pays 2 instead of 1.)

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P1GROUNDARENAUNIT:0:EXHAUSTED
