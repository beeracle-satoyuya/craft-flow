<?php

use function Livewire\Volt\{state, rules};
use App\Models\Workshop;
use App\Models\WorkshopCategory;

// フォーム状態
state([
    'workshop_category_id' => '',
    'program_name' => '',
    'description' => '',
    'duration_minutes' => 60,
    'max_capacity' => 10,
    'price_per_person' => 0,
    'is_active' => true,
    'categories' => fn() => WorkshopCategory::orderBy('name')->get(),
]);

// バリデーションルール
rules([
    'workshop_category_id' => 'required|exists:workshop_categories,id',
    'program_name' => 'required|string|max:255',
    'description' => 'nullable|string',
    'duration_minutes' => 'required|integer|min:1',
    'max_capacity' => 'required|integer|min:1',
    'price_per_person' => 'required|integer|min:0',
    'is_active' => 'boolean',
]);

// プログラムを保存
$save = function () {
    $this->validate();

    $workshop = Workshop::create([
        'workshop_category_id' => $this->workshop_category_id,
        'program_name' => $this->program_name,
        'description' => $this->description,
        'duration_minutes' => $this->duration_minutes,
        'max_capacity' => $this->max_capacity,
        'price_per_person' => $this->price_per_person,
        'is_active' => $this->is_active,
    ]);

    session()->flash('success', '体験プログラムを登録しました。');
    return $this->redirect(route('workshops.show', $workshop), navigate: true);
};

?>

<div>
    <flux:header container class="bg-white border-b">
        <flux:heading size="xl" class="mb-0">新規プログラム登録</flux:heading>
    </flux:header>

    <flux:main container>
        <div class="max-w-3xl">
            <flux:card>
                <form wire:submit="save" class="space-y-6">
                    <!-- カテゴリ選択 -->
                    <flux:field>
                        <flux:label>カテゴリ <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="workshop_category_id" required>
                            <option value="">カテゴリを選択してください</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="workshop_category_id" />
                        <flux:description>
                            カテゴリがない場合は、
                            <a href="{{ route('workshop-categories.index') }}" class="text-blue-600 hover:underline" wire:navigate>
                                カテゴリ管理
                            </a>
                            から先に作成してください。
                        </flux:description>
                    </flux:field>

                    <!-- プログラム名 -->
                    <flux:field>
                        <flux:label>プログラム名 <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="program_name" placeholder="例: 藍染めハンカチ体験" required />
                        <flux:error name="program_name" />
                    </flux:field>

                    <!-- 説明 -->
                    <flux:field>
                        <flux:label>説明</flux:label>
                        <flux:textarea wire:model="description" rows="4" placeholder="プログラムの詳細を入力してください..." />
                        <flux:error name="description" />
                    </flux:field>

                    <!-- 所要時間 -->
                    <flux:field>
                        <flux:label>所要時間（分） <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" wire:model="duration_minutes" min="1" required />
                        <flux:error name="duration_minutes" />
                    </flux:field>

                    <!-- 最大受入人数 -->
                    <flux:field>
                        <flux:label>最大受入人数 <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" wire:model="max_capacity" min="1" required />
                        <flux:error name="max_capacity" />
                    </flux:field>

                    <!-- 料金 -->
                    <flux:field>
                        <flux:label>料金（1人あたり） <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" wire:model="price_per_person" min="0" required />
                        <flux:description>円単位で入力してください</flux:description>
                        <flux:error name="price_per_person" />
                    </flux:field>

                    <!-- アクティブフラグ -->
                    <flux:field>
                        <div class="flex items-center gap-2">
                            <flux:checkbox wire:model="is_active" />
                            <flux:label>有効化</flux:label>
                        </div>
                        <flux:description>チェックを入れると、予約受付可能になります</flux:description>
                        <flux:error name="is_active" />
                    </flux:field>

                    <!-- ボタン -->
                    <div class="flex gap-3 justify-end pt-4">
                        <flux:button variant="ghost" href="{{ route('workshops.index') }}" wire:navigate>
                            キャンセル
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            登録
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </flux:main>
</div>
