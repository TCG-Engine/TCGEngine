# Action_PlaysASevenCostUnitForTwoLess
#// IC27_057 Director Krennic, Architect of Annihilation — Unit (Ground) 0/4, cost 2, [Command][Villainy],
#// Imperial/Official, unique.
#// Text: Action [Exhaust]: Play a unit that costs 7 or more from your hand. It costs [2 resources] less.
#// (Spelling pass 2026-11-03: the mock read "7 or LESS"; the printed card says "7 or MORE" — fixed in CardMocks.php.)
#// COVERAGE: offer=Offer_SevenOrMoreUnitsOnly (cost-6 unit, a cost-7 EVENT and an off-aspect cost-6 unit excluded;
#//           cost 7 and 8 offered) · decline=Decline_KrennicStillExhausted (play-from-hand is always declinable) ·
#//           no target=NoSevenCostUnit_StillExhausts (CR 6.4.587.c: the [Exhaust] cost changes game state, so the Action
#//           is usable and resolves to nothing) · boundary=this (cost 7, exactly 5 resources) + Offer_* (6 excluded) +
#//           TooFewResources_NotOffered (4 resources: 7 − 2 = 5 unaffordable) · quantity=EightCost_PaysSix (exactly −2) ·
#//           printed cost=OffAspectSeven_PaysThePenalty (the threshold reads PRINTED cost; the penalty still applies) ·
#//           payment=Credits_CountTowardTheDiscountedCost · turn=TurnPassesToTheOpponent (no P1OnlyActions) ·
#//           blanked=Blanked_NoAction · control=Control_StolenKrennic_PlaysFromTheControllersHand ·
#//           reqboundary=RequestBoundary_BeforeThePick · modes=2P only ("your hand" — self in every format).
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Modelled on SOR_093 Alliance Dispatcher ("Action
#// [Exhaust]: Play a unit from your hand. It costs 1 less"). Unlike the Dispatcher (whose availability gate predates the
#// CR 6.4.587.c sweep), Krennic is NOT gated on having a playable unit: the exhaust is a state-changing cost.
#//
#// THE POSITIVE. Shadow Crawler (SEC_121, Command, cost 7) costs 5 through Krennic: 5 resources → 0 left, Krennic
#// exhausted. No enemy units, so its Ambush has nothing to prompt about.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;myhandCardIds:SEC_121}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SEC_121
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1NODECISION

---

# Offer_SevenOrMoreUnitsOnly
#// The pool is hand UNITS with printed cost 7 or more. Hand: Hyperspace Wayfarer (unit, 6), Shadow Crawler (unit, 7),
#// Beach Patrol AT-ACT (unit, 8), Choose Sides (EVENT, 7), Falchion Ion Tank (Vigilance unit, 6 — 8 with the aspect
#// penalty, still a cost-6 card). Only the 7 and the 8 are offered.
## GIVEN
CommonSetup: ggk/ggw/{myResources:12;myhandCardIds:LOF_119,SEC_121,LAW_162,SHD_132,TWI_065}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-1&myHand-2

---

# EightCost_PaysSix
#// Exactly 2 less: Beach Patrol AT-ACT (8) with 6 resources → 0 left.
## GIVEN
CommonSetup: ggk/ggw/{myResources:6;myhandCardIds:LAW_162}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_162
P1RESAVAILABLE:0

---

# OffAspectSeven_PaysThePenalty
#// Coastal Catamarans (HMW_093, Vigilance, printed 7) qualifies on its PRINTED cost; the +2 aspect penalty is still paid:
#// 7 + 2 − 2 = 7, so 7 resources → 0 left.
## GIVEN
CommonSetup: ggk/ggw/{myResources:7;myhandCardIds:HMW_093}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_093
P1RESAVAILABLE:0

---

# TooFewResources_NotOffered
#// 4 resources: Shadow Crawler at 7 − 2 = 5 is unaffordable, so there is nothing to play. The Action still resolves
#// (exhausting Krennic) and plays nothing.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:SEC_121}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1RESAVAILABLE:4

---

# NoSevenCostUnit_StillExhausts
#// Only a cost-6 unit in hand: CR 6.4.587.c — the Action is usable (its [Exhaust] cost changes the game state) and
#// resolves to nothing. Krennic is exhausted, the Wayfarer stays in hand.
## GIVEN
CommonSetup: ggk/ggw/{myResources:10;myhandCardIds:LOF_119}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1RESAVAILABLE:10

---

# Decline_KrennicStillExhausted
#// Play-from-hand is ALWAYS declinable (the hand is hidden). Declining keeps both units in hand and the resources
#// untouched, but the Action's cost was paid: Krennic is exhausted.
## GIVEN
CommonSetup: ggk/ggw/{myResources:10;myhandCardIds:SEC_121,LAW_162}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:1
P1HANDCOUNT:2
P1RESAVAILABLE:10

---

# Credits_CountTowardTheDiscountedCost
#// Payment capacity, not bare resources: 4 resources + 1 Credit token can pay Shadow Crawler's 5, so it is offered.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:SEC_121}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0

---

# TurnPassesToTheOpponent
#// The Action closes exactly once: without P1OnlyActions the turn passes to P2 after the nested play, and no second
#// close is attempted.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;myhandCardIds:SEC_121}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_121
TURNPLAYER:2
NOEXTRAACTION

---

# Blanked_NoAction
#// A Krennic that has lost its abilities (SOR_138 Force Lightning marker) has no Action: nothing happens and he stays
#// ready.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;myhandCardIds:SEC_121}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0:SOR_138
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1

---

# Control_StolenKrennic_PlaysFromTheControllersHand
#// "your hand" is the CONTROLLER's: a Krennic P2 owns but P1 controls offers P1's Shadow Crawler (and not P2's AT-ACT),
#// and the unit lands in P1's arena.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;myhandCardIds:SEC_121;theirhandCardIds:LAW_162}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: IC27_057:2
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_121
P1HANDCOUNT:0
P2HANDCOUNT:1
P1RESAVAILABLE:0

---

# RequestBoundary_BeforeThePick
#// The discount rides the decision, not a PHP global: answered in a later request, the Crawler still costs 5.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;myhandCardIds:SEC_121}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_057:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_121
P1RESAVAILABLE:0
