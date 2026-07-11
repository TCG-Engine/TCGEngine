# SHD_058 Val — "When Defeated: Give 2 Experience tokens to a friendly unit." P1's Val (2/4)
# attacks Industrious Team (LAW_124 4/7) and dies to the 4-power counter; her When Defeated
# resolves inline (attacker self-death) and the sole surviving friendly (Battlefield Marine 3/3,
# reindexed to 0) auto-receives 2 Experience → 5/5. Val's bounty is offered to P2 (the opponent of
# her controller, CR 13.f), who declines.

## GIVEN
CommonSetup: grw/grw
WithActivePlayer: 1
WithP1GroundArena: SHD_058:1:0    # Val — attacks and dies
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine — receives the 2 Experience
WithP2GroundArena: LAW_124:1:0    # Industrious Team — the killer

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1
