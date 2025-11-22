<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 売上集計詳細アイテムモデル
 *
 * @property int $id
 * @property int $sales_aggregation_id
 * @property string $product_code
 * @property string $product_name
 * @property int $unit_price
 * @property int $quantity
 * @property int $sales_amount
 * @property string|null $register_name
 * @property \Illuminate\Support\Carbon $sale_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SalesAggregationItem extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sales_aggregation_id',
        'product_code',
        'product_name',
        'unit_price',
        'quantity',
        'sales_amount',
        'register_name',
        'sale_date',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'sales_amount' => 'integer',
            'sale_date' => 'date',
        ];
    }

    /**
     * 所属する集計履歴
     *
     * @return BelongsTo<SalesAggregation, SalesAggregationItem>
     */
    public function aggregation(): BelongsTo
    {
        return $this->belongsTo(SalesAggregation::class, 'sales_aggregation_id');
    }
}
