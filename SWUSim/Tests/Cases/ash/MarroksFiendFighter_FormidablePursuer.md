# Marrok241_SupportLendsBonusAndOverwhelm
#// ASH_241 Marrok's Fiend Fighter (Space, 3/2, Support, Overwhelm, "+2/+0 while attacking a damaged unit").
#// Support: when played, another friendly unit may attack and gains Marrok's OTHER abilities for that
#// attack. Marrok is played and lends its abilities to JTL_095 (3/2), which attacks a damaged SEC_213
#// (1/2, 1 remaining HP): JTL_095 gains +2/+0 vs the damaged defender (5 power) and Overwhelm, defeating
#// SEC_213 and spilling the 4 excess to the enemy base. JTL_095 takes the 1-power counter.
## GIVEN
CommonSetup: grk/grk/{myResources:5;handCardIds:ASH_241}
WithP1SpaceArena: JTL_095:1:0
WithP2SpaceArena: SEC_213:1:1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:JTL_095
P1SPACEARENAUNIT:0:DAMAGE:1

---

# Marrok241_OverwhelmVsUndamaged_NoBonus
#// ASH_241 Marrok's Fiend Fighter — attacking an UNDAMAGED unit grants NO +2/+0 bonus, but Overwhelm still
#// spills. Marrok (base 3 power) attacks an undamaged SEC_213 (1/2): no bonus applies (power stays 3), it
#// defeats the 2-HP unit and Overwhelm spills the 1 excess to the enemy base. Marrok takes the 1 counter.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_241:1:0
WithP2SpaceArena: SEC_213:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:1
P1SPACEARENAUNIT:0:CARDID:ASH_241
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:DAMAGE:1

---

# Marrok241_BonusAndOverwhelmVsDamaged
#// ASH_241 Marrok's Fiend Fighter — the +2/+0 (vs a damaged unit) and Overwhelm both apply on a direct
#// attack. Marrok attacks a damaged SEC_213 (1/2, 1 remaining HP): +2 makes it 5 power, it defeats the
#// 1-HP unit, and Overwhelm spills the 4 excess to the enemy base. Marrok takes the 1 counter.
## GIVEN
CommonSetup: grk/grk
WithP1SpaceArena: ASH_241:1:0
WithP2SpaceArena: SEC_213:1:1
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:ASH_241
P1SPACEARENAUNIT:0:DAMAGE:1
