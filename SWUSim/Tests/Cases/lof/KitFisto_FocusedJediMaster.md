# Deployed_Passive_PerJedi
#// LOF_011 Kit Fisto (deployed, 1/6) — passive: gets +1/+0 for each OTHER friendly Jedi unit. With
#// two other Jedi (LOF_230, LOF_093) → power 3.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:LOF_011:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_230:1:0
WithP1GroundArena: LOF_093:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:2:POWER:3

---

# JediAttackDeal2
#// LOF_011 Kit Fisto — Action [1 resource, Exhaust]: If you attacked with a Jedi unit this phase, deal 2
#// damage to a unit. Plo Koon (a Jedi) attacks first; then the leader deals 2 to SOR_059.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_011;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# NoJediAttack_NoEffect
#// LOF_011 Kit Fisto (front) — the Action's "deal 2 damage" is gated on having attacked with a Jedi this
#// phase. P1 attacks only with a NON-Jedi (SOR_046 Battlefield Marine) and then activates Kit Fisto: the
#// condition is unmet so no damage is dealt, but the ability still resolves — Kit Fisto exhausts and the
#// 1-resource cost is paid. Intended: "should not be able to deal 2 damage ... hasn't attacked with a Jedi".

## GIVEN
CommonSetup: brw/bbk/{myLeader:LOF_011;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# StolenJediAttacked_EnablesAbility
#// LOF_011 Kit Fisto (front) — "If YOU attacked with a Jedi unit this phase". Ownership is irrelevant;
#// what matters is that the attack was made by YOU with a unit that has the Jedi trait. LOF_050 Plo Koon
#// (Jedi) sits in P1's ground arena but is OWNED by P2 — the end state after a take-control effect. P1
#// attacks the enemy base with him, which sets P1's "attacked with a Jedi" flag, so Kit Fisto's Action
#// then deals its 2 damage normally.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_011;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArenaControlled: LOF_050:2
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# OnlyOpponentAttackedWithJedi_NoEffect
#// LOF_011 Kit Fisto (front) — the "attacked with a Jedi this phase" flag is PER PLAYER. The OPPONENT
#// attacking with a Jedi must not enable P1's Action. P2 attacks the P1 base with LOF_050 Plo Koon (a
#// Jedi); P1 then activates Kit Fisto, which is still usable (a state-changing cost keeps it available)
#// but deals NO damage — the leader exhausts and the 1 resource is spent for nothing.
#// Distinct cause from NoJediAttack_NoEffect above, where nobody attacked with a Jedi at all.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_011;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 1
WithP1GroundArena: SOR_059:1:0
WithP2GroundArena: LOF_050:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# JediStolenByOpponentAttacked_NoEffect
#// LOF_011 Kit Fisto (front) — a Jedi P1 OWNS but no longer CONTROLS does not help P1: the flag follows
#// whoever declared the attack. LOF_050 Plo Koon is owned by P1 but sits in P2's arena (the end state
#// after the opponent took control of him); P2 attacks P1's base with him. P1's Action is therefore
#// unenabled — usable, but no damage — even though the attacking Jedi is P1's own card.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_011;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 1
WithP1GroundArena: SOR_059:1:0
WithP2GroundArenaControlled: LOF_050:1

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# JediTraitLostAfterAttacking_AbilityStillEnabled
#// LOF_011 Kit Fisto (front) — "If you ATTACKED with a Jedi unit this phase" records a PAST event; it is
#// not re-derived from the board when the Action is activated. LOF_061 Secretive Sage (a Force unit)
#// carries LOF_052 Jedi Trials + 3 Shield tokens = 4 upgrades, exactly the threshold at which Jedi Trials
#// grants the Jedi trait. The Sage attacks the enemy base — a Jedi at that moment, so P1's flag is set.
#// Two enemy attacks then strip it back below the threshold and it STOPS being a Jedi, yet Kit Fisto's
#// Action must still deal its 2 damage.
#// ⚠ Jedi Trials also grants "On Attack: give an Experience token to this unit", so attacking pushes the
#// Sage to FIVE upgrades — one popped Shield only returns it to 4 (still a Jedi). Two Shields must go.
#// The Sage ends at 3 upgrades (Jedi Trials + 1 Shield + the Experience) and is no longer a Jedi.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_011;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 1
WithP1GroundArena: LOF_061:1:0
WithP1GroundArenaUpgrade: 0:LOF_052
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:0
- P1>Pass
- P2>AttackGroundArena:1:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_061
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:NOTTRAIT:Jedi
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:2:DAMAGE:2
