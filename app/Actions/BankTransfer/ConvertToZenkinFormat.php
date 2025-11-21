<?php

declare(strict_types=1);

namespace App\Actions\BankTransfer;

use Illuminate\Support\Collection;

/**
 * 全銀フォーマット変換アクション
 * エクセルデータを全銀フォーマット（固定長形式）に変換する
 */
class ConvertToZenkinFormat
{
    /**
     * エクセルデータを全銀フォーマットに変換
     * 標準的な構造: 1つのヘッダーレコード + 複数のデータレコード + トレーラ + エンド
     *
     * @param  Collection<int, array<string, mixed>>  $excelData  エクセルから読み込んだデータ
     * @param  array<string, mixed>  $config  変換設定
     * @return string 全銀フォーマットの文字列
     */
    public function convert(Collection $excelData, array $config = []): string
    {
        $lines = [];
        $totalCount = 0;
        $totalAmount = 0;
        $firstRow = null;

        // 有効なデータを収集し、最初の行を取得
        $validRows = [];
        foreach ($excelData as $row) {
            // 必須項目のチェック
            if (empty($row['金融機関コード']) || empty($row['口座番号']) || empty($row['振込金額'])) {
                continue;
            }

            $validRows[] = $row;
            if ($firstRow === null) {
                $firstRow = $row;
            }
        }

        // 有効なデータがない場合は空文字を返す
        if (empty($validRows)) {
            return '';
        }

        // ヘッダー情報を設定から取得、または最初の行から取得
        // 取組日の変換（Excel形式: 2025/12/31 → DateTime）
        $transactionDate = now();
        if (! empty($config['transaction_date'])) {
            try {
                $dateStr = str_replace('/', '-', $config['transaction_date']);
                $transactionDate = new \DateTime($dateStr);
            } catch (\Exception $e) {
                $transactionDate = now();
            }
        } elseif (! empty($firstRow['振込予定日'])) {
            try {
                $dateStr = str_replace('/', '-', $firstRow['振込予定日']);
                $transactionDate = new \DateTime($dateStr);
            } catch (\Exception $e) {
                $transactionDate = now();
            }
        }

        // ヘッダー設定を構築
        $headerConfig = [
            'requester_code' => $config['requester_code'] ?? '0000000001',
            'requester_name' => $config['requester_name'] ?? trim(($firstRow['事業者名'] ?? '') . ' ' . ($firstRow['代表者氏名'] ?? '')),
            'transaction_date' => $transactionDate,
            // ヘッダーには仕向銀行情報は含めない（標準仕様では不要）
            'bank_code' => '',
            'bank_name' => '',
            'branch_code' => '',
            'branch_name' => '',
            'account_type' => '1',
            'account_number' => '',
        ];

        // 1つのヘッダーレコードを生成
        $headerRecord = $this->createHeaderRecord($headerConfig);
        $lines[] = $headerRecord;

        // すべてのデータレコードを生成
        foreach ($validRows as $row) {
            $dataRecord = $this->createDataRecord($row);
            $lines[] = $dataRecord;
            $totalCount++;
            $totalAmount += (int) $row['振込金額'];
        }

        // トレーラレコードを追加
        if ($totalCount > 0) {
            $trailerRecord = $this->createTrailerRecord($totalCount, $totalAmount);
            $lines[] = $trailerRecord;
        }

        // エンドレコードを追加
        $endRecord = $this->createEndRecord();
        $lines[] = $endRecord;

        return implode("\r\n", $lines);
    }

    /**
     * ヘッダーレコードを生成
     * 120バイト固定長
     *
     * @param  array<string, mixed>  $config
     */
    private function createHeaderRecord(array $config): string
    {
        // データ区分: 1（ヘッダー）
        $dataType = '1';

        // 種別コード: 21（総合振込）
        $typeCode = '21';

        // コード区分: 0（JIS）
        $codeType = '0';

        // 振込依頼人コード（10桁、右詰め0埋め）
        $requesterCode = str_pad(
            (string) ($config['requester_code'] ?? '0000000000'),
            10,
            '0',
            STR_PAD_LEFT
        );

        // 振込依頼人名（40バイト、左詰めスペース、SJIS換算）
        $requesterName = $this->convertToZenkinTeleChars($config['requester_name'] ?? '');
        $requesterName = $this->truncateToSJISBytes($requesterName, 40);
        $requesterName = $this->padToSJISBytes($requesterName, 40);

        // 取組日（MMDD形式、4桁）
        $transactionDate = $this->formatDate($config['transaction_date'] ?? now());

        // 仕向銀行番号（4桁）
        $bankCode = str_pad(
            (string) ($config['bank_code'] ?? '0000'),
            4,
            '0',
            STR_PAD_LEFT
        );

        // 仕向銀行名（15バイト、左詰めスペース、SJIS換算）
        $bankName = $this->convertToZenkinTeleChars($config['bank_name'] ?? '');
        $bankName = $this->truncateToSJISBytes($bankName, 15);
        $bankName = $this->padToSJISBytes($bankName, 15);

        // 仕向支店番号（3桁）
        $branchCode = str_pad(
            (string) ($config['branch_code'] ?? '000'),
            3,
            '0',
            STR_PAD_LEFT
        );

        // 仕向支店名（15バイト、左詰めスペース、SJIS換算）
        $branchName = $this->convertToZenkinTeleChars($config['branch_name'] ?? '');
        $branchName = $this->truncateToSJISBytes($branchName, 15);
        $branchName = $this->padToSJISBytes($branchName, 15);

        // 預金種目（1桁: 1=普通、2=当座、9=その他）
        $accountType = (string) ($config['account_type'] ?? '1');

        // 口座番号（7桁、右詰め0埋め）
        $accountNumber = str_pad(
            (string) ($config['account_number'] ?? '0000000'),
            7,
            '0',
            STR_PAD_LEFT
        );

        // ダミー（17文字、スペース）
        $dummy = str_repeat(' ', 17);

        // レコードを結合（UTF-8のまま）
        $record = $dataType
            . $typeCode
            . $codeType
            . $requesterCode
            . $requesterName
            . $transactionDate
            . $bankCode
            . $bankName
            . $branchCode
            . $branchName
            . $accountType
            . $accountNumber
            . $dummy;

        // Shift_JISに変換（一度だけ）
        $record = mb_convert_encoding($record, 'SJIS', 'UTF-8');

        // 120バイトに調整（足りない場合はスペースで埋める、多い場合は安全に切り詰める）
        $byteLength = strlen($record);
        if ($byteLength < 120) {
            $record .= str_repeat(' ', 120 - $byteLength);
        } elseif ($byteLength > 120) {
            // マルチバイト文字の途中で切れないように安全に切り詰める
            $record = mb_strcut($record, 0, 120, 'SJIS');
            // 切り詰め後も120バイトになるようにスペースで埋める
            $record = str_pad($record, 120, ' ', STR_PAD_RIGHT);
        }

        return $record;
    }

