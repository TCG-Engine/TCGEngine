# UpgradePlayed_CreatesToken
#// ASH_047 Gar Saxon (Ground, 3/4) — "When you play an upgrade on this unit: you may create a Mandalorian
#// token (once each round)." P1 plays Academy Training (SOR_120) which auto-attaches to the lone friendly
#// unit (ASH_047); the reaction offers a YESNO → YES → a Mandalorian token is created.

## GIVEN
CommonSetup: brk/rrk/{myResources:6;handCardIds:SOR_120}
WithP1GroundArena: ASH_047:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_047
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:ASH_T01

---

# UpgradePlayed_Decline_NoToken
#// ASH_047 Gar Saxon — the token creation is optional. P1 plays SOR_120 onto Gar but declines, so no
#// Mandalorian token is created.
## GIVEN
CommonSetup: brk/rrk/{myResources:6;handCardIds:SOR_120}
WithP1GroundArena: ASH_047:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# UpgradeOnAnotherUnit_NoToken
#// ASH_047 Gar Saxon — the reaction only fires for upgrades played on Gar himself. P1 plays Infiltrator's
#// Skill (SOR_166) onto the friendly Battlefield Marine (SOR_095) instead, so no Mandalorian token is made.
## GIVEN
CommonSetup: brk/rrk/{myResources:6;handCardIds:SOR_166}
WithP1GroundArena: [ASH_047:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OncePerRound_SecondUpgradeNoToken
#// ASH_047 Gar Saxon — "use this ability only once each round." P1 plays Academy Training (SOR_120) on Gar
#// (YES → token), then a second upgrade Infiltrator's Skill (SOR_166) on Gar the same round: no prompt and
#// no second token. Gar carries both upgrades; still only one Mandalorian token exists.
## GIVEN
CommonSetup: brk/rrk/{myResources:6;handCardIds:SOR_120,SOR_166}
WithP1GroundArena: ASH_047:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_047
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
