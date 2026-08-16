<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\View\View;

/** The financial reconciliation board (docs/07 § 7.2.1): status vs financial-status side by side. */
class ReconciliationController extends Controller
{
    public function index(): View
    {
        $shipments = Shipment::with(['merchant', 'assignedDriver'])
            ->where('collection_required', true)
            ->where('shipment_status', Shipment::STATUS_DELIVERED)
            ->where('financial_status', '!=', Shipment::FIN_SETTLED_TO_MERCHANT)
            ->latest('delivered_at')
            ->paginate(30);

        return view('admin.reconciliation', compact('shipments'));
    }
}
