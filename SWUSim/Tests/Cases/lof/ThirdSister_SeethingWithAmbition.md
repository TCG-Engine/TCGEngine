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

---

# DeployedOnAttack_PilotingSkipped
#// LOF_010 Third Sister (deployed) — On Attack: "the next unit you play this phase gains Hidden." A unit
#// played WITH PILOTING (as an upgrade, not entering as a unit) does NOT consume the pending Hidden grant and
#// does not itself become Hidden. Third Sister attacks the base; P1 first plays Paige Tico (JTL_046) as a
#// Pilot onto the friendly TIE Advanced — no Hidden — then plays Battlefield Marine (SOR_095) as a unit,
#// which is the one that gains Hidden. (FT: "should not give hidden to units played with piloting".)

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
WithP1Hand: JTL_046
WithP1Hand: SOR_095
WithP1SpaceArena: SOR_231:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_231
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:NOTKEYWORD:Hidden
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
