# SplitDamage_FiresTheWhenDamagedObserver
#// COVERAGE: offer=N/A (this file guards a shared DAMAGE FUNNEL, not a card's target pool — each
#//           section drives an existing card's own already-covered offer) · decline=N/A ·
#//           boundary=SurvivesGate_DefeatedBySplitShare_SurvivalGatedObserverStaysSilent vs
#//                    NoSurvivesClause_ObserverFiresEvenWhenItsOwnShareDefeatsIt (the $survived pair) ·
#//           control=N/A (the observer is resolved by the DAMAGED unit's own controller, which these
#//                    sections already exercise as the non-acting player) ·
#//           reqboundary=N/A (the split is one MZSPLITASSIGN answer; the observers it fires carry their
#//                    own boundary guards in their own card files) · modes=2P only (a damage funnel
#//                    names no player and no friendly/enemy scope)
#//
#// ★ ENGINE BUG, fixed 2026-08-27. `_SWUApplySplitHits` — the applier behind EVERY divided-damage
#// effect (MZSPLITASSIGN → SPLIT_DAMAGE → SWUDealSplitDamage) — never called `_SWUOnUnitDamaged`, so the
#// whole "when this unit is dealt damage" observer family was BLIND to divided damage:
#// SEC_143 The Elite Squad, HMW_211 Tech, SEC_002 Jabba, SHD_250 Tarfful, ASH_032 Rancor Keeper and the
#// ASH_188 damaged-this-phase marker. The INDIRECT path had been given its own explicit call for exactly
#// this reason (with a comment saying so); the split path was simply never given one.
#// Same shape as the JTL_177 Stay on Target bug: one trigger, several damage funnels, one funnel missed.
#//
#// Driver: SHD_177 Vambrace Flamethrower grants the host "On Attack: You may deal 3 damage divided as
#// you choose among enemy ground units." All 3 land on P2's SEC_143 (6/8, survives), whose own reaction
#// — "When damage is dealt to this unit: you may deal 2 damage to another unique unit" — then hits the
#// only other unique unit on the table, P2's own LOF_093.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SEC_143:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P2>Drain
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_143
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:LOF_093
P2GROUNDARENAUNIT:1:DAMAGE:2
P2BASEDMG:4

---

# SplitDamage_FiresASurvivalGatedObserverToo
#// The other half of `_SWUOnUnitDamaged`: an observer BELOW its `$survived` gate. HMW_211 Tech
#// ("When this unit is dealt damage and survives: You may exhaust a unit") takes all 3 of the split,
#// survives at 3 of 5, and its controller exhausts a ready unit.
#// ⚠ Tech's own file proves this through combat, ability and INDIRECT damage; divided damage is the
#// fourth funnel and only this file reaches it.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: HMW_211:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P2>Drain
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_211
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:LAW_124
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# SurvivesGate_DefeatedBySplitShare_SurvivalGatedObserverStaysSilent
#// ⚠ THE `$survived` PAIR, half one. The observers must be fired AFTER the defeat sweep, not inside the
#// damage loop — otherwise every unit still looks alive at the moment its share lands and a
#// survival-gated reaction fires on a unit the same effect just killed.
#// Tech is pre-damaged to 2, so its 3-damage share is exactly lethal. It must NOT get its exhaust offer,
#// and P2's ready LAW_124 — a perfectly legal target had the offer been made — must stay READY.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: HMW_211:1:2
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P2>Drain

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:READY
P1NODECISION
P2NODECISION

---

# NoSurvivesClause_ObserverFiresEvenWhenItsOwnShareDefeatsIt
#// ⚠ THE `$survived` PAIR, half two — and the reason the observer call must pass a computed `$survived`
#// rather than simply being skipped for a dead unit. SEC_143 has NO "and survives" clause, so it sits
#// ABOVE that gate in `_SWUOnUnitDamaged` and fires even when this very damage defeats it (its target is
#// ANOTHER unit, so Elite Squad being gone is fine — and its offer is built by a queued CUSTOM that runs
#// post-cleanup for exactly that reason).
#// SEC_143 is pre-damaged to 6, so its 3-damage share (8 HP) is lethal; LOF_093 still takes the 2.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SEC_143:1:6
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3
- P2>Drain
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_093
P2GROUNDARENAUNIT:0:DAMAGE:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_143
