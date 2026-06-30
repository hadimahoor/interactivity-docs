/**
 * Cache utilities for storing and retrieving data snapshots.
 *
 * Reduces redundant API calls by caching results based on request parameters.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/cache/cache
 */

/**
 * Generates a cache key from a request object.
 *
 * Uses JSON serialization to create a stable, unique key.
 *
 * @param {Object} request - The request object (filters, sort, page, etc.).
 * @return {string} A JSON string representing the request.
 */
export function getCacheKey( request ) {
	return JSON.stringify( request );
}

/**
 * Restores cached data into the context if available.
 *
 * @param {Object} ctx - The context object.
 * @param {string} key - The cache key to look up.
 * @return {boolean} True if cache was restored, false otherwise.
 */
export function restoreFromCache( ctx, key ) {
	if ( ! ctx.cache?.[ key ] ) {
		return false;
	}

	// Deep clone to avoid reference issues.
	ctx.data = JSON.parse( JSON.stringify( ctx.cache[ key ] ) );

	return true;
}

/**
 * Persists the current data into the cache under the given key.
 *
 * @param {Object} ctx - The context object.
 * @param {string} key - The cache key to store under.
 */
export function persistCache( ctx, key ) {
	if ( ! ctx.cache ) {
		ctx.cache = {};
	}

	// Deep clone to avoid reference issues.
	ctx.cache[ key ] = JSON.parse( JSON.stringify( ctx.data ) );
}
