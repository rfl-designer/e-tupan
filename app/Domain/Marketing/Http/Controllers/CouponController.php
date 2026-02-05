<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Http\Controllers;

use App\Domain\Marketing\Enums\CouponType;
use App\Domain\Marketing\Http\Requests\{StoreCouponRequest, UpdateCouponRequest};
use App\Domain\Marketing\Models\Coupon;
use App\Http\Controllers\Controller;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class CouponController extends Controller
{
    /**
     * Display a listing of coupons.
     */
    public function index(Request $request): View
    {
        $query = Coupon::query()
            ->withCount('usages')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->input('status') === 'expired') {
                $query->where('expires_at', '<', now());
            }
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Search by code or name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $coupons = $query->paginate(15)->withQueryString();
        $types   = CouponType::options();

        return view('admin.coupons.index', compact('coupons', 'types'));
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function create(): View
    {
        $types = CouponType::options();

        return view('admin.coupons.create', compact('types'));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(StoreCouponRequest $request): RedirectResponse
    {
        Coupon::create($request->validated());

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Cupom criado com sucesso!');
    }

    /**
     * Show the form for editing the specified coupon.
     */
    public function edit(Coupon $coupon): View
    {
        $types = CouponType::options();
        $coupon->loadCount('usages');

        return view('admin.coupons.edit', compact('coupon', 'types'));
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($request->validated());

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Cupom atualizado com sucesso!');
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        // Check if coupon is currently applied to any active carts
        $inUse = $coupon->carts()->whereNull('converted_at')->exists();

        if ($inUse) {
            return back()->withErrors(['error' => 'Nao e possivel excluir um cupom que esta aplicado em carrinhos ativos.']);
        }

        $coupon->delete();

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Cupom excluido com sucesso!');
    }

    /**
     * Toggle the active status of a coupon.
     */
    public function toggleActive(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => !$coupon->is_active]);

        $message = $coupon->is_active
            ? 'Cupom ativado com sucesso!'
            : 'Cupom desativado com sucesso!';

        return back()->with('success', $message);
    }
}
