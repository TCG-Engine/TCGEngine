# SOR_052 Redemption (Unit, Space, 6/9, Sentinel) — When Played: heal up to 8 total damage from any
# number of units and/or bases, then deal that much (the ACTUAL healed) to itself. P1 heals 4 from a
# damaged ground unit (4→0) + 2 from its base (3→1) = 6 total, so Redemption self-damages 6 (partial:
# 6 of the 8 pool). Sentinel is auto-wired and not tested here.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8;handCardIds:SOR_052;myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:4    # 3/7 with 4 damage → healed to 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:4,myBase-0:2

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:1
P1SPACEARENAUNIT:0:CARDID:SOR_052
P1SPACEARENAUNIT:0:DAMAGE:6
