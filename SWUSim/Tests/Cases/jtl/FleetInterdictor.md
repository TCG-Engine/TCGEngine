# WhenDefeated_DefeatsCheapSpaceUnit
#// JTL_040 Fleet Interdictor — When Defeated: You may defeat a space unit that costs 3 or less. JTL_040
#// (6/6, pre-damaged to 1 remaining HP) attacks SOR_225 and is defeated by the counter (SOR_225 is also
#// defeated by JTL_040's 6 power). JTL_040's When Defeated then lets P1 defeat the remaining cost-2
#// SOR_237. (Driven by the active player so the combat whenDefeated orchestration resolves cleanly.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_040:1:5
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0

---

# ExhaustImmune
#// LOF_040 Kylo Ren's Lightsaber — "If attached unit is a Force unit, it gains: 'This unit can't be
#// exhausted by enemy card abilities.'" Rey (a Force unit) carries the Lightsaber; P1 plays Evasive
#// Maneuver (JTL_262: exhaust a unit) at her, but she stays ready.

## GIVEN
CommonSetup: yyk/rrk/{myResources:8;handCardIds:JTL_262}
P1OnlyActions: true
WithP2GroundArena: LAW_149:1:0
WithP2GroundArenaUpgrade: 0:LOF_040

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_149
P2GROUNDARENAUNIT:0:READY
