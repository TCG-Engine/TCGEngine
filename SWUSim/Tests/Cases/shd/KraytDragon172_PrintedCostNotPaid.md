# SHD_172 Krayt Dragon — damage is the PRINTED cost, not the amount actually paid. P2's base/leader (bbw =
# Vigilance/Heroism) covers neither of SEC_080's aspects (Command,Villainy), so P2 pays 2 + 4 penalty = 6.
# Krayt still deals only the PRINTED 2 to P2's base (proving it's not the 6 paid).

## GIVEN
CommonSetup: rrk/bbw/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P2RESAVAILABLE:0
