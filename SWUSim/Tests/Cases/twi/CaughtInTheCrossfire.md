# MutualDamage
#// TWI_176 Caught in the Crossfire (Event, Aggression) — "Choose 2 enemy units in the same arena. Each deals
#// damage equal to its power to the other." SOR_046 (3/7) and SOR_095 (3/3): SOR_095 dies to 3, SOR_046 takes 3.
## GIVEN
CommonSetup: rrk/bbw/{myResources:6;handCardIds:TWI_176}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# HelperText_SecondPickNamesTheFirstUnitAndItsPower
#// TWI_176 — helper text on the second pick. "Choose the second enemy unit in the same arena" described
#// the click and not the trade: both units deal their CURRENT power to each other simultaneously, so the
#// first unit's power is exactly what decides whether the second one survives being picked.
#// SOR_046 Consular Security Force is printed 3 and carries SOR_070 Devotion (+1/+1) here, so the prompt
#// must say 4 — a printed-power read says 3 and is wrong precisely when an upgrade is what makes the
#// trade lethal.
#// ⚠ THREE enemies in the arena: two would leave a single second-pick candidate, which auto-resolves and
#// leaves no prompt to inspect.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6;handCardIds:TWI_176}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0 SOR_128:1:0]
WithP2GroundArenaUpgrade: 0:SOR_070

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_second_enemy_unit_-_it_and_Consular_Security_Force_deal_their_power_to_each_other_(Consular_Security_Force_deals_4)
