# AttackBonusPerEnemy
#// ASH_234 Masterstroke (Event, cost 2) — Attack with a unit. It gets +1/+0 for this attack for each unit
#// the defending player controls in its arena. P1's SOR_095 (3 power) attacks while P2 has 2 ground units,
#// so it gets +2 → 5; attacking the enemy base deals 5.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_234}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:5

---

# NoEnemyUnits_NoBonus
#// ASH_234 Masterstroke — the +1/+0 is per enemy unit in the attacker's arena. With no enemy ground units,
#// SOR_095 attacks the base for its base 3 (no bonus).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_234}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:3
