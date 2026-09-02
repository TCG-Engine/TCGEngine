# Front_HealsAndWeakensADamagedEnemy
#// HMW_015 Bossk "Cruel Hunter" (Leader, Ground, 4/5, cost 5, [Cunning][Villainy], Underworld/Bounty
#// Hunter, unique).
#// FRONT:  Action [Exhaust]: Heal 1 damage from a damaged enemy unit and give a Weakness token to it.
#// EPIC:   Epic Action: If you control 5 or more resources, deploy this leader.
#// DEPLOY: On Attack: You may deal 2 damage to a unit with a token upgrade on it.
#//         (Shield and Weakness tokens are token upgrades.)
#//
#// COVERAGE (front): offer=Front_Offer_OnlyDamagedEnemies_NotUndamagedNorFriendly (SELECTABLEEXACT —
#//           BOTH printed restrictions at once, "damaged" and "enemy") ·
#//           decline=N/A (structural: no "may" and no "up to"; a mandatory MZCHOOSE, and its zone is a
#//           public arena so the hidden-zone always-declinable rule does not reach it) ·
#//           boundary=N/A (structural: no threshold or count in the clause — exactly 1 healed, exactly
#//           1 token. The Epic pair below covers the only number the card prints) ·
#//           control=N/A (structural: "enemy" is recomputed from live control at offer time, and the
#//           clause names no owner-scoped zone; a stolen unit is simply enemy or not, which is what
#//           Front_TeamSuns_TeammatesDamagedUnitIsNotEnemy already exercises) ·
#//           reqboundary=Front_RequestBoundary_TheTargetSurvivesIt
#// COVERAGE (deployed): offer=Deployed_Offer_OnlyUnitsWithATokenUpgrade_BothSides ·
#//           decline=Deployed_Decline_NothingHappensAndTheAttackStillResolves ·
#//           boundary=N/A (structural: a flat 2 damage, no threshold) ·
#//           control=N/A (structural: "a unit" is unqualified — it names neither controller nor owner,
#//           so there is no seat-relative reading for a control change to alter) ·
#//           reqboundary=Deployed_RequestBoundary_TheOnAttackPickSurvivesIt
#// modes=2P,TeamSuns — the FRONT says "**enemy**", which in a 2v2 is the opposing TEAM and excludes a
#//           teammate's damaged unit (Front_TeamSuns_TeammatesDamagedUnitIsNotEnemy). Neither side
#//           contains a player reference ("an opponent"/"a player"/"that player"), so there is no
#//           who-acts choice and no Twin Suns section: at 3–4 seats "enemy" and "a unit" both fan out
#//           through the shared pool helpers on the same code path a Team Suns board already walks.
#//
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md. Two readings are reasoned rather than
#// looked up, and each is flagged at the section that pins it:
#//   (1) FRONT — "Heal 1 … AND give a Weakness token to it" is ONE target and TWO effects, joined by
#//       "and" rather than "If you do". ⚠ MEASURED: their ORDER turns out to be unobservable and no
#//       shrink sweep is reachable, because the heal and the −1 HP cancel exactly — see
#//       Front_CanNeverDefeatItsTarget_HealAndMinusOneHpCancel, which corrected my first reading.
#//   (2) DEPLOYED — the reminder "(Shield and Weakness tokens are token upgrades.)" is a REMINDER, not
#//       an exhaustive list. Implemented against the rules CATEGORY (CardType 'Token Upgrade'), which
#//       also covers Experience and Advantage. See Deployed_ExperienceIsATokenUpgradeToo.
#//
#// The Epic Action needs NO code — SWUDeployLeader's threshold IS the printed cost (5), i.e. "5 or more
#// resources" — but it gets the boundary pair below so a change to that default cannot silently alter
#// this card. It is not counted as a clause for the section floor.
#//
#// FRONT positive: two damaged enemies so the choose really prompts. SOR_046 is a 3/7 on 3 damage —
#// heal 1 leaves it on 2, and the Weakness (HMW_T02, a −1/−1 Token Upgrade) makes it a 2/6. The other
#// damaged enemy is untouched, and the leader has paid its exhaust.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# Front_Offer_OnlyDamagedEnemies_NotUndamagedNorFriendly
#// HMW_015 front — the OFFER cell, carrying BOTH printed restrictions in one board. "a DAMAGED ENEMY
#// unit" excludes two different populations, and answering a target proves neither:
#//   • an UNDAMAGED enemy (SEC_080 at 0 damage) — the heal would do nothing, so it is not a legal
#//     target at all rather than a legal target that fizzles;
#//   • a DAMAGED FRIENDLY (P1's own SOR_095 on 2) — "enemy" is a controller restriction, and a card
#//     that collected "any damaged unit" would happily offer your own.
#// Two legal targets remain, which is also what keeps the choose from auto-resolving and leaves an
#// offer to read at all.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_046:1:1
WithP2GroundArena: SOR_095:1:1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# Front_CanNeverDefeatItsTarget_HealAndMinusOneHpCancel
#// HMW_015 front — the NET EFFECT of the two clauses on one target, and the fact that falls out of it:
#// this side can never defeat the unit it hits. A legal target is damaged and alive (1 ≤ D < H); after
#// heal-1 and −1/−1 it sits on max(0, D−1) damage with H−1 HP, which is lethal only when D ≥ H — the
#// contradiction. Here a 2/2 on 1 damage ends as a 1/1 on 0 damage: still standing.
#//
#// ⚠ THIS SECTION IS NOT AN ORDERING TEST, and it was written as one before the mutations corrected
#// me. Swapping the two effects, and deleting the shrink sweep, BOTH came back green — because with no
#// reachable defeat there is nothing for a sweep to find and nothing for the order to change. The card
#// file records the arithmetic; the sweep is deliberately absent there rather than kept as dead code.
#// What this section does pin is the net stat/damage result and the survival, which is worth having:
#// an implementation that healed 0, or applied −1/−1 twice, or dealt damage instead of healing, all
#// fail here.
#// The second damaged enemy is there so the choose prompts rather than auto-resolving.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: TWI_T02:1:1
WithP2GroundArena: SOR_046:1:1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02

