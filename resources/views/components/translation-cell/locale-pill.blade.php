@props(['locale', 'translatedLabel', 'missingLabel'])
<span
    :class="translations['{{ $locale }}'] && translations['{{ $locale }}'].trim()
        ? 'bg-success-100 text-success-700 ring-success-200 dark:bg-success-900/40 dark:text-success-400 dark:ring-success-800/40'
        : 'bg-danger-50 text-danger-400 ring-danger-100 dark:bg-danger-900/20 dark:text-danger-500 dark:ring-danger-800/30'"
    :title="`{{ strtoupper($locale) }}: ` + (translations['{{ $locale }}'] && translations['{{ $locale }}'].trim() ? '{{ $translatedLabel }}' : '{{ $missingLabel }}')"
    class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[0.6rem] font-mono font-bold uppercase leading-none ring-1 transition-colors duration-150"
>
    <span
        :class="translations['{{ $locale }}'] && translations['{{ $locale }}'].trim() ? 'bg-success-500' : 'bg-danger-300 dark:bg-danger-600'"
        class="w-1.5 h-1.5 rounded-full transition-colors duration-150"
    ></span>
    {{ strtoupper($locale) }}
</span>
