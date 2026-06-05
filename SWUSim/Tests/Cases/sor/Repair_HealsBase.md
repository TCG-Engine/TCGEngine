# SOR_074 Repair (Event, cost 1) — "Heal 3 damage from a unit or base." With no
# units in play, the only targets are the two bases. P1 chooses its own base
# (myBase-0), healing 3: base damage 5 → 2. (Proves bases are valid MZCHOOSE
# targets via myBase-0 / theirBase-0.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myBaseDamage:5;handCardIds:SOR_074}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
