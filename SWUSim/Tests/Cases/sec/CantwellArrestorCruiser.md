# DeclineDisclose_NoExhaust
#// SEC_037 Cantwell Arrestor Cruiser — decline the optional disclose → no exhaust, no lock.
#// Fodder is in hand (so disclose IS offered), but P1 declines (AnswerDecision:-); the enemy SOR_046
#// stays READY and unlocked.

## GIVEN
CommonSetup: bbk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_037
WithP1Hand: SEC_054
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_037
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# DiscloseExhaust_CantReadyWhileInPlay
#// SEC_037 Cantwell Arrestor Cruiser (Space, 6/7, Vigilance/Villainy, cost 7) — When Played: you
#//   may disclose VigilanceVigilanceVillainy. If you do, exhaust an enemy unit; that unit can't ready
#//   while THIS unit is in play.
#//
#// bk leader (Vigilance+Villainy) covers both pips → cost 7. P1 hand: SEC_037 + disclose fodder
#// SEC_054 (Vigilance,Vigilance) + SEC_080 (Command,Villainy) → together cover VigVigVillainy.
#// Play SEC_037 → disclose both fodder → exhaust the READY enemy SOR_046 (idx 0). Then drive to the
#// next regroup: the locked SOR_046 stays EXHAUSTED (SEC_037 still in play) while the control
#// SEC_080 (idx 1, started exhausted) readies normally. Mirrors the SOR_186 ready-lock test, but the
#// lock is source-in-play-scoped (not round-scoped).

## GIVEN
CommonSetup: bbk/rrk/{myResources:7}
WithActivePlayer: 1
WithP1Hand: SEC_037
WithP1Hand: SEC_054
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_037
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# NoAspectsInHand_AbilityAutoSkipped
#// SEC_037 Cantwell Arrestor Cruiser — the disclose requires VigilanceVigilanceVillainy among revealed
#// hand cards. With no disclosable fodder in hand (only SEC_037 itself + an off-requirement card), the
#// When Played disclose is auto-skipped: no prompt, no exhaust, the enemy SOR_046 stays READY.

## GIVEN
CommonSetup: bbk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_037
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_037
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# LockLiftsWhenCantwellLeavesPlay
#// SEC_037 Cantwell Arrestor Cruiser — the ready-lock is source-in-play-scoped. P1 plays Cantwell,
#// discloses, and exhausts the enemy SOR_046. P2 then Vanquishes (SOR_078) Cantwell off the board.
#// With Cantwell gone, the lock is released, so at the next regroup SOR_046 readies normally.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7}
WithActivePlayer: 1
WithP2Resources: 6
WithP1Hand: SEC_037
WithP1Hand: SEC_054
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:READY
