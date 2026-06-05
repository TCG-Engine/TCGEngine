# When a Shielded+Ambush unit enters play via ECL, both triggers appear in the EffectStack
# before any ordering choice is made. This verifies the trigger bag populates the EffectStack
# (not the old YESNO) when 2+ triggers are pending.
# Same GIVEN as EnergyConversionLab_Wilderness_ShieldFirst — stops after card enters play.

## GIVEN
SkipPreGame: true
P1LeaderBase: SOR_014/SOR_022
P2LeaderBase: SOR_014/SOR_023
WithP1Resources: 5:SOR_095
WithP1Hand: SOR_064
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0

## EXPECT
EFFECTSTACKCOUNT:2
EFFECTSTACKHAS:Shielded
EFFECTSTACKHAS:Ambush
P1HASDECISION
