# VISUAL CHECK — Twin Suns house rules on the board (2026-10-19)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Spec: docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md
# SETUP — load in the Test Schema Editor, or:
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php \
#          --data-urlencode "schema@SWUSim/Tests/Visual/HouseRules_Board.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   PHONE: append &swuLayout=mobile at 430x932. Check Chromium, Firefox AND Safari/WebKit.
#   Automated: DevTools/ui-harness/swusim-house-rules-board-xbrowser.mjs (screenshots; read them).
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • A green "HOUSE RULES" chip at the right end of the sidebar's Round / Phase row ("HR" beside the phase on a phone).
#     Click/tap it: a small list drops below — "Extra Base HP +10", "Take Credit 4th Action", "Plan Scry 2". Click
#     anywhere else: it closes.
#   • Your counter buttons: Blast, Plan AND "Credit", same style, side by side with the Take Initiative button.
#     Hover Plan: "look at the top 2 cards of your deck: put each on the top or bottom". Hover Credit: "gain a Credit token".
#   • Every base's HP pip/health bar reads 40 (30 printed + 10).
#   • The game log's first lines include "House rules: Extra Base HP +10, Take Credit 4th Action, Plan Scry 2".
#   • A game WITHOUT house rules (any other Twin Suns visual fixture): no chip, no Credit button.

## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: extraBaseHp=10,takeCredit,planScry=2
WithP1Hand: SOR_095
WithP1Deck: [SOR_095 SOR_128 SOR_095]
