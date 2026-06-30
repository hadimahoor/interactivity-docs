/**
 * Context state initializer.
 * Ensures all required context properties exist with their default values.
 * @module interactivity-docs/blocks/interactive/docs-archive/state/context
 */

/**
 * Ensures all required context properties exist with their default values.
 *
 * Call this at the top of any action or callback that reads from `ctx`
 * to avoid null-reference errors on first render or after a context reset.
 *
 * @param {Object} ctx The Interactivity API context object.
 *
 * @return {void}
 */
export function ensureState( ctx ) {
	if ( ! ctx.query ) {
		ctx.query = {};
	}

	if ( ! ctx.query.filters ) {
		ctx.query.filters = {};
	}

	if ( ! ctx.selectedFilters ) {
		ctx.selectedFilters = {};
	}

	if ( ! ctx.selectedFilters.map ) {
		ctx.selectedFilters.map = {};
	}

	if ( ! ctx.selectedFilters.order ) {
		ctx.selectedFilters.order = [];
	}

	if ( ! ctx.request ) {
		ctx.request = {};
	}

	if ( ! ctx.ui ) {
		ctx.ui = {};
	}

	if ( ! ctx.singlePage ) {
		ctx.singlePage = {
			active: false,
			snapshot: null,
			trigger: null,
			triggerIndex: -1,
		};
	}
}
