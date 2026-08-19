# Front_HeroismDied_PlaysVillainyFromResources_ThenResourcesACard
#// HMW_017 Osha - Haunted by her Past (Cunning/Heroism, Force, LEADER, cost 6, 5/6 Ground, unique)
#// FRONT: "Action [Exhaust]: If a friendly Heroism unit was defeated this phase, play a Villainy unit
#//   from your resources, ignoring its Villainy aspect penalties. If you do so, you may resource a card
#//   from your hand. / Epic Action: If you control 6 or more resources, deploy this leader."
#// DEPLOYED: "Saboteur / Action: Play a Villainy unit from your resources, ignoring its Villainy aspect
#//   penalties. If you do, you may resource a card from your hand."
#// COVERAGE (front): offer=Front_OfferIsVillainyUNITSOnly · negative=Front_NoHeroismDeath_SoftPass +
#//   Front_FriendlyVillainyDeathDoesNotCount + Front_EnemyHeroismDeathDoesNotCount ·
#//   boundary=Front_WaiverIsVillainyPipsOnly_PlaysAtFive / _BlockedAtFour (a PAIR, because it pins the
#//   waiver as PARTIAL - with no waiver neither plays, with a full waiver both do) ·
#//   decline=Front_ResourceFromHand_Declined · reqboundary=Front_RequestBoundary_AcrossTheResourcePick ·
#//   control=N/A - a leader is untakeable (every take-control effect reads "non-leader unit") and the
#//   ability touches only the controller's OWN resources and hand, so no stolen unit interacts with it.
#// COVERAGE (deployed): Deployed_NoHeroismDeathRequired (the asymmetry) · Deployed_ActionDoesNotExhaust
#//   (usable twice in one phase) · Deployed_HasSaboteur · Deployed_RequestBoundary.
#// ⚠ THE SIDES DIFFER IN TWO WAYS AND ONE SHARED HELPER WOULD FLATTEN BOTH: the front carries the
#//   Heroism-death CONDITION and an [Exhaust] cost; the deployed side carries NEITHER.
#// ⚠ The condition is NOT an affordability gate. It sits after the colon, OUTSIDE the [Exhaust] bracket,
#//   so it is an EFFECT condition: the Action is always offered, and with no qualifying death it exhausts
#//   Osha and does nothing. Putting it in SWULeaderActionAffordable would make the action vanish instead
#//   of resolving to nothing (the TS26_02 Anakin lesson).
#// Fixture arithmetic: PlayerAspects DOES include an UNDEPLOYED leader (it loops the Leader zone regardless
#// of Deployed), so provided = base Cunning + Osha's Cunning,Heroism. Mae HMW_055 costs 3, pips
#// Cunning/Vigilance/Villainy: Cunning matched, Vigilance unmatched (+2), Villainy unmatched but WAIVED
#// -> effective 5. (An earlier draft of this comment said an undeployed leader contributes nothing; that
#// is WRONG, and it only happened to give the right number here because the base is Cunning too.) Battlefield Marine (Command/Heroism) is the
#// Heroism unit that dies to set the condition. TWO affordable Villainy units are seeded so the pick is a
#// REAL choice (a lone legal target auto-resolves via PASSPARAMETER and there is nothing to answer);
#// answering myResources-1 must put the DARK TROOPER into play and leave Mae sitting in resources.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,1:SEC_080:1,8:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1
- P1>AnswerDecision:myHand-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1HANDCOUNT:0
P1NODECISION

---

# Front_NoHeroismDeath_SoftPass_LeaderStillExhausts
#// HMW_017 front - the condition negative, and the ruling that goes with it. Nothing has been defeated,
#// so the ability resolves to nothing: no play, no resource offer, no prompt at all - but Osha IS
#// exhausted, because [Exhaust] is the COST and the condition is an EFFECT. An implementation that
#// gates this in SWULeaderActionAffordable leaves her READY, which is the assertion that separates them.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,6:SOR_046:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESCOUNT:7
P1NODECISION

---

# Front_FriendlyVillainyDeathDoesNotCount
#// HMW_017 front - "a friendly HEROISM unit". A friendly Villainy unit trading is a friendly DEATH but
#// not a Heroism one, so the gate stays shut. Without this section an implementation reading the generic
#// SWU_FRIENDLY_DEFEATED flag (which every defeat sets) passes the happy path unchanged.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,6:SOR_046:1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1NODECISION

---

# Front_EnemyHeroismDeathDoesNotCount
#// HMW_017 front - "a FRIENDLY Heroism unit". P1's Villainy trooper trades with P2's Heroism Marine, so
#// a Heroism unit died this phase but it was the OPPONENT's. The flag is keyed to the defeated unit's
#// controller, so P1's Osha must not fire.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,6:SOR_046:1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1NODECISION

---

