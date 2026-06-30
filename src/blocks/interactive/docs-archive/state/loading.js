/**
 * Loading state management utilities.
 * @module interactivity-docs/blocks/interactive/docs-archive/state/loading
 */

/**
 * Marks the context as loading.
 *
 * @param {Object} ctx The Interactivity API context object.
 *
 * @return {void}
 */
export function startLoading( ctx ) {
	ctx.ui.isLoading = true;
}

/**
 * Clears the loading flag on the context.
 *
 * @param {Object} ctx The Interactivity API context object.
 *
 * @return {void}
 */
export function stopLoading( ctx ) {
	ctx.ui.isLoading = false;
}
