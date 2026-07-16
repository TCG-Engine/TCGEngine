# BasesCantBeHealed
#// TWI_132 Confederate Tri-Fighter (Unit, Space) — "Bases can't be healed." TWI_247 (Restore 3) attacks the
#// enemy base but its Restore can't heal P1's base (stays at 5 damage) while the Tri-Fighter is in play.
## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:5}
P1OnlyActions: true
WithP1SpaceArena: TWI_132:1:0
WithP1GroundArena: TWI_247:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:5
