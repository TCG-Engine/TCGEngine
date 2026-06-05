# SOR_150 Heroic Sacrifice — the self-defeat fires on dealing combat damage to a UNIT too, and it
# defeats the attacker even when it survives the counter. SOR_046 (3/7) gets +2/+0 → 5 power and must
# attack the Sentinel SOR_063 (2/4): it kills the Sentinel (5 ≥ 4) and survives the 2-power counter
# (7 HP), but the granted "when it deals combat damage: defeat it" still defeats it.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
