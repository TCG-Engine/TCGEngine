# AnotherCunning_GainsSentinel
#// SOR_211 Gamorrean Guards (4/4) guard — "While you control another Cunning unit,
#// this unit gains Sentinel." P2 controls Gamorrean Guards + another Cunning unit
#// (SOR_217), so the Guards have Sentinel. P1's base-attack is force-redirected onto
#// the Guards (only valid target — SOR_217 is non-Sentinel and can't be attacked
#// while a Sentinel unit is present). Combat uses printed HP: P1's 3/3 attacker deals
#// 3 to the 4/4 Guards (survive); the Guards deal 4 back (attacker dies). Base 0.
#// COVERAGE: offer=Offer_SentinelNarrowsTheAttackPoolToTheSentinelUnitsOnly (pending SELECTABLEEXACT
#//           on the attack-target picker: two Sentinel Guards in, a non-Sentinel friendly unit AND
#//           P2's base out) · reqboundary=RequestBoundary_SentinelIsRecomputedFromTheSerializedState ·
#//           control=ControlChange_ACunningUnitYouControlButDoNotOwnStillEnablesIt ("while YOU CONTROL
#//           another Cunning unit" reads control, not ownership) · boundary pair=zero vs one other
#//           Cunning unit: NoOtherCunningUnit_NoSentinel_TheBaseIsAttackable (0 → no Sentinel, base
#//           reachable) vs AnotherCunning_GainsSentinel (1 → Sentinel, attack redirected), with the
#//           dynamic edge EnablerLeavesPlay_SentinelIsLostImmediately crossing back over the same
#//           line mid-turn · decline=N/A — a static conditional keyword grant raises no decision at
#//           all, so there is no branch to decline; the only choice it produces is the attacker's
#//           target pick, covered by the offer section.
#// Scope guards: EnemyCunningUnitDoesNotEnableIt (controller scope),
#// CunningUnitInTheOtherArenaStillEnablesIt (no arena scope),
#// TwoGuards_EachIsTheOthersCunningUnit_BothGainSentinel ("another" excludes self, not a second copy).

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # attacker (3/3)
WithP2GroundArena: SOR_211:1:0    # Gamorrean Guards (4/4, Cunning)
WithP2GroundArena: SOR_217:1:0    # another Cunning unit

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2

---

# Offer_SentinelNarrowsTheAttackPoolToTheSentinelUnitsOnly
#// Intended: with Sentinel up, "units in this arena can't attack your NON-Sentinel units or your
#// base" — so the attack-target pool is exactly the Sentinel units. P2 fields TWO Gamorrean Guards
#// (each is the "another [Cunning] unit" the other needs, so both have Sentinel) plus a non-Sentinel
#// Battlefield Marine. P1 plays Shoot First, whose attacker auto-resolves onto P1's only unit, and the
#// attack-target decision is left PENDING: the pool is the two Guards, with the Marine AND P2's base
#// both excluded.

## GIVEN
CommonSetup: yrw/grw/{myResources:1;myhandCardIds:SOR_217}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_211:1:0 SOR_211:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# TwoGuards_EachIsTheOthersCunningUnit_BothGainSentinel
#// SOR_211 — "ANOTHER [Cunning] unit" excludes the Guards themselves but a SECOND copy satisfies it
#// for both: each Guard is the other's enabler, so both carry Sentinel while a non-Cunning friendly
#// unit beside them does not.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [SOR_211:1:0 SOR_211:1:0 SOR_095:1:0]

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:2:NOTKEYWORD:Sentinel

---

# NoOtherCunningUnit_NoSentinel_TheBaseIsAttackable
#// SOR_211 — the NEGATIVE that proves the gate is load-bearing. The Guards are P2's ONLY unit, and
#// "another" excludes themselves, so they do NOT have Sentinel: P1's Battlefield Marine attacks the
#// base unimpeded for 3 and the Guards take no damage.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_211:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3
P1GROUNDARENACOUNT:1

---

# EnemyCunningUnitDoesNotEnableIt
#// SOR_211 — "While YOU control another [Cunning] unit" is controller-scoped. Here the only other
#// Cunning unit on the table is P1's Crafty Smuggler, which is not P2's, so P2's Guards get no
#// Sentinel and P1's Marine still reaches the base for 3.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_207:1:0]
WithP2GroundArena: SOR_211:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2BASEDMG:3

---

# CunningUnitInTheOtherArenaStillEnablesIt
#// SOR_211 — "another [Cunning] unit" is not arena-scoped: P2's Outer Rim Headhunter sits in SPACE
#// and still turns the ground Guards' Sentinel on. P1's ground attack declared at the base is
#// force-redirected onto the Guards (4/4): the Marine deals 3 and dies to the Guards' 4, and the base
#// takes nothing.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_211:1:0
WithP2SpaceArena: SOR_208:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1GROUNDARENACOUNT:0

---

# EnablerLeavesPlay_SentinelIsLostImmediately
#// SOR_211 — "WHILE you control another [Cunning] unit" is a live condition, not a one-time stamp.
#// P2's only enabler is the Headhunter in space; Sentinel is arena-scoped so P1's X-Wing can kill it
#// there (2 more damage on a 3-HP unit already at 2). With the enabler gone the Guards lose Sentinel
#// and P1's ground Marine then reaches the base for 3 in the same turn.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_211:1:0
WithP2SpaceArena: SOR_208:1:2

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3

---

# ControlChange_ACunningUnitYouControlButDoNotOwnStillEnablesIt
#// SOR_211 — "while YOU CONTROL another [Cunning] unit" reads control, not ownership. The Crafty
#// Smuggler P2 controls but P1 OWNS (the end state after a take-control effect) is a Cunning unit P2
#// controls, so the Guards gain Sentinel and P1's attack declared at the base is force-redirected onto
#// the Guards. Controlled units seat AFTER the plain arena lines, so the Guards are index 0.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_211:1:0
WithP2GroundArenaControlled: SOR_207:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_211
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SOR_207
P2GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:0

---

# RequestBoundary_SentinelIsRecomputedFromTheSerializedState
#// SOR_211 — the grant is a live board read, not a cached flag, so it must survive the request
#// boundary that separates two of P1's actions in production. After the round-trip the Guards still
#// have Sentinel (P2's Crafty Smuggler is the Cunning enabler) and P1's attack
#// declared at the base is still force-redirected onto them.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_211:1:0 SOR_207:1:0]

## WHEN
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:0
