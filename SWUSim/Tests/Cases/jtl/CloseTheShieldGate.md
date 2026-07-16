# PreventThenConsume
#// JTL_074 Close the Shield Gate — "Choose a base. The next time damage would be dealt to it this phase,
#// prevent that damage." P1 protects the opponent's base, then attacks it twice with 2-power X-Wings.
#// The FIRST attack's 2 damage is prevented; the SECOND deals 2 (proves prevent + one-shot consume).

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:JTL_074}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:1:BASE

## EXPECT
P2BASEDMG:2
