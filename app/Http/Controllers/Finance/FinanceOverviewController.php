<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Support\Finance\FinanceCalculations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceOverviewController extends Controller
{
    public function show(Request $request, Wedding $wedding, FinanceCalculations $calculations): Response
    {
        $budgetSummary = $calculations->budgetSummary($wedding);
        $savingsSummary = $calculations->savingsSummary($wedding);

        return Inertia::render('weddings/finance/index', [
            'budget' => $budgetSummary,
            'savings' => $savingsSummary,
            'categoryBreakdown' => $calculations->categoryBreakdown($wedding),
            'eventBreakdown' => $calculations->eventBreakdown($wedding),
            'upcomingPayments' => $calculations->upcomingPayments($wedding),
            'canEdit' => $request->user()->can('editOperationalData', $wedding),
        ]);
    }
}
