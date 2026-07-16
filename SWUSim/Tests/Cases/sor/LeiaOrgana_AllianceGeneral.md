# Deployed_RaidAndAttackAnother
#// SOR_009 Leia Organa — Deployed: Raid 1 + "When this unit completes an attack: you may attack
#// with another Rebel unit." Deployed Leia (3/6, Rebel) attacks the base for 3+1(Raid)=4, then her
#// OnAttackEnd lets a second Rebel attack the base for 3 → 7 total base damage.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:DEPLOYED

---

# LeaderAction_AttackTwoRebels
#// SOR_009 Leia Organa — Leader Action [Exhaust]: Attack with a Rebel unit. Then, you may attack
#// with another Rebel unit. P1 has two Rebels; both attack the base (opponent has only a base) for
#// 3 each → 6 total.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1LEADER:EXHAUSTED

---

# LeaderAction_DeclineSecond
#// SOR_009 Leia Organa — the second attack is optional ("you may"). Declining it leaves only the
#// first Rebel's attack: base takes 3, the second Rebel is untouched and stays ready.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:READY
P1LEADER:EXHAUSTED

---

# LeaderAction_MultiTarget_ChooseAttackTargets
#// SOR_009 Leia Organa — leader action with the opponent holding a UNIT (not just a base), so each
#// attack chooses its target. First Rebel (3/7) attacks the enemy 3/1 (a real MZCHOOSE between the
#// unit and the base) and defeats it; the chained second Rebel then attacks the base (the only target
#// left) for 3.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3
