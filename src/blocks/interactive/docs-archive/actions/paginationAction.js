/**
 * Pagination action handlers.
 *
 * Handles page changes and triggers the filter pipeline to fetch new data.
 *
 * @module interactivity-docs/actions/pagination
 */

import { getContext, getElement } from '@wordpress/interactivity';
import { extractPayload } from './core';
import { runFilterPipeline } from '../pipeline/pipeline';
import { setRequestSource } from '../request/request';

export const paginationAction = {
	/**
	 * Changes the current page and re-runs the filter pipeline.
	 *
	 * Extracts the page number from the clicked element's `data-id`
	 * attribute, updates the query, then fetches paginated results.
	 *
	 * @generator
	 * @yields {Promise} Async operations in the filter pipeline.
	 */
	*changePage() {
		const ctx = getContext();
		const { ref } = getElement();

		// Extract the page number from the element.
		const payload = extractPayload( ref );
		if ( ! payload ) {
			return;
		}

		// Update the query with the new page.
		ctx.query.page = payload.page;

		// Run the filter pipeline to fetch paginated results.
		yield* runFilterPipeline( ctx, payload, null, ( source ) =>
			setRequestSource( ctx, source )
		);
	},
};
