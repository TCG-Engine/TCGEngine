# AttackBuff_Overwhelm
#// SEC_157 One Way Out (event) — Attack with a unit; it gets +1/+0 and gains Overwhelm for this attack.
#//   P1's SOR_046 (3/7) attacks SOR_128 (3/1) via One Way Out: +1 → power 4, defeats the 1-HP defender,
#//   and Overwhelm spills the 3 excess (4-1) to P2's base. (No +1 → only 2 would spill, so P2BASEDMG:3
#//   proves the +1 AND the Overwhelm grant.) The attacker survives the 3 counter (7 HP).

## GIVEN
CommonSetup: rrw/grk/{myResources:1;handCardIds:SEC_157}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# DefenderLosesAbilities
#// SEC_157 One Way Out — "If it attacks a unit, the defender loses all abilities for this attack." P1's
#//   JTL_069 (4/7) attacks LOF_047 (3/4), whose On Defense ("when attacked, you may give it an Experience
#//   token") would normally fire (a pending decision) and buff it to 4/5 so it survives. With One Way Out,
#//   LOF_047 loses all abilities for this attack → its On Defense does NOT fire (P2NODECISION), so it stays
#//   3/4 and is defeated by the 5 (4+1) attack. Overwhelm spills the 1 excess to P2's base.

## GIVEN
CommonSetup: rrw/grk/{myResources:1;handCardIds:SEC_157}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: LOF_047:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:1
P2NODECISION
