# FrontBountyHunterAttackCredit
#// LAW_007 Boba Fett (leader front) — "When a friendly Bounty Hunter unit's attack ends: If the defending
#// unit was defeated, you may exhaust this leader. If you do, create a Credit token." LAW_124 (4/7 Bounty
#// Hunter) attacks and defeats SOR_128 (3/1); P1 exhausts Boba to create a Credit.
#// COVERAGE: offer=FrontNoTriggerWhenAttackerNotBountyHunter (P1NODECISION when the condition fails; the
#//           trigger itself is a YES/NO with no target pick, so no SELECTABLEEXACT applies) ·
#//           reqboundary=FrontBountyHunterAttackCredit (the exhaust YES is answered on a later request
#//           after the attack resolves) · control=FrontStolenBountyHunterNowFriendlyCreatesCredit +
#//           DeployedStolenBountyHunterNowFriendlyCreatesCredit + FrontStolenBountyHunterNowEnemyNoCredit
#//           + DeployedStolenBountyHunterNowEnemyNoCredit (control, not ownership, gates the trigger) ·
#//           boundary=FrontBountyHunterAttackCredit vs FrontNoTriggerWhenDefenderSurvives (and the
#//           Deployed pair), plus attacker-also-defeated Front/Deployed trade sections ·
#//           decline=FrontDeclineExhaust_NoCredit (deployed side has no decline — no "you may").

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1LEADER:EXHAUSTED

---

# FrontNoTriggerWhenAttackerNotBountyHunter
#// LAW_007 Boba Fett (leader front) — the trigger requires the ATTACKER to be a friendly Bounty Hunter.
#// SOR_164 Wampa (4/5, Creature, not a Bounty Hunter) defeats SOR_095 Battlefield Marine (3/3): no
#// credit, Boba is not exhausted, no decision offered.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P1LEADER:READY
P1NODECISION

---

# FrontNoTriggerWhenDefenderSurvives
#// LAW_007 Boba Fett (leader front) — the credit only comes if the DEFENDING unit was defeated. A friendly
#// Bounty Hunter (LAW_156 Hunter For Hire, 4/4) attacks SOR_232 AT-ST (6/7): the AT-ST survives (4 damage)
#// and kills the Hunter. Because the defender was not defeated, no Credit is created and Boba is not
#// exhausted.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1CREDITCOUNT:0
P1LEADER:READY

---

# FrontNoTriggerForOpponentBountyHunterAttack
#// LAW_007 Boba Fett (leader front) — an OPPONENT's Bounty Hunter attack does not benefit P1. P2's LAW_065
#// 4-LOM (4/5 Bounty Hunter) attacks and defeats P1's LAW_156 Hunter For Hire (4/4). No Credit is created
#// for P1 and Boba stays ready.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: LAW_065:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P1LEADER:READY

---

# FrontNoTriggerWhenFriendlyDefendingBountyHunterKillsAttacker
#// LAW_007 Boba Fett (leader front) — the trigger is on a friendly Bounty Hunter ATTACK, not on defense.
#// P2's SOR_095 Battlefield Marine (3/3) attacks P1's LAW_156 Hunter For Hire (4/4): the Marine is defeated
#// by the defending Hunter, but since the attacker is the enemy Marine no Credit is created for P1.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:0
P1LEADER:READY

---

# DeployedBountyHunterAttackCreatesCredit
#// LAW_007 Boba Fett (DEPLOYED) — "When a friendly Bounty Hunter unit's attack ends: If the defending unit
#// was defeated, create a Credit token." (No exhaust cost on the deployed side; it just happens.) LAW_156
#// Hunter For Hire (4/4 Bounty Hunter) defeats SOR_095 Battlefield Marine (3/3) → a Credit is created.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1LEADER:DEPLOYED

---

# DeployedNoTriggerWhenAttackerNotBountyHunter
#// LAW_007 Boba Fett (DEPLOYED) — no Credit when the attacker is not a Bounty Hunter. SOR_164 Wampa (4/5)
#// defeats SOR_095 Battlefield Marine (3/3): no Credit.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0

---

# DeployedNoTriggerWhenDefenderSurvives
#// LAW_007 Boba Fett (DEPLOYED) — no Credit when the defender survives. LAW_156 Hunter For Hire (4/4)
#// attacks SOR_232 AT-ST (6/7): the Hunter dies, AT-ST survives → no Credit.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P1CREDITCOUNT:0

---

# DeployedNoTriggerForOpponentBountyHunterAttack
#// LAW_007 Boba Fett (DEPLOYED) — an opponent's Bounty Hunter attack gives P1 nothing. P2's LAW_065 4-LOM
#// (4/5 Bounty Hunter) defeats P1's LAW_156 Hunter For Hire (4/4): no Credit for P1.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: LAW_065:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1CREDITCOUNT:0

---

# DeployedNoTriggerWhenFriendlyDefendingBountyHunterKillsAttacker
#// LAW_007 Boba Fett (DEPLOYED) — defending doesn't count. P2's SOR_095 Battlefield Marine (3/3) attacks
#// P1's LAW_156 Hunter For Hire (4/4); the Marine dies to the defending Hunter, but no Credit for P1.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0

---

# DeployedBobaHimselfDefeatsCreatesCredit
#// LAW_007 Boba Fett (DEPLOYED) — Boba himself is a Bounty Hunter, so his own attack that defeats a unit
#// makes a Credit. Deployed Boba (3/6) defeats SOR_095 Battlefield Marine (3/3) → 1 Credit.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1

