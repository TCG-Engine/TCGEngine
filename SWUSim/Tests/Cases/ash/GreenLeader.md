
---

# WhenDefeated_DealTwo
#// ASH_153 Green Leader (Space, 3/1) — When Defeated: you may deal 2 damage to a unit. Green Leader dies to
#// the counter attacking the space wall ASH_081 (3/6), then its When Defeated deals 2 more to it (3 + 2 = 5).
## GIVEN
CommonSetup: rrk/rrk
WithP1SpaceArena: ASH_153:1:0
WithP2SpaceArena: ASH_081:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:5