    /**
     * データレコードを生成
     * エクセルの各行から振込データを生成
     *
     * @param  array<string, mixed>  $row  エクセルの1行分のデータ
     */
    private function createDataRecord(array $row): string
    {
        // データ区分: 2（データ）
        // 注意: 必須項目のチェックはconvertメソッドで既に実施済み
        $dataType = '2';

        // 被仕向銀行番号（4桁）
        // 数字以外の文字を除去してから処理
        $bankCodeInput = preg_replace('/[^0-9]/', '', (string) $row['金融機関コード']);
        $bankCode = str_pad(
            $bankCodeInput ?: '0000',
            4,
            '0',
            STR_PAD_LEFT
        );

        // 被仕向銀行名（15バイト、左詰めスペース、SJIS換算）
        $bankName = $this->convertToZenkinTeleChars($row['金融機関名'] ?? '');
        $bankName = $this->truncateToSJISBytes($bankName, 15);
        $bankName = $this->padToSJISBytes($bankName, 15);

        // 被仕向支店番号（3桁）
        // 数字以外の文字を除去してから処理
        $branchCodeInput = preg_replace('/[^0-9]/', '', (string) ($row['支店コード'] ?? '000'));
        $branchCode = str_pad(
            $branchCodeInput ?: '000',
            3,
            '0',
            STR_PAD_LEFT
        );

        // 被仕向支店名（15バイト、左詰めスペース、SJIS換算）
        $branchName = $this->convertToZenkinTeleChars($row['支店名'] ?? '');
        $branchName = $this->truncateToSJISBytes($branchName, 15);
        $branchName = $this->padToSJISBytes($branchName, 15);

        // 口座番号の処理
        // 入力データが10桁以上の場合、手形交換所番号+預金種目+口座番号が結合されている可能性がある
        $accountNumberInput = (string) $row['口座番号'];
        $accountNumberInput = preg_replace('/[^0-9]/', '', $accountNumberInput); // 数字のみ抽出

        // 手形交換所番号（4桁、任意項目）
        // 入力データから手形交換所番号が分離されている場合はそれを使用、そうでなければデフォルト
        if (isset($row['手形交換所番号']) && ! empty($row['手形交換所番号'])) {
            $clearingHouseCode = str_pad(
                (string) $row['手形交換所番号'],
                4,
                '0',
                STR_PAD_LEFT
            );
        } elseif (strlen($accountNumberInput) >= 12) {
            // 入力が12桁以上の場合、左から4桁を手形交換所番号として扱う
            $clearingHouseCode = substr($accountNumberInput, 0, 4);
            $accountNumberInput = substr($accountNumberInput, 4);
        } elseif (strlen($accountNumberInput) === 10) {
            // 入力が10桁の場合、左から4桁を手形交換所番号として扱う（預金種目は後で処理）
            $clearingHouseCode = substr($accountNumberInput, 0, 4);
            $accountNumberInput = substr($accountNumberInput, 4);
        } else {
            $clearingHouseCode = '0000';
        }

        // 預金種目（1桁: 1=普通、2=当座、4=貯蓄、9=その他）
        // 入力データから預金種目が分離されている場合はそれを使用
        if (isset($row['預金種目']) && ! empty($row['預金種目'])) {
            $accountType = $this->normalizeAccountType($row['預金種目']);
        } elseif (strlen($accountNumberInput) >= 8) {
            // 入力が8桁以上の場合、左から1桁を預金種目として扱う
            $accountType = substr($accountNumberInput, 0, 1);
            $accountNumberInput = substr($accountNumberInput, 1);
        } elseif (strlen($accountNumberInput) === 6) {
            // 入力が6桁の場合、預金種目は1（普通預金）として扱う
            $accountType = '1';
        } else {
            $accountType = '1';
        }

        // 口座番号（7桁、右詰め0埋め）
        // 残りの部分から右から7桁を取得
        if (strlen($accountNumberInput) > 7) {
            $accountNumberInput = substr($accountNumberInput, -7);
        }
        $accountNumber = str_pad(
            $accountNumberInput,
            7,
            '0',
            STR_PAD_LEFT
        );

        // 受取人名（30バイト、左詰めスペース、SJIS換算）
        $accountName = $this->convertToZenkinTeleChars($row['口座名義（カナ）'] ?? '');
        $accountName = $this->truncateToSJISBytes($accountName, 30);
        $accountName = $this->padToSJISBytes($accountName, 30);

        // 振込金額（10桁、右詰め0埋め）
        $amount = str_pad(
            (string) (int) $row['振込金額'],
            10,
            '0',
            STR_PAD_LEFT
        );

        // 新規コード（1桁: 1=第1回振込分、2=変更分、0=その他）
        $newCode = $row['新規コード'] ?? '0';
        if (! in_array($newCode, ['0', '1', '2'], true)) {
            $newCode = '0';
        }

        // 顧客コード1またはEDI情報（10バイトまたは20バイト）
        // 識別表示が'Y'の場合はEDI情報（20バイト）、それ以外は顧客コード1（10バイト）
        $identificationFlag = ($row['識別表示'] ?? '') === 'Y' ? 'Y' : ' ';
        $hasEDI = $identificationFlag === 'Y';

        if ($hasEDI) {
            // EDI情報（20バイト）
            $ediInfo = $this->truncateToSJISBytes($row['EDI情報'] ?? '', 20);
            $ediInfo = $this->padToSJISBytes($ediInfo, 20);
            $customerCode1 = '';
            $customerCode2 = '';
        } else {
            // 顧客コード1（10バイト）
            $customerCode1 = str_pad(
                (string) ($row['顧客ID'] ?? '0000000000'),
                10,
                '0',
                STR_PAD_LEFT
            );
            // 顧客コード2（10バイト）
            $customerCode2 = str_pad(
                (string) ($row['顧客コード2'] ?? '0000000000'),
                10,
                '0',
                STR_PAD_LEFT
            );
            $ediInfo = '';
        }

        // 振込指定区分（1桁、任意項目: 7=テレ振込、8=文書振込）
        $transferType = $row['振込指定区分'] ?? '';
        if (! in_array($transferType, ['7', '8'], true)) {
            $transferType = ' ';
        }

        // 識別表示（1バイト: Yまたはスペース）
        // 上で既に設定済み

        // ダミー（7バイト、スペース）
        $dummy = str_repeat(' ', 7);

        // レコードを結合（UTF-8のまま）
        $record = $dataType
            . $bankCode
            . $bankName
            . $branchCode
            . $branchName
            . $clearingHouseCode
            . $accountType
            . $accountNumber
            . $accountName
            . $amount
            . $newCode;

        if ($hasEDI) {
            $record .= $ediInfo;
        } else {
            $record .= $customerCode1 . $customerCode2;
        }

        $record .= $transferType
            . $identificationFlag
            . $dummy;

        // Shift_JISに変換（一度だけ）
        $record = mb_convert_encoding($record, 'SJIS', 'UTF-8');

        // 120バイトに調整（足りない場合はスペースで埋める、多い場合は安全に切り詰める）
        $byteLength = strlen($record);
        if ($byteLength < 120) {
            $record .= str_repeat(' ', 120 - $byteLength);
        } elseif ($byteLength > 120) {
            // マルチバイト文字の途中で切れないように安全に切り詰める
            $record = mb_strcut($record, 0, 120, 'SJIS');
            // 切り詰め後も120バイトになるようにスペースで埋める
            $record = str_pad($record, 120, ' ', STR_PAD_RIGHT);
        }

        return $record;
    }