# Front_OfferIsVillainyUNITSOnly
#// HMW_017 front - the pool, left PENDING. "a VILLAINY UNIT" is two filters at once, and the resource
#// zone is seeded to break both: Mae (Villainy UNIT, legal), Dark Trooper (Villainy UNIT, legal),
#// Consular Security Force (a unit, but HEROISM - excluded by aspect) and Confiscate (Villainy-free
#// EVENT - excluded by type). Two legal targets so nothing auto-resolves.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,1:SEC_080:1,1:SOR_046:1,1:SOR_251:1,6:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myResources-0&myResources-1
P1HASDECISION

---

# Front_WaiverIsVillainyPipsOnly_PlaysAtFive
#// HMW_017 front - the waiver, half one of the pair. Mae is Cunning/Vigilance/Villainy against a Cunning
#// base and an undeployed (aspect-less) leader: Cunning matches, Vigilance costs +2, Villainy is waived.
#// Effective 5, and FIVE resources total is exactly enough: Mae exhausts her own slot toward her cost
#// (CR 8.22.e) and the other four cover the rest. ⚠ This section asserted SIX before bug #976 - the
#// affordability gate was subtracting Mae's own ready slot while the payment path spent it.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,4:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_055

---

# Front_WaiverIsVillainyPipsOnly_BlockedAtFour
#// HMW_017 front - the other half, one resource short. Mae is not affordable and so is not offered; the
#// arena stays empty and Osha exhausts for nothing. This is what makes the pair diagnostic: with NO
#// waiver Mae would cost 7 and fail BOTH sections, and with a FULL waiver she would cost 3 and pass
#// both. Only a Villainy-pips-only waiver produces plays-at-five / blocked-at-four.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,3:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# Front_ResourceFromHand_Declined
#// HMW_017 front - the decline branch of "you MAY resource a card from your hand". The unit is played,
#// the offer appears, and refusing it leaves the hand and the resource count exactly where the play left
#// them (7 resources: 6 remaining after Mae left the zone, plus... no - Mae LEFT the zone to enter play,
#// so 6 remain and nothing is added). Asserting the resource count as well as the hand is what proves
#// the decline did not quietly resource something anyway.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,6:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_055
P1HANDCOUNT:1
P1RESCOUNT:6
P1NODECISION

---

# Front_NoVillainyUnitInResources_NoPlay_AndNoResourceOffer
#// HMW_017 front - "If you do so" is a real gate. With no Villainy unit among the resources there is
#// nothing to play, so the follow-up resource offer must NOT appear either: the hand is untouched and no
#// decision is pending. A rider queued unconditionally alongside the play shows up here.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESCOUNT:6
P1NODECISION

---

# Front_ResourcedCardEntersExhausted
#// HMW_017 front - "resource a card from your hand" with NO "and ready it" rider (contrast TS26_12
#// Sundari Palace, which says so explicitly), so the new resource enters EXHAUSTED: 7 resources after
#// the play (6 left + the one from hand) of which 2 are ready. Were it entering READY it would be 3, so
#// this number is what pins the status.
#// ⚠ The 2 is worth understanding rather than pattern-matching: 7 ready to start, Mae costs 5, and the
#// engine lets a card played OUT OF the resource zone exhaust ITSELF toward its own cost (the CR 8.22.e
#// rule the Smuggle path documents) - so 4 other resources pay the rest and 2 survive ready. Note this
#// is one MORE than the affordability GATE assumes: that gate subtracts the card's own ready slot
#// (bug #955 / ASH_001), so it is deliberately one stricter than the payment actually needs. That
#// asymmetry is pre-existing engine behaviour, not something this card introduces.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,6:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1RESCOUNT:7
P1RESAVAILABLE:2
P1HANDCOUNT:0

---

# Front_RequestBoundary_AcrossTheResourcePick
#// HMW_017 front - the request-boundary cell, and a real one: the Villainy-pip waiver has to be applied
#// again when the PLAY happens, in a different request from the one that built the affordable-target
#// offer. A waiver flag set when the offer was raised and read when the answer arrives is gone in
#// production, and Mae would be charged the full 7 and silently fail to enter play.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,5:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_055

---

# Deployed_NoHeroismDeathRequired
#// HMW_017 DEPLOYED - the asymmetry, and the single most important section on this card. The deployed
#// Action prints NO condition: nothing has been defeated this phase and it still plays Mae. An
#// implementation that routes both sides through one helper carrying the front's Heroism-death gate
#// does nothing here.
#// Deployed via a real DeployLeader (Epic threshold = the printed cost, 6 resources) so the deploy ->
#// unit-action dispatch is exercised, not just the closure.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,6:SOR_046:1

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_055

---

