<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * 売上集計履歴モデル
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $aggregated_at
 * @property string $excel_filename
 * @property string $excel_file_path
 * @property array $original_pdf_files
 * @property int $total_sales_amount
 * @property int $total_quantity
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SalesAggregation extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'aggregated_at',
        'excel_filename',
        'excel_file_path',
        'original_pdf_files',
        'total_sales_amount',
        'total_quantity',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aggregated_at' => 'datetime',
            'original_pdf_files' => 'array',
            'total_sales_amount' => 'integer',
            'total_quantity' => 'integer',
        ];
    }

    /**
     * 集計を実行したユーザー
     *
     * @return BelongsTo<User, SalesAggregation>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 集計詳細アイテム
     *
     * @return HasMany<SalesAggregationItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesAggregationItem::class);
    }

    /**
     * ファイルのフルパスを取得
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('local')->path($this->excel_file_path);
    }

    /**
     * ファイルが存在するかチェック
     */
    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->excel_file_path);
    }
}
