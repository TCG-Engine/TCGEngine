# OnAttack_ArenaDebuff
#// JTL_035 Tam Ryvora (pilot) — Attached gains "On Attack: give an enemy unit in this arena -1/-1." The
#// host (SOR_237 + pilot) attacks the base; the granted On Attack debuffs SOR_044 to 1/2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_035
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:POWER:1

---

# SimulateRequestBoundary_ArenaDebuffTarget
#// JTL_035 Tam Ryvora — the granted "give an enemy unit in this arena -1/-1" On Attack offer ends the
#// request in production, so the answer arrives in a fresh process with every transient global empty.
#// Mirrors OnAttack_ArenaDebuff but seats a SECOND enemy space unit (SOR_237) so the mandatory offer stays
#// interactive instead of auto-resolving its lone target, with the boundary inserted before the answer:
#// the pending continuation (APPLY_PHASE_DEBUFF|1|1|JTL_035), its arena/side restriction and the in-flight
#// attack must all survive serialization. SOR_044 (2/3) drops to 1/2; the unchosen SOR_237 stays 2/3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_035
WithP2SpaceArena: SOR_044:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_044
P2SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:HP:2
P2SPACEARENAUNIT:1:CARDID:SOR_237
P2SPACEARENAUNIT:1:POWER:2
P2SPACEARENAUNIT:1:HP:3


---

# Offer_OnlyEnemyUnitsInTheAttackingArena
#// JTL_035 Tam Ryvora — the granted "Give an ENEMY unit IN THIS ARENA -1/-1 for this phase" offer must be
#// scoped by BOTH controller and arena. The piloted host attacks from the SPACE arena, so only the two
#// enemy SPACE units may be offered: the enemy GROUND unit (wrong arena) and P1's own second space unit
#// (wrong controller) must not be. The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_035
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_044:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirSpaceArena-0&theirSpaceArena-1
