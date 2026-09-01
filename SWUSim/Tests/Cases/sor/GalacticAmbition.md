# NoNonHeroismUnit_Fizzles
#// SOR_235 Galactic Ambition — guard: the only other card in hand is a [Heroism] unit (SOR_095), which
#// is NOT a legal target → the event fizzles: no free play, no self-base damage, SOR_095 stays in hand.
#// COVERAGE: offer=Offer_NonHeroismUnitsOnly_HeroismUnitAndEventExcluded (pending SELECTABLEEXACT
#//           over the hand; a Heroism unit and an Event are the excluded cards) ·
#//           reqboundary=RequestBoundary_FreePlayAndSelfDamageSurviveTheBoundary (the chosen unit's
#//           PRINTED cost must be captured before it leaves the hand) ·
#//           control=CrossPlayer_P2PlaysIt_P2sOwnBaseTakesTheDamage — "your base"/"your hand" are the
#//           RESOLVER's, not seat 1's; there is no owner-vs-controller reading here because the only
#//           zone the card touches is the resolver's own hand, and a card in hand has no controller
#//           distinct from its owner · boundary pair=PlayFreeUnit_SelfBaseDamage (cost 2 → 2 self
#//           damage) vs CostFive_BaseTakesFive_ThePlayIsStillFree (cost 5 → 5), plus the zero-target
#//           edge NoNonHeroismUnit_Fizzles · decline=Declined_NoFreePlayAndNoSelfBaseDamage — RED,
#//           see below.
#// ⚠ Declined_NoFreePlayAndNoSelfBaseDamage is RED on purpose: it encodes the standing SWUSim ruling
#// that a play FROM YOUR HAND is always declinable, and SOR_235 currently queues a MANDATORY MZCHOOSE
#// (and, with a single legal unit, a forced PASSPARAMETER with no prompt at all), so the player cannot
#// refuse to take the self-base damage. The sibling "play a unit from your hand" card SOR_022 Energy
#// Conversion Lab queues MZMAYCHOOSE for the same shape. Left red as the signal.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_235
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0
P1HANDCOUNT:1

---

# PlayFreeUnit_SelfBaseDamage
#// SOR_235 Galactic Ambition (Event, cost 7, Villainy) — "Play a non-[Heroism] unit from your hand for
#// free. Deal damage to your base equal to its cost." P1 plays Galactic Ambition (cost 7 → 0 left),
#// then plays the only non-Heroism unit in hand (SEC_080, cost 2) FREE → it enters the ground arena and
#// P1's OWN base takes 2 (its printed cost). Hand ends empty.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_235
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
#// ⚠ One added answer, assertions untouched. SOR_235's hand-play offer is now a declinable
#// MZMAYCHOOSE (play-from-hand is always declinable — the hand is a hidden zone), so the lone
#// legal unit no longer AUTO-RESOLVES and the pick has to be made explicitly.
#// ⚠ During the event's own resolution the event card still occupies myHand-0, so the unit is
#// myHand-1; the hand only compacts afterwards.
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1BASEDMG:2
P1HANDCOUNT:0

---

# Offer_NonHeroismUnitsOnly_HeroismUnitAndEventExcluded
#// Intended: "Play a NON-[Heroism] UNIT from your hand for free" — two filters on the hand pool.
#// After Galactic Ambition leaves the hand it holds the Dark Trooper (Command/Villainy) and the
#// Cantina Bouncer (Cunning/Cunning) — both legal — plus the Battlefield Marine (Command/HEROISM,
#// excluded by aspect) and Shoot First (an EVENT, excluded by card type). Two legal targets keep the
#// pick interactive, so the decision is left PENDING and the offer itself is the assertion.
#// Indexing note: Galactic Ambition itself still occupies myHand-0 while its own ability resolves
#// (the hand compacts only after the event finishes), so the two legal units are myHand-1/myHand-2.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: [SOR_235 SEC_080 SOR_202 SOR_095 SOR_217]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-1&myHand-2

---

# CostFive_BaseTakesFive_ThePlayIsStillFree
#// SOR_235 — "Deal damage to your base equal to ITS COST" scales with the chosen unit, and the play
#// itself is free. Boundary pair against PlayFreeUnit_SelfBaseDamage (cost 2 → 2 damage): here the
#// cost-5 Cantina Bouncer is chosen, so P1's OWN base takes 5. Galactic Ambition's own cost 7 empties
#// the resources, and the Bouncer still enters play with 0 resources left — proof it cost nothing.
#// (The event still sits at myHand-0 while it resolves, so the Bouncer is myHand-2.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: [SOR_235 SEC_080 SOR_202]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_202
P1BASEDMG:5
P2BASEDMG:0
P1RESAVAILABLE:0
P1HANDCOUNT:1
P1HANDCARD:0:SEC_080

---

# RequestBoundary_FreePlayAndSelfDamageSurviveTheBoundary
#// SOR_235 — with two legal units in hand the pick is a real prompt, and in production that prompt
#// ends the request: the answer arrives in a fresh process. The chosen unit's PRINTED COST must have
#// been captured before the card left the hand, so the self-base damage still reads 5 after the
#// round-trip and the unchosen Dark Trooper stays in hand. The boundary also COMPACTS the hand (the
#// event has left it by then), so the Bouncer answers as myHand-1 here where it is myHand-2 in the
#// single-process sections — the pick must be re-resolved against the serialized state, not a
#// remembered index.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: [SOR_235 SEC_080 SOR_202]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_202
P1BASEDMG:5
P1HANDCOUNT:1
P1HANDCARD:0:SEC_080

---

# CrossPlayer_P2PlaysIt_P2sOwnBaseTakesTheDamage
#// SOR_235 — "YOUR base" is the base of the player RESOLVING the event, not a hardcoded seat. P2
#// plays Galactic Ambition and the free Dark Trooper (cost 2): the unit enters P2's ground arena and
#// P2's OWN base takes 2 while P1's base is untouched. Also proves the hand searched is the
#// resolver's hand.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:7; theirhandCardIds:SOR_235,SEC_080}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true

## WHEN
- P2>PlayHand:0
#// ⚠ One added answer, assertions untouched. SOR_235's hand-play offer is now a declinable
#// MZMAYCHOOSE (play-from-hand is always declinable — the hand is a hidden zone), so the lone
#// legal unit no longer AUTO-RESOLVES and the pick has to be made explicitly.
#// ⚠ During the event's own resolution the event card still occupies myHand-0, so the unit is
#// myHand-1; the hand only compacts afterwards.
- P2>AnswerDecision:myHand-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2BASEDMG:2
P1BASEDMG:0
P1GROUNDARENACOUNT:0

---

# Declined_NoFreePlayAndNoSelfBaseDamage
#// Per the standing SWUSim ruling that a play FROM YOUR HAND is always declinable (the hand is a
#// hidden zone, so a player can never be forced to reveal they held a playable card), Galactic
#// Ambition's free play must be offered as a declinable pick even though the printed text carries no
#// "you may". Declining leaves both units in hand, the ground arena empty and — crucially — P1's own
#// base UNDAMAGED, since the damage clause is "equal to its cost" and there is no "it".
#// Galactic Ambition itself is still paid for and discarded.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: [SOR_235 SEC_080 SOR_202]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0
P1HANDCOUNT:2
P1DISCARDCOUNT:1
