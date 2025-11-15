<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 委託販売モデル
 * 
 * @property int $id
 * @property \Illuminate\Support\Carbon $sale_date 販売日
 * @property string $product_name 商品名
 * @property int $quantity 数量
 * @property int $unit_price 単価
 * @property int $amount 金額
 * @property int $commission 手数料
 * @property string|null $notes 備考
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ConsignmentSale extends Model
{
    use SoftDeletes;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_date',
        'product_name',
        'quantity',
        'unit_price',
        'amount',
        'commission',
        'notes',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
            'commission' => 'integer',
        ];
    }
}
