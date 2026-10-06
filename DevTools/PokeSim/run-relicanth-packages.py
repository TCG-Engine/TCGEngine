"""Isolated PHP workers; frozen plans, reproducible pairs and process-local blank cards."""
import argparse, concurrent.futures, json, pathlib, shutil, subprocess
p=argparse.ArgumentParser();p.add_argument('directory');p.add_argument('--phase',default='screen');p.add_argument('--pairs',type=int,default=75);p.add_argument('--seed',type=int,default=70001);p.add_argument('--workers',type=int,default=4);p.add_argument('--php',default=shutil.which('php') or r'C:\xampp\php\php.exe')
a=p.parse_args();root=pathlib.Path(__file__).resolve().parents[2];folder=pathlib.Path(a.directory).resolve();plan=folder/(a.phase+'-plan.json');data=json.loads(plan.read_text(encoding='utf-8'))
if not 1<=a.workers<=8 or not 1<=a.pairs<=10000 or not 1<=a.seed<=2147483647-a.pairs+1:p.error('Invalid workers, pairs or seed range')
def run(job):
    variant,opp=job;out=folder/(a.phase+'-'+variant+'-'+opp+'.json')
    if out.exists(): raise RuntimeError('Existing output: '+str(out))
    cmd=[a.php,str(root/'DevTools/PokeSim/relicanth-packages.php'),'--plan='+str(plan),'--variant='+variant,'--opponent='+opp,'--pairs='+str(a.pairs),'--seed='+str(a.seed),'--output='+str(out)]
    r=subprocess.run(cmd,cwd=root,capture_output=True,text=True,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    if r.returncode:raise RuntimeError(r.stdout+r.stderr)
    rows=json.loads(out.read_text(encoding='utf-8'))['games']
    bad=[x for x in rows if x['status']!='complete']
    if bad:raise RuntimeError(str(out)+' incomplete games: '+str(bad[:2]))
    return r.stdout.strip()
jobs=[(v,o) for v in data['variants'] for o in data['opponents']]
with concurrent.futures.ThreadPoolExecutor(max_workers=a.workers) as pool:
    for future in concurrent.futures.as_completed([pool.submit(run,job) for job in jobs]):print(future.result(),flush=True)