---

# Front_NoDamagedEnemy_SoftPass_LeaderStillExhausts
#// HMW_015 front — the no-valid-target cell, and the house rule it has to follow. "a damaged enemy
#// unit" is an EFFECT GATE, not a cost: with no legal target the Action is still AVAILABLE, the leader
#// still pays its exhaust, and the ability simply resolves to nothing. Moving such a condition into
#// SWULeaderActionAffordable would make the whole action vanish instead (the TS26_02 Anakin lesson,
#// and HMW_005 Jar Jar's soft-pass in this same set).
#// The board is deliberately adversarial: the opponent HAS a unit (undamaged) and P1 HAS a damaged one,
#// so only the conjunction "damaged AND enemy" produces an empty pool.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P2NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Front_TeamSuns_TeammatesDamagedUnitIsNotEnemy
#// HMW_015 front — the TEAM SUNS cell, earned by the printed word "**enemy**". In a 2v2 a teammate's
#// unit is FRIENDLY even though you do not control it, so it must not appear in an enemy-scoped pool.
#// Teams are seat PARITY (1+3 versus 2+4), so P1's partner is P3 and its enemies are P2 and P4.
#// All three other seats field a damaged unit, so only the team relationship can shorten the offer —
#// a pool built as "every unit that is not mine" would wrongly include P3's.
#// (Far seats address as p{n}GroundArena-{i}; CommonSetup dresses seats 1–2 only, which is why the far
#// boards are seeded explicitly. The ACTOR stays on seat 1 — there is no WithP3Leader.)

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:1
WithP3GroundArena: SOR_046:1:1
WithP4GroundArena: SEC_080:1:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:p2GroundArena-0&p4GroundArena-0

---

# Front_RequestBoundary_TheTargetSurvivesIt
#// HMW_015 front — the REQUEST-BOUNDARY cell. The target choose ends the request, so the continuation
#// that heals and then attaches the token resumes in a fresh process; anything it held in memory
#// between queueing the offer and resolving it would be gone, and the usual symptom is the handler
#// returning silently with the leader exhausted for nothing. Identical board and answer to the
#// positive, with one boundary inserted before the answer.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2

---

# Epic_DeployAtFiveResources
#// HMW_015 — the Epic Action needs no per-leader code (SWUDeployLeader gates on the leader's printed
#// cost, which is 5, i.e. exactly "5 or more resources"). This pair is the guard on that default.
#// Deploying is FREE — the threshold is a condition, not a payment — so all five resources stay ready.
#// The deployed leader unit enters play READY, which is what lets the On Attack sections below swing
#// on the turn it lands.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myResources:5
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_015
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:5

---

# Epic_BlockedAtFourResources
#// HMW_015 — the other half of the pair. One short of the threshold is a complete no-op: the leader
#// stays undeployed, nothing enters the arena, and the player keeps their action and their resources.
#// Without this the positive alone passes for ANY threshold value.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4

---

