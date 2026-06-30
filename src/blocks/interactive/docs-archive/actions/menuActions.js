/**
 * Menu interaction actions for the Interactivity API.
 *
 * Handles click, focus, keyboard events, and menu open/close state.
 * Each menu is keyed by its `data-tax` value, allowing multiple
 * independent menus to coexist within the same store.
 *
 * @module interactivity-docs/actions/menu
 * @param {Object} actions - Global actions object (for cross-action calls).
 * @param {Object} state   - Global state object (for derived state like `isMenuOpen`).
 * @returns {Object} Action handlers for menu interactions.
 */

import { getContext, getElement } from '@wordpress/interactivity';

export const menuActions = ( actions, state ) => ( {
	/**
	 * Toggles a menu on click.
	 * If the menu is already open (via click or focus), close it.
	 * Otherwise, store the previously focused element and open it.
	 */
	toggleMenuOnClick() {
		const context = getContext();
		const { ref } = getElement();
		const { tax } = ref.dataset;

		// Ensure the trigger is focused before toggling.
		if ( window.document.activeElement !== ref ) {
			ref?.focus();
		}

		// If already open by any method, close both click and focus state.
		if (
			context.ui.menuOpenedBy[ tax ].click ||
			context.ui.menuOpenedBy[ tax ].focus
		) {
			actions.closeMenu( 'click', tax );
			actions.closeMenu( 'focus', tax );
		} else {
			// Store the trigger so focus can be restored on close.
			context.ui.previousFocus[ tax ] = ref;
			actions.openMenu( 'click', tax );
		}
	},

	/**
	 * Closes a menu on click (e.g. backdrop click or menu item selection).
	 *
	 * Also closes focus-triggered state to ensure full cleanup.
	 */
	closeMenuOnClick() {
		const { ref } = getElement();
		const { tax } = ref.dataset;

		actions.closeMenu( 'click', tax );
		actions.closeMenu( 'focus', tax );
	},

	/**
	 * Handles the Escape key to close an open menu.
	 * Only acts if the menu was opened via click.
	 *
	 * @param {KeyboardEvent} event - The keyboard event.
	 */
	handleMenuKeydown( event ) {
		const context = getContext();
		const { tax } = event.target.dataset;

		if ( context.ui.menuOpenedBy[ tax ].click && event?.key === 'Escape' ) {
			actions.closeMenu( 'click', tax );
			actions.closeMenu( 'focus', tax );
		}
	},

	/**
	 * Opens a menu when the triger element receives focus.
	 */
	openMenuOnFocus() {
		const { ref } = getElement();
		const { tax } = ref.dataset;

		actions.openMenu( 'focus', tax );
	},

	/**
	 * Closes a menu when focus leaves both the triger and the menu container.
	 *
	 * Closes if focus moved to an unrelated element (`relatedTarget` is null
	 * or sits outside the menu).
	 *
	 * @param {FocusEvent} event - The focusout event.
	 */
	handleMenuFocusout( event ) {
		const context = getContext();
		const { tax } = event.target.dataset;
		const menuContainer = context.ui.dropdownMenu[ tax ];

		// Close if focus left the entire menu system.
		if (
			event.relatedTarget === null ||
			( ! menuContainer?.contains( event.relatedTarget ) &&
				event.target !== window.document.activeElement )
		) {
			actions.closeMenu( 'click', tax );
			actions.closeMenu( 'focus', tax );
		}
	},

	/**
	 * Marks a menu as open via the specified method.
	 * @param {string} menuOpenedOn - Either "click" or "focus".
	 * @param {string} tax          - The taxonomy key identifying the menu.
	 */
	openMenu( menuOpenedOn = 'click', tax ) {
		const context = getContext();
		context.ui.menuOpenedBy[ tax ][ menuOpenedOn ] = true;
	},

	/**
	 * Marks a menu as closed via the specified method.
	 *
	 * If both methods are now closed, restore focus to the trigger and
	 * clean up stored references.
	 * @param {string} menuClosedOn - Either "click" or "focus".
	 * @param {string} tax          - The taxonomy key identifying the menu.
	 */
	closeMenu( menuClosedOn = 'click', tax ) {
		const context = getContext();
		context.ui.menuOpenedBy[ tax ][ menuClosedOn ] = false;

		// Fully closed when both click and focus states are false.
		if ( ! state.isMenuOpen[ tax ] ) {
			// Restore focus to the trigger if focus is still inside the menu.
			if (
				context.ui.dropdownMenu[ tax ]?.contains(
					window.document.activeElement
				)
			) {
				context.ui.previousFocus[ tax ]?.focus();
			}

			// Clean up references.
			context.ui.previousFocus[ tax ] = null;
			context.ui.dropdownMenu[ tax ] = null;
		}
	},
} );
