# SOR_218 Asteroid Sanctuary (Event) — Exhaust an enemy unit. Give a Shield token to a
# friendly unit that costs 3 or less. The lone enemy unit is exhausted and the lone friendly
# unit (Battlefield Marine, cost 2 ≤ 3) gains a Shield. Both effects auto-resolve.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218
WithP1GroundArena: SEC_080:1:0    # friendly, cost 2 (≤3) — Shield recipient
WithP2GroundArena: SEC_080:1:0    # enemy — exhaust target

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
