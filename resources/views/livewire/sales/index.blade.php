<?php

use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;

use function Livewire\Volt\rules;
use function Livewire\Volt\state;
use function Livewire\Volt\uses;

// ファイルアップロード機能を有効化
uses(WithFileUploads::class);

// 状態管理
state([
    'pdfFiles' => [],
    'isProcessing' => false,
    'error' => null,
]);

// バリデーションルール
rules([
    'pdfFiles' => 'required|array|min:1',
    'pdfFiles.*' => 'required|file|mimes:pdf|max:10240', // 10MBまで
]);

// PDFファイルを処理してExcelをダウンロード
$processAndExport = function () {
    $this->isProcessing = true;
    $this->error = null;

    try {
        $this->validate();

        if (empty($this->pdfFiles)) {
            $this->error = 'PDFファイルを選択してください。';
            $this->isProcessing = false;

            return;
        }

        // コントローラーにリクエストを送信
        // Livewireのファイルアップロードは一時ストレージに保存されるため、
        // セッションにファイルパスを保存してコントローラーに渡す
        $filePaths = [];
        foreach ($this->pdfFiles as $file) {
            if (is_object($file) && method_exists($file, 'getRealPath')) {
                $filePaths[] = $file->getRealPath();
            }
        }

        if (empty($filePaths)) {
            $this->error = 'ファイルパスの取得に失敗しました。';
            $this->isProcessing = false;

            return;
        }

        session()->put('pdf_files_paths', $filePaths);

        // JavaScriptでPOSTリクエストを送信するためにイベントを発火
        $this->dispatch('submit-export-form');
    } catch (\Illuminate\Validation\ValidationException $e) {
        $errors = $e->errors();
        $errorMessages = [];
        foreach ($errors as $key => $messages) {
            $errorMessages = array_merge($errorMessages, $messages);
        }
        $this->error = 'バリデーションエラー: ' . implode(', ', $errorMessages);
        $this->isProcessing = false;
    } catch (\Exception $e) {
        Log::error('PDF処理エラー: ' . $e->getMessage());
        $this->error = '処理中にエラーが発生しました: ' . $e->getMessage();
        $this->isProcessing = false;
    }
};

// ファイルを削除
$removeFile = function ($index) {
    if (isset($this->pdfFiles[$index])) {
        unset($this->pdfFiles[$index]);
        $this->pdfFiles = array_values($this->pdfFiles);
    }
};

