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

---

# PilotAttachedByRelocation_Bounce
#// JTL_223 Razor Crest — "When a Pilot ATTACHES to this unit: you may return a non-leader unit that costs
#// 2 or less … to its owner's hand." The trigger keys off the ATTACH event, so it must fire when a Pilot
#// arrives by a route other than being played on it. Here P2 plays SOR_077 Takedown to defeat P1's JTL_049
#// L3-37, whose own replacement instead attaches her as a Pilot upgrade to a friendly pilot-less Vehicle —
#// Razor Crest. That attach fires Razor Crest's ability and P1 returns the 2-cost SOR_095 to hand.
#// Companion to PilotAttach_Bounce (a Pilot PLAYED onto it) and NonPilotUpgrade_NoBounce.
#// REGRESSION GUARD: the relocation paths bagged the host's pilot-attach reaction but never FLUSHED the
#// pending-trigger bag (the normal play ceremony does that), so JTL_223 looked like it simply never fired.
#// ⚠ P1 is the non-active player here, so its reaction needs an explicit P1>Drain before answering.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_077
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: JTL_223:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>Drain
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_223
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
