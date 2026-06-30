/**
 * Extracts a structured payload from a DOM event or element.
 *
 * Supports two payload types:
 * - `filter`     — when the resolved element has a `data-tax` attribute
 * - `pagination` — when the resolved element has a `data-id` attribute
 *
 * The `source` argument can be:
 * - A DOM Event   (uses `event.target` and walks up via `closest`)
 * - A DOM Element (calls `closest` directly, or uses the element itself)
 *
 * @param {Event|Element} source - The event or element to extract a payload from.
 * @returns {{ type: 'filter', tax: string, value: string }
 *          | { type: 'pagination', page: number }
 *          | null} The extracted payload, or null if extraction fails.
 */
export function extractPayload( source ) {
	// Resolve the target element:
	// - If source is an Event, walk up from event.target
	// - If source is an Element, call closest on it directly
	// - Fall back to source itself (already the right element)
	const el = source?.target
		? source.target.closest( '[data-tax], [data-id]' )
		: source?.closest?.( '[data-tax], [data-id]' ) || source;

	if ( ! el ) {
		return null;
	}

	// --- Filter payload ---
	// Triggered by elements carrying taxonomy + value information.
	// Value is read from `data-val`, then visible text content.
	if ( el.dataset.tax ) {
		const tax = el.dataset.tax;
		const value = el.dataset.val || el.innerText?.trim();

		if ( ! tax || ! value ) {
			return null;
		}

		return { type: 'filter', tax, value };
	}

	// --- Pagination payload ---
	// Triggered by elements carrying a numeric page identifier via `data-id`.
	if ( el.dataset.id ) {
		const page = parseInt( el.dataset.id, 10 );

		if ( Number.isNaN( page ) ) {
			return null;
		}

		return { type: 'pagination', page };
	}

	return null;
}
