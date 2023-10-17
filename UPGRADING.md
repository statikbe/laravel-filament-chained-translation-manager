# Upgrading

Because there are sometimes breaking changes, an upgrade may not always be easy. There might be edge cases that this guide does not cover. Feel free to help improve this guide.

## From 3.0 to 3.1

You can perform the upgrade by renaming the configuration variables (located in config/filament-translation-manager.php):

- Change `supported_locales` to `locales`.
- Move `access.gate` to `gate`.

There have also been some translations that have been renamed:

- Change `navigation-group` to `navigation_group`.
