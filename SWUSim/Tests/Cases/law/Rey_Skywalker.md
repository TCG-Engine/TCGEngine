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

---

# EnemyUnitDefeat_Immune
#// LAW_149 Rey — the immunity covers enemy UNIT abilities too, not just events. P2 plays TWI_036
#// Devastating Gunship ("When Played: Defeat an enemy unit with 2 or less remaining HP"). Rey (9/9, damaged
#// 7 = 2 remaining HP) is the only legal target, but the enemy-ability defeat is blocked → Rey survives.

## GIVEN
CommonSetup: rrk/bbk/{theirResources:6;theirhandCardIds:TWI_036}
WithActivePlayer: 2
WithP1GroundArena: LAW_149:1:7

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_149

---

# OwnUnitDefeat_Defeats
#// LAW_149 Rey — the immunity is to ENEMY abilities only, so your OWN unit's ability still defeats her. P1
#// plays SOR_038 Count Dooku ("When Played: You may defeat a unit with 4 or less remaining HP") and targets
#// his own Rey (damaged 7 = 2 remaining HP) → she is defeated.

## GIVEN
CommonSetup: bbk/rrk/{myResources:7;myhandCardIds:SOR_038}
P1OnlyActions: true
WithP1GroundArena: LAW_149:1:7

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_038

---

# EnemyEventPick_Immune
#// LAW_149 Rey — "cannot be defeated by opponent event even if you pick". P1 plays SOR_041 Power of the Dark
#// Side ("An opponent chooses a unit they control. Defeat that unit."). The opponent (P2) picks their own
#// Rey, but the defeat originates from an enemy (P1) ability → Rey is not defeated. Both P2 units survive.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3;myhandCardIds:SOR_041}
WithP2GroundArena: [LAW_149:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2

---

# GiveControlToOpponent_Immune
#// LAW_149 Rey — "cannot be given control of to an opponent". P1 plays TWI_204 Impropriety Among Thieves
#// (each player swaps control of a chosen unit). P1 picks their own Rey and P2's SOR_095 marine; P1 gains
#// the marine, but Rey can't be handed to P2 so she stays under P1's control. At regroup the marine reverts
#// to P2 while Rey remains P1's.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4;myhandCardIds:TWI_204}
WithP1GroundArena: [LAW_149:1:0 SOR_164:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:LAW_149
