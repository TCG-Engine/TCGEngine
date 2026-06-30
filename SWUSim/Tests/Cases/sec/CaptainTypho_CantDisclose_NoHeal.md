# SEC_098 Captain Typho — fizzle: P2's hand can't cover CommandHeroism (only SOR_225, Villainy), so
#   PlayerCanDisclose is false and NO disclose decision is queued. Combat auto-resolves with no answer
#   needed and the base is not healed. Proves the no-valid-disclose path doesn't hang combat.

## GIVEN
CommonSetup: ggw/ggw/{theirBaseDamage:3;theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_098:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION
P2NODECISION
