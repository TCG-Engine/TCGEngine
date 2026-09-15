# AurraSing_FrozenInCarbonite_CantReady
#// USER DECISION 2026-09-11 (gamelog-updates leftovers): the self-readies resolved INLINE by an engine
#// observer — TWI_166 Aurra Sing ("When an enemy ground unit attacks your base: Ready this unit"), ASH_160,
#// SHD_137 Punishing One, Rex's DC-17s' host — wrote Status directly and skipped OnReadyCard's rules. SHD_193
#// Frozen in Carbonite: "Attached unit can't ready." Aurra must stay exhausted (and the log says why).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_166:0:0
WithP2GroundArenaUpgrade: 0:SHD_193

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
LOGCONTAINS:couldn't ready P2's [[TWI_166|Aurra Sing]]
LOGCOUNT:0:readied itself

---

# AurraSing_InDebtToCrimsonDawn_TaxFires_Declined
#// JTL_192 In Debt to Crimson Dawn: "When attached unit readies: Exhaust it unless its controller pays 2
#// resources." It fires for ANY ready (when-readies-upgrade-tax family) — including Aurra's inline ready. P2
#// declines to pay, so Aurra is exhausted again.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_166:0:0
WithP2GroundArenaUpgrade: 0:JTL_192

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:3

---

# AurraSing_InDebtToCrimsonDawn_TaxPaid_StaysReady
#// The paying branch of the same tax: P2 pays 2 and Aurra stays ready.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_166:0:0
WithP2GroundArenaUpgrade: 0:JTL_192

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2RESAVAILABLE:1
