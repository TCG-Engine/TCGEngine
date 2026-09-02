# Deploy_BelowThreshold_NoOp
#// COVERAGE: offer=WhenDeployed_Offer_SpansBOTHSides_ExcludesUndamagedAndLeaderUnits +
#//           Deployed_OnAttack_Offer_ExcludesHIMSELF_AndTheEnemy (the two deployed clauses' pools)
#//           decline=OnAttack_SacrificeNo · boundary=Deploy_BelowThreshold_NoOp (the Epic threshold)
#//           control=N/A - STRUCTURAL: the front Action's cost and both deployed clauses read only the
#//           board, never an owner-scoped zone; the TAKE-CONTROL direction is the card's own effect and
#//           is covered by WhenDeployed_StealDamagedUnit.
#//           reqboundary=N/A - ⚠ SITUATIONAL GAP, not a structural one: the front Action defeats a
#//           friendly unit as a COST and then deals damage + draws behind the target decision, which is
#//           exactly the shape the cell exists for. No boundary section exists. Open cell.
#//           modes=2P only - no player reference; "another friendly unit" is the caster's own board.
#// SOR_006 Emperor Palpatine — Epic Action: "If you control 8 or more resources, deploy
#// this leader." With only 7 resources the condition is unmet, so DeployLeader is a no-op:
#// the leader stays in the leader zone, ready, with the epic action still available.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1LEADER:READY
P1GROUNDARENACOUNT:0
P1NODECISION

---

# LeaderAbility_DealDamageDrawCard
#// SWUSim Replay Schema
Palpatine leader ability — pay 1 resource, defeat friendly, deal 1 damage to a unit

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# LeaderAction_NoFriendlyUnit_Unaffordable
#// SOR_006 Emperor Palpatine — Leader Action costs [1 resource, exhaust, defeat a friendly
#// unit]. With 8 resources but no friendly unit, the defeat-a-friendly-unit cost cannot be
#// paid, so the action is a no-op: leader stays ready, no resource spent, nothing queued.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:8
P1NODECISION

---

# OnAttack_SacrificeNo
#// SWUSim Replay Schema
Palpatine OnAttack — decline sacrifice, no bonus damage, normal combat

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# OnAttack_SacrificeYes
#// SWUSim Replay Schema
Palpatine OnAttack — sacrifice friendly unit, deal 1 damage, proceed to combat

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_006
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1

---

# OnAttack_SacrificeYes_Useless
#// SWUSim Replay Schema
Palpatine OnAttack — sacrifice friendly unit, deal 1 damage, proceed to combat

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_006
P1GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1

---

# WhenDeployed_NoDamagedUnit
#// SWUSim Replay Schema
Palpatine WhenDeployed — no damaged units, no steal trigger fires

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1NODECISION

---

# WhenDeployed_StealDamagedUnit
#// SWUSim Replay Schema
Palpatine WhenDeployed — take control of a damaged non-leader unit (auto-resolve single target)

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP2GroundArena: SOR_095:1:2
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# Deployed_DamagedTOKENIsALegalStealTarget
#// "When Deployed: Take control of a damaged NON-LEADER unit" — non-leader excludes leaders, not
#// TOKENS. A bare ["Unit"] pool dropped both (the Open Fire family sweep): the damaged Clone Trooper
#// token and the damaged real unit must BOTH be offered, and stealing the token crosses it to P1's
#// arena. The undamaged enemy unit pins the damage gate in the same offer.

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: [TWI_T02:1:1 SOR_095:1:1 SOR_046:1:0]

## WHEN
- P1>DeployLeader

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# WhenDeployed_Offer_SpansBOTHSides_ExcludesUndamagedAndLeaderUnits
#// THE OFFER CELL for the When Deployed clause. Every existing section answers or auto-resolves it, so
#// the POOL itself was never asserted — and this clause carries THREE restrictions at once, two of them
#// printed and one implicit:
#//   • "DAMAGED"    — an undamaged unit is excluded (P2's Stormtrooper, seeded at 0 damage);
#//   • "NON-LEADER" — Palpatine himself is a leader unit and is out, even though he is undamaged too;
#//   • no controller word at all — so "a damaged non-leader unit" spans BOTH SIDES, and P1's own damaged
#//     Consular Security Force is a legal (if pointless) pick. Taking control of a unit you already
#//     control does nothing, but the text gives no basis for excluding it.
#// That last one is the load-bearing part: an enemy-only pool is the natural reading of a take-control
#// effect and satisfies every other section in this file, all of which steal from P2.
#// Two legal targets, so nothing auto-resolves and there is a real pool to inspect.
## GIVEN
CommonSetup: ggk/ggk/{myLeader:SOR_006}
SkipPreGame: true
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_128:1:0
WithP1Resources: 8
## WHEN
- P1>DeployLeader
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Deployed_OnAttack_Offer_ExcludesHIMSELF_AndTheEnemy
#// THE OFFER CELL for the deployed On Attack, whose three existing sections all resolve a single
#// auto-picked target and so never see the pool.
#// "You may defeat ANOTHER FRIENDLY unit" carries both qualifiers, and they exclude different things:
#// "friendly" keeps the enemy Marine out, and "another" keeps PALPATINE HIMSELF out — he is attacking,
#// so he is very much a friendly unit, and only the word "another" removes him.
#// Two friendly units are seeded so the choice is genuine; a pool missing the "another" clause would
#// read three entries and let Palpatine sacrifice himself to his own ability.
#// ⚠ The deployed leader is appended last, so P1's ground is [SOR_063, SOR_046, Palpatine] and he
#// attacks from index 2.
## GIVEN
CommonSetup: ggk/ggk/{myLeader:SOR_006}
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8
## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:YES
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
