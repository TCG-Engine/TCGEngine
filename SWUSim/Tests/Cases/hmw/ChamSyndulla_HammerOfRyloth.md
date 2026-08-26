# Front_FriendlyUnitDamaged_ExhaustToPing
#// COVERAGE: offer=Front_OfferIsEnemyUnitsAndBaseOnly ·
#//           decline=Front_Decline_LeaderStaysReady and Deployed_Decline_NoDamage ·
#//           boundary=N/A (no numeric threshold; the gate is the binary combat/non-combat split,
#//           covered by Front_CombatDamage_DoesNotTrigger) ·
#//           control=Front_EnemyUnitDamaged_DoesNotTrigger (the "friendly" scoping) ·
#//           reqboundary=SimulateRequestBoundary_OfferSurvives
#//
#// HMW_013 Cham Syndulla - Hammer of Ryloth (Leader, Ground, 3/8, cost 6, Aggression+Heroism, Twi'lek)
#//   FRONT : "When non-combat damage is dealt to a friendly unit or base: You may exhaust this leader.
#//            If you do, deal 1 damage to an enemy unit or base."
#//   EPIC  : "If you control 6 or more resources, deploy this leader."  (generic — needs no code)
#//   DEPLOY: "When non-combat damage is dealt to a friendly unit or base:
#//            You may deal 1 damage to an enemy unit or base."   (same trigger, no exhaust cost)
#//
#// ⚠ PREVIEW SET — no card-specific-rulings.md entry. Two readings are USER RULINGS taken 2026-08-26
#// rather than sourced, and both are flagged here so they can be re-checked when HMW releases:
#//   (a) he triggers ONCE PER DAMAGED THING — an AoE hitting three friendly units fires three times.
#//       This matches HMW_045 Logray in the same set, which already fires per damaged unit through the
#//       very same observer seam. Pinned by Front_TwoFriendlyUnitsDamaged_TwoTriggers.
#//   (b) in Team Suns a TEAMMATE's damaged unit or base triggers him ("friendly" spans the team).
#//       Pinned by TeamSuns_TeammatesUnitDamaged_Triggers.
#//
#// This section is the plain front-side positive: the OPPONENT's Daring Raid (an event, so non-combat)
#// damages seat 1's unit, Cham's controller accepts, Cham exhausts, and 1 damage goes onto an enemy unit.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_FriendlyBaseDamaged_Triggers
#// "…to a friendly unit OR BASE." The base half is a SEPARATE code path from the unit half (base damage
#// never routes through the unit-damage observer), so it needs its own section or half the trigger is
#// untested. Same Daring Raid, aimed at seat 1's base instead.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirBase-0
- P1>Drain
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:2
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_CombatDamage_DoesNotTrigger
#// ⚠⚠ THE LOAD-BEARING NEGATIVE. The word is "NON-COMBAT damage" — the single gate the whole card hangs
#// on, and the one an implementation hooked into the generic damage funnel would silently lose.
#// Seat 2 attacks seat 1's unit, which takes real combat damage. Cham must NOT offer anything, and must
#// still be READY afterwards. Asserting P1NODECISION as well as the ready leader is what separates
#// "the offer was declined" from "the offer was never made".

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:READY
P1NODECISION

---

# Front_EnemyUnitDamaged_DoesNotTrigger
#// "a FRIENDLY unit or base" — the controller scoping. Seat 1 damages seat 2's unit with its own Daring
#// Raid; that is non-combat damage, but to an ENEMY, so Cham stays out of it and stays ready.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:READY
P1NODECISION

---

# Front_SelfInflictedDamage_StillTriggers
#// The text puts NO restriction on who deals the damage — only on who receives it. So seat 1 damaging
#// its OWN unit still turns Cham on. Worth its own section because the natural implementation instinct
#// is to key the observer on the DEALER (which is what JTL_009 Boba, the other non-combat-damage leader,
#// actually does) and Cham is the mirror of that.
#// Seat 1 aims its own Daring Raid at its own unit.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_178
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# Front_Decline_LeaderStaysReady
#// "You MAY exhaust this leader" — declining must cost nothing at all. Answering NO leaves Cham READY
#// (so he is still available for a later damage event this phase) and deals no damage anywhere.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:NO

