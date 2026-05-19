<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseOrderItem;
use App\Traits\ProcurementHelpers;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ProcurementHelpers;

    public function dashboard(): View|RedirectResponse
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $orders = $this->visibleOrders();
        $visibleOrderIds = $this->visibleOrdersQuery()->pluck('id');
        $invoicePaid = Invoice::query()
            ->whereIn('purchase_order_id', $visibleOrderIds)
            ->where('status', 'PAID')
            ->sum('total_amount');
        $invoiceUnpaid = Invoice::query()
            ->whereIn('purchase_order_id', $visibleOrderIds)
            ->where('status', 'UNPAID')
            ->sum('total_amount');
        $estimatedUnbilled = PurchaseOrderItem::query()
            ->whereIn('purchase_order_id', $visibleOrderIds)
            ->where('is_invoiced', false)
            ->get()
            ->sum(fn (PurchaseOrderItem $item): float|int => $item->qty * $item->price);

        return view('dashboard.index', [
            'currentUser' => $this->currentUser(),
            'orders' => $orders,
            'stats' => [
                'total_po' => $orders->count(),
                'total_value' => $orders->sum(fn (array $order): int => $this->orderTotal($order)),
                'valid' => $orders->where('status', 'VALID')->count(),
                'processing' => $orders->where('status', 'PROCESSING')->count(),
                'completed' => $orders->where('status', 'COMPLETED')->count(),
                'invoiced' => $orders->where('status', 'INVOICED')->count(),
                'estimated_unbilled' => $estimatedUnbilled,
                'invoice_unpaid' => $invoiceUnpaid,
                'invoice_paid' => $invoicePaid,
                'debt_unpaid' => 0,
                'debt_paid' => 0,
                'unpaid' => $invoiceUnpaid,
                'paid' => $invoicePaid,
            ],
        ]);
    }
}
