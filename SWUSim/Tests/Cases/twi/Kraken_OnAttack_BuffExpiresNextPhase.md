# TWI_084 Kraken — the On Attack "+1/+1 for this phase" token buff must EXPIRE at regroup. Kraken
# attacks (buffs the Battle Droid to 2/2), then both players pass to end the action phase; the
# central SWUExpireTurnEffects('phase') at regroup drops the STAT_BUFF and the token returns to 1/1.

## GIVEN
CommonSetup: gyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_084:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:1
