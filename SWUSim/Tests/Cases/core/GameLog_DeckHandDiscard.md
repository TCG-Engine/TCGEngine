# Draw_PublicCount_PrivateCards
#// Game-log sweep, phase 3a — draws, discards, mills (2026-09-11). A draw is logged as a PUBLIC count
#// ("P1 drew 1 card (Patrolling V-Wing)") plus a line only the drawer can see naming the cards — the log
#// entry's visibility is the seat tag, which GetNextTurn.php filters per viewer (user decision:
#// "public count, private cards"). SOR_111 Patrolling V-Wing (Command, 2): "When Played: Draw a card."

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_111
WithP1Deck: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 drew 1 card ([[SOR_111|Patrolling V-Wing]])
P1LOGSEES:You drew [[SOR_095|Battlefield Marine]]
P2LOGNOTSEES:You drew
P2LOGSEES:P1 drew 1 card

---

# RegroupDraw_NoSource
#// The regroup draw belongs to no ability — no "(…)" suffix, even right after a card was played.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_111
WithP1Deck: [SOR_095 SEC_080 SOR_046 SOR_128]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LOGCONTAINS:P1 drew 2 cards
LOGCONTAINS:P2 drew 2 cards
LOGCOUNT:0:drew 2 cards (

---

# Garindan_DiscardsTheNamedCard
#// The rest of the reported Garindan line: the discard of the named card is logged, attributed to
#// Garindan and naming whose hand it left. (All 14 hand-rolled DISCARD log lines were removed in favour
#// of ONE line in SWUAddToDiscard, the funnel every one of them used — LOGCOUNT pins "no duplicate".)

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_186
WithP2Hand: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P2HANDCOUNT:1
LOGCONTAINS:P1's [[SEC_186|Garindan]] discarded [[SOR_095|Battlefield Marine]] from P2's hand
LOGCOUNT:1:discarded

---

# LothalInsurgent_RandomDiscard_KeepsTheAtRandomNote
#// SOR_190 Lothal Insurgent (Cunning/Heroism, 2): "When Played: If you played another card this phase, each
#// opponent draws a card then discards a random card." Confiscate (1) is played first to satisfy the gate.
#// The random-discard sites leave a one-shot note the central line appends. P2 draws their only deck card
#// and discards it — its identity is public on the discard line, private on the draw line.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: [SOR_251 SOR_190]
WithP2Deck: SEC_080

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:0
LOGCONTAINS:P2 drew 1 card ([[SOR_190|Lothal Insurgent]])
LOGCONTAINS:P1's [[SOR_190|Lothal Insurgent]] discarded [[SEC_080|Imperial Dark Trooper]] from P2's hand (at random)
P2LOGSEES:You drew [[SEC_080|Imperial Dark Trooper]]
P1LOGNOTSEES:You drew

---

# Mill_JarJar_FromTheirOwnDeck
#// IC27_187 Jar Jar Binks (Heroism, 1/5): "On Attack: Discard a card from your deck." A mill is a 'DECK'
#// discard; the owner is the source's player, so it reads "P1 discarded X from their deck (Jar Jar Binks)".

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_187:1:0
WithP1Deck: [SOR_095 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:1
LOGCONTAINS:P1 discarded [[SOR_095|Battlefield Marine]] from their deck ([[IC27_187|Jar Jar Binks]])

---

# PursueTheLead_SelfChosenDiscard
#// SEC_178 Pursue the Lead (Aggression, 2): "Choose a player. That player discards a card from their hand."
#// Choosing YOURSELF discards through DoDiscardCard — the self-chosen path that moves the card directly and
#// never reaches SWUAddToDiscard — so it has its own line. (The played event still occupies myHand-0 while it
#// resolves, so the Marine is offered as myHand-1.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: [SEC_178 SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myHand-1

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 discarded [[SOR_095|Battlefield Marine]] ([[SEC_178|Pursue the Lead]])
LOGCOUNT:1:discarded

---

# PlayingAnEvent_IsNotADiscard
#// An event resolves to its owner's discard through the same funnel with from='HAND'. Playing a card is not
#// discarding it — the central line reuses the existing gPlayingEventCardID guard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
LOGCOUNT:0:discarded

---

# RegroupDraw_NoSource_EvenAfterARegroupStartTrigger
#// The case RegroupDraw_NoSource could not see: a trigger resolving at the START of regroup sets a source
#// after RegroupPhaseStart has cleared it, and the draw that follows used to inherit it ("P1 drew 2 cards
#// (Ruthless Raider)"). Each regroup STEP now clears again. SOR_219 Sneak Attack plays SOR_134 Ruthless
#// Raider for 3 less; at regroup Sneak Attack defeats it, and its When Defeated (2 to an enemy base; no enemy
#// unit to hit) resolves with Raider as the source — immediately before the draw.

## GIVEN
CommonSetup: yrk/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: [SOR_219 SOR_134]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:0
LOGCONTAINS:dealt 2 damage to P2's base
LOGCONTAINS:P1 drew 2 cards
LOGCOUNT:0:drew 2 cards (
