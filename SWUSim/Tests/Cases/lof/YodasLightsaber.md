# UseForce_HealBase
#// LOF_102 Yoda's Lightsaber — When Played: may use the Force → heal 3 damage from a base. P1 plays it onto
#// SOR_095, uses the Force, and heals P1's own base (5 → 2).

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_102;myBaseDamage:5}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P1NOFORCE
P1BASEDMG:2
