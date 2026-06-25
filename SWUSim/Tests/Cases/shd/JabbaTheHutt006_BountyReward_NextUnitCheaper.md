# SHD_006 Jabba the Hutt — collecting the granted Bounty. P1 Jabba bounties the enemy Battlefield Marine
# (SOR_095, 3/3); P1's Industrious Team (LAW_124, 4/7) attacks and defeats it; P1 — the opponent of the
# bountied unit's controller (CR 13.f) — is offered the Bounty and collects it, arming "the next unit you
# play this phase costs 1 resource less." P1 then plays Imperial Dark Trooper (SEC_080, cost 2). With the
# discount it costs 1, so 1 of P1's 2 ready resources remains (without the discount it would be 0).

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:1
