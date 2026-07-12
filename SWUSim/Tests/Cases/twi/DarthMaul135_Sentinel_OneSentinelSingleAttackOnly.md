# TWI_135 Darth Maul (5/6) — official ruling (2024-10-31): if the defending player controls ANY Sentinel,
# Maul may only choose Sentinels as his defenders (unless he has Saboteur). With exactly ONE Sentinel
# (SOR_035, 2/2) and a non-Sentinel (SOR_236, 1/4), Maul CANNOT pair the Sentinel with the free unit — he
# is limited to a single attack on the Sentinel. SOR_035 takes 5 → dies; Maul takes only 2 (DAMAGE:2);
# SOR_236 is untouched; and there is NO second-target prompt.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_035:1:0 SOR_236:1:0]
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_236
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
