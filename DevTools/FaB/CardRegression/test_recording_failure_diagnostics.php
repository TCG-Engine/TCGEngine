<?php
/** Two recorder setup rejections: no gameplay, saved fixtures, or snapshot updates. */
declare(strict_types=1);
$repo = dirname(__DIR__, 3);
$recorder = __DIR__ . '/record_card_regressions.php';
$fixtures = $repo . '/Tests/Integration/FaBSim';
$temp = sys_get_temp_dir() . '/fabsim-recorder-diagnostics-' . bin2hex(random_bytes(6));
if (!mkdir($temp, 0700)) throw new RuntimeException('Cannot create diagnostic test directory.');
try {
    $base = ['name'=>'Recorder setup rejection', 'activeSeats'=>[1,2,3,4],
        'heroes'=>array_fill(0,4,'dorinthea'), 'health'=>[20,20,20,20],
        'resources'=>[0,0,0,0], 'actionPoints'=>[1,0,0,0],
        'state'=>['gameMode'=>'UPF','passiveSeats'=>[]], 'zones'=>[], 'steps'=>[],
        'diagnostic_only'=>true, 'oracle'=>'Controlled setup rejection; never save a fixture.'];
    $first = $base; $first['slug']='harness-recorder-invalid-three-seat-control';
    $first['activeSeats']=[1,2,3];
    $second = $base; $second['slug']='harness-recorder-invalid-all-queue-control';
    $second['steps']=[['kind'=>'checks','checks'=>[['type'=>'decision_queue_empty','player'=>'all']]]];
    foreach ([$first,$second] as $case) {
        if (is_dir($fixtures.'/'.$case['slug'])) throw new RuntimeException('Reserved control fixture already exists.');
    }
    $before=glob($fixtures.'/*/meta.json') ?: []; sort($before);
    $input=$temp.'/invalid-inputs.json';
    file_put_contents($input, json_encode([$first,$second], JSON_THROW_ON_ERROR));
    $process=proc_open([PHP_BINARY,$recorder,$input,'--probe-only'],
        [0=>['pipe','r'],1=>['file',$temp.'/stdout.log','w'],2=>['file',$temp.'/stderr.log','w']],
        $pipes,$repo);
    if (!is_resource($process)) throw new RuntimeException('Cannot launch recorder control.');
    fclose($pipes[0]); $exit=proc_close($process);
    if ($exit!==1 || file_get_contents($temp.'/stderr.log')!=='') {
        throw new RuntimeException('Recorder did not report two ordinary controlled rejections.');
    }
    $rows=json_decode(file_get_contents($temp.'/invalid-inputs.recording-results.json'),true,512,JSON_THROW_ON_ERROR);
    if (count($rows)!==2) throw new RuntimeException('Expected two separate failure records.');
    foreach ($rows as $index=>$row) {
        $message=$row['error']??'';
        $primary=$index===0 ? str_contains($message,'activeSeats') : str_contains($message,'each decision queue');
        if (!$primary || ($row['setup_completed']??null)!==false || ($row['attempted_actions']??null)!==[]
            || ($row['fixture_saved']??null)!==false || !empty($row['diagnostic_errors'])) {
            throw new RuntimeException('Original preflight error or zero-action boundary was lost.');
        }
        foreach (['recording','observed_state','observed_decisions','observed_hand_refs'] as $key) {
            if (!array_key_exists($key,$row) || $row[$key]!==null) {
                throw new RuntimeException('Uninitialized observations must remain explicitly unavailable.');
            }
        }
    }
    $after=glob($fixtures.'/*/meta.json') ?: []; sort($after);
    if ($before!==$after) throw new RuntimeException('Control changed the regression inventory.');
    echo "[PASS] Recorder diagnostics: 2 setup rejections, 0 game actions, 0 saved fixtures.\n";
    echo "[EVIDENCE] Local temporary diagnostics: $temp\n";
} catch (Throwable $error) {
    fwrite(STDERR, '[FAIL] '.$error->getMessage()."\n[EVIDENCE] $temp\n");
    exit(1);
}
