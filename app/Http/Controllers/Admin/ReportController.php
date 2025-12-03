<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Agent;
use App\Models\Collector;
use App\Models\VoucherPurchase;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = Carbon::now();
        $previousStart = $this->getPreviousStartDate($period, $startDate);

        // Summary Stats
        $totalRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');

        $previousRevenue = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$previousStart, $startDate])
            ->sum('total');

        $revenueGrowth = $previousRevenue > 0 
            ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1) 
            : 0;

        $activeCustomers = Customer::where('status', 'active')->count();
        $previousActiveCustomers = Customer::where('status', 'active')
            ->where('created_at', '<', $startDate)
            ->count();
        $customerGrowth = $previousActiveCustomers > 0 
            ? round((($activeCustomers - $previousActiveCustomers) / $previousActiveCustomers) * 100, 1) 
            : 0;

        $paidInvoices = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->count();
        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])->count();

        $voucherSales = VoucherPurchase::whereBetween('created_at', [$startDate, $endDate])->count();
        $voucherRevenue = VoucherPurchase::whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        // Chart Data - Revenue (last 6 months)
        $revenueLabels = [];
        $revenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenueLabels[] = $month->format('M Y');
            $revenueData[] = Invoice::where('status', 'paid')
                ->whereYear('paid_at', $month->year)
                ->whereMonth('paid_at', $month->month)
                ->sum('total');
        }

        // Chart Data - Customer Growth (last 6 months)
        $customerLabels = [];
        $customerData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $customerLabels[] = $month->format('M Y');
            $customerData[] = Customer::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        // Package Distribution
        $packages = Package::withCount('customers')->get();
        $packageLabels = $packages->pluck('name')->toArray();
        $packageData = $packages->pluck('customers_count')->toArray();

        // Payment Methods (simplified)
        $paymentLabels = ['Cash', 'Transfer', 'Online'];
        $paymentData = [
            Invoice::where('payment_method', 'cash')->where('status', 'paid')->count(),
            Invoice::where('payment_method', 'transfer')->where('status', 'paid')->count(),
            Invoice::whereIn('payment_method', ['midtrans', 'xendit'])->where('status', 'paid')->count(),
        ];

        // Invoice Status
        $invoiceStatusData = [
            Invoice::where('status', 'paid')->count(),
            Invoice::where('status', 'unpaid')->where('due_date', '>=', Carbon::now())->count(),
            Invoice::where('status', 'unpaid')->where('due_date', '<', Carbon::now())->count(),
        ];

        // Top Packages
        $topPackages = Package::withCount('customers')
            ->with(['customers.invoices' => function($q) use ($startDate, $endDate) {
                $q->where('status', 'paid')->whereBetween('paid_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($package) {
                $package->revenue = $package->customers->sum(function($customer) {
                    return $customer->invoices->sum('total');
                });
                return $package;
            })
            ->sortByDesc('customers_count')
            ->take(5);

        // Top Collectors
        $topCollectors = Collector::withCount(['payments' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($collector) use ($startDate, $endDate) {
                $collector->total_collected = $collector->payments()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
                $collector->collections_count = $collector->payments_count;
                return $collector;
            })
            ->sortByDesc('total_collected')
            ->take(5);

        // Agent Performance
        $agentPerformance = Agent::all()->map(function($agent) use ($startDate, $endDate) {
            $purchases = VoucherPurchase::where('agent_id', $agent->id)
                ->whereBetween('created_at', [$startDate, $endDate]);
            
            $agent->vouchers_sold = $purchases->count();
            $agent->revenue = $purchases->sum('amount');
            $agent->commission = $purchases->sum('commission');
            
            return $agent;
        })->sortByDesc('vouchers_sold');

        return view('admin.reports.index', compact(
            'totalRevenue', 'revenueGrowth', 'activeCustomers', 'customerGrowth',
            'paidInvoices', 'totalInvoices', 'voucherSales', 'voucherRevenue',
            'revenueLabels', 'revenueData', 'customerLabels', 'customerData',
            'packageLabels', 'packageData', 'paymentLabels', 'paymentData',
            'invoiceStatusData', 'topPackages', 'topCollectors', 'agentPerformance'
        ));
    }

    public function export(Request $request)
    {
        $period = $request->get('period', 'month');
        
        // Generate CSV export
        $filename = 'report_' . $period . '_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($period) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Laporan ISP Billing - ' . ucfirst($period)]);
            fputcsv($file, ['Generated: ' . date('Y-m-d H:i:s')]);
            fputcsv($file, []);
            
            // Summary
            fputcsv($file, ['RINGKASAN']);
            fputcsv($file, ['Total Pelanggan Aktif', Customer::where('status', 'active')->count()]);
            fputcsv($file, ['Total Pendapatan', Invoice::where('status', 'paid')->sum('total')]);
            fputcsv($file, ['Invoice Terbayar', Invoice::where('status', 'paid')->count()]);
            fputcsv($file, ['Invoice Belum Bayar', Invoice::where('status', 'unpaid')->count()]);
            fputcsv($file, []);
            
            // Packages
            fputcsv($file, ['DISTRIBUSI PAKET']);
            fputcsv($file, ['Nama Paket', 'Jumlah Pelanggan', 'Harga']);
            foreach (Package::withCount('customers')->get() as $package) {
                fputcsv($file, [$package->name, $package->customers_count, $package->price]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getStartDate($period)
    {
        return match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function getPreviousStartDate($period, $currentStart)
    {
        return match($period) {
            'today' => Carbon::yesterday(),
            'week' => $currentStart->copy()->subWeek(),
            'month' => $currentStart->copy()->subMonth(),
            'year' => $currentStart->copy()->subYear(),
            default => $currentStart->copy()->subMonth(),
        };
    }
}
