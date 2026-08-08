# Deployed_FriendlyDamagedSurvives_DealsBack
#// SEC_002 Jabba the Hutt (deployed) — "When another friendly unit is dealt damage and survives: You may
#// have that unit deal that much damage to an enemy unit. Once each round."
#// P1's SEC_080 (3/3) attacks the enemy SOR_063 (2/4 Sentinel): deals 3 (SOR_063 survives at 4 HP),
#// takes 2 counter-damage and survives. SEC_002 (deployed) reacts → SEC_080 deals that much (2) to an
#// enemy unit. Only enemy = SOR_063 → 3 + 2 = 5 damage on 4 HP → defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# LeaderAction_3PlusDamageDeals2
#// SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals damage
#// to an enemy unit; if the friendly unit has 3 or more damage on it, it deals 2 instead of 1.
#// Friendly LAW_124 (4/7) carries 3 damage → deals 2 to the only enemy (SOR_095, 3/3 survives at 2).
#// Proves the 3+-damage → 2 branch (vs the 1 in the sibling test).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: LAW_124:1:3
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_DamagedUnitDeals1
#// SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals 1
#// damage to an enemy unit. (If it has 3+ damage it deals 2 instead — see the other test.)
#// Friendly SEC_080 has 1 damage → deals 1 to the only enemy (SOR_095). Both picks auto-resolve.
#// Costs 1 resource (2 ready → 1), leader exhausts.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_NoEnemy_UsableNoEffect
#// SEC_002 Jabba the Hutt (leader) — CR 6.4.587.c: the [1 resource, Exhaust] cost changes game state, so the
#// Action is usable even with no enemy unit for the friendly damaged unit to hit. It pays the cost (exhaust +
#// 1 resource) and does nothing.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# Deployed_UnitDoesNotSurvive_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction needs the friendly unit to SURVIVE the damage. Death
#// Star Stormtrooper (SOR_128, 3/1) attacks Consular Security Force (SOR_046, 3/7): it deals 3 (SOR_046
#// survives) but takes 3 counter-damage and is defeated. Because the friendly unit did not survive, Jabba
#// offers nothing.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:1
P1LEADER:DEPLOYED

---

# Deployed_MayDecline
#// SEC_002 Jabba the Hutt (deployed) — the reaction is optional. Imperial Dark Trooper (SEC_080, 3/3)
#// attacks a Sentinel (SOR_063, 2/4) and survives the 2 counter-damage; Jabba offers to deal 2 to an
#// enemy unit, but P1 declines, so no extra damage is dealt (SOR_063 keeps only its 3 combat damage).
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:DEPLOYED

---

# Deployed_JabbaHimselfDamaged_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction is for ANOTHER friendly unit; Jabba being dealt
#// damage himself does not trigger it. P2's Imperial Dark Trooper (SEC_080) attacks the deployed Jabba
#// (2/8), who survives with 3 damage; no reaction is offered.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_002
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_DamageFromEnemyAbility_ReactionDealsThatMuch
#// SEC_002 Jabba the Hutt (deployed) — the reaction also fires on non-combat damage. P2 plays Open Fire
#// (SOR_172, deal 4) onto P1's Consular Security Force (SOR_046, 3/7), which survives. Jabba then has that
#// unit deal 4 to an enemy unit; P1 targets Death Star Stormtrooper (SOR_128, 3/1), defeating it.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2Hand: SOR_172
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# Deployed_EnemyUnitDamaged_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction is only for FRIENDLY units taking damage. P1's own
#// Daring Raid (SHD_178) deals 2 to the enemy Consular Security Force (SOR_046), which survives; since the
#// damaged unit is an enemy, Jabba offers nothing.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_ShieldedFriendlyTakesNoDamage_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction needs a friendly unit to actually BE DEALT damage. A
#// Shield absorbs the whole instance, so a shielded friendly that "survives" an attack was never dealt
#// damage and Jabba does not fire. P1's SEC_080 (3/3) carries a Shield and attacks SOR_063 (2/4 Sentinel):
#// the 2 counter-damage is absorbed by the Shield, so SEC_080 ends at 0 damage with no Shield, SOR_063
#// survives at 3 damage, and no reaction prompt appears.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>AttackGroundArena:0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Deployed_OnceEachRound_SecondDamageEventDoesNotReact
#// SEC_002 Jabba the Hutt (deployed) — "Once each round." After the reaction fires on the first friendly
#// unit to be damaged and survive, a SECOND such event in the same round must not offer it again. P1's
#// SEC_080 attacks SOR_063 and the reaction fires (killing SOR_063); P1's second unit (SOR_046) then
#// attacks P2's LAW_124 and survives its counter — no second prompt, so LAW_124 keeps just the combat
#// damage.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
