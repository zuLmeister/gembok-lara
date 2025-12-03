<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $customer = Customer::where('pppoe_username', $request->username)
            ->orWhere('customer_id', $request->username)
            ->first();

        if ($customer && Hash::check($request->password, $customer->pppoe_password)) {
            Auth::loginUsingId($customer->user_id ?? $customer->id);
            return redirect()->route('customer.dashboard');
        }

        return back()->with('error', 'Username atau password salah');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('customer.login');
    }

    public function dashboard()
    {
        $customer = Customer::where('user_id', Auth::id())->with('package')->first();
        
        $nextInvoice = Invoice::where('customer_id', $customer->id ?? 0)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->first();

        $recentInvoices = Invoice::where('customer_id', $customer->id ?? 0)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('customer.dashboard', compact('customer', 'nextInvoice', 'recentInvoices'));
    }

    public function invoices()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $invoices = Invoice::where('customer_id', $customer->id ?? 0)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.invoices', compact('customer', 'invoices'));
    }

    public function showInvoice(Invoice $invoice)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        
        if ($invoice->customer_id != $customer->id) {
            abort(403);
        }

        return view('customer.invoice-detail', compact('customer', 'invoice'));
    }

    public function payments()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $payments = Invoice::where('customer_id', $customer->id ?? 0)
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->paginate(10);

        return view('customer.payments', compact('customer', 'payments'));
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        
        if ($invoice->customer_id != $customer->id) {
            abort(403);
        }

        $paymentService = new PaymentGatewayService();
        $gateway = $request->get('gateway', 'midtrans');

        if ($gateway === 'midtrans') {
            $result = $paymentService->createMidtransPayment($invoice);
        } else {
            $result = $paymentService->createXenditInvoice($invoice);
        }

        if (isset($result['redirect_url'])) {
            return redirect($result['redirect_url']);
        }

        if (isset($result['snap_token'])) {
            return view('customer.pay', compact('invoice', 'result'));
        }

        return back()->with('error', 'Gagal membuat pembayaran');
    }

    public function profile()
    {
        $customer = Customer::where('user_id', Auth::id())->with('package')->first();
        return view('customer.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        
        $request->validate([
            'phone' => 'required',
            'email' => 'nullable|email',
        ]);

        $customer->update([
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $customer->update([
                'pppoe_password' => Hash::make($request->password),
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui');
    }

    public function support()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        return view('customer.support', compact('customer'));
    }

    public function submitTicket(Request $request)
    {
        $request->validate([
            'subject' => 'required',
            'message' => 'required',
        ]);

        // TODO: Create support ticket model and save
        
        return back()->with('success', 'Tiket berhasil dikirim. Tim kami akan segera menghubungi Anda.');
    }
}
