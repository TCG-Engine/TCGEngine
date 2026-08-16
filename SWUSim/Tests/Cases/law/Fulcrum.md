# GrantsRebelAndAura
#// LAW_150 Fulcrum (Upgrade, +2/+2) — "Attached unit gains the Rebel trait and 'Each other friendly
#// Rebel unit gets +2/+2.'" Two Imperial SEC_080s each wear a Fulcrum: each becomes Rebel (grant) and
#// each gets +2/+2 from the OTHER's Fulcrum aura. So each = 3/3 base + own Fulcrum (2/2) + other Fulcrum
#// aura (2/2) = 7/7. (Without the Rebel grant, an Imperial wouldn't receive the aura → only 5/5.)

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_150
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 1:LAW_150

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:POWER:7
P1GROUNDARENAUNIT:1:HP:7

---

# AttachOffer_NonVehicleSpansBothSides
#// LAW_150 Fulcrum — offer assertion for the printed attach restriction "Attach to a NON-VEHICLE unit".
#// Naming no controller, it spans both sides (CR 2.e), so the board seeds a Vehicle and a non-Vehicle on
#// each side: friendly SOR_095 and enemy SEC_080 (both non-Vehicle) are in, while the friendly AT-ST, the
#// friendly X-Wing and the enemy TIE fighter are Vehicles and out. The deployed Cad Bane leader unit at
#// theirGroundArena-1 is also in — "non-Vehicle unit" carries no leader exclusion, unlike LAW_128 Veiled
#// Strength's "non-leader unit". Attaching Fulcrum to an ENEMY unit is a real play: the aura it grants
#// reads "each OTHER friendly Rebel unit", and friendly is measured from the upgrade's controller (whoever
#// played it), not from the host. The decision is left pending so the pool is the assertion.
#// COVERAGE: offer=AttachOffer_NonVehicleSpansBothSides (pending SELECTABLEEXACT: Vehicles excluded on both
#//           sides and in both arenas, enemy non-Vehicle and deployed enemy leader unit included) ·
#//           decline=N/A (an upgrade's attach target is a mandatory part of playing it; there is no "you
#//           may") · boundary pair=AttachOffer_NonVehicleSpansBothSides itself (a Vehicle and a
#//           non-Vehicle on each side, so the pool separates them) + GrantsRebelAndAura (with the Rebel
#//           grant 7/7; without it the Imperial host would only reach 5/5) · control=N/A (the +2/+2 and
#//           the Rebel grant are properties of the attachment, and the aura is scoped to the upgrade's
#//           controller) · reqboundary=not encoded (the play and the attach answer are separate requests
#//           in production; no serialize round-trip section exists yet)

## GIVEN
CommonSetup: ggw/rrk/{myResources:5; theirLeader:ASH_011:1:1:1}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_232:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1
