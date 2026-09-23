#// ASH_102 Ravager (space, 8/10): "When you play a unit: You may have it deal damage equal to its power to a unit in
#// the same arena." Played Lepi Lookout's Shielded triggers with it, and P1 orders them (CR 7.6.9; CR 7.6.13.b puts
#// Shielded in the When Played window). Ravager is in SPACE, but "the same arena" is the PLAYED unit's: Lepi is a
#// ground unit, so its 3 damage goes to a ground unit.
#//
#// FOUND BY: sweep run 3, retro #1 (2026-09-13): ORDER ASH_102:ASH_102 + LAW_038:Shielded (1×,
#//   ahsoka_blue.piett_blue.s005, round 8, seat 2). Not seen in run 2 (its Shielded pairs were
#//   ASH_048 and LAW_118 — see Ravager_PlayTrigger_vsWhenPlayedAndShielded.md).
#//
#// Cards: LAW_038 Lepi Lookout 3/1 ground (Shielded, Overwhelm) · SOR_095 Battlefield Marine 3/3 (P2) · SOR_237
#//   Alliance X-Wing 2/3 (P2, space — the wrong-arena control: it must not be offered).
#//
# BothTriggers_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_038
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# ShieldedFirst_ThenRavager_LepiDealsThreeToTheMarine_NotTheXWing
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_038
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Shielded
## EXPECT
P1HASDECISION
P1SELECTABLENOT:theirSpaceArena-0
P1SELECTABLEHAS:theirGroundArena-0

---

# ShieldedFirst_ThenRavager_TheMarineIsDefeated_LepiKeepsItsShield
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_038
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Shielded
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:LAW_038
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# RavagerFirst_TheMarineIsDefeated_ThenTheShieldArrives
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_038
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION

---

# RavagerDeclined_NoDamage_LepiStillShielded
## GIVEN
CommonSetup: bbk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1SpaceArena: ASH_102:1:0
WithP1Hand: LAW_038
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:ASH_102
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION
