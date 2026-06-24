# ASH_007 Grand Admiral Sloane — Leader Action [Exhaust]: choose one — give each ground unit (or each space
# unit) Sentinel and Overwhelm for this phase. P1 chooses Ground, so SOR_095 (ground) gains both keywords
# while SOR_237 (space) is unaffected; Sloane exhausts.
## GIVEN
P1LeaderBase: ASH_007/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Ground
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P1LEADER:EXHAUSTED
