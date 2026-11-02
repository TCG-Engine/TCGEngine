# CobbPlusKylosTie_Stack_OneFreeEntry
#// Named Action abilities on the DISCARD PILE — stacking (owner, 2026-11-02). Cobb Vanth dies and his search discards
#// Kylo's TIE Silencer from the deck: Cobb's "play it for free" AND the TIE's own "discarded from your deck this phase →
#// play it paying its cost" both apply (a search's discard is a discard from the deck). They are the same play, and it's
#// free: ONE entry, Cobb Vanth's.
## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SHD_135 SEC_110 SEC_110 SEC_110]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SHD_135
## EXPECT
P1DISCARDUNIT:1:CARDID:SHD_135
P1DISCARDUNIT:1:MODIFIER:TPF,TPP
P1ACTIONENTRIES:myDiscard-1:EXACT:SHD_115.d0
P1ACTIONENTRIES:myDiscard-1:ENABLED:SHD_115.d0

---

# CobbPlusKylosTie_Stack_PlaysFree
## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SHD_135 SEC_110 SEC_110 SEC_110]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SHD_135
- P1>PlayFromDiscard:1
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_135
P1DISCARDCOUNT:1

---

# CobbThenBoga_TwoEntries
#// Not stacking (owner, 2026-11-02): Cobb Vanth discards Battlefield Marine with "play it for free", then Boga chooses
#// that Marine — "it costs 1 resource less" is its own terms, so the Marine offers TWO ways to be played: Cobb Vanth's
#// (free) and Boga's (1 less). (The owner's first example paired Boga with Kylo's TIE Silencer, but Boga can't choose a
#// Vehicle; this is the same shape on a legal board.) 7 resources: Boga's play leaves 1, so both are usable.
## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SEC_110 SEC_110 SEC_110]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
## EXPECT
P1ACTIONENTRIES:myDiscard-1:EXACT:SHD_115.d0&HMW_122.d0
P1ACTIONENTRIES:myDiscard-1:ENABLED:SHD_115.d0&HMW_122.d0

---

# CobbThenBoga_PickBoga_PaysOne
#// Picking the (worse) Boga play proves the choice is real: it pays 1.
## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SEC_110 SEC_110 SEC_110]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>PlayFromDiscard:1:HMW_122.d0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1RESAVAILABLE:0

---

# CobbThenBoga_PickCobb_PaysNothing
## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SEC_110 SEC_110 SEC_110]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>PlayFromDiscard:1:SHD_115.d0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1RESAVAILABLE:1

---

# CobbThenBoga_BarePlay_IsAmbiguousError
## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SEC_110 SEC_110 SEC_110]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>PlayFromDiscard:1
## EXPECT
EXPECTERROR:ambiguous

---

# TwoCopies_EachKeepsItsOwnGrants
#// The owner's "two copies in discard": an older Battlefield Marine is already in the discard; Cobb's search discards a
#// second one (free); Boga chooses the OLDER copy (1 less). Grants belong to a COPY, never to a card name: each Marine
#// offers exactly its own one entry.
## GIVEN
CommonSetup: ggw/rrk/{myResources:7;myhandCardIds:HMW_122}
P1OnlyActions: true
WithP1Discard: [SOR_095]
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SEC_110 SEC_110 SEC_110]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
## EXPECT
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:2:CARDID:SOR_095
P1ACTIONENTRIES:myDiscard-0:EXACT:HMW_122.d0
P1ACTIONENTRIES:myDiscard-2:EXACT:SHD_115.d0
