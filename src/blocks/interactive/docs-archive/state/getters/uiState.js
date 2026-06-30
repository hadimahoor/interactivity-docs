/**
 * General UI visibility state for the docs-archive filter UI.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/ui
 */

import { getContext } from '@wordpress/interactivity';
import { isSinglePage } from '../singlePage';

/**
 * State slice for general UI visibility flags.
 */
export const uiState = {
	/**
	 * Whether the pagination controls should be hidden.
	 *
	 * Hidden when the result fits on a single page, or while
	 * a new page is loading to prevent layout jank.
	 *
	 * @return {boolean} Whether the pagination controls should be hidden.
	 */
	get isHidePagination() {
		const ctx = getContext();

		return isSinglePage( ctx ) || ctx.ui.isLoading;
	},
};
