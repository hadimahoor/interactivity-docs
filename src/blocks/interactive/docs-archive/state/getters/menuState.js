/**
 * Menu open/close state for the docs-archive filter UI.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/menu
 */

import { getContext } from '@wordpress/interactivity';

/**
 * State slice for menu open/close logic.
 */
export const menuState = {
	/**
	 * A dynamic object of getters, one per menu entry in `ctx.ui.menuOpenedBy`.
	 *
	 * Each getter (e.g. `isMenuOpen.navigation`) returns true if any
	 * trigger source for that menu has opened it.
	 *
	 * @return {Object} Map of menu keys to boolean getters.
	 */
	get isMenuOpen() {
		const ctx     = getContext();
		const getters = {};

		Object.keys( ctx.ui.menuOpenedBy ).forEach( ( property ) => {
			Object.defineProperty( getters, property, {
				get() {
					return Object
						.values( ctx.ui.menuOpenedBy[ property ] )
						.some( Boolean );
				},
			} );
		} );

		return getters;
	},
};
