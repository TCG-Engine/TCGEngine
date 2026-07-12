# TWI_135 Darth Maul (5/6) — with 2+ Sentinels present the base is not a legal target, so there is NO
# Base-vs-Units mode prompt: the unit multi-select is offered directly, restricted to the Sentinels. The
# opponent controls TWO Sentinel units (SOR_035 2/2, SOR_063 2/4) AND a non-Sentinel (SOR_095 3/3); only
# the two Sentinels are offered. Maul attacks both: they take 5 each → both die. The non-Sentinel SOR_095
# is left untouched (reindexes to slot 0, DAMAGE:0). Maul takes 2+2 = 4 (survives).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_035:1:0 SOR_063:1:0 SOR_095:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4
