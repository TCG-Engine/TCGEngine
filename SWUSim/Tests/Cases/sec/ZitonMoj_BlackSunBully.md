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
