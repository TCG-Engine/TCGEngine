# DefeatRefused_Takedown_vsLurkingTiePhantom
#// Game-log follow-up (2026-09-11). An effect that did NOT happen because its target is immune ("can't be
#// defeated / returned / captured / exhausted by enemy card abilities", "can't ready") was silent — the
#// effect line simply never appeared, with nothing to say why. SOR_077 Takedown: "Defeat a unit with 5 or
#// less remaining HP." SHD_187 Lurking TIE Phantom is immune. (Fixture from shd/LurkingTiePhantom.md.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_077
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
LOGCONTAINS:P1's [[SOR_077|Takedown]] couldn't defeat P2's [[SHD_187|Lurking TIE Phantom]]
LOGCOUNT:0:defeated P2's [[SHD_187

---

# CaptureRefused_RelentlessPursuit
#// SHD_232 Relentless Pursuit — a friendly unit captures an enemy non-leader unit; the Phantom is immune.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_232
WithP1GroundArena: SHD_148:1:0
WithP1GroundArena: SOR_131:1:0
WithP1SpaceArena: SHD_137:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
LOGCONTAINS:couldn't capture P2's [[SHD_187|Lurking TIE Phantom]]

---

# TakeControlRefused_Rey
#// LAW_149 Rey: "Opponents can't take control of this unit." SOR_224 Change of Heart: "Take control of an
#// enemy unit. At the start of the regroup phase, its owner takes control of it." (Fixture from
#// law/Rey_Skywalker.md.)

## GIVEN
CommonSetup: rrk/yyw/{theirResources:6;theirhandCardIds:SOR_224}
WithActivePlayer: 2
WithP1GroundArena: LAW_149:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
LOGCONTAINS:P2's [[SOR_224|Change of Heart]] couldn't take control of P1's [[LAW_149|Rey]]

---

# ReturnRefused_WatchThis_vsChewbacca
#// JTL_103 Chewbacca: "can't be defeated or returned to hand by enemy card abilities." IBH_052 Watch This:
#// "Return a non-leader unit that costs 6 or less to its owner's hand. Exhaust each other enemy unit in the
#// same arena." (Fixture from ibh/WatchThis.md.) The exhaust half still resolves — and is logged.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:READY
LOGCONTAINS:P1's [[IBH_052|Watch This]] couldn't return P2's [[JTL_103|Chewbacca]]
LOGCONTAINS:P1's [[IBH_052|Watch This]] exhausted P2's [[SOR_095|Battlefield Marine]]

---

# ExhaustRefused_ForceIllusion_vsKylosLightsaber
#// The exhaust immunity (LOF_040 Kylo Ren's Lightsaber on a Force unit) — the refusal now says so.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_149:1:0
WithP2GroundArenaUpgrade: 0:LOF_040

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY
LOGCONTAINS:P1's [[LOF_223|Force Illusion]] couldn't exhaust P2's [[LAW_149|Rey]]

---

# ReadyRefused_FrozenInCarbonite
#// SHD_193 Frozen in Carbonite: "Attached unit can't ready." SHD_182 Bravado: "Ready a unit." The ready is
#// refused in OnReadyCard (the unit stays exhausted) — and now says so.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_182
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:SHD_193

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
LOGCONTAINS:P1's [[SHD_182|Bravado]] couldn't ready P1's [[SOR_095|Battlefield Marine]]
