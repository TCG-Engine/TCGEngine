# GrantsUnderworldToCreature
#// LAW_212 Malakili (2/4) — "Each friendly Creature unit ... gains the Underworld trait." SOR_164 is a
#// Creature but NOT natively Underworld; with Malakili in play it counts as Underworld, so LAW_249 Black
#// Sun Cabalist (When Played: give an Experience token to another friendly Underworld unit) can target
#// it. Choosing SOR_164 (the granted unit) makes it 5/6. Without the grant SOR_164 wouldn't be a legal
#// target and the choice would auto-resolve to Malakili instead — so SOR_164 ending at 5/6 proves the
#// grant works.
#// COVERAGE: offer=EnemyCreatureNotUnderworld_FriendlyOnlyGrant + ControlledEnemyCreature_GainsUnderworld
#//           (SELECTABLEEXACT over the granted set) · decline=N/A (the grant is a static ability with no
#//           "you may"; declines belong to the reader cards, guarded in their own files) ·
#//           control=ControlledEnemyCreature_GainsUnderworld (P1 controls a P2-owned Creature; the friendly
#//           in-play grant keys on the CONTROLLER) · boundary pair=OutOfPlay_PlayOwnedCreatureTriggersLadyProxima
#//           + OutOfPlay_NoMalakili_LadyProximaNoTrigger and OutOfPlay_DoctorAphraReturnsOwnedCreature +
#//           OutOfPlay_NoMalakili_NoReturn (with/without the grant) · request boundary=N/A (static aura,
#//           no decision-then-state-read spans a request; every reader flow is answered in one section)

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1GroundArena: SOR_164:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:6

---

# EnemyCreatureNotUnderworld_FriendlyOnlyGrant
#// LAW_212 Malakili — the grant applies only to Creatures you control; enemy Creatures do NOT gain
#// Underworld. P1 has Malakili (Underworld) + SOR_164 Wampa (friendly Creature, granted Underworld); P2 has
#// LOF_044 Loth-Wolf (enemy Creature). LAW_249 Black Sun Cabalist ("give Experience to another friendly
#// Underworld unit") can select exactly Malakili and Wampa — NOT the enemy Loth-Wolf. The target decision
#// stays pending to prove the enemy Creature is excluded from the granted-Underworld set. (Verified genuine:
#// without Malakili, Wampa loses the grant, leaving a single legal target that auto-resolves with no prompt.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [LAW_212:1:0 SOR_164:1:0]
WithP2GroundArena: LOF_044:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# OutOfPlay_DoctorAphraReturnsOwnedCreature
#// LAW_212 Malakili — "each Creature unit you own that isn't in play gains the Underworld trait." Doctor
#// Aphra (LAW_194) mills 3 Creatures you own from your deck; because Malakili is in play they all count as
#// Underworld, so Aphra's "return an Underworld card discarded this way" can return one to hand.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_212:1:0 LAW_194:1:0]
WithP1Deck: SOR_164
WithP1Deck: SHD_168
WithP1Deck: LOF_033

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# OutOfPlay_NoMalakili_NoReturn
#// LAW_212 control — WITHOUT Malakili in play, the milled Creatures are NOT Underworld, so Doctor Aphra
#// has nothing to return: all 3 stay in the discard and the hand stays empty.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_194:1:0
WithP1Deck: SOR_164
WithP1Deck: SHD_168
WithP1Deck: LOF_033

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:3

---

# OutOfPlay_PlayOwnedCreatureTriggersLadyProxima
#// LAW_212 Malakili — a Creature you own counts as Underworld WHEN PLAYED. P1 controls Malakili + Lady
#// Proxima (SHD_255, "When you play another Underworld card: deal 1 to a base"). Playing the Creature
#// SOR_164 Wampa (not printed Underworld) triggers Lady Proxima, dealing 1 to P2's base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: [LAW_212:1:0 SHD_255:1:0]
WithP1Hand: SOR_164

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1

---

# OutOfPlay_NoMalakili_LadyProximaNoTrigger
#// LAW_212 control — WITHOUT Malakili, the Wampa is a plain Creature (not Underworld), so playing it does
#// NOT trigger Lady Proxima; P2's base takes no damage.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SHD_255:1:0
WithP1Hand: SOR_164

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0

