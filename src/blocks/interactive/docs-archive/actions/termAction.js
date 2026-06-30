/**
 * Term interaction actions.
 *
 * Provides visual feedback for taxonomy term elements
 * by toggling their foreground and background colors.
 *
 * @module interactivity-docs/actions/term
 */

import { getElement } from '@wordpress/interactivity';

export const termAction = {/**
	 * Swaps the text color and background color of the term element.
	 *
	 * Intended for hover/active visual feedback driven by
	 * the Interactivity API (e.g. data-wp-on--mouseenter).
	 */
	toggleTermColor() {
		const { ref } = getElement();

		const color           = ref.style.color;
		const backgroundColor = ref.style.backgroundColor;

		ref.style.backgroundColor = color;
		ref.style.color           = backgroundColor;
	},
};
