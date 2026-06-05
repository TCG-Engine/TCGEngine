import re, glob
from collections import Counter

FUNCS = ['SWUQueueChooseTarget','SWUQueueMayChooseTarget','SWUOpponentChoosesOwnUnit',
 'SWUApplyIndirectAssignment','SWUDealIndirectDamage','SWUDealIndirectToChosenPlayer',
 'SWUDealIndirectToEachOpponent','SWUOfferDroidPayment','SWUDispatchDroidContinuation',
 '_topDeckSearchBegin','SWUQueueDefeatUpgrade']
cardid = re.compile(r'^[A-Z]{2,5}_\d{2,4}$')

data = {f: open(f, encoding='utf-8').read() for f in glob.glob('SWUSim/Custom/*.php')}
allt = "\n".join(data.values())
# BARE = keys that NOW exist only as #0 (already-migrated defs) — these are the continuation keys
MIG = {m for m in re.findall(r'customDQHandlers\["([A-Z]{2,5}_\d{2,4})#0"\]', allt)}

def find_close(txt, open_idx):
    """comment+string aware paren match; open_idx points at '(' . returns index after ')'."""
    depth=0; j=open_idx; instr=None; n=len(txt)
    while j<n:
        c=txt[j]
        if instr:
            if c=='\\': j+=2; continue
            if c==instr: instr=None
            j+=1; continue
        # not in string: check comments
        if c=='/' and j+1<n and txt[j+1]=='/':
            j=txt.find('\n',j);  j = n if j<0 else j;  continue
        if c=='#':
            j=txt.find('\n',j);  j = n if j<0 else j;  continue
        if c=='/' and j+1<n and txt[j+1]=='*':
            e=txt.find('*/',j+2); j = n if e<0 else e+2; continue
        if c in "'\"": instr=c; j+=1; continue
        if c=='(': depth+=1
        elif c==')':
            depth-=1
            if depth==0: return j+1
        j+=1
    return -1

changes=Counter(); samples=[]
for f in data:
    txt=data[f]
    cp=re.compile(r'\b('+'|'.join(FUNCS)+r')\s*\(')
    out=[]; pos=0
    while True:
        m=cp.search(txt,pos)
        if not m:
            out.append(txt[pos:]); break
        open_idx=m.end()-1
        close=find_close(txt,open_idx)
        if close<0:
            out.append(txt[pos:m.end()]); pos=m.end(); continue
        call=txt[open_idx+1:close-1]
        # last quoted string in call (string-aware: skip over escapes)
        last=None
        for sm in re.finditer(r'''(['"])((?:\\.|[^\\])*?)\1''', call):
            last=sm
        newcall=call
        if last:
            inner=last.group(2)
            key=re.split(r'[~|]', inner, 1)[0]
            if cardid.match(key) and key in MIG:
                newinner=key+'#0'+inner[len(key):]
                newcall=call[:last.start()]+last.group(1)+newinner+last.group(1)+call[last.end():]
                changes[m.group(1)]+=1
                samples.append(f"{f.split('/')[-1]}: {m.group(1)}(... {key} -> {key}#0)")
        out.append(txt[pos:open_idx+1]); out.append(newcall); out.append(')')
        pos=close
    data[f]=''.join(out)

for f in data: open(f,'w',encoding='utf-8').write(data[f])
print("v2 changes by function:")
for k,c in sorted(changes.items()): print(f"  {k}: {c}")
print("total:", sum(changes.values()))
for s in samples: print("   ",s)
