# PlayHeroismUnitWithExp
#// LAW_144 Phantom (Command,Heroism, cost 2) — When Played: you may play a Heroism unit from your hand
#// (paying its cost) and give an Experience token to it. Play SOR_095 (Command,Heroism, 3/3) -> enters
#// with 1 Experience (4/4).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP1Hand: SOR_095
WithP1Hand: LAW_144

## WHEN
- P1>PlayHand:1
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4

---

# OfferIsOnlyHeroismUnitsFromHand
#// LAW_144's When Played offers exactly the HEROISM units in hand. Hand after Phantom is played:
#// SOR_095 Battlefield Marine (Command/Heroism), SOR_241 Wing Leader (Heroism), SOR_164 Wampa
#// (Aggression) — only the first two are selectable. The choice is left pending so the offer is what's
#// asserted; two candidates also keep it from auto-resolving.

## GIVEN
CommonSetup: ggw/bgw/{myResources:8}
P1OnlyActions: true
WithP1Hand: [LAW_144 SOR_095 SOR_241 SOR_164]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_Heroism_unit
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# DecliningPlaysNothingAndGrantsNoExperience
#// "You MAY play a Heroism unit" — declining is a clean no-op: nothing leaves hand, nothing enters play,
#// and no Experience token is created anywhere (in particular Phantom does not keep it for itself).
#// Phantom is alone in the space arena with no upgrades.

## GIVEN
CommonSetup: ggw/bgw/{myResources:8}
P1OnlyActions: true
WithP1Hand: [LAW_144 SOR_095 SOR_241 SOR_164]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_144
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENACOUNT:0
P1HANDCOUNT:3

---

# PlayedUnitsOwnWhenPlayedAlsoResolves
#// The unit Phantom plays is played in full, so its OWN When Played resolves on top of the Experience
#// token. SOR_241 Wing Leader ("When Played: Give 2 Experience tokens to another friendly Rebel unit")
#// enters with 1 Experience from Phantom, then hands 2 Experience to Phantom — the only other friendly
#// Rebel unit, so that target auto-resolves. Both grants land, on the right units.
#// Resources: 8 - 2 (Phantom) - 3 (Wing Leader, on-aspect) = 3.

## GIVEN
CommonSetup: ggw/bgw/{myResources:8}
P1OnlyActions: true
WithP1Hand: [LAW_144 SOR_095 SOR_241 SOR_164]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:LAW_144
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:1:CARDID:SOR_241
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:3
P1HANDCOUNT:2

---

# NoHeroismUnitInHandDoesNotTrigger
#// With no Heroism unit in hand (SOR_164 Wampa = Aggression, SOR_232 AT-ST = Villainy) the ability never
#// offers anything — no decision is left pending, the hand is untouched, and Phantom gains nothing.

## GIVEN
CommonSetup: ggw/bgw/{myResources:8}
P1OnlyActions: true
WithP1Hand: [LAW_144 SOR_164 SOR_232]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:2

---

# EmptyHandDoesNotTrigger
#// The zero-candidate case with an EMPTY hand rather than a wrong-aspect hand: same clean no-op.

## GIVEN
CommonSetup: ggw/bgw/{myResources:8}
P1OnlyActions: true
WithP1Hand: LAW_144

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:0
