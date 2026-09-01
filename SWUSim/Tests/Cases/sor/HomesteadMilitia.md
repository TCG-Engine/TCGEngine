# SixResources_GainsSentinel
#// SOR_113 Homestead Militia (Unit, cost 3, [Command], Fringe/Trooper, 3/4) — "While you control 6 or
#// more resources, this unit gains Sentinel."
#// COVERAGE: boundary pair=Boundary_FiveResources_NoSentinel (5) vs SixResources_GainsSentinel (6),
#//           plus KeywordGrant_IsAssertedDirectlyAtSixAndAtFive which reads the grant off the unit
#//           rather than off a combat outcome, and ExhaustedResourcesStillCount (controlled ≠ ready) ·
#//           control change=ControlChange_TheGrantReadsTheCONTROLLERSResources +
#//           ControlChange_OwnersResourcesAreNotCounted — same owner≠controller seating, resource
#//           piles swapped, opposite answers · offer=N/A — the card offers nothing: Sentinel is a
#//           continuous keyword grant and the redirect it causes is enforced by the attack-legality
#//           rules, so there is no target pool to inspect (the closest observable, "which units may be
#//           attacked", is asserted as the redirect outcome in every combat section here) ·
#//           decline=N/A — no "you may" and no cost anywhere in the printed text; a granted keyword
#//           cannot be declined · request boundary=N/A — nothing pends and nothing is stored: the gate
#//           is recomputed from the live resource zone on every read, which is what
#//           KeywordGrant_IsAssertedDirectlyAtSixAndAtFive asserts with no action taken at all.
#// ⚠ CreditTokensAreNotResources (last section) is RED — a known engine defect, left in the file as
#//   the evidence. The gate counts every object in the resource zone, so Credit tokens (CR 3.13: not
#//   resources) push the Militia over the line. Do not delete it.
#// (Implemented in HasConditionalKeyword_Sentinel; this section locks the ≥6-resource condition
#// behaviorally.)
#// P2 controls 6 resources, so its Homestead Militia has Sentinel. P1's base-attack
#// is force-redirected onto it (only valid target). Combat uses printed HP: P1's 3/3
#// attacker deals 3 to the 3/4 Militia (survives); the Militia deals 3 back (attacker
#// dies). P2's base takes 0 — proving Sentinel blocked the base attack.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 6
WithP1GroundArena: SOR_095:1:0    # attacker (3/3)
WithP2GroundArena: SOR_113:1:0    # Homestead Militia (3/4) → Sentinel at 6 resources

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# Boundary_FiveResources_NoSentinel
#// THE N−1 SIDE. "While you control 6 OR MORE resources, this unit gains Sentinel." P2 controls
#// exactly FIVE resources, one short, so the Militia has NO Sentinel and P1's 3/3 Battlefield Marine
#// attacks the base unopposed: 3 damage to the base, the Militia untouched, the attacker alive.
#// Read against SixResources_GainsSentinel (identical board, one more resource, base takes 0 and the
#// attacker dies) this pins the threshold at exactly 6 — a one-resource difference flips every
#// observable in the section.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 5
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_113
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENACOUNT:1

---

# KeywordGrant_IsAssertedDirectlyAtSixAndAtFive
#// The same boundary read off the KEYWORD rather than off a combat outcome — a conditional grant can
#// be "present" in a redirect check while never appearing on the unit itself (or vice versa), so the
#// grant is asserted directly on both sides of the line in one board: P1's Militia sits on 6 resources
#// and HAS Sentinel; P2's identical Militia sits on 5 and does NOT. No attack is made — this section
#// is purely the static grant.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2Resources: 5
WithP1GroundArena: SOR_113:1:0
WithP2GroundArena: SOR_113:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# ExhaustedResourcesStillCount
#// "While you CONTROL 6 or more resources" counts resources CONTROLLED, not resources READY — spending
#// them exhausts them but never removes them from your control. All six of P2's resources are seeded
#// exhausted (status 0) and the Militia still has Sentinel, so P1's base attack is still redirected
#// onto it.
#// This is the load-bearing negative for the obvious wrong implementation (counting only ready
#// resources), which would silently switch this card off for the whole turn a player taps out.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 6:SOR_095:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2RESCOUNT:6
P2RESAVAILABLE:0
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# SentinelIsArenaScoped_SpaceAttackerStillReachesTheBase
#// The reminder text is "Units in THIS ARENA can't attack your non-Sentinel units or your base." The
#// Militia is a GROUND unit, so the grant only restrains ground attackers. P2 controls 6 resources and
#// the Militia does have Sentinel — proved on the same board — yet P1's SPACE attacker (TWI_253
#// Headhunter Squadron, 1/4, vanilla) reaches P2's base for its full 1 damage and takes nothing back.
#// The scope exclusion cell: without it, an over-broad "any attacker must hit the Sentinel" redirect
#// would pass every ground section in this file.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 6
WithP1SpaceArena: TWI_253:1:0
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2BASEDMG:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ControlChange_TheGrantReadsTheCONTROLLERSResources
#// OWNER ≠ CONTROLLER. "While YOU control 6 or more resources" resolves against the unit's CONTROLLER,
#// never its owner. The Militia sits in P1's arena but is OWNED by P2 (the end state of a take-control
#// effect). P1 — the controller — holds 6 resources, so the grant is ON, and P2's attack on P1's base
#// is redirected into the stolen Militia. P2 owns the card and holds only 1 resource, which is the
#// half that must NOT be read.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 6
WithP2Resources: 1
WithP1GroundArenaControlled: SOR_113:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1BASEDMG:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# ControlChange_OwnersResourcesAreNotCounted
#// THE DISCRIMINATING HALF of the control axis — the same stolen-Militia board with the two resource
#// piles swapped. P1 CONTROLS the Militia and holds only 5 resources (below the line), while P2 — who
#// merely OWNS the card — holds 9. If the grant read the OWNER's resources the Militia would have
#// Sentinel and P2's attack would be redirected; it must instead have NO Sentinel, so P2's Battlefield
#// Marine attacks P1's base for its full 3 and the Militia is untouched.
#// Paired with ControlChange_TheGrantReadsTheCONTROLLERSResources above: same seating, opposite
#// answer, and only an owner-vs-controller mix-up can produce the other result.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP2Resources: 9
WithP1GroundArenaControlled: SOR_113:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# CreditTokensAreNotResources
#// CR 3.13 — a Credit token is NOT a resource: it sits in the resource zone and can be spent to pay
#// costs, but it is not a resource card and never counts toward "you control N resources". SWUSim
#// already encodes this centrally (SWUResourceCount skips Credit tokens explicitly).
#// P2 controls FIVE real resources plus TWO Credit tokens. Five is below the line, so the Militia must
#// NOT have Sentinel and P1's Battlefield Marine must reach the base for its full 3 — exactly the
#// Boundary_FiveResources_NoSentinel outcome, which is this section's passing control on the identical
#// board minus the tokens.
#// Intended: the seven objects in P2's resource zone are five resources and two tokens, not seven
#// resources.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
P1OnlyActions: true
WithP2Resources: 5
WithP2Credits: 2
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_113:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2RESCOUNT:5
P2CREDITCOUNT:2
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2BASEDMG:3
P1GROUNDARENACOUNT:1
