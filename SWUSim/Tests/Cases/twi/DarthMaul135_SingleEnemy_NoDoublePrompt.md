# TWI_135 Darth Maul (5/6) — the double attack needs at least 2 eligible enemy units. With only ONE
# enemy unit (SOR_095, 3/3) in his arena, Maul makes an ordinary single attack: SOR_095 takes 5 → dies,
# Maul takes 3 (DAMAGE:3), and there is NO second-target prompt left pending.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
