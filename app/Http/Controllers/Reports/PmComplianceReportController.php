<?php


namespace App\Http\Controllers\Reports;


use App\Http\Controllers\Controller;
use App\Http\Resources\ComplianceCollection;
use App\SAP\Models\Compliance;

class PmComplianceReportController extends Controller
{
    public function index()
    {
        return new ComplianceCollection(Compliance::all());
    }
}