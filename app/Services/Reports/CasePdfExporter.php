<?php

namespace App\Services\Reports;

use App\Models\CaseModel;
use Barryvdh\DomPDF\PDF;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Support\Carbon;

class CasePdfExporter
{
    public function build(CaseModel $case): PDF
    {
        $case->loadMissing(['owner', 'executor', 'actions.user', 'documents.uploader']);

        return PdfFacade::loadView('cases.pdf', [
            'case' => $case,
            'generatedAt' => Carbon::now(),
        ])->setPaper('a4');
    }
}