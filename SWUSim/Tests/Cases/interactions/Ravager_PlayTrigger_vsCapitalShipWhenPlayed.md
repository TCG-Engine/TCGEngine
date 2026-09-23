#// ASH_102 Ravager ("When you play a unit: You may have it deal damage equal to its power to a unit in the same
#// arena.") ordered against a played Capital Ship's own When Played — both P1's, so P1 orders them (CR 7.6.9). Each
#// 9-power ship hits a space unit through Ravager.
#//
#// FOUND BY: sweep run 5, retro #5 (2026-09-14): ORDER ASH_102:ASH_102 + SEC_142:WhenPlayed / ASH_149:WhenPlayed /
#//   JTL_143:WhenPlayed (1× each, dedra_colossus.piett_red s092 / s004 / s088). Newly reachable:
#//   Piett red now casts its Capital Ships (Phase 1b part 2, 'keep').
#//
#// Cards: SEC_142 Fulminatrix 9/7 ("When Played/On Attack: You may deal 4 damage to a ground unit.") · ASH_149
#//   Eviscerator 9/7 ("…When Played/On Attack: Give 2 Advantage tokens to each other friendly unit.") · JTL_143
#//   Devastator 9/6 ("You assign all indirect damage you deal to opponents. / When Played: Deal 4 indirect damage to
#//   each opponent.") · SOR_237 Alliance X-Wing 2/3 (P2, space) · SOR_164 Wampa 4/5 (P2, ground) · SOR_095
#//   Battlefield Marine (P1, ground).
#//
# Fulminatrix_BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: SEC_142
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# Fulminatrix_RavagerFirst_NineDefeatsTheXWing_ThenFourOnTheWampa
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: SEC_142
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION

---

# Eviscerator_BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: ASH_149
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# Eviscerator_AdvantageFirst_RavagerAndTheMarineGetTwo_ThenNineDefeatsTheXWing
#// "Each OTHER friendly unit": Ravager and the Marine get 2 Advantage tokens each, Eviscerator none.
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: ASH_149
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:WhenPlayed
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_102
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:2
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1NODECISION

---

# Devastator_BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: JTL_143
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# Devastator_RavagerFirst_NineDefeatsTheXWing_ThenP1AssignsTheFourIndirect
#// Devastator: P1 assigns the indirect damage it deals to the opponent — after Ravager's 9 removed the X-Wing,
#//   only the Wampa and the base are left; all 4 go to the base.
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: JTL_143
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirBase-0:4
## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:4
P1NODECISION
