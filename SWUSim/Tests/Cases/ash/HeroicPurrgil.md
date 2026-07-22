# AmbushAttackBonus
#// ASH_207 Heroic Purrgil (Space, 3/6, Ambush) — While attacking using Ambush, this unit gets +2/+0. Played
#// into a board with the enemy JTL_069 (4/7), it Ambush-attacks for 3 + 2 = 5; JTL_069 ends with 5 damage
#// and the Purrgil takes the 4 counter.
## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:ASH_207}
WithP2SpaceArena: JTL_069:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:5
P1SPACEARENAUNIT:0:CARDID:ASH_207
P1SPACEARENAUNIT:0:DAMAGE:4

---

# AmbushAttack_PlusTwo
#// ASH_207 Heroic Purrgil — Ambush + "while attacking using Ambush, this unit gets +2/+0." Played, it ambush-
#// attacks SOR_237 (2/3) at 3+2 = 5 power, defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:5;handCardIds:ASH_207}
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
