# WhenDefeatedResourceSelf
#// LAW_159 Expendable Mercenary (3/3) — When Defeated: you may resource this unit from its owner's
#// discard pile. Attacks SOR_046 (3/7) and dies; it returns as a resource (exhausted — the text has no
#// "and ready it" rider). P1 started with 0 resources -> 1 (exhausted).
#// AUTO-RESOLVES: no prompt is raised, matching SOR_083/SHD_085 Superlaser Technician. P1NODECISION is
#// the guard that keeps the ramp family consistent — a prompt reappearing here is the regression.

## GIVEN
CommonSetup: ggw/bgw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:0

---

# DefeatedUnderEnemyControl_ResourcedByThatController
#// "…from its OWNER'S discard pile" — when the unit is defeated while an OPPONENT controls it, the card
#// goes to its OWNER's discard but the When Defeated belongs to whoever controlled it at the moment of
#// defeat. P2 plays JTL_043 No Glory, Only Results (take control of a non-leader unit, then defeat it):
#// the mercenary is resourced into P2's row, not P1's. P1 ends with 0 resources and an empty discard;
#// P2's only discard is the event itself.
#// (The take-control target auto-resolves — the mercenary is the only non-leader unit in play — and the
#// When Defeated now auto-resolves too, so the whole sequence needs no answers at all.)

## GIVEN
CommonSetup: ggw/bbk/{myResources:0; theirResources:10}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: LAW_159:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0

## EXPECT
P1RESCOUNT:0
P2RESCOUNT:11
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0

---

# AnotherEffectResourcesItFirst_NoDoubleResource
#// The ability resources the card FROM the discard, so it can only fire if the card is still there.
#// P2 plays SHD_230 Swoop Down to let SHD_122 Arquitens Assault Cruiser (7/8) attack the ground and
#// defeat the mercenary; Arquitens' own "put the defeated unit into play as a resource under YOUR
#// control" moves it to P2 first. P1's When Defeated then finds nothing to move and bails out silently,
#// so the card is resourced exactly ONCE, to P2 — P1 gains nothing and its discard stays empty.
#// The resource counts ARE the no-double-resource proof here. This section deliberately does NOT assert
#// P1NODECISION: a trigger continuation (no tooltip, no candidates) is still queued at this point, and
#// P1NODECISION cannot tell that apart from a stray player prompt. The auto-resolve guard lives in
#// WhenDefeatedResourceSelf above, where the queue is genuinely empty.

## GIVEN
CommonSetup: ggw/yyk/{myResources:0; theirResources:10}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: LAW_159:1:0
WithP2SpaceArena: SHD_122:1:0
WithP2Hand: SHD_230

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESCOUNT:0
P2RESCOUNT:11
P1DISCARDCOUNT:0

---

# UsedByChimaeraWhileStillInPlay_DoesNothing
#// JTL_039 Chimaera, Reinforcing the Center ("When Played: you may use a 'When Defeated' ability on
#// another friendly unit") can point at the mercenary, but the ability resources the card FROM THE
#// DISCARD and the mercenary is still on the board — so it is a clean no-op: nobody gains a resource
#// and the mercenary stays in the ground arena.

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP1Hand: JTL_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESCOUNT:8
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_159
P1SPACEARENACOUNT:1

---

# OfferPool_ChimaeraOffersAnyFriendlyWhenDefeatedHolder
#// LAW_159 Expendable Mercenary — RE-VERIFICATION of the one residual scenario, the offer-legality
#// nicety behind UsedByChimaeraWhileStillInPlay_DoesNothing: JTL_039 Chimaera ("When Played: you may use
#// a 'When Defeated' ability on another friendly unit") OFFERS the mercenary even though the mercenary's
#// ability resources the card FROM THE DISCARD and it is still on the board, so the use is a guaranteed
#// no-op. Re-checked against the current code: the premise still holds — the pool admits any other
#// friendly unit that HAS a When Defeated ability (innate or granted) and does not additionally ask
#// whether that ability could change the game state.
#// Judged CORRECT and left as-is: "another friendly unit" plus "has a When Defeated ability" are the
#// printed criteria, and the mercenary meets both; whether the ability then finds anything to do is a
#// resolution question, not a targeting one. The residual therefore stays valid as a presentation-only
#// difference — but it is now PINNED by an assertion rather than left as prose, which is what this
#// section adds.
#// Discriminating board: LAW_159 (friendly, has a When Defeated → IN), SOR_095 Battlefield Marine
#// (friendly, NO When Defeated → OUT), SOR_204 Greedo (has a When Defeated but is an ENEMY → OUT), and
#// Chimaera itself, which has its own When Defeated and is excluded by "another" → OUT. Pool must be
#// exactly the mercenary. The MZMAYCHOOSE still prompts on a single target (it must be declinable), so
#// the pick is left UNANSWERED and the pool is read directly.
#// COVERAGE: offer=OfferPool_ChimaeraOffersAnyFriendlyWhenDefeatedHolder (a no-When-Defeated friendly,
#//           an enemy When-Defeated holder and the source itself are all the "out") ·
#//           reqboundary=NOT COVERED · control=DefeatedUnderEnemyControl_ResourcedByThatController ·
#//           boundary pair=WhenDefeatedResourceSelf (card is in the discard → resourced) vs
#//           AnotherEffectResourcesItFirst_NoDoubleResource and UsedByChimaeraWhileStillInPlay_Does
#//           Nothing (card is NOT in the discard → silent no-op) · decline=N/A by user ruling
#//           2026-08-11 (the ramp family auto-resolves; the guard is P1NODECISION in
#//           WhenDefeatedResourceSelf, and re-adding a prompt here is the regression)

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_204:1:0
WithP1Hand: JTL_039

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0
P1SPACEARENAUNIT:0:CARDID:JTL_039
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:0:CARDID:SOR_204
