# SHD_006 Jabba the Hutt (leader front) — "Action [Exhaust]: Choose a unit. For this phase it gains
# 'Bounty - The next unit you play this phase costs 1 resource less.'" P1 Jabba bounties the enemy
# Battlefield Marine (SOR_095). The marine gains the Bounty keyword (the badge shows) and Jabba exhausts.
# Only one unit is in play, so the "choose a unit" auto-resolves to it (PASSPARAMETER, no AnswerDecision).

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P1LEADER:EXHAUSTED
