/**
 * Single-page mode utilities for the docs-archive filter state.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/singlePage
 */

/**
 * Returns true when the current result set fits on a single page.
 *
 * @param {Object} ctx - The Interactivity API context object.
 * @return {boolean} Whether the result set fits on one page.
 */
export function isSinglePage( ctx ) {
	return ( ctx.data?.pagesCount ?? 1 ) === 1;
}

/**
 * Returns true when selecting `value` for `tax` would leave only one page
 * of results — meaning we can enter "single-page mode" without a new fetch.
 *
 * The check compares the count inside the current filtered result
 * (`currentMeta`) against the global unfiltered count (`meta`).
 * If they match and are greater than zero, no other filter combination
 * would add more results, so a network request is unnecessary.
 *
 * @param {Object} ctx   - The Interactivity API context object.
 * @param {string} tax   - Taxonomy key being evaluated.
 * @param {string} value - Taxonomy term value being evaluated.
 * @return {boolean} Whether single-page mode can be entered without a fetch.
 */
export function canEnterSinglePage( ctx, tax, value ) {
	const currentCount = ctx.data?.currentMeta?.[ tax ]?.[ value ] ?? 0;

	const globalCount =
		ctx.data?.meta?.[ tax ]?.find( ( item ) => item.value === value )?.count ?? 0;

	return currentCount > 0 && currentCount === globalCount;
}

/**
 * Activates single-page mode by snapshotting the current data,
 * recording the triggering filter, and marking its position in
 * the selected-filters order array.
 *
 * Snapshot is a deep clone so subsequent mutations don't corrupt it.
 *
 * @param {Object} ctx    - The Interactivity API context object.
 * @param {Object} filter - The filter object `{ tax, value }` that triggered this mode.
 * @return {void}
 */
export function activateSinglePage( ctx, filter ) {
	ctx.singlePage.snapshot     = JSON.parse( JSON.stringify( ctx.data ) );
	ctx.singlePage.active       = true;
	ctx.singlePage.trigger      = filter;
	ctx.singlePage.triggerIndex = ctx.selectedFilters.order.length - 1;
}

/**
 * Deactivates single-page mode and restores data from the snapshot.
 *
 * Use this when the filter that triggered single-page mode is itself
 * removed — we need to roll back to the pre-snapshot state.
 *
 * @param {Object} ctx - The Interactivity API context object.
 * @return {void}
 */
export function deactivateSinglePage( ctx ) {
	if ( ! ctx.singlePage.active ) {
		return;
	}

	ctx.data = JSON.parse( JSON.stringify( ctx.singlePage.snapshot ) );

	clearSinglePageState( ctx );
}

/**
 * Clears single-page flags without restoring data.
 *
 * Use this when a filter *before* the trigger is removed — the trigger
 * is gone too, but we should keep the current data as-is.
 *
 * @param {Object} ctx - The Interactivity API context object.
 * @return {void}
 */
export function clearSinglePageState( ctx ) {
	ctx.singlePage.active       = false;
	ctx.singlePage.trigger      = { tax: null, value: null };
	ctx.singlePage.triggerIndex = -1;
	ctx.singlePage.snapshot     = null;
}

/**
 * Returns true when removing the filter at `index` should restore
 * the pre-single-page snapshot.
 *
 * This is the case when a snapshot exists and the removed filter
 * is exactly the one that triggered single-page mode.
 *
 * @param {Object} ctx   - The Interactivity API context object.
 * @param {number} index - Index of the filter being removed.
 * @return {boolean} Whether the snapshot should be restored.
 */
export function shouldRestoreSnapshot( ctx, index ) {
	return ctx.singlePage.triggerIndex >= 0 && index === ctx.singlePage.triggerIndex;
}

/**
 * Returns true when removing the filter at `index` should invalidate
 * the snapshot entirely.
 *
 * This happens when a filter *before* the trigger is removed — the trigger
 * is no longer reachable so the snapshot is stale and must be discarded.
 *
 * @param {Object} ctx   - The Interactivity API context object.
 * @param {number} index - Index of the filter being removed.
 * @return {boolean} Whether the snapshot should be invalidated.
 */
export function shouldInvalidateSnapshot( ctx, index ) {
	return ctx.singlePage.triggerIndex >= 0 && index < ctx.singlePage.triggerIndex;
}

/**
 * Restores `ctx.data` from the stored snapshot (deep clone).
 *
 * @param {Object} ctx - The Interactivity API context object.
 * @return {void}
 */
export function restoreSnapshot( ctx ) {
	ctx.data = JSON.parse( JSON.stringify( ctx.singlePage.snapshot ) );
}

/**
 * Clears all snapshot-related fields without touching `ctx.data`.
 *
 * @param {Object} ctx - The Interactivity API context object.
 * @return {void}
 */
export function resetSinglePageCache( ctx ) {
	ctx.singlePage.snapshot     = null;
	ctx.singlePage.trigger      = { tax: null, value: null };
	ctx.singlePage.triggerIndex = -1;
}

/**
 * Captures the current data as a snapshot and records which filter
 * (the last one added) caused the transition into single-page mode.
 *
 * @param {Object} ctx - The Interactivity API context object.
 * @return {void}
 */
export function captureSinglePageSnapshot( ctx ) {
	ctx.singlePage.snapshot     = JSON.parse( JSON.stringify( ctx.data ) );
	ctx.singlePage.trigger      = ctx.selectedFilters.order.at( -1 );
	ctx.singlePage.triggerIndex = ctx.selectedFilters.order.length - 1;
}
