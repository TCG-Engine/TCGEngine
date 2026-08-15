# Deployed_Action_PlayVillainyNonUnit
#// LOF_018 Anakin Skywalker (deployed) — Action [use the Force]: play a Villainy non-unit card from
#// your hand, ignoring its aspect penalties. Anakin spends the Force and plays the Villainy event
#// SHD_243 (cost 1); it goes to discard.
#// (Extra answer since 2026-08-14: this "you may play" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: bgw/brk/{
  myLeader:LOF_018;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_018:1:0
WithP1Hand: SHD_243
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# PlayVillainyIgnorePenalty
#// LOF_018 Anakin Skywalker — Action [Exhaust, use the Force]: Play a Villainy non-unit card from your hand,
#// ignoring its aspect penalties. Anakin's deck is Heroism/Vigilance, so LOF_239 (Villainy, cost 2) would
#// normally cost 4 (off-aspect +2). With the penalty ignored, P1 plays it for 2 onto Plo Koon: +2 Experience
#// then 2 damage → 8/10 with 2 damage.
#// (Extra answer since 2026-08-14: this "you may play" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_018;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_239
WithP1Resources: 2
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NOFORCE

---

# PlayVillainyPilot_AsPilot_OnVehicle
#// LOF_018 Anakin Skywalker — a Villainy card can be a UNIT only if it is a Pilot played AS A PILOT (upgrade)
#// on a friendly Vehicle. Iden Versio (JTL_036, Villainy pilot, Piloting cost 3 Vigilance/Villainy) attaches
#// to the friendly TIE Advanced, ignoring aspect penalties: cost is 3 (not 3+2), so 5→2 resources remain.
#// Iden's "when attaches: give a Shield" also fires (SOR_T02 shield token). Force spent, Anakin exhausted.
#// (Extra answer since 2026-08-14: this "you may play" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: JTL_036
WithP1SpaceArena: SOR_231:1:0
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P1HANDCOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_036
P1RESAVAILABLE:2

---

# VillainyPilot_NoVehicle_NotSelectable_UseAnyway
#// LOF_018 Anakin — with no friendly Vehicle in play, the Villainy pilot Iden cannot be played as a pilot,
#// and a Villainy UNIT is never a valid "non-unit" target. So there is nothing to play, but the cost is
#// still paid ("use it anyway"): Anakin exhausts and spends the Force; Iden stays in hand.

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: JTL_036
WithP1Resources: 5

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P1HANDCOUNT:1

---

# Deployed_PlayVillainyPilot_ForceOnly_NoExhaust
#// LOF_018 Anakin (DEPLOYED) — same pilot play, but the deployed action costs only the Force (no exhaust).
#// Iden attaches to the TIE Advanced at cost 3 (aspect ignored); Force spent; the deployed Anakin unit is
#// NOT exhausted by the action.
#// (Extra answer since 2026-08-14: this "you may play" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: JTL_036
WithP1SpaceArena: SOR_231:1:0
WithP1GroundArena: LOF_018:1:0
WithP1Resources: 5

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_036
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:0:READY

---

# Leader_NoForce_AbilityUnavailable
#// LOF_018 Anakin (leader) — FRONT action costs "use the Force". With NO Force token, the leader ability is
#// unavailable: activating it does nothing (no decision, leader stays ready, hand unchanged). Intended: "should
#// do nothing without the Force."

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021;handCardIds:SOR_041,SOR_251;myResources:4}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NODECISION
P1LEADER:READY
P1HANDCOUNT:2

---

# Leader_NoPlayableCard_UseAnyway
#// LOF_018 Anakin (leader) — FRONT with the Force but nothing playable: SOR_041 Power of the Dark Side
#// (Villainy, cost 3) is unaffordable at 2 resources and SOR_251 Confiscate is colorless (not Villainy), so
#// there is no Villainy non-unit to play. The cost is still paid: Anakin exhausts and spends the Force; both
#// cards stay in hand. Intended: "should exhaust and spend the Force if no card can be played."

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021;handCardIds:SOR_041,SOR_251;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P1HANDCOUNT:2

---

# Leader_SelectableExactly_OnlyVillainyNonUnits
#// LOF_018 Anakin (leader) — the play choice offers ONLY Villainy non-unit cards. Hand holds two Villainy
#// events (SOR_041 Power of the Dark Side, SOR_043 Superlaser Blast), a Villainy UNIT (SEC_080 Imperial Dark
#// Trooper) and a Heroism unit (LOF_050 Plo Koon). Only the two events are selectable; the units are
#// excluded. Intended: "should not be allowed to choose heroism cards or villainy units."

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021;handCardIds:SOR_041,SOR_043,SEC_080,LOF_050;myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# Leader_ChooseNothing
#// LOF_018 Anakin (leader) — the play is a "you may": with two playable Villainy events in hand P1 is
#// prompted, then chooses nothing. No card is played and no resources are spent, but Anakin still exhausts
#// and spends the Force. Intended: "should be allowed to choose nothing."

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021;handCardIds:SOR_041,SOR_043;myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:PASS

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P1HANDCOUNT:2
P1RESAVAILABLE:10

