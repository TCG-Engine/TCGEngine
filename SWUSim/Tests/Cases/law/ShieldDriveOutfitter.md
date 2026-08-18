# PayShield
#// LAW_113 Shield Drive Outfitter (Vigilance, cost 1) — When Played: you may pay 1 resource. If you do,
#// give a Shield token to a unit. Pay 1; the only unit (itself) auto-targets and gains a Shield.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
WithP1Hand: LAW_113

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_113
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0

---

# DeclinePayNoShield
#// LAW_113 Shield Drive Outfitter — the When Played pay is optional; declining it gives no Shield and
#// costs no extra resource. Play it (cost 1), decline the pay: 1 resource used total, no Shield token.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
WithP1Hand: LAW_113

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_113
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:1

---

# Offer_ShieldTargetIsANYUnit_EitherSide_EitherArena
#// LAW_113 Shield Drive Outfitter — "give a Shield token to A UNIT" carries no controller word and no
#// arena word, so the pool must be every unit in play: the friendly ground SOR_095, the friendly space
#// SOR_237, the ENEMY ground SOR_046, the ENEMY space SOR_225 — and the Outfitter itself, which "a unit"
#// does not exclude. The existing PayShield section had the Outfitter as the only unit on the board, so it
#// auto-targeted and could not have detected a friendly-only or ground-only pool.
#// The pay gate is answered YES first so the target choose is the pending decision at end state.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_113

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_113
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# Unaffordable_NoPayOfferAtAll
#// LAW_113 Shield Drive Outfitter — the "you may pay 1 resource" gate is only offered when the resource
#// can actually be paid. With exactly 1 resource the play cost consumes it, leaving nothing for the
#// optional pay: no prompt is raised at all (not a prompt that then fails), no Shield is created, and the
#// action ends cleanly. Boundary partner of PayShield, which has 2 resources and does get the offer.

## GIVEN
CommonSetup: bbw/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_113

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:LAW_113
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:0
