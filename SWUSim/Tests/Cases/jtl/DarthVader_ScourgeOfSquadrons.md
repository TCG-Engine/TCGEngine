# OnAttack_DealChain
#// JTL_142 Darth Vader (pilot) — Attached gains "On Attack: may deal 1 to a unit; if a unit is defeated
#// this way, may deal 1 to a unit or base." The host attacks SOR_044; the granted On Attack kills the
#// 1-HP SOR_225 and chains 1 damage to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# OnAttack_NoDefeat_NoChain
#// JTL_142 Darth Vader (pilot) — the chained second damage only fires "if a unit is defeated" by the first
#// 1 damage. Dealing 1 to a healthy SOR_095 (3 HP) does NOT defeat it, so there is no follow-up damage:
#// SOR_095 sits at 1 and P2's base is untouched by the ability.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:0

---

# OnAttack_ShieldedNoChain
#// JTL_142 Darth Vader (pilot) — the chained second damage requires the first 1 damage to DEFEAT a unit.
#// Dealing 1 to a SHIELDED enemy unit only pops the shield (no HP damage, no defeat), so there is no
#// follow-up damage: SOR_095 keeps DAMAGE 0, loses its shield (SHIELDCOUNT 1 → 0), and P2's base is
#// untouched by the ability.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:0
P1NODECISION

---

# TwinSuns_TheChainCanHitAFarSeatsBase
#// ⚠ TWIN SUNS SWEEP (2026-08-27) — TWO defects on one card, and neither was visible to a seat-helper scan.
#//   • the offer built its base list BY HAND as ['theirBase-0','myBase-0'] — a STRING that names seat 2
#//     and nothing else, so a far seat's base was not even offered;
#//   • the applier resolved the pick with a my/their ternary, collapsing every non-"my" mzID onto seat 2.
#// Vader's pilot attacks SEAT 2's base, kills a SEAT 4 unit with the chain, then sends the follow-up
#// damage to SEAT 4's base — three different seats in one action, so nothing about the old code survives.
## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaPilot: 0:JTL_142
WithP4GroundArena: SOR_059:1:2
## WHEN
- P1>AttackSpaceArena:0:P2B
- P1>AnswerDecision:p4GroundArena-0
- P1>AnswerDecision:p4Base-0
## EXPECT
SEATCOUNT:4
P4GROUNDARENACOUNT:0
P4BASEDMG:1
P2BASEDMG:5
