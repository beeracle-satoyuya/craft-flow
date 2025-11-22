<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 委託販売モデル
 *
 * @property int $id
 * @property string|null $batch_id バッチID
 * @property string|null $vendor_name 委託先名
 * @property int|null $commission_rate 手数料率（%）
 * @property \Illuminate\Support\Carbon $sale_date 販売日
 * @property string|null $receipt_number レシート番号
 * @property string|null $client_id クライアントID
 * @property string|null $company_name 会社名
 * @property string|null $product_code 商品コード
 * @property string $product_name 商品名
 * @property int $quantity 数量
 * @property int $unit_price 単価
 * @property int $amount 金額
 * @property string|null $category カテゴリ
 * @property int $commission 手数料
 * @property int|null $net_amount 実際に支払う金額
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
        'batch_id',
        'vendor_name',
        'commission_rate',
        'sale_date',
        'receipt_number',
        'client_id',
        'company_name',
        'product_code',
        'product_name',
        'quantity',
        'unit_price',
        'amount',
        'category',
        'commission',
        'net_amount',
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
            'commission_rate' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
            'commission' => 'integer',
            'net_amount' => 'integer',
        ];
    }
}
