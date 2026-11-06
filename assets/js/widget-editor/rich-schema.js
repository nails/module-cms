import StarterKit from '@tiptap/starter-kit';

/** The heading levels a rich text field can apply. The basic editor has none of them. */
export const RICH_HEADINGS = [1, 2, 3, 4, 5, 6];

/**
 * The rich text schema: paragraphs, bold, italic, links, lists, headings, blockquotes and code blocks.
 * `basic` is the CKEditor wysiwyg-basic preset: paragraphs, bold, italic and links only.
 * Anything else in the stored HTML is dropped when it loads, and the cleaned HTML is only written
 * back once the user edits.
 */
export function richExtensions(basic = false) {
    const options = {
        heading: { levels: RICH_HEADINGS },
        // Inline code is not in the menu. Code blocks (fences) are.
        code: false,
        horizontalRule: false,
        strike: false,
        underline: false,
        link: { openOnClick: false, autolink: false },
    };

    if (basic) {
        options.heading = false;
        options.blockquote = false;
        options.codeBlock = false;
        options.bulletList = false;
        options.orderedList = false;
        options.listItem = false;
        options.listKeymap = false;
    }

    return [StarterKit.configure(options)];
}

/** True when the HTML has no text, so an untouched empty document is stored as an empty string */
export function isBlankHtml(html) {
    return String(html)
        .replace(/<[^>]*>/g, '')
        .replace(/&nbsp;/gi, '')
        .trim() === '';
}
