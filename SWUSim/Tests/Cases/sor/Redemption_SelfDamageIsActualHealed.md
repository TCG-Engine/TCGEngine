# SOR_052 — the self-damage equals the ACTUAL healed, not the amount assigned. A unit with only 2
# damage is over-assigned 6 heal; OnHealUnit clamps the heal to 2, so Redemption self-damages 2 (not
# 6 and not the pool 8). Guards that "deal that much" reads actual-healed, not the assignment string.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2    # 3/7 with 2 damage

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:6

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:2
