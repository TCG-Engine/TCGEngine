# SOR_185 Chimaera (Space Unit 8/7, cost 8, Cunning/Villainy, Shielded) — "On Attack: Name a card.
# An opponent reveals their hand and discards a card with that name from it." The discard always
# auto-resolves (copies are identical, so the first matching copy is picked with no player choice),
# which means the player would never otherwise see the revealed hand. Behavior (mirrors SOR_201
# Bodhi Rook): a snapshot of the hand is SAVED before the auto-discard, the discard resolves, and the
# saved snapshot is then shown as a Viper-Probe-Droid (SOR_228) OK popup. This test stops BEFORE
# answering the popup: the discard has ALREADY happened (P2DISCARDCOUNT:1) and the saved-hand popup
# is pending (P1HASDECISION) — and combat damage has NOT yet been dealt (P2BASEDMG:0), proving the
# popup resolves after the discard and before combat.

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SOR_171
WithP2Hand: SEC_080

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing

## EXPECT
P1HASDECISION
P2BASEDMG:0
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
LOGCONTAINS:revealed
