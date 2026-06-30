/**
 * Menu lifecycle callbacks.
 *
 * Tracks references to dropdown menu containers for focus management.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/callbacks/menu
 */

import { getContext, getElement } from '@wordpress/interactivity';

/**
 * Builds the menu callback set bound to the given store state.
 *
 * @param {Object} state - The store state object.
 * @return {Object} The menu callbacks.
 */
export const menuCallbacks = ( state ) => ( {
	/**
	 * Watches for menu open state and stores a reference to the menu container.
	 *
	 * This reference is used for focus trapping and cleanup when the menu closes.
	 */
	onWatchMenu() {
		const context  = getContext();
		const { ref }  = getElement();
		const { tax }  = ref.dataset;

		if ( ! tax ) {
			return;
		}

		// Initialize the dropdown menu registry if needed.
		context.ui.dropdownMenu ??= {};

		// Store a reference to the menu container when it opens.
		if ( state.isMenuOpen?.[ tax ] ) {
			context.ui.dropdownMenu[ tax ] = ref;
		}
	},
} );
