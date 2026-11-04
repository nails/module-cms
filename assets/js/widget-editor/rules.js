/**
 * Area rules for the WidgetEditor, matching Catalogue::permits()
 *
 * An entry is a slug, an alias, or `group:<label>`. `allowed` is applied first. An empty `allowed`
 * list matches nothing. A widget with no groups of its own is in `Generic`.
 *
 * @param {{slug: string, aliases?: string[], groups?: string[]}} widget
 * @param {{allowed?: string[], disallowed?: string[]}} [rules]
 * @return {boolean}
 */
export function permits(widget, rules = {}) {

    const identities = [widget.slug, ...(widget.aliases || [])];
    const groups = widget.groups && widget.groups.length ? widget.groups : ['Generic'];

    if (Array.isArray(rules.allowed) && !matches(rules.allowed, identities, groups)) {
        return false;
    }

    if (Array.isArray(rules.disallowed) && matches(rules.disallowed, identities, groups)) {
        return false;
    }

    return true;
}

/**
 * @param {string[]} entries
 * @param {string[]} identities
 * @param {string[]} groups
 * @return {boolean}
 */
function matches(entries, identities, groups) {

    return entries.some((entry) => {
        if (typeof entry !== 'string' || entry === '') {
            return false;
        }
        if (entry.startsWith('group:')) {
            return groups.includes(entry.slice(6));
        }
        return identities.includes(entry);
    });
}

/**
 * Widgets the organiser should offer for one area
 *
 * @param {Object} catalogue
 * @param {{allowed?: string[], disallowed?: string[]}} rules
 * @param {string} [term]
 * @return {Array<{label: string, slugs: string[]}>}
 */
export function organiserGroups(catalogue, rules, term = '') {

    const needle = term.trim().toLowerCase();
    const groups = [];

    const favourites = (catalogue.favourites || []).filter((slug) => offered(catalogue, slug, rules, needle));
    if (favourites.length) {
        groups.push({ label: 'Favourites', slugs: favourites });
    }

    (catalogue.groups || []).forEach((group) => {
        const slugs = group.slugs.filter((slug) => offered(catalogue, slug, rules, needle));
        if (slugs.length) {
            groups.push({ label: group.label, slugs });
        }
    });

    return groups;
}

/**
 * @param {Object} catalogue
 * @param {string} slug
 * @param {Object} rules
 * @param {string} needle
 * @return {boolean}
 */
function offered(catalogue, slug, rules, needle) {

    const widget = catalogue.widgets[slug];
    if (!widget || widget.status === 'hidden' || widget.status === 'deprecated' || !permits(widget, rules)) {
        return false;
    }
    if (needle === '') {
        return true;
    }

    const haystack = [widget.label, widget.summary, widget.slug, ...(widget.keywords || [])]
        .join(' ')
        .toLowerCase();

    return haystack.includes(needle);
}