// ファイルをリセット
$resetFiles = function () {
    $this->pdfFiles = [];
    $this->error = null;
};

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- ページタイトル -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-3">
                POSデータ集計システム
            </h1>
            <p class="text-lg text-zinc-600 dark:text-zinc-400">
                複数のPOSレジから出力された日次売上レポートPDFをアップロードして、商品コード別に集計したExcelファイルをダウンロードします
            </p>
        </div>

        <!-- PDFファイルアップロード -->
        <div class="mb-8 max-w-3xl mx-auto">
            <flux:card>
                <div class="p-6">
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-4">
                        PDFファイルアップロード
                    </h2>

                    <!-- ドロップゾーン -->
                    <div x-data="{
                        isDragging: false,
                        handleDrop(e) {
                            e.preventDefault();
                            this.isDragging = false;
                            if (e.dataTransfer.files.length > 0) {
                                @this.uploadMultiple('pdfFiles', Array.from(e.dataTransfer.files));
                            }
                        },
                        handleDragOver(e) {
                            e.preventDefault();
                            this.isDragging = true;
                        },
                        handleDragLeave(e) {
                            e.preventDefault();
                            this.isDragging = false;
                        }
                    }" @drop.prevent="handleDrop" @dragover.prevent="handleDragOver"
                        @dragleave.prevent="handleDragLeave"
                        class="border-2 border-dashed rounded-lg p-8 text-center transition-colors mb-4"
                        :class="isDragging ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' :
                            'border-zinc-300 dark:border-zinc-600'">
                        <div wire:loading.remove wire:target="pdfFiles">
                            <svg class="mx-auto h-12 w-12 text-zinc-400 mb-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-lg font-medium text-zinc-900 dark:text-white mb-2">
                                PDFファイルをドロップするか、クリックして選択
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                                複数のPDFファイルを一度にアップロードできます（最大10MB/ファイル）
                            </p>
                            <flux:input.file wire:model="pdfFiles" accept=".pdf" multiple class="mx-auto" />
                        </div>
                        <div wire:loading wire:target="pdfFiles" class="py-8">
                            <div class="flex flex-col items-center">
                                <svg class="animate-spin h-8 w-8 text-primary-500 mb-4"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <p class="text-lg font-medium text-zinc-900 dark:text-white">
                                    アップロード中...
                                </p>
                            </div>
                        </div>

                        <!-- アップロードされたファイル一覧 -->
                        @if (count($pdfFiles) > 0)
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">
                                    アップロードされたファイル（{{ count($pdfFiles) }}件）
                                </h3>
                                <div class="space-y-2">
                                    @foreach ($pdfFiles as $index => $file)
                                        <div
                                            class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                            <div class="flex items-center gap-2">
                                                <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-sm text-zinc-900 dark:text-white">
                                                    {{ is_string($file) ? $file : $file->getClientOriginalName() }}
                                                </span>
                                            </div>
                                            <button type="button" wire:click="removeFile({{ $index }})"
                                                class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- エラーメッセージ -->
                        @if ($error)
                            <div class="mt-4">
                                <flux:callout variant="danger">
                                    {{ $error }}
                                </flux:callout>
                            </div>
                        @endif

                        @error('pdfFiles')
                            <div class="mt-4">
                                <flux:error>{{ $message }}</flux:error>
                            </div>
                        @enderror
                        @error('pdfFiles.*')
                            <div class="mt-4">
                                <flux:error>{{ $message }}</flux:error>
                            </div>
                        @enderror

                        <!-- 処理ボタン -->
                        <div class="mt-6 flex gap-4 justify-center" x-data="{
                            submitForm() {
                                // CSRFトークンを取得
                                const csrfToken = document.querySelector('meta[name=csrf-token]')?.content ||
                                    document.querySelector('input[name=_token]')?.value;
                        
                                if (!csrfToken) {
                                    alert('CSRFトークンが見つかりません。ページを再読み込みしてください。');
                                    return;
                                }
                        
                                // POSTリクエストを送信（セッションからファイルパスを取得）
                                fetch('{{ route('sales.export') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Accept': 'application/json',
                                    },
                                    credentials: 'same-origin',
                                }).then(response => {
                                    if (response.ok) {
                                        return response.blob();
                                    }
                                    return response.json().then(data => {
                                        throw new Error(data.message || '処理に失敗しました');
                                    });
                                }).then(blob => {
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = '盛岡手づくり村_日次売上集計_' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '.xlsx';
                                    document.body.appendChild(a);
                                    a.click();
                                    window.URL.revokeObjectURL(url);
                                    document.body.removeChild(a);
                                }).catch(error => {
                                    alert('エラーが発生しました: ' + error.message);
                                });
                            }
                        }"
                            x-on:submit-export-form.window="submitForm()">
                            @if (count($pdfFiles) > 0)
                                <flux:button type="button" wire:click="processAndExport" wire:loading.attr="disabled"
                                    wire:target="processAndExport" variant="primary" class="min-w-[200px]">
                                    <span wire:loading.remove wire:target="processAndExport">
                                        集計してExcelダウンロード
                                    </span>
                                    <span wire:loading wire:target="processAndExport" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        処理中...
                                    </span>
                                </flux:button>
                                <flux:button type="button" wire:click="resetFiles" variant="ghost">
                                    リセット
                                </flux:button>
                            @endif
                        </div>

                        <!-- 使い方の説明 -->
                        <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">
                                使い方
                            </h3>
                            <ul class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1 list-disc list-inside">
                                <li>複数のPOSレジから出力された「盛岡手づくり村 日次売上レポート」PDFファイルをアップロードしてください</li>
                                <li>各PDFファイルから営業日と売上データが自動的に抽出されます</li>
                                <li>全レジのデータを商品コード別に集計し、Excelファイルとしてダウンロードできます</li>
                                <li>1ファイルあたり最大10MBまでアップロード可能です</li>
                            </ul>
                        </div>
                    </div>
            </flux:card>
        </div>
    </div>
</div>
