# Support_ReadyResource
#// COVERAGE: offer=N/A (both clauses are non-targeting — Support's attacker choice is asserted by the
#//                 lend section, and "ready a resource" picks no target) ·
#//           decline=N/A (neither clause is optional; Support's "you may attack" declines to a no-op that
#//                 the keyword file already covers) ·
#//           boundary=OwnAttack_ReadiesAResource vs OwnAttack_NoExhaustedResource (1 exhausted vs 0) ·
#//           control=N/A (no owner-scoped zone; the readied resource is the attacker's controller's) ·
#//           reqboundary=N/A (the trigger resolves inside the attack it fires on)
#// ASH_189 Emperor's Messenger (Ground, 0/3, Support) — the On Attack "ready a resource" is lent to the
#// Support attacker. Messenger is played from hand; the friendly Wampa (SOR_164) is chosen to attack and
#// gains the lent On Attack, readying one of P1's exhausted resources.
## GIVEN
CommonSetup: yyk/yyk/{handCardIds:ASH_189}
WithP1Resources: 1:SOR_046:1,2:SOR_046:1,3:SOR_046:0
WithP1GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1RESAVAILABLE:3
P2BASEDMG:4

---

# OwnAttack_ReadiesAResource
#// ASH_189 — the On Attack clause on the Messenger's OWN attack, which the Support section above never
#// exercises (there the ability is LENT to a different unit and the Messenger never attacks).
#// Seated ready with 1 of 3 resources exhausted; it attacks the base and readies that resource.
#// Its printed power is 0, so P2's base takes nothing — which is itself the tell that the resource ready
#// came from the trigger and not from some damage-driven side effect.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 2:SOR_046:1,1:SOR_046:0
WithP1GroundArena: ASH_189:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1RESAVAILABLE:3
P1RESCOUNT:3
P2BASEDMG:0

---

# OwnAttack_NoExhaustedResource_ReadiesNothingAndStillAttacks
#// ASH_189 — the no-op boundary. "Ready a resource" with every resource already ready has nothing to do,
#// and it must not fizzle the attack or leave a dangling prompt.
#// Pairs with the section above: 1 exhausted → 3 ready, 0 exhausted → still 3 ready.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 3:SOR_046:1
WithP1GroundArena: ASH_189:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1RESAVAILABLE:3
P1RESCOUNT:3
P1NODECISION

---

# Support_LendsTheAbility_TheMessengerItselfDoesNotAttack
#// ASH_189 — Support LENDS the On Attack to the chosen attacker; the Messenger itself never attacks.
#// The Wampa (SOR_164, 4 power) deals the 4 and the LENT trigger readies the resource — so the base
#// damage is the Wampa's alone and the Messenger is simply present on the board.
#// ⚠ Do NOT assert the Messenger is READY here: in SWUSim a unit played this turn enters EXHAUSTED (a
#// plain vanilla unit does the same), so a ready-check tests the play convention, not Support.

## GIVEN
CommonSetup: yyk/yyk/{handCardIds:ASH_189}
P1OnlyActions: true
WithP1Resources: 1:SOR_046:1,2:SOR_046:1,3:SOR_046:0
WithP1GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1RESAVAILABLE:3
P1GROUNDARENAUNIT:1:CARDID:ASH_189
P1GROUNDARENACOUNT:2
