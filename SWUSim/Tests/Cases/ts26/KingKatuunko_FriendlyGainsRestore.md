# TS26_016 King Katuunko (Unit 2/4, cost 2) — When Played: all units (incl. enemy) gain Restore 1 for
# this phase. The friendly SEC_080 already in play gains Restore 1: when it attacks the enemy base, P1's
# base heals 1 (damage 3 → 2) while combat deals 3 to the enemy base.
## GIVEN
CommonSetup: bgw/rrk/{myResources:2;myBaseDamage:3;handCardIds:TS26_016}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:2
P2BASEDMG:3
