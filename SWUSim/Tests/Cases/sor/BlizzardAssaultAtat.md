# AttackDefeats_DealExcess
#// SOR_088 Blizzard Assault AT-AT (9/9) — "When this unit attacks and defeats a unit: You may deal
#// the excess damage from this attack to an enemy ground unit." It attacks a 3/3 (excess = 9-3 = 6),
#// defeats it, then deals 6 to the opponent's other ground unit (a 3/7, which survives at 6 damage).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackDefeats_DeclineExcess
#// SOR_088 Blizzard Assault AT-AT — the excess deal is "you may": declining leaves the other enemy
#// unit untouched. Same setup as the deal-excess test, but the player declines → SOR_046 stays at 0
#// damage (only the 3/3 it defeated is gone).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_GrantsRaid1
#// SOR_012 IG-88 — deployed leader unit's passive: Each OTHER friendly unit gains Raid 1
#// (+1/+0 while attacking). IG-88 is deployed (ground); a friendly space unit (Distant
#// Patroller, 2 power) attacks the enemy base and deals 2 + 1 (Raid) = 3. (The Raid grant is
#// already implemented in GetConditionalKeyword_Raid_Value — this test verifies it.)

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:SOR_012
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_060:1:0     # gains Raid 1 from deployed IG-88

## WHEN
- P1>DeployLeader
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# LeaderAction_MoreUnits_Buff
#// SOR_012 IG-88 — Leader Action [Exhaust]: Attack with a unit. If you control more units than
#// the defending player, the attacker gets +1/+0 for this attack. P1 controls 1 unit, P2 controls 0
#// → bonus applies. The 3-power unit attacks the base for 3+1=4. The +1 is one-shot (POWER stays 3).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_012;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED

---

# LeaderAction_NotMoreUnits_NoBuff
#// SOR_012 IG-88 — when you do NOT control more units than the defending player, no +1/+0.
#// P1 controls 1 unit, P2 controls 1 unit (equal) → no bonus. The 3-power unit attacks the base
#// (chosen over the enemy unit) for 3 damage.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_012;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
