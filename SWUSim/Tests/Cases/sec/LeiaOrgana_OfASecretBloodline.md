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

---

# LeaderAction_DisclosedButNoUnits_ExpSkipped
#// SEC_004 Leia Organa (leader) — Action [1 resource, Exhaust]: Disclose an aspect → give an Experience
#// token. With a discloseable card (LOF_061 Secretive Sage, Vigilance) in hand but NO units in play, the
#// disclose still happens but the Experience step is auto-skipped (no valid recipient). The ability still
#// resolves: leader exhausts and 1 resource is spent.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: LOF_061

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deployed_OnAttack_NoCardsInHand_Skipped
#// SEC_004 Leia Organa (deployed) — On Attack: You may disclose an aspect → give Experience. With an empty
#// hand there is nothing to disclose, so the On Attack ability is auto-skipped: Leia (4/7) attacks the enemy
#// base for 4 and no decision is offered.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_004:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SEC_004
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# Deployed_OnAttack_NoDiscloseableAspect_Skipped
#// SEC_004 Leia Organa (deployed) — On Attack disclose is auto-skipped when the hand holds no card with a
#// disclosable icon (Vigilance/Command/Aggression/Cunning/Heroism). LOF_254 Porg is a neutral (no-aspect)
#// unit → not discloseable, so the ability skips: base takes 4, no decision.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_004:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: LOF_254

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SEC_004
P1NODECISION
P1HANDCOUNT:1

---

# Deployed_OnAttack_ChooseNothing_NoEffect
#// SEC_004 Leia Organa (deployed) — the On Attack disclose is optional ("you may"). With a discloseable
#// card (LOF_061 Vigilance) in hand and a friendly unit (SOR_046) on board, P1 declines the disclose. Base
#// takes 4 and no Experience token is given.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_004:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: LOF_061
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SEC_004
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1HANDCOUNT:1

---

# Deployed_OnAttack_GiveExpToSelf
#// SEC_004 Leia Organa (deployed, Vigilance/Heroism) — On Attack: disclose Command (SOR_114 Escort Skiff)
#// → give Experience to a unit not sharing an aspect. Leia doesn't share Command, and she is the only unit
#// in play, so she may target HERSELF. The Experience (+1/+1) resolves during the On Attack (before combat
#// damage), so she attacks the base for 5.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_004:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_114

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SEC_004
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
