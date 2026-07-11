@props(['key', 'label', 'separator' => false])
@if($separator)<span class="mx-0.5">·</span>@endif
<kbd class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-1 py-0.5 font-mono text-[0.6rem]">{{ $key }}</kbd>
<span>{{ $label }}</span>
