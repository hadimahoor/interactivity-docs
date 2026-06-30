/**
 * Server-side REST API strategy.
 *
 * Fetches fresh data from the WordPress REST endpoint and handles
 * single-page mode transitions ( capture/reset snapshot ).
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/strategies/serverStrategy
 */

import { createRequest } from '../request/request';
import {
	isSinglePage,
	captureSinglePageSnapshot,
	resetSinglePageCache,
} from '../state/singlePage';
import { runServerPipeline } from '../pipeline/serverPipeline';
import { paginate } from '../pagination/pagination';

/**
 * Creates a server-side REST API strategy descriptor.
 *
 * @return {Object} Strategy configuration object.
 */
export function serverStrategy() {
	return {
		type: 'server',
		source: 'server',
	};
}

/**
 * Executes the server-side REST API strategy.
 *
 * Manages single-page mode transitions and updates context with fresh data.
 *
 * @param {Object} ctx The context object.
 *
 * @return {Promise<void>}
 */
export async function runServerStrategy( ctx ) {
	// Build the API request from current context.
	const request = createRequest( ctx );

	// Capture single-page state before fetching.
	const wasSingle = isSinglePage( ctx );

	// Fetch data from the server.
	const result = await runServerPipeline( request, ctx );

	// Extract new pagination state from response.
	const nextPagesCount = result.pagination?.pages ?? 1;
	const willBeSingle = nextPagesCount === 1;

	/*
	 * 1. Transition: Multi -> Single ( capture snapshot ).
	 * If we're entering single-page mode, save a snapshot of the
	 * current state for potential restoration.
	 */
	if ( ! wasSingle && willBeSingle ) {
		captureSinglePageSnapshot( ctx );
	}

	/*
	 * 2. Update data ( source of truth ).
	 * Apply the fresh data from the server response.
	 */
	ctx.data.items = result.items;
	ctx.data.meta = result.meta;
	ctx.data.count = result.count;
	ctx.data.pagination = paginate( {
		current: ctx.query.page,
		max: nextPagesCount,
	} );
	ctx.data.pagesCount = nextPagesCount;

	/*
	 * 3. Transition: Single -> Multi ( reset cache ).
	 * If we're leaving single-page mode, clear the snapshot since
	 * it's no longer needed.
	 */
	if ( wasSingle && ! willBeSingle ) {
		resetSinglePageCache( ctx );
	}
}
