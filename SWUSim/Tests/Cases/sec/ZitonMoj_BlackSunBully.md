# TakeInitiative_Deal2Base
#// SEC_168 Ziton Moj (Unit, Aggression) — When you take the initiative: deal 2 to a base. P1 claims
#//   initiative → deal 2 to P2's base.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1GroundArena: SEC_168:1:0

## WHEN
- P1>Claim
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2

---

# OpponentClaims_NoTrigger
#// SEC_168 Ziton Moj — "When YOU take the initiative." P1 passes and P2 CLAIMS the initiative instead.
#//   Ziton belongs to P1, who did not take the initiative, so no damage is dealt to any base.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1GroundArena: SEC_168:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P2>Claim

## EXPECT
P1BASEDMG:0
P2BASEDMG:0

---

# BothPass_NoTrigger
#// SEC_168 Ziton Moj — nobody takes the initiative: both players pass. Ziton does not fire.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1GroundArena: SEC_168:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1BASEDMG:0
P2BASEDMG:0

---

# StolenZiton_TriggersForTheNEWControllerWhenTHEYClaim
#// SEC_168 Ziton Moj — "When YOU take the initiative" is read from the unit's CURRENT controller. P2 takes
#// control of P1's Ziton with SOR_224 Change of Heart and then claims the initiative: the deal-2 trigger
#// is raised on P2'S queue, i.e. it now belongs to P2.
#// The sibling OpponentClaims_NoTrigger is the discriminator — there P2 makes the very same claim while
#// Ziton is still P1's and NO prompt appears at all. Asserting the prompt's owner (rather than the
#// resulting damage) keeps this section clear of the regroup that the claim triggers, during which Change
#// of Heart hands Ziton back and both bases pick up unrelated damage.

## GIVEN
CommonSetup: rrk/yyk/{theirResources:6;theirHandCardIds:SOR_224}
WithActivePlayer: 2
WithP1GroundArena: SEC_168:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Claim

## EXPECT
P2DECISIONTOOLTIP:Deal_2_to_a_base
P1NODECISION
