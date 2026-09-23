#// When The Invisible Hand's attack ends, two of P1's triggers fire together: its own "When this unit completes an
#// attack (and survives): You may search the top 8 cards of your deck for a Droid unit…" and the Advantage token's
#// "When attached unit's attack or defense ends: Defeat this upgrade." P1 orders them (CR 7.6.9). The Advantage's
#// +1/+0 already counted in combat (7 to the base) whichever goes first.
#//
#// FOUND BY: sweep run 5, retro #1 (2026-09-14): ORDER ASH_T02:AdvantageShed + JTL_089:OnAttackEnd (3×, e.g.
#//   ahsoka_blue.piett_red.s029). Newly reachable: Piett red now CASTS its Capital Ships (Phase 1b part 2,
#//   the 'keep' feature) instead of resourcing them.
#//
#// Cards: JTL_089 The Invisible Hand 6/6 space · ASH_T02 Advantage (+1/+0) · LOF_158 (a Droid, cost 3 → draw only,
#//   no free play; see jtl/TheInvisibleHand_CrawlingWithVultures.md).
#//
# AttackEnds_BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_005;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_089:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
WithP1Deck: [LOF_158 SOR_095 SOR_237]
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:7
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# AdvantageShedFirst_ThenTheSearchDrawsTheDroid
## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_005;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_089:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
WithP1Deck: [LOF_158 SOR_095 SOR_237]
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>ResolveTrigger:AdvantageShed
- P1>AnswerDecision:LOF_158
## EXPECT
P2BASEDMG:7
P1HANDCOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1NODECISION

---

# SearchFirst_ThenTheAdvantageIsShed
## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_005;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_089:1:0
WithP1SpaceArenaUpgrade: 0:ASH_T02
WithP1Deck: [LOF_158 SOR_095 SOR_237]
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>ResolveTrigger:OnAttackEnd
- P1>AnswerDecision:LOF_158
## EXPECT
P2BASEDMG:7
P1HANDCOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1NODECISION
