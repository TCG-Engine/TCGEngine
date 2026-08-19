# Front_HeroismDied_PlaysVillainyFromResources_ThenResourcesACard
#// HMW_017 Osha - Haunted by her Past (Cunning/Heroism, Force, LEADER, cost 6, 5/6 Ground, unique)
#// FRONT: "Action [Exhaust]: If a friendly Heroism unit was defeated this phase, play a Villainy unit
#//   from your resources, ignoring its Villainy aspect penalties. If you do so, you may resource a card
#//   from your hand. / Epic Action: If you control 6 or more resources, deploy this leader."
#// DEPLOYED: "Saboteur / Action: Play a Villainy unit from your resources, ignoring its Villainy aspect
#//   penalties. If you do, you may resource a card from your hand."
#// COVERAGE (front): offer=Front_OfferIsVillainyUNITSOnly · negative=Front_NoHeroismDeath_SoftPass +
#//   Front_FriendlyVillainyDeathDoesNotCount + Front_EnemyHeroismDeathDoesNotCount ·
#//   boundary=Front_WaiverIsVillainyPipsOnly_PlaysAtSix / _BlockedAtFive (a PAIR, because it pins the
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
#// Fixture arithmetic: an UNDEPLOYED leader contributes NO aspects, so provided = the BASE's alone
#// (Cunning). Mae HMW_055 costs 3, pips Cunning/Vigilance/Villainy: Cunning matched, Vigilance unmatched
#// (+2), Villainy unmatched but WAIVED -> effective 5. Battlefield Marine (Command/Heroism) is the
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

# Front_WaiverIsVillainyPipsOnly_PlaysAtSix
#// HMW_017 front - the waiver, half one of the pair. Mae is Cunning/Vigilance/Villainy against a Cunning
#// base and an undeployed (aspect-less) leader: Cunning matches, Vigilance costs +2, Villainy is waived.
#// Effective 5. Six resources total - Mae's own slot plus five - is exactly enough, and she plays.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,5:SOR_046:1
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

# Front_WaiverIsVillainyPipsOnly_BlockedAtFive
#// HMW_017 front - the other half, one resource short. Mae is not affordable and so is not offered; the
#// arena stays empty and Osha exhausts for nothing. This is what makes the pair diagnostic: with NO
#// waiver Mae would cost 7 and fail BOTH sections, and with a FULL waiver she would cost 3 and pass
#// both. Only a Villainy-pips-only waiver produces plays-at-six / blocked-at-five.

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,4:SOR_046:1
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
