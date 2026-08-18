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
#// Strength's "non-leader unit". Attaching Fulcrum to an ENEMY unit is legal, but note it HELPS them: the
#// aura it grants reads "each OTHER friendly Rebel unit" and is text the HOST gains, so friendly follows
#// the host's controller — see EnemyHost_TheGRANTEDAuraIsControlledByTheHOSTsController. The decision is
#// left pending so the pool is the assertion.
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

---

# AuraExcludesItsOwnHost_SingleFulcrumIsOnlyFivePower
#// LAW_150 Fulcrum — the granted aura reads "each OTHER friendly Rebel unit", so the host never receives
#// its own Fulcrum's +2/+2: a lone Fulcrum leaves its Imperial host at 3/3 base + the upgrade's own +2/+2
#// = 5/5, and the second friendly Rebel next to it goes to 5/5 from the aura. The existing
#// GrantsRebelAndAura board has TWO Fulcrums, where each host is buffed by the OTHER copy — 7/7 there is
#// consistent both with a correct "other" and with an aura that wrongly includes its own host, so it
#// cannot separate the two. This section is the discriminator.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_150
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5

---

# EnemyHost_TheGRANTEDAuraIsControlledByTheHOSTsController
#// LAW_150 Fulcrum — the aura is text the ATTACHED UNIT gains, so its controller is the host's controller
#// (CR 2.e: an upgrade may be played on an enemy unit, and that unit's controller resolves what it grants).
#// "Friendly" therefore follows the HOST, not the player who paid for the upgrade. P1 attaches Fulcrum to
#// P2's SEC_080: the host gains the Rebel trait and the upgrade's own +2/+2 (3/3 -> 5/5), and P2's other
#// Rebel SOR_095 — friendly to the host's controller — is the one that receives the +2/+2 aura (3/3 -> 5/5).
#// P1's own Rebel SOR_095 gains nothing and stays a printed 3/3. Attaching Fulcrum to an enemy unit is a
#// legal play that helps the OPPONENT, which is exactly why the direction has to be pinned by a section.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1Hand: LAW_150

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
