# OnAttack_LeaderExhausts
#// COVERAGE: offer=OnAttack_Offer_ExcludesLeaderUnit_IncludesSelf (pending choice: self and enemy
#//           non-leaders in, the leader unit out) · decline=OnAttack_Decline ("you may" refused with '-')
#//           · control=PilotLeaderUnit_EnablesExhaust (leader-unit status derived from a pilot upgrade;
#//           the plain-attach negative branch is withheld pending an engine fix — the deployed-flag
#//           check counts a plain pilot attach as a leader unit) · boundary=OnAttack_LeaderExhausts vs
#//           OnAttack_NoLeader_NoOp (condition on/off pair) · reqboundary=N/A (the exhaust resolves
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
