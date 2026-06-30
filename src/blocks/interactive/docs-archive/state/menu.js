/**
 * Shared action helpers for filter interactions.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/menu
 */

/**
 * Finalizes a filter action by recording the request source
 * and closing any open menus for the given taxonomy.
 *
 * @param {Object} ctx     The Interactivity API context object.
 * @param {string} tax     Taxonomy key whose menus should be closed.
 * @param {string} source  The source identifier to record on `ctx.request`.
 * @param {Object} actions The Interactivity API actions object.
 *
 * @return {void}
 */
export function finish( ctx, tax, source, actions ) {
	ctx.request.source = source;

	actions.closeMenu( 'click', tax );
	actions.closeMenu( 'focus', tax );
}

/**
 * Closes both click- and focus-triggered menus for a taxonomy.
 *
 * @param {Object} actions The Interactivity API actions object.
 * @param {string} tax     Taxonomy key whose menus should be closed.
 *
 * @return {void}
 */
export function closeMenus( actions, tax ) {
	actions.closeMenu( 'click', tax );
	actions.closeMenu( 'focus', tax );
}
