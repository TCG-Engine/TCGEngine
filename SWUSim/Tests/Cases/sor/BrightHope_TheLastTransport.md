# WhenPlayed_Decline_NoReturn
#// SOR_099 Bright Hope — the return is optional ("You may"). Declining means no unit is
#// returned and NO card is drawn. The friendly ground unit stays, hand holds only what's left
#// after playing Bright Hope (0), and the deck is untouched.
#// COVERAGE: offer=Offer_FriendlyGroundOnly_NoSpaceNoEnemyNoLeader (pending SELECTABLEEXACT) ·
#//           decline=WhenPlayed_Decline_NoReturn (and the "if you do" rider correctly withholds the
#//           draw) · reqboundary=WhenPlayed_ReturnGroundDraw (the return answer arrives on a later
#//           request than the play) · boundary pair=WhenPlayed_ReturnGroundDraw (return → draw) +
#//           WhenPlayed_Decline_NoReturn (no return → no draw), plus the token variant
#//           ReturnTokenGroundUnit_TokenCeases_StillDraws (returned-token cease still counts as
#//           "if you do") · control=StolenGroundUnit_ReturnsToItsOWNERSHand_AndYouStillDraw — the
#//           earlier "N/A, one-shot When Played" reading missed the OWNER half: the card prints
#//           "to its OWNER'S hand", so a unit P1 merely CONTROLS is in the pool but returns to P2's
#//           hand, while the "if you do" draw still goes to the controller ·
#//           no-valid-target=NoFriendlyGroundUnitAtAll_NothingIsOfferedAndNoCardIsDrawn ·
#//           keyword clause=Sentinel_EnemySpaceAttackerCannotReachTheBase paired with
#//           Sentinel_ProtectsOnlyItsOwnArena_GroundAttackGoesThrough

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# WhenPlayed_ReturnGroundDraw
#// SOR_099 Bright Hope (2/6, Space, Sentinel) — When Played: You may return a friendly
#// non-leader GROUND unit to its owner's hand. If you do, draw a card. P1 returns its
#// Battlefield Marine and draws. Net hand: played Bright Hope (-1), Marine back (+1), draw (+1)
#// = 2; deck -1; the ground arena is emptied.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0    # friendly non-leader ground unit — returned
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:1

---

# Offer_FriendlyGroundOnly_NoSpaceNoEnemyNoLeader
#// Intended: "return a friendly non-leader GROUND unit" — the pool is exactly P1's two ordinary
#// ground units. Excluded: P1's own space unit (wrong arena), the enemy Wampa (not friendly), the
#// deployed leader unit (non-leader only), and Bright Hope itself (space). The decision is left
#// pending so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# ReturnTokenGroundUnit_TokenCeases_StillDraws
#// Intended (per CR — a token that leaves play by a non-defeat route is removed from the game, but
#// still counts as having been returned to hand): returning the friendly Clone Trooper TOKEN makes
#// it cease — it never reaches the hand and is not in the discard — yet the "if you do" rider is
#// satisfied and a card is still drawn. Hand ends with exactly the drawn card.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: TWI_T02:1:0
WithP1Deck: [SOR_232 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DISCARDCOUNT:0
P1NODECISION

---

# Sentinel_EnemySpaceAttackerCannotReachTheBase
#// Intended: "Sentinel (Units in this arena can't attack your non-Sentinel units or your base.)" — the
#// keyword clause, which none of the When Played sections touch. P2's Alliance X-Wing (2/3) declares an
#// attack on P1's base while Bright Hope (2/6, Sentinel) holds the space arena: the base takes nothing,
#// Bright Hope absorbs the 2, and the X-Wing takes Bright Hope's 2 back and survives on 3 HP.

## GIVEN
CommonSetup: ggw/ggw
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: SOR_099:1:0     # Bright Hope (Sentinel, 2/6)
WithP2SpaceArena: SOR_237:1:0     # Alliance X-Wing 2/3

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:DAMAGE:2

---

# Sentinel_ProtectsOnlyItsOwnArena_GroundAttackGoesThrough
#// Intended: "Units in THIS arena" — Sentinel is arena-scoped, and Bright Hope is a SPACE unit. The
#// load-bearing negative of the section above: the same Bright Hope is in space, but P2's attacker is a
#// Battlefield Marine (3/3) on the GROUND, so nothing stops it and P1's base takes the full 3 while
#// Bright Hope is not even damaged. A Sentinel implemented board-wide would still pass the positive.

## GIVEN
CommonSetup: ggw/ggw
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: SOR_099:1:0     # Bright Hope (Sentinel) — SPACE
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine 3/3 — GROUND

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1SPACEARENAUNIT:0:DAMAGE:0

---

# StolenGroundUnit_ReturnsToItsOWNERSHand_AndYouStillDraw
#// Intended: the card says "return a friendly non-leader ground unit to ITS OWNER'S hand" — friendly is
#// decided by CONTROL, but the destination is the OWNER's hand. P1 controls a Battlefield Marine that
#// P2 still OWNS: it is in P1's pool and P1 may return it, yet the card lands in P2's hand, not P1's.
#// P1's own hand therefore ends with just the drawn card (Bright Hope was played out of it), which is
#// also the proof that the "If you do, draw a card" rider still fired for the CONTROLLER.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArenaControlled: SOR_095:2    # P1 CONTROLS it, P2 OWNS it
WithP1Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1DECKCOUNT:1
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095

---

# NoFriendlyGroundUnitAtAll_NothingIsOfferedAndNoCardIsDrawn
#// Intended no-valid-target case: Bright Hope is a SPACE unit, so it can be played with an empty ground
#// arena and there is then nothing its When Played could return. Neither P1's own space unit nor the
#// enemy's ground unit is a legal target, so no prompt is raised at all — and because nothing was
#// returned, the "If you do" rider withholds the draw exactly as the explicit decline does. Deck and
#// hand are both untouched and no decision is left pending.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1SpaceArena: SOR_237:1:0     # friendly, but SPACE — not a legal target
WithP2GroundArena: SOR_095:1:0    # ground, but ENEMY — not a legal target
WithP1Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DECKCOUNT:2
P1SPACEARENACOUNT:2
P2GROUNDARENACOUNT:1
