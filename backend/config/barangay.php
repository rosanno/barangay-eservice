<?php
return [
    'documents' => json_encode([
        ['name' => 'Barangay Clearance', 'requirements' => ['Valid ID', 'Proof of residency'], 'fee' => 50, 'processing_days' => 1],
        ['name' => 'Certificate of Indigency', 'requirements' => ['Valid ID'], 'fee' => 0, 'processing_days' => 1],
        // ...
    ], JSON_UNESCAPED_UNICODE),
];