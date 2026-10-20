"""Capture results of the existing runner; no expected-snapshot updates."""
import pathlib,subprocess,shutil,json,sys,datetime,os,hashlib
from runtime_fingerprint import runtime_fingerprint
here=pathlib.Path(__file__).resolve().parent
repo=pathlib.Path(os.environ.get('FAB_REGRESSION_REPO',next((p for p in here.parents if (p/'Core').is_dir()),pathlib.Path.cwd())))
run_id=sys.argv[1] if len(sys.argv)>1 else datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ')
fixtures=repo/'Tests/Integration/FaBSim'
def inventory():
 result={}
 for p in fixtures.iterdir():
  if not p.is_dir() or not (p/'meta.json').exists():continue
  if p.name.startswith('negative-control-'):raise RuntimeError('Wait for disposable negative controls to finish before full replay')
  digest=hashlib.sha256()
  for name in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt']:
   digest.update(name.encode());digest.update((p/name).read_bytes())
  result[p.name]=dict(fixture_sha256=digest.hexdigest(),name=json.loads((p/'meta.json').read_text(encoding='utf-8-sig'))['name'])
 return result
before=inventory();slugs=sorted(before)
runtime_before=runtime_fingerprint(repo)
run=subprocess.run([shutil.which('php'),'DevTools/RunIntegrationTests.php','--root=FaBSim'],cwd=repo,text=True,encoding='utf-8',capture_output=True)
if runtime_before!=runtime_fingerprint(repo):raise RuntimeError('Runtime changed during replay; no qualified replay report saved')
if before!=inventory():raise RuntimeError('Fixture inventory changed during replay; no qualified replay report saved')
lines=[l for l in run.stdout.splitlines() if l.startswith(('[PASS]','[FAIL]'))]
if len(lines)!=len(slugs):raise RuntimeError('Runner result count differs from fixture inventory: '+run.stdout+' '+run.stderr)
results=[]
for slug,line in zip(slugs,lines):
 meta=json.loads((fixtures/slug/'meta.json').read_text(encoding='utf-8-sig'))
 if not line.startswith('[PASS] '+meta['name']) and not line.startswith('[FAIL] '+meta['name']):raise RuntimeError('Unexpected result order '+slug+' '+line)
 digest=hashlib.sha256()
 for name in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt']:
  digest.update(name.encode());digest.update((fixtures/slug/name).read_bytes())
 results.append(dict(slug=slug,fixture_sha256=digest.hexdigest(),passed=line.startswith('[PASS]'),result=line))
report=dict(command='php DevTools/RunIntegrationTests.php --root=FaBSim',runtime_sha256=runtime_before,returncode=run.returncode,completed_utc=datetime.datetime.now(datetime.timezone.utc).isoformat(),fixtures=results,stdout=run.stdout,stderr=run.stderr)
(here/('card-regression-replay-'+run_id+'.json')).write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
print('\n'.join(l for l in run.stdout.splitlines() if l.startswith(('[FAIL]','[SUMMARY]'))));print(run.stderr,end='')
raise SystemExit(run.returncode)