# Deployed_ActionDoesNotExhaustTheLeaderUnit_AndRepeats
#// HMW_017 DEPLOYED - the other half of the asymmetry: the deployed Action prints no bracket at all, so
#// unlike the front's [Exhaust] it costs nothing and the leader unit stays READY. Proven the only way
#// that really settles it - by using it TWICE in one phase, playing both Villainy units.
#// ⚠ FLAGGED FOR REVIEW: a bare "Action:" with no cost is unusual. This encodes the preview data as
#// transcribed in CardMocks.php; if the printed card actually reads "Action [Exhaust]:" this section and
#// the cost kind are what need changing, nothing else.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,1:SEC_080:1,10:SOR_046:1

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myResources-0
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:READY

---

# Deployed_HasSaboteur
#// HMW_017 DEPLOYED - Saboteur is printed on the deployed side only and is auto-derived by the keyword
#// generator from deployTextData, so this is a guard rather than new work: it fails loudly if a regen
#// ever stops picking it up. The UNDEPLOYED leader has no keywords to speak of, so the assertion is
#// meaningful only against the arena unit.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# Deployed_ResourceFromHand_AndRequestBoundary
#// HMW_017 DEPLOYED - the "If you do, you may resource a card from your hand" rider on the deployed
#// side, exercised across a request boundary. The deployed side must carry the rider too; testing it
#// only on the front would let a deployed implementation drop it silently.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,6:SOR_046:1

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_055
P1HANDCOUNT:0
P1NODECISION

---

# Bug976_MaeAtExactlyHerCost_IsOffered
#// BUG #976 (game 3329) — "Osha leader ability not working even though Yaddle was defeated this phase."
#// The real board: base LOF_021 Shadowed Undercity (Vigilance) + Osha undeployed (Cunning, Heroism), so
#// PlayerAspects = [Vigilance, Cunning, Heroism]. Mae HMW_055 (Cunning/Vigilance/Villainy, cost 3) matches
#// Cunning and Vigilance, and her Villainy pip is waived by Osha — so she costs EXACTLY 3. The player had
#// exactly 3 ready resources, Mae among them, and the ability silently did nothing.
#// ROOT CAUSE: the affordability gate subtracts the played card's OWN ready slot (`cost > capacity -
#// selfReady`), but the PAYMENT path lets that slot pay (CR 8.22.e — a card played out of the resource
#// zone may exhaust itself toward its own cost, which Front_ResourcedCardEntersExhausted measures). So at
#// cost == capacity the card is never offered even though the engine would happily charge it. For a READY
#// resource `cost > capacity - 1` is just `cost >= capacity`, i.e. the pre-bug-#955 gate — that fix only
#// ever helped the EXHAUSTED case and left this one untouched.
#// Yaddle dies by attacking into a 4/7 body, which is what sets SWU_FRIENDLY_HEROISM_DEFEATED.
#// ⚠ The base is the PLAIN Vigilance one, not the reported board's LOF_021 Shadowed Undercity. Same
#// aspect, so Mae's cost is identical (3) — but LOF_021 reads "When a friendly FORCE unit attacks:
#// create your Force token", and Yaddle is a Force unit, so its trigger jumps ahead of the attack-target
#// picker; declareAttack only injects when the queue head is `Choose_an_attack_target`, so the attack
#// stalled and Yaddle never died. That is a harness/fixture interaction, not part of this bug.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,2:SOR_046:1
WithP1GroundArena: LOF_045:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_055
P1LEADER:EXHAUSTED

---

# Bug976_Control_OneSpareResource_AlreadyWorked
#// BUG #976 control — the SAME board with ONE more resource. Here the buggy gate is satisfied
#// (3 <= 4 - 1), so Mae was always offered. This isolates the defect to the boundary: the aspect
#// waiver, the Villainy-unit filter and the play itself were all working, and only cost == capacity
#// was wrong. It passes BEFORE the fix and must keep passing after.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,3:SOR_046:1
WithP1GroundArena: LOF_045:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_055

---

# Bug976b_ResourceRiderPromptsBeforeAnyEntryTrigger
#// BUG #976b — prompt ORDERING. Per CR 522.e / 7.6.8 and the standing project ruling (HMW_043 Darth
#// Vader), a card played MID-ABILITY holds its entry triggers until the OUTER ability — RIDER INCLUDED —
#// has finished resolving. So Osha's "you may resource a card from your hand" must be asked BEFORE any
#// of Mae's triggers, including the "choose a trigger to resolve" ORDERING prompt.
#// Mae has Shielded AND Ambush, so with an enemy unit still alive she has TWO entry triggers and the
#// ordering MZCHOOSE appears — which is exactly what was jumping the queue.
#// ROOT CAUSE: HMW_043's rider runs INLINE, so it lands ahead of the queued triggers. Osha's rider is a
#// QUEUED decision, and it was queued at the same block as the entry triggers ActivateCard had already
#// queued — AddDecision inserts before the first HIGHER block and otherwise appends, so same-block meant
#// last. The rider is now queued at block 0, ahead of the block-1 trigger flush.
#// ⚠ This is why the original happy-path section never caught it: there, the trade left P2's board EMPTY,
#// so Mae's Ambush added no trigger at all and Shielded alone raised no ordering prompt.
#// The decision is deliberately left PENDING so the assertion reads WHICH prompt is up.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,3:SOR_046:1
WithP1GroundArena: LOF_045:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1DECISIONTOOLTIP:Choose_a_card_to_resource
P1HASDECISION

