"""
Cost-curve Phase 2 — classify a unit's printed ability text into effect families.

An ability clause is `<trigger>: <optional?> <body>`.  We recognise a fixed list of effect
families; a clause we cannot recognise makes the whole card UNCLASSIFIED and it is dropped
from the fit, exactly as Phase 1 drops units with ability text.  Being strict is the point:
a half-understood clause priced as if it were the whole card poisons every coefficient.
"""
import re

TRIGGERS = [
    ("WhenPlayed",   r"When Played"),
    ("OnAttack",     r"On Attack"),
    ("WhenDefeated", r"When Defeated"),
    ("Action",       r"Action \[[^\]]*\]|Action"),
]
NUM = {"a": 1, "an": 1, "one": 1, "two": 2, "three": 3, "four": 4, "five": 5}

TOKENS = ["Battle Droid", "Clone Trooper", "TIE Fighter", "X-Wing", "Spy",
          "Mandalorian", "Battle Droid", "Credit", "Beast"]

def _n(s):
    if s is None:
        return 1
    s = s.strip().lower()
    return NUM.get(s, int(s) if s.isdigit() else 1)

# (family, regex, group holding N or None).  Order matters: most specific first.
#
# EVERY pattern is anchored to the end of the clause.  A prefix match is a trap: "deal 2 damage
# to a friendly ground unit and 2 damage to an enemy ground unit" prefix-matches the friendly
# half and silently discards the enemy half, which is the whole point of the card.  If the
# clause says more than the family says, the clause is UNCLASSIFIED.
TARGET_ENEMY = r"(?:an?|each|the)\s+(?:enemy|opposing)\s+"
PATTERNS = [
    # --- peek + rider, before the bare peek ---
    ("peek_discard",   r"^look at an opponent'?s hand(?:\s*[.,]|\s+and)?\s*(?:then\s+)?(?:you may\s+)?(?:choose a card|discard)\b.*$", None),
    ("peek_bounce",    r"^look at an opponent'?s hand(?:\s*[.,]|\s+and)?\s*(?:then\s+)?(?:you may\s+)?return\b.*$", None),
    ("peek",           r"^look at an opponent'?s hand$", None),
    # --- damage.  Damage aimed at YOUR OWN board is a downside, tracked separately. ---
    ("dmg_friendly",   r"^deal (\d+|a|an|one|two|three|four) damage to (?:a|another|this) friendly\b.*$", 1),
    ("dmg_friendly",   r"^deal (\d+|a|an|one|two|three|four) damage to this unit$", 1),
    ("dmg_base",       r"^deal (\d+|a|an|one|two|three|four) damage to (?:an? )?(?:enemy )?base$", 1),
    ("dmg_unit_arena", r"^deal (\d+|a|an|one|two|three|four) damage to an? (?:enemy |damaged )*(?:ground|space) unit$", 1),
    ("dmg_unit",       r"^deal (\d+|a|an|one|two|three|four) damage to an? (?:enemy |damaged )*unit$", 1),
    ("strike_true_arena", r"^an? (?:friendly )?(?:ground|space) unit deals damage equal to its power to an? (?:enemy )?(?:ground|space) unit$", None),
    ("strike_true",    r"^an? (?:friendly )?unit deals damage equal to its power to an? (?:enemy )?unit$", None),
    # --- heal ---
    ("heal_base",      r"^heal (\d+|a|an|one|two|three|all) damage from (?:your |a )?base$", 1),
    ("heal_unit",      r"^heal (\d+|a|an|one|two|three|all) damage from (?:an?|another|this) (?:friendly )?unit$", 1),
    # --- cards ---
    ("draw",           r"^draw (\d+|a|an|one|two|three) cards?$", 1),
    ("discard_random", r"^an opponent discards (\d+|a|an|one|two|three) cards? at random$", 1),
    ("discard_choose", r"^an opponent discards (\d+|a|an|one|two|three) cards?$", 1),
    # --- board ---
    ("bounce",         r"^return an? (?:non-leader |enemy |friendly )*unit to (?:its|their) owner'?s hand$", None),
    ("capture_arena",  r"^capture an? (?:enemy |non-leader )*(?:ground|space) unit$", None),
    ("capture",        r"^capture an? (?:enemy |non-leader )*unit$", None),
    # --- tokens on a unit ---
    ("give_exp",       r"^give (\d+|a|an|one|two|three) experience tokens? to (?:an?|another|each|this)\b[^.]*$", 1),
    ("give_shield",    r"^give (\d+|a|an|one|two|three) shield tokens? to (?:an?|another|each|this)\b[^.]*$", 1),
    ("give_advantage", r"^give (\d+|a|an|one|two|three) advantage tokens? to (?:an?|another|each|this)\b[^.]*$", 1),
    ("give_weakness",  r"^give (\d+|a|an|one|two|three) weakness tokens? to (?:an?|another|each|this)\b[^.]*$", 1),
]
# create N <name> token(s) -- must be the whole clause
CREATE = re.compile(r"^create (\d+|a|an|one|two|three) (" + "|".join(
    re.escape(t) for t in sorted(set(TOKENS), key=len, reverse=True)) + r") tokens?$", re.I)


def split_trigger(clause):
    """-> (trigger, optional, conditional, body)"""
    c = clause.strip()
    trigger = "Constant"
    for name, pat in TRIGGERS:
        m = re.match(r"^(?:%s)\s*:\s*" % pat, c, re.I)
        if m:
            trigger, c = name, c[m.end():]
            break
    conditional = False
    # a leading "If ...," / "While ...," gate, or a trailing "if you do"-style rider
    m = re.match(r"^(?:if|while)\b[^,]{0,90},\s*", c, re.I)
    if m:
        conditional, c = True, c[m.end():]
    if re.search(r"\bif you (?:do|control|played)\b|\bfor each\b", c, re.I):
        conditional = True
    optional = False
    m = re.match(r"^you may\s+", c, re.I)
    if m:
        optional, c = True, c[m.end():]
    return trigger, optional, conditional, c.strip()


def classify(clause):
    """-> dict(family, n, trigger, optional, conditional) or None if unrecognised."""
    trigger, optional, conditional, body = split_trigger(clause)
    body = re.sub(r"\s*\([^)]*\)", "", body).strip().rstrip(".").strip()
    if not body:
        return None
    low = body.lower()
    m = CREATE.match(body)
    if m:
        fam = "create_" + m.group(2).lower().replace(" ", "_").replace("-", "_")
        return dict(family=fam, n=_n(m.group(1)), trigger=trigger,
                    optional=optional, conditional=conditional)
    for fam, pat, grp in PATTERNS:
        m = re.match(pat, low)
        if m:
            n = _n(m.group(grp)) if grp else 1
            return dict(family=fam, n=n, trigger=trigger,
                        optional=optional, conditional=conditional)
    return None


def classify_card(residual_text):
    """-> list of clause dicts, or None if ANY clause is unrecognised."""
    out = []
    for clause in residual_text.split(" ~~ "):
        if not clause.strip():
            continue
        c = classify(clause)
        if c is None:
            return None
        out.append(c)
    return out
