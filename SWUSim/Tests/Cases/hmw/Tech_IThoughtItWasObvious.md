# CombatDefender_DamagedAndSurvives_ExhaustsEnemy
#// COVERAGE: offer=Offer_ExcludesExhausted_SpansBothSidesAndSelf · decline=Decline_NothingIsExhausted
#//           boundary=DamagedAndDefeated_NoTrigger (the "and survives" gate is the only threshold this
#//                    card has; N/N-1 is expressed as survives-vs-dies)
#//           negatives=DamagedAndDefeated_NoTrigger (survives) · ShieldAbsorbsTheHit_NoDamageDealt_NoTrigger
#//                     (dealt damage) · LostAllAbilities_NoTrigger (the ability still exists)
#//           damage-paths=combat defender + combat attacker + ability (non-combat) + indirect — all four
#//                     funnels, since the text says "damage", not "combat damage"
#//           control=ControlChange_NewControllerChoosesAndCanExhaustTheOldOwnersUnit
#//           reqboundary=RequestBoundary_OfferSurvivesTheAnswerBoundary
#//           modes=2P,TwinSuns (the pool is an unqualified "a unit", so it spans EVERY live seat's board
#//                 — TwinSuns_FarSeatUnitIsSelectable cannot pass at two seats)
#//                 TeamSuns=N/A (no friendly/enemy wording — same code path as Twin Suns)
#//
#// HMW_211 Tech, I Thought It Was Obvious (3 cost, 3/5, Cunning/Heroism, Clone)
#//   "When this unit is dealt damage and survives: You may exhaust a unit."
#//
#// P2's SEC_080 (3/3) attacks P1's Tech (3/5). Tech takes 3 and SURVIVES → the reaction offers P1 an
#// exhaust. P1 exhausts P2's ready LAW_124 bystander.
#// ⚠ SEC_080 is DEFEATED by Tech's 3-power counter, so the arena reindexes before P1 answers — the offer
#// must therefore be built POST-cleanup (LAW_124 is at index 0 by then, not 1).

## GIVEN
CommonSetup: yyw/grk
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# CombatAttacker_TookCounterDamageAndSurvives_Triggers
#// The attacker half of the same window: Tech attacks SOR_046 Consular Security Force (3/7), deals 3,
#// and takes 3 counter-damage while surviving → the reaction fires for the ATTACKER too.
#// P1 exhausts its own ready SOR_095 (the pool is unqualified, so a friendly unit is legal).
#// ⚠ Tech is exhausted by its own attack, so it is NOT in the ready-only pool here.

## GIVEN
CommonSetup: yyw/grk
P1OnlyActions: true
WithP1GroundArena: HMW_211:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# DamagedAndDefeated_NoTrigger
#// ⚠ THE NEGATIVE that proves "and survives" is load-bearing. Same board as the positive, but P2's
#// attacker is LAW_124 (4/7) and Tech is pre-damaged to 2, so the 4 damage is lethal (2+4 >= 5).
#// Tech is dealt damage and does NOT survive → NO offer at all.

## GIVEN
CommonSetup: yyw/grk
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1NODECISION
P2NODECISION

---

# ShieldAbsorbsTheHit_NoDamageDealt_NoTrigger
#// ⚠ The second NEGATIVE: a Shield token PREVENTS the damage, so no damage is DEALT and the trigger
#// must not fire. Tech survives either way, which is exactly why this is a different test from the
#// defeat negative above — it isolates "dealt damage" from "survives".
#// LAW_124 (4/7) attacks Tech; the Shield eats the whole hit (DAMAGE:0, SHIELDCOUNT:0) and P2's ready
#// SOR_095 bystander stays READY because no offer was ever made.
#// ⚠ The attacker must SURVIVE Tech's 3-power counter (hence 4/7, not a 3/3) — a dead attacker compacts
#// P2's arena and the bystander's index moves, which reads as a fixture failure rather than the point.
#// ⚠ Tech itself is a ready, legal target, so the pool would NOT have been empty — P1NODECISION is
#// therefore about the trigger not firing, not about there being nothing to exhaust.

## GIVEN
CommonSetup: yyw/grk
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# AbilityDamage_NonCombat_AlsoTriggers
#// The card says "dealt damage", NOT "dealt combat damage" — so NON-COMBAT damage triggers it too.
#// Backed by the official ruling on the same wording (Jabba the Hutt, Wonderful Human Being, 10/31/2025:
#// "Jabba's ability triggers when a friendly unit is dealt combat damage or non-combat damage").
#// Contrast SHD_250 Tarfful, which prints "dealt COMBAT damage" and is combat-only.
#// P2 plays Open Fire (SOR_172, deal 4) at Tech; Tech survives at 4 of 5 and exhausts P2's LAW_124.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:4;theirhandCardIds:SOR_172}
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# IndirectDamage_AlsoTriggers
#// The THIRD damage funnel. Indirect damage writes ->Damage directly and bypasses SWUDealDamageToUnit
#// entirely, so it needs its own observer call — a card that only tested combat + ability damage would
#// be blind to a whole damage KIND (the JTL_177 Stay on Target shape).
#// P2 plays Torpedo Barrage (JTL_234, 5 indirect to a player); P1 assigns 2 to Tech and 3 to their base.
#// Tech survives at 2 of 5 → the offer fires.

## GIVEN
CommonSetup: yyw/yyk/{theirResources:4;theirhandCardIds:JTL_234}
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:myGroundArena-0:2,myBase-0:3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:2
P1BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# Decline_NothingIsExhausted
#// "You MAY exhaust a unit" — the decline branch. Same board as the opening positive; P1 answers `-`
#// and P2's LAW_124 stays READY. (`-` is the MZMAYCHOOSE decline; `NO` is for YESNO.)

