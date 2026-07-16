# Deployed_OnAttack_DiscloseGiveExp
#// SEC_004 Leia Organa (deployed) — On Attack: You may disclose an aspect. If you do, give an Experience
#// token to a unit that doesn't share an aspect with the disclosed card.
#// Deployed SEC_004 (4/7) attacks the enemy base. On Attack → may disclose SOR_237 (Heroism) → give Exp
#// to a non-Heroism unit. SEC_004 itself (Vigilance/Heroism) shares Heroism → excluded; SEC_080
#// (Command/Villainy) is the only eligible unit → auto.

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

---

# LeaderAction_DiscloseGiveExpNonSharing
#// SEC_004 Leia Organa (leader) — Action [1 resource, Exhaust]: Disclose Vigilance/Command/Aggression/
#// Cunning/Heroism (reveal a hand card with one of those icons). If you do, give an Experience token to a
#// unit that doesn't share an aspect with the disclosed card.
#// P1 discloses SOR_237 (Heroism, stays in hand). Eligible Exp recipients = units NOT sharing an aspect
#// with it: SEC_080 (Command/Villainy → eligible) vs SOR_046 (Vigilance/Heroism → shares Heroism →
#// EXCLUDED). Only SEC_080 → auto. Costs 1 resource.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
