/**
 * Single-page entry strategy.
 * Activates single-page mode and applies client-side filtering.
 * Used when a filter narrows results to a single page without
 * requiring an API call.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/strategies/enterSinglePageStrategy
 */

import { activateSinglePage } from '../state/singlePage';
import { runClientStrategy } from '../strategies/clientStrategy';

/**
 * Creates an enter-single-page strategy descriptor.
 *
 * @param {Object} filter       The filter that triggered single-page mode.
 * @param {string} filter.tax   Taxonomy key.
 * @param {string} filter.value Taxonomy value.
 *
 * @return {Object} Strategy configuration object.
 */
export function enterSinglePageStrategy( filter ) {
	return {
		type: 'enterSingle',
		source: 'client',
		filter,
	};
}

/**
 * Executes the enter-single-page strategy.
 *
 * Activates single-page mode, then runs client-side filtering on
 * the existing dataset.
 *
 * @param {Object} ctx      The context object.
 * @param {Object} strategy The strategy configuration.
 *
 * @return {void}
 */
export function runEnterSinglePageStrategy( ctx, strategy ) {
	// Mark single-page mode as active and store the trigger filter.
	activateSinglePage( ctx, strategy.filter );

	// Apply client-side filtering on the existing dataset.
	runClientStrategy( ctx, strategy );
}
