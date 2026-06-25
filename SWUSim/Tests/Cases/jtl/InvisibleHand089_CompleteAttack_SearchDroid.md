# JTL_089 The Invisible Hand — the "When this unit completes an attack (and survives)" copy of its
# search. The Invisible Hand attacks the enemy base, survives, then searches the top 8 for a Droid
# (LOF_158, cost 3 → draw-only, no free-play branch) and draws it. Mirrors the When Played tests.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_089:1:0
WithP1Deck: [LOF_158 SOR_095 SOR_237]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:LOF_158

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION
