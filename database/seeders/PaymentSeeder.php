<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Collector;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $collectors = Collector::all();
        $paidInvoices = Invoice::where('status', 'paid')->get();

        foreach ($paidInvoices as $invoice) {
            $collector = $collectors->random();
            $commissionRate = $collector->commission_rate ?? 2;
            $commission = ($invoice->total * $commissionRate) / 100;

           Payment::create([
                'invoice_id' => $invoice->id,
                'collector_id' => rand(0, 1) ? $collector->id : null,
                'amount' => $invoice->total ?? 0,
                'payment_method' => $invoice->payment_method
                    ?? collect(['cash','transfer','midtrans','xendit'])->random(),
                'commission' => $commission,
                'notes' => rand(0, 1) ? 'Pembayaran tepat waktu' : null,
                'reference_number' => 'PAY-' . strtoupper(uniqid()),
                'paid_at' => $invoice->paid_at ?? Carbon::now()->subDays(rand(1, 30)),
                'created_at' => $invoice->paid_at ?? Carbon::now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
