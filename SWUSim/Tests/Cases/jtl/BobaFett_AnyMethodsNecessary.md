# DeployAsPilot_SplitFourDamage
#// JTL_009 Boba Fett (leader) — "When deployed as an upgrade: Deal up to 4 damage divided as you choose
#// among any number of units." Boba deploys as a Pilot onto SOR_225, then splits 4 damage as 3 + 1 across
#// two enemy ground units (both survive: SOR_046 is 3/7, SOR_063 is 2/4).

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0:3,theirGroundArena-1:1

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:1

---

# DeployAsPilot_SplitDamage_ShieldAbsorbs
#// JTL_009 Boba Fett — his "deploy as an upgrade: deal up to 4 divided among units" is non-combat,
#// preventable damage, so a Shield token on a target ABSORBS the whole instance (CR 8.31). Boba deploys as
#// a Pilot and assigns all 4 to a Shielded enemy SOR_046: the shield is consumed and SOR_046 takes 0 damage.
#// (Regression guard for divided damage consuming Shields in _SWUApplySplitHits.)

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0:4

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# NonCombatDamage_Indirect
#// JTL_009 Boba Fett (undeployed leader) — When you deal non-combat damage: you may exhaust this leader;
#// if you do, deal 1 indirect damage to a player. P1 plays JTL_176 Shoot Down (3 to a space unit) onto
#// P2's SOR_046 — that effect damage is non-combat, so Boba's reaction is offered. P1 exhausts Boba and
#// deals 1 indirect to P2, who assigns it to their base. (Base damage of 1 comes only from the reaction.)

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_176
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:1

---

# Pryde133_Interaction_AllIndirectToBase
#// JTL_009 Boba Fett (leader) + JTL_133 Allegiant General Pryde interaction. P1 controls both, with the
#// initiative. Pryde attacks P2's base: its On Attack deals 2 indirect, and because that indirect is
#// non-combat damage Boba reacts (exhaust → 1 more indirect). P2 controls only a fragile 1-HP unit
#// (SOR_128, 3/1) — and Pryde's "defeat a non-unique upgrade on an indirect-damaged unit" plus the unit's
#// 1 HP make assigning the indirect onto it terrible — so P2 dumps ALL of it (2 + 1 = 3) onto their base.
#// Pryde then deals its 2 combat damage to the base as well → 5 total base damage. The 1-HP unit is
#// untouched (Pryde's upgrade-defeat reaction never fires, since no unit took indirect).

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_133:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:2
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NonCombatReaction_Declined
#// JTL_009 Boba Fett — the "when you deal non-combat damage, you may exhaust this leader" reaction is a MAY.
#// P1 plays Shoot Down (JTL_176) dealing 3 non-combat to SOR_046, but DECLINES the exhaust — so no indirect
#// damage is dealt and Boba stays ready.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_176
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1LEADER:READY

---

# CombatDamage_NoReaction
#// JTL_009 Boba Fett — the reaction is for NON-combat damage only. A friendly unit dealing COMBAT damage
#// (SOR_095 attacks SOR_046) does NOT offer Boba's exhaust reaction: no decision is queued and Boba stays
#// ready.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:READY

---

# OverwhelmDamage_NoReaction
#// JTL_009 Boba Fett — Overwhelm excess damage to the base is COMBAT damage (it is dealt as part of the
#// attack), so it must NOT offer Boba's non-combat exhaust reaction. Wampa (SOR_164, 4-power Overwhelm)
#// attacks SOR_108 (1/2): 2 damage kills the defender and 2 excess Overwhelm damage hits P2's base. No
#// decision is queued and Boba stays ready. (Guarded by the gInCombatDamage flag at the base-damage site.)

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_108:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1NODECISION
P1LEADER:READY
P2BASEDMG:2

---

# DeployAsUnit_NoFourDamage
#// JTL_009 Boba Fett — the "deal up to 4 damage" is a WHEN-DEPLOYED-AS-AN-UPGRADE (Pilot) ability. Deploying
#// Boba as a normal UNIT (with a friendly Vehicle present so the Unit/Pilot choice is offered) must NOT deal
#// any damage: the enemy unit stays at 0 and no distribution decision is queued.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Unit

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# SimultaneousMultiTarget_ReactionOfferedOnce
#// JTL_009 Boba Fett (undeployed leader) — when a single source deals SIMULTANEOUS non-combat damage to
#// MULTIPLE units, his "when you deal non-combat damage" reaction is offered exactly ONCE (not per target).
#// P1 controls front-leader Boba + a dealer (SOR_046, 3/7); P1 plays Overwhelming Barrage (SOR_092) buffing
#// the dealer to power 5 and splitting 3 + 2 across two enemy units (both survive). That divided damage is
#// one non-combat event → Boba's reaction fires once; P1 exhausts him to deal 1 indirect to P2, who assigns
#// it to their base. (Regression guard: _SWUApplySplitHits fires the Boba reaction once for the whole split.)

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_009;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_092
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_063:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3,theirGroundArena-1:2
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:2
P2BASEDMG:1
P1NODECISION
P2NODECISION
