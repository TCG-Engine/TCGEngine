# PilotAttach_Bounce
#// JTL_223 Razor Crest — When a Pilot attaches to this unit: you may return a non-leader unit costing 2 or
#// less to its owner's hand. Playing JTL_034 onto Razor Crest lets P1 bounce P2's SOR_095 (cost 2).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: JTL_034
WithP1SpaceArena: JTL_223:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# NonPilotUpgrade_NoBounce
#// JTL_223 Razor Crest — the bounce triggers only when a PILOT attaches. Attaching a non-pilot upgrade
#// (SOR_054 Jedi Lightsaber) triggers nothing, so the enemy SOR_095 is not returned.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: SOR_054
WithP1SpaceArena: JTL_223:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
