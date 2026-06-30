/**
 * Sort-option active state for the docs-archive filter UI.
 *
 * @module interactivity-docs/blocks/interactive/docs-archive/state/sort
 */

import { getContext } from '@wordpress/interactivity';

/**
 * State slice for sort-option active states.
 *
 * Each getter compares `ctx.query.sort` against a known sort key
 * so the UI can highlight the currently active sort button.
 */
export const sortState = {
	/**
	 * @return {boolean} True when sorting by name.
	 */
	get isActiveName() {
		const ctx = getContext();

		return ctx.query.sort === 'name';
	},

	/**
	 * @return {boolean} True when sorting by paper count.
	 */
	get isActivePaperCount() {
		const ctx = getContext();

		return ctx.query.sort === 'paper_count';
	},

	/**
	 * @return {boolean} True when sorting by book count.
	 */
	get isActiveBookCount() {
		const ctx = getContext();

		return ctx.query.sort === 'book_count';
	},
};
