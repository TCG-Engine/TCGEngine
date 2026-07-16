# DeployedOnAttack
#// LOF_010 Third Sister (deployed) — On Attack: the next unit you play this phase gains Hidden. She attacks
#// the base, then P1 plays Plo Koon, who enters with Hidden.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 12
WithP1Hand: LOF_050

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_050
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden

---

# PlayHiddenUnit
#// LOF_010 Third Sister — Action [Exhaust]: Play a unit from your hand. It gains Hidden for this phase. Plo
#// Koon enters with Hidden.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_050
WithP1Resources: 10

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