---

# Deployed_NoForce_AbilityUnavailable
#// LOF_018 Anakin (DEPLOYED) — the deployed action costs "use the Force". With NO Force token the play
#// ability is unavailable; activating the unit produces no play decision and it stays ready with hand
#// unchanged. Intended (deployed): "should not be able to play a Villainy card from hand without the force."

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021;handCardIds:SOR_041,SOR_251;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_018:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NODECISION
P1HANDCOUNT:2
P1GROUNDARENAUNIT:0:READY

---

# Deployed_ChooseNothing_NotExhausted
#// LOF_018 Anakin (DEPLOYED) — the play is a "you may": with two playable Villainy events in hand, P1 is
#// prompted then chooses nothing. No card is played and no resources spent; the Force is spent but the
#// deployed Anakin stays ready. Intended (deployed): "should be allowed to choose nothing."

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021;handCardIds:SOR_041,SOR_043;myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_018:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:PASS

## EXPECT
P1NOFORCE
P1HANDCOUNT:2
P1RESAVAILABLE:10
P1GROUNDARENAUNIT:0:READY

---

# Leader_PlayVillainyUpgrade_IgnorePenalty
#// LOF_018 front: play a Villainy UPGRADE (SHD_038, cost 2) ignoring aspect penalty. bgw deck covers
#// Vigilance but NOT Villainy → normal cost 4; waived → 2. Attaches to LOF_050 for 2 (0 left).
#// (Extra answer since 2026-08-14: this "you may play" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: bgw/bbk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SHD_038
WithP1Resources: 2
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_038
P1RESAVAILABLE:0

---

# Deployed_NoPlayable_UsesForceNoPlay
#// LOF_018 Anakin (deployed) — CR 6.4.587.c: "use the Force" is the COST, so the Action is usable with the
#// Force even when no Villainy card is playable (only SOR_095, a Command/Heroism unit, in hand). The handler
#// spends the Force (UseTheForce) before checking targets, so the Force is spent and nothing is played.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_018:1:0
WithP1Hand: SOR_095
WithP1Resources: 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:READY

---

# Deployed_SelectableExactly_OnlyVillainyNonUnits
#// LOF_018 Anakin (DEPLOYED) mirror of Leader_SelectableExactly_OnlyVillainyNonUnits — the two sides are
#// dispatched by different code (unitAbilities vs leaderAbilities), so the "Villainy NON-UNIT only" filter
#// has to be proven on each. Hand holds two Villainy events (SOR_041, SOR_043), a Villainy UNIT (SEC_080)
#// and a Heroism unit (LOF_050); only the two events are selectable on the deployed side too.
## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021;handCardIds:SOR_041,SOR_043,SEC_080,LOF_050;myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_018:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# Deployed_PlayVillainyUpgrade_IgnorePenalty
#// LOF_018 Anakin (DEPLOYED) mirror of Leader_PlayVillainyUpgrade_IgnorePenalty — an UPGRADE is a Villainy
#// non-unit card, so the deployed Action plays it too, and the aspect-penalty waiver applies on this side
#// as well. SHD_038 (Villainy, cost 2) under a bgw deck that does NOT cover Villainy would normally cost 4;
#// waived → 2, attaching to Plo Koon with 0 resources left. Anakin stays READY (no exhaust on this side).
#// (Extra answer since 2026-08-14: this "you may play" offer no longer auto-resolves a lone target.)
## GIVEN
CommonSetup: bgw/bbk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_018:1:0
WithP1GroundArena: LOF_050:1:0
WithP1Hand: SHD_038
WithP1Resources: 2
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:LOF_050
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_038
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:READY

---

# Decline_SingleTarget_NoVillainyCardPlayed
#// LOF_018 Anakin Skywalker (leader) — new since 2026-08-14: the "you may play" offer with exactly ONE
#// legal target now prompts instead of auto-resolving, so the lone playable Villainy non-unit can be
#// declined. Hand holds only SHD_243 (Villainy event, cost 1) and P1 has 3 resources, so it IS playable;
#// P1 declines. Nothing is played (hand unchanged, discard empty, resources untouched) but the Action's
#// cost was still paid: Anakin exhausts and the Force is spent.

## GIVEN
CommonSetup: bgw/brk/{myLeader:LOF_018;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SHD_243
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1LEADER:EXHAUSTED
P1NOFORCE
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESAVAILABLE:3
P1NODECISION
