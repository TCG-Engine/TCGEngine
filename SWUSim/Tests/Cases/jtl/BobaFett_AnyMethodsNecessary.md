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
