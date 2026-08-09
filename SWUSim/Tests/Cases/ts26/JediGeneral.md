# CreatesClonePerRepublicLeader
#// TS26_55 Jedi General (Unit 2/3, cost 5) — Ambush. When Played: for each Republic leader you control,
#// create a Clone Trooper token and give it an Experience token. With a Republic leader (Yoda TWI_004), one
#// Clone Trooper is created and gets 1 Experience (2/2 → 3/3).
## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_55;myLeader:TWI_004}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
P1GROUNDARENAUNIT:1:POWER:3

---

# NoCloneWithoutRepublicLeader
#// TS26_55 Jedi General — with a non-Republic leader (Vader SOR_010), no Republic leader is controlled,
#// so no Clone Trooper token is created (only Jedi General enters play).
## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_55;myLeader:SOR_010}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_55

---

# DoubledClonesBothGetTheirExperience
#// TS26_55 Jedi General with ASH_094 Moff Jerjerrod ("if you would create a number of tokens, you may
#// defeat this unit; if you do, create twice that number instead"). One Republic leader means one Clone,
#// doubled to two — and the "give an Experience token to it" rider must reach BOTH, so each is a 3/3
#// rather than a bare 2/2. The arena ends as Jedi General plus the two Clones (Jerjerrod paid himself).
#// Discriminating: the rider used to be stamped on the one UID the create call returned, so Jerjerrod's
#// second Clone arrived with no Experience.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_55;myLeader:TWI_004}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_094:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:2:CARDID:TS26_T02
P1GROUNDARENAUNIT:2:POWER:3

---

# ADEPLOYEDRepublicLeaderStillCounts
#// TS26_55 Jedi General — "for each Republic leader you control (AS A LEADER OR UNIT)". Yoda (TWI_004,
#// Force/Jedi/Republic) is on the board as a deployed leader unit and still counts: one Clone Trooper
#// token is created with its Experience (2/2 -> 3/3).

## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_55;myLeader:TWI_004:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:TS26_T02
P1GROUNDARENAUNIT:2:POWER:3

---

# AUnitMADEALeaderByTheDarksaberCounts
#// TS26_55 Jedi General — the "(as a leader or UNIT)" clause reaches units that are leader units only by
#// GRANT. P1's leader is Vader (not Republic), but their Phase I Clone Trooper (TWI_241, Republic) wears
#// ASH_135 The Darksaber, whose text makes the attached unit a leader unit — so the count is 1 and a Clone
#// Trooper token is created.
#// Discriminating: the count used to scan only the Leader ZONE, which is exactly what the parenthetical
#// exists to widen, so a Darksaber-made Republic leader produced nothing.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_55;myLeader:SOR_010}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_241:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:TS26_T02
