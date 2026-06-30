/**
 * Client-side filtering strategy.
 *
 * Applies filters entirely in the browser without making API requests.
 * Used when the data is already loaded ( e.g. in single-page mode ).
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/strategies/clientStrategy
 */

import { runClientPipeline } from '../pipeline/clientPipeline';

/**
 * Creates a client-side filtering strategy descriptor.
 *
 * @param {Object|null} filter       The filter to apply.
 * @param {string}      filter.tax   Taxonomy key.
 * @param {string}      filter.value Taxonomy value.
 *
 * @return {Object} Strategy configuration object.
 */
export function clientStrategy( filter ) {
	return {
		type: 'client',
		source: 'client',
		filter,
	};
}

/**
 * Executes the client-side filtering strategy.
 *
 * Runs the client pipeline on a copy of the existing items and writes
 * the processed result back to the context data.
 *
 * @param {Object} ctx      The context object.
 * @param {Object} strategy The strategy configuration.
 *
 * @return {void}
 */
export function runClientStrategy( ctx, strategy ) {
	// Clone items to avoid direct mutation during pipeline execution.
	let items = [ ...ctx.data.items ];

	// Apply client-side filtering, sorting, and metadata rebuild.
	items = runClientPipeline( items, ctx, strategy.filter );

	// Update context with processed items.
	ctx.data.items = items;

    // Set pagesCount to 1.
    ctx.data.pagesCount = 1;
}
