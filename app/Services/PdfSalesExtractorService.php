<?php

declare(strict_types=1);

namespace App\Services;

class PdfSalesExtractorService
{
    /**
     * PDFテキストから営業日と売上レコードを抽出する
     *
     * @param  string  $pdfText  PDFから抽出したテキストコンテンツ
     * @return array{date: string, register_number: string, records: array<int, array{product_code: string, product_name: string, unit_price: int, quantity: int, subtotal: int}>}
     */
    public function extractSalesData(string $pdfText): array
    {
        $date = $this->extractBusinessDate($pdfText);
        $registerNumber = $this->extractRegisterNumber($pdfText);
        $records = $this->extractSalesRecords($pdfText);

        return [
            'date' => $date,
            'register_number' => $registerNumber,
            'records' => $records,
        ];
    }

    /**
     * PDFテキストから営業日を抽出し、西暦形式に変換する
     *
     * @param  string  $pdfText  PDFから抽出したテキストコンテンツ
     * @return string YYYY/MM/DD形式の日付文字列
     */
    private function extractBusinessDate(string $pdfText): string
    {
        // 営業日：\s*(令和|平成|昭和|大正|明治)\s*(\d+)年(\d+)月(\d+)日 のパターンで抽出
        $pattern = '/営業日：\s*(令和|平成|昭和|大正|明治)\s*(\d+)年(\d+)月(\d+)日/u';
        if (preg_match($pattern, $pdfText, $matches)) {
            $era = $matches[1];
            $year = (int) $matches[2];
            $month = (int) $matches[3];
            $day = (int) $matches[4];

            $westernYear = $this->convertToWesternYear($era, $year);

            return sprintf('%04d/%02d/%02d', $westernYear, $month, $day);
        }

        // 営業日が見つからない場合は現在の日付を使用
        return now()->format('Y/m/d');
    }

    /**
     * 和暦を西暦に変換する
     *
     * @param  string  $era  元号（令和、平成、昭和、大正、明治）
     * @param  int  $year  和暦の年
     * @return int 西暦の年
     */
    private function convertToWesternYear(string $era, int $year): int
    {
        return match ($era) {
            '令和' => $year + 2018,
            '平成' => $year + 1988,
            '昭和' => $year + 1925,
            '大正' => $year + 1911,
            '明治' => $year + 1867,
            default => $year,
        };
    }

    /**
     * PDFテキストからレジ番号を抽出する
     *
     * @param  string  $pdfText  PDFから抽出したテキストコンテンツ
     * @return string レジ番号（例: POS1, POS2）。見つからない場合は空文字列
     */
    private function extractRegisterNumber(string $pdfText): string
    {
        // レジ番号：\s*(\S+) のパターンで抽出
        $pattern = '/レジ番号[：:]\s*(\S+)/u';
        if (preg_match($pattern, $pdfText, $matches)) {
            return trim($matches[1]);
        }

        // レジ番号が見つからない場合は空文字列を返す
        return '';
    }

    /**
     * PDFテキストから売上レコードを抽出する
     *
     * @param  string  $pdfText  PDFから抽出したテキストコンテンツ
     * @return array<int, array{product_code: string, product_name: string, unit_price: int, quantity: int, subtotal: int}>
     */
    private function extractSalesRecords(string $pdfText): array
    {
        $records = [];

        // 1行に2レコードが横並びのパターンを抽出（改訂版）
        // 商品名の抽出を改善：商品名の中に¥記号が含まれないことを前提に、次の¥記号まで確実にマッチ
        // パターン: (P\d{3})\s*([^\¥]+?)\s*¥([\d,]+)\s*(\d+)\s*¥([\d,]+)\s+(P\d{3})\s*([^\¥]+?)\s*¥([\d,]+)\s*(\d+)\s*¥([\d,]+)
        $pattern = '/(P\d{3})\s*([^\¥]+?)\s*¥([\d,]+)\s*(\d+)\s*¥([\d,]+)\s+(P\d{3})\s*([^\¥]+?)\s*¥([\d,]+)\s*(\d+)\s*¥([\d,]+)/u';

        if (preg_match_all($pattern, $pdfText, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                // 1レコード目（左側）
                $records[] = [
                    'product_code' => $match[1],
                    'product_name' => $this->cleanProductName($match[2]),
                    'unit_price' => $this->parsePrice($match[3]),
                    'quantity' => (int) $match[4],
                    'subtotal' => $this->parsePrice($match[5]),
                ];

                // 2レコード目（右側）
                $records[] = [
                    'product_code' => $match[6],
                    'product_name' => $this->cleanProductName($match[7]),
                    'unit_price' => $this->parsePrice($match[8]),
                    'quantity' => (int) $match[9],
                    'subtotal' => $this->parsePrice($match[10]),
                ];
            }
        }

        // 1レコードのみのパターンも抽出（2レコードのパターンにマッチしない場合）
        // 商品名の抽出を改善：商品名の中に¥記号が含まれないことを前提に、次の¥記号まで確実にマッチ
        $singlePattern = '/(P\d{3})\s*([^\¥]+?)\s*¥([\d,]+)\s*(\d+)\s*¥([\d,]+)(?!\s+P\d{3})/u';
        if (preg_match_all($singlePattern, $pdfText, $singleMatches, PREG_SET_ORDER)) {
            foreach ($singleMatches as $match) {
                // 既に2レコードパターンで抽出済みかチェック
                $isDuplicate = false;
                $cleanedProductName = $this->cleanProductName($match[2]);
                foreach ($records as $record) {
                    if (
                        $record['product_code'] === $match[1] &&
                        $record['product_name'] === $cleanedProductName &&
                        $record['unit_price'] === $this->parsePrice($match[3]) &&
                        $record['quantity'] === (int) $match[4] &&
                        $record['subtotal'] === $this->parsePrice($match[5])
                    ) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (! $isDuplicate) {
                    $records[] = [
                        'product_code' => $match[1],
                        'product_name' => $cleanedProductName,
                        'unit_price' => $this->parsePrice($match[3]),
                        'quantity' => (int) $match[4],
                        'subtotal' => $this->parsePrice($match[5]),
                    ];
                }
            }
        }

        return $records;
    }

    /**
     * 商品名をクリーンアップする（前後の空白を削除し、連続する空白を1つに統一）
     *
     * @param  string  $productName  商品名
     * @return string クリーンアップされた商品名
     */
    private function cleanProductName(string $productName): string
    {
        // 前後の空白を削除
        $cleaned = trim($productName);
        // 連続する空白を1つに統一
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned);

        return $cleaned;
    }

    /**
     * 価格文字列から整数値を取得する（¥とカンマを除去）
     *
     * @param  string  $price  価格文字列（例: "¥1,234" または "1,234"）
     * @return int 整数値
     */
    private function parsePrice(string $price): int
    {
        // ¥とカンマを除去して整数値に変換
        $cleaned = str_replace(['¥', ','], '', $price);

        return (int) $cleaned;
    }
}
