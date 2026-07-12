# TWI_135 Darth Maul (5/6) — simultaneity: all combat damage is dealt at once. Maul double-attacks two
# 3/3 units (SOR_095, SEC_080). Each defender takes Maul's full 5 → both die. Both defenders deal 3 back
# → Maul takes 3+3 = 6 on 6 HP → Maul dies too, SIMULTANEOUSLY (a defeated defender still deals its
# combat damage). Board ends empty on both sides.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
