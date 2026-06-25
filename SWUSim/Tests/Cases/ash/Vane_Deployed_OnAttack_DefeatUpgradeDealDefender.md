# ASH_012 Vane (deployed) — On Attack: you may defeat a friendly upgrade; if you do, deal 2
# damage to the defending unit or a base. Vane (3 power) attacks the enemy wall SOR_046 (3/7),
# defeats the upgrade on the friendly Dark Trooper, then deals 2 to the defending unit:
# combat 3 + ability 2 = 5 damage on SOR_046; the upgrade is gone.

## GIVEN
CommonSetup: grk/brk/{
  myLeader:ASH_012:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
