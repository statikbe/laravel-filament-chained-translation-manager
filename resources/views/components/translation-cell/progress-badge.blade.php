@props(['total'])
<span
    :class="{
        'bg-success-50 text-success-700 ring-success-200 dark:bg-success-900/30 dark:text-success-400 dark:ring-success-800/40': translatedCount === {{ $total }},
        'bg-warning-50 text-warning-700 ring-warning-200 dark:bg-warning-900/30 dark:text-warning-400 dark:ring-warning-800/40': translatedCount > 0 && translatedCount < {{ $total }},
        'bg-danger-50 text-danger-600 ring-danger-200 dark:bg-danger-900/30 dark:text-danger-400 dark:ring-danger-800/40': translatedCount === 0
    }"
    class="shrink-0 tabular-nums text-[0.7rem] font-semibold px-2 py-0.5 rounded-full ring-1 leading-tight transition-colors duration-150"
    x-text="`${translatedCount}/{{ $total }}`"
></span>
