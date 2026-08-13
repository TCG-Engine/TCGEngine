# NoEligibleTargets
#// SOR_169 Keep Fighting — no units with power ≤ 3 means no effect.
#// SOR_148 (Guerilla Attack Pod, 6/4) has power 6 > 3; Keep Fighting fizzles.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
WithP1GroundArena: SOR_148:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# ReadiesUnit
#// SOR_169 Keep Fighting — readies the only eligible unit (power ≤ 3).
#// SOR_095 (Battlefield Marine, 3/3) is exhausted; Keep Fighting auto-picks it and readies it.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# TokenUnitIsALegalTarget
#// The Open Fire family fix: "Ready a UNIT with 3 or less power" is unqualified, so an exhausted Clone
#// Trooper token (TWI_T02, 2/2) is a legal target and readies. Its exhausted real neighbour proves the
#// choice was genuinely offered (two legal targets — no auto-resolve) — and being the FIRST section to
#// offer two targets, this also caught the block-ordering bug where the READY_UNIT continuation
#// (block 0) jumped the MZCHOOSE (block 1) and the pick readied nothing.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_169}
P1OnlyActions: true
WithP1GroundArena: [TWI_T02:0:0 SOR_095:0:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
