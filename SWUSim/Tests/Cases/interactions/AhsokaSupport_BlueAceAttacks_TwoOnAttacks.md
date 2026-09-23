#// Ahsoka Tano (ASH_009) deploying: her Support lends Blue Ace her On Attack ("You may give a unit with less power
#// than this unit +2/+0 for this phase" — "this unit" is the ATTACKER), and Blue Ace's own On Attack ("Ready an
#// exhausted enemy unit.") triggers on the same attack. Both P1's — P1 orders them (CR 7.6.9). Same family as
#// BlueAce_AttacksThroughN1Support_TwoOnAttacks.md and Ahsoka_Deploy_SupportAndPlot.md.
#//
#// FOUND BY: sweep run 5, retro #9 (2026-09-14): ORDER ASH_009:SupportOnAttack + SEC_204:OnAttack (1×,
#//   talzin_force.ahsoka_yellow.s059).
#//
#// Cards: SEC_204 Blue Ace 4/5 space · SOR_095 Battlefield Marine 3/3 (P2, EXHAUSTED — Blue Ace's only target, and
#//   with 3 < 4 power also the only unit Ahsoka's granted buff could pick). P2 has no space unit: the attack goes to
#//   the base.
#//
# BothOnAttacks_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1SpaceArena: SEC_204:1:0
WithP2GroundArena: SOR_095:0:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# BlueAceFirst_TheMarineIsReadied_AhsokasBuffDeclined_FourToTheBase
## GIVEN
CommonSetup: ggw/ngw/{myLeader:ASH_009}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8:SOR_095:1
WithP1SpaceArena: SEC_204:1:0
WithP2GroundArena: SOR_095:0:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:mySpaceArena-0
- P1>ResolveTrigger:OnAttack
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:READY
P2BASEDMG:4
TURNPLAYER:2
