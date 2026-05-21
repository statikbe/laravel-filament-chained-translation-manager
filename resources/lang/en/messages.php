<?php

return [
    // Page & navigation
    'title' => 'Translation manager',
    'navigation_group' => 'Settings',

    // Table columns
    'key' => 'Key',
    'group' => 'Group',
    'translations' => 'Translations',
    'source_locale_label' => 'source',
    'set_as_source' => 'Set as source locale',
    'key_copied' => 'Copied!',
    'copy_key' => 'Copy key',

    // Filters
    'search_term_placeholder' => 'Search translation key or value…',
    'selected_groups_placeholder' => 'Filter by group',
    'selected_languages_placeholder' => 'Filter by language',
    'only_show_missing_translations_lbl' => 'Missing only',

    // Status labels
    'translated' => 'Translated',
    'missing_translation' => 'Not translated',
    'copy_source' => 'Copy source',

    // Empty states
    'error_no_translations_for_filters' => 'No translations match your filters.',
    'error_no_translations_for_filters_description' => 'Try adjusting your search term or removing some filters.',
    'error_no_translation_loaded' => 'No translations were found. Check your lang directory.',

    // Actions
    'edit_action' => 'Edit',
    'save_btn' => 'Save',
    'saved_translation' => 'Translation saved',
    'cancel_translation_btn' => 'Cancel',

    // AI translation — row action
    'ai_translate_row_action' => 'AI Fill',
    'ai_translate_row_action_tooltip' => 'Automatically translate all missing locales for this key using AI.',
    'ai_translate_row_success' => 'AI translated :count locale(s).',
    'ai_translate_nothing_missing' => 'All locales already have a translation for this key.',
    'ai_translate_no_source' => 'No source text found to translate from.',

    // AI translation — edit modal
    'ai_fill_modal_action' => 'AI Fill All',
    'ai_fill_modal_success' => 'AI suggestions filled in. Review and save.',

    // AI translation — header action (translate all missing)
    'ai_translate_all_missing_action' => 'Translate All Missing',
    'ai_translate_all_missing_heading' => 'Translate all missing translations with AI',
    'ai_translate_all_missing_description' => 'This will queue AI translation jobs for every missing key across all groups and locales. Jobs run in the background — translations will appear once completed.',
    'ai_translate_all_missing_confirm' => 'Queue jobs',
    'ai_translate_all_missing_queued' => 'Translation queued for :count locale(s).',

    // AI translation — bulk action
    'ai_translate_bulk_action' => 'AI Translate Selected',
    'ai_translate_bulk_queued' => 'Queued AI translation for :count missing value(s) across selected keys.',

    // Dashboard widget
    'widget_stat_description' => ':translated of :total translated, :missing missing',
];
