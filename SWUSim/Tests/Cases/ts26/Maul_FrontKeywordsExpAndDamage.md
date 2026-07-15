# TS26_003 Maul (leader front) — Action [Exhaust]: choose a unit; if it has more different keywords than
# Experience tokens on it, give it an Experience token and deal 1 damage. SOR_063 (Sentinel, 1 keyword, 0
# Experience) qualifies → gains 1 Experience (2 → 3 power) and takes 1 damage.
## GIVEN
CommonSetup: ggk/rrk/{myLeader:TS26_003}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
