# 2ExpToImperial
#// SOR_231 TIE Advanced (Space, 3/2) — When Played: give 2 Experience tokens to
#// another friendly IMPERIAL unit. P1's Death Trooper (SOR_033, Imperial, 3/3) is the
#// only other Imperial → auto-receives +2/+2 (→ 5/5).
#// COVERAGE: offer=Offer_OtherFriendlyImperialsOnly (pending SELECTABLEEXACT: excludes self,
#//           non-Imperial friendlies, and enemy Imperials) · decline=N/A (mandatory give, no
#//           "you may") · control=Offer_OtherFriendlyImperialsOnly ("friendly" is read from the
#//           CASTER's side — P2's Imperial is not a candidate) + NoOtherFriendlyImperial_NothingGiven
#//           (an Imperial that only the OPPONENT controls leaves the pool empty) ·
#//           boundary=StacksOntoExistingExperience_ThreeTotal (token stacking edge) and
#//           NoOtherFriendlyImperial_NothingGiven (pool of 1 vs 0 → the give simply does not happen,
#//           and the play still stands) ·
#//           reqboundary=Offer_OtherFriendlyImperialsOnly (the play request ends with the give-pick
#//           still pending; the pool survives into the next request)

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_231}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# StacksOntoExistingExperience_ThreeTotal
#// SOR_231 TIE Advanced — Intended: the 2 Experience tokens stack onto a unit that ALREADY has
#// one. Death Trooper (3/3) starts with 1 Experience (4/4); the sole other friendly Imperial
#// auto-receives 2 more → 3 Experience total, 6/6.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_231}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# Offer_OtherFriendlyImperialsOnly
#// SOR_231 TIE Advanced — Intended: the give-2-Experience pool is "another friendly IMPERIAL
#// unit" only. P1 controls two ground Imperials (SEC_080, SOR_128) and a non-Imperial Rebel
#// (SOR_095); P2 controls an Imperial (SOR_229). The pick is left PENDING: the pool must hold
#// exactly the two friendly Imperials — not the Rebel, not the enemy Imperial, and not the
#// just-played TIE Advanced itself ("another").

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_231}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_229:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# NoOtherFriendlyImperial_NothingGiven
#// SOR_231 TIE Advanced — the no-valid-target cell. P1's only other unit is a Rebel (Battlefield
#// Marine) and the only IMPERIAL besides the TIE itself belongs to P2, so the mandatory
#// "give 2 Experience to another friendly IMPERIAL unit" has an EMPTY pool: no prompt is raised,
#// nothing receives a token, and the play itself still stands (the TIE seats at its printed 3/2).
#// "Another" also excludes the TIE from bailing itself out, so it stays bare too.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_231}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # friendly, but a Rebel — not Imperial
WithP2GroundArena: SOR_229:1:0    # Imperial, but the OPPONENT'S

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_231
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
