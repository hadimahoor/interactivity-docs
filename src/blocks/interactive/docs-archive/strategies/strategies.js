/**
 * Strategy resolution and execution orchestrator.
 *
 * Routes filter/sort operations to the appropriate strategy
 * (client, server, or single-page entry).
 *
 * @module interactivity-docs/strategies/strategies
 */

import { resolveSortStrategy } from './sortStrategy';
import { clientStrategy, runClientStrategy } from './clientStrategy';
import { serverStrategy, runServerStrategy } from './serverStrategy';
import {
	enterSinglePageStrategy,
	runEnterSinglePageStrategy,
} from './singlePageStrategy';
import { canEnterSinglePage, isSinglePage } from '../state/singlePage';

/**
 * Resolves the appropriate strategy based on the current context and payload.
 *
 * Decision tree:
 * 1. If sorting, delegate to the sort strategy resolver.
 * 2. If already in single-page mode, use the client strategy.
 * 3. If the filter can trigger single-page mode, use the enter-single-page strategy.
 * 4. Otherwise, use the server strategy (fetch from API).
 *
 * @param {Object} ctx           The Interactivity context object.
 * @param {Object} payload       The action payload.
 * @param {string} payload.tax   Taxonomy key (or 'sort' for sorting).
 * @param {string} payload.value Taxonomy value.
 *
 * @return {Object} The resolved strategy configuration.
 */
export function resolveStrategy( ctx, { tax, value } ) {
	// Special case: sorting operation.
	if ( tax === 'sort' ) {
		return resolveSortStrategy( ctx );
	}

	// Already in single-page mode, filter locally.
	if ( isSinglePage( ctx ) ) {
		return clientStrategy( { tax, value } );
	}

	// If this filter resolves to a single page, activate single-page
	// mode without an API call.
	if ( canEnterSinglePage( ctx, tax, value ) ) {
		return enterSinglePageStrategy( { tax, value } );
	}

	// Default: fetch fresh data from the server.
	return serverStrategy();
}

/**
 * Executes the resolved strategy.
 *
 * Delegates to the appropriate runner based on the strategy type.
 *
 * @param {Object} ctx      The Interactivity context object.
 * @param {Object} strategy The strategy configuration.
 *
 * @generator
 * @yields {Promise|void} Async operations for the server strategy,
 *                        nothing for synchronous strategies.
 *
 * @throws {Error} If the strategy type is unknown.
 *
 * @return {void}
 */
export function* executeStrategy( ctx, strategy ) {
	switch ( strategy.type ) {
		case 'client':
			// Synchronous client-side filtering.
			runClientStrategy( ctx, strategy );
			return;

		case 'server':
			// Asynchronous server request (yields a promise).
			yield runServerStrategy( ctx );
			return;

		case 'enterSingle':
			// Activate single-page mode and filter client-side.
			runEnterSinglePageStrategy( ctx, strategy );
			return;

		default:
			throw new Error( `Unknown strategy type: ${ strategy.type }` );
	}
}
