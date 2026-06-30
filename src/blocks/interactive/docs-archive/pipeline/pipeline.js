/**
 * Main filter pipeline orchestrator.
 *
 * Combines state mutation, caching, and strategy execution
 * into a single generator consumed by the filter actions.
 *
 * @example
 * yield* runFilterPipeline( ctx, payload, mutationFn, finishFn );
 *
 * @module interactivity-docs/pipeline/runFilterPipeline
 */

import { startLoading, stopLoading } from '../state/loading';
import { createRequest } from '../request/request';
import { getCacheKey, restoreFromCache, persistCache } from '../cache/cache';
import { resolveStrategy, executeStrategy } from '../strategies/strategies';

/**
 * Runs the full filter pipeline for a given context and payload.
 *
 * @param {Object}   ctx          The Interactivity context object.
 * @param {Object}   payload      Filter payload containing tax/value.
 * @param {Function} [mutationFn] Optional state mutation (e.g. applySelection, removeFiltersFromState).
 * @param {Function} [finishFn]   Optional callback run after success. Receives the source ( 'api' | 'cache' ).
 *
 * @generator
 * @yield {Promise} Async operations from the chosen strategy.
 */
export function* runFilterPipeline( ctx, payload, mutationFn = null, finishFn = null ) {
	startLoading( ctx );

	try {
		// 1. Apply state mutation (if provided).
		if ( typeof mutationFn === 'function' ) {
			mutationFn( ctx, payload );
		}

		// 2. Build request and check cache.
		const request  = createRequest( ctx );
		const cacheKey = getCacheKey( request );

		if ( restoreFromCache( ctx, cacheKey ) ) {
			if ( finishFn ) {
				finishFn( 'cache' );
			}
			return;
		}

		// 3. Execute the appropriate strategy (REST API or client-only).
		const strategy = resolveStrategy( ctx, payload );
		yield* executeStrategy( ctx, strategy );

		// 4. Persist the fresh response to cache.
		persistCache( ctx, cacheKey );

		// 5. Execute post-processing callback.
		if ( finishFn ) {
			finishFn( strategy.source );
		}
	} catch ( error ) {
		console.error( 'Filter pipeline error:', error );
	} finally {
		stopLoading( ctx );
	}
}
