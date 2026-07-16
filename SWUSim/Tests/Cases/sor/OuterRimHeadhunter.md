# OnAttack_LeaderExhausts
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