# Deployed_OnAttack_DealsTwoToAUnitWithATokenUpgrade
#// HMW_015 deployed side, driven through the REAL deploy→attack dispatch rather than a seeded leader
#// unit: P1 deploys Bossk (5 resources) and attacks with him the same turn (a deployed leader enters
#// ready). Seeding him into the arena would exercise the handler closure but not the wiring.
#//
#// P2's SOR_046 (3/7) carries a Weakness token, so it is a 2/6 and is the only unit with a token
#// upgrade; the 2 damage lands on it. The attack itself goes to the BASE (`:BASE` forces it past the
#// enemy units), so Bossk's 4 power is separately visible and the On Attack is not confused with combat
#// damage. A Weakness rather than a Shield on purpose — a Shield would ABSORB the 2 and hide the effect.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myResources:5
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
NOEXTRAACTION
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# Deployed_Decline_NothingHappensAndTheAttackStillResolves
#// HMW_015 deployed side — the DECLINE branch, which the printed "You may" earns. Answering '-' must
#// leave every unit untouched while the attack itself resolves normally (the base still takes Bossk's
#// 4). A decline that silently applied the damage anyway, or that swallowed the attack, would both
#// look like a pass without this pairing of assertions.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Deployed_Offer_OnlyUnitsWithATokenUpgrade_BothSides
#// HMW_015 deployed side — the OFFER cell, and it pins two things behaviour cannot:
#//   • "a unit" is UNQUALIFIED, so a FRIENDLY unit carrying a token upgrade is a legal target too
#//     (P1's own SOR_095 wears a Shield and IS offered — the card gives you the option of shooting
#//     your own board, which a friendly-excluding pool would silently remove);
#//   • "with a TOKEN upgrade on it" excludes a unit wearing an ordinary upgrade. SOR_120 Academy
#//     Training is a real Upgrade, not a Token Upgrade, so SEC_080 must NOT be offered — a filter that
#//     merely asked "is this unit upgraded?" would pass every other section in this file.
#// A bare unit is present as the third exclusion. The decision is left pending so the pool can be read.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArenaUpgrade: 1:SOR_120

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Deployed_ExperienceIsATokenUpgradeToo
#// ⚠ HMW_015 deployed side — THE OTHER JUDGEMENT CALL. The printed reminder says "(Shield and Weakness
#// tokens are token upgrades.)", and the tempting implementation is a two-CardID list. But a reminder
#// restates rules, it does not narrow them: SOR_T01 Experience is typed 'Token Upgrade' in the card
#// data exactly like SOR_T02 Shield and HMW_T02 Weakness (so are Advantage and every reprint of them),
#// so a unit wearing only an Experience token IS a legal target. Implemented against the rules
#// CATEGORY — CardType 'Token Upgrade' — rather than against the reminder's examples.
#// This section is the ONLY thing that reds a Shield-and-Weakness-only list; if the card is ever
#// errata'd to mean literally those two, it is the one to change.
#// P2's SOR_046 wears an Experience token (+1/+1, so a 4/8) and takes the 2; the bare SEC_080 does not.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:8
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# Deployed_NoUnitHasATokenUpgrade_NoPrompt
#// HMW_015 deployed side — the no-valid-target cell. With nothing on the table wearing a token upgrade
#// there is nothing to offer, so the attack resolves with no decision raised at all. The board is
#// adversarial again: BOTH sides field units and one of them wears a real (non-token) upgrade, so only
#// the correct filter produces an empty pool.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1NODECISION
P2NODECISION
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Deployed_RequestBoundary_TheOnAttackPickSurvivesIt
#// HMW_015 deployed side — the REQUEST-BOUNDARY cell for the other half of the card. The On Attack
#// offer pauses combat, so its answer arrives in a fresh process with the attack still mid-resolution:
#// the continuation must carry everything it needs on the decision itself. Same board and answer as
#// the Experience section, with one boundary inserted before the pick.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# Front_TheActionCLOSES_TurnPassesExactlyOnce
#// ⚠⚠ HMW_015 front — THE ACTION-CLOSE CELL, and it is deliberately written WITHOUT `P1OnlyActions`.
#// That directive claims initiative for the opponent, who then auto-passes, so the turn returns to P1
#// whether the action closed once, twice or not at all — every other section in this file is
#// structurally blind to it. MEASURED: deleting the closer from the leader Action left all fourteen of
#// them green, which is why this section exists.
#// At two seats the swap is an INVOLUTION, so `TURNPLAYER:2` catches BOTH failure directions at once:
#// no close leaves the turn on 1, and a double close swaps back to 1. NOEXTRAACTION is the stronger
#// structural form — it sees a second close even when the turn ends up compensated.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
TURNPLAYER:2
NOEXTRAACTION
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02

---

# Front_SoftPassAlsoCLOSESTheAction
#// HMW_015 front — the CONTROL for the section above, and the half that is easiest to leave unwired.
#// When there is no damaged enemy the offer queues NOTHING, so the closer is the only decision on the
#// queue; an implementation that closed the action inside the continuation rather than beside the offer
#// would resolve correctly on the effect path and then STRAND THE TURN here — the soft pass would eat
#// the player's action and never hand it over.
#// Same alternating fixture, no answer to give, and the turn must still pass exactly once.

## GIVEN
CommonSetup: yyk/rrk/{
  myLeader:HMW_015
}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
TURNPLAYER:2
NOEXTRAACTION
P1LEADER:EXHAUSTED
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