## EXPECT
P1LEADER:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_ExhaustedLeader_NoOffer
#// The exhaust is a COST, so an already-exhausted Cham cannot pay it and must not be offered at all —
#// an offer that can only be refused is the "fizzle-only optional" shape the repo bans.
#// Cham starts exhausted (myLeader's 2nd inline field = ready 0).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013:0; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_OfferIsEnemyUnitsAndBaseOnly
#// THE OFFER, left pending. "an ENEMY unit or base" — so the pool must hold both enemy units and the
#// enemy base, and must exclude everything friendly. Two enemy units so the choice cannot auto-resolve,
#// and a friendly unit on the board that must NOT appear.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirBase-0

---

# Front_TargetCanBeTheEnemyBase
#// The "or base" half of the TARGET (distinct from the "or base" half of the trigger). Answering the
#// enemy base must put 1 damage on it.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# Deployed_FriendlyUnitDamaged_PingsWithoutExhausting
#// THE DEPLOYED SIDE — a separate ability set that must clear the bar on its own. Same trigger, but the
#// exhaust cost is GONE: it is a straight "you may deal 1 damage", so there is no YESNO, just the target
#// choice, and the leader unit is left untouched.
#// myLeader's 3rd inline field deploys him as a real linked ground-arena leader unit.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013:1:1; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:DEPLOYED

---

# Deployed_Decline_NoDamage
#// The deployed side is still a "you may" — declining the target choice deals nothing.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013:1:1; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Deployed_CombatDamage_DoesNotTrigger
#// The non-combat gate must hold on the DEPLOYED side too — it is a different registration, so a fix
#// applied to only one side passes the other's positive and fails nothing.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013:1:1}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1NODECISION
#// 3 is the pure counter-damage from the defending SOR_046. Asserting exactly 3 (not 0) is what makes
#// this discriminate: if the deployed trigger wrongly fired on combat damage it would read 4.
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# SimulateRequestBoundary_OfferSurvives
#// REQUEST BOUNDARY. The observer is queued during the OPPONENT's action and answered by Cham's
#// controller in a later request, so the boundary sits between the damage and the acceptance — exactly
#// where the offer would evaporate if any of it were parked in an in-memory global.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; theirResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_178

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# Deployed_TwoFriendlyUnitsDamaged_TwoTriggers
#// ⚠ USER RULING 2026-08-26 — he triggers ONCE PER DAMAGED THING, not once per damage effect.
#// This matches HMW_045 Logray in the same set, which already fires per damaged unit through the very
#// same observer seam. Tested on the DEPLOYED side because the front's exhaust cost self-limits it to
#// one use, so only the free side can show the count.
#//
#// TWI_173 Blood Sport deals 2 damage to EVERY ground unit. Seat 1 fields the deployed Cham himself
#// (3/8, a ground unit, so he is damaged too) plus one more unit = TWO friendly units damaged = two
#// offers. Seat 2's unit is damaged as well but is an ENEMY, so it must NOT add a third.
#//
#// The enemy 3/7 ends on 2 (Blood Sport) + 1 + 1 = 4. That number is what makes this discriminate:
#// one trigger reads 3, three triggers read 5, and "once per effect" reads 3.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013:1:1; myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_173
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1LEADER:DEPLOYED

---

# TeamSuns_TeammatesUnitDamaged_Triggers
#// ⚠ USER RULING 2026-08-26 — in Team Suns "a FRIENDLY unit or base" spans the TEAM, so a teammate's
#// damaged unit turns your Cham on. Seats 1 and 3 are both Red.
#//
#// Seat 1 aims its own Daring Raid at its TEAMMATE's unit at seat 3 (legal, and the cleanest way to
#// drive the trigger in one action), and seat 1's Cham must offer.
#// ⚠ Note the enemy pool this produces is itself a check on the team model: seat 3 is a teammate, so
#// the target offered here can only be a seat-2 or seat-4 card.
#// This section CANNOT PASS AT TWO SEATS — seat 3 does not exist there.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:p2GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# TwinSunsControl_TeammateSeatIsNotFriendly
#// ⚠ THE CONTROL that makes the section above mean something — a byte-identical board with
#// SWU_MODE_TEAMS REMOVED. In plain Twin Suns seat 3 is just another opponent, so damaging its unit is
#// damaging an ENEMY and Cham must stay silent and READY.
#// Without this, the Team Suns section would pass for a build that simply triggered on ANY damaged
#// unit anywhere, which is the easiest way to get "friendly" wrong.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:HMW_013; myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:READY
P1NODECISION
