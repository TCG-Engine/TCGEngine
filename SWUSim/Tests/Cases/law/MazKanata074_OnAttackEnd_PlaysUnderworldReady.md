# LAW_074 Maz Kanata (4/4) — When Attack Ends (she survived): search the top 5 for an Underworld unit
# and play it; it costs 4 less and enters play ready. Maz attacks the base (survives — no counter), then
# searches the top 5 (only SOR_247 is an Underworld unit) and plays it. SOR_247 (cost 2) costs 0 after
# the -4 discount, so resources are untouched, and it enters READY.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: LAW_074:1:0
WithP1Deck: SOR_247
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:SOR_247

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:READY
P1RESAVAILABLE:3
