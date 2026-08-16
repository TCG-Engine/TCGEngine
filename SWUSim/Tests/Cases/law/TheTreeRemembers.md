# DefeatsCheapUnit
#// LAW_132 The Tree Remembers (Vigilance event, cost 4) — "An enemy unit loses all abilities for this
#// phase. If it costs 3 or less, defeat it." SEC_080 (cost 2) -> defeated.

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# LosesAbilitiesNotDefeated
#// LAW_132 The Tree Remembers — a costly enemy (SOR_035, cost 4, innate Sentinel) is NOT defeated but
#// loses all abilities for this phase (Sentinel gone).

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2GroundArena: SOR_035:1:0
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_035
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1DISCARDCOUNT:1

---

# WipesLeaderNotDefeated
#// LAW_132 The Tree Remembers — a deployed enemy LEADER unit is a legal target: it loses all abilities for
#// the phase but is NOT defeated (a leader is not "cost 3 or less"). Lone enemy auto-resolves as the target.

## GIVEN
CommonSetup: bbw/bgw/{myResources:4;theirLeader:SOR_005:1:1:1}
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0

## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P1DISCARDCOUNT:1

---

# Control_UnblankedSelfBuffAndAuraStack
#// Bug #924 baseline. JTL_115 Clone Combat Squadron (3/3) "gets +1/+1 for each other friendly space
#// unit" — its OWN ability. JTL_085 Victor Leader (2/4) gives "each other friendly space unit +1/+1"
#// — an EXTERNAL aura. With both in play and nothing blanked, JTL_115 is 3/3 +1 (its own, counting
#// Victor Leader) +1 (Victor Leader's aura) = 5/5. Proves the fixture actually produces both buffs,
#// so the blanking cases below can't pass vacuously.

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2SpaceArena: JTL_115:1:0
WithP2SpaceArena: JTL_085:1:0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_115
P2SPACEARENAUNIT:0:POWER:5
P2SPACEARENAUNIT:0:HP:5

---

# BlankedUnitLosesSelfBuffButKeepsExternalAura
#// Bug #924, as reported. The Tree Remembers targets JTL_115 (cost 4 → NOT defeated, correct). It
#// loses all abilities, so its OWN "+1/+1 per other friendly space unit" stops applying — but
#// JTL_085 Victor Leader is untouched, so its aura still reaches the blanked unit. 3/3 +1 = 4/4.
#//
#// _SWUSpaceUnitBonus computed both effects without ever consulting LostAbilities(), so the blanked
#// unit kept buffing itself and stayed 5/5.

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2SpaceArena: JTL_115:1:0
WithP2SpaceArena: JTL_085:1:0
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:JTL_115
P2SPACEARENAUNIT:0:POWER:4
P2SPACEARENAUNIT:0:HP:4

---

# BlankedAuraSourceStopsBuffingOthers
#// The counterpart, same root cause from the other side: a blanked SOURCE must stop granting. Uses
#// SHD_072 Imprisoned (+0/+0, "loses its current abilities") rather than The Tree Remembers, because
#// JTL_085 costs 3 — Tree Remembers would DEFEAT it, so it can never exercise the blanked-source path.
#//
#// Victor Leader blanked → its aura stops. JTL_115 keeps its own self-buff (JTL_085 is still a
#// friendly space unit to count, it just has no abilities): 3/3 +1 = 4/4. JTL_085 was never affected
#// by its own aura, so it stays 2/4. Mirrors the existing "a blanked source can't grant keywords to
#// allies" rule in KeywordEffects.

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2SpaceArena: JTL_115:1:0
WithP2SpaceArena: JTL_085:1:0
WithP2SpaceArenaUpgrade: 1:SHD_072

## EXPECT
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:JTL_115
P2SPACEARENAUNIT:0:POWER:4
P2SPACEARENAUNIT:0:HP:4
P2SPACEARENAUNIT:1:CARDID:JTL_085
P2SPACEARENAUNIT:1:POWER:2
P2SPACEARENAUNIT:1:HP:4

---

# TreeRemembersDefeatsVictorLeader_SelfBuffDropsToZero
#// Completes the picture for the reported board: Victor Leader costs 3, so targeting it with The Tree
#// Remembers DEFEATS it outright. JTL_115 then has no other friendly space unit to count and loses the
#// aura too, falling to its printed 3/3. (This is what my first attempt at the case above accidentally
#// asserted — kept, because it pins the cost-3 branch on this exact board.)

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2SpaceArena: JTL_115:1:0
WithP2SpaceArena: JTL_085:1:0
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_115
P2SPACEARENAUNIT:0:POWER:3
P2SPACEARENAUNIT:0:HP:3

---

# BlankedUnitLosesSelfBuff_SurvivesTheRequestBoundary
#// LAW_132 The Tree Remembers — the event is played in one request and its target is chosen in a FRESH
#// process, so the in-flight event resolution and the "loses all abilities for this phase" stamp it then
#// writes must both come out of the serialized gamestate. Mirrors
#// BlankedUnitLosesSelfBuffButKeepsExternalAura with a request boundary inserted between the play and the
#// answer. The pool is a genuine two-candidate MZCHOOSE (theirSpaceArena-0 & theirSpaceArena-1), so the
#// decision is really pending across the boundary.

## GIVEN
CommonSetup: bbw/bgw/{myResources:4}
WithP2SpaceArena: JTL_115:1:0
WithP2SpaceArena: JTL_085:1:0
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:JTL_115
P2SPACEARENAUNIT:0:POWER:4
P2SPACEARENAUNIT:0:HP:4

---

# OfferPool_EnemyOnlyBothArenasLeaderUnitIncluded
#// LAW_132 The Tree Remembers — offer assertion for "AN ENEMY UNIT loses all abilities". "Enemy" is the
#// only restriction, so friendly units in BOTH arenas are the violators and must be out, while every enemy
#// body is in: the enemy ground unit, the enemy space unit, and the deployed Cad Bane leader unit at
#// theirGroundArena-1 (a leader unit has abilities to lose). Note the pool is NOT filtered by the "costs 3
#// or less" clause — that is a rider on the effect, not on targeting, which is exactly what
#// LosesAbilitiesNotDefeated and WipesLeaderNotDefeated depend on. Every existing section either
#// auto-resolved against a lone enemy or answered a two-enemy pick, so none of them could see a friendly
#// unit leaking into the pool.
#// COVERAGE: offer=OfferPool_EnemyOnlyBothArenasLeaderUnitIncluded (pending SELECTABLEEXACT: friendly units
#//           in both arenas excluded, deployed enemy leader unit included) · decline=N/A (no "you may" —
#//           the blank-and-maybe-defeat is mandatory once the event resolves) · boundary
#//           pair=DefeatsCheapUnit (cost 2 -> defeated) vs LosesAbilitiesNotDefeated / WipesLeaderNotDefeated
#//           (cost 4 and a leader -> blanked but alive) · reqboundary=
#//           BlankedUnitLosesSelfBuff_SurvivesTheRequestBoundary · control=N/A (the blank is a phase-scoped
#//           stamp on the chosen card, not a seat-bound marker; Control_UnblankedSelfBuffAndAuraStack is
#//           the unblanked baseline that keeps the blanking cases from passing vacuously)

## GIVEN
CommonSetup: bbw/rrk/{myResources:4; theirLeader:ASH_011:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_132

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0
