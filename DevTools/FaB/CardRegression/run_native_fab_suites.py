from pathlib import Path
import subprocess,json,sys,datetime,uuid
root=Path(r'C:\xampp\htdocs\TCGEngine');native=root/'DevTools/FaB/CardRegression'
sys.path.insert(0,str(native));from runtime_fingerprint import runtime_fingerprint
fingerprint=runtime_fingerprint(root);runs=[]
for file in ['DevTools/FaB/wtr_test.php','DevTools/FaB/arc_test.php']:
    run=subprocess.run([r'C:\xampp\php\php.exe',file],cwd=root,text=True,capture_output=True)
    runs.append(dict(command='php '+file,exit_code=run.returncode,stdout=run.stdout,stderr=run.stderr))
    print(file+': '+str(run.returncode),flush=True)
    if run.returncode:print(run.stdout+run.stderr,flush=True)
assert fingerprint==runtime_fingerprint(root),'Runtime changed while native suites ran'
report=dict(runtime_sha256=fingerprint,completed_utc=datetime.datetime.now(datetime.timezone.utc).isoformat(),runs=runs)
run_id=sys.argv[1] if len(sys.argv)>1 else datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ')+'-'+uuid.uuid4().hex[:8]
assert all(c.isalnum() or c in '-_' for c in run_id)
target=native/('card-regression-native-suite-'+run_id+'-results.json')
with target.open('x',encoding='utf-8') as output:output.write(json.dumps(report,indent=2)+'\n')
raise SystemExit(0 if all(r['exit_code']== 0 for r in runs) else 1)
