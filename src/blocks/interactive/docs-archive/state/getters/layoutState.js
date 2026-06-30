/**
 * Layout-level visibility state for the docs-archive filter UI.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/layout
 */

import { getContext } from '@wordpress/interactivity';

/**
 * State slice for layout-level visibility logic.
 */
export const layoutState = {
	/**
	 * Whether the separator should be hidden.
	 *
	 * True when every meta key either has an active selected filter
	 * or has fewer than 2 options — meaning there's nothing useful
	 * left to separate visually.
	 *
	 * @return {boolean} Whether the separator should be hidden.
	 */
	get separatorHide() {
		const ctx = getContext();

		return Object.keys( ctx.data.meta ).every( ( key ) => {
			return (
				ctx.selectedFilters.map[ key ] ||
				ctx.data.meta[ key ].length < 2
			);
		} );
	},
};