---

# InDeck_PlayedFromTopTriggersLadyProxima
#// LAW_212 Malakili — a Creature you own on top of your DECK is Underworld when played from there.
#// SOR_192 Ezra Bridger completes an attack and offers to play the top card; playing the Wampa
#// (SOR_164, cost 4, on-aspect) from the deck triggers SHD_255 Lady Proxima ("When you play another
#// Underworld card: you may deal 1 damage to a base") for 1 to P2's base (3 attack + 1 = 4).

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: [LAW_212:1:0 SHD_255:1:0 SOR_192:1:0]
WithP1Deck: SOR_164

## WHEN
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:Play
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:4
P1RESAVAILABLE:0

---

# ControlledEnemyCreature_GainsUnderworld
#// LAW_212 Malakili — the in-play grant is to FRIENDLY (controlled) Creatures, so an enemy-owned
#// Creature that P1 has taken control of DOES gain Underworld. P1 controls Malakili plus a P2-owned
#// LOF_044 Loth-Wolf; LAW_249 Black Sun Cabalist ("give Experience to another friendly Underworld
#// unit") can select exactly both of them — the offer stays pending with two legal targets, proving
#// the controlled enemy Creature is inside the granted set.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1GroundArenaControlled: LOF_044:2
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# EnemyPlayedCreature_NotGranted_NoProximaTrigger
#// LAW_212 Malakili — the grant never reaches cards the OPPONENT owns/plays. P1 has Malakili in play;
#// P2 controls Lady Proxima (SHD_255) and plays a Wampa (SOR_164, plain Creature). The Wampa is NOT
#// Underworld for P2, so Proxima's "when you play another Underworld card" does not trigger: no
#// decision, no base damage.

## GIVEN
CommonSetup: yyk/rrk/{myResources:0;theirResources:4}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_212:1:0
WithP2GroundArena: SHD_255:1:0
WithP2Hand: SOR_164

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2NODECISION
P1BASEDMG:0
P2BASEDMG:0

---

# GrantedUnderworld_DiscardReturnOfferIncludesCreature
#// The out-of-play grant must be visible to OTHER cards' trait filters, not just the play-observers.
#// LAW_261 Street Gang Recruiter's "return an Underworld card from your discard" offers an owned
#// Creature (SOR_164 Wampa, no printed Underworld) while Malakili is in play; the non-Creature
#// SOR_095 is not offered. Two candidates seeded so the may-offer's contents are assertable.
#// The offer is asserted while pending (no answer consumes it here).

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1Hand: LAW_261
WithP1Discard: [SOR_164 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_card
P1SELECTABLEEXACT:myDiscard-0

---

# GrantedUnderworld_DiscardReturnResolves
#// Continuation of the offer above: taking the granted-Underworld Wampa returns it to hand.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1Hand: LAW_261
WithP1Discard: [SOR_164 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_164
P1DISCARDCOUNT:1

---

# GrantedUnderworld_TraitShareEventPlaysHandCreature
#// TWI_225 "play a non-Vehicle unit from your hand that shares a Trait with the unit you control":
#// with Malakili as the only controlled unit (printed Underworld), an owned SOR_164 Wampa in HAND
#// shares Underworld via the out-of-play grant and gets played at -5 (4-5 -> free). The single
#// matching hand unit resolves without a prompt; the played Wampa lands in the ground arena.

## GIVEN
CommonSetup: yrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1Hand: [TWI_225 SOR_164]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1HANDCOUNT:0

---

# GrantedUnderworld_DeckSearchMatchesCreature
#// LOF_219 Psychometry: the chosen discard card (LAW_261, printed Underworld) must match an owned
#// Creature in the TOP-5 search via the out-of-play grant — SOR_164 Wampa in the deck counts as
#// Underworld while Malakili is in play.

## GIVEN
CommonSetup: yrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1Hand: LOF_219
WithP1Discard: [LAW_261]
WithP1Deck: [SOR_164 SOR_046 SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_164

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_164
P1DECKCOUNT:5
