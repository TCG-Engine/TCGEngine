# TWI_135 Darth Maul — official ruling (2024-10-31): if Maul has Overwhelm, he deals the COMBINED excess
# of his attack on both defenders to the defending player's base. Maul (5/6) carries TWI_119 Nameless
# Valor (+2/+2, "Attached unit gains Overwhelm") → 7/8 with Overwhelm. He double-attacks two 3/3 units
# (SOR_095, SEC_080): each takes 7 → dies with 4 excess → combined 4+4 = 8 to P2's base. Maul takes 3+3 = 6 (survives
# at 6 on 8 HP).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:TWI_119
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2BASEDMG:8
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:6
