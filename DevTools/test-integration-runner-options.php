<?php
// Executes the exact supplied runner in an isolated tiny filesystem with controlled
// dependency doubles. No real engine, card runtime, fixtures or snapshots are loaded.
$options = getopt('', ['runner:', 'expected-sha:']);
$runner = strval($options['runner'] ?? '');
$expected = strval($options['expected-sha'] ?? '');
if (!is_file($runner) || !preg_match('/^[a-f0-9]{64}$/D', $expected) || hash_file('sha256', $runner) !== $expected) {
  fwrite(STDERR, "Exact --runner and --expected-sha are required.\n"); exit(1);
}
$work = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'runner-cli-contract-' . bin2hex(random_bytes(5));
if (!mkdir($work, 0777, true)) throw new RuntimeException('Cannot create controlled workspace.');
$stub = <<<'PHP'
<?php
$stubRoot = dirname(__DIR__);
$stubConfig = json_decode(file_get_contents($stubRoot . '/stub-config.json'), true);
if (!empty($stubConfig['splitOutput'])) register_shutdown_function(static function() { echo 'TAIL_UNTERMINATED'; });
function StubTrace($text) { file_put_contents($GLOBALS['stubRoot'] . '/trace.txt', $text . "\n", FILE_APPEND); }
function RegressionRepoRoot() { return $GLOBALS['stubRoot']; }
function RegressionListFixtures($root) { StubTrace('discovery'); return array_column($GLOBALS['stubConfig']['fixtures'], 'slug'); }
function RegressionFixtureDir($root, $slug) { return RegressionRepoRoot() . '/Tests/Integration/' . $root . '/' . $slug; }
function RegressionEnsureDir($path) { if (!is_dir($path) && !mkdir($path, 0777, true)) throw new RuntimeException('mkdir'); }
function RegressionNormalizeNewlines($text) { return str_replace("\r\n", "\n", $text); }
function RegressionNormalizeGamestateTextForRoot($root, $text) { return $text; }
function RegressionNormalizeGamestateTextForComparison($root, $text) { return $text; }
function EngineLoadRootRuntime($root) { if (!empty($GLOBALS['stubConfig']['prepareFail'])) throw new RuntimeException('Controlled preparation failure.'); }
function ParseGamestate($path) {
  StubTrace('run:' . $GLOBALS['gameName']);
  if (!empty($GLOBALS['stubConfig']['splitOutput'])) { echo '[FA'; echo 'IL] controlled chunked diagnostic '; echo str_repeat('y', 10000); echo "CHUNK_END\n"; }
}
function RegressionLoadActionsForFixture($dir) { return json_decode(file_get_contents($dir . '/actions.json'), true); }
function RegressionLoadAssertionsForFixture($dir) { return json_decode(file_get_contents($dir . '/assertions.json'), true); }
function RegressionEvaluateAssertionsForStep($assertions, $step) {
  if ($step === 0 && !empty($assertions[0]['stubFailure'])) {
    $message = !empty($assertions[0]['huge']) ? 'LONG_BEGIN' . str_repeat('x', 50000) . "LONG_END\nContextTail" : 'Controlled assertion failure.';
    return [false, $message];
  }
  return [true, ''];
}
function EngineRunAction($action, $root, $game, $options) { StubTrace('action'); return ['success' => empty($action['expectFailure']), 'message' => 'Controlled rejection.', 'writeGamestate' => false]; }
function GetDecisionQueue($player) { return []; }
function RegressionCurrentGamestateText($root, $game) { return "controlled-initial\n"; }
function RegressionFormatSnapshotDiff($expected, $actual) { return 'Controlled snapshot mismatch.'; }
function RegressionDeleteDirRecursive($dir) {
  $base = realpath(RegressionRepoRoot() . '/FakeRoot/Games');
  $gaBase = realpath(RegressionRepoRoot() . '/GrandArchiveSim/Games');
  $target = realpath($dir);
  if ($target === false || !($base !== false && str_starts_with($target, $base . DIRECTORY_SEPARATOR)) && !($gaBase !== false && str_starts_with($target, $gaBase . DIRECTORY_SEPARATOR))) throw new RuntimeException('Unsafe controlled cleanup target.');
  if (is_file($target . '/Gamestate.txt')) unlink($target . '/Gamestate.txt');
  rmdir($target);
}
PHP;
$gaSuite = <<<'PHP'
<?php
function gaBehaviorCases() {
  $config = json_decode(file_get_contents(dirname(__DIR__, 3) . '/stub-config.json'), true); $out = [];
  foreach ($config['fixtures'] as $f) if ($f['kind'] === 'ga-behavior-fail') $out[$f['slug']] = true;
  return $out;
}
PHP;
$gaCommand = <<<'PHP'
<?php
if (in_array('--check-source-format', $argv, true)) { echo "Controlled source format OK.\n"; exit(0); }
echo "Controlled Grand Archive diagnostic.\n"; exit(7);
PHP;
$standard = [['slug'=>'a-pass','kind'=>'pass'],['slug'=>'b-huge','kind'=>'huge'],['slug'=>'c-pass','kind'=>'pass'],['slug'=>'d-fail','kind'=>'fail']];
$results = []; $sequence = 0;
$check = static function($ok, $message) { if (!$ok) throw new RuntimeException($message); };
$invoke = static function($name, $fixtures, $extra = [], $config = []) use ($work, $runner, $expected, $stub, $gaSuite, $gaCommand, &$sequence) {
  $base = $work . '/' . (++$sequence) . '-' . $name; mkdir($base, 0777, true);
  foreach (['Core','DevTools/ga-fixtures/behavior-families','FakeRoot/Games','GrandArchiveSim/Games'] as $dir) mkdir($base . '/' . $dir, 0777, true);
  copy($runner, $base . '/DevTools/RunIntegrationTests.php');
  if (hash_file('sha256', $base . '/DevTools/RunIntegrationTests.php') !== $expected) throw new RuntimeException('Runner copy hash changed.');
  file_put_contents($base . '/Core/EngineActionRunner.php', $stub);
  file_put_contents($base . '/DevTools/ga-fixtures/behavior-families/suites.php', $gaSuite);
  file_put_contents($base . '/DevTools/create-prd-ability-fixtures.php', $gaCommand);
  file_put_contents($base . '/stub-config.json', json_encode(array_merge(['fixtures'=>$fixtures], $config)));
  $root = strval($config['root'] ?? 'FakeRoot');
  foreach ($fixtures as $fixture) {
    if ($fixture['kind'] === 'missing-dir') continue;
    $dir = $base . '/Tests/Integration/' . $root . '/' . $fixture['slug']; mkdir($dir, 0777, true);
    if ($fixture['kind'] !== 'missing-initial') file_put_contents($dir . '/initial_gamestate.txt', "controlled-initial\n");
    $meta = ['name'=>$fixture['slug']];
    if ($fixture['kind'] === 'bad-base') $meta['baseFixture'] = '../invalid';
    if ($fixture['kind'] === 'ga-source-fail') $meta['sourceScenario'] = 'controlled';
    file_put_contents($dir . '/meta.json', json_encode($meta));
    file_put_contents($dir . '/actions.json', json_encode($fixture['kind'] === 'expected-rejection' ? [['mode'=>100,'playerID'=>1,'expectFailure'=>true]] : []));
    file_put_contents($dir . '/assertions.json', json_encode(in_array($fixture['kind'], ['fail','huge'], true) ? [['stubFailure'=>true,'huge'=>$fixture['kind']==='huge']] : []));
  }
  $log = $base . '/full.log';
  if (!empty($config['existingLog'])) file_put_contents($log, 'PRESERVE_EXISTING');
  $extra = array_map(static fn($a) => str_replace('{LOG}', $log, $a), $extra);
  $out = $base . '/stdout.txt'; $err = $base . '/stderr.txt';
  $command = array_merge([PHP_BINARY, $base . '/DevTools/RunIntegrationTests.php', '--root=' . $root], $extra);
  $process = proc_open($command, [0=>['pipe','r'],1=>['file',$out,'w'],2=>['file',$err,'w']], $pipes, $base);
  if (!is_resource($process)) throw new RuntimeException('Cannot launch controlled runner.');
  fclose($pipes[0]); $code = proc_close($process);
  return ['name'=>$name,'base'=>$base,'code'=>$code,'out'=>file_get_contents($out),'err'=>file_get_contents($err),'trace'=>is_file($base.'/trace.txt')?file_get_contents($base.'/trace.txt'):'','log'=>is_file($log)?file_get_contents($log):null];
};
$summary = static function($run, $values) use ($check) {
  preg_match('/\[SUMMARY\] Total: (\d+) \| Pass: (\d+) \| Fail: (\d+) \| Discovered: (\d+) \| Not run: (\d+) \| Skipped: (\d+)/', $run['out'], $m);
  $check(count($m)===7 && array_map('intval', array_slice($m,1))===$values, $run['name'].' exact selected summary');
};
$record = static function($r) use (&$results) { $results[]=['name'=>$r['name'],'status'=>'PASS','exit_code'=>$r['code'],'stdout_bytes'=>strlen($r['out']),'log_bytes'=>$r['log']===null?null:strlen($r['log']),'artifacts'=>$r['base']]; };
try {
  $r=$invoke('default-full',$standard); $check($r['code']===1&&str_contains($r['out'],'LONG_END')&&str_contains($r['out'],'[PASS] c-pass')&&!str_contains($r['out'],'[STOP]'),'default full/unlimited unchanged'); $summary($r,[4,2,2,4,0,0]); $record($r);
  $r=$invoke('limit-one',$standard,['--max-failures=1']); $check($r['code']===1&&!str_contains($r['trace'],'regression_c-pass_')&&str_contains($r['out'],'[STOP]'),'limit stops before next fixture'); $summary($r,[2,1,1,4,2,0]); $record($r);
  $r=$invoke('limit-at-last',$standard,['--max-failures=2']); $check($r['code']===1&&!str_contains($r['out'],'[STOP]'),'no false early stop at natural end'); $summary($r,[4,2,2,4,0,0]); $record($r);
  $r=$invoke('compact-complete-log',$standard,['--max-failures=1','--compact','--log={LOG}']); $check($r['code']===1&&strlen($r['out'])<1800&&!str_contains($r['out'],'LONG_END')&&!str_contains($r['out'],'[PASS]')&&str_contains($r['log'],'LONG_BEGIN'.str_repeat('x',50000).'LONG_END')&&str_contains($r['log'],'ContextTail')&&str_contains($r['log'],'[PASS] a-pass'),'bounded console retains entire huge diagnostic+passes in log'); $summary($r,[2,1,1,4,2,0]); $record($r);
  $r=$invoke('log-full-console',$standard,['--max-failures=1','--log={LOG}']); $check($r['code']===1&&str_contains($r['out'],'LONG_END')&&$r['out']===$r['log'],'log alone tees exact full stdout'); $summary($r,[2,1,1,4,2,0]); $record($r);
  $r=$invoke('chunk-split-unterminated',[['slug'=>'a-fail','kind'=>'fail']],['--compact','--log={LOG}'],['splitOutput'=>true]); $check($r['code']===1&&strlen($r['out'])<1400&&str_contains($r['out'],'[FAIL] controlled chunked diagnostic')&&!str_contains($r['out'],'CHUNK_END')&&str_contains($r['log'],'[FAIL] controlled chunked diagnostic '.str_repeat('y',10000).'CHUNK_END')&&str_ends_with($r['log'],'TAIL_UNTERMINATED'),'split prefixes and final unterminated stdout preserve full log/bounded console'); $summary($r,[1,0,1,1,0,0]); $record($r);
  $r=$invoke('selected-one',$standard,['--test=c-pass','--max-failures=1']); $check($r['code']===0&&!str_contains($r['trace'],'regression_a-pass_'),'--test selected invocation only'); $summary($r,[1,1,0,1,0,0]); $record($r);
  $r=$invoke('empty',[]); $check($r['code']===0,'empty success'); $summary($r,[0,0,0,0,0,0]); $record($r);
  foreach (['--max-failures','--max-failures:1','--max-failuresx','--max-failures=','--max-failures=0','--max-failures=-1','--max-failures=word','--max-failures=1.5','--max-failures=999999999999999999999999','--compact','--compact=1','--compact:1','--log=','--log:bad','--log=php://stdout'] as $arg) {
    $r=$invoke('invalid-option',$standard,[$arg]); $check($r['code']!==0&&$r['trace']===''&&str_contains($r['err'],'[ERROR]'),'malformed option must not discover/run: '.$arg); $record($r);
  }
  $r=$invoke('existing-log',$standard,['--compact','--log={LOG}'],['existingLog'=>true]); $check($r['code']!==0&&$r['trace']===''&&$r['log']==='PRESERVE_EXISTING','existing evidence never overwritten'); $record($r);
  $r=$invoke('missing-log-parent',$standard,['--compact','--log={LOG}/missing/file.log']); $check($r['code']!==0&&$r['trace']==='','nonwritable log fails before discovery/run'); $record($r);
  foreach (['missing-dir','missing-initial','bad-base'] as $kind) {
    $r=$invoke($kind,[['slug'=>'a-fail','kind'=>$kind],['slug'=>'b-pass','kind'=>'pass']],['--max-failures=1']); $check($r['code']===1&&!str_contains($r['trace'],'regression_b-pass_'),'early failure continue respects limit'); $summary($r,[1,0,1,2,1,0]); $record($r);
  }
  $r=$invoke('prepare-catch',[['slug'=>'a-fail','kind'=>'pass'],['slug'=>'b-pass','kind'=>'pass']],['--max-failures=1'],['prepareFail'=>true]); $check($r['code']===1&&!str_contains($r['trace'],'regression_b-pass_'),'preparation catch respects limit'); $summary($r,[1,0,1,2,1,0]); $record($r);
  foreach (['ga-behavior-fail','ga-source-fail'] as $kind) {
    $r=$invoke($kind,[['slug'=>'a-fail','kind'=>$kind],['slug'=>'b-pass','kind'=>'pass']],['--max-failures=1'],['root'=>'GrandArchiveSim']); $check($r['code']===1&&!str_contains($r['trace'],'regression_b-pass_'),'GrandArchive continue respects limit'); $summary($r,[1,0,1,2,1,0]); $record($r);
  }
  $r=$invoke('expected-rejection',[['slug'=>'a-rejection','kind'=>'expected-rejection']],['--max-failures=1','--verbose','--compact','--log={LOG}']); $check($r['code']===0&&str_contains($r['log'],'[STEP]')&&str_contains($r['log'],'expected-rejection')&&!str_contains($r['out'],'[STEP]'),'existing expected-rejection/verbose path unweakened'); $summary($r,[1,1,0,1,0,0]); $record($r);
  $result=['status'=>'PASS','runner_sha256'=>$expected,'contracts'=>count($results),'results'=>$results,'limits'=>'Exact runner CLI executed only against tiny dependency doubles; no real engine/gameplay/card fixtures. Actual bounded FaB run remains separate.'];
  file_put_contents($work.'/result.json',json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
  echo json_encode(['status'=>'PASS','contracts'=>count($results),'result'=>$work.'/result.json'])."\n";
} catch (Throwable $e) {
  file_put_contents($work.'/result.json',json_encode(['status'=>'FAIL','error'=>$e->getMessage(),'completed'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
  fwrite(STDERR,"[FAIL] Controlled runner contract: ".$e->getMessage()."; artifacts ".$work."\n"); exit(1);
}
