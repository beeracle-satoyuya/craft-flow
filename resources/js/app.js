import flatpickr from 'flatpickr';
import { Japanese } from 'flatpickr/dist/l10n/ja.js';
import ApexCharts from 'apexcharts';

// ApexChartsをグローバルに設定
window.ApexCharts = ApexCharts;

// Flatpickrのデフォルト設定
flatpickr.setDefaults({
    locale: Japanese,
});

// Livewire hooks
document.addEventListener('livewire:init', () => {
    // 日付ピッカーの初期化
    Livewire.hook('morph.updated', () => {
        initDatePickers();
        initTimePickers();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    initDatePickers();
    initTimePickers();
});

function initDatePickers() {
    const datePickers = document.querySelectorAll('[data-date-picker]');
    datePickers.forEach(input => {
        if (input._flatpickr) {
            return; // 既に初期化済み
        }

        flatpickr(input, {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            onChange: function (selectedDates, dateStr) {
                // Livewireの状態を更新
                input.value = dateStr;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });
}

function initTimePickers() {
    const timePickers = document.querySelectorAll('[data-time-picker]');
    timePickers.forEach(input => {
        if (input._flatpickr) {
            return; // 既に初期化済み
        }

        flatpickr(input, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            minuteIncrement: 15,
            minTime: '09:00',
            maxTime: '17:00',
            onChange: function (selectedDates, timeStr) {
                // Livewireの状態を更新
                input.value = timeStr;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });
}




