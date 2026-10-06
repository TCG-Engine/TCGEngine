"""Summarize paired games; uncertainty is clustered by seed (both starting orders)."""
import argparse,csv,json,math,pathlib,statistics
p=argparse.ArgumentParser();p.add_argument('directory');p.add_argument('--phase',default='screen');a=p.parse_args();folder=pathlib.Path(a.directory)
files=list(folder.glob(a.phase+'-*.json'));data={}
for f in files:
    x=json.loads(f.read_text(encoding='utf-8'))
    if 'games' in x:data[(x['variant'],x['opponent'])]=x
def summary(x):
    rows=x['games'];n=len(rows);t=sum(r['eligibleTurns'] for r in rows)
    energy=None
    if all('zeroReasons' in r for r in rows):
        energy=100*sum(sum(reason=='missingAttachedEnergy' for reason in (r['zeroReasons'].values() if isinstance(r['zeroReasons'],dict) else r['zeroReasons'])) for r in rows)/t
    late=[(r,turn) for r in rows for turn in r['damageByTurn'] if turn['turn']>=4]
    lateEnergy=None
    if late and all('zeroReasons' in r for r in rows):
        lateEnergy=100*sum(isinstance(r['zeroReasons'],dict) and r['zeroReasons'].get(str(turn['turn']))=='missingAttachedEnergy' for r,turn in late)/len(late)
    usage={'retrievalUsedGamePct':None,'retrievalPlaysPerGame':None,'retrievalEnergyPerGame':None}
    if all('resourceUsage' in r for r in rows):
        usage={'retrievalUsedGamePct':100*sum(r['resourceUsage']['retrievalPlays']>0 for r in rows)/n,
               'retrievalPlaysPerGame':sum(r['resourceUsage']['retrievalPlays'] for r in rows)/n,
               'retrievalEnergyPerGame':sum(r['resourceUsage']['retrievalEnergyReturned'] for r in rows)/n}
    consistency={}
    if all('consistencyUsage' in r for r in rows):
        for label,plays,draws in [('lacey','laceyPlays','laceyCardsDrawn'),('iris','irisPlays','irisCardsDrawn'),('helmet','helmetPlays','helmetCardsDrawn')]:
            consistency[label+'UsedGamePct']=100*sum(r['consistencyUsage'][plays]>0 for r in rows)/n
            consistency[label+'PlaysPerGame']=sum(r['consistencyUsage'][plays] for r in rows)/n
            consistency[label+'CardsDrawnPerGame']=sum(r['consistencyUsage'][draws] for r in rows)/n
        if x.get('consistencyMetricsVersion',0)<2:
            # Earlier exploratory counters included the automatic next-turn draw.
            consistency['helmetCardsDrawnPerGame']=None
        attacks=sum(r['consistencyUsage']['relicanthAttacks'] for r in rows)
        consistency['fiveFossilAttackPct']=100*sum(r['consistencyUsage']['fiveFossilAttacks'] for r in rows)/attacks if attacks else None
        consistency['laceyLatePlaysPerGame']=sum(r['consistencyUsage']['laceyLatePlays'] for r in rows)/n
    return {'games':n,'wins':sum(r['winner']==1 for r in rows),'winPct':100*sum(r['winner']==1 for r in rows)/n,
    'zeroPct':100*sum(r['zeroTurns'] for r in rows)/t,'damagePerTurn':sum(r['earlyDamage'] for r in rows)/t,
    'bulwarkPct':100*sum(r['bastiodonEvolved'] for r in rows)/n,'eligibleTurns':t,
    'openingDamagePct':100*sum(any(t['turn']==(2 if r['firstPlayer']==1 else 1) and t['damage']>0 for t in r['damageByTurn']) for r in rows)/n,
    'energyStarvedPct':energy,'lateZeroPct':100*sum(turn['damage']==0 for r,turn in late)/len(late) if late else None,
    'lateEnergyStarvedPct':lateEnergy,**usage,**consistency}
out=[]
for (name,opp),x in sorted(data.items()):
    s=summary(x);b=data.get(('baseline',opp));delta=None;ci=None
    if b:
        if (x['policy'],x['baselineHash'],x['seed'],x['pairs'])!=(b['policy'],b['baselineHash'],b['seed'],b['pairs']):raise RuntimeError('Incompatible baseline: '+name)
        pairs={}
        for r,q in zip(x['games'],b['games']):
            if (r['seed'],r['firstPlayer'])!=(q['seed'],q['firstPlayer']):raise RuntimeError('Unpaired rows')
            pairs.setdefault(r['seed'],[]).append(int(r['winner']==1)-int(q['winner']==1))
        d=[statistics.mean(v) for v in pairs.values()];delta=100*statistics.mean(d);ci=100*1.96*statistics.stdev(d)/math.sqrt(len(d)) if len(d)>1 else 0
        s['zeroDelta']=s['zeroPct']-summary(b)['zeroPct'];s['damageDelta']=s['damagePerTurn']-summary(b)['damagePerTurn']
    out.append({'variant':name,'opponent':opp,**s,'winDelta':delta,'paired95HalfWidth':ci,'policy':x['policy']})
if out:
    target=folder/(a.phase+'-summary.csv')
    with target.open('w',newline='',encoding='utf-8') as f:w=csv.DictWriter(f,fieldnames=list(out[0]));w.writeheader();w.writerows(out)
    for r in out:print(f"{r['variant']:27} {r['opponent']:17} win {r['winPct']:5.1f}% delta {r['winDelta']:+5.1f} +/-{r['paired95HalfWidth']:4.1f}; zero {r['zeroPct']:5.1f}%; dmg {r['damagePerTurn']:6.1f}; Bulwark {r['bulwarkPct']:4.1f}%")
