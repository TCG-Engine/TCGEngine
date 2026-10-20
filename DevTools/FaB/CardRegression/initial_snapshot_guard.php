<?php
/** Authoring-only fail-closed raw initial-state guard; no game or fixture writes. */
function FCRValidateInitialSnapshotHash($expected, $path): void {
    if(!is_string($expected)||!preg_match('/\A[0-9a-f]{64}\z/D',$expected)){
        throw new RuntimeException('Initial snapshot guard requires a lowercase SHA256 string.');
    }
    if(!is_file($path)){
        throw new RuntimeException('Initial snapshot guard file is missing.');
    }
    $actual=hash_file('sha256',$path);
    if(!is_string($actual)||!hash_equals($expected,$actual)){
        throw new RuntimeException('Initial snapshot guard mismatch; authoring stopped before game actions or fixture save.');
    }
}
