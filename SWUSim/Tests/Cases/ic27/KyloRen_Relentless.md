# FirstAttack_ReadiesHim
#// IC27_106 Kylo Ren — Relentless (5, 5/5 Ground, Aggression/Villainy, Force·First Order, unique, Legendary)
#// "Hidden. On Attack: If this is the first time this unit has attacked this phase, ready him."
#// PREVIEW card (no official rulings). Hidden is printed (generated keyword code). The On Attack counts this
#// unit's SWU_UNIT_ATTACKED_<uid> entries — ExecuteSWUAttack (the chokepoint every attack passes through:
#// declared, Ambush, event-driven) adds ONE per attack, never idempotent, and every phase start clears them — so
#// exactly 1 at On Attack time means THIS is the first. Summed over every seat: the entry lives on the controller
#// at the time, and a control change mid-phase must not make a second attack look like a first.
#//
#// COVERAGE: offer=N/A (no choice — a conditional ready of himself) · decline=N/A (no "you may") ·
#//           negative=SecondAttack_DoesNotReadyHim (the gate is load-bearing) ·
#//           boundary=N/A (the threshold is "first", covered as 1st vs 2nd attack) ·
#//           duration=NextRound_FirstAttackReadiesAgain (the "this phase" count resets) ·
#//           keyword=Hidden_IsPrinted ·
#//           control=Control_StolenKylo_FirstAttackReadies + Control_StolenAfterAttacking_SecondAttackDoesNotReady
#//           (the count is summed over every seat; reading only the new controller reds the second) ·
#//           blank=Blanked_Kylo_StaysExhausted ·
#//           reqboundary=RequestBoundary_BetweenTheTwoAttacks (the count is a serialized GlobalEffect) ·
#//           modes=2P only (no player reference; "this unit" only)
#//
#// Kylo (5/5) attacks the base: 5 damage, and he ends the attack READY.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_106:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:READY

---

# SecondAttack_DoesNotReadyHim
#// The NEGATIVE. Readied by his first attack, Kylo attacks again the same phase: no ready this time, so he ends
#// exhausted. 5 + 5 on the base.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_106:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:10
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# RequestBoundary_BetweenTheTwoAttacks
#// The first-attack count must survive a request boundary: the same two attacks with one in between, same result.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_106:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:10
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# NextRound_FirstAttackReadiesAgain
#// DURATION EDGE: "this phase". Kylo attacks twice in round 1 (readied once, then exhausted), the round ends, and
#// his first attack of round 2's action phase readies him again. (Round 2 opens with P2 to act — probed — so P2
#// passes first; the regroup's own ready is why the EXPECT reads READY only via the On Attack: he exhausts to
#// attack and the On Attack readies him.)

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_106:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:15
P1GROUNDARENAUNIT:0:READY

---

# Hidden_IsPrinted
#// The printed keyword is there (generated keyword code).

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithP1GroundArena: IC27_106:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# Control_StolenKylo_FirstAttackReadies
#// "this unit" — a Kylo OWNED by P2 and controlled by P1 is readied by its first attack for P1 like any other.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: IC27_106:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:READY

---

# Control_StolenAfterAttacking_SecondAttackDoesNotReady
#// Why the count is summed over every seat. P2's Kylo attacks P1's base (his first attack: readied). P1 then
#// takes control of him with Change of Heart (SOR_224) and attacks with him — his SECOND attack this phase, so
#// no ready. The first attack's entry sits on P2 (his controller then); reading only the new controller would
#// see 0 and ready him.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8;handCardIds:SOR_224}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: IC27_106:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:5
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:IC27_106
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Blanked_Kylo_StaysExhausted
#// A Kylo that has lost all abilities (SOR_138 marker) has no On Attack: he ends his first attack exhausted.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_106:1:0:SOR_138

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
