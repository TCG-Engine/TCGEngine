# WhenPlayed_ReturnUpgrade
#// SHD_209 Criminal Muscle (1-cost, Cunning ground) — "When Played: You may return a non-unique upgrade to
#// its owner's hand." The non-unique SOR_120 on SEC_080 is returned to P1's hand.
#// COVERAGE: offer=N/A (each section seeds exactly one legal non-unique upgrade; the "you may" still
#//           prompts so the pick is real) · decline=KNOWN-OPEN (the "you may" pass branch is not
#//           asserted in this file) · control=WhenPlayed_TokenUpgradeIsDefeated (the
#//           upgrade sits on an ENEMY unit and still resolves — the return is to the OWNER, not to the
#//           player who resolved it) · boundary=WhenPlayed_ReturnUpgrade (real card → owner's hand) vs
#//           WhenPlayed_TokenUpgradeIsDefeated (token → defeated, no hand, no discard) ·
#//           reqboundary=N/A (a single pick, resolved inside the When Played with no follow-up read)

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_209
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1

---

# WhenPlayed_TokenUpgradeIsDefeated
#// SHD_209 Criminal Muscle — a TOKEN upgrade has no owner's hand to go back to, so it is DEFEATED
#// instead of returned. P2's marine wears an Experience token (SOR_T01, the only non-unique upgrade in
#// play); Criminal Muscle picks it → the host loses the upgrade, and the token lands in NO zone: P2's
#// hand stays empty and P2's discard stays empty.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_209
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
