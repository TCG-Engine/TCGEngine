# SEC_004 Leia Organa (deployed) — On Attack: You may disclose an aspect. If you do, give an Experience
# token to a unit that doesn't share an aspect with the disclosed card.
# Deployed SEC_004 (4/7) attacks the enemy base. On Attack → may disclose SOR_237 (Heroism) → give Exp
# to a non-Heroism unit. SEC_004 itself (Vigilance/Heroism) shares Heroism → excluded; SEC_080
# (Command/Villainy) is the only eligible unit → auto.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_004:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
