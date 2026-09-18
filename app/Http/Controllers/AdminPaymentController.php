<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPaymentController extends Controller
{
    public function index()
    {
        return view('admin.payments.index', ['payments' => SubscriptionPayment::with('user')->latest()->get()]);
    }

    public function update(Request $request, SubscriptionPayment $payment)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);
        $payment->update(['status' => $data['status'], 'admin_note' => $data['admin_note'] ?? null, 'reviewed_at' => now()]);
        if ($data['status'] === 'approved') $payment->user->update(['is_subscribed' => true]);
        if ($data['status'] === 'rejected' && $payment->user->subscriptionPayments()->where('status', 'approved')->doesntExist()) $payment->user->update(['is_subscribed' => false]);

        return back()->with('success', 'Status pembayaran diperbarui.');
    }

    public function proof(SubscriptionPayment $payment)
    {
        abort_unless($payment->proof_path && Storage::disk('public')->exists($payment->proof_path), 404);
        return Storage::disk('public')->response($payment->proof_path);
    }
}
