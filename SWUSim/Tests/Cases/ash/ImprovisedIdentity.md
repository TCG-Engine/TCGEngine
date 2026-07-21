# TransplantAbilities
#// ASH_230 Improvised Identity (Upgrade) — grants the host: "Action: search the top 3 for a ground unit and
#// discard it; then you may attack with this unit, gaining the discarded unit's abilities for this attack."
#// SOR_046 (wearing the upgrade) discards SOR_059 (On Attack: may heal 2 from another unit) and attacks P2's
#// base; the transplanted On Attack heals 2 from the damaged SOR_095 (2 → 0 damage).
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArena: SOR_095:1:2
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_059
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# DeclineSearch_TakeNothing
#// ASH_230 Improvised Identity — the search may take nothing. P1 uses the action but declines to take a
#// ground unit; nothing is discarded and no attack follows.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P1DISCARDCOUNT:0
P1GROUNDARENAUNIT:0:READY

---

# DiscardThenDeclineAttack
#// ASH_230 Improvised Identity — the attack after discarding is optional ("you may attack"). P1 discards
#// SOR_059 but declines to attack, so the host stays ready and deals no damage.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_230
WithP1GroundArena: SOR_095:1:2
WithP1Deck: [SOR_059 SOR_063 SOR_063]
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:SOR_059
- P1>AnswerDecision:NO
## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:DAMAGE:2
