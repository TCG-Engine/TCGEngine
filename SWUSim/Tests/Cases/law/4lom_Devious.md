# WhenPlayedAttackBountyHunter
#// LAW_065 4-LOM (4/5) — When Played: you may attack with a friendly Bounty Hunter unit, even if it's
#// exhausted. It can't attack bases this attack. Exhausted LAW_124 (Bounty Hunter, 4/7) attacks the enemy
#// SOR_046 (3/7): deals 4, takes 3 counter.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayedAttackBountyHunter_SurvivesTheRequestBoundary
#// LAW_065 4-LOM — request-boundary guard. The ability's "it can't attack bases this attack" restriction and
#// the "attack even if exhausted" permission are recorded when the Bounty Hunter is chosen, and are only
#// consumed after the SEPARATE attack-target decision — which in production is answered by a fresh process.
#// Same flow as WhenPlayedAttackBountyHunter but with a SECOND enemy ground unit so the attack-target choose
#// is genuinely pending (pool = theirGroundArena-0 & theirGroundArena-1, no base offered), with a serialize
#// round-trip inserted before that answer. The exhausted LAW_124 still attacks and the outcome is unchanged.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayedOffer_FriendlyBountyHuntersOnly
#// LAW_065 4-LOM — OFFER assertion for "you may attack with a friendly Bounty Hunter unit". Discriminating
#// board: P1 holds an EXHAUSTED friendly Bounty Hunter (LAW_124, idx 0 — in, the "even if exhausted" clause)
#// and a friendly NON-Bounty-Hunter (SOR_095 Battlefield Marine, idx 1 — out on trait); P2 holds a
#// non-BH (SOR_046, idx 0) and its OWN Bounty Hunter (LAW_124, idx 1 — out on controller scope). 4-LOM
#// itself lands at idx 2 and IS in the pool: the text says "a friendly Bounty Hunter unit", not "another",
#// and 4-LOM is a Bounty Hunter. Pool must be exactly the two friendly Bounty Hunters.
#// COVERAGE: offer=this section (trait filter + friendly-only scope + self-inclusion) and
#//           AttackTargetOffer_NoBaseOffered (the "can't attack bases" restriction at the offer) ·
#//           reqboundary=WhenPlayedAttackBountyHunter_SurvivesTheRequestBoundary ·
#//           decline=N/A (the "you may" MZMAYCHOOSE decline is generic prompt behavior; the grant only
#//           fizzles) · control=N/A (the attack permission is granted to the chooser's own unit; no
#//           control-change interaction) · boundary pair=WhenPlayedAttackBountyHunter (exhausted BH still
#//           attacks) vs this section's enemy-BH exclusion

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [LAW_124:0:0 SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0 LAW_124:1:0]
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:2:CARDID:LAW_065
P2GROUNDARENAUNIT:1:CARDID:LAW_124

---

# AttackTargetOffer_NoBaseOffered
#// LAW_065 4-LOM — the SECOND decision of the granted attack. "It can't attack bases for this attack" is
#// asserted at the OFFER, not just the outcome: after the exhausted LAW_124 is chosen as the attacker, the
#// attack-target pool is exactly the two enemy ground units and contains NO base entry (theirBase-0 is
#// absent). Same discriminating board as WhenPlayedOffer_FriendlyBountyHuntersOnly.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [LAW_124:0:0 SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0 LAW_124:1:0]
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# DeclineTheAttack_NothingHappens
#// LAW_065 4-LOM — "You MAY attack with a friendly Bounty Hunter unit", so the pick is declinable and
#// declining is a complete resolution: the exhausted LAW_124 stays exhausted and undamaged, the enemy
#// SOR_046 takes nothing, and 4-LOM is simply in play. None of the existing sections takes this branch.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_124:0:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Offer_OnlyFourLomHimselfWhenNoOtherFriendlyBountyHunter
#// LAW_065 4-LOM — the attacker pool is FRIENDLY Bounty Hunters and the text says nothing about "another",
#// so 4-LOM is a legal choice for his own ability even though he was played this action ("even if it's
#// exhausted" is what lets a just-played unit attack). On a board whose only other friendly unit is the
#// non-Bounty-Hunter SOR_095 and whose other Bounty Hunter belongs to the OPPONENT, the pool narrows to
#// exactly one entry: 4-LOM. That single entry is the assertion — it separates the controller filter and
#// the trait filter from a pool that had simply gone empty.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 LAW_124:1:0]
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1
P1GROUNDARENAUNIT:1:CARDID:LAW_065

---

# NoEnemyUnitToAttack_NoOfferAtAll
#// LAW_065 4-LOM — "It can't attack bases for this attack", so with the opponent's board EMPTY the granted
#// attack has no legal target and the optional offer is withheld entirely rather than raised and then
#// fizzled (USER RULING 2026-08-17). The exhausted Bounty Hunter stays exhausted and undamaged and the
#// enemy base takes nothing.
#// This began as a RED: before the fix the offer WAS raised, and choosing an attacker left it READY —
#// BeginSWUAttack readies the attacker for the "even if it's exhausted" clause and then aborts for want of
#// a target without restoring that, so the prompt handed out a free ready every time 4-LOM was played into
#// an empty enemy board. Controls that pinned it to the fizzle path: with an enemy unit present the same
#// flow leaves the attacker EXHAUSTED (WhenPlayedAttackBountyHunter), and DECLINING on this exact board
#// also leaves it EXHAUSTED (DeclineTheAttack_NothingHappens).
#// Boundary partner of AttackTargetOffer_NoBaseOffered, where two enemy units exist and only the base is
#// excluded.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_124:0:0
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# EnemyOnlyInTHEOTHERArena_StillNoOffer
#// LAW_065 4-LOM — the "can it attack anything" gate is per ARENA, not per board. The only enemy unit is
#// in SPACE while the friendly Bounty Hunter is on the GROUND, so it still has no legal target and the
#// offer is withheld. Without this section a gate that merely counted enemy units anywhere would look
#// correct on the empty board above.

## GIVEN
CommonSetup: gyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_124:0:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:DAMAGE:0

---

# ADeployedBountyHunterLEADERIsAValidAttacker
#// LAW_065 4-LOM — a deployed leader is a unit in the ground arena, so a leader whose printed traits
#// include Bounty Hunter belongs in the attacker pool. P1's LAW_004 Aurra Sing (Underworld, Bounty Hunter)
#// is deployed alongside the freshly played 4-LOM and both are offered.
#// This section exists because the pool is built with a bare-CardID `HasTrait($o->CardID, 'Bounty Hunter')`
#// rather than the object-aware `TraitContains`, which is the shape that historically missed deployed
#// leaders. Probed and CLEARED: for a leader with no deployed-side trait override the two agree, and no
#// card in the dictionary currently GRANTS the Bounty Hunter trait, so the two checks cannot diverge here
#// today. This guard is what will fail if a trait-granting card is ever added.
#// ⚠ Aurra Sing is Vigilance/Villainy, so overriding the leader leaves 4-LOM's Cunning uncovered by the
#// bgw-style default: 8 resources are seeded to cover the cost-5 unit plus that +2 aspect penalty.

## GIVEN
CommonSetup: gyk/bgw/{myResources:8; myLeader:LAW_004:1:1:1}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_065

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_004
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:1:CARDID:LAW_065
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
