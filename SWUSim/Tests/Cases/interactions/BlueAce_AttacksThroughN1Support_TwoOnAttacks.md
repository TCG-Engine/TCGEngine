#// Support (CR 20.a): "You may attack with another unit. If you do, it gains this unit's other abilities for this
#// attack." Mando's N-1 Starfighter lends its On Attack to Blue Ace, whose own On Attack triggers on the same attack
#// declaration — two On Attacks for one player, so P1 orders them (CR 7.6.9). The granted "this unit gets +2/+0"
#// lands on the ATTACKER (see Support_GrantedOnAttack_LandsOnTheAttacker.md).
#//
#// FOUND BY: sweep run 3, retro #1 (2026-09-13): ORDER ASH_203:SupportOnAttack + SEC_204:OnAttack (3×, e.g.
#//   ahsoka_yellow.greef.s045, round 5, seat 1). Not seen in run 2.
#//
#// Cards: ASH_203 Mando's N-1 Starfighter 1/3 space (Support; "On Attack: You may exhaust a friendly (non-upgrade)
#//   leader. If you do, this unit gets +2/+0 for this attack.") · SEC_204 Blue Ace 4/5 space (Ambush; "On Attack:
#//   Ready an exhausted enemy unit.") · SOR_095 Battlefield Marine 3/3 (P2, exhausted — Blue Ace's only target).
#//   No enemy space unit, so Blue Ace's attack goes to the base.
#//
# BothOnAttacks_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: gyw/brk/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_203
WithP1SpaceArena: SEC_204:1:0
WithP2GroundArena: SOR_095:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# SupportFirst_LeaderPaysForPlusTwo_ThenTheMarineIsReadied_SixToTheBase
## GIVEN
CommonSetup: gyw/brk/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_203
WithP1SpaceArena: SEC_204:1:0
WithP2GroundArena: SOR_095:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>ResolveTrigger:SupportOnAttack
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:6
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
P1SPACEARENAUNIT:0:CARDID:SEC_204
P1SPACEARENAUNIT:0:EXHAUSTED

---

# BlueAceFirst_ThenTheLeaderIsNotExhausted_FourToTheBase
#// The decline branch of the granted ability: no leader exhausted, no +2/+0 — Blue Ace's own ability still readies
#//   the Marine.
## GIVEN
CommonSetup: gyw/brk/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_203
WithP1SpaceArena: SEC_204:1:0
WithP2GroundArena: SOR_095:0:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>ResolveTrigger:OnAttack
- P1>AnswerDecision:NO
## EXPECT
P2BASEDMG:4
P1LEADER:READY
P2GROUNDARENAUNIT:0:READY