## GIVEN
CommonSetup: yyw/grk
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:READY

---

# Offer_ExcludesExhausted_SpansBothSidesAndSelf
#// ⚠ THE OFFER CELL — answering a target proves the branch, never the pool.
#// Three things at once, on one board: (a) an already-EXHAUSTED unit is a zero-effect target and must be
#// unselectable (the engine's exhaust-only-ready convention, cf. SEC_069 / SEC_015 / SHD_201);
#// (b) "a unit" carries no controller word, so the pool spans BOTH sides; (c) it carries no "another",
#// so Tech ITSELF is a legal target.
#// Tech is the DEFENDER here, so it is still ready and appears in its OWN offer.
#// P2's board is [LAW_124 attacker, SOR_095 exhausted, SEC_080 ready] — the attacker is exhausted BY
#// attacking and SOR_095 was seeded exhausted, so exactly one enemy (SEC_080) is offerable. That is
#// two independent reasons for exclusion, so a missing ready-filter reds this in two places.
#// ⚠ The attacker is LAW_124 (4/7) so it SURVIVES Tech's 3-power counter — a 3/3 attacker dies and
#// compacts P2's arena, moving every index the assertion names.

## GIVEN
CommonSetup: yyw/grk
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-2

---

# NoReadyUnitAnywhere_NoPromptAtAll
#// A fizzle-only optional must not be offered: with every unit on the table already exhausted there is
#// nothing an exhaust could do, so no prompt is raised.
#// ⚠ The board deliberately still CONTAINS units — an empty board would prove nothing about the ready
#// filter. Tech is dealt non-combat damage while exhausted, and P2's only other unit is exhausted too.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:4;theirhandCardIds:SOR_172}
WithActivePlayer: 2
WithP1GroundArena: HMW_211:0:0
WithP2GroundArena: LAW_124:0:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
P2NODECISION

---

# TwoDamageInstances_TriggersEachTime
#// ⚠ There is NO "use this ability only once each round" clause (contrast ASH_032 Rancor Keeper and
#// SEC_002 Jabba, which both have one) — so every qualifying damage instance gets its own offer.
#// Two 1/1 Battle Droid tokens attack Tech in turn; Tech takes 1 each time (2 total, survives) and
#// exhausts one of P2's two LAW_124s per trigger. Both end EXHAUSTED, which a once-per-round
#// implementation could not produce.
#// ⚠ Each attacking Droid is DEFEATED by Tech's 3-power counter, so P2's ground compacts between the
#// two attacks — the second offer must be built against the compacted board.

## GIVEN
CommonSetup: yyw/grk
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-1
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:LAW_124
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# ControlChange_NewControllerChoosesAndCanExhaustTheOldOwnersUnit
#// "YOU may exhaust a unit" resolves for whoever CONTROLS Tech, not whoever owns it.
#// P2 plays Change of Heart (SOR_224) to take control of P1's Tech, then Open Fire (SOR_172) damages it.
#// The offer must go to P2 — and P2 must be able to exhaust P1's unit with it.
#// ⚠ If the reaction were queued for the OWNER, P1 would get the prompt and SOR_046 would stay ready.

## GIVEN
CommonSetup: yyw/yyk/{theirResources:12;theirhandCardIds:SOR_224}
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP1GroundArena: SOR_046:1:0
WithP2Hand: SOR_172

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:HMW_211
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# TwinSuns_FarSeatUnitIsSelectable
#// ⚠ CANNOT PASS AT TWO SEATS. "A unit" names no player, so the pool is the WHOLE TABLE — every live
#// seat, not just the one opponent in view. Built on the shared SWUOfferUnitTarget pool (team + their),
#// where ZoneSearch's `their` fans out across all live opponents; a hand-rolled `my`+`their` pair or an
#// OtherPlayer() shortcut would offer only seats 1 and 2 and this section would red.
#// Seat 1's Tech is damaged by seat 2, and seat 3's ready unit must appear in the offer.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:4;theirhandCardIds:SOR_172}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1GroundArena: HMW_211:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:p1GroundArena-0
- P1>Drain

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# RequestBoundary_OfferSurvivesTheAnswerBoundary
#// ⚠ THE REQUEST-BOUNDARY CELL — the one axis a later validation pass provably never backfills.
#// The offer is queued mid-combat and answered in a LATER request, in a fresh process. Nothing may be
#// held in memory across it: the reaction rides the CUSTOM decision's own Param and the pool is built
#// at drain time, so the boundary is safe by construction — this section is what proves it stays that
#// way. Byte-identical to the opening positive apart from the inserted boundary.

## GIVEN
CommonSetup: yyw/grk
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>Drain
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# LostAllAbilities_NoTrigger
#// The THIRD negative: this is Tech's OWN ability, so a Tech that has lost all abilities must not react
#// to being damaged. Force Lightning (SOR_138) does both halves in one card — "Choose a unit. It loses
#// all abilities for this phase. Then, if you control a Force unit, pay any number of resources and deal
#// 2 damage to the chosen unit for each resource paid this way." P2 controls LOF_230 (a Force unit) and
#// pays 1, so Tech is blanked AND dealt 2, and survives at 2 of 5 — every precondition of the trigger is
#// met except the ability still existing.
#// ⚠ The discriminator is P1NODECISION: P1's ready SOR_095 is a perfectly legal exhaust target, so a
#// missing LostAbilities gate would raise a real offer here.

## GIVEN
CommonSetup: yyw/rrk/{theirResources:4;theirhandCardIds:SOR_138}
WithActivePlayer: 2
WithP1GroundArena: HMW_211:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_230:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:1
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_211
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1NODECISION
