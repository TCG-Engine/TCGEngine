import re, glob, sys

WRAPPERS = [
    'SWUQueueChooseTarget', 'SWUQueueMayChooseTarget', 'SWUOpponentChoosesOwnUnit',
    'SWUApplyIndirectAssignment', 'SWUDealIndirectDamage', 'SWUDealIndirectToChosenPlayer',
]
cardid = re.compile(r'^[A-Z]{2,5}_\d{2,4}$')

files = glob.glob('SWUSim/Custom/*.php')
data = {f: open(f, encoding='utf-8').read() for f in files}
allt = "\n".join(data.values())
BARE = {k for k in re.findall(r'customDQHandlers\["([^"]+)"\]\s*=\s*function', allt) if cardid.match(k)}
print(f"bare keys to migrate: {len(BARE)}")

changes = []  # (file, kind, snippet_before -> after)

def append0_in_quoted(text, kind, fname):
    """Append #0 to a bare-key occurrence inside a quoted string at a specific match;
       used via regex sub callbacks below."""
    pass

for f in files:
    txt = data[f]
    out = []
    n = len(txt)
    i = 0

    # --- PASS A: definitions  customDQHandlers["KEY"]  ->  ["KEY#0"]
    def def_sub(m):
        k = m.group(1)
        if k in BARE:
            changes.append((f, 'DEF', f'{k} -> {k}#0'))
            return f'customDQHandlers["{k}#0"]'
        return m.group(0)
    txt = re.sub(r'customDQHandlers\["([A-Z]{2,5}_\d{2,4})"\]', def_sub, txt)

    # --- PASS B: CUSTOM decision param  'CUSTOM', 'KEY'  (KEY may be followed by | or ')
    def custom_sub(m):
        k = m.group('k')
        if k in BARE:
            changes.append((f, 'CUSTOM', f'{k} -> {k}#0'))
            return m.group('pre') + k + '#0' + m.group('post')
        return m.group(0)
    txt = re.sub(r'''(?P<pre>['"]CUSTOM['"]\s*,\s*['"])(?P<k>[A-Z]{2,5}_\d{2,4})(?P<post>['"|])''',
                 custom_sub, txt)

    # --- PASS C: indirect  "KEY~...."  ->  "KEY#0~...."
    def ind_sub(m):
        k = m.group('k')
        if k in BARE:
            changes.append((f, 'INDIRECT~', f'{k} -> {k}#0'))
            return m.group('q') + k + '#0~'
        return m.group(0)
    txt = re.sub(r'''(?P<q>['"])(?P<k>[A-Z]{2,5}_\d{2,4})~''', ind_sub, txt)

    data[f] = txt

# --- PASS D: wrapper trailing-handler — paren-aware scan on the (already B/C-updated) text
for f in files:
    txt = data[f]
    result = []
    pos = 0
    callpat = re.compile(r'\b(' + '|'.join(WRAPPERS) + r')\s*\(')
    while True:
        m = callpat.search(txt, pos)
        if not m:
            result.append(txt[pos:])
            break
        result.append(txt[pos:m.end()])
        # paren-match from m.end()-1 (the '(')
        depth = 1
        j = m.end()
        instr = None
        while j < len(txt) and depth > 0:
            c = txt[j]
            if instr:
                if c == '\\':
                    j += 2; continue
                if c == instr: instr = None
            else:
                if c in "'\"": instr = c
                elif c == '(': depth += 1
                elif c == ')': depth -= 1
            j += 1
        call = txt[m.end():j-1]  # inside-paren args
        # find LAST quoted string in call = the handler arg
        last = None
        for sm in re.finditer(r'''(['"])([^'"]*)\1''', call):
            last = sm
        newcall = call
        if last:
            inner = last.group(2)
            keypart = re.split(r'[~|]', inner, 1)[0]
            if cardid.match(keypart) and keypart in BARE:
                # insert #0 right after the key portion (idempotent: keypart has no #)
                newinner = keypart + '#0' + inner[len(keypart):]
                newcall = call[:last.start()] + last.group(1) + newinner + last.group(1) + call[last.end():]
                changes.append((f, 'WRAPPER:' + m.group(1), f'{keypart} -> {keypart}#0'))
        result.append(newcall)
        result.append(')')
        pos = j
    data[f] = ''.join(result)

# write back
for f in files:
    open(f, 'w', encoding='utf-8').write(data[f])

from collections import Counter
kinds = Counter(k for _,k,_ in changes)
print("changes by kind:")
for k,c in sorted(kinds.items()):
    print(f"  {k}: {c}")
print("total changes:", len(changes))
