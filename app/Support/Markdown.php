<?php

namespace App\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class Markdown
{
    /**
     * Render user-authored Markdown to sanitised HTML.
     *
     * Raw HTML in the source is escaped and unsafe links are stripped, so the
     * output is safe to render with `{!! !!}`.
     */
    public static function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);

        return (string) $converter->convert($markdown);
    }
}
