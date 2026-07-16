# EnemyDefeatEvent_Immune
#// LAW_149 Rey, Skywalker (9/9) — "This unit can't be defeated by enemy card abilities." P2 plays
#// SHD_079 Rival's Fall ("Defeat a unit.") with Rey as the only unit → it auto-targets Rey, but the
#// enemy-ability defeat is blocked → Rey survives. (Engine: SWUDefeatUnit enemy-actor + SWUAvoidsDefeat.)

## GIVEN
CommonSetup: rrk/bbw/{theirResources:6;theirhandCardIds:SHD_079}
WithActivePlayer: 2
WithP1GroundArena: LAW_149:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_149

---

# OwnDefeatEvent_Defeats
#// Guard: the immunity is to ENEMY abilities only — your OWN card ability still defeats Rey. P1 plays
#// SHD_079 on their own Rey (only unit) → actor is the controller, so the defeat is NOT blocked → Rey
#// is defeated.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;myhandCardIds:SHD_079}
P1OnlyActions: true
WithP1GroundArena: LAW_149:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0

---

# StateBasedDefeat_Defeats
#// Guard: the immunity is to card ABILITIES only — Rey still dies to no remaining HP (state-based /
#// combat). Rey (9/9, pre-damaged 8) attacks a 3/1 (SOR_128) → the 3 counter damage takes her to 11 ≥ 9
#// → she is defeated by lethal combat damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_149:1:8
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# EnemyTakeControl_Immune
#// "Opponents can't take control of this unit." P2 plays SOR_224 Change of Heart ("Take control of a
#// non-leader unit.") with Rey as the only target → the take-control is blocked → Rey stays under P1's
#// control (P2 gains nothing).

## GIVEN
CommonSetup: rrk/yyw/{theirResources:6;theirhandCardIds:SOR_224}
WithActivePlayer: 2
WithP1GroundArena: LAW_149:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_149
P2GROUNDARENACOUNT:0
