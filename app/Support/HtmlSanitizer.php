<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist sanitiser for untrusted employer job HTML (design §8).
 *
 * Keeps only a small set of formatting tags, strips every attribute except a
 * safe href on <a> (http/https/mailto/relative only, always with
 * rel="noopener noreferrer nofollow"), and removes <script>/<style> with their
 * contents. Everything else is unwrapped to its text. Render the result with
 * {!! !!} — never render raw employer HTML.
 */
class HtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED = ['p', 'br', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'h2', 'h3', 'h4', 'a'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // Wrap so we can grab a known root; the XML hint keeps UTF-8 intact.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"?><div id="nw-sanitize-root">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('nw-sanitize-root');
        if (! $root instanceof DOMElement) {
            return '';
        }

        self::sanitizeNode($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue; // text node — left as-is (escaped on output)
            }

            $tag = strtolower($child->nodeName);

            // Drop these entirely, including their contents.
            if ($tag === 'script' || $tag === 'style') {
                $node->removeChild($child);

                continue;
            }

            // Sanitise the subtree first, then decide on this element.
            self::sanitizeNode($child);

            if (in_array($tag, self::ALLOWED, true)) {
                self::stripAttributes($child, $tag);

                continue;
            }

            // Not allowed: unwrap — promote its (already sanitised) children, drop the tag.
            while ($child->firstChild !== null) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
        }
    }

    private static function stripAttributes(DOMElement $el, string $tag): void
    {
        $href = null;
        if ($tag === 'a' && $el->hasAttribute('href')) {
            $candidate = trim($el->getAttribute('href'));
            // Allow only safe schemes / relative / anchor. Rejects javascript:, data:, etc.
            if ($candidate !== '' && preg_match('#^(https?:|mailto:|/|\#)#i', $candidate) === 1) {
                $href = $candidate;
            }
        }

        foreach (iterator_to_array($el->attributes) as $attr) {
            $el->removeAttribute($attr->nodeName);
        }

        if ($tag === 'a') {
            if ($href !== null) {
                $el->setAttribute('href', $href);
            }
            $el->setAttribute('rel', 'noopener noreferrer nofollow');
        }
    }
}
