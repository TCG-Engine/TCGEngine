# OnAttackEnd_DefeatsExp
#// LOF_156 Infused Brawler — "When this unit completes an attack: defeat an Experience token on it." With
#// one Experience token (power 2+1=3), it attacks the base for 3, then loses the Experience token.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_156:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# UseForce_Give2Exp
#// LOF_156 Infused Brawler (2/2) — When Played: may use the Force → give 2 Experience tokens to this unit.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_156}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
