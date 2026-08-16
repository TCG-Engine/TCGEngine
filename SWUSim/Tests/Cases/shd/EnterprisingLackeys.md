# WhenDefeated_Decline
#// SHD_107 Enterprising Lackeys — declining the "may" leaves it in the discard and the resources
#// untouched.
#// COVERAGE: offer=WhenDefeated_Offer_EveryFriendlyResourceReadyOrExhausted (pending P1SELECTABLEEXACT) ·
#//           decline=WhenDefeated_Decline ('-') ·
#//           control=WhenDefeated_FizzlesWhenAlreadyResourcedByTheAttacker (the When Defeated resolves for
#//           P2 — SHD_107's controller — while the card itself sits in P1's resource zone) ·
#//           boundary=WhenDefeated_ResourceSwap (rider lands) vs
#//           WhenDefeated_FizzlesWhenAlreadyResourcedByTheAttacker (rider fizzles, cost still paid) ·
#//           reqboundary=WhenDefeated_FizzlesWhenAlreadyResourcedByTheAttacker (the cross-player offer is
#//           built at P2>Drain, AFTER the attack's cleanup — state is re-read at the request boundary)

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_107:1:1
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_107

---

# WhenDefeated_ResourceSwap
#// SHD_107 Enterprising Lackeys (4-cost 5/5) — "When Defeated: You may defeat a friendly resource.
#// If you do, put this unit into play as a resource." P1's 1-damaged Lackeys attacks a Wampa
#// (SOR_164, 4/5): mutual kill (5 ≥ 5; counter 4 → 1+4 = 5); the When Defeated resolves inline
#// (attacker self-death). P1 picks a resource to defeat → Lackeys leaves the discard and becomes a
#// resource (exhausted, no "ready it" wording). Net: still 2 resources (1 ready survivor +
#// exhausted Lackeys), the defeated SOR_046 in discard.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_107:1:1
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myResources-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_046

---

# WhenDefeated_Offer_EveryFriendlyResourceReadyOrExhausted
#// SHD_107 — the "defeat a friendly resource" pool is EVERY friendly resource, exhausted ones included
#// (the ability defeats a resource, it does not exhaust one). P1 holds one ready SOR_046 and one
#// exhausted SOR_095; both are offered. Decision left PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_107:1:1
WithP1Resources: 1:SOR_046:1,1:SOR_095:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myResources-0&myResources-1

---

# WhenDefeated_FizzlesWhenAlreadyResourcedByTheAttacker
#// SHD_107's "If you do" rider can fizzle: the unit must still be in the discard to be put into play as
#// a resource. P1's SHD_122 Arquitens Assault Cruiser (7/8 space) uses SHD_230 Swoop Down to reach the
#// ground (+2/+0 attacking a ground unit, defender -2/-0 → SHD_107 is 3/5) and defeats SHD_107. Arquitens'
#// own trigger puts the defeated SHD_107 into P1's resource zone FIRST, so when SHD_107's When Defeated
#// resolves for P2 the resource defeat still happens (P2 loses SOR_046) but the "put this into play as a
#// resource" half finds nothing to move — SHD_107 stays a P1 resource and P2 gains none.
#// P1 ends on 3 resources (2 seeded, 1 spent on Swoop Down, + the exhausted SHD_107) and Arquitens
#// takes the debuffed 3 counter-damage.

## GIVEN
CommonSetup: yyk/ggw
P1OnlyActions: true
WithP1Resources: 2:SOR_095:1
WithP1Hand: SHD_230
WithP1SpaceArena: SHD_122:1:0
WithP2GroundArena: SHD_107:1:0
WithP2Resources: 3:SOR_046:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain
- P2>AnswerDecision:myResources-0

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:1
P2RESCOUNT:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_046
P1SPACEARENAUNIT:0:DAMAGE:3
