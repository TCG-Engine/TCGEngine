# VISUAL CHECK — Whisper chat, Team Suns (2v2: seats 1,3 RED · 2,4 BLUE)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php --data-urlencode "schema@SWUSim/Tests/Visual/WhisperChat_TeamSuns.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   Seat N: http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=N&authKey=testschema
#   Automated: node DevTools/ui-harness/swusim-whisper-chat-xbrowser.mjs
#
# WHAT THIS PINS
#   Team Suns offers ONE "Team only" checkbox (no per-seat boxes; you cannot whisper an opponent).
#   The server enforces it too: a hand-built whisperTo naming an opponent is refused ("Whisper not allowed.").
#
# WHAT TO LOOK AT (desktop, then mobile 400px with &swuLayout=mobile)
#   • Seat 1 sees "☐ Team only" above the input — and nothing else.
#   • Tick it → placeholder "Whisper to P3…". Send "focus their base".
#   • Seats 1 and 3 read the text ("P1 → you: focus their base" on seat 3).
#   • Seats 2 and 4 see "P1 whispered something to P3".
#   • Seat 2's box targets seat 4 (the blue teammate).
#   • Remove the WithP1GlobalEffect line below to A/B against the Twin Suns per-seat row.
#
# AUTOMATED PROBE (2026-09-17): covered by the same probe run — Chromium, Firefox and WebKit all PASS
#   (see WhisperChat_TwinSuns.md for the full result block).

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
#// ⚠ THIS LINE IS WHAT MAKES IT TEAM SUNS.
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
TURNPLAYER:1