---

# DeployedMultipleAttacksSamePhase
#// LAW_007 Boba Fett (DEPLOYED) — the ability fires for EACH friendly Bounty Hunter attack in a phase.
#// LAW_156 Hunter For Hire defeats one SOR_095 Battlefield Marine (Credit 1), then deployed Boba defeats a
#// second Battlefield Marine (Credit 2) → 2 Credits total.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_156:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:2

---

# DeployedStolenBountyHunterNowFriendlyCreatesCredit
#// LAW_007 Boba Fett (DEPLOYED) — the trigger keys on CONTROL, not ownership: a Bounty Hunter P1 controls
#// but does not own still makes a Credit. P1 uses LAW_156 Hunter For Hire's "Action [defeat a friendly
#// Credit token]: Take control of this unit. Any player may use this ability." on the ENEMY Hunter For
#// Hire (defeating P1's own Credit), taking control of it. The now-friendly Hunter then defeats SOR_095
#// Battlefield Marine → a Credit is created.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP2GroundArena: [LAW_156:1:0 SOR_095:1:0]

## WHEN
- P1>UseUnitAbility:theirGroundArena-0
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:LAW_156
P1CREDITCOUNT:1

---

# FrontStolenBountyHunterNowFriendlyCreatesCredit
#// LAW_007 Boba Fett (leader front) — same control-not-ownership check on the front side. P1 takes control
#// of the enemy LAW_156 Hunter For Hire via its "any player may use" action (defeating P1's own Credit);
#// the now-friendly Hunter defeats SOR_095 Battlefield Marine, and P1 exhausts Boba to create a Credit.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP2GroundArena: [LAW_156:1:0 SOR_095:1:0]

## WHEN
- P1>UseUnitAbility:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1LEADER:EXHAUSTED

---

# DeployedStolenBountyHunterNowEnemyNoCredit
#// LAW_007 Boba Fett (DEPLOYED) — ownership is not enough; the attacking Bounty Hunter must be under P1's
#// CONTROL. P2 uses LAW_156 Hunter For Hire's "any player may use" action (defeating P2's own Credit) to
#// take control of P1's Hunter For Hire, then attacks with it and defeats SOR_095 Battlefield Marine.
#// Because the attacker is now enemy-controlled, P1 gets no Credit even though P1 still owns the Hunter.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Credits: 1
WithP1GroundArena: [LAW_156:1:0 SOR_095:1:0]

## WHEN
- P2>UseUnitAbility:theirGroundArena-0
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_156
P1CREDITCOUNT:0

---

# FrontStolenBountyHunterNowEnemyNoCredit
#// LAW_007 Boba Fett (leader front) — same ownership-not-control check on the front side. P2 takes control
#// of P1's LAW_156 Hunter For Hire via its "any player may use" action, then attacks and defeats P1's
#// SOR_095 Battlefield Marine. The attacker is enemy-controlled, so no Credit for P1 and Boba stays ready.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Credits: 1
WithP1GroundArena: [LAW_156:1:0 SOR_095:1:0]

## WHEN
- P2>UseUnitAbility:theirGroundArena-0
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_156
P1CREDITCOUNT:0
P1LEADER:READY

---

# FrontBHTradesAndDefeated_StillCreatesCredit
#// LAW_007 Boba Fett (leader form) — "When a friendly Bounty Hunter's attack ends, if the defending unit
#// was defeated, you may exhaust Boba → create a Credit." This fires even when the attacking Bounty Hunter
#// is DEFEATED in the same combat (Boba's ability is a field observer, not the attacker's own trigger).
#// A friendly 3/3 Bounty Hunter trades with an enemy 3/3 (both defeated); Boba is still offered the Credit.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:LAW_007}
WithActivePlayer: 1
WithP1GroundArena: JTL_065:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1LEADER:EXHAUSTED

---

# DeployedBHTradesAndDefeated_StillCreatesCredit
#// LAW_007 Boba Fett (DEPLOYED) — the deployed side also fires when the attacking Bounty Hunter is
#// DEFEATED in the same combat: the ability is Boba's field observer, not the attacker's own trigger.
#// A friendly 3/3 Bounty Hunter (JTL_065) trades with an enemy 3/3 (SOR_095); both are defeated, and
#// the Credit is still created (no exhaust decision on the deployed side — it just happens).

## GIVEN
CommonSetup: ggk/rrk/{myLeader:LAW_007:1:1:1}
WithActivePlayer: 1
WithP1GroundArena: JTL_065:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P1LEADER:DEPLOYED

---

# FrontDeclineExhaust_NoCredit
#// LAW_007 Boba Fett (leader front) — the exhaust is optional ("you may exhaust this leader"). LAW_124
#// defeats SOR_128 on attack, but P1 declines the offer: no Credit is created and Boba stays ready.

## GIVEN
CommonSetup: ygk/grw/{
  myLeader:LAW_007;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P1LEADER:READY

---

# DeployedBobaHimselfTrades_StillCreatesCredit
#// The field observer must count Boba himself when HE is the attacking Bounty Hunter and dies in the
#// trade: per CR simultaneous-removal the condition is evaluated as of the state that caused it, so a
#// deployed Boba (3/6, seeded 3 damage) trading with SOR_095 (3/3) still creates the Credit even
#// though he is gone (leader returns exhausted) by the time the observer looks.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:LAW_007:1:1:1:3}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1CREDITCOUNT:1
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1NODECISION
