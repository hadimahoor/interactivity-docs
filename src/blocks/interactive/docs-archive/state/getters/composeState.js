/**
 * State composition helper for the docs-archive filter UI.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/compose
 */

/**
 * Composes multiple state slices into a single state object.
 *
 * Uses `Object.defineProperties` + `Object.getOwnPropertyDescriptors` to
 * preserve getter/setter descriptors from each slice — a plain
 * `Object.assign` would invoke the getters eagerly and lose reactivity.
 *
 * @param {...Object} stateParts - State slice objects (from uiState, sortState, …).
 * @return {Object} A single flat object with all descriptors merged.
 *
 * @example
 * const state = composeState( uiState, sortState, menuState );
 */
export function composeState( ...stateParts ) {
	const state = {};

	stateParts.forEach( ( part ) => {
		Object.defineProperties(
			state,
			Object.getOwnPropertyDescriptors( part ),
		);
	} );

	return state;
}
