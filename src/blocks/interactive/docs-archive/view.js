/**
 * View Module – interactivity-docs
 *
 * Registers the WordPress Interactivity API store for the
 * "interactivity-docs" namespace. Manages state, actions, and
 * callbacks for sorting, filtering, pagination, and menus.
 *
 * @module interactivity-docs/view
 * @see {@link https://developer.wordpress.org/block-editor/reference-guides/packages/packages-interactivity/ WordPress Interactivity API}
 */

import { store } from '@wordpress/interactivity';

// ---------------------------------------------------------------------------
// State getters
// Pure functions — each returns a slice of the global state tree.
// ---------------------------------------------------------------------------
import { composeState } from './state/getters/composeState';
import { uiState }       from './state/getters/uiState';
import { sortState }     from './state/getters/sortState';
import { menuState }     from './state/getters/menuState';
import { selectorState } from './state/getters/selectorState';
import { layoutState }   from './state/getters/layoutState';

// ---------------------------------------------------------------------------
// Action creators
// Side-effectful functions triggered by user events.
// ---------------------------------------------------------------------------
import { termAction }         from './actions/termAction';
import { menuActions }        from './actions/menuActions';
import { paginationAction }   from './actions/paginationAction';
import { selectFilterAction } from './actions/selectFilterAction';
import { removeFilterAction } from './actions/removeFilterAction';

// ---------------------------------------------------------------------------
// Callback handlers
// Invoked at specific lifecycle moments.
// ---------------------------------------------------------------------------
import { menuCallbacks }      from './callbacks/menuCallbacks';
import { lifecycleCallbacks } from './callbacks/lifecycleCallbacks';

// ---------------------------------------------------------------------------
// Store registration
// ---------------------------------------------------------------------------
// All PHP templates and JS modules that participate in shared interactivity
// must reference the same namespace: "interactivity-docs".
// ---------------------------------------------------------------------------
const { state, actions, callbacks } = store( 'interactivity-docs', {
	/**
	 * Composed state tree.
	 *
	 * `composeState` merges every getter slice into a single flat object
	 * so consumers can access e.g. ``state.ui.currentPage` without traversing
	 * nested namespaces.
	 */
	state: composeState(
		uiState,
		sortState,
		menuState,
		selectorState,
		layoutState,
	),

	/**
	 * Eagerly-registered actions.
	 *
	 * `menuActions` and `selectFilterAction` close over the resolved
	 * `state`/`actions` references, so they are injected after store
	 * creation (see "Dynamic registration" below).
	 */
	actions: {
		...termAction,
		...paginationAction,
		...removeFilterAction,
	},

	/**
	 * Eagerly-registered callbacks.
	 *
	 * `menuCallbacks` requires a reference to `state` and is injected
	 * after store creation.
	 */
	callbacks: {
		...lifecycleCallbacks,
	},
} );

// ---------------------------------------------------------------------------
// Dynamic registration
// ---------------------------------------------------------------------------
// Actions and callbacks that close over `state` or `actions` can only be
// constructed after the store is initialised. We patch them in here via
// Object.assign so templates see one flat registry.
// ---------------------------------------------------------------------------
Object.assign( actions, menuActions( actions, state ) );
Object.assign( actions, selectFilterAction( actions ) );
Object.assign( callbacks, menuCallbacks( state ) );
