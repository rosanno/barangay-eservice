<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'BARANGAY_CLEARANCE',
                'name' => 'Barangay Clearance',
                'description' => 'General-purpose clearance certifying good standing in the barangay.',
                'fee' => 50,
                'processing_days' => 1,
                'requirements' => ['Valid ID', 'Proof of residency'],
            ],
            [
                'code' => 'CERT_INDIGENCY',
                'name' => 'Certificate of Indigency',
                'description' => 'Certifies indigent status for medical, legal, or financial assistance.',
                'fee' => 0,
                'processing_days' => 1,
                'requirements' => ['Valid ID'],
            ],
            [
                'code' => 'CERT_RESIDENCY',
                'name' => 'Certificate of Residency',
                'description' => 'Certifies that the resident lives within the barangay.',
                'fee' => 30,
                'processing_days' => 1,
                'requirements' => ['Valid ID', 'Proof of billing'],
            ],
            [
                'code' => 'BUSINESS_PERMIT_ENDORSEMENT',
                'name' => 'Business Permit Endorsement',
                'description' => 'Barangay endorsement required before applying for a municipal business permit.',
                'fee' => 100,
                'processing_days' => 3,
                'requirements' => ['Valid ID', 'DTI/SEC registration', 'Lease contract or land title'],
            ],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
