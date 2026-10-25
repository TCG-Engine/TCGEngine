#!/usr/bin/env python3
"""Authoring aid for GrandArchiveSim scenarios: php DevTools-style retry harness. Run from the repo root:
    python3 DevTools/ga-fixture-try.py DevTools/ga-fixtures/<file>.php <slug> [<slug>...]
Regenerates each fixture with `create-prd-ability-fixtures.php --fixture=<slug>`; when a castSpell() has no window: argument it tries '', both, me and opp and keeps the first that the generator accepts (no dropped action, verification passes). On failure the file is restored and the failing assertion is printed per variant."""
import re,subprocess,sys
f=sys.argv[1]; slugs=sys.argv[2:]
def gen(slug):
    out=subprocess.run(['php','DevTools/create-prd-ability-fixtures.php','--fixture='+slug],capture_output=True,text=True).stdout
    return out
def detail(slug):
    o=subprocess.run(['php','DevTools/RunIntegrationTests.php','--root=GrandArchiveSim','--test='+slug],capture_output=True,text=True).stdout
    return [l for l in o.splitlines() if 'FAIL' in l]
for slug in slugs:
    t=open(f).read()
    m=re.search(r"gaScenario\('"+re.escape(slug)+r"'\)(.*?)->register\(\);",t,re.S)
    if not m: print(slug,'NOT FOUND'); continue
    body=m.group(1)
    cm=re.search(r"->castSpell\(([^\n]*?)\)\n",body)
    variants=[None]
    if cm and 'window:' not in cm.group(1):
        variants=[None]+['both','me','opp']
    elif cm:
        variants=[None]
    res=None; dets=[]
    for w in variants:
        if w is not None:
            nb=body.replace(cm.group(0),"->castSpell(%s, window: '%s')\n"%(cm.group(1),w),1)
            open(f,'w').write(t[:m.start(1)]+nb+t[m.end(1):])
        out=gen(slug)
        ok='[OK] Fixture passes' in out and '[WARN]' not in out
        if ok: res='OK'+('' if w is None else ' (window %s)'%w); break
        res=('FAIL' if '[WARN]' not in out else 'DROPPED')
        last=out
        dets.append((w,res,detail(slug)[:1]))
    if res.startswith('OK'):
        print('OK  ',slug,res[2:])
    else:
        open(f,'w').write(t)  # restore original
        gen(slug)  # leave the fixture as the original text produces it, for inspection
        print(res,slug); print('\n'.join(l for l in last.splitlines() if 'WARN' in l or 'FAIL' in l or 'Action:' in l)[:600]); print('  variants:',dets)
