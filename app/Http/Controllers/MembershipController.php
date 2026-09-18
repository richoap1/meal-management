<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function index(Request $request)
    {
        return view('membership.index', [
            'payment' => $request->user()->subscriptionPayments()->latest()->first(),
            'price' => (float) config('services.subscription.price', 20000),
            'bankAccount' => config('services.subscription.bank_account', 'BCA 5060354202 a.n. RICHO ANDIKA PUTRA'),
            'qrisMerchant' => config('services.subscription.qris_merchant', 'MEALWISE SUBSCRIPTION'),
        ]);
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'method' => ['required', 'in:bank_transfer,qris'],
            'proof' => ['required_if:method,bank_transfer', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
        if ($request->user()->subscriptionPayments()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Masih ada pembayaran yang menunggu verifikasi admin.');
        }

        $reference = $data['method'] === 'qris' ? 'QRIS-' . strtoupper(Str::random(10)) : null;
        $request->user()->subscriptionPayments()->create([
            'invoice_number' => 'MW-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'method' => $data['method'],
            'amount' => config('services.subscription.price', 20000),
            'proof_path' => $request->file('proof')?->store('payment-proofs', 'public'),
            'qris_reference' => $reference,
        ]);

        return redirect()->route('membership.index')->with('success', 'Pembayaran berhasil dikirim dan menunggu approval admin.');
    }

    public function update(Request $request)
    {
        return response()->json(['status' => 'error', 'message' => 'Membership aktif setelah pembayaran disetujui admin.'], 403);
    }

    public function show(Request $request)
    {
        $isSubscribed = $request->user()?->is_subscribed ?? $request->session()->get('is_subscribed', false);

        return response()->json([
            'is_subscribed' => (bool) $isSubscribed,
            'plan' => $isSubscribed ? 'Weekly Member' : 'Daily Plan',
        ]);
    }

    public function upgrade(Request $request)
    {
        return $this->submit($request);
    }
}