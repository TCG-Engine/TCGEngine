#// TWIN SUNS HOUSE RULES — how a chosen rule set reaches the game (docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md).
#// The waiting room resolves the active rules at Start; SWUSetupGame hands them to SWUApplyHouseRules, which stores one
#// HR_* game variable per rule and logs a single "House rules: …" line. Every engine hook reads them back through
#// SWUHouseRule(). The `WithHouseRules:` directive is the SAME SWUApplyHouseRules call, applied after the fixture.
# ChosenRules_AreStoredAsGameVariables
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: extraBaseHp=10,takeCredit,planScry=3
## WHEN
## EXPECT
SWUVAR:HR_EXTRABASEHP:10
SWUVAR:HR_TAKECREDIT:1
SWUVAR:HR_PLANSCRY:3
SWUVAR:HR_TAKERESTORE:
LOGCONTAINS:House rules: Extra Base HP +10, Take Credit 4th Action, Plan Scry 3

---

# NoRules_NothingIsStored_AndNothingIsLogged
#// A game started with no house rules carries no HR_* variable at all, so every hook stays a no-op. Also proves the
#// previous section's rules did not leak into this one through the shared test process.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
## EXPECT
SWUVAR:HR_EXTRABASEHP:
SWUVAR:HR_TAKECREDIT:
SWUVAR:HR_PLANSCRY:
LOGCOUNT:0:House rules
