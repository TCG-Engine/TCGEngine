# ConsularDiscount_ExploitUnit_TheDiscountSurvivesTheExploitPicker
#// A play begun WITH a discount ("it costs 2 less") can stop at a pre-payment picker — Exploit, TWI_116
#// Clone's copy choice — before it is paid for. The discount has to ride that picker to the charge.
#// EXPLOIT_RESOLVE used to receive only the Exploit numbers, so the play's own discount was DROPPED:
#// its affordability gate priced the card without it and aborted a legal play ("not enough resources
#// even after Exploit — nothing was defeated"), and when it did not abort the charge was too high.
#// Found 2026-09-14 while routing HMW_204 Nightbrother's play through the full play path, but it predates
#// that: every "play a unit, it costs N less" continuation that reaches an Exploit unit hit it.
#//
#// LOF_094 Jedi Consular ("Action [Exhaust, use the Force]: Play a unit from your hand. It costs 2 less")
#// plays TWI_182 Infiltrating Demolisher (Exploit 1, cost 4, on-aspect here). 4 - 2 (Consular) - 2
#// (exploiting SEC_080) = 0, so both resources are untouched. Without the Consular discount the charge
#// is 4 - 2 = 2 and nothing is left. (Consular offers only what is affordable WITHOUT Exploit — 4 - 2 = 2
#// — which is why the fixture holds 2 and not 1.)

## GIVEN
CommonSetup: gyk/rrk/{myResources:2;myLeader:HMW_016}
P1OnlyActions: true
WithP1Force: true
WithP1Hand: TWI_182
WithP1GroundArena: LOF_094:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_182
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:2

---

# NightbrotherDiscount_Clone_TheDiscountSurvivesTheCopyChoice
#// The same seam on TWI_116 Clone's copy choice: CLONE_COPY_CHOICE also dropped the play's discount.
#// HMW_204 Nightbrother plays Clone (cost 7, Command — on-aspect on a Command base) from the discard at
#// 3 less, and it copies the enemy SOR_095. 11 resources: 7 for Nightbrother, 7 - 3 = 4 for Clone,
#// 0 left. Without the discount the Clone costs 7 against the 4 remaining and the play fails.
#// Nightbrother's "enters play ready" rider also has to survive the copy choice.

## GIVEN
CommonSetup: gyk/rrk/{myResources:11;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: TWI_116
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:0
P1RESAVAILABLE:0
