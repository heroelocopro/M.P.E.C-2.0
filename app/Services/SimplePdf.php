<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class SimplePdf
{
    /** @param array<string, mixed> $data */
    public function make(array $data): string
    {
        return Pdf::loadView('pdf.grade-report', $data)
            ->setPaper('letter')
            ->setOption([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
            ])
            ->output();
    }
}
