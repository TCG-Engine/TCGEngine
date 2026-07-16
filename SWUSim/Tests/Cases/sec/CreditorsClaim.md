# GrantedWhenDefeated_DefeatLowHp
#// SEC_039 Creditor's Claim (Upgrade) — Attached unit gains "When Defeated: you may defeat a unit with 3
#//   or less remaining HP." Host SOR_095 (with SEC_039) attacks LAW_124 and dies → may defeat SOR_128 (1 HP).

## GIVEN
CommonSetup: bbk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP1GroundArenaUpgrade: 0:SEC_039
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1NODECISION
