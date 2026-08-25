# CadBaneOrdering_BothResolve
#// SHD_172 Krayt Dragon — CR 7.6.10 ordering with a simultaneous trigger from the OTHER player. P1 controls
#// Cad Bane (SHD_014, undeployed) and plays SOR_247 (Underworld, printed cost 2). P1's Cad Bane ("when you
#// play an Underworld card") AND P2's Krayt ("when an opponent plays a card") both trigger. As the active
#// player, P1 first answers "Resolve Which Player First?" (YES = P1's own trigger first). Both resolve:
#//   - Cad Bane: P1 exhausts it → P2 chooses their unit (Krayt) → Krayt takes 1.
#//   - Krayt: P2 may deal SOR_247's printed cost (2) to P1's base.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myhandCardIds:SOR_247;myLeader:SHD_014}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_172
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2

---

# Decline_NoDamage
#// SHD_172 Krayt Dragon — it's a "may": declining deals no damage.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0

---

# OppPlays_DealsToBase
#// SHD_172 Krayt Dragon (Unit, Ground, cost 9, 10/10, Overwhelm, Creature)
#//   "When an opponent plays a card: You may deal damage equal to that card's cost to their base or a
#//    ground unit they control."
#// P1 controls Krayt. P1 passes; P2 plays SEC_080 (printed cost 2). Krayt triggers on the opponent's play →
#// P1 may deal 2 (the printed cost) to P2's base or a P2 ground unit. P1 picks P2's base → P2BASEDMG 2.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2

---

# OppPlays_DealsToGroundUnit
#// SHD_172 Krayt Dragon — deal the damage to a ground unit the opponent controls (not the base).
#// P2 plays SEC_080 (printed 2); P1's Krayt deals 2 to that just-played ground unit (survives, DAMAGE:2).

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0

---

# OwnPlay_NoTrigger_IHaveKrayt
#// SHD_172 Krayt Dragon — it triggers only on an OPPONENT's play. P1 controls Krayt and plays SEC_080
#// themselves → Krayt does NOT trigger (no damage, no decision).

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myhandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# OwnPlay_NoTrigger_TheyHaveKrayt
#// SHD_172 Krayt Dragon — the opponent playing their OWN card doesn't trigger their Krayt. P2 controls
#// Krayt; P1 passes and P2 plays SEC_080 → P2's Krayt does NOT trigger (its own play is not an opponent's).

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0

---

# PrintedCostNotPaid
#// SHD_172 Krayt Dragon — damage is the PRINTED cost, not the amount actually paid. P2's base/leader (bbw =
#// Vigilance/Heroism) covers neither of SEC_080's aspects (Command,Villainy), so P2 pays 2 + 4 penalty = 6.
#// Krayt still deals only the PRINTED 2 to P2's base (proving it's not the 6 paid).

## GIVEN
CommonSetup: rrk/bbw/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P2RESAVAILABLE:0

---

# TheyHaveKrayt_IPlay
#// SHD_172 Krayt Dragon — cross-player direction: P2 controls Krayt, P1 (active) plays SEC_080 (printed 2).
#// P2's Krayt triggers on P1's play → P2 may deal 2 to P1's base or a P1 ground unit. P2 picks P1's base
#// (from P2's frame that's theirBase-0) → P1BASEDMG 2. Drives as one P1 action + a P2 reaction answer.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myhandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:EffectStack-0
- P2>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:2

---

# TwoKrayts_BothTrigger
#// SHD_172 Krayt Dragon — Krayt is NOT unique, so a player can control two. When P2 plays SEC_080 (printed
#// 2), BOTH of P1's Krayts trigger; P1 resolves them one at a time (a single collapsed trigger loops the
#// may-deal per Krayt — this avoids the pre-existing engine hang on two IDENTICAL reactive triggers). P1
#// sends both to P2's base → 2 + 2 = 4 base damage.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [SHD_172:1:0 SHD_172:1:0]

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4

---

# TwinSuns_OffersONLYThePlayingSeatsBoard
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, DETERMINED). Krayt names the player who PLAYED the
#// card ("their base or a ground unit THEY control"), so no seat is ever chosen. FOUR independent causes:
#//   (1) `GameLogic.php` built the payload "cost~count" with $playingPlayer IN SCOPE and DROPPED it,
#//       forcing the continuation to re-derive the seat as "the reactor's opponent" — a guess.
#//   (2) `KraytDragon.php` hand-built `'theirBase-0'`, a RELATIVE mzID resolved by a literal
#//       `$playerID == 1 ? 2 : 1`.
#//   (3) the unit pool was `ZoneSearch('theirGroundArena')`, which FANS OUT across every opponent in Twin
#//       Suns, so the menu offered BYSTANDERS' units. ⚠ The sweep's inverse defect — the pool GREW, so
#//       nothing looked broken and every existing test stayed green.
#//   (4) the base-damage branch used `GetOpponent()`, NULL above seat 2.
#//
#// ⚠⚠ THE FIXTURE IS THE HARD PART, AND THE OBVIOUS ONE IS WORTHLESS. A first attempt put the Krayt on
#// seat 3 and the PLAYING player on seat 1 — and mutations (1) and (2) BOTH still passed, because for a
#// seat-3 frame `SWUChooseOpponent(3)` and `$playerID == 1 ? 2 : 1` BOTH answer seat 1, which was the
#// playing player. The legacy code was right by accident, exactly like SHD_161's bounty section.
#// So: the Krayt sits on SEAT 3 and the card is played by SEAT 4. Now the legacy answers (seat 1) and the
#// correct answer (seat 4) are different seats, and every cause is separable.
#//
#// SEAT 4 (active) plays SEC_080, printed cost 2. Seat 3's Krayt reacts. The offer must be EXACTLY seat
#// 4's base plus seat 4's ground unit; seats 1 and 2 each control a ground unit that must NOT appear.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent the reactor's opponent IS the playing player.
#// Mutation check: each of the four causes reverted independently reds this, and all nine 2-player
#// sections stay green.

## GIVEN
CommonSetup: rrk/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 4
WithInitiativePlayer: 4
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SHD_172:1:0
WithP4Hand: SEC_080
WithP4Resources: 6
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P4>PlayHand:0
- P3>AnswerDecision:EffectStack-0

## EXPECT
SEATCOUNT:4
P3HASDECISION
P3SELECTABLEEXACT:p4Base-0&p4GroundArena-0
