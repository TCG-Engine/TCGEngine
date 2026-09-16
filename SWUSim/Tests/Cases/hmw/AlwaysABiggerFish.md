# DefeatsACreature_PlaysOneCostingUpToThreeMore_ForFree
#// HMW_099 Always a Bigger Fish (Event, 2, Vigilance, Innate) — "Defeat a friendly Creature unit. If you
#// do, play a Creature unit that costs up to 3 more than the defeated unit from your hand for free."
#// COVERAGE: offer=Offer_DefeatPool_FriendlyCreaturesOnly + Offer_HandPool_CreatureUnitsWithinTheCap ·
#//           decline=DecliningThePlay_TheDefeatStillHappens (the play from HAND is always declinable —
#//           a hidden zone; the defeat has no "may" and is mandatory) ·
#//           boundary=Offer_HandPool_… (cost 5 in / cost 6 out off a cost-2 defeat) +
#//           TokenBeast_CostsZero_CapIsThree · control=StolenCreature_IsFriendly_GoesToItsOwner ·
#//           reqboundary=RequestBoundaryBeforeThePlay + SameWindow_ParkedTriggersSurviveARequestBoundary
#//           (the parked When Defeated rides the continuation's param) ·
#//           ordering=SameWindow_WhenDefeatedFIRST / SameWindow_WhenPlayedFIRST (USER RULING 2026-09-15) ·
#//           modes=2P,TeamSuns ("a FRIENDLY Creature unit" — a teammate's, per the IBH_095 user ruling
#//           2026-08-25) · TwinSuns=N/A (no player reference)
#// Rulings applied (CR, not preview guesses): "cost" is the PRINTED cost (CR 2.6 — a token is 0); "for
#// free" bypasses every cost modifier including the aspect penalty and the cost-reducing pickers (CR 6.2
#// step 3.d). ⚠ PREVIEW-SET ASSUMPTION: "If you do" is met only when the defeat actually happens.
#// Here: LOF_109 (cost 2) is the only friendly Creature, so the defeat auto-resolves; the cap is 5 and
#// IBH_076 (cost 5, AGGRESSION — off-aspect under bbw) is played with 0 resources left, entering
#// exhausted. Discard: the event + LOF_109.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LOF_109:1:0
WithP1Hand: [HMW_099 IBH_076]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IBH_076
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:2
NOEXTRAACTION

---

