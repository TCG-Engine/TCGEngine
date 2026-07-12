# TWI_135 Darth Maul (5/6) — attacking a SINGLE unit is still allowed: choose "Units", then the 1-or-2
# multi-select accepts just ONE unit. Maul picks only LAW_124 (4/7) → an ordinary single attack: LAW_124
# takes 5 (survives, DAMAGE:5), Maul takes only LAW_124's 4 (DAMAGE:4), and the other enemy SOR_236 is
# untouched (DAMAGE:0). Both boards keep all units.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [LAW_124:1:0 SOR_236:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:CARDID:SOR_236
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4
