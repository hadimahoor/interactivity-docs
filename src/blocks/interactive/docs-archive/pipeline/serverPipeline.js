/**
 * Server-side REST API pipeline.
 *
 * Builds a WordPress REST request, executes it, and normalizes the response
 * into the shape expected by the docs archive store.
 *
 * @module interactivity-docs/pipeline/runServerPipeline
 */


/**
 * Builds a WordPress REST API URL from the request and context.
 *
 * Appends all query parameters, including taxonomy filters.
 *
 * @param {Object} request The request object.
 * @param {Object} ctx     The Interactivity context object.
 *
 * @return {Object} Augmented request with the final `url`.
 */
const buildRequestStage = ( request, ctx ) => {
	const params = new URLSearchParams();

	params.set( 'post_type', request.type );
	params.set( 'page', request.page );
	params.set( 'per_page', request.perPage );
	params.set( 'sort', request.sort || 'latest' );
	params.set( 'only_items', request.only_items ?? '0' );

	if ( request.only_meta?.length ) {
		params.set( 'only_meta', request.only_meta.join( ',' ) );
	}

	// Append taxonomy filters.
	for ( const key in ctx.query.filters ) {
		const values = ctx.query.filters[ key ];

		if ( ! values?.length ) {
			continue;
		}

		// Special case: 'year' maps to 'filter_year' on the API.
		const apiKey = key === 'year' ? 'filter_year' : key;
		params.set( apiKey, values.join( ',' ) );
	}

	return {
		...request,
		url: `/wp-json/${ ctx.endpoint }?${ params.toString() }`,
	};
};

/**
 * Fetches data from the WordPress REST endpoint.
 *
 * @param {Object} request The request object with a `url` property.
 *
 * @return {Promise<Object>} Request augmented with the parsed JSON response.
 */
const fetchStage = async ( request ) => {
	const response = await fetch( request.url );

	if ( ! response.ok ) {
		throw new Error( 'Docs API request failed' );
	}

	const json = await response.json();

	return {
		...request,
		response: json,
	};
};

/**
 * Normalizes the raw API response into a consistent shape.
 *
 * @param {Object} request The request object with a `response` property.
 *
 * @return {Object} Normalized data: { items, meta, count, pagination }.
 */
const normalizeStage = ( request ) => {
	const res = request.response;

	return {
		items: ( res.items || [] ).map( ( item ) => ( { data: item } ) ),
		meta: res.meta || {},
		count: res.pagination?.total ?? 0,
		pagination: res.pagination || {},
	};
};

/**
 * Pipeline stages executed sequentially on the server.
 */
const serverPipeline = [ buildRequestStage, fetchStage, normalizeStage ];

/**
 * Executes the server-side pipeline.
 *
 * @param {Object} request The initial request object.
 * @param {Object} ctx     The Interactivity context object.
 *
 * @return {Promise<Object>} The normalized result.
 */
export async function runServerPipeline( request, ctx ) {
	let result = request;

	for ( const stage of serverPipeline ) {
		result = await stage( result, ctx );
	}

	return result;
}
