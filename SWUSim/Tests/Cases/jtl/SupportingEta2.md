# 160_OnAttack_BuffGround
#// JTL_160 Supporting Eta-2 — On Attack: You may give a ground unit +2/+0 this phase. JTL_160 attacks
#// P2's base and buffs the friendly ground SOR_095 (3 → 5 power).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_160:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:2

---

# SimulateRequestBoundary_OnAttackGroundBuff
#// JTL_160 Supporting Eta-2 — the "you may give a ground unit +2/+0" On Attack offer ends the request in
#// production, so the answer arrives in a fresh process with every transient global empty. Mirrors
#// 160_OnAttack_BuffGround with the boundary inserted before the target answer: the offer's pending
#// continuation (APPLY_PHASE_BUFF|2|0|JTL_160) and the in-flight attack must both survive serialization.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_160:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:2
