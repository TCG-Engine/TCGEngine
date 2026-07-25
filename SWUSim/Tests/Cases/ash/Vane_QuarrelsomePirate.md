# DefeatUpgradeDealBase
#// ASH_012 Vane — Leader Action [Exhaust, defeat a friendly upgrade]: deal 2 damage to a base. P1 pays the
#// cost by defeating SOR_120 off SOR_095 (which reverts to 3 power), then deals 2 to P2's base.
## GIVEN
CommonSetup: grk/brk/{
  myLeader:ASH_012
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_DefeatUpgradeDealDefender
#// ASH_012 Vane (deployed) — On Attack: you may defeat a friendly upgrade; if you do, deal 2
#// damage to the defending unit or a base. Vane (3 power) attacks the enemy wall SOR_046 (3/7),
#// defeats the upgrade on the friendly Dark Trooper, then deals 2 to the defending unit:
#// combat 3 + ability 2 = 5 damage on SOR_046; the upgrade is gone.

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

---

# DefeatUpgrade_DealOwnBase
#// ASH_012 Vane — the "deal 2 damage to a base" target is the player's choice; P1 may point it at their OWN
#// base. Same upgrade-defeat cost, but P1 chooses myBase.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P1LEADER:EXHAUSTED

---

# LeaderAction_DefeatTokenUpgrade_DealEnemyBase
#// ASH_012 Vane (leader Action) — the "defeat a friendly upgrade" cost accepts a TOKEN upgrade. SOR_095 bears
#// only an Advantage token (ASH_T02); using Vane's ability defeats that token and deals 2 to the enemy base.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED

---

# LeaderAction_DefeatTokenUpgrade_DealOwnBase
#// ASH_012 Vane (leader Action) — same token-upgrade cost, but the 2 damage is aimed at P1's own base.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_T02
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_DefeatUpgradeDealEnemyBase
#// ASH_012 Vane (deployed) — On Attack: may defeat a friendly upgrade; if so, deal 2 to the defending unit OR
#// a base. Vane (3 power) attacks the enemy base directly (3 combat), defeats the upgrade on the friendly
#// SEC_080, then deals 2 to the enemy base: 3 + 2 = 5 on P2's base; the upgrade is gone.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_OnAttack_DefeatUpgradeDealOwnBase
#// ASH_012 Vane (deployed) — the 2-damage rider may point at P1's own base. Vane attacks the enemy base for 3
#// combat, then deals the ability's 2 to P1's base: enemy base 3, own base 2.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:2
P2BASEDMG:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Deployed_OnAttack_DeclineMay
#// ASH_012 Vane (deployed) — the On Attack rider is a "you may". Declining it defeats no upgrade and deals no
#// bonus damage. Vane attacks the enemy SOR_046 (3/7) for 3 combat only; the friendly upgrade stays attached.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# LeaderAction_NoFriendlyUpgrade_Unavailable
#// ASH_012 Vane — the leader Action's cost includes "defeat a friendly upgrade". With no friendly upgrade in
#// play the cost is unpayable, so the action is unavailable: using it is a no-op — Vane stays READY, no base damage.
## GIVEN
CommonSetup: yyk/brk/{myLeader:ASH_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility:0
## EXPECT
P1LEADER:READY
P1BASEDMG:0
P2BASEDMG:0

---

# LeaderAction_TokenUpgradeOnControlChangedUnit
#// ASH_012 Vane — an Advantage token that rode along when a unit changed control is a valid "defeat a friendly
#// upgrade" cost. P1 plays Change of Heart (SOR_224) to take control of the enemy SOR_095, which carries an
#// Advantage token (ASH_T02). The token now belongs to P1's unit, so Vane's Action defeats it and deals 2 to
#// the enemy base.
## GIVEN
CommonSetup: yrk/brk/{myResources:8;handCardIds:SOR_224;myLeader:ASH_012}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:ASH_T02
## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED
