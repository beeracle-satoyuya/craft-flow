<?php

/** @phpstan-ignore-file */

use App\Actions\BankTransfer\ConvertToZenkinFormat;
use App\Models\User;
use Livewire\Volt\Volt;

test('認証済みユーザーは全銀フォーマット変換ページにアクセスできる', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bank-transfers.index'))
        ->assertOk();
});

test('ゲストは全銀フォーマット変換ページにアクセスできない', function () {
    $this->get(route('bank-transfers.index'))
        ->assertRedirect(route('login'));
});

test('全銀フォーマット変換Actionが正しく動作する', function () {
    $converter = new ConvertToZenkinFormat;

    // テストデータ
    $excelData = collect([
        [
            '顧客ID' => '12345',
            '事業者名' => 'テスト株式会社',
            '金融機関コード' => '0001',
            '金融機関名' => 'テスト銀行',
            '支店コード' => '001',
            '支店名' => '本店',
            '預金種目' => '1',
            '口座番号' => '1234567',
            '口座名義（カナ）' => 'テストカブシキガイシヤ',
            '振込金額' => '10000',
        ],
    ]);

    // 変換設定
    $config = [
        'requester_code' => '0000000001',
        'requester_name' => '依頼人テスト',
        'transaction_date' => now(),
        'bank_code' => '0001',
        'bank_name' => 'テスト銀行',
        'branch_code' => '001',
        'branch_name' => '本店',
        'account_type' => '1',
        'account_number' => '1234567',
    ];

    // 変換実行
    $result = $converter->convert($excelData, $config);

    // 結果の検証
    expect($result)->toBeString();
    expect($result)->not->toBeEmpty();

    // 標準的な構造を確認: ヘッダー + データ + トレーラ + エンド
    $lines = explode("\r\n", $result);
    $lines = array_filter($lines); // 空行を除外

    // 少なくとも4行（ヘッダー、データ、トレーラ、エンド）が含まれる
    expect(count($lines))->toBeGreaterThanOrEqual(4);

    // 最初の行はヘッダーレコード（データ区分: 1）
    expect($lines[0])->toStartWith('1');
    expect($lines[0])->toContain('21'); // 種別コード: 21（総合振込）

    // 2行目はデータレコード（データ区分: 2）
    expect($lines[1])->toStartWith('2');

    // 最後から2行目はトレーラレコード（データ区分: 8）
    $trailerIndex = count($lines) - 2;
    expect($lines[$trailerIndex])->toStartWith('8');

    // 最後の行はエンドレコード（データ区分: 9）
    $endIndex = count($lines) - 1;
    expect($lines[$endIndex])->toStartWith('9');

    // 各行が120バイト（Shift_JIS）になっているか確認
    foreach ($lines as $line) {
        if (! empty($line)) {
            expect(strlen($line))->toBe(120);
        }
    }
});

test('全銀フォーマット変換Actionが空データを処理できる', function () {
    $converter = new ConvertToZenkinFormat;

    $excelData = collect([]);

    $config = [
        'requester_code' => '0000000001',
        'requester_name' => '依頼人テスト',
        'transaction_date' => now(),
        'bank_code' => '0001',
        'bank_name' => 'テスト銀行',
        'branch_code' => '001',
        'branch_name' => '本店',
        'account_type' => '1',
        'account_number' => '1234567',
    ];

    $result = $converter->convert($excelData, $config);

    expect($result)->toBeString();
    // 空データの場合は空文字を返す
    expect($result)->toBeEmpty();
});

test('Voltコンポーネントが正しく表示される', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('bank-transfers.index');

    $response->assertOk();
});

test('Voltコンポーネントでヘッダー情報を設定できる', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('bank-transfers.index')
        ->set('requesterCode', '0000000001')
        ->set('requesterName', 'テスト依頼人')
        ->set('transactionDate', now()->format('Y-m-d'))
        ->set('bankCode', '0001')
        ->set('bankName', 'テスト銀行')
        ->set('branchCode', '001')
        ->set('branchName', '本店')
        ->set('accountType', '1')
        ->set('accountNumber', '1234567');

    $response->assertSet('requesterCode', '0000000001');
    $response->assertSet('requesterName', 'テスト依頼人');
});
