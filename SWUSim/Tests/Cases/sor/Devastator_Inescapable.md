# DealsDamageEqualResources
#// SOR_090 Devastator (cost 10) — When Played: you may deal damage to a unit equal to
#// the number of resources you control. P1 controls 10 resources, so the chosen enemy
#// (Consular Security Force, 7 HP) takes 10 and is defeated.
#// COVERAGE: offer=WhenPlayed_OfferSpansEVERYUnit_IncludingITSELFAndALeaderUnit (four units across
#//           both sides and both arenas, plus a deployed leader unit, decision left pending and
#//           P1SELECTABLEEXACT asserting the exact pool), with WhenPlayed_MayTargetITSELF and
#//           WhenPlayed_MayTargetAFRIENDLYUnit resolving the two branches a defensive self- or
#//           friendly-exclusion would have removed · reqboundary=SimulateRequestBoundary_TargetPickAnd
#//           TheResourceCountSurvive (the damage amount is baked into the queued continuation before
#//           the boundary) · control=ControlTakenDevastator_OverwhelmSpillsToTHEOPPONENTOfIts
#//           Controller (owner differs from controller; "the opponent's base" is resolved from the
#//           CONTROLLER) · boundary=DealsDamageEqualResources (10 resources -> 10 damage) vs
#//           WhenPlayed_CountsRESOURCESCONTROLLED_TwelveMeansTwelve (12 -> 12, with only 2 of them
#//           READY, which also rules out a ready-only count); on the Overwhelm clause,
#//           Overwhelm_ExcessSpillsToTheEnemyBase (9 spilled) vs Overwhelm_ShieldPrevents_NoSpillAtAll
#//           (0) vs WhenPlayed_DamageIsNotCombat_OverwhelmDoesNOTSpillIt (0, same numbers, non-combat
#//           damage) · decline=WhenPlayed_Decline_NoDamageDealt ("you MAY deal damage"; Sentinel and
#//           Overwhelm are keywords with no branch to decline).
#// ⚠ The zero-resource edge of the When Played ("if you control no resources, no offer") is NOT
#//    encodable here: the ability only fires on PLAY and Devastator costs 10, so a player who reaches
#//    the trigger necessarily controls at least 10 resources. It would need a put-into-play effect.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# BothKeywordsArePrinted_AndTheStatsAreTenTen
#// SOR_090 Devastator — the static half of the card: Sentinel AND Overwhelm on a 10/10 Space unit.
#// Cheap, but it is the section that separates "the keyword is absent" from "the keyword is present and
#// its behaviour is wrong" in every functional section below, and it pins the power/HP the Overwhelm
#// arithmetic is measured against.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithP1SpaceArena: SOR_090:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:POWER:10
P1SPACEARENAUNIT:0:HP:10

---

