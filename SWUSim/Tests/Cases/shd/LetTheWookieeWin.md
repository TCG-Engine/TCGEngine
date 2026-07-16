# LetTheWookieeWin_Mode1_ReadyResources
#// SHD_205 Let the Wookiee Win — "An opponent chooses one: [You ready up to 6 resources] OR [ready a
#// friendly unit...]." P1 plays it (cost 2, leaving 4 ready of 6); the opponent picks the ready-resources
#// mode, so P1's 2 spent resources are readied back → all 6 ready.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_205

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Ready6Resources

## EXPECT
P1RESAVAILABLE:6

---

# LetTheWookieeWin_Mode2_ReadyWookieeAttack
#// SHD_205 Let the Wookiee Win — the second mode: "You ready a friendly unit. If it's a Wookiee unit,
#// attack with it. It gets +2/+0 for this attack." The opponent picks this mode; P1 readies the exhausted
#// SHD_249 (Wookiee, 2 power), which attacks the base for 2 + 2 = 4.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_205
WithP1GroundArena: SHD_249:0:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:ReadyUnit
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
