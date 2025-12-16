<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxSetup;
use App\Models\TaxCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Taxes",
 *     description="Tax management endpoints"
 * )
 */
class TaxController extends Controller
{
    // ========== TAX SETUP (Tax Brackets/Slabs) ==========

    /**
     * @OA\Get(
     *     path="/api/v1/tax-setup",
     *     summary="Get all tax brackets",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of tax brackets")
     * )
     */
    public function index()
    {
        $taxBrackets = TaxSetup::orderByRange('ASC')->get();

        return response()->json([
            'success' => true,
            'data' => $taxBrackets
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tax-setup/{id}",
     *     summary="Get tax bracket details",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Tax bracket details")
     * )
     */
    public function show($id)
    {
        $taxBracket = TaxSetup::find($id);

        if (!$taxBracket) {
            return response()->json([
                'success' => false,
                'message' => 'Tax bracket not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $taxBracket
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tax-setup",
     *     summary="Create tax bracket(s)",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"brackets"},
     *             @OA\Property(
     *                 property="brackets",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"start_amount","end_amount","rate"},
     *                     @OA\Property(property="start_amount", type="number", example=0),
     *                     @OA\Property(property="end_amount", type="number", example=250000),
     *                     @OA\Property(property="rate", type="number", example=0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tax bracket(s) created")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brackets' => 'required|array|min:1',
            'brackets.*.start_amount' => 'required|numeric|min:0',
            'brackets.*.end_amount' => 'required|numeric|gt:brackets.*.start_amount',
            'brackets.*.rate' => 'required|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdBrackets = [];

            foreach ($request->brackets as $bracket) {
                $taxBracket = TaxSetup::create([
                    'start_amount' => $bracket['start_amount'],
                    'end_amount' => $bracket['end_amount'],
                    'rate' => $bracket['rate']
                ]);
                $createdBrackets[] = $taxBracket;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax bracket(s) created successfully',
                'data' => $createdBrackets
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tax bracket(s)',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/tax-setup/{id}",
     *     summary="Update tax bracket",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_amount","end_amount","rate"},
     *             @OA\Property(property="start_amount", type="number"),
     *             @OA\Property(property="end_amount", type="number"),
     *             @OA\Property(property="rate", type="number")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tax bracket updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $taxBracket = TaxSetup::find($id);

        if (!$taxBracket) {
            return response()->json([
                'success' => false,
                'message' => 'Tax bracket not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'start_amount' => 'required|numeric|min:0',
            'end_amount' => 'required|numeric|gt:start_amount',
            'rate' => 'required|numeric|min:0|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $taxBracket->update([
            'start_amount' => $request->start_amount,
            'end_amount' => $request->end_amount,
            'rate' => $request->rate
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tax bracket updated successfully',
            'data' => $taxBracket
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tax-setup/{id}",
     *     summary="Delete tax bracket",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Tax bracket deleted")
     * )
     */
    public function destroy($id)
    {
        $taxBracket = TaxSetup::find($id);

        if (!$taxBracket) {
            return response()->json([
                'success' => false,
                'message' => 'Tax bracket not found'
            ], 404);
        }

        $taxBracket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax bracket deleted successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tax-setup/calculate",
     *     summary="Calculate tax for an amount",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", example=500000)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tax calculation result")
     * )
     */
    public function calculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;
        $tax = TaxSetup::calculateTax($amount);
        $netAmount = $amount - $tax;

        // Get bracket breakdown
        $brackets = TaxSetup::orderByRange('ASC')->get();
        $breakdown = [];
        $remainingAmount = $amount;

        foreach ($brackets as $bracket) {
            if ($remainingAmount <= 0) {
                break;
            }

            if ($amount >= $bracket->start_amount) {
                $bracketStart = $bracket->start_amount;
                $bracketEnd = $bracket->end_amount;
                $rate = $bracket->rate;

                if ($amount > $bracketEnd) {
                    $taxableInBracket = $bracketEnd - $bracketStart + 1;
                } else {
                    $taxableInBracket = $amount - $bracketStart + 1;
                }

                if ($taxableInBracket > 0) {
                    $taxInBracket = ($taxableInBracket * $rate) / 100;
                    $breakdown[] = [
                        'range' => $bracketStart . ' - ' . $bracketEnd,
                        'rate' => $rate . '%',
                        'taxable_amount' => $taxableInBracket,
                        'tax' => round($taxInBracket, 2)
                    ];
                }

                $remainingAmount -= $taxableInBracket;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'gross_amount' => $amount,
                'total_tax' => $tax,
                'net_amount' => $netAmount,
                'breakdown' => $breakdown
            ]
        ]);
    }

    // ========== TAX COLLECTION ==========

    /**
     * @OA\Get(
     *     path="/api/v1/tax-collections",
     *     summary="Get all tax collections",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Filter by month (YYYY-MM)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of tax collections")
     * )
     */
    public function collections(Request $request)
    {
        $query = TaxCollection::with('employee')
            ->orderBy('tax_coll_id', 'DESC');

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by month
        if ($request->has('month')) {
            $query->where('sal_month', $request->month);
        }

        // Date range filter
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->dateRange($request->from_date, $request->to_date);
        }

        $collections = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $collections
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tax-collections/{id}",
     *     summary="Delete tax collection",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Tax collection deleted")
     * )
     */
    public function deleteCollection($id)
    {
        $collection = TaxCollection::find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'Tax collection not found'
            ], 404);
        }

        $collection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tax collection deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tax-collections/summary",
     *     summary="Get tax collection summary",
     *     tags={"Taxes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Tax summary")
     * )
     */
    public function summary(Request $request)
    {
        $query = TaxCollection::query();

        if ($request->has('month')) {
            $query->where('sal_month', $request->month);
        }

        if ($request->has('from_date') && $request->has('to_date')) {
            $query->dateRange($request->from_date, $request->to_date);
        }

        $summary = [
            'total_collections' => $query->count(),
            'total_tax_collected' => $query->sum('tax'),
            'total_net_amount' => $query->sum('net_amount'),
            'average_tax_rate' => round($query->avg('tax_rate'), 2),
            'by_month' => TaxCollection::select(
                'sal_month',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(tax) as total_tax'),
                DB::raw('SUM(net_amount) as total_net')
            )
            ->groupBy('sal_month')
            ->orderBy('sal_month', 'DESC')
            ->limit(12)
            ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}