# Offer_DefeatPool_FriendlyCreaturesOnly
#// HMW_099 — the DEFEAT pool: friendly units with the Creature trait. Excluded: a friendly NON-Creature
#// (SEC_080) and an ENEMY Creature (P2's LOF_109). Two legal Creatures, so the mandatory choice prompts.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: [LOF_109:1:0 LOF_245:1:0 SEC_080:1:0]
WithP2GroundArena: LOF_109:1:0
WithP1Hand: [HMW_099 IBH_076]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Offer_HandPool_CreatureUnitsWithinTheCap
#// HMW_099 — the PLAY pool, and its boundary pair. The defeated LOF_109 costs 2, so the cap is 5:
#//   IBH_076 cost 5 Creature → IN (exactly at the cap)   · LOF_119 cost 6 Creature → OUT (one over)
#//   SEC_080 cost 2 non-Creature → OUT                     · SHD_080 cost 1 Creature → IN
#// After the event leaves the hand: myHand-0 IBH_076, -1 LOF_119, -2 SEC_080, -3 SHD_080.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LOF_109:1:0
WithP1Hand: [HMW_099 IBH_076 LOF_119 SEC_080 SHD_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-3
P1GROUNDARENACOUNT:0

---

# TokenBeast_CostsZero_CapIsThree
#// HMW_099 — value class: a Beast TOKEN is a Creature with printed cost 0, so the cap is 3. HMW_226 (cost
#// 3) is offered, LOF_162 (cost 4) is not. A MAY-choose still prompts with a lone legal card.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: HMW_T03:1:0
WithP1Hand: [HMW_099 HMW_226 LOF_162]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HASDECISION
P1SELECTABLEEXACT:myHand-0

---

# DecliningThePlay_TheDefeatStillHappens
#// HMW_099 — the play is from the HAND, a hidden zone, so it is always declinable (user ruling
#// 2026-08-15). Declining after the mandatory defeat leaves LOF_109 defeated and IBH_076 in hand; the
#// event's cost is spent regardless.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LOF_109:1:0
WithP1Hand: [HMW_099 IBH_076]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:2
P1NODECISION

---

# NoFriendlyCreature_NothingHappens
#// HMW_099 — no friendly Creature: nothing is defeated ("If you do" fails), so no play is offered even
#// though a legal Creature sits in hand. The friendly NON-Creature is untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: [HMW_099 IBH_076]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# NoLegalCreatureInHand_TheDefeatStillHappens_NoPrompt
#// HMW_099 — the defeat is NOT conditional on a playable Creature existing (only an explicit "If you do"
#// gates, and it gates the PLAY, not the defeat — the Lightspeed Assault ruling). LOF_109 is defeated;
#// the only Creature in hand costs 6 (over the cap of 5), so nothing is offered.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LOF_109:1:0
WithP1Hand: [HMW_099 LOF_119]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# ForFree_BypassesCostReducingPickers_GreaterSarlacc
#// HMW_099 — "for free" bypasses every modifier to the cost (CR 6.2 step 3.d), and HMW_049 Greater
#// Sarlacc's "defeat any number of ready resources … 3 less each" is one — so its picker must NOT be
#// raised. Defeat LOF_119 (cost 6) → cap 9 → Sarlacc (cost 9). P1 holds 3 spare ready resources that a
#// wrongly-raised picker would offer; they stay untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LOF_119:1:0
WithP1Hand: [HMW_099 HMW_049]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_049
P1RESCOUNT:5
P1RESAVAILABLE:3

---

# NestedPlay_BothTriggersResolve_AndTheActionClosesOnce
#// HMW_099 — the real turn structure (no P1OnlyActions). IBH_015 (cost 2, "When Defeated: heal 2 from
#// your base") is defeated; SHD_080 (cost 1, "When Played: heal 1 from your base") is played. Both
#// abilities resolve: base 5 → 2. And a card that PLAYS another card must close its action exactly once:
#// the turn passes to P2 — TURNPLAYER on an ALTERNATING turn is the authority here.
#// ⚠ NOT NOEXTRAACTION in this section: a nested play of a unit WITH an entry trigger has the documented
#// DEFERRED close leg (SWU_TRIGGER_RESUME; SWUSim/docs/action-close-ownership.md), which ATTEMPTS a second
#// close that the gate refuses. Measured identical on the released SHD_129 Timely Intervention playing the
#// same SHD_080 — engine-wide, not this card. The strict NOEXTRAACTION form is on the first section, whose
#// played unit (IBH_076) has no entry trigger.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myBaseDamage:5}
WithActivePlayer: 1
WithP1GroundArena: IBH_015:1:0
WithP1Hand: [HMW_099 SHD_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_080
TURNPLAYER:2

---

# SameWindow_BothTriggersAreOfferedInOnePrompt
#// ★ USER RULING 2026-09-15 — same-window triggers are ORDERABLE. The defeated LOF_064 (When Defeated: may
#// give a Shield to a damaged non-Vehicle unit) and the played LOF_259 (When Played: deal 1 to a ground
#// unit) are both set off by this one event, wait for it (CR 7.6.8), then resolve in the order the player
#// chooses (CR 7.6.9) — so after the hand pick there is ONE ordering prompt holding both. Before the fix the
#// When Defeated sat on a separate queue and always resolved first, with no prompt.
#// Cap: LOF_064 costs 3 → 6; LOF_259 costs 5.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: [LOF_064:1:0 SOR_095:1:1]
WithP2GroundArena: SEC_080:1:0
WithP1Hand: [HMW_099 LOF_259]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# SameWindow_WhenDefeatedFIRST
#// HMW_099 — the mirrored pair that makes the ordering prompt a real choice (a single answer passes just as
#// well against a fixed order relabelled as a prompt). Picking the When Defeated first: the next decision
#// is LOF_064's Shield offer (the damaged SOR_095 is its only target).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: [LOF_064:1:0 SOR_095:1:1]
WithP2GroundArena: SEC_080:1:0
WithP1Hand: [HMW_099 LOF_259]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit
P1SELECTABLEEXACT:myGroundArena-0

---

# SameWindow_WhenPlayedFIRST
#// HMW_099 — the other half: picking the When Played first, the next decision is LOF_259's "deal 1 to a
#// ground unit" (both friendly units and the enemy SEC_080).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: [LOF_064:1:0 SOR_095:1:1]
WithP2GroundArena: SEC_080:1:0
WithP1Hand: [HMW_099 LOF_259]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-1

## EXPECT
P1DECISIONTOOLTIP:Deal_1_to_a_ground_unit
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# NoPlay_TheParkedWhenDefeatedStillResolves_DeclinedByPass
#// HMW_099 — the defeat's triggers are parked while the play is offered, so every NO-PLAY path must flush
#// them. Here the play is declined with the Pass button — the STICKY decline, which skips any continuation
#// not marked to survive it. IBH_015's When Defeated (heal 2 from your base) still resolves: 5 → 3.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: IBH_015:1:0
WithP1Hand: [HMW_099 IBH_076]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1BASEDMG:3
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# NoPlay_TheParkedWhenDefeatedStillResolves_NothingAffordable
#// HMW_099 — the other no-play path: no Creature in hand within the cap (LOF_119 costs 6 > 5), so no play
#// is offered at all, and IBH_015's When Defeated resolves on its own: 5 → 3.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: IBH_015:1:0
WithP1Hand: [HMW_099 LOF_119]

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1NODECISION
P1HANDCOUNT:1

---

# SameWindow_ParkedTriggersSurviveARequestBoundary
#// HMW_099 — the parked When Defeated rides the hand-pick continuation's PARAM, so it must survive a fresh
#// process: boundary before the pick, then both triggers still meet in one prompt and both resolve
#// (IBH_015 heal 2 + SHD_080 heal 1: 5 → 2).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: IBH_015:1:0
WithP1Hand: [HMW_099 SHD_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-1

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:SHD_080

---

# StolenCreature_IsFriendly_GoesToItsOwner
#// HMW_099 — control: a Creature P1 CONTROLS but P2 OWNS is a friendly Creature. It is defeated into its
#// OWNER's discard (P2), its printed cost still sets the cap (IBH_015, 2 → 5), and P1 plays from P1's
#// own hand. P1's discard holds only the event.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArenaControlled: IBH_015:2
WithP1Hand: [HMW_099 IBH_076]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IBH_076
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:IBH_015
P1DISCARDCOUNT:1

---

# TeamSuns_TeammatesCreatureIsFriendly
#// HMW_099 — Team Suns (1+3 vs 2+4): "a friendly Creature unit" includes teammate P3's (user ruling
#// 2026-08-25 on IBH_095's identical wording). P1 controls no Creature; P3's LOF_109 is the only friendly
#// one (so the defeat auto-resolves onto it); both enemies' Creatures are untouched. P1 then plays IBH_076
#// from P1's own hand.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [HMW_099 IBH_076]
WithP3GroundArena: LOF_109:1:0
WithP2GroundArena: LOF_109:1:0
WithP4GroundArena: LOF_109:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IBH_076

---

# TeamSuns_TeammatesProtectedCreature_IsNotProtectedFromAFriend
#// HMW_099 — teammate P3's LOF_109 wears TWI_220 Shadowed Intentions ("can't be … defeated … by ENEMY card
#// abilities"). A teammate is not an enemy, so P1's Bigger Fish defeats it and the play follows. Until
#// 2026-09-15 SWUDefeatUnit read "enemy" as "any other seat", refused the defeat, and (correctly, given a
#// failed defeat) offered no play. Fixed via SWUIsEnemySeat; the released-card guard is in
#// twi/ShadowedIntentions.md (IBH_095 on a teammate, and Vanquish on an ENEMY still refused).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [HMW_099 IBH_076]
WithP3GroundArena: LOF_109:1:0
WithP3GroundArenaUpgrade: 0:TWI_220

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P3GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IBH_076

---

# RequestBoundaryBeforeThePlay
#// HMW_099 — the positive section with a request boundary before the hand pick: the pending picker and
#// its continuation must survive a fresh process.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LOF_109:1:0
WithP1Hand: [HMW_099 IBH_076]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IBH_076
P1HANDCOUNT:0
