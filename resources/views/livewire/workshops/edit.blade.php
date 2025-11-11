<?php

use function Livewire\Volt\{state, rules, mount};
use App\Models\Workshop;
use App\Models\WorkshopCategory;

// フォーム状態
state([
    'workshop',
    'workshop_category_id' => '',
    'program_name' => '',
    'description' => '',
    'duration_minutes' => 60,
    'max_capacity' => 10,
    'price_per_person' => 0,
    'is_active' => true,
    'categories' => fn() => WorkshopCategory::orderBy('name')->get(),
]);

// 初期化
mount(function (Workshop $workshop) {
    $this->workshop = $workshop;
    
    // フォームデータをセット
    $this->workshop_category_id = $workshop->workshop_category_id;
    $this->program_name = $workshop->program_name;
    $this->description = $workshop->description ?? '';
    $this->duration_minutes = $workshop->duration_minutes;
    $this->max_capacity = $workshop->max_capacity;
    $this->price_per_person = $workshop->price_per_person;
    $this->is_active = $workshop->is_active;
});

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

// プログラムを更新
$update = function () {
    $this->validate();

    $this->workshop->update([
        'workshop_category_id' => $this->workshop_category_id,
        'program_name' => $this->program_name,
        'description' => $this->description,
        'duration_minutes' => $this->duration_minutes,
        'max_capacity' => $this->max_capacity,
        'price_per_person' => $this->price_per_person,
        'is_active' => $this->is_active,
    ]);

    session()->flash('success', '体験プログラムを更新しました。');
    return $this->redirect(route('workshops.show', $this->workshop), navigate: true);
};

// プログラムを削除
$delete = function () {
    // 予約がある場合は削除不可
    if ($this->workshop->reservations()->count() > 0) {
        session()->flash('error', '予約が存在するため削除できません。');
        return;
    }
    
    $this->workshop->delete();
    
    session()->flash('success', '体験プログラムを削除しました。');
    return $this->redirect(route('workshops.index'), navigate: true);
};

?>

<div>
    <flux:header container class="bg-white border-b">
        <flux:heading size="xl" class="mb-0">プログラム編集</flux:heading>
    </flux:header>

    <flux:main container>
        <div class="max-w-3xl">
            <flux:card>
                <form wire:submit="update" class="space-y-6">
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
                    <div class="flex gap-3 justify-between pt-4">
                        <flux:button variant="danger" wire:click="delete" wire:confirm="本当に削除しますか？予約がある場合は削除できません。">
                            削除
                        </flux:button>
                        <div class="flex gap-3">
                            <flux:button variant="ghost" href="{{ route('workshops.show', $workshop) }}" wire:navigate>
                                キャンセル
                            </flux:button>
                            <flux:button type="submit" variant="primary">
                                更新
                            </flux:button>
                        </div>
                    </div>
                </form>
            </flux:card>
        </div>
    </flux:main>
</div>
