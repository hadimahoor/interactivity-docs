/**
 * Filter selection action handlers.
 *
 * Handles selecting taxonomy terms and sort options, and triggers the
 * filter pipeline.
 *
 * @module interactivity-docs/actions/selectFilter
 */

import { getContext } from '@wordpress/interactivity';
import { ensureState } from '../state/context';
import { resetPagination } from '../pagination/pagination';
import { runFilterPipeline } from '../pipeline/pipeline';
import { setRequestSource } from '../request/request';
import { extractPayload } from './core';
import { closeMenus } from '../state/menu';

/**
 * Checks if a filter is already selected.
 *
 * @param {Object} ctx           - The context object.
 * @param {Object} payload       - The filter payload.
 * @param {string} payload.tax   - The taxonomy key (or 'sort').
 * @param {string} payload.value - The value to check.
 * @return {boolean} True if already selected.
 */
export function isAlreadySelected( ctx, { tax, value } ) {
	if ( tax === 'sort' ) {
		return ctx.query.sort === value;
	}

	return ctx.selectedFilters?.map?.[ tax ] === value;
}

/**
 * Applies a filter selection to the context state.
 *
 * For sort filters, updates `ctx.query.sort`. For taxonomy filters, updates
 * `selectedFilters.map`, `selectedFilters.order`, and `query.filters`.
 * Always resets pagination to page 1.
 *
 * @param {Object} ctx           - The context object.
 * @param {Object} payload       - The filter payload.
 * @param {string} payload.tax   - The taxonomy key (or 'sort').
 * @param {string} payload.value - The selected value.
 */
export function applySelection( ctx, { tax, value } ) {
	ensureState( ctx );

	// Handle sort separately (no multi-select, no order tracking).
	if ( tax === 'sort' ) {
		ctx.query.sort = value;
		resetPagination( ctx );
		return;
	}

	// Update the filter map.
	ctx.selectedFilters.map[ tax ] = value;

	// Update or append to the filter order.
	const index = ctx.selectedFilters.order.findIndex(
		( item ) => item.tax === tax
	);

	if ( index === -1 ) {
		// New filter, add to the end.
		ctx.selectedFilters.order.push( { tax, value } );
	} else {
		// Existing filter, update in place.
		ctx.selectedFilters.order[ index ] = { tax, value };
	}

	// Sync query filters (API expects an array).
	ctx.query.filters[ tax ] = [ value ];

	// Reset to page 1 whenever a filter changes.
	resetPagination( ctx );
}

export const selectFilterAction = ( actions ) => ( {
	/**
	 * Selects a filter (taxonomy term or sort option) and re-runs the
	 * filter pipeline.
	 *
	 * Ignores clicks on already-selected filters and closes the menu
	 * after a successful selection.
	 *
	 * @generator
	 * @param {Event} e - The event that triggered the selection.
	 * @yields {Promise} Async operations in the filter pipeline.
	 */
	*selectFilter( e ) {
		const ctx = getContext();
		const payload = extractPayload( e );

		// Bail if no payload or the filter is already selected.
		if ( ! payload || isAlreadySelected( ctx, payload ) ) {
			return;
		}

		// Always reset to page 1 when changing filters.
		ctx.query.page = 1;

		// Run the filter pipeline with the new selection.
		yield* runFilterPipeline(
			ctx,
			payload,
			applySelection,
			( source ) => {
				setRequestSource( ctx, source );
				closeMenus( actions, payload.tax );
			}
		);
	},
} );
