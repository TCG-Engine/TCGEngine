<?php
require_once __DIR__ . '/../Custom/Tutorial.php';
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$lessons = DFGTutorialLessons();
$tutorial = DFGTutorialStart();
$count = 0;
foreach ($lessons as $l => $lesson) {
    foreach ($lesson['frames'] as $i => $frame) {
        check($tutorial['lesson'] === $l && $tutorial['step'] === $i, 'Progress mismatch');
        check($tutorial['state'] === $frame['state'], "Engine replay differs at $l:$i");
        $before = $tutorial;
        try { DFGTutorialAdvance($tutorial, ['type'=>'resign']); throw new RuntimeException('Off-script move accepted'); }
        catch (InvalidArgumentException $e) { check($tutorial === $before, 'Rejected action mutated state'); }
        $tutorial = DFGTutorialAdvance($tutorial, $frame['action']);
        $count++;
    }
}
check($tutorial['complete'], 'Completion missing');
foreach ($lessons as $l => $lesson) foreach ($lesson['frames'] as $i => $frame) {
    check(DFGTutorialStart($l, $i)['state'] === $frame['state'], 'Replay checkpoint mismatch');
}
echo "$count tutorial steps passed, including live engine replay, checkpoints, and rejected actions.\n";
