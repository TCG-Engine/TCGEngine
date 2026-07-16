# Deployed_OnAttack_GivesExperience
#// SOR_008 Hera (deployed Leader Unit, 4/6) — "On Attack: You may give an Experience token to another
#// unique unit." P1 deploys Hera (6 resources) and attacks the base; On Attack, she gives an Experience
#// token to the other unique unit (Zeb, in space → UPGRADECOUNT 1). Her 4 power hits the base.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_146:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:4

---

# IgnoresAspectPenalty_OnSpectre
#// SOR_008 Hera Syndulla (leader) — "Ignore the aspect penalty on SPECTRE cards you play." P1's leader is
#// Hera (Command/Heroism). SOR_146 Zeb (Spectre, Aggression/Heroism, cost 5) would normally cost 7 (the
#// Aggression pip is off-aspect, +2), but Hera waives it — so with exactly 5 resources Zeb enters play.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_146
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0

---

# NonHeraLeader_PenaltyApplies
#// SOR_008 Hera — control: with a non-Hera leader that has the SAME aspects (SOR_009, Command/Heroism),
#// Zeb's off-aspect Aggression pip still adds +2 → cost 7. With only 5 resources the play is a silent
#// no-op (Zeb stays in hand), proving the waiver is Hera-specific, not just the shared Heroism aspect.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_146
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
