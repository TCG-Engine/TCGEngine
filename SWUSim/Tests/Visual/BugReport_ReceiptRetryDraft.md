# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the in-game "Report a Bug" modal (Core/UILibraries<date>.js,
# openBugReportModal / submitBugReport / _bugReportSubmitLocal), which only exists in a browser.
#
# VISUAL CHECK — Petranaki bug report: receipt, retries, saved draft
#
# OWNER REQUIREMENT (2026-10-22, docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md)
#   "players claimed they submitted a bug report and i never saw it. so maybe let's add a retry mechanism"
#   SWUSim reports are now stored on the Petranaki box itself; a submitted report must reliably land.
#
# AUTOMATED: DevTools/ui-harness/swusim-bugreport-xbrowser.mjs covers all of this in Chromium, Firefox and WebKit
# (server mocked for the failure screens). This file is for a human look at the same screens.
#
# SETUP — any SWUSim game you are seated in, e.g. a Test Schema Editor board:
#   .../NextTurn.php?folderPath=SWUSim&gameName=<n>&playerID=1&authKey=testschema&viewerPerspective=1&opponentID=2
#   Gear menu → Report Bug (or the end-of-game screen's Report Bug button).
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • SUCCESS. Type a description, press Submit Report. The modal's body is REPLACED by:
#       "Bug report P-<n> saved."   (large)
#       "Mention P-<n> if you bring it up on Discord."
#     and a single Close button. It does NOT auto-dismiss (the old flash vanished after 6s; the number is
#     what the player quotes on Discord). The report shows on zzBugReportViewer.php?rootName=SWUSim as P-<n>.
#   • RETRYING (DevTools → Network → Offline, then Submit). The status line under the inputs reads
#       "Connection problem — retrying (attempt 2 of 4)…"  then 3 of 4, 4 of 4  (2s, 5s, 10s apart)
#     Go back Online during the retries: the receipt appears, and the viewer lists ONE report, not two.
#   • GIVING UP (stay Offline). After the 4th attempt, in red:
#       "Couldn't send the report after 4 tries. Your text is saved here — try again in a minute, or post it on Discord."
#     The modal stays open with the text intact; Submit Report is clickable again.
#   • DRAFT. Type a description, reload the page, reopen Report Bug: the text (and Discord name) is still there.
#     After a successful submit, reopening shows an EMPTY box.
#   • RATE LIMIT. Submit twice within 30 seconds from the same seat: the second shows the server's
#     "Please wait <s> seconds before sending another report." in red, with NO retries.
#   • PHONE (375px wide). The receipt and its Close button fit on screen.
#   • OTHER SIMS ARE UNCHANGED. In a Grand Archive / Azuki / FaB game the modal behaves exactly as before
#     (one attempt, the 6s "Bug report submitted." flash) — the new behaviour is SWUSim-only.
