<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MovementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockMovement::class);

        $search = trim((string) $request->string('search')->toString());
        $type = $request->string('type')->toString();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $fromDate = $this->parseDate($dateFrom)?->startOfDay();
        $toDate = $this->parseDate($dateTo)?->endOfDay();

        $movementsQuery = StockMovement::query()
            ->with(['product', 'actor'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($subQuery) use ($search) {
                    $subQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(in_array($type, ['in', 'out', 'adjust'], true), fn ($query) => $query->where('type', $type))
            ->when($fromDate, fn ($query) => $query->where('happened_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('happened_at', '<=', $toDate))
            ->orderByDesc('happened_at');

        /** @var LengthAwarePaginator $movements */
        $movements = $movementsQuery->paginate(20)->appends($request->query());

        $products = Product::query()
            ->orderBy('sku')
            ->with('category')
            ->get(['id', 'sku', 'name', 'qty', 'reorder_point']);

        return view('admin.movements.index', [
            'movements' => $movements,
            'products' => $products,
            'filters' => [
                'search' => $search,
                'type' => in_array($type, ['in', 'out', 'adjust'], true) ? $type : null,
                'date_from' => $fromDate?->toDateString(),
                'date_to' => $toDate?->toDateString(),
            ],
        ]);
    }

    public function storeIn(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $this->validateInboundOutbound($request);

        DB::transaction(function () use ($validated, $request) {
            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            $product->increment('qty', $validated['qty']);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'qty' => $validated['qty'],
                'note' => $validated['note'] ?? null,
                'actor_id' => $request->user()->id,
                'happened_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.movements.index')
            ->with('status', 'ทำรายการสำเร็จ');
    }

    public function storeOut(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $this->validateInboundOutbound($request);

        DB::transaction(function () use ($validated, $request) {
            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);

            if ($validated['qty'] > $product->qty) {
                throw ValidationException::withMessages([
                    'qty' => 'จำนวนเบิกออกมากกว่าสต็อกคงเหลือ',
                ])->errorBag('default')->redirectTo(route('admin.movements.index'));
            }

            $product->decrement('qty', $validated['qty']);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'out',
                'qty' => $validated['qty'],
                'note' => $validated['note'] ?? null,
                'actor_id' => $request->user()->id,
                'happened_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.movements.index')
            ->with('status', 'ทำรายการสำเร็จ');
    }

    public function storeAdjust(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $this->validateAdjust($request);

        $delta = DB::transaction(function () use ($validated, $request) {
            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);

            $currentQty = $product->qty;
            $targetQty = $validated['target_qty'];
            $delta = $targetQty - $currentQty;

            $product->update(['qty' => $targetQty]);

            $noteDetails = $validated['note'] ?? '';
            $deltaText = 'Δ' . ($delta > 0 ? '+' : '') . $delta;
            $noteToStore = trim($noteDetails . ' ' . $deltaText);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'adjust',
                'qty' => abs($delta),
                'note' => $noteToStore !== '' ? $noteToStore : $deltaText,
                'actor_id' => $request->user()->id,
                'happened_at' => now(),
            ]);

            return $delta;
        });

        $deltaText = $delta > 0 ? "+{$delta}" : (string) $delta;

        return redirect()
            ->route('admin.movements.index')
            ->with('status', "ปรับยอดสต็อกเรียบร้อย (Δ{$deltaText})");
    }

    private function validateInboundOutbound(Request $request): array
    {
        return $request->validate(
            [
                'form_type' => ['required', 'in:in,out'],
                'product_id' => ['required', 'exists:products,id'],
                'qty' => ['required', 'integer', 'min:1'],
                'note' => ['nullable', 'string'],
            ],
            [
                'product_id.required' => 'กรุณาเลือกสินค้า',
                'product_id.exists' => 'สินค้าไม่ถูกต้อง',
                'qty.required' => 'กรุณาระบุจำนวน',
                'qty.integer' => 'จำนวนต้องเป็นตัวเลขจำนวนเต็ม',
                'qty.min' => 'จำนวนต้องมากกว่าศูนย์',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ]
        );
    }

    private function validateAdjust(Request $request): array
    {
        return $request->validate(
            [
                'form_type' => ['required', 'in:adjust'],
                'product_id' => ['required', 'exists:products,id'],
                'target_qty' => ['required', 'integer', 'min:0'],
                'note' => ['nullable', 'string'],
            ],
            [
                'product_id.required' => 'กรุณาเลือกสินค้า',
                'product_id.exists' => 'สินค้าไม่ถูกต้อง',
                'target_qty.required' => 'กรุณาระบุจำนวนที่ควรเป็น',
                'target_qty.integer' => 'จำนวนต้องเป็นตัวเลขจำนวนเต็ม',
                'target_qty.min' => 'จำนวนต้องมากกว่าหรือเท่ากับศูนย์',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ]
        );
    }

    private function parseDate(?string $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $exception) {
            return null;
        }
    }
}
