# WhenPlayed_ShieldMandalorians
#// SHD_047 The Armorer (5-cost 3/5 ground) — "When Played: Give a Shield token to each of up to 3 Mandalorian
#// units." Two Mandalorian units (SHD_150) are shielded; the non-Mandalorian SOR_095 is not eligible.
#// COVERAGE: offer=ThreePicked_PoolSpansBothArenasAndBothSides (4 Mandalorians are eligible — friendly
#//           ground, friendly SPACE, an ENEMY one, and the Armorer herself — and exactly 3 may be taken,
#//           so the picks are named explicitly) · decline=N/A ("up to 3" includes zero, but a zero-pick
#//           board is indistinguishable from no ability) · control=ThreePicked_PoolSpansBothArenas
#//           AndBothSides (the text says "Mandalorian units", not "friendly", so an opponent's
#//           Mandalorian is a legal — if odd — recipient) · boundary=WhenPlayed_ShieldMandalorians
#//           (2 eligible, 2 taken) vs ThreePicked_... (4 eligible, capped at 3) ·
#//           reqboundary=N/A (the tokens land during the play that queued the pick)

## GIVEN
CommonSetup: bbw/bbw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_047
WithP1GroundArena: SHD_150:1:0
WithP1GroundArena: SHD_150:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:SOR_095
P1GROUNDARENAUNIT:2:SHIELDCOUNT:0

---

# ThreePicked_PoolSpansBothArenasAndBothSides
#// Intended: the recipients are "Mandalorian units" with no friendly and no arena restriction, capped at
#// 3. Four are eligible here — SHD_162 House Kast Soldier (friendly ground), SHD_042 Concord Dawn
#// Interceptors (friendly SPACE), SHD_034 Supercommando Squad (ENEMY ground) and the Armorer herself,
#// who lands at ground index 2 behind the two pre-seated friendlies. Taking the cross-arena and enemy
#// Mandalorians uses the cap exactly: those three get a Shield, while the Armorer (eligible but not
#// picked) and SOR_095 (never eligible — a Rebel Trooper) both stay at zero.

## GIVEN
CommonSetup: bbw/bbw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_047
WithP1GroundArena: SHD_162:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SHD_042:1:0
WithP2GroundArena: SHD_034:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_162
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_042
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_034
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:SHD_047
P1GROUNDARENAUNIT:2:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
