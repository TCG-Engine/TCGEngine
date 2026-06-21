# SEC_005 Satine Kryze (leader) — Action [Exhaust]: Heal up to 2 damage from a unit. If you do, deal
# that much damage to your base. Friendly SEC_080 has 2 damage → heal 2 (DAMAGE:0), then deal 2 to P1's
# own base. Player chooses Heal2 (the up-to amount). No resource cost.

## GIVEN
P1LeaderBase: SEC_005/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Heal2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:2
P1LEADER:EXHAUSTED
