# DamageAndHealBase
#// ASH_258 Grassroots Resistance (Event, cost 4) — Deal 3 damage to a unit. Heal 3 damage from your base.
#// P1's base (3 damage) is healed to 0, and the enemy SEC_080 (3/3) takes 3 and is defeated.
## GIVEN
CommonSetup: bbw/bbk/{myResources:4;handCardIds:ASH_258;myBaseDamage:3}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:0
P2GROUNDARENACOUNT:0
