# OnAttack_BuffExpiresNextPhase
#// TWI_084 Kraken — the On Attack "+1/+1 for this phase" token buff must EXPIRE at regroup. Kraken
#// attacks (buffs the Battle Droid to 2/2), then both players pass to end the action phase; the
#// central SWUExpireTurnEffects('phase') at regroup drops the STAT_BUFF and the token returns to 1/1.

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

---

# OnAttack_BuffsFriendlyTokens
#// TWI_084 Kraken — "On Attack: Give each friendly token unit +1/+1 for this phase." Kraken (ready)
#// attacks P2's base (its only target → auto-resolves). On Attack fires: the friendly Battle Droid
#// token (TWI_T01, 1/1) becomes 2/2 for the phase. Kraken itself is NOT a token → unaffected (2/5).

## GIVEN
CommonSetup: gyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_084:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:2
P1GROUNDARENAUNIT:0:POWER:2

---

# WhenPlayed_Create2Droids
#// TWI_084 Kraken (Unit 2/5, Ground, cost 5, Command/Villainy) — "When Played: Create 2 Battle Droid
#// tokens." Kraken enters at ground index 0, then its When Played creates 2 Battle Droid (TWI_T01)
#// tokens at indices 1,2. Base g = Command + leader yk = Villainy cover both pips → no penalty.

## GIVEN
CommonSetup: gyk/grw/{myResources:5;handCardIds:TWI_084}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_084
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:2:CARDID:TWI_T01
