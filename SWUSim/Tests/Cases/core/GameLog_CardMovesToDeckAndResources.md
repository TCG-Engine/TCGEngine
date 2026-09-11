# ToDeck_KreiasWhispers_HandToTopAndBottom
#// Game-log sweep — card handlers that put a card into a DECK with a raw array_unshift/array_push, so the
#// move reached no logging funnel. SEC_232 Kreia's Whispers (Cunning, 2): "Draw 3 cards, then put a card from
#// your hand on the top of your deck and another card from your hand on the bottom of your deck." Hand and
#// deck are hidden, so each placement is a COUNT and neither card is ever named to the opponent. Empty deck
#// (fixture from sec/KreiasWhispers.md): P1 picks the top card; the bottom pick auto-resolves.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Hand: SOR_095
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
LOGCONTAINS:P1 put 1 card on the top of their deck ([[SEC_232|Kreia's Whispers]])
LOGCONTAINS:P1 put 1 card on the bottom of their deck ([[SEC_232|Kreia's Whispers]])
P2LOGNOTSEES:[[SOR_095
P2LOGNOTSEES:[[SOR_046

---

# ToDeck_YodaSensingDarkness_HandToTop
#// TWI_004 Yoda - Sensing Darkness (leader): "Action [Exhaust]: If a unit left play this phase, draw a card,
#// then put a card from your hand on the top or bottom of your deck." The TOP path pushed onto the deck
#// raw and logged nothing (the bottom path already logs through _topDeckPutRemainingToBottom). Hidden:
#// the card put back is never named to the opponent.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TWI_004;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Top

## EXPECT
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:1
LOGCONTAINS:P1 put 1 card on the top of their deck ([[TWI_004|Yoda]])
P2LOGNOTSEES:[[SEC_080

---

# ToDeck_YodaSensingDarkness_BottomReadsTheSame
#// USER DECISION 2026-09-11 (gamelog-updates #5): the two halves of one choice read alike. The BOTTOM path
#// logs through the shared placement funnel (_topDeckPutRemainingToBottom → SWULogDeckPlacement), which now
#// carries the source suffix too: "P1 put 1 card on the bottom of their deck (Yoda)".

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TWI_004;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1GlobalEffect: SWU_FRIENDLY_LEFT_PLAY
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Bottom

## EXPECT
LOGCONTAINS:P1 put 1 card on the bottom of their deck ([[TWI_004|Yoda]])
P2LOGNOTSEES:[[SEC_080

---

# ToDeck_PrincessLeia_HandToTop
#// IC27_008 Princess Leia - On a Diplomatic Mission (leader): "Action [1 resource, Exhaust]: Draw a card,
#// then put a card from your hand on the top or bottom of your deck." Same raw top-of-deck push as TWI_004
#// Yoda. (Fixture from ic27/PrincessLeia_OnADiplomaticMission.md.)

## GIVEN
CommonSetup: yyw/yyw/{myResources:3;myLeader:IC27_008;myhandCardIds:SEC_080}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:Top

## EXPECT
P1DECKTOPCARD:SEC_080
LOGCONTAINS:P1 put 1 card on the top of their deck ([[IC27_008|Princess Leia]])
P2LOGNOTSEES:[[SEC_080

---

# ToDeck_CaptainVaughn_HandToTop
#// TS26_39 Captain Vaughn - Search the Tunnels: "When Defeated: Search the top 3 cards of your deck for a card
#// and draw it. Then, put a card from your hand on top of your deck." Only the put-on-top half is asserted
#// here (the search is logged elsewhere). (Fixture from ts26/CaptainVaughn_SearchTheTunnels.md.)

## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_128]
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>AnswerDecision:myHand-0

## EXPECT
P1DECKTOPCARD:SEC_080
LOGCONTAINS:P1 put 1 card on the top of their deck ([[TS26_39|Captain Vaughn]])
P2LOGNOTSEES:[[SEC_080

---

# ToDeck_BurdenOfMasters_DiscardToBottom
#// LOF_125 The Burden of Masters (Command, 1): "Put a Force unit from your discard pile on the bottom of your
#// deck. If you do, play a unit from your hand and give 2 Experience tokens to it." The card comes from a
#// PUBLIC zone (the discard pile), so it is named. (Fixture from lof/TheBurdenOfMasters.md.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:LOF_125,SOR_059;discardCardIds:LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_059
LOGCONTAINS:P1 put [[LOF_050|Plo Koon]] from their discard pile on the bottom of their deck ([[LOF_125|The Burden of Masters]])

---

# ToDeck_LuminousBeings_DiscardToBottom_Batched
#// LOF_104 Luminous Beings (Command/Heroism, 6): "Put up to 3 Force units from your discard pile on the bottom
#// of your deck in a random order. Give that many units +4/+4 for this phase." One batched line for both
#// cards (public zone, named) — written BEFORE the shuffle so it cannot leak the random order.
#// (Fixture from lof/LuminousBeings.md.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_104;discardCardIds:SOR_051,SOR_045}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1DISCARDCOUNT:1
LOGCOUNT:1:from their discard pile on the bottom of their deck ([[LOF_104|Luminous Beings]])
LOGCONTAINS:P1 put [[SOR_051|Luke Skywalker]], [[SOR_045|Yoda]] from their discard pile on the bottom of their deck ([[LOF_104|Luminous Beings]])

---

# ToDeck_TraskWalker_DiscardToBottom
#// ASH_133 Trask Walker (Command, 8): "When Played/On Attack: Choose a unit in your discard pile that costs 7
#// or less. Either put that card on the bottom of your deck and heal 3 damage from your base or return it to
#// your hand." The Bottom branch — public zone, named. (Fixture from ash/TraskWalker.md.)

## GIVEN
CommonSetup: ggk/ggk/{myResources:8;handCardIds:ASH_133;discardCardIds:SOR_095;myBaseDamage:5}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Bottom

## EXPECT
P1BASEDMG:2
LOGCONTAINS:P1 put [[SOR_095|Battlefield Marine]] from their discard pile on the bottom of their deck ([[ASH_133|Trask Walker]])

---

# ToDeck_BactaTank_DiscardToTop
#// HMW_037 Bacta Tank (Fortify upgrade): "Action [defeat this upgrade]: Put a non-Vehicle unit from your discard
#// pile on top of your deck." Public zone, named. (Fixture from hmw/BactaTank.md.)

## GIVEN
CommonSetup: bgw/rrk/{discardCardIds:SOR_095}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_037
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_095
LOGCONTAINS:P1 put [[SOR_095|Battlefield Marine]] from their discard pile on the top of their deck ([[HMW_037|Bacta Tank]])

---

# ToDeck_YodaTricksterInExile_DiscardToTop
#// HMW_056 Yoda, Trickster In Exile: "When Defeated: You may put this card from your discard pile on top of
#// your deck. If you do, heal 2 damage from your base." Public zone, named.
#// (Fixture from hmw/Yoda_TricksterInExile.md.)

## GIVEN
CommonSetup: ybw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: HMW_056:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1DECKTOPCARD:HMW_056
LOGCONTAINS:P1 put [[HMW_056|Yoda]] from their discard pile on the top of their deck ([[HMW_056|Yoda]])

---

# ToDeck_ShipbreakingYard_DiscardToTop
#// LAW_026 Shipbreaking Yard (base): "Epic Action: Discard 3 cards from your deck. You may return a card
#// discarded this way to the top of your deck." The milled cards are public, so the returned one is named.
#// (Fixture from law/ShipbreakingYard.md.)

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_046
LOGCONTAINS:P1 put [[SOR_046|Consular Security Force]] from their discard pile on the top of their deck ([[LAW_026|Shipbreaking Yard]])

---

# ToResources_DJ_TakesControlOfAnEnemyResource
#// SHD_213 DJ - Blatant Thief: "When played using Smuggle: Take control of an enemy resource." A control change
#// of a FACE-DOWN card: logged as "P2's resource", never by name. (Fixture from shd/Dj_BlatantThief.md.)

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Resources: 7:SOR_046:1,1:SHD_213:1
WithP2Resources: 2:SEC_080:0
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:7
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:1
LOGCONTAINS:P1 took control of P2's resource ([[SHD_213|DJ]])
P2LOGNOTSEES:[[SEC_080

---

# ToResources_BrokenHorn_TopOfDeck
#// LAW_083 Broken Horn - Vizago's Pride: "When Played: If you have fewer cards in hand than an opponent, draw a
#// card. If you control fewer resources than an opponent, resource the top card of your deck." Face down —
#// the resourced card (SOR_095) is never named. (Fixture from law/BrokenHorn_VizagosPride.md.)

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Deck: SOR_237
WithP1Deck: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
LOGCONTAINS:P1 resourced the top card of their deck ([[LAW_083|Broken Horn]])
P2LOGNOTSEES:[[SOR_095

---

# ToResources_Stockpile_EventAndTopOfDeck
#// LAW_171 Stockpile: "Resource this event and the top card of your deck." One line for the whole effect; the
#// deck card (SOR_237) is face down and never named. (Fixture from law/Stockpile.md.)

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Deck: SOR_237
WithP1Hand: LAW_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:8
LOGCOUNT:1:P1 resourced this event and the top card of their deck ([[LAW_171|Stockpile]])
P2LOGNOTSEES:[[SOR_237

---

# ToResources_ExpendableMercenary_FromDiscard
#// LAW_159 Expendable Mercenary: "When Defeated: You may resource this unit from its owner's discard pile."
#// (Fixture from law/ExpendableMercenary.md.)

## GIVEN
CommonSetup: ggw/bgw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1RESCOUNT:1
LOGCONTAINS:P1 resourced this unit from their discard pile ([[LAW_159|Expendable Mercenary]])

---

# ToResources_DisplayPiece_ControllerResourcesTheDefeatedUnit
#// LAW_103 Display Piece: "Defeat an enemy non-leader unit. Its controller resources it from its owner's
#// discard pile." The RESOURCING player is the unit's controller (P2); the source is P1's event.
#// (Fixture from law/DisplayPiece.md.)

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESCOUNT:1
LOGCONTAINS:P2 resourced the defeated unit ([[LAW_103|Display Piece]])

---

# ToResources_Overgrowth_ResourceThisCard
#// HMW_151 Overgrowth: "If you control a Kashyyyk base, a friendly unit deals damage equal to its power to an
#// enemy unit. Resource this card." The resource clause is unconditional. (Fixture from hmw/Overgrowth.md.)

## GIVEN
CommonSetup: ggw/bgw/{myBase:SOR_029;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: HMW_151

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
LOGCONTAINS:P1 resourced this card ([[HMW_151|Overgrowth]])

---

# ToResources_KingGrakchawwaa_ThreeFromTheTop_Batched
#// HMW_123 King Grakchawwaa: "When Played: For each other friendly Wookiee unit, resource the top card of your
#// deck. Ready each card resourced this way." Three cards move in one effect: ONE batched line, and none of
#// the face-down cards is named. (Fixture from hmw/KingGrakchawwaa.md.)

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_211:1:0
WithP1GroundArena: SHD_061:1:0
WithP1GroundArena: SHD_200:1:0
WithP1Hand: HMW_123
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_128
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:9
LOGCOUNT:1:P1 resourced the top 3 cards of their deck and readied them ([[HMW_123|King Grakchawwaa]])
P2LOGNOTSEES:[[SOR_237
P2LOGNOTSEES:[[SOR_046
P2LOGNOTSEES:[[SOR_128

---

# ToResources_Osha_ACardFromHand
#// HMW_017 Osha - Haunted by her Past: "... play a Villainy unit from your resources ... If you do so, you may
#// resource a card from your hand." The hand card (SOR_128) is hidden and never named. (Fixture from
#// hmw/Osha_HauntedByHerPast.md, with the hand card swapped to one that appears nowhere else in the log.)

## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_128}
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,1:SEC_080:1,8:SOR_046:1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
LOGCONTAINS:P1 resourced a card from their hand ([[HMW_017|Osha]])
P2LOGNOTSEES:[[SOR_128
