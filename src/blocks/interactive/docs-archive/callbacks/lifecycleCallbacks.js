/**
 * Lifecycle callbacks for the Interactivity API store.
 *
 * Handles initialization and state change reactions.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/callbacks/lifecycle
 */

import { getContext } from '@wordpress/interactivity';
import { createRequest } from '../request/request';
import { getCacheKey, persistCache } from '../cache/cache';

export const lifecycleCallbacks = {
	/**
	 * Called when the component mounts or initial state is loaded.
	 *
	 * Persists the initial server-rendered data into the cache.
	 */
	loadState() {
		const ctx = getContext();

		// Generate a cache key from the initial query/filter state.
		const request  = createRequest( ctx );
		const cacheKey = getCacheKey( request );

		// Store the initial data snapshot.
		persistCache( ctx, cacheKey );
	},

	/**
	 * Called when state or context changes.
	 *
	 * Placeholder for future side effects or analytics.
	 *
	 * @generator
	 * @yields {Promise} Future async operations.
	 */
	*changeState() {
		// Runs when state/context changes.
		// Add any reactive side effects here (e.g. analytics, scroll position).
	},
};