    /**
     * 日付をMMDD形式に変換
     */
    private function formatDate(string|\DateTimeInterface $date): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format('md');
    }

    /**
     * 預金種目を正規化
     */
    private function normalizeAccountType(string|int $type): string
    {
        $type = (string) $type;

        // 文字列の場合の変換
        if (preg_match('/普通/i', $type)) {
            return '1';
        }
        if (preg_match('/当座/i', $type)) {
            return '2';
        }
        if (preg_match('/貯蓄/i', $type)) {
            return '4';
        }

        // 数値の場合
        if (in_array($type, ['1', '2', '4', '9'], true)) {
            return $type;
        }

        // デフォルトは普通預金
        return '1';
    }

    /**
     * トレーラレコードを生成
     * 120バイト固定長
     *
     * @param  int  $totalCount  合計件数
     * @param  int  $totalAmount  合計金額
     */
    private function createTrailerRecord(int $totalCount, int $totalAmount): string
    {
        // データ区分: 8（トレーラ）
        $dataType = '8';

        // 合計件数（6桁、右詰め0埋め）
        $count = str_pad(
            (string) $totalCount,
            6,
            '0',
            STR_PAD_LEFT
        );

        // 合計金額（12桁、右詰め0埋め）
        $amount = str_pad(
            (string) $totalAmount,
            12,
            '0',
            STR_PAD_LEFT
        );

        // ダミー（101バイト、スペース）
        $dummy = str_repeat(' ', 101);

        // レコードを結合（UTF-8のまま）
        $record = $dataType
            . $count
            . $amount
            . $dummy;

        // Shift_JISに変換（一度だけ）
        $record = mb_convert_encoding($record, 'SJIS', 'UTF-8');

        // 120バイトに調整（足りない場合はスペースで埋める、多い場合は安全に切り詰める）
        $byteLength = strlen($record);
        if ($byteLength < 120) {
            $record .= str_repeat(' ', 120 - $byteLength);
        } elseif ($byteLength > 120) {
            // マルチバイト文字の途中で切れないように安全に切り詰める
            $record = mb_strcut($record, 0, 120, 'SJIS');
            // 切り詰め後も120バイトになるようにスペースで埋める
            $record = str_pad($record, 120, ' ', STR_PAD_RIGHT);
        }

        return $record;
    }

    /**
     * エンドレコードを生成
     * 120バイト固定長
     */
    private function createEndRecord(): string
    {
        // データ区分: 9（エンド）
        $dataType = '9';

        // ダミー（119バイト、スペース）
        $dummy = str_repeat(' ', 119);

        // レコードを結合（UTF-8のまま）
        $record = $dataType . $dummy;

        // Shift_JISに変換（一度だけ）
        $record = mb_convert_encoding($record, 'SJIS', 'UTF-8');

        // 120バイトに調整（足りない場合はスペースで埋める、多い場合は安全に切り詰める）
        $byteLength = strlen($record);
        if ($byteLength < 120) {
            $record .= str_repeat(' ', 120 - $byteLength);
        } elseif ($byteLength > 120) {
            // マルチバイト文字の途中で切れないように安全に切り詰める
            $record = mb_strcut($record, 0, 120, 'SJIS');
            // 切り詰め後も120バイトになるようにスペースで埋める
            $record = str_pad($record, 120, ' ', STR_PAD_RIGHT);
        }

        return $record;
    }

    /**
     * UTF-8文字列をSJISのバイト数制限に合わせて切り詰める
     *
     * @param  string  $text  UTF-8の文字列
     * @param  int  $maxBytes  SJIS換算での最大バイト数
     * @return string 切り詰められたUTF-8文字列
     */
    private function truncateToSJISBytes(string $text, int $maxBytes): string
    {
        if (empty($text)) {
            return '';
        }

        // UTF-8のまま、SJISに変換した場合のバイト数を考慮して切り詰める
        $sjisText = mb_convert_encoding($text, 'SJIS', 'UTF-8');
        $sjisLength = strlen($sjisText);

        if ($sjisLength <= $maxBytes) {
            return $text;
        }

        // バイト数制限に合わせて切り詰める
        $truncatedSJIS = mb_strcut($sjisText, 0, $maxBytes, 'SJIS');

        // UTF-8に戻す
        return mb_convert_encoding($truncatedSJIS, 'UTF-8', 'SJIS');
    }

    /**
     * UTF-8文字列をSJISのバイト数に合わせてスペースで埋める
     *
     * @param  string  $text  UTF-8の文字列
     * @param  int  $maxBytes  SJIS換算での最大バイト数
     * @return string パディングされたUTF-8文字列
     */
    private function padToSJISBytes(string $text, int $maxBytes): string
    {
        if (empty($text)) {
            return str_repeat(' ', $maxBytes);
        }

        // SJISに変換してバイト数を確認
        $sjisText = mb_convert_encoding($text, 'SJIS', 'UTF-8');
        $sjisLength = strlen($sjisText);

        if ($sjisLength >= $maxBytes) {
            return $text;
        }

        // 不足分をスペースで埋める（UTF-8のスペースは1バイト）
        $padding = $maxBytes - $sjisLength;

        return $text . str_repeat(' ', $padding);
    }

    /**
     * 文字列を全銀テレ為替文字に変換
     * 使用可能な文字: 半角カタカナ、大文字英字、数字、特定の記号
     *
     * @param  string  $text  変換前の文字列
     * @return string 変換後の文字列
     */
    private function convertToZenkinTeleChars(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // 全角カタカナを半角カタカナに変換
        $text = mb_convert_kana($text, 'a', 'UTF-8');

        // 小文字英字を大文字に変換
        $text = mb_strtoupper($text, 'UTF-8');

        // 全角英数字を半角に変換
        $text = mb_convert_kana($text, 'n', 'UTF-8');

        // 使用可能な文字のみを残す
        // 半角カタカナ（ァ-ヶ、ー）、大文字英字、数字、特定の記号のみ許可
        // 正規表現で許可された文字のみを抽出
        $result = '';
        $length = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');

            // 半角カタカナ（ァ-ヶ、ー、濁点、半濁点）
            if (preg_match('/[ァ-ヶー]/u', $char)) {
                $result .= $char;
            }
            // 大文字英字（A-Z）
            elseif (preg_match('/[A-Z]/', $char)) {
                $result .= $char;
            }
            // 数字（0-9）
            elseif (preg_match('/[0-9]/', $char)) {
                $result .= $char;
            }
            // スペース
            elseif ($char === ' ') {
                $result .= ' ';
            }
            // ハイフン（-）
            elseif ($char === '-') {
                $result .= '-';
            }
            // ピリオド（.）
            elseif ($char === '.') {
                $result .= '.';
            }
            // スラッシュ（/）
            elseif ($char === '/') {
                $result .= '/';
            }
            // 円マーク（￥）
            elseif ($char === '￥' || $char === '\\' || $char === '¥') {
                $result .= '￥';
            }
            // 左括弧（(）
            elseif ($char === '(') {
                $result .= '(';
            }
            // 右括弧（)）
            elseif ($char === ')') {
                $result .= ')';
            }
        }

        return $result;
    }
}
