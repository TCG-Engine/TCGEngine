# BosskUnit_OnAttackDamage_OffersBobaReaction
#// JTL_009 Boba Fett (undeployed leader) + JTL_187 Bossk played as a UNIT. Bossk's On Attack "exhaust the
#// defender and deal 1 damage to it" is non-combat damage (official Boba ruling: On Attack damage is
#// non-combat), so Boba's "When you deal non-combat damage" reaction is offered. P1 accepts and deals the
#// 1 indirect to P2, who puts it on their base. SOR_046 then takes 1 + 4 combat = 5.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_187:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:1
P1LEADER:EXHAUSTED

---

# BosskUnit_OnAttackDamage_ReactionIsOfferedToTheDealer
#// Same board, decision left pending: the offer exists and is Boba's exhaust prompt (proves the reaction
#// is raised by Bossk's On Attack damage itself, not by anything later in the attack).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_187:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1DECISIONTOOLTIP:Exhaust_Boba_Fett_to_deal_1_indirect_damage?
P1LEADER:READY

---

# BosskUnit_OnAttackDamage_ShieldAbsorbs_NoBobaReaction
#// The reported board (P-7, game 957): the defender carries a Shield token, so Bossk's 1 On Attack damage
#// is PREVENTED. CR 20.1: "If an ability prevents all damage from being dealt ... any abilities that would
#// trigger when damage is dealt don't trigger" — Bossk dealt no damage, so Boba is not offered. The Shield
#// is spent; the defender is still exhausted and takes Bossk's 4 combat damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_187:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:0
P1LEADER:READY
P1NODECISION

---

# BosskPilot_GrantedOnAttackDamage_OffersBobaReaction
#// Bossk played with Piloting onto SHD_161 Stolen Landspeeder: the host gains Bossk's On Attack, and that
#// granted ability's 1 damage is also non-combat damage dealt by P1 — Boba is offered.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_187
WithP1GroundArena: SHD_161:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DECISIONTOOLTIP:Exhaust_Boba_Fett_to_deal_1_indirect_damage?

---

# FourSeat_BosskUnit_OnAttackDamage_OffersBobaReaction
#// The reported game was a four-seat table. Seat 1's Bossk (a unit) attacks seat 2's unshielded unit:
#// the On Attack's 1 damage is still dealt by seat 1, so seat 1's Boba is offered.

## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:JTL_009}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_187:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:P2G0

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:EXHAUSTED
P1DECISIONTOOLTIP:Exhaust_Boba_Fett_to_deal_1_indirect_damage?
