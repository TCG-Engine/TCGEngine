# IndirectToUnit_DefeatsUpgrade
#// JTL_133 Allegiant General Pryde — On Attack: if you have the initiative, deal 2 indirect to a player;
#// AND "when indirect damage is dealt to a unit, you may defeat a non-unique upgrade on it." Pryde attacks
#// P2's base; with initiative the On Attack deals 2 indirect to P2, who puts both on SOR_046 (carrying a
#// non-unique upgrade SOR_120). Because P1 controls Pryde, the indirect-to-a-unit reaction lets P1 defeat
#// SOR_120 on it.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_133:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# IndirectToBase_WithInitiative
#// JTL_133 Allegiant General Pryde — On Attack: if you have the initiative, deal 2 indirect damage to a
#// player. Pryde (with initiative) attacks P2's base; P2 puts both points of indirect on its own base. Base
#// then takes those 2 indirect plus Pryde's 2 combat damage (total 4); the enemy unit is untouched.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_133:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:2

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoInitiative_NoIndirect
#// JTL_133 Allegiant General Pryde — the On Attack indirect requires the initiative. With initiative held by
#// P2, Pryde attacks P2's base and the ability does NOT trigger: no indirect decision is offered and the
#// base takes only Pryde's 2 combat damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1GroundArena: JTL_133:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Offer_NonUniqueUpgradesOnly
#// JTL_133 Allegiant General Pryde — "When indirect damage is dealt to a unit: you may defeat a
#// NON-UNIQUE upgrade ON IT." The staged pool must contain only the damaged unit's NON-UNIQUE upgrades:
#// SOR_120 Academy Training and SOR_069 Resilient qualify, while SOR_136 Vader's Lightsaber (UNIQUE) is
#// excluded. Pryde attacks, P2 assigns both points of indirect to SOR_046, and P1 picks that host — the
#// upgrade pick is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: JTL_133:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_136
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1SELECTABLEEXACT:theirGroundArena-0.u0&theirGroundArena-0.u2
