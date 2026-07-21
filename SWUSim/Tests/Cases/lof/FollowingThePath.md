# ForceUnitToTop
#// LOF_103 Following the Path — Search the top 8 for up to 2 Force units, put them on top of the deck; the
#// rest go to the bottom. Deck top is an event (LOF_077) with Plo Koon (Force) beneath; choosing Plo Koon
#// moves him to the top.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_103}
P1OnlyActions: true
WithP1Deck: LOF_077
WithP1Deck: LOF_050

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_050

## EXPECT
P1DECKTOPCARD:LOF_050

---

# ChooseNothing_AllToBottom
#// LOF_103 Following the Path — choosing up to 2 Force units is optional. With Force units available on top
#// (LOF_050 Plo Koon, SOR_049 Obi-Wan) P1 declines and all 8 searched cards go to the bottom of the deck; the
#// deck size is unchanged. Ref: "should be allowed to choose nothing and place all cards on the
#// bottom of the deck".

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_103}
P1OnlyActions: true
WithP1Deck: LOF_050
WithP1Deck: SOR_049
WithP1Deck: LOF_077

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1DECKCOUNT:3

---

# ChooseTwoForceUnits_OfferedPool
#// LOF_103 Following the Path — the search offers only FORCE units to put on top. Among the top 8 the two
#// Force units (Plo Koon LOF_050, Obi-Wan SOR_049) are the playable picks while the event LOF_077 is not. The
#// TOPDECKSEARCH is left pending so its playable set (matchIDs) is asserted directly: the two Force units are
#// selectable and the event is not (the final on-top ordering of
#// the two chosen is random per "in any order", so it is not asserted here).

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_103}
P1OnlyActions: true
WithP1Deck: LOF_077
WithP1Deck: LOF_050
WithP1Deck: SOR_049

## WHEN
- P1>PlayHand:0

## EXPECT
P1SEARCHPLAYABLEHAS:LOF_050
P1SEARCHPLAYABLEHAS:SOR_049
P1SEARCHPLAYABLENOT:LOF_077
