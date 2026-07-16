# Exploit2_DefeatTwo_ReducedCost
#// TWI_037 Droideka Security (4/5, Ground, cost 6, Vigilance+Villainy) — Exploit 2 with both
#// fodder units selected. P1 has two friendly SEC_080 (Villainy 3/3 ground vanilla) in the
#// ground arena. Playing Droideka triggers MZMULTICHOOSE (0..2); P1 selects BOTH units.
#// Each defeat reduces cost by 2 → effective cost = 6 − 4 = 2. Both fodder units leave play
#// (arena count = 1: only Droideka remains). Starting with 10 ready resources → 8 left after.
#// Leader: bbk (Iden Versio, Vigilance+Villainy) covers both aspects; no penalty.
#// Selections submitted in ASCENDING index order (0 then 1) to guard the UID-snapshot fix:
#// without the fix, defeating index 0 shifts index 1 down to index 0, and the second defeat
#// hits a wrong slot — only one unit dies and the discount is wrong.

## GIVEN
CommonSetup: bbk/grw/{myResources:10;handCardIds:TWI_037}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_037
P1RESAVAILABLE:8

---

# Exploit_DeclineFullCost
#// TWI_037 Droideka Security (4/5, Ground, cost 6, Vigilance+Villainy) — Exploit 2 declined.
#// P1 has two friendly SEC_080 (Villainy 3/3 ground vanilla) in the ground arena. Playing
#// Droideka triggers MZMULTICHOOSE (0..2); P1 declines (selects 0 units). No defeats occur,
#// no discount → effective cost = 6. Both fodder units remain in play (arena count = 3).
#// Starting with 8 ready resources → 2 left after paying full cost.
#// Leader: bbk (Iden Versio, Vigilance+Villainy) covers both aspects; no penalty.

## GIVEN
CommonSetup: bbk/grw/{myResources:8;handCardIds:TWI_037}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:2
