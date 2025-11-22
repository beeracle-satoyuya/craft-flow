<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 日別売上サマリーモデル
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $sale_date
 * @property string $product_code
 * @property string $product_name
 * @property int $total_sales_amount
 * @property int $total_quantity
 * @property string|null $register_name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DailySalesSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_date',
        'product_code',
        'product_name',
        'total_sales_amount',
        'total_quantity',
        'register_name',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_sales_amount' => 'integer',
            'total_quantity' => 'integer',
        ];
    }
}
