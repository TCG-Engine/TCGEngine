# ControlSwap
#// TWI_204 Impropriety Among Thieves (Event, cost 4, Cunning/Cunning) — "Choose a ready non-leader unit
#// controlled by each player. Each player takes control of the chosen unit controlled by the player to
#// their right." In 2P this is a control SWAP: P1 chooses its own SOR_095 and P2's SEC_080 → P1 takes
#// control of SEC_080 and P2 takes control of SOR_095 (each moves into the new controller's arena).
## GIVEN
CommonSetup: rrk/bbw/{myResources:10;handCardIds:TWI_204}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# Fizzle_NoReadyEnemyUnit
#// TWI_204 Impropriety Among Thieves — "Choose a ready non-leader unit controlled by each player. If you
#// do..." The swap requires a valid READY non-leader unit for BOTH players. Here P2's only unit (SEC_080)
#// is EXHAUSTED, so there is no eligible enemy unit: the event fizzles — no choice is offered and no
#// control changes. (Guards the "ready" requirement and the "if you do" conditional.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:10;handCardIds:TWI_204}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:0:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1NODECISION

---

# RevertsAtRegroup
#// TWI_204 Impropriety Among Thieves — "At the start of the regroup phase, each player takes control of
#// each unit they own that was chosen for this ability." The control swap is temporary (TEMPORARY_STEAL):
#// after the swap, advancing to the regroup phase returns each unit to its OWNER — SOR_095 back to P1,
#// SEC_080 back to P2.
## GIVEN
CommonSetup: rrk/bbw/{myResources:10;handCardIds:TWI_204}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
