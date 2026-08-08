# OppAttackedBase_PlayUnitAmbush
#// SEC_194 Fully Armed and Operational (Event, cost 1, Cunning/Villainy, Trick, Plot)
#//   "If an opponent attacked your base during their previous action this phase, play a unit from your
#//    hand. Give it Ambush for this phase."
#// P2's space unit (SOR_237) attacks P1's base for 2 (P2's previous action = a base attack). P1 then plays
#// SEC_194: the condition is met, so P1 plays SOR_095 from hand, and it enters with Ambush granted for the
#// phase. P2 has no GROUND unit, so the Ambush attack has no legal target and the unit simply enters.
#// (HASKEYWORD:Ambush on a vanilla SOR_095 proves the grant.)

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1DISCARDCOUNT:1

---

# OppDidNotAttackBase_NoEffect
#// SEC_194 Fully Armed and Operational — condition guard: if the opponent's previous action was NOT a
#// base attack, SEC_194 does nothing. P2 passes (its previous action is a pass, not a base attack), then
#// P1 plays SEC_194 — no unit is played; SOR_095 stays in hand.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095

## WHEN
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# OppClaimedInitiative_NoEffect
#// SEC_194 Fully Armed and Operational — condition guard: taking the initiative is not a base attack.
#// P2's previous action is claiming the initiative, so when P1 plays SEC_194 the condition is not met
#// and no unit is played; SOR_095 stays in hand.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095

## WHEN
- P2>Claim
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# OppAttackedUnitNotBase_NoEffect
#// SEC_194 Fully Armed and Operational — condition guard: attacking a UNIT is not attacking your base.
#// P2's Imperial Dark Trooper (SEC_080) attacks P1's Krayt Dragon (SHD_172, survives), so P2's previous
#// action was a unit attack, not a base attack. SEC_194 does nothing; SOR_095 stays in hand and no unit
#// is played (P1 keeps only the Krayt on the board).

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095
WithP1GroundArena: SHD_172:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# OppAttackedBase_AmbushAttackResolves
#// SEC_194 Fully Armed and Operational — condition met, and the granted Ambush actually resolves an
#// attack. P2's space unit (SOR_237) attacks P1's base. P1 plays SEC_194 and plays Imperial Dark Trooper
#// (SEC_080, 3/3) from hand with Ambush; it Ambush-attacks P2's ground Imperial Dark Trooper (3/3). Both
#// trade 3 damage and are defeated, proving the Ambush attack ran.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SEC_080
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# OppPassed_NoEffect
#// SEC_194 Fully Armed and Operational — condition guard: a PASS is not a base attack. P2's previous
#// action is a pass, so the condition fails and SOR_095 stays in hand. (Distinct from
#// OppClaimedInitiative_NoEffect: claiming also passes, but a bare pass is its own action type.)
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095
## WHEN
- P2>Pass
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# OppHasNotActedThisPhase_NoEffect
#// SEC_194 Fully Armed and Operational — condition guard: with NO previous opponent action at all this
#// phase there is nothing to qualify, so the condition fails. P1 acts first in the phase and plays SEC_194
#// before P2 has done anything.
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_194
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1


---

# PlayerTakesAnActionInBetween_ConditionStillMet
#// SEC_194 Fully Armed and Operational — the condition reads THE OPPONENT'S previous action, not the
#// globally previous action. P2 attacks P1's base; P1 then takes an action of their own (Kazuda Xiono's
#// leader Action, JTL_018, which grants "Take an extra action after this one") and plays SEC_194 with that
#// extra action. P2's previous action is still the base attack, so the condition holds and the unit is
#// played with Ambush.
#// (Kazuda's extra action is what makes this reachable at all: SWUSim alternates strictly, so without it
#// P2 would have to act in between and would overwrite their own "previous action".)
## GIVEN
CommonSetup: yyk/rrk/{myLeader:JTL_018}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 12
WithP1Hand: SEC_194
WithP1Hand: SOR_095
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush

---

# OppAttackedBaseViaAnEVENT_ConditionStillMet
#// SEC_194 Fully Armed and Operational — "an opponent ATTACKED your base during their previous action"
#// counts the attack, not how it was initiated. P2's previous action is playing SOR_220 Surprise Strike,
#// which sends their unit at P1's base rather than declaring the attack directly. The condition is met
#// and P1 plays SOR_095 with Ambush.
#// Companion to OppAttackedBase_PlayUnitAmbush (a plainly declared attack) and to
#// OppPlayedACardThatDidNotAttackBase_NoEffect below.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 2
WithP1Resources: 10
WithP2Resources: 4
WithP1Hand: SEC_194
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_220

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirBase-0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1BASEDMG:6
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# OppPlayedACardThatDidNotAttackBase_NoEffect
#// SEC_194 Fully Armed and Operational — the negative for the section above: P2's previous action is
#// playing a card that does NOT initiate a base attack (SHD_178 Daring Raid aimed at a unit). The
#// condition fails, so Fully Armed does nothing and SOR_095 stays in P1's hand.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 2
WithP1Resources: 10
WithP2Resources: 4
WithP1Hand: SEC_194
WithP1Hand: SOR_095
WithP1GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1HANDCOUNT:1

---

# PlayedViaPLOT_DuringOurOwnLeaderDeploy_ConditionStillMet
#// SEC_194 Fully Armed and Operational — "If an opponent attacked your base during THEIR PREVIOUS ACTION
#// this phase…" plus Plot. Playing it via Plot is the sharp case: the Plot window opens on OUR leader
#// deploy, so an action of OURS necessarily sits between the opponent's base attack and this card
#// resolving. The condition asks about the OPPONENT'S own previous action, so our deploy is irrelevant
#// and the card still fires.
#// P2's SpecForce Soldier attacks P1's base; P1 deploys and Plots SEC_194 out of its resources for 1,
#// plays SOR_210 Swoop Racer (3) from hand with Ambush, and the Ambush attack kills the Soldier.
#// Previously the deploy's own end-of-action overwrote the single global "last action" record and
#// silently switched the condition off — the card resolved for nothing.

## GIVEN
CommonSetup: yyk/rrk/{theirBase:SOR_021}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 1:SEC_194:1,5:SOR_095:1
WithP1Hand: SOR_210
WithP2GroundArena: SOR_140:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_210
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:2

---

# OppAttackedBaseViaAnACTIONABILITY_ConditionStillMet
#// SEC_194 Fully Armed and Operational — "attacked your base during their previous action" doesn't care
#// HOW the attack was initiated. The sibling section covers an EVENT; this is the third route, a unit's
#// Action ability. P2 spends TWI_105 Steadfast Senator's "Action [2 resources, Exhaust]: Attack with a
#// unit. It gets +2/+0 for this attack", sending SOR_128 into P1's base for 5 (3 + 2). P1 then plays
#// SEC_194 and the condition holds: SOR_210 Swoop Racer comes out of hand WITH Ambush. P1 declines the
#// Ambush attack here so the section stays about the condition; the attack itself is covered by
#// OppAttackedBase_AmbushAttackResolves.

## GIVEN
CommonSetup: yyk/ggk/{myResources:5;handCardIds:SEC_194,SOR_210;theirResources:2}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2GroundArena: TWI_105:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P2>UseUnitAbility:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_210
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1HANDCOUNT:0
P2GROUNDARENACOUNT:2
