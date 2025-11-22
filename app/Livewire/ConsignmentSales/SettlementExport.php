<?php

declare(strict_types=1);

namespace App\Livewire\ConsignmentSales;

use App\Exports\Concerns\FromView;
use App\Exports\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * 委託販売精算書のExcel出力クラス
 */
class SettlementExport implements FromView, ShouldAutoSize
{
    protected string $batchId;
    protected ?string $vendorName;
    protected Collection $salesData;
    protected array $headerInfo;
    protected float $totalAmount;
    protected float $totalCommission;
    protected float $totalNetAmount;
    protected int $totalQuantity;

    public function __construct(string $batchId = '', ?string $vendorName = null)
    {
        $this->batchId = $batchId;
        $this->vendorName = $vendorName;
        $this->loadData();
        $this->calculateTotals();
    }

    /**
     * セッションからデータを読み込む
     */
    protected function loadData(): void
    {
        // セッションからデータを取得
        $sales = $this->loadDataFromSession();

        // map()の前に再度ソートを適用して順序を確実に保持
        $sales = $sales->sortBy([
            [
                function ($item) {
                    return is_object($item)
                        ? ($item->company_name ?? ($item->vendor_name ?? 'zzz'))
                        : ($item['company_name'] ?? ($item['vendor_name'] ?? 'zzz'));
                },
                'asc',
            ],
            [
                function ($item) {
                    return is_object($item)
                        ? ($item->client_id ?? 'zzz')
                        : ($item['client_id'] ?? 'zzz');
                },
                'asc',
            ],
            [
                function ($item) {
                    return is_object($item)
                        ? ($item->product_code ?? 'zzz')
                        : ($item['product_code'] ?? 'zzz');
                },
                'asc',
            ],
        ])->values();

        // データを配列形式に変換（Viewで使用するため）
        $this->salesData = $sales->map(function ($sale) {
            // オブジェクトの場合はプロパティにアクセス、配列の場合はキーにアクセス
            if (is_object($sale)) {
                return [
                    'product_name' => $sale->product_name ?? '',
                    'quantity' => $sale->quantity ?? 0,
                    'unit_price' => $sale->unit_price ?? 0,
                    'amount' => $sale->amount ?? 0,
                    'commission' => $sale->commission ?? 0,
                    'net_amount' => $sale->net_amount ?? 0,
                    'notes' => $sale->notes ?? '',
                ];
            } else {
                return [
                    'product_name' => $sale['product_name'] ?? '',
                    'quantity' => $sale['quantity'] ?? 0,
                    'unit_price' => $sale['unit_price'] ?? 0,
                    'amount' => $sale['amount'] ?? 0,
                    'commission' => $sale['commission'] ?? 0,
                    'net_amount' => $sale['net_amount'] ?? 0,
                    'notes' => $sale['notes'] ?? '',
                ];
            }
        });

        // ヘッダー情報を取得
        $this->headerInfo = $this->getHeaderInfo();
    }

