#// Support (CR 20.a): Gozanti Assault Carrier is played and Eviscerator attacks through its Support, gaining
#// Gozanti's "On Attack: This unit gains Sentinel for this phase" — "this unit" is the ATTACKER (see
#// Support_GrantedOnAttack_LandsOnTheAttacker.md). Eviscerator's own On Attack triggers on the same declaration;
#// both are P1's, so P1 orders them (CR 7.6.9). Eviscerator gives 2 Advantage tokens to each OTHER friendly unit —
#// the just-played Gozanti and a ground Marine included, never Eviscerator itself — and its static ("Advantage
#// tokens on friendly units lose all abilities. (They aren't defeated after combat.)") keeps them after the attack.
#//
#// FOUND BY: sweep run 3, retro #9 (2026-09-14): ORDER ASH_099:SupportOnAttack + ASH_149:OnAttack (1×,
#//   piett_red.lando_blue.s028, round 7, seat 1). Not seen in run 2.
#//
#// Cards: ASH_099 Gozanti Assault Carrier 4/6 space (Support; On Attack: gains Sentinel for this phase) · ASH_149
#//   Eviscerator 9/7 space (the static above; "When Played/On Attack: Give 2 Advantage tokens to each other
#//   friendly unit.") · ASH_T02 Advantage (+1/+0) · SOR_095 Battlefield Marine 3/3 (P1, ground). P2 has no unit,
#//   so the attack goes to the base for Eviscerator's 9.
#//
# BothOnAttacks_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: bbk/ggw/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_099
WithP1SpaceArena: ASH_149:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# SupportFirst_EviscGainsSentinel_ThenAdvantageOnGozantiAndTheMarine_TheyStayAfterCombat
## GIVEN
CommonSetup: bbk/ggw/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_099
WithP1SpaceArena: ASH_149:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>ResolveTrigger:SupportOnAttack
## EXPECT
P2BASEDMG:9
P1SPACEARENAUNIT:0:CARDID:ASH_149
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:1:CARDID:ASH_099
P1SPACEARENAUNIT:1:NOTKEYWORD:Sentinel
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1NODECISION

---

# EviscFirst_SameEndState_TheOrderChangesNothingHere
## GIVEN
CommonSetup: bbk/ggw/{myLeader:SOR_005;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: ASH_099
WithP1SpaceArena: ASH_149:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>ResolveTrigger:OnAttack
## EXPECT
P2BASEDMG:9
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1NODECISION
