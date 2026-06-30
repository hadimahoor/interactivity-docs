/**
 * Sort strategy resolver.
 * Determines whether sorting should happen client-side or server-side.
 * @module interactivity-docs/blocks/interactive/docs-archive/strategies/sortStrategy
 */

import { isSinglePage } from '../state/singlePage';
import { clientStrategy } from '../strategies/clientStrategy';
import { serverStrategy } from '../strategies/serverStrategy';

/**
 * Resolves the appropriate strategy for sorting operations.
 * Uses client-side sorting in single-page mode, otherwise fetches
 * sorted results from the server.
 * @param {Object} ctx The context object.
 *
 * @return {Object} The resolved strategy (client or server).
 */
export function resolveSortStrategy( ctx ) {
	if ( isSinglePage( ctx ) ){
		// All items are already loaded, sort locally.
		return clientStrategy( null );
	}

	// Multiple pages exist, fetch sorted results from server.
	return serverStrategy();
}
