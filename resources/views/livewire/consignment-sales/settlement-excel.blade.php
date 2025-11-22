<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "メイリオ", Meiryo, "MS ゴシック", sans-serif;
            font-size: 12px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #d3d3d3;
            font-weight: bold;
            text-align: center;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <table>
        <!-- ヘッダー -->
        <tr>
            <td colspan="7" class="header-title">委託販売精算書</td>
        </tr>
        <tr>
            <td colspan="7">請求期間: {{ $billing_period ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="7">委託先名: {{ $vendor_name ?? '' }}</td>
        </tr>
        <tr>
            <td colspan="7"></td>
        </tr>
        
        <!-- 明細テーブルヘッダー -->
        <tr>
            <th>商品名</th>
            <th class="text-right">数量</th>
            <th class="text-right">単価</th>
            <th class="text-right">金額</th>
            <th class="text-right">手数料</th>
            <th class="text-right">差引支払額</th>
            <th>備考</th>
        </tr>
        
        <!-- データループ -->
        @foreach ($sales as $sale)
        <tr>
            <td>{{ $sale['product_name'] ?? '' }}</td>
            <td class="text-right">{{ number_format($sale['quantity'] ?? 0) }}</td>
            <td class="text-right">{{ number_format($sale['unit_price'] ?? 0) }}</td>
            <td class="text-right">{{ number_format($sale['amount'] ?? 0) }}</td>
            <td class="text-right">{{ number_format($sale['commission'] ?? 0) }}</td>
            <td class="text-right">{{ number_format($sale['net_amount'] ?? 0) }}</td>
            <td>{{ $sale['notes'] ?? '' }}</td>
        </tr>
        @endforeach
        
        <!-- 合計行 -->
        <tr class="total-row">
            <td colspan="1">合計</td>
            <td class="text-right">{{ number_format($total_quantity ?? 0) }}</td>
            <td></td>
            <td class="text-right">{{ number_format($total_amount ?? 0) }}</td>
            <td class="text-right">{{ number_format($total_commission ?? 0) }}</td>
            <td class="text-right">{{ number_format($total_net_amount ?? 0) }}</td>
            <td></td>
        </tr>
        
        <!-- 空行 -->
        <tr>
            <td colspan="7"></td>
        </tr>
        
        <!-- 振込先情報 -->
        <tr>
            <td colspan="2" class="total-row">振込先</td>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td>銀行名</td>
            <td colspan="6"></td>
        </tr>
        <tr>
            <td>支店名</td>
            <td colspan="6"></td>
        </tr>
        <tr>
            <td>口座種別</td>
            <td colspan="6"></td>
        </tr>
        <tr>
            <td>口座番号</td>
            <td colspan="6"></td>
        </tr>
        <tr>
            <td>口座名義</td>
            <td colspan="6"></td>
        </tr>
    </table>
</body>
</html>

