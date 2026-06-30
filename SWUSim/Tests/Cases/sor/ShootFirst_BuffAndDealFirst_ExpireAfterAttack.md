# SOR_217 Shoot First — "It gets +1/+0 for this attack and deals its combat damage before the
# defender." Both halves are ATTACK-duration registry effects (the SOR_217 +1/+0 STAT_BUFF and the
# SHOOT_FIRST deal-first marker), dropped by SWUExpireTurnEffects('attack') when combat resolves.
# Battlefield Marine SOR_095 (3 power) gets +1 → 4, deals first and defeats the shielded SOR_207
# (so it takes NO counter, DAMAGE:0), and afterward is back to its base power 3 (buff expired).

## GIVEN
CommonSetup: gyw/gyw/{myResources:1;handCardIds:SOR_217}
WithP1GroundArena: SOR_095
WithP2GroundArena: SOR_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:3
