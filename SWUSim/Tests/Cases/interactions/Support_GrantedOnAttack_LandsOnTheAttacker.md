#// Support (CR 20.a): "You may attack with another unit. If you do, it gains this unit's other abilities for this
#// attack." A granted On Attack that says "this unit" means the ATTACKER (Yellow Aces Bomber ruling 07/21/2026:
#// "…if the attacking unit is upgraded"). The attacker's own On Attack triggers alongside it, and P1 orders them.
#//
#// FOUND BY: sweep retros #5–#7 of run 2 (2026-09-13): ORDER ASH_099:SupportOnAttack + SEC_110:OnAttack (12–18×,
#//   Piett red) · ASH_203:SupportOnAttack + ASH_248:OnAttack (9–26×, Ahsoka yellow).
#//   All sections were green on first run: this file is REGRESSION COVERAGE, not a bug.
#//
#// Cards: ASH_099 Gozanti Assault Carrier 4/6 space (Support; "On Attack: This unit gains Sentinel for this
#//   phase.") · SEC_110 GNK Power Droid 1/3 ("On Attack: The next unit you play this phase costs 1 resource less.")
#//   · ASH_203 Mando's N-1 Starfighter (Support; "On Attack: You may exhaust a friendly (non-upgrade) leader. If
#//   you do, this unit gets +2/+0 for this attack.") · ASH_248 Neel 1/4 ("On Attack: The next unit you play this
#//   phase with 1 or less power enters play ready.") · SOR_108 Vanguard Infantry 1/2.
#//
# Gozanti_GNKAttacksThroughSupport_TheGrantedSentinelGoesOnGNK_NotOnGozanti
## GIVEN
CommonSetup: bbk/ggw/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_099
WithP1GroundArena: SEC_110:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>ResolveTrigger:SupportOnAttack
## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:SEC_110
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:CARDID:ASH_099
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# N1_NeelAttacksThroughSupport_PaysTheLeaderForPlusTwo_AndHisOwnOnAttackReadiesTheNextSmallUnit
## GIVEN
CommonSetup: gyw/brk/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: [ASH_203 SOR_108]
WithP1GroundArena: ASH_248:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>ResolveTrigger:SupportOnAttack
- P1>AnswerDecision:YES
- P1>PlayHand:0
## EXPECT
P2BASEDMG:3
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_108
P1GROUNDARENAUNIT:1:READY
