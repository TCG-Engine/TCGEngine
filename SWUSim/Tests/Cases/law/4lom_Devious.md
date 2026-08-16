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
