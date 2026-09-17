# VISUAL CHECK — Whisper chat, Twin Suns (4 seats, free-for-all)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php --data-urlencode "schema@SWUSim/Tests/Visual/WhisperChat_TwinSuns.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   Seat N: http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=N&authKey=testschema
#   Spectator: same URL with playerID=S (no authKey)
#   Phone layout: append &swuLayout=mobile (the layout is chosen by user agent or that param, never by width)
#   Automated: node DevTools/ui-harness/swusim-whisper-chat-xbrowser.mjs
#
# WHAT THIS PINS (spec docs/superpowers/specs/2026-09-17-swusim-twinsuns-whisper-chat-design.md)
#   Only the TEXT of a whisper is private. Everyone sees THAT it happened and to whom.
#
# WHAT TO LOOK AT (desktop 1700x1050, then mobile 400x860)
#   • Above the chat input, seat 1 sees "WHISPER:" and three pill toggles P2 · P3 · P4 on ONE line (desktop sidebar
#     and phone). Off = faint outline + empty box. On = purple-filled pill with a checked box, and the "WHISPER:"
#     label turns purple. A mouse click leaves NO focus ring; Tab-focusing a box shows a light outline.
#   • Tick P3 → placeholder reads "Whisper to P3…". Send "meet me at the Death Star".
#   • Seat 1 and seat 3: an italic, purple-tinted row "P1 → P3: meet me at the Death Star"
#     (seat 3 reads "P1 → you: …").
#   • Seats 2, 4 and the spectator: a dimmed italic row "P1 whispered something to P3" — no text, no toast.
#   • The P3 box stays ticked after sending (sticky). Untick → placeholder returns to "Message...".
#   • Spectator: no Whisper row at all.
#   • Mobile: the row wraps inside the chat drawer; no horizontal page scroll.
#   • Premier (2-seat) games show no Whisper row (covered by the probe).
#
# AUTOMATED PROBE (2026-09-17, node DevTools/ui-harness/swusim-whisper-chat-xbrowser.mjs)
#   27 checks per engine across Twin Suns (seats 1-4 + spectator, DOM AND network bodies), the phone layout,
#   Team Suns and Premier. Chromium: 27/27 · Firefox: 27/27 · WebKit: 27/27 (WebKit DID launch this time).
#   Screenshots looked at in all three: seat 1 sidebar "P1 → P3: …" (italic, purple tint) with "Whisper to P3…";
#   seat 2 "P1 whispered something to P3"; phone composer row "Whisper: ☐ P2 ☑ P3 ☑ P4" above the input.
#   ⚠ First pass caught the row rendering in the UA SERIF font (no inherited font-family in the sidebar) — the
#   row now sets barlow explicitly. A check that only reads DOM/placeholders cannot see that; look at the shot.
# ⚠ NAMES — A HARNESS LIMIT, NOT A BUG (reported 2026-09-17: "whispered from logged-in P1 (ninin); another seat's view
#   just said P1"). TestSchemaSetup games have NO match record, so SWU_SEAT_USERNAMES only gets the VIEWER'S OWN seat
#   from their login session (the matchless fallback). One browser logged in as ninin therefore reads "ninin" in the
#   seat-1 tab and "P1" in every other tab. Real lobby games DO name every seat for every viewer — verified on Twin
#   Suns match M1263 / game 505902: seats 1, 2 and 3 all receive {"1":"claudebot1"}. To eyeball usernames, use a
#   game created from a real lobby by logged-in accounts. Pinned separately by GameLogAndChat_MergedSidebarStream.md.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
TURNPLAYER:1
