# TWI_073 Grievous Reassembly (Event, cost 2, Vigilance, Supply) — "Heal 3 damage from a unit. Create a
# Battle Droid token." The sole damaged unit (SOR_046 at 3) auto-targets and heals to 0, and a Battle
# Droid token (TWI_T01) is created.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TWI_073}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
