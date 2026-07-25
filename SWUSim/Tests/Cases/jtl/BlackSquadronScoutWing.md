# UpgradePlayed_Attack
#// JTL_202 Black Squadron Scout Wing — When you play an upgrade on this unit, you may attack with it
#// (+1/+0). P1 plays the vanilla upgrade SOR_069 onto JTL_202 (power 4), accepts, and it attacks the
#// enemy base for 4+1=5.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_069
WithP1Resources: 5
WithP1SpaceArena: JTL_202:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:5
P1SPACEARENAUNIT:0:EXHAUSTED

---

# Exhausted_UpgradeAttaches_NoAttack
#// JTL_202 Black Squadron Scout Wing — the "you may attack" rider requires the unit be ABLE to attack.
#// While EXHAUSTED it cannot, so playing SOR_069 onto it just attaches the upgrade — no attack, no base
#// damage — and it stays exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_069
WithP1Resources: 5
WithP1SpaceArena: JTL_202:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:EXHAUSTED
P2BASEDMG:0

---

# OpponentPlaysUpgrade_NoTrigger
#// JTL_202 Black Squadron Scout Wing — the ability reads "When YOU play an upgrade on this unit". When the
#// OPPONENT plays an upgrade on it (P2 attaches SHD_123 Bounty Hunter's Quarry), the controller's attack
#// ability does NOT trigger: the upgrade attaches but Black Squadron never attacks (P2 base stays 0).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1SpaceArena: JTL_202:1:0
WithP2Hand: SHD_123
WithP2Resources: 8

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:0
