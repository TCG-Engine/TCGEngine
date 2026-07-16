# OnAttack_NameCard_SpyPerCopy
#// SEC_210 Stolen Starpath Unit (Upgrade) — Attached unit gains "On Attack: Name a card. The defending
#//   player reveals their hand. For each card with that name, create a Spy token." Host SOR_095 bears
#//   SEC_210, attacks the base; name "Battlefield Marine"; P2 hand has 2 → create 2 Spy tokens.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_210
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:3
P1NODECISION
