# SOR_068 Cargo Juggernaut — the heal is conditional on controlling ANOTHER Vigilance unit.
# Here P1's only other unit is Battlefield Marine (Command, not Vigilance), so the condition
# fails and the base stays damaged. The Shielded token is still granted (unconditional). The
# Juggernaut is itself Vigilance, but "another" excludes itself. Absence guard for the filter.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (Command, NOT Vigilance) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
