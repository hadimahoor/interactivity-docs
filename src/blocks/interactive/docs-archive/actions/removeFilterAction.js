/**
 * Filter removal action handlers.
 *
 * Handles removing a filter from the active selection and re-running the
 * pipeline. Manages single-page snapshot restoration and cache invalidation.
 *
 * @module interactivity-docs/actions/removeFilter
 */

import { getContext } from '@wordpress/interactivity';
import { extractPayload } from './core';
import { runFilterPipeline } from '../pipeline/pipeline';
import { setRequestSource } from '../request/request';
import {
	resetSinglePageCache,
	shouldRestoreSnapshot,
	restoreSnapshot,
	shouldInvalidateSnapshot,
} from '../state/singlePage';

/**
 * Removes filters from context state starting at a given index.
 *
 * Helper for cleaning up `selectedFilters` and `query.filters`.
 *
 * @param {Object} ctx        - The context object.
 * @param {number} startIndex - The index from which to remove filters.
 */
function removeFiltersFromState( ctx, startIndex ) {
	const termsToRemove = ctx.selectedFilters.order.slice( startIndex );

	for ( const term of termsToRemove ) {
		delete ctx.selectedFilters.map[ term.tax ];
		delete ctx.query.filters[ term.tax ];
	}

	ctx.selectedFilters.order.length = startIndex;
}

export const removeFilterAction = {
	/**
	 * Removes a filter from the active selection.
	 *
	 * If removing this filter should restore a snapshot (single-page
	 * optimization), restore it immediately without a network request.
	 * Otherwise, re-run the filter pipeline.
	 *
	 * @generator
	 * @param {Event} e - The event that triggered the removal.
	 * @yields {Promise} Async operations in the filter pipeline.
	 */
	*removeFilter( e ) {
		const ctx = getContext();
		const payload = extractPayload( e );

		if ( ! payload ) {
			return;
		}

		// Find the index of the filter to remove.
		const index = ctx.selectedFilters.order.findIndex(
			( item ) =>
				item.tax === payload.tax && item.value === payload.value
		);

		if ( index === -1 ) {
			return;
		}

		// Always reset to page 1 when removing a filter.
		ctx.query.page = 1;

		// --- Snapshot restoration path (single-page optimization) ---
		// Restore a cached snapshot when no network request is needed.
		if ( shouldRestoreSnapshot( ctx, index ) ) {
			removeFiltersFromState( ctx, index );
			restoreSnapshot( ctx );
			resetSinglePageCache( ctx );
			return;
		}

		// --- Snapshot invalidation path ---
		// If the removal affects a filter earlier in the chain, invalidate
		// the snapshot so it is not incorrectly restored later.
		if ( shouldInvalidateSnapshot( ctx, index ) ) {
			resetSinglePageCache( ctx );
		}

		// --- Normal pipeline path ---
		// Remove the filter and re-fetch results.
		yield* runFilterPipeline(
			ctx,
			payload,
			() => removeFiltersFromState( ctx, index ),
			( source ) => setRequestSource( ctx, source )
		);
	},
};
