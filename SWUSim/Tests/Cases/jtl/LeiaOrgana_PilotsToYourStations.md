# WhenPlayed_PilotAttack_RestoreBuff
#// JTL_097 Leia Organa — When Played: you may attack with a Pilot unit; it gets +1/+0 and Restore 1 for
#// this attack. The ready Pilot JTL_046 (power 3) attacks the enemy base for 3+1=4, and Restore 1 heals
#// P1's base from 3 damage to 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_097
WithP1Resources: 7
WithP1GroundArena: JTL_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1BASEDMG:2

---

# SimulateRequestBoundary_PilotAttackBuffSurvives
#// JTL_097 Leia Organa — the When Played "you may attack with a Pilot unit" offer is an interactive
#// decision, and the +1/+0 / Restore 1 rider is applied only AFTER the answer arrives. In production that
#// answer lands in a fresh process, so the pending ability's rider must be reconstructed from the
#// serialized gamestate. Mirrors WhenPlayed_PilotAttack_RestoreBuff with a boundary before the answer.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_097
WithP1Resources: 7
WithP1GroundArena: JTL_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1BASEDMG:2
