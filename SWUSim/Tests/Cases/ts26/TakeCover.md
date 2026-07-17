# HealAndShield
#// TS26_47 Take Cover (Event, cost 3, Vigilance) — Heal up to 3 damage from a unit and give it a Shield.
#// LAW_124 (4/7) with 3 damage is healed to 0 damage and shielded.
## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TS26_47}
WithP1GroundArena: LAW_124:1:3
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
