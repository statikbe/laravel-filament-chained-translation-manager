<?php

namespace Statikbe\FilamentTranslationManager\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Removes unsafe HTML (scripts, event handlers, dangerous URL schemes, ...) from translations, while keeping the
 * formatting and links that are allowed in the config (`filament-translation-manager.sanitize_html`).
 */
class TranslationHtmlSanitizer
{
    public const DEFAULT_ALLOWED_ELEMENTS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'strong' => [], 'em' => [], 'b' => [], 'i' => [], 'u' => [],
        'p' => [], 'br' => [], 'span' => [],
        'ul' => [], 'ol' => [], 'li' => [],
    ];

    public const DEFAULT_ALLOWED_LINK_SCHEMES = ['https', 'http', 'mailto'];

    private readonly HtmlSanitizer $sanitizer;

    /**
     * @param  array<string, array<int, string>>  $allowedElements  Allowed tags with their allowed attributes.
     * @param  array<int, string>  $allowedLinkSchemes
     */
    public function __construct(
        array $allowedElements = self::DEFAULT_ALLOWED_ELEMENTS,
        array $allowedLinkSchemes = self::DEFAULT_ALLOWED_LINK_SCHEMES,
        bool $allowRelativeLinks = true,
    ) {
        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes($allowedLinkSchemes)
            ->allowRelativeLinks($allowRelativeLinks)
            // The sanitizer truncates input longer than 20 000 bytes by default.
            ->withMaxInputLength(-1);

        foreach ($allowedElements as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        if (array_key_exists('a', $allowedElements)) {
            $config = $config->forceAttribute('a', 'rel', 'noopener noreferrer');
        }

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public static function fromConfig(): self
    {
        return new self(
            config('filament-translation-manager.sanitize_html.allowed_elements', self::DEFAULT_ALLOWED_ELEMENTS),
            config('filament-translation-manager.sanitize_html.allowed_link_schemes', self::DEFAULT_ALLOWED_LINK_SCHEMES),
            (bool) config('filament-translation-manager.sanitize_html.allow_relative_links', true),
        );
    }

    public function sanitize(string $value): string
    {
        // Without a tag there is nothing to remove. Return plain text unchanged, because the sanitizer would encode
        // characters like &, ', " and @ as HTML entities, which breaks translations that are escaped when printed.
        if (! self::containsMarkup($value)) {
            return $value;
        }

        return $this->sanitizer->sanitize($value);
    }

    /**
     * Checks if sanitizing removed something from the value, e.g. an element, an attribute or a link.
     *
     * The sanitizer also changes markup without removing anything: it encodes characters as entities, closes
     * open tags, adds rel="noopener noreferrer" to links and rewrites quotes, whitespace and the case of tags.
     * Those changes are ignored, so only a real removal counts.
     */
    public static function removedContent(string $original, string $sanitized): bool
    {
        return self::normalize($original) !== self::normalize($sanitized);
    }

    private static function normalize(string $value): string
    {
        // Closing tags only end an element, the opening tag and the content show if an element was removed.
        $value = (string) preg_replace('#</[^>]*>#', '', $value);
        $value = mb_strtolower(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return (string) preg_replace_callback('#<[a-z][^>]*>#', static function (array $match): string {
            $tag = $match[0];
            // The sanitizer always sets rel on links.
            $tag = (string) preg_replace('#\s+rel\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#', '', $tag);
            $tag = (string) preg_replace('#\s*=\s*\'([^\']*)\'#', '="$1"', $tag);
            $tag = (string) preg_replace('#\s*=\s*([^\s"\'>]+)#', '="$1"', $tag);
            $tag = (string) preg_replace('#\s*=\s*"#', '="', $tag);
            $tag = (string) preg_replace('#\s*/?\s*>$#', '>', $tag);

            return (string) preg_replace('#\s+#', ' ', $tag);
        }, $value);
    }

    /**
     * A browser only starts a tag, comment or other markup when "<" is followed by a letter, "/", "!" or "?".
     */
    public static function containsMarkup(string $value): bool
    {
        return preg_match('/<[a-zA-Z\/!?]/', $value) === 1;
    }
}
