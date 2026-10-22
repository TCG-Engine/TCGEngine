# RescueMakesDuplicate_ChoiceBeforeDraw
#// Uniqueness rule at the START OF THE REGROUP PHASE (Bug Report game 1647080, 2026-10-20). CR 2.x.5 (game-state upkeep):
#// "certain situations require immediate resolution and take priority over other waiting triggered abilities or action
#// steps … b. If multiple copies of a unique card are controlled by the same player, that player chooses and defeats copies
#// of that card until only one remains", and CR 8.30.3: "Defeating one of the copies occurs immediately and is not a
#// triggered ability." A rescued unit ENTERS PLAY as a new copy (CR 7.x.3 / 8.34.3).
#// SEC_195 Arrest's captive is rescued "at the start of the regroup phase" (CR 5.5.1.a), so the duplicate exists at step a
#// and the choice must come before step b (Draw cards). The engine only enforced uniqueness when an ACTION closed, and the
#// regroup phase has no action: P2 kept two HMW_254 Captain Tarpals and both players drew.
#// P1 arrests P2's Tarpals; P2 plays a second copy (Shielded → it carries a Shield); both pass. The choice is left PENDING:
#// nobody has drawn yet (each hand is still empty), and P2 is being asked.
## GIVEN
CommonSetup: yyk/ggw
WithP1Resources: 5
WithP2Resources: 5
WithP1Hand: SEC_195
WithP2Hand: HMW_254
WithP2GroundArena: HMW_254:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
## EXPECT
P2DECISIONTOOLTIP:Uniqueness_rule_choose_a_copy_to_defeat
P2GROUNDARENACOUNT:2
P1HANDCOUNT:0
P2HANDCOUNT:0

---

# RescueMakesDuplicate_DefeatRescuedCopy_ThenDraw
#// Same board. P2 defeats the RESCUED copy (exhausted, index 1); the copy P2 played this round stays, still carrying its
#// Shield. Only then does the regroup phase move on: both players draw their 2 cards.
## GIVEN
CommonSetup: yyk/ggw
WithP1Resources: 5
WithP2Resources: 5
WithP1Hand: SEC_195
WithP2Hand: HMW_254
WithP2GroundArena: HMW_254:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P2>ChooseMyGroundUnit:1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_254
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:HMW_254
P1HANDCOUNT:2
P2HANDCOUNT:2

---

# RescueMakesDuplicate_DefeatPlayedCopy_ThenDraw
#// Same board, the other answer: P2 defeats the copy it PLAYED (index 0, with the Shield). The rescued copy stays — bare, no
#// upgrades. The pair proves the choice is P2's and is honoured, not a fixed "defeat the newest/oldest".
## GIVEN
CommonSetup: yyk/ggw
WithP1Resources: 5
WithP2Resources: 5
WithP1Hand: SEC_195
WithP2Hand: HMW_254
WithP2GroundArena: HMW_254:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P2>ChooseMyGroundUnit:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_254
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1
P1HANDCOUNT:2
P2HANDCOUNT:2

---

# RescueMakesDuplicate_ChoiceBeforeRegroupStartTriggers
#// "Take priority over other waiting triggered abilities" (CR 2.x.5): LAW_071 The Max Rebo Band is unique, and each copy
#// "When the regroup phase starts: Create a Credit token." P1 arrests P2's Band; P2 plays a second one; at the regroup the
#// rescue makes two. The choice comes FIRST — so only the surviving copy's trigger resolves: ONE Credit. (Triggers before the
#// choice would make two Credits.) It also proves the suspended start of the regroup phase does resume after the choice.
## GIVEN
CommonSetup: yyk/ggw
WithP1Resources: 5
WithP2Resources: 6
WithP1Hand: SEC_195
WithP2Hand: LAW_071
WithP2GroundArena: LAW_071:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P2>ChooseMyGroundUnit:1
## EXPECT
P2GROUNDARENACOUNT:1
P2CREDITCOUNT:1
P2HANDCOUNT:2

---

# RescueMakesDuplicate_DefeatedCopyWhenDefeatedResolves
#// CR 8.30.3: "The player still must resolve any abilities that trigger upon either copy being … defeated." TS26_14 Yoda is
#// unique: "When Played/When Defeated: Create a Clone Trooper token and give it Sentinel for this phase." P2 plays a second
#// Yoda (When Played → a token); the regroup rescue makes two Yodas. P2 defeats the rescued one (index 3, after the Band, the
#// played Yoda and its token): its When Defeated creates a second token, and only then does the regroup phase carry on.
#// A single LAW_071 The Max Rebo Band sits on the board as the witness that the start of the regroup phase RESUMED after the
#// When Defeated (the trigger-resume path, UNIQREGROUP): its "When the regroup phase starts" Credit. The draw alone cannot
#// show it — the phase advances to the draw step whether or not the rest of the start step ran.
## GIVEN
CommonSetup: yyk/ggw
WithP1Resources: 5
WithP2Resources: 7
WithP1Hand: SEC_195
WithP2Hand: TS26_14
WithP2GroundArena: [TS26_14:1:0 LAW_071:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P2>ChooseMyGroundUnit:3
## EXPECT
P2GROUNDARENACOUNT:4
P2GROUNDARENAUNIT:1:CARDID:TS26_14
P2CREDITCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:TS26_14
P1HANDCOUNT:2
P2HANDCOUNT:2
