# SEC_168 Ziton Moj (Unit, Aggression) — When you take the initiative: deal 2 to a base. P1 claims
#   initiative → deal 2 to P2's base.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1GroundArena: SEC_168:1:0

## WHEN
- P1>Claim
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
