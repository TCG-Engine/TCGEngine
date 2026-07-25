# CantAttack
#// JTL_059 Corporate Defense Shuttle — This unit can't attack. Attempting to attack the base is a no-op:
#// no damage is dealt and the shuttle stays ready.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_059:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:0
P1SPACEARENAUNIT:0:CARDID:JTL_059
P1SPACEARENAUNIT:0:READY

---

# NotSelectableForEventGrantedAttack
#// JTL_059 Corporate Defense Shuttle — "This unit can't attack" is enforced at ATTACKER-SELECTION time for
#// an event-granted attack, not only at resolution. P1 plays Outflank (SHD_128, "Attack with 2 units") with
#// a shuttle (mySpaceArena-0, can't attack) and a normal SOR_237 X-Wing (mySpaceArena-1): only the X-Wing
#// is a selectable attacker; the shuttle is excluded from the picker.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_128
WithP1Resources: 5
WithP1SpaceArena: [JTL_059:1:0 SOR_237:1:0]
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEHAS:mySpaceArena-1
P1SELECTABLEHAS:myGroundArena-0
P1SELECTABLENOT:mySpaceArena-0
