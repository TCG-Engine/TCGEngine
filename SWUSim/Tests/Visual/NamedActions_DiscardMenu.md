# VISUAL CHECK — named Action abilities: two ways to play one discard card
#
# Visual-only schema. Run DevTools/ui-harness/swusim-named-actions-xbrowser.mjs (it builds this board and runs the WHEN
# steps through the Test Schema Editor's own TestSchemaStep.php).
#
# Cobb Vanth dies and discards Battlefield Marine ("play it for free"); Boga then chooses that Marine ("1 less"). The
# Marine — top of the discard pile — can now be played two ways. Clicking it opens the shared Action menu:
#   Cobb Vanth  (Free)
#   Boga        (1 resource)
# Each entry names the card that gives the permission and shows its price under the label.

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
P1ACTIONENTRIES:myDiscard-1:ENABLED:SHD_115.d0&HMW_122.d0
