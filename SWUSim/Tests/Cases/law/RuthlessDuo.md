# DealIfVillainy
#// LAW_137 Ruthless Duo (Command,Villainy, cost 4) — When Played: if you control another Villainy unit,
#// you may deal 2 damage to a ground unit. P1 controls SEC_080 (Villainy) -> deal 2 to enemy SOR_046.

## GIVEN
CommonSetup: grk/bgw/{myResources:4}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_137

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoTriggerWithoutOtherVillainy
#// LAW_137 Ruthless Duo — the "deal 2 damage" clause is gated on controlling ANOTHER Villainy unit.
#// With no other friendly Villainy unit in play, the ability does not trigger and the enemy takes 0.

## GIVEN
CommonSetup: grk/bgw/{myResources:4}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_137

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_137
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OfferPool_GroundOnlyBothSidesIncludesSelf
#// LAW_137 Ruthless Duo — offer assertion for "deal 2 damage to A GROUND UNIT". The arena is the only
#// printed restriction, so the pool must span BOTH controllers and include Ruthless Duo itself (the text
#// says "a ground unit", not "another" and not "enemy"): friendly SEC_080, Ruthless Duo at
#// myGroundArena-1, the enemy SOR_046 and the deployed enemy Luke leader unit at theirGroundArena-1 are
#// all in. A friendly space unit and an enemy space unit are the arena violators and must both be out —
#// DealIfVillainy only ever answered with an enemy ground pick, so a friendly-excluding or
#// arena-ignoring pool would have passed it unchanged.
#// COVERAGE: offer=OfferPool_GroundOnlyBothSidesIncludesSelf (pending SELECTABLEEXACT: space units of both
#//           sides excluded, friendly units and the source itself included, deployed enemy leader unit
#//           included) · boundary pair=DealIfVillainy (another Villainy unit -> 2 damage) vs
#//           NoTriggerWithoutOtherVillainy (no other Villainy unit -> no trigger at all) ·
#//           decline=not encoded — the clause is a "you may" MZMAYCHOOSE and no section passes on it
#//           (KNOWN-OPEN) · control=N/A (one-shot damage, no persistent per-unit marker) ·
#//           reqboundary=not encoded (the play and the damage answer are separate requests in production;
#//           no serialize round-trip section exists yet)

## GIVEN
CommonSetup: grk/bgw/{myResources:4; theirLeader:SOR_005:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_137

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_137
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1
