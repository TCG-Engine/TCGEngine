# AttackEnd_SelfAndEnemySentinel
#// SEC_048 (Ground, 7/7) — When this unit completes an attack: give this unit AND an enemy unit
#//   Sentinel for this phase. SEC_048 attacks P2's base; on attack-end it gains Sentinel and grants the
#//   only enemy unit (SOR_046) Sentinel too.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_048:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenPlayed_SelfAndEnemySentinel
#// SEC_048 (Ground, 7/7, cost 6, Vigilance/Heroism) — When Played: give this unit AND an enemy unit
#//   Sentinel for this phase. P1 plays SEC_048 (on-aspect under bw leader → cost 6); the only enemy
#//   unit (SOR_046) auto-resolves as the Sentinel target.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_048

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
