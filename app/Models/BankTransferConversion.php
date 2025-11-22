<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * 銀行振込変換履歴モデル
 *
 * @property int $id
 * @property int $user_id 変換を実行したユーザーID
 * @property string $original_filename アップロードしたExcelファイル名
 * @property string $converted_filename 変換後の全銀フォーマットファイル名
 * @property string $file_path 保存先の相対パス
 * @property \Illuminate\Support\Carbon $converted_at 変換実行日時
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class BankTransferConversion extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'original_filename',
        'converted_filename',
        'file_path',
        'converted_at',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
        ];
    }

    /**
     * 変換を実行したユーザー
     *
     * @return BelongsTo<User, BankTransferConversion>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ファイルのフルパスを取得
     *
     * @return string
     */
    public function getFullPathAttribute(): string
    {
        return Storage::disk('local')->path($this->file_path);
    }

    /**
     * ファイルが存在するかチェック
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->file_path);
    }
}
