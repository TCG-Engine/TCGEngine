# SearchFinalize_IveFoundThem_RevealedDraw
#// Game-log follow-up (2026-09-11). I've Found Them has its own search finalizer (draw the pick, DISCARD
#// the rest), which drew with a raw AddHand — the discards were logged, the draw was not. IBH_009: "Reveal
#// the top 3 cards of your deck. Draw a unit revealed this way…" — revealed, so the pick is public.
#// (Fixture from ibh/IveFoundThem.md.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_009
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 revealed and drew [[SOR_095|Battlefield Marine]] ([[IBH_009|I've Found Them]])

---

# BladeOfTalzin_ReturnsToHand
#// ASH_055 Blade of Talzin: "When Defeated: If this upgrade was on a friendly Night unit, return it from your
#// discard pile to your hand." Resolved inline with a raw AddHand. It is the Blade's own ability — not
#// credited to whatever defeated it. (Fixture from ash/BladeOfTalzin_AGiftOfShadows.md.)

## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: LOF_031:1:3
WithP1GroundArenaUpgrade: 0:ASH_055
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 returned [[ASH_055|Blade of Talzin]] from their discard pile to their hand

---

# SmuggleSlotRefill_FaceDown
#// CR 8.22.g: a card played using Smuggle is replaced in the resource zone by the top card of the deck. The
#// refill was silent; it is face down, so the line never names the card. (Fixture from shd/PrivateerCrew.md.)

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1Resources: 6:SOR_046:1,1:SHD_113:1
WithP1Deck: SEC_080

## WHEN
- P1>SmuggleResource:6

## EXPECT
LOGCONTAINS:P1 resourced the top card of their deck (Smuggle slot refill)
P2LOGNOTSEES:[[SEC_080

---

# PlotSlotRefill_FaceDown
#// CR 19.c: a Plot card's slot is refilled from the top of the deck — same shape as Smuggle.
#// (Fixture from sec/OneInAMillion.md.)

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_005;myResources:5}
P1OnlyActions: true
WithP1Resources: 1:SEC_053:1
WithP1Deck: [SEC_080 SEC_080]
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-5
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
LOGCONTAINS:P1 resourced the top card of their deck (Plot slot refill)
P2LOGNOTSEES:[[SEC_080

---

# DjSteal_ReturnIsLogged
#// SHD_213 DJ: "When played using Smuggle: Take control of an enemy resource. When this unit leaves play,
#// that resource's owner takes control of it." The steal is logged by DJ's card file; the RETURN runs in
#// the engine's leave-play sweep (_SWURevertShd213Steals) and was silent. Face down: never named.
#// (Fixture from shd/Dj_BlatantThief.md.)

## GIVEN
CommonSetup: yyw/yyw
WithActivePlayer: 1
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0
- P2>AttackGroundArena:0:0

## EXPECT
P2RESCOUNT:2
LOGCONTAINS:P1 took control of P2's resource ([[SHD_213|DJ]])
LOGCONTAINS:P2 regained control of their resource ([[SHD_213|DJ]] left play)
P1LOGNOTSEES:[[SEC_080