---

# Bug976b_TriggersStillResolveAfterTheRider
#// BUG #976b, the other half — an ordering fix's own failure mode is quieter than the bug it fixes, so
#// this drives the WHOLE chain to the end and proves nothing was dropped by moving the rider ahead of
#// the trigger flush. Order: Osha's resource rider (block 0) -> Mae's trigger-ordering choose -> the
#// triggers themselves. The card is resourced (hand 0, and it enters EXHAUSTED so only 1 of 4 resources
#// is ready), and Mae still ends up with her Shielded token, i.e. the entry triggers were deferred and
#// not discarded.
#// Resource arithmetic: 4 ready to start, Mae costs 3 and self-pays one slot, so 3 remain (1 ready) and
#// the resourced hand card makes 4 with that same 1 ready.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,3:SOR_046:1
WithP1GroundArena: LOF_045:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1HANDCOUNT:0
P1RESCOUNT:4
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_055
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Front_UnaffordableVillainyUnit_SoftPasses
#// HMW_017 front - the affordability negative on the bug-#976 board, asserted as a SOFT PASS on STATE.
#// The Heroism-death condition is TRUE (Yaddle traded), so affordability is the ONLY thing stopping this
#// ability - which is what makes it a clean isolation of the gate from the condition.
#// TWI_231 Dwarf Spider Droid costs 4 and its ONLY pip is Villainy, which Osha waives - so its effective
#// cost is exactly 4 (not 6), against a payment capacity of 3. Unaffordable, so it is never offered.
#// ⚠ Assert the STATE, not merely "nothing visible happened" (the LAW_257 fizzle-only discipline):
#//   • the leader IS exhausted - [Exhaust] is the COST and is paid even when the effect does nothing;
#//   • NO resources were spent and none were exhausted - a fizzle must never charge;
#//   • the Droid is still sitting in resources;
#//   • the hand is untouched, i.e. the "if you do so" rider never fired off a play that did not happen;
#//   • and nothing is left pending.
#// ⚠ WHAT THIS SECTION CANNOT PROVE, measured by mutation: loosening the gate leaves it GREEN. With a
#// looser gate the Droid is offered, auto-resolves (it is the only candidate), and the play then fails
#// on payment - producing a byte-identical end state. Two mechanisms, one observable. So this guards the
#// STATE only; the sibling Front_UnaffordableVillainyUnit_IsExcludedFromTheOffer is what pins the POOL.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:TWI_231:1,2:SOR_046:1
WithP1GroundArena: LOF_045:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:3
P1HANDCOUNT:1
P1NODECISION

---

# Front_UnaffordableVillainyUnit_Companion_AffordableAtFour
#// HMW_017 front - the boundary partner for the section above, and the reason that one is diagnostic
#// rather than merely descriptive. Identical board with ONE more resource: capacity 4 now covers the
#// Droid's waived cost of 4 and it plays. Without this, "blocked at 3" would hold just as well if the
#// Villainy waiver were NOT being applied (cost 6) - this pins the cost at exactly 4.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 1:TWI_231:1,3:SOR_046:1
WithP1GroundArena: LOF_045:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_231
P1HANDCOUNT:0

---

# Front_UnaffordableVillainyUnit_IsExcludedFromTheOffer
#// HMW_017 front - the load-bearing half of the unaffordable case: the POOL, not the end state. Its
#// sibling soft-pass section survives a loosened gate (an unaffordable card that is offered, auto-
#// resolves and then fails to pay looks identical from outside), so the exclusion has to be asserted
#// where it actually happens - in the offer.
#// Resources are two Mae (cost 3 each after the Villainy waiver) plus one Dwarf Spider Droid (cost 4),
#// three resources total so capacity is exactly 3. Both Mae are affordable and the Droid is not, which
#// gives a REAL offer of two - a single legal target would auto-resolve and leave nothing to inspect.
#// Under a loosened gate the Droid joins the pool and this goes red; under a lost Villainy waiver every
#// candidate becomes unaffordable and it goes red the other way.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP1Resources: 2:HMW_055:1,1:TWI_231:1
WithP1GroundArena: LOF_045:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myResources-0&myResources-1
P1HASDECISION
