<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSetup extends Model
{
    use HasFactory;

    protected $table = 'payroll_tax_setup';
    protected $primaryKey = 'tax_setup_id';
    public $timestamps = false;

    protected $fillable = [
        'start_amount',
        'end_amount',
        'rate',
    ];

    protected $casts = [
        'start_amount' => 'decimal:2',
        'end_amount' => 'decimal:2',
        'rate' => 'decimal:2',
    ];

    /**
     * Scope to order by amount range
     */
    public function scopeOrderByRange($query, $direction = 'ASC')
    {
        return $query->orderBy('start_amount', $direction);
    }

    /**
     * Get tax bracket for a given amount
     */
    public static function getTaxBracket($amount)
    {
        return self::where('start_amount', '<=', $amount)
            ->where('end_amount', '>=', $amount)
            ->first();
    }

    /**
     * Calculate tax for a given amount
     */
    public static function calculateTax($amount)
    {
        $brackets = self::orderByRange('ASC')->get();
        $totalTax = 0;
        $remainingAmount = $amount;

        foreach ($brackets as $bracket) {
            if ($remainingAmount <= 0) {
                break;
            }

            $bracketStart = $bracket->start_amount;
            $bracketEnd = $bracket->end_amount;
            $rate = $bracket->rate;

            // Calculate taxable amount in this bracket
            if ($amount > $bracketEnd) {
                $taxableInBracket = $bracketEnd - $bracketStart + 1;
            } else {
                $taxableInBracket = $amount - $bracketStart + 1;
            }

            // Calculate tax for this bracket
            if ($taxableInBracket > 0) {
                $totalTax += ($taxableInBracket * $rate) / 100;
            }

            $remainingAmount -= $taxableInBracket;
        }

        return round($totalTax, 2);
    }
}

