# ExhaustGive2Exp
#// LAW_165 Combat Exercise (Command event, cost 1) — "Exhaust a friendly unit. If you do, give 2
#// Experience tokens to it." Single ready friendly (SOR_095 3/3) -> auto-target -> exhausted, +2/+2.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_165

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# NoFriendlyUnit_NoEffect
#// LAW_165 Combat Exercise — with no friendly unit to exhaust, the event has no legal effect. It is still
#// played and goes to the discard pile (nothing else happens).

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
P1OnlyActions: true
WithP1Hand: LAW_165

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:0

---

# AlreadyExhausted_NoExperience
#// LAW_165 Combat Exercise — "Exhaust a friendly unit. If you do, give 2 Experience tokens to it." The
#// Experience is gated on actually exhausting. Targeting an already-exhausted SOR_095 (seated exhausted)
#// exhausts nothing, so NO Experience tokens are added — it stays 3/3 with no upgrades.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1GroundArena: SOR_095:0:0
WithP1Hand: LAW_165

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OfferPool_FriendlyReadyUnitsOnly
#// LAW_165 Combat Exercise — "Exhaust A FRIENDLY unit. If you do, give 2 Experience tokens to it." The
#// pool has to be friendly-only, and an already-exhausted unit cannot pay the exhaust, so it is not a legal
#// choice either. Discriminating board: two READY friendly units (SOR_095 ground, SOR_178 space — the text
#// names no arena) are IN; the friendly EXHAUSTED SEC_080 is OUT; and the ready ENEMY SOR_046 is OUT on
#// controller. Two legal targets keep the choose genuinely pending — ExhaustGive2Exp has one and
#// auto-resolves.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:0:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_165

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
P1GROUNDARENAUNIT:1:CARDID:SEC_080
