"""Fingerprint gameplay sources used to qualify a replay report."""
import hashlib,pathlib
def runtime_fingerprint(repo):
 repo=pathlib.Path(repo);paths=list((repo/'Core').rglob('*.php'))
 paths+=list((repo/'FaBSim').glob('*.php'))+list((repo/'FaBSim/Custom').glob('*.php'))
 paths+=list((repo/'FaBSim/GeneratedCode').glob('*.php'))
 paths+=[repo/'DevTools/RunIntegrationTests.php',repo/'Schemas/FaBSim/GameSchema.txt']
 digest=hashlib.sha256()
 for p in sorted(set(paths)):
  digest.update(p.relative_to(repo).as_posix().encode());digest.update(p.read_bytes())
 return digest.hexdigest()