# Sentinel_AnAttackDeclaredAtTheBaseIsRedirectedOntoDevastator
#// SOR_090 Devastator — Sentinel: "Units in this arena can't attack your non-Sentinel units or your
#// base." P2's TIE/ln Fighter (2/1) declares an attack on P1's BASE while Devastator sits in the same
#// arena. The declaration is not legal, so it resolves against the Sentinel instead: P1's base takes
#// NOTHING, Devastator takes the TIE's 2, and Devastator's 10 counter-damage defeats the TIE outright.
#// P1's other space unit (Alliance X-Wing) is untouched — the redirect goes to the Sentinel, not to
#// "some unit".

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: [SOR_090:1:0 SOR_237:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:1:DAMAGE:0
P2SPACEARENACOUNT:0

---

# Sentinel_AnAttackDeclaredAtANonSentinelUnitIsRedirectedToo
#// SOR_090 Devastator — the second half of the Sentinel clause: it protects "your NON-SENTINEL UNITS"
#// as well as your base. P2's TIE declares its attack at P1's Alliance X-Wing (index 1) rather than at
#// the base; that is equally illegal while Devastator is in the arena, so the attack lands on
#// Devastator (2 damage) and the X-Wing takes nothing.
#// The base-only reading of Sentinel passes the section above and fails here.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: [SOR_090:1:0 SOR_237:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P2>AttackSpaceArena:0:theirSpaceArena-1

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:1:DAMAGE:0
P2SPACEARENACOUNT:0

---

# Control_WithNoSentinel_TheDeclaredUnitIsHitNormally
#// The passing CONTROL for the two Sentinel sections above — without it they are satisfied by an engine
#// that mis-resolves EVERY space attack onto index 0, or that cannot attack a chosen unit at all.
#// Devastator is replaced by an ordinary Consular Security Force (no Sentinel) and the same
#// declaration is made at index 1: this time it lands where it was declared, the X-Wing takes 2, and
#// index 0 is untouched. The TIE still dies to the X-Wing's counter-damage.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: [SOR_046:1:0 SOR_237:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P2>AttackSpaceArena:0:theirSpaceArena-1

## EXPECT
P1SPACEARENAUNIT:1:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENACOUNT:0

---

# Sentinel_IsARENAScoped_TheGroundArenaIsUnprotected
#// SOR_090 Devastator — "Units in THIS ARENA can't attack…". Devastator is a SPACE unit, so its
#// Sentinel does nothing about the ground: P2's Battlefield Marine attacks P1's base and all 3 damage
#// lands. Devastator is untouched — the ground attacker was never redirected into the space arena.
#// The load-bearing negative for the two redirect sections: a Sentinel implemented globally rather than
#// per-arena passes both of them and fails only here.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: SOR_090:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENACOUNT:1

---

# Overwhelm_ExcessSpillsToTheEnemyBase
#// SOR_090 Devastator — Overwhelm: attacking an enemy unit, the excess combat damage goes to the
#// opponent's base. Devastator (10 power) attacks a TIE/ln Fighter (2/1): 1 damage defeats it and the
#// remaining 9 hit P2's base. Devastator takes the TIE's 2 back and survives on 10 HP.
#// The arithmetic is 10 - 1 (the defender's HP), not 10 - 2 (its power) and not the whole 10.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithP1SpaceArena: SOR_090:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:9
P1SPACEARENAUNIT:0:DAMAGE:2

---

# Overwhelm_ShieldPrevents_NoSpillAtAll
#// SOR_090 Devastator — per CR 7.e, if a Shield token on the defender prevents the attacker's combat
#// damage, an Overwhelm attacker deals NOTHING to the enemy base. The same TIE now carries a Shield:
#// the whole 10 is prevented, the TIE survives undamaged with its Shield spent, and P2's base takes 0
#// rather than the 9 a "power minus printed HP" calculation would spill.
#// Devastator still takes the TIE's 2 counter-damage — the Shield protects the defender, not the
#// attacker.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithP1SpaceArena: SOR_090:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_Decline_NoDamageDealt
#// SOR_090 Devastator — "You MAY deal damage to a unit": the decline branch. P1 plays Devastator with
#// 10 resources and an enemy Consular Security Force on the board, then answers the choose with '-'.
#// No damage is dealt anywhere, the enemy unit is untouched at 0, and Devastator is on the board with
#// no decision left pending.
#// The offer is an MZMAYCHOOSE whose continuation carries the entire payoff, so this catches both a
#// sticky decline that skips the continuation AND a continuation that fires the damage regardless of
#// the answer — neither of which any other section in this file can see.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENACOUNT:1
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# WhenPlayed_CountsRESOURCESCONTROLLED_TwelveMeansTwelve
#// SOR_090 Devastator — "damage equal to the number of resources YOU CONTROL". Two readings are
#// separated here at once, and the existing 10-resource section can distinguish neither:
#//   * the amount SCALES with the count rather than being a constant 10 — P1 controls 12, so the
#//     Consular Security Force (3/7) is comfortably defeated where 10 would also have killed it, and
#//     the count itself is pinned by P1RESCOUNT:12.
#//   * READY-ness is irrelevant: Devastator's own cost has just exhausted 10 of those 12, leaving only
#//     2 ready, and the damage is still measured against all 12. A count taken over READY resources
#//     would deal 2 here.

## GIVEN
CommonSetup: ggk/ggk/{myResources:12;handCardIds:SOR_090}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:12
P1RESAVAILABLE:2
P1SPACEARENACOUNT:1

---

# WhenPlayed_OfferSpansEVERYUnit_IncludingITSELFAndALeaderUnit
#// SOR_090 Devastator — "deal damage to A UNIT" is completely unqualified: no controller, no arena, no
#// non-leader clause. The offer therefore has to contain every unit on the table, and that is asserted
#// on the pending decision rather than by answering it. Four units are seated across both sides and
#// both arenas:
#//   * myGroundArena-0 P1's own Battlefield Marine — "a unit" spans YOUR side too
#//   * mySpaceArena-0  Devastator ITSELF — nothing excludes the source of the ability
#//   * theirGroundArena-0 P2's Consular Security Force
#//   * theirGroundArena-1 P2's DEPLOYED LEADER UNIT — a leader unit is still a unit, and this card
#//     prints no "non-leader" restriction, unlike SOR_078 Vanquish
#// Intended: exactly those four.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1

---

# WhenPlayed_MayTargetITSELF
#// SOR_090 Devastator — the sharpest branch of the unqualified target word: with 10 resources the
#// damage is 10, exactly Devastator's own HP, so pointing the ability at itself defeats it the instant
#// it arrives. The enemy unit is untouched.
#// A self-exclusion silently added to the pool (an easy defensive habit — most "deal damage" cards on
#// units do exclude the source) would make this answer unreachable, and the offer section above would
#// be the only other place it showed.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_MayTargetAFRIENDLYUnit
#// SOR_090 Devastator — the other friendly branch: not the source, but another unit P1 controls. The
#// 10 damage defeats P1's own Consular Security Force (3/7) and the card goes to P1's discard, while
#// the enemy Battlefield Marine takes nothing.
#// Distinct from WhenPlayed_MayTargetITSELF because a self-exclusion and a friendly-side exclusion are
#// separate filters; an implementation can have either one without the other.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_DamageIsNotCombat_OverwhelmDoesNOTSpillIt
#// SOR_090 Devastator — the interaction between this card's own two clauses, and the reason they must
#// not share a code path. Overwhelm applies to COMBAT damage dealt while ATTACKING an enemy unit; the
#// When Played ability is neither. P1 plays Devastator and points the 10 damage at a TIE/ln Fighter
#// (2/1). The TIE is defeated and the other 9 damage simply ceases to exist: P2's base stays at 0.
#// Compare Overwhelm_ExcessSpillsToTheEnemyBase, which is the same 10 power against the same 1 HP and
#// DOES send 9 to the base. The two sections differ only in how the damage was dealt, which is exactly
#// the distinction being asserted. Devastator also takes no counter-damage here — it never attacked.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:0
P1BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:0

---

# SimulateRequestBoundary_TargetPickAndTheResourceCountSurvive
#// SOR_090 Devastator — in production the "deal damage to a unit" MZMAYCHOOSE ENDS the request, so the
#// answer arrives in a fresh process with every non-serialized global empty. The amount is baked into
#// the queued continuation at trigger time (DEAL_UNIT_DAMAGE|N), so both the pending pick AND the
#// resource count measured before the boundary have to survive the gamestate round-trip.
#// Mirrors WhenPlayed_OfferSpansEVERYUnit's board minus the deployed leader: P1 answers the enemy
#// Consular Security Force (3/7) and 10 damage defeats it.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENACOUNT:1

---

# ControlTakenDevastator_OverwhelmSpillsToTHEOPPONENTOfItsController
#// SOR_090 Devastator × a control change. Devastator sits in P1's space arena under P1's CONTROL but is
#// OWNED by P2 — the end state after a take-control effect. It attacks P2's TIE/ln Fighter; Overwhelm's
#// "deal excess damage to THE OPPONENT'S base" has to be resolved from the CONTROLLER's seat, so the 9
#// excess lands on P2's base and P1's base takes nothing.
#// An implementation that read the spill target off the unit's OWNER would fire 9 damage into the base
#// of the player who is currently attacking with it — the worst possible failure mode, and one that is
#// completely invisible on every same-seat section in this file.
#// The Sentinel clause is likewise the CONTROLLER's here: it is P1's arena the enemy would have to
#// attack through.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithP1SpaceArenaControlled: SOR_090:2
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:9
P1BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:2
