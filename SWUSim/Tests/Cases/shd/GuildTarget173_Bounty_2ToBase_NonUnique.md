# SHD_173 Guild Target — attached unit gains "Bounty — Deal 2 damage to a base. If this unit is
# unique, deal 3 instead." NON-unique host (Battlefield Marine) → 2. P1 collects and picks P2's base.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_173

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
