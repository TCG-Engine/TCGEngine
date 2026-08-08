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
