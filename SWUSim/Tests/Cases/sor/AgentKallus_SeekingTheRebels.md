# DeclineDraw
#// SOR_115 Agent Kallus — the draw is optional ("You may"): declining draws nothing. Kallus kills an
#// enemy unique unit, the reactive offers a draw, P1 says NO → no card drawn.
#// COVERAGE: offer=AmbushTargetPool_EnemyUnitsInHisOwnArenaOnly (the Ambush attack target: friendly
#//           units, the other arena and the enemy base are all excluded with a fixture for each, two
#//           legal targets so nothing auto-resolves, decision left pending) · decline=DeclineDraw
#//           (the "you may draw" NO branch; the Ambush prompt's own NO branch is covered generically
#//           by the Ambush keyword suite) · control=ControlChanged_NewControllerDrawsFromTheirOwnDeck
#//           ("you" is Kallus's CONTROLLER, not his owner, and the observed unit is unqualified so a
#//           unique FRIENDLY to Kallus counts) · boundary pair=OncePerRound (two unique defeats in one
#//           round → exactly ONE draw, pinning "only once each round" at 1 rather than per-defeat)
#//           against UniqueDefeated_Draw (the single defeat), with UniqueDefeated_Draw vs
#//           NonUniqueDefeated_NoDraw and UniquePilotUpgradeDefeated_NoDraw as the gate's class pairs ·
#//           reqboundary=AmbushTargetPool_EnemyUnitsInHisOwnArenaOnly and
#//           UniquePilotUpgradeDefeated_NoDraw (play → Ambush yes/no → target, and play → target →
#//           reaction, are separate serialized requests in production) plus
#//           ControlChanged_NewControllerDrawsFromTheirOwnDeck, whose reaction is built on the
#//           non-active seat and only surfaces after that seat's drain.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_079:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:0

---

# NonUniqueDefeated_NoDraw
#// SOR_115 Agent Kallus — uniqueness gate: defeating a NON-unique unit does NOT trigger the draw.
#// Kallus (4/4) attacks a non-unique SOR_128 (3/1) and defeats it → no reactive, no draw, no decision.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:0
P1NODECISION

---

# OncePerRound
#// SOR_115 Agent Kallus — "Use this ability only once each round." Two enemy UNIQUE units are defeated
#// in the same round; Kallus draws only for the FIRST. Kallus (4/4) kills SOR_079 (1/4) → draw (YES);
#// then LAW_124 (4/7) kills SOR_109 (2/3) → no second offer. P1 drew exactly 1 (deck 2 → 1).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_109:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:1

---

# UniqueDefeated_Draw
#// SOR_115 Agent Kallus — "When another unique unit is defeated: You may draw a card." Kallus (4/4)
#// attacks an enemy UNIQUE unit (SOR_079, 1/4) and defeats it → the reactive offers P1 a draw → YES →
#// P1 draws 1. (Kallus takes 1 counter, survives.)

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_079:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:0
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# UniquePilotUpgradeDefeated_NoDraw
#// SOR_115 Agent Kallus — "When another unique UNIT is defeated." A unique PILOT riding a unit is an
#// UPGRADE, not a unit (CR 17.c), so defeating its host does not make the pilot a defeated unit:
#// P1 defeats the non-unique TIE/ln Fighter carrying the unique JTL_036 Iden Versio pilot with
#// SHD_079 Rival's Fall — the host is non-unique and the pilot is an upgrade, not a unit, so NO draw
#// offer appears at all.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaPilot: 0:JTL_036
WithP1Hand: SHD_079
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1NODECISION
P1HANDCOUNT:0
P1DECKCOUNT:1
P1DISCARDCOUNT:1

---

# AmbushTargetPool_EnemyUnitsInHisOwnArenaOnly
#// SOR_115 Agent Kallus — the OFFER axis, on the Ambush clause: "he may ready and attack an ENEMY
#// UNIT." Three exclusions have to hold at once and each has a fixture on the board: FRIENDLY units
#// are not attackable (P1's own Battlefield Marine), an enemy unit in the OTHER arena is out of reach
#// (P2's Alliance X-Wing in space, while Kallus is a Ground unit), and the enemy BASE is not a unit so
#// it is never in the pool. Two enemy ground units are seeded so the choice cannot auto-resolve.
#// P1 plays Kallus and accepts the Ambush; the attack-target decision is left PENDING — the offer is
#// the assertion, so no attack has happened and every unit is still undamaged.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:SOR_115}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# ControlChanged_NewControllerDrawsFromTheirOwnDeck
#// SOR_115 Agent Kallus — the CONTROL axis, plus the unqualified half of the trigger. "When another
#// unique unit is defeated: YOU may draw a card" names no controller on the observed unit, and "you"
#// is Kallus's CONTROLLER, not his owner. Kallus is OWNED by P1 but sits under P2's control (the end
#// state after a take-control effect). P1's Industrious Team kills P2's Admiral Piett — a unique unit
#// FRIENDLY to Kallus — and the reaction must belong to P2: P2 is the one offered the draw and P2
#// draws from P2's deck, while P1 (the owner) is offered nothing and draws nothing. The reaction is
#// queued on the non-active reactor's seat, so P2>Drain surfaces it before it is answered.

## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_079:1:0
WithP2GroundArenaControlled: SOR_115:1
WithP1Deck: [SOR_128 SOR_237]
WithP2Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P2>Drain
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2HANDCOUNT:1
P2DECKCOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2
