# TWI_135 Darth Maul (Ground, 5/6) — "This unit can attack 2 units instead of 1. It deals its combat
# damage to BOTH defenders (full power to each, not split) and both deal their combat damage to it; all
# simultaneous." Maul double-attacks LAW_124 (4/7) and SOR_236 (1/4): each takes Maul's FULL 5 (LAW_124
# survives at 5, SOR_236 dies), Maul takes 4+1 = 5 (survives at 5). Proves full-to-each: a split would
# leave LAW_124 at ~2-3, not 5. UX: base + 2 units in play → an OPTIONCHOOSE (Base vs Units); choosing
# "Units" opens a 1-or-2 unit multi-select — here both units are picked.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [LAW_124:1:0 SOR_236:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:5
