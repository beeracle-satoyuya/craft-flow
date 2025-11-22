<table>
    <thead>
        <tr>
            <th colspan="2" style="text-align: center; font-size: 16px; font-weight: bold; padding: 10px;">
                精算書
            </th>
        </tr>
        <tr>
            <th style="background-color: #f3f4f6; font-weight: bold; padding: 8px; border: 1px solid #d1d5db;">
                商品名
            </th>
            <th style="background-color: #f3f4f6; font-weight: bold; padding: 8px; border: 1px solid #d1d5db; text-align: right;">
                販売数量合計
            </th>
        </tr>
    </thead>
    <tbody>
        @if ($products->count() > 0)
            @foreach ($products as $product)
                <tr>
                    <td style="padding: 8px; border: 1px solid #d1d5db;">
                        {{ $product['product_name'] }}
                    </td>
                    <td style="padding: 8px; border: 1px solid #d1d5db; text-align: right;">
                        {{ number_format($product['total_quantity']) }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td style="padding: 8px; border: 1px solid #d1d5db; font-weight: bold; background-color: #f9fafb;">
                    合計金額
                </td>
                <td style="padding: 8px; border: 1px solid #d1d5db; text-align: right; font-weight: bold; background-color: #f9fafb;">
                    ¥{{ number_format($totalAmount) }}
                </td>
            </tr>
        @else
            <tr>
                <td colspan="2" style="padding: 8px; border: 1px solid #d1d5db; text-align: center;">
                    商品データがありません
                </td>
            </tr>
        @endif
    </tbody>
</table>