    /**
     * セッションからデータを読み込む
     */
    protected function loadDataFromSession(): Collection
    {
        $allSales = collect();

        if ($this->batchId) {
            $sessionKey = "consignment_sales_batch_{$this->batchId}";
            $batchData = Session::get($sessionKey);

            if ($batchData && isset($batchData['sales'])) {
                $batchSales = collect($batchData['sales']);

                // vendorNameが指定されている場合はフィルタリング
                if ($this->vendorName) {
                    $batchSales = $batchSales->filter(function ($sale) {
                        $saleVendorName = is_array($sale) ? ($sale['vendor_name'] ?? $sale['company_name'] ?? null) : ($sale->vendor_name ?? $sale->company_name ?? null);
                        return $saleVendorName === $this->vendorName;
                    });
                }

                // 配列をオブジェクトのコレクションに変換
                $allSales = $batchSales->map(function ($sale) {
                    return (object) $sale;
                });
            }
        } else {
            // vendorNameが指定されている場合は、すべてのバッチから該当するデータを取得
            $allSessions = Session::all();
            foreach ($allSessions as $key => $value) {
                if (str_starts_with($key, 'consignment_sales_batch_') && is_array($value) && isset($value['sales'])) {
                    // 委託先名でフィルタリング
                    if ($this->vendorName) {
                        $batchVendorName = $value['vendor_name'] ?? null;
                        $matches = false;
                        if ($batchVendorName === $this->vendorName) {
                            $matches = true;
                        } else {
                            foreach ($value['sales'] as $sale) {
                                $companyName = is_array($sale) ? ($sale['company_name'] ?? null) : ($sale->company_name ?? null);
                                if ($companyName === $this->vendorName) {
                                    $matches = true;
                                    break;
                                }
                            }
                        }
                        if (!$matches) {
                            continue;
                        }
                    }

                    $batchSales = collect($value['sales'])->map(function ($sale) {
                        return (object) $sale;
                    });
                    $allSales = $allSales->merge($batchSales);
                }
            }
        }

        // 会社名、クライアントID、商品コードの順でソート
        return $allSales
            ->sortBy([
                [
                    function ($item) {
                        return is_object($item)
                            ? ($item->company_name ?? ($item->vendor_name ?? 'zzz'))
                            : ($item['company_name'] ?? ($item['vendor_name'] ?? 'zzz'));
                    },
                    'asc',
                ],
                [
                    function ($item) {
                        return is_object($item)
                            ? ($item->client_id ?? 'zzz')
                            : ($item['client_id'] ?? 'zzz');
                    },
                    'asc',
                ],
                [
                    function ($item) {
                        return is_object($item)
                            ? ($item->product_code ?? 'zzz')
                            : ($item['product_code'] ?? 'zzz');
                    },
                    'asc',
                ],
            ])
            ->values();
    }

    /**
     * ヘッダー情報を取得（セッションから）
     */
    protected function getHeaderInfo(): array
    {
        // 選択された委託先名に基づいてヘッダー情報を取得
        if ($this->vendorName) {
            $allSessions = Session::all();
            foreach ($allSessions as $key => $value) {
                if (str_starts_with($key, 'consignment_sales_batch_') && is_array($value)) {
                    $batchVendorName = $value['vendor_name'] ?? null;
                    if ($batchVendorName === $this->vendorName) {
                        return [
                            'vendor_name' => $value['vendor_name'] ?? null,
                            'commission_rate' => $value['commission_rate'] ?? null,
                            'billing_period' => $value['billing_period'] ?? null,
                        ];
                    }
                }
            }
        }

        // batchIdが指定されている場合はそのバッチの情報を取得
        if ($this->batchId) {
            $sessionKey = "consignment_sales_batch_{$this->batchId}";
            $batchData = Session::get($sessionKey);

            if ($batchData) {
                return [
                    'vendor_name' => $batchData['vendor_name'] ?? ($this->vendorName ?? null),
                    'commission_rate' => $batchData['commission_rate'] ?? null,
                    'billing_period' => $batchData['billing_period'] ?? null,
                ];
            }
        }

        return [
            'vendor_name' => $this->vendorName ?? null,
            'commission_rate' => null,
            'billing_period' => null,
        ];
    }

    /**
     * 合計値を計算
     */
    protected function calculateTotals(): void
    {
        $this->totalQuantity = $this->salesData->sum('quantity');
        $this->totalAmount = $this->salesData->sum('amount');
        $this->totalCommission = $this->salesData->sum('commission');
        $this->totalNetAmount = $this->salesData->sum('net_amount');
    }

    /**
     * ビュー名を返す
     */
    public function view(): string
    {
        return 'livewire.consignment-sales.settlement-excel';
    }

    /**
     * ビューに渡すデータを返す
     */
    public function data(): array
    {
        return [
            'sales' => $this->salesData->toArray(),
            'vendor_name' => $this->headerInfo['vendor_name'],
            'billing_period' => $this->headerInfo['billing_period'],
            'total_quantity' => $this->totalQuantity,
            'total_amount' => $this->totalAmount,
            'total_commission' => $this->totalCommission,
            'total_net_amount' => $this->totalNetAmount,
        ];
    }
}

