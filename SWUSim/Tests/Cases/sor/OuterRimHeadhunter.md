# OnAttack_LeaderExhausts
#// COVERAGE: offer=OnAttack_Offer_ExcludesLeaderUnit_IncludesSelf (pending choice: self and enemy
#//           non-leaders in, the leader unit out) + OnAttack_Offer_ExcludesENEMYLeaderUnitToo (both
#//           players' leader units out — the exclusion is leader-ness, not controller) · decline=OnAttack_Decline ("you may" refused with '-')
#//           · control=PilotLeaderUnit_EnablesExhaust (leader-unit status derived from a pilot upgrade;
#//           the plain-attach negative branch is withheld pending an engine fix — the deployed-flag
#//           check counts a plain pilot attach as a leader unit) · boundary=OnAttack_LeaderExhausts vs
#//           OnAttack_NoLeader_NoOp (condition on/off pair), plus the Raid 1 on/off pair
#//           OnAttack_Decline (attacking, base takes 2) vs Raid1_OnlyWhileAttacking_NotWhileDefending
#//           (defending, deals its printed 1) · reqboundary=N/A (the exhaust resolves
#//           inside the attack window; nothing is carried across an action boundary)
#// SOR_208 Outer Rim Headhunter (1/3, Space, Raid 1) — On Attack: If you control a leader
#// unit, you may exhaust a non-leader unit. P1 controls a deployed leader unit (Leia, ground @0),
#// so on attack the player may exhaust a non-leader unit — here the enemy Battlefield Marine.
#// (The leader unit itself is non-targetable here; "non-leader unit" excludes it.)
#// (Raid 1 is a keyword, auto-applied; this tests only the On Attack ability.)

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_208:1:0     # Outer Rim Headhunter (ready) — attacker, space idx 0
WithP2GroundArena: SOR_095:1:0    # enemy non-leader unit — exhaust target

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# OnAttack_NoLeader_NoOp
#// SOR_208 Outer Rim Headhunter — the exhaust is conditional on controlling a LEADER unit.
#// Here the leader is NOT deployed, so on attack nothing is offered: no decision is pending
#// and the enemy unit stays ready. Absence guard for the leader-unit condition.

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_208:1:0     # attacker
WithP2GroundArena: SOR_095:1:0    # enemy unit — must stay ready

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# OnAttack_Offer_ExcludesLeaderUnit_IncludesSelf
#// SOR_208 Outer Rim Headhunter — the exhaust pool is every NON-LEADER unit on either side,
#// including the attacker itself; the deployed leader unit (Leia, ground idx 0) is excluded.
#// The may-choose is left PENDING so the exact legal-target set can be asserted: the Headhunter
#// (mySpaceArena-0) plus both enemy ground units — Leia not offered.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_208:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0&theirGroundArena-1

---

# OnAttack_Decline
#// SOR_208 Outer Rim Headhunter — the exhaust is "you may": refusing the offer with '-' leaves
#// every unit as it was. The attack itself still resolves: Raid 1 makes the base hit 2.
#// The enemy unit stays READY and no decision is left pending.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_208:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2BASEDMG:2
P1NODECISION

---

# PilotLeaderUnit_EnablesExhaust
#// SOR_208 Outer Rim Headhunter — the condition also holds when the leader unit is DERIVED: a leader
#// deployed as a Pilot upgrade (JTL_008 Wedge on an AT-ST) makes the host a leader unit. The host is
#// then excluded from the pool as a leader unit, while the Headhunter itself and the enemy unit stay
#// eligible. The enemy Battlefield Marine is exhausted.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:JTL_008;
  myLeaderDeployedPilot:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1SpaceArena: SOR_208:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:2

---

# Raid1_OnlyWhileAttacking_NotWhileDefending
#// SOR_208 Outer Rim Headhunter — the FIRST printed clause, "Raid 1 (This unit gets +1/+0 while
#// attacking.)", was only ever seen from the attacking side (OnAttack_Decline reads a base hit of 2).
#// This is the negative that proves the "while attacking" qualifier is load-bearing: the Headhunter
#// DEFENDS instead. P2's Alliance X-Wing (2/3) attacks it, so the Headhunter deals its printed 1 back,
#// not 2 — the X-Wing ends on 1 damage and the Headhunter (1/3) on 2, both alive. Its power reads 1
#// throughout. The On Attack ability belongs to the attacker, so no decision is raised for P1 either.

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: SOR_208:1:0     # Outer Rim Headhunter 1/3 — DEFENDER
WithP2SpaceArena: SOR_237:1:0     # Alliance X-Wing 2/3 — attacker

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# OnAttack_Offer_ExcludesENEMYLeaderUnitToo
#// SOR_208 Outer Rim Headhunter — "exhaust a NON-LEADER unit" is unqualified as to controller, so the
#// exclusion is leader-ness and nothing else. OnAttack_Offer_ExcludesLeaderUnit_IncludesSelf only ever
#// showed the FRIENDLY leader unit being excluded, which a controller-scoped filter would also produce.
#// Here BOTH players have a deployed leader unit: P1's (the enabling condition) and P2's Sabine Wren.
#// The pool must be exactly the two non-leader units — the Headhunter itself and the enemy Battlefield
#// Marine — with both leader units out. The may-choose is left PENDING so the pool can be read.
#// (Deployed leaders seat at the END of their arena, so P2's Sabine is theirGroundArena-1.)

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1;
  theirLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_208:1:0     # attacker
WithP2GroundArena: SOR_095:1:0    # enemy NON-leader — must be offered

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0
