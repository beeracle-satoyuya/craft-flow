<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 月別売上サマリーモデル
 *
 * @property int $id
 * @property string $year_month
 * @property string $product_code
 * @property string $product_name
 * @property int $total_sales_amount
 * @property int $total_quantity
 * @property string|null $register_name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class MonthlySalesSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_month',
        'product_code',
        'product_name',
        'total_sales_amount',
        'total_quantity',
        'register_name',
    ];

    protected function casts(): array
    {
        return [
            'total_sales_amount' => 'integer',
            'total_quantity' => 'integer',
        ];
    }
}
