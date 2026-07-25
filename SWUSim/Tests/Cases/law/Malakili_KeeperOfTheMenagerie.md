# GrantsUnderworldToCreature
#// LAW_212 Malakili (2/4) — "Each friendly Creature unit ... gains the Underworld trait." SOR_164 is a
#// Creature but NOT natively Underworld; with Malakili in play it counts as Underworld, so LAW_249 Black
#// Sun Cabalist (When Played: give an Experience token to another friendly Underworld unit) can target
#// it. Choosing SOR_164 (the granted unit) makes it 5/6. Without the grant SOR_164 wouldn't be a legal
#// target and the choice would auto-resolve to Malakili instead — so SOR_164 ending at 5/6 proves the
#// grant works.

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
