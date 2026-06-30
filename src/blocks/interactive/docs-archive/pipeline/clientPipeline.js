/**
 * Client-side filtering pipeline.
 *
 * Applies filters, sorting, and rebuilds metadata entirely in the browser.
 * Useful for instant feedback before the server request completes.
 *
 * @module interactivity-docs/pipeline/runClientPipeline
 */

/**
 * Filters items by a specific taxonomy filter.
 *
 * @param {Array}  items         Array of item objects.
 * @param {Object} ctx           The Interactivity context object.
 * @param {Object} filters       The filter to apply.
 * @param {string} filters.tax   Taxonomy key.
 * @param {string} filters.value Value to match.
 *
 * @return {Array} Filtered items.
 */
const filterStage = ( items, ctx, filters ) => {
	if ( ! filters ) {
		return items;
	}

	return items.filter(
		( item ) => item.data[ filters.tax ]?.name === filters.value
	);
};

/**
 * Sorts items by a field specified in the query.
 *
 * Works with any numeric field (e.g. paper_count, book_count).
 *
 * @param {Array}  items Array of item objects.
 * @param {Object} ctx   The Interactivity context object.
 *
 * @return {Array} Sorted items.
 */
const sortStage = ( items, ctx ) => {
	if ( ! ctx.query.sort ) {
		return items;
	}

	return [ ...items ].sort( ( a, b ) => {
		if ( a[ ctx.query.sort ] < b[ ctx.query.sort ] ) {
			return -1;
		}

		if ( a[ ctx.query.sort ] > b[ ctx.query.sort ] ) {
			return 1;
		}

		return 0;
	} );
};

/**
 * Rebuilds the metadata (counts) based on the current filtered items.
 *
 * Updates `ctx.data.meta` and `ctx.data.count` to reflect the current state.
 *
 * @param {Array}  items Array of item objects.
 * @param {Object} ctx   The Interactivity context object.
 *
 * @return {Array} Input items unchanged.
 */
const rebuildMetaStage = ( items, ctx ) => {
	// Shallow copy of meta to avoid mutating the original.
	const newMeta = { ...ctx.data.meta };

	for ( const key in newMeta ) {
		if ( ! newMeta[ key ]?.length ) {
			continue;
		}

		const nameCounts = {};

		items.forEach( ( item ) => {
			const name = item.data[ key ].name;
			nameCounts[ name ] = ( nameCounts[ name ] || 0 ) + 1;
		} );

		newMeta[ key ] = Object.keys( nameCounts ).map( ( name ) => ( {
			name,
			count: nameCounts[ name ],
		} ) );
	}

	ctx.data.meta = newMeta;
	ctx.data.count = items.length;

	return items;
};

/**
 * Pipeline stages executed in sequence on the client.
 */
const clientPipeline = [ filterStage, sortStage, rebuildMetaStage ];

/**
 * Executes the client-side pipeline on a set of items.
 *
 * @param {Array}  items   Items to process.
 * @param {Object} ctx     The Interactivity context object.
 * @param {Object} filters Optional filter to apply.
 *
 * @return {Array} Processed items.
 */
export function runClientPipeline( items, ctx, filters ) {
	return clientPipeline.reduce(
		( result, stage ) => stage( result, ctx, filters ),
		items
	);
}
