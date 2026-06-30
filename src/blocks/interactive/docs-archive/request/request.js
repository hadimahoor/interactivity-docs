/**
 * Request creation utilities.
 *
 * Builds request objects from context and manages request source tracking.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/request/request
 */

/**
 * Creates a request object from the current context state.
 *
 * Removes empty/null fields to keep the payload clean.
 *
 * @param {Object} ctx - The context object.
 * @return {Object} A clean request object.
 */
export function createRequest( ctx ) {
	return removeEmptyFields( {
		type:       ctx.query.type,
		page:       ctx.query.page,
		perPage:    ctx.query.perPage,
		sort:       ctx.query.sort,
		search:     ctx.query.search,
		only_items: '0',
		only_meta:  [],
		...ctx.query.filters,
	} );
}

/**
 * Sets the request source in the context.
 *
 * Used to track where a particular request came from (e.g. 'click', 'cache').
 *
 * @param {Object} ctx    - The context object.
 * @param {string} source - The source identifier.
 */
export function setRequestSource( ctx, source ) {
	if ( ! ctx.request ) {
		ctx.request = {};
	}

	ctx.request.source = source;
}

/**
 * Removes empty, null, undefined, and empty-array fields from an object.
 *
 * Can be replaced with a project-level utility if one exists.
 *
 * @param {Object} obj - The object to clean.
 * @return {Object} A new object with empty fields removed.
 */
function removeEmptyFields( obj ) {
	return Object.fromEntries(
		Object.entries( obj ).filter( ( [ , value ] ) => {
			if ( value == null ) {
				return false;
			}

			if ( value === '' ) {
				return false;
			}

			if ( Array.isArray( value ) && value.length === 0 ) {
				return false;
			}

			return true;
		} ),
	);
}
