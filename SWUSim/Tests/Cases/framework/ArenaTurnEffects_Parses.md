# DSL: arena unit spec accepts a 4th field of active TurnEffects ('~'-delimited), e.g.
# "LOF_096:0:3:LOF_045" = Obi-Wan (exhausted, 3 dmg) carrying Yaddle's granted Restore (LOF_045).
# The token is a registry GRANT_KEYWORD_VALUE (RESTORE 1), so Obi-Wan reads as having Restore 1.
# A second unit with two effects ('~'-joined) confirms multi-effect parsing.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: [LOF_096:0:3:LOF_045 SEC_098:1:0:SENTINEL^SEC_041~RESTORE-1]

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_096
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1GROUNDARENAUNIT:1:CARDID:SEC_098
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore
