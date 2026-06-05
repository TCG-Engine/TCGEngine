---
name: swusim-set-validation
description: Use when asked to verify a SWU set is "card complete" / review a set for implementation gaps (e.g. "is JTL card complete?", "validate the SOR set", "what's left in LOF?"). Runs two independent completeness checks — a dictionary-vs-Done-list diff AND an inverse-stub sweep that proves every trigger stub has a real handler — classifies each finding as a real gap or a false positive, and reports. Read-only validation; hand real gaps to swusim-implement-card.
---

# SWUSim Set Validation

Verify a set is genuinely **card complete** and surface any gaps. "In the Done list" is NOT proof of completeness — cards routinely sit in the list with a silently-deferred rider (a trigger stub wired to fire but no handler registered → a silent in-game no-op). This skill catches exactly that.

**Input:** one set abbreviation (e.g. `JTL`, `SOR`, `LOF`). **Output:** a completeness verdict + a classified gap list. This is **read-only** — it does not implement anything. Hand confirmed gaps to `swusim-implement-card`.

Run from the repo root: `/Users/mariotorresjr/Documents/GitHub/Karabast-SWU/SWUStats`. Set `SET=JTL` (uppercase) below.

---

## Why two methods (both are required)

| Check | Catches | Misses on its own |
|---|---|---|
| **A — Dictionary diff** | CardIDs never added to the `### Already Done` list at all | cards in the list that are only half-implemented |
| **B — Inverse-stub sweep** | cards in the list whose trigger stub has **no handler** (silent no-op) | cost-modifier / passive / aura cards that have no trigger stub |

Run **both**. Method A found JTL_070/JTL_191 (never listed); Method B found JTL_039, JTL_089, and the 4 pilot leaders JTL_003/006/009/017 (listed but a rider unwired) — none of which Method A could see.

---

## Method A — Dictionary diff (Done list vs printed set)

The generated dictionary is the authoritative card roster. Diff it against the doc's `### Already Done` line.

```bash
SET=JTL
DICT=SWUSim/GeneratedCode/GeneratedCardDictionaries.php
DOC=SWUSim/docs/$(echo $SET | tr A-Z a-z)-implement.md
grep -oE "${SET}_(T?[0-9]+)" "$DICT" | sort -u > /tmp/set_all.txt
# The Already Done list is the line immediately AFTER the "### Already Done" heading.
# (Find it by heading — never hardcode a line number; header edits shift it.)
ln=$(grep -n "^### Already Done" "$DOC" | cut -d: -f1)
sed -n "$((ln+1))p" "$DOC" | grep -oE "${SET}_(T?[0-9]+)" | sort -u > /tmp/set_done.txt
echo "=== In dictionary but NOT in Done list (should be empty) ==="
comm -23 /tmp/set_all.txt /tmp/set_done.txt | tr '\n' ' '; echo
echo "dictionary: $(wc -l < /tmp/set_all.txt)  done-list: $(wc -l < /tmp/set_done.txt)"
```

A non-empty "In dictionary but NOT in Done list" = cards never touched. The roster = all numbered IDs (`${SET}_001…`) + tokens (`${SET}_T0x`). For a Premier set that's ~262 numbered + ~4 tokens = ~266 (the printed count counts double-sided leaders/tokens; the distinct CardIDs are what matters here).

---

## Method B — Inverse-stub sweep (every stub has a handler)

A `Has*Ability(cardID)` returning true means the engine WILL fire that trigger window — but the effect lives in a separately-registered handler closure that may never have been written. A stub with no handler is a **silent no-op in-game** (the trigger dispatches to nothing). This sweep cross-references every stub against the handler registries.

Registries by stub (a stub is satisfied if ANY of its registries has a `${CID}:` key, or for the bare-key registries a `${CID}` key):
- `HasWhenPlayedAbility` → `whenPlayedAbilities` | `leaderAbilities` (bare key) | `baseAbilities` (bare key)
- `HasOnAttackAbility` → `onAttackAbilities`
- `HasOnAttackEndAbility` → `onAttackEndAbilities`
- `HasWhenDefeatedAbility` → `whenDefeatedAbilities` | `cardDiscardedHandlers` (⚠ the on-discard path — JTL_221's "When Defeated" lives here, NOT in whenDefeatedAbilities)
- `HasOnDefenseAbility` → `onDefenseAbilities`
- `HasWhenPlayedAsUpgradeAbility` → `whenPlayedAsUpgradeAbilities` | `whenPlayedAbilities` (CollectWhenPlayedAsUpgradeTriggers falls back to WhenPlayed)
- `HasOnAttachedAbility` → `onAttachedAbilities`

```bash
SET=JTL python3 - <<'EOF'
import re,glob,os
SET=os.environ['SET']
stub=open('SWUSim/GeneratedCode/GeneratedAbilityStubs.php').read()
funcs={
 'HasWhenPlayedAbility':['whenPlayedAbilities','leaderAbilities','baseAbilities'],
 'HasOnAttackAbility':['onAttackAbilities'],
 'HasOnAttackEndAbility':['onAttackEndAbilities'],
 'HasWhenDefeatedAbility':['whenDefeatedAbilities','cardDiscardedHandlers'],
 'HasOnDefenseAbility':['onDefenseAbilities'],
 'HasWhenPlayedAsUpgradeAbility':['whenPlayedAsUpgradeAbilities','whenPlayedAbilities'],
 'HasOnAttachedAbility':['onAttachedAbilities'],
}
bare={'leaderAbilities','baseAbilities'}  # keyed by bare CardID, not CardID:N
custom=''
for f in glob.glob('SWUSim/Custom/*.php'): custom+=open(f).read()
def cases(fn):
    m=re.search(r'function '+fn+r'\(.*?switch.*?\{(.*?)\n\s*\}', stub, re.S)
    return set(re.findall(r"case '("+SET+r"_[0-9]+)'", m.group(1))) if m else set()
problems=[]
for fn,regs in funcs.items():
    for cid in sorted(cases(fn)):
        ok=False
        for reg in regs:
            if f'{reg}["{cid}:' in custom or f"{reg}['{cid}:" in custom: ok=True;break
            if reg in bare and (f'{reg}["{cid}"]' in custom or f"{reg}['{cid}']" in custom): ok=True;break
        if not ok: problems.append((cid,fn))
if problems:
    print("POTENTIAL UNWIRED STUBS (stub present, no matching handler):")
    for cid,fn in problems: print(" ",cid,fn)
else:
    print("✅ CLEAN — every "+SET+" trigger stub has a matching handler.")
EOF
```

---

## Triage each Method-B finding (real gap vs false positive)

The sweep is a heuristic. Confirm each hit before reporting it as a gap — read the card text and grep the four ability files:

```bash
CID=JTL_039
awk '/\$textData = array \(/,/^\);/' SWUSim/GeneratedCode/GeneratedCardDictionaries.php | grep -A1 "'$CID'"
grep -rn "$CID" SWUSim/Custom/*.php | grep -iE "abilities|customDQ|cardDiscarded|leaderAbilities|baseAbilities"
```

Classify:
- **Real gap** — the card text describes a trigger effect, and no handler exists in ANY registry (e.g. JTL_039's "When Defeated: create 2 TIEs" had only the When-Played handler). → carry to the implement step.
- **False positive** — the effect IS implemented, just via a registry the sweep didn't map for that stub. The known one: a **"When Defeated"** stub satisfied by **`cardDiscardedHandlers['CID:0']`** (the on-discard hook, fires synchronously when the card hits discard — JTL_221 Stolen AT-Hauler). Also a `whenPlayedAsUpgrade` stub legitimately falling back to a `whenPlayed` handler. Note it and move on.

⚠ **"No explicit FromUpgrade ref" ≠ unwired (a false-alarm trap).** A granted **"attached unit gains: On Attack: …"** rides the **generic `OnAttackFromUpgrade` seam** — `CollectCombatStep1Triggers` scans the attacker's upgrades and fires for ANY upgrade whose CardID has an `$onAttackAbilities["X:0"]`, calling that same closure with the host mzID. So a leader/pilot/upgrade that already has an `onAttackAbilities` key needs **NO** `onAttackFromUpgradeAbilities` entry and no stub for its host-grant to work (e.g. JTL_018 — its deployed-unit On Attack key doubles as the pilot's host-grant; JTL_172/SOR_137 likewise). Before reporting a granted-On-Attack as a gap, **reproduce it via TestSchemaStep** (place the card as a subcard, attack with the host, see if the offer fires). Same "reproduce before concluding" rule as for a suspected engine bug.

Two recurring real-gap shapes to expect:
- **Dual-window cards** where only one window was built — "When Played / When this unit completes an attack" (JTL_089), "When Played: X. When Defeated: Y" (JTL_039). The unbuilt window silently no-ops.
- **Pilot leaders' "When deployed as an upgrade:"** ability — the long-deferred deploy-as-Pilot Epic variant (JTL_003/006/009/017). The pilot-deploy flush path already exists (`SWUDeployLeader` Pilot branch → `_SWUFinalizeUpgradeAttach` → `CollectWhenPlayedAsUpgradeTriggers`); the gap is just an unregistered `$whenPlayedAsUpgradeAbilities["X:0"]`.

---

## Stub-detection drift (a source of false NEGATIVES — Method B can't see these)

The `Has*Ability` stubs are generated by substring-matching card text in `zzCardCodeGenerator.php`. Reworded/parenthetical phrasings slip through, so the stub is **absent** and the trigger silently never fires — Method B won't flag it (no stub to check). When a card's rider seems missing but it's not in the sweep output, suspect this. Real example: `"When this unit completes an attack (and survives):"` — the "(and survives)" parenthetical broke the generator's `"completes an attack:"` match, so JTL_070/JTL_089/SEC_096 were never given an onAttackEnd stub. Fix = patch the generator detection (durable) AND hand-add the `case` to `GeneratedAbilityStubs.php` (the generator isn't re-run mid-session). To audit, grep the dictionary for known-tricky phrasings and confirm each has a stub:

```bash
awk '/\$textData = array \(/,/^\);/' SWUSim/GeneratedCode/GeneratedCardDictionaries.php \
  | grep -iE "completes an attack \(and survives\)|when deployed as an upgrade|when this unit is attacked|when deployed:" \
  | grep -oE "'${SET}_[0-9]+'"
# then for each, confirm Has<Window>Ability(CID) is true (grep the stub switch).
```

---

## Final confirmation + report

After triage (and after any fixes land via `swusim-implement-card`), re-run BOTH methods until Method A's diff is empty AND Method B prints `✅ CLEAN`, then capture the regression count:

```bash
curl -s "http://localhost:3400/TCGEngine/zzRegressionSWUSim.php" 2>/dev/null | sed 's/<[^>]*>//g' | grep -iE "passed|failed"
```

Report:
- **Verdict** — `100% card complete (N/N)` only when Method A diff is empty AND Method B is clean; otherwise list the open gaps.
- **Gap table** — CardID · what's missing · real-gap-or-false-positive.
- **Regression** — `passed / failed`.

If gaps are found and the user wants them closed, hand them to **swusim-implement-card** (tests-first; Simple/Medium proceed autonomously per the impl-card gate, Hard stops for review). When you fix a generator-detection gap, **patch `zzCardCodeGenerator.php` too** so it survives the next regen, then re-run the sweep.
