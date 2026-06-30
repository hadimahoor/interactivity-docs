/**
 * Pagination utilities. Handles page reset and pagination UI generation.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/pagination/pagination
 */

/**
 * Resets pagination to page 1. Called whenever filters, sort, or other query
 * parameters change.
 *
 * @param {Object} ctx The Interactivity API context object.
 *
 * @return {void}
 */
export function resetPagination( ctx ) {
	ctx.query.page = 1;

	if ( ctx.data?.pagination ) {
		ctx.data.pagination.current = 1;
	}
}

/**
 * Generates pagination items for rendering. Creates an array of page numbers
 * and ellipsis placeholders.
 *
 * Example output for current = 5, max = 10:
 * [ 1, "…", 3, 4, 5, 6, 7, "…", 10 ]
 *
 * @param {Object} params         Pagination parameters.
 * @param {number} params.current Current page number.
 * @param {number} params.max     Maximum number of pages.
 *
 * @return {Object|null} Pagination object with `current`, `prev`, `next`, and
 *                       `items`, or `null` when input is invalid.
 */
export function paginate( { current, max } ) {
	if ( ! current || ! max ) {
		return null;
	}

	const prev = current === 1 ? null : current - 1;
	const next = current === max ? null : current + 1;

	const items = [
		{
			item: 1,
			isCurrent: current === 1,
		},
	];

	// Handle single-page case.
	if ( current === 1 && max === 1 ) {
		return { current, prev, next, items };
	}

	// Add leading ellipsis if the current page is far from the start.
	if ( current > 4 ) {
		items.push( { item: '…', isCurrent: false } );
	}

	// Show 2 pages on each side of the current page.
	const range = 2;
	const rangeStart = Math.max( 2, current - range );
	const rangeEnd = Math.min( max, current + range );

	for ( let i = rangeStart; i <= rangeEnd; i++ ) {
		items.push( { item: i, isCurrent: i === current } );
	}

	// Add trailing ellipsis if the current page is far from the end.
	if ( rangeEnd + 1 < max ) {
		items.push( { item: '…', isCurrent: false } );
	}

	// Always show the last page.
	if ( rangeEnd < max ) {
		items.push( { item: max, isCurrent: max === current } );
	}

	return { current, prev, next, items };
}

