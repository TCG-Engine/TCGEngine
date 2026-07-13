# TWI_069 Roger Roger — with NO other friendly Battle Droid token to receive it, the "When Defeated"
# re-attach has no target, so Roger Roger goes to its owner's discard normally when its host is defeated.
# (Discard holds Roger Roger at idx0 and the defeated host token at idx1.)
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:TWI_069
