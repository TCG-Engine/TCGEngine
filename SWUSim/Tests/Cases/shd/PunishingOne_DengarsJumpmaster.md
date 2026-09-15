# PunishingOne_UpgradedEnemyDefeated_MayReady
#// SHD_137 Punishing One — "When an upgraded enemy unit is defeated: You may ready this unit. Once each
#// round." Punishing One starts exhausted. P1's SOR_046 (3 power) attacks the enemy SHD_095 (2/3) that
#// wears SHD_072 (an upgrade → "upgraded"): SHD_095 is defeated, so Punishing One readies (readying an
#// exhausted unit is pure benefit → auto-resolved).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: SHD_137:0:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_072

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:READY

---

# OncePerRound_SecondUpgradedDefeatDoesNotReadyAgain
#// "Use this ability only once each round." The first upgraded enemy defeated readies Punishing One; it
#// then attacks P2's base (exhausting itself), and a second upgraded enemy defeated the same round leaves
#// it exhausted. (Each Clone Deserter carries a Bounty — declined, so its prompt doesn't hold up the next
#// attack.)
## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: SHD_137:0:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP2GroundArena: SHD_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_072
WithP2GroundArenaUpgrade: 1:SHD_072
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
- P1>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1SPACEARENAUNIT:0:EXHAUSTED
