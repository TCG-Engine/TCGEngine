# LAW_019 Alliance Outpost (Base, Vigilance) — "Epic Action [defeat a friendly token]: Give an
# Experience or Shield token to a unit, or create a Credit token." P1 has one TIE Fighter token
# (JTL_T01); the epic defeats it (cost) and P1 chooses the Credit mode → 1 Credit created.

## GIVEN
CommonSetup: bbw/grw/{
  myBase:LAW_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_T01:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:Credit

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:1
