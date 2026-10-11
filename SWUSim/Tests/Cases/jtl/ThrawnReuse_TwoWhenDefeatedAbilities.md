# ThrawnPrompt_NamesTheAbilitysSourceCard
#// REPORTED 2026-09-27 (game 1400002). JTL_002 Grand Admiral Thrawn — "When you use a 'When Defeated'
#// ability: you may use that ability again." Its prompt read "Use that When Defeated ability again
#// (Thrawn)?" — a CONSTANT string naming nothing.
#//
#// That is fine while a unit has one When Defeated ability and unreadable the moment it has two. Here
#// ASH_050 Morgan Elsbeth is played and her SUPPORT attacks with LAW_097 Imperial Door Technician, which
#// "gains this unit's other abilities for this attack" — so the Door Tech dies holding TWO When Defeated
#// abilities: its own "Heal 2 damage from your base" and Morgan's lent "give a unit -2/-2". The player
#// sees Thrawn's offer next to Morgan's "Choose a unit" prompt with no way to tell which is which.
#//
#// The prompt must name the card that SUPPLIES the ability — the same identification rule
#// SWUQueueChooseWhenDefeatedAbility already uses for JTL_039 Chimaera ("labelled by the CardID that
#// SUPPLIES the ability"). Left PENDING so the tooltip is still there to read.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:JTL_002;myResources:7}
WithP1GroundArena: LAW_097:1:0
WithP1Hand: ASH_050
WithP2GroundArena: LAW_072:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1DECISIONTOOLTIP:Use_Imperial_Door_Technician's_When_Defeated_ability_again_(Thrawn)?

---

# ThrawnDeclined_StillOffersTheSupportLentAbility
#// THE SECOND REPORTED BUG. Declining Thrawn's offer on the Door Tech's OWN ability must leave the offer
#// for the SUPPORT-LENT one — the reuse costs an exhaust (undeployed) or the once-per-round budget
#// (deployed), and a DECLINE spends neither (owner ruling: "only once each round" is spent by USING it).
#//
#// It did not appear at all: `case 'SupportWhenDefeated'` called OnWhenDefeated and stopped, with no
#// SWUCollectThrawnReuse — the one granted-When-Defeated dispatch missing the hook that JTL_073, SHD_104,
#// TS26_52/035, SEC_039/156, LAW_141/201, ASH_063/134 and TWI_218/169/129 all carry.
#// The official ruling for this exact leader (03/06/2025, "...How Unfortunate") is as broad as it gets:
#// "Any ability whose trigger starts with 'When defeated…' is considered a 'When Defeated' ability."
#// A lent one is no exception.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:JTL_002;myResources:7}
WithP1GroundArena: LAW_097:1:0
WithP1Hand: ASH_050
WithP2GroundArena: LAW_072:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
#// ⚠ THE OFFER FOLLOWS THE ABILITY, NOT THE DECLINE. Morgan's lent ability resolves first (its own
#// "Choose a unit" target prompt, answered on the line above) and only then does Thrawn ask to repeat
#// it — the same order the Door Tech's heal already follows. An earlier cut of this section expected the
#// prompt immediately after the NO and failed against correct sequencing.
P1DECISIONTOOLTIP:Use_Morgan_Elsbeth's_When_Defeated_ability_again_(Thrawn)?

---

# ThrawnReuse_OfTheSupportLentAbility_AppliesItTwice
#// A prompt that appears but does nothing would satisfy the section above, so this one drives the reuse
#// to its EFFECT: decline the Door Tech's heal, accept the reuse of Morgan's lent "-2/-2 for this phase",
#// and apply it to Max Rebo BOTH times. LAW_072 Max Rebo is 2 power / 7 HP printed, so two applications
#// take him to 7 -> 5 -> 3 HP (his power floors at 0 on the first — see the note on the assertion).
#// ⚠ Without this the fix could re-arm the wrong trigger type: the granted-reuse path re-dispatches
#// `AddTrigger($type, $type, $mzID)`, which assumes trigger type == CardID. That holds for JTL_073 and
#// friends and is FALSE for Support (type 'SupportWhenDefeated', card ASH_050).
## GIVEN
CommonSetup: ngw/ngw/{myLeader:JTL_002;myResources:7}
WithP1GroundArena: LAW_097:1:0
WithP1Hand: ASH_050
WithP2GroundArena: LAW_072:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
#// ⚠ ASSERT HP, NOT POWER. LAW_072 Max Rebo is 2 power / 7 HP, so his power FLOORS AT 0 after a single
#// -2/-2 and one application is indistinguishable from two. HP walks 7 -> 5 -> 3, so only the HP figure
#// separates "the reuse happened" from "the prompt appeared and did nothing".
P2GROUNDARENAUNIT:0:CARDID:LAW_072
P2GROUNDARENAUNIT:0:HP:3

---

# ThrawnPrompt_SingleAbility_NamesItToo_AndOffersOnlyOnce
#// THE CONTROL. A unit with exactly ONE When Defeated ability must still be named, and must raise exactly
#// ONE offer — so the fix cannot be "always queue a second offer". P1's Door Tech is defeated by a plain
#// attack with no Support in play: one named prompt, and after answering it there is nothing left.
## GIVEN
CommonSetup: ngw/ngw/{myLeader:JTL_002}
WithP1GroundArena: LAW_097:1:0
WithP2GroundArena: LAW_072:1:0
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
- P1>Drain
## EXPECT
P1DECISIONTOOLTIP:Use_Imperial_Door_Technician's_When_Defeated_ability_again_(Thrawn)?
