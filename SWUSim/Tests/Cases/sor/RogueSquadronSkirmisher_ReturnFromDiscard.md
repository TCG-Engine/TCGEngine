# SOR_101 Rogue Squadron Skirmisher (cost 6, Command/Heroism) — When Played:
# return a unit that costs 2 or less from your discard to hand.
# P1 discard seeded with Battlefield Marine (SOR_095, cost 2 — valid) and
# Consular Security Force (SOR_046, cost 4 — too expensive). Playing SOR_101
# auto-returns the only ≤2-cost unit (SOR_095) to hand; SOR_046 stays in discard.
# P2 has no units, so SOR_101's Ambush has no target and does not prompt.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_101;discardCardIds:SOR_095,SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_046
