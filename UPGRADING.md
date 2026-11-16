# Upgrading

Because there are sometimes breaking changes, an upgrade may not always be easy. There might be edge cases that this guide does not cover. Feel free to help improve this guide.

## From 4.4 to 4.5

- The package now requires `statikbe/laravel-chained-translator` `^3.0` and PHP 8.2 or higher. Check the
  [upgrade notes of the Laravel Chained Translator](https://github.com/statikbe/laravel-chained-translator/blob/3.0.0/CHANGELOG.md).
- Laravel's own translation groups (`auth`, `pagination`, `passwords` and `validation`) are now listed in the
  translation manager, even when your `lang` directory has no files for them. Add them to `ignore_groups` if you
  don't want them to be edited.

## From 4.3 to 4.4

- The package now requires `statikbe/laravel-chained-translator` `^2.7`.
- The configured `gate` is now also checked when a translation is saved, not only when the page is opened. Make sure
  that every user who should be able to edit translations passes the gate.
- We strongly recommend configuring a `gate` (see the README). Without one, every user of the panel can edit the
  translations.
- Only the configured locales and the translation groups that are not listed in `ignore_groups` can be shown or saved.

## From 3.0 to 3.1

You can perform the upgrade by renaming the configuration variables (located in config/filament-translation-manager.php):

- Change `supported_locales` to `locales`.
- Move `access.gate` to `gate`.

There have also been some translations that have been renamed:

- Change `navigation-group` to `navigation_group`.
