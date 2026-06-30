/**
 * Per-filter selector/chip visibility state for the docs-archive filter UI.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/selector
 */

import { getContext } from '@wordpress/interactivity';

/**
 * State slice for per-filter selector/chip visibility logic.
 */
export const selectorState = {
	/**
	 * A dynamic object of getters, two per meta key:
	 *
	 * - `{key}SelectorHide` — hide the selector when a filter is already
	 *   active for this key, or when there are fewer than 2 options.
	 * - `{key}FilterHide`   — hide the active-filter chip when no filter
	 *   is selected for this key.
	 *
	 * @return {Object} Map of visibility getters keyed by meta field name.
	 */
	get selector() {
		const ctx     = getContext();
		const getters = {};

		Object.keys( ctx.data.meta ).forEach( ( property ) => {
			Object.defineProperty( getters, `${ property }SelectorHide`, {
				get() {
					return (
						ctx.selectedFilters.map[ property ] ||
						ctx.data.meta[ property ].length < 2
					);
				},
			} );

			Object.defineProperty( getters, `${ property }FilterHide`, {
				get() {
					return ctx.selectedFilters.map[ property ];
				},
			} );
		} );

		return getters;
	},
};
