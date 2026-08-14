# ExpAfterBaseHealed
#// TS26_38 Dooku's Solar Sailer (Unit 2/4 space, cost 3) — When Played/On Attack: if a base was healed
#// this phase, give an Experience token to another Separatist unit. Jendirian Valley (Restore 1) attacks
#// and heals P1's base; then playing the Sailer gives 1 Experience to the friendly Battle Droid (Separatist).
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_38;myBaseDamage:3}
WithP1SpaceArena: TS26_18:1:0
WithP1GroundArena: TS26_T01:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# NoExpWithoutHeal
#// TS26_38 Dooku's Solar Sailer — with no base healed this phase, playing the Sailer gives no Experience:
#// the friendly Battle Droid stays at 1 power (no decision, no Experience).
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_38}
WithP1GroundArena: TS26_T01:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:1

---

# WhenPlayed_HealedInAPreviousPhase_NoExp
#// TS26_38 Dooku's Solar Sailer — "if a base was healed THIS PHASE". Repair (SOR_074) heals P1's base
#// from 3 to 0, then both players pass out the action phase and decline to resource. Playing the Sailer in
#// the NEW phase finds the condition unmet: it still enters play (space arena 1) but the friendly Battle
#// Droid stays at 1 power. Guards that the flag is cleared at the phase boundary rather than the round's.

## GIVEN
CommonSetup: byk/rrk/{myResources:4;myBaseDamage:3}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1Hand: [SOR_074 TS26_38]
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:1

---

# WhenPlayed_OPPONENTSBaseHealed_StillGivesExp
#// TS26_38 Dooku's Solar Sailer — the condition is "A base", not "your base". P2 heals THEIR OWN base
#// from 3 to 0 with Repair; P1 then plays the Sailer and the Experience offer appears, naming exactly the
#// friendly Battle Droid, which ends at 2 power.

## GIVEN
CommonSetup: byk/byk/{myResources:4;theirResources:4;theirBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: SOR_074
WithP1Hand: TS26_38
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myBase-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:2

---

# WhenPlayed_BaseCouldNotBeHealed_NoExp
#// TS26_38 Dooku's Solar Sailer — an ATTEMPTED heal is not a heal. With "bases can't be healed this phase"
#// active (SWU_NOHEAL_BASE, as set by SOR_160 Wolffe), Repair resolves against P1's damaged base and
#// removes nothing: the base stays at 3, so the Sailer's condition is unmet and no offer is raised.
#// Discriminating against a naive "a heal effect resolved this phase" flag, which this would satisfy.

## GIVEN
CommonSetup: byk/rrk/{myResources:4;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GlobalEffect: SWU_NOHEAL_BASE
WithP1Hand: [SOR_074 TS26_38]
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:1
P1NODECISION

---

# OnAttack_HealedThisPhase_GivesExp
#// TS26_38 Dooku's Solar Sailer — the same clause on the ON ATTACK half. The Sailer is already in play;
#// Repair heals P1's base from 3 to 0, then the Sailer attacks P2's base for 2 and the Experience offer
#// fires, putting the Battle Droid at 2 power.

## GIVEN
CommonSetup: byk/rrk/{myResources:4;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_074
WithP1SpaceArena: TS26_38:1:0
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:0
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:2

---

# OnAttack_HealedInAPreviousPhase_NoExp
#// TS26_38 Dooku's Solar Sailer — the On Attack half also reads THIS phase. The heal happens, the phase is
#// passed out and the next round's resource step declined, and only then does the Sailer attack: it deals
#// its 2 to P2's base and raises no offer.

## GIVEN
CommonSetup: byk/rrk/{myResources:4;myBaseDamage:3}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1Hand: SOR_074
WithP1SpaceArena: TS26_38:1:0
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:0
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:1
P1NODECISION

---

# OnAttack_OPPONENTSBaseHealed_StillGivesExp
#// TS26_38 Dooku's Solar Sailer — "A base" on the On Attack half too. P2 heals their own base with Repair;
#// the Sailer then attacks that base for 2 and still hands the Battle Droid an Experience token.

## GIVEN
CommonSetup: byk/byk/{myResources:4;theirResources:4;theirBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: SOR_074
WithP1SpaceArena: TS26_38:1:0
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myBase-0
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:2

---

# OnAttack_NoHealThisPhase_NoExp
#// TS26_38 Dooku's Solar Sailer — the plain negative on the On Attack half: nothing was healed, so the
#// attack on P2's base resolves for 2 with no offer and the Battle Droid stays at 1 power.

## GIVEN
CommonSetup: byk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: TS26_38:1:0
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:1
P1NODECISION
