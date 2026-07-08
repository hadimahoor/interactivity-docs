/**
 * Block Editor Component for Docs Archive.
 *
 * Provides an editor interface for configuring a dynamic docs archive block.
 * Supports filtering by post type (paper, book, person) and faceted taxonomy
 * filters. Fetches data from the REST API and renders a live preview inside
 * the editor.
 *
 * @package InteractivityDocs
 */

import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
  PanelBody,
  RadioControl,
  ToggleControl,
  RangeControl,
  Notice,
  TextControl,
  CheckboxControl,
} from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "@wordpress/element";
import { paginate } from "./pagination/pagination";

/**
 * REST API namespace.
 */
const REST_NAMESPACE = "interactivity-docs/v1";

/**
 * Sentinel value meaning "all" / "no filter applied".
 */
const ALL_VALUE = "-";

/**
 * Default option shown in every filter list.
 */
const ALL_OPTION = { label: __("All", "interactivity-docs"), value: ALL_VALUE };

/**
 * Supported post types.
 */
const VALID_POST_TYPES = ["paper", "book", "person"];

/**
 * Facet keys available per post type.
 * Keys are camelCase (frontend) and map to snake_case (backend).
 */
const FACET_KEYS_BY_TYPE = {
  paper: ["year", "language", "paperType", "magazine"],
  book: ["year", "language", "bookType", "publication"],
  person: ["gender"],
};

/**
 * Map camelCase attribute names to snake_case API parameter names.
 */
const ATTRIBUTE_TO_API_MAP = {
  year: "filter_year",
  paperType: "paper_type",
  bookType: "book_type",
};

/**
 * Check whether a post type is supported.
 *
 * @param {string} type
 * @return {boolean}
 */
const isValidPostType = (type) => VALID_POST_TYPES.includes(type);

/**
 * Normalize a filter value to an array.
 *
 * - If already an array, clean it and drop empty values.
 * - If ALL_VALUE is present alongside real values, keep only the real values.
 * - If empty or null, return [ALL_VALUE].
 *
 * @param {any} value
 * @return {string[]}
 */
const normalizeMultiValue = (value) => {
  if (Array.isArray(value)) {
    const cleanValues = value.map((v) => String(v)).filter(Boolean);

    if (!cleanValues.length) {
      return [ALL_VALUE];
    }

    if (cleanValues.includes(ALL_VALUE) && cleanValues.length > 1) {
      const withoutAll = cleanValues.filter((v) => v !== ALL_VALUE);
      return withoutAll.length ? withoutAll : [ALL_VALUE];
    }

    return cleanValues;
  }

  if (value === undefined || value === null || value === "") {
    return [ALL_VALUE];
  }

  return [String(value)];
};

/**
 * Toggle a checkbox value within a multi-select filter.
 *
 * - Toggling ALL_VALUE on resets the selection to [ALL_VALUE].
 * - Toggling a specific value on adds it to the array.
 * - Toggling a specific value off removes it.
 * - An empty result falls back to [ALL_VALUE].
 *
 * @param {string[]} currentValue
 * @param {string}   toggledValue
 * @param {boolean}  checked
 * @return {string[]}
 */
const toggleCheckboxValue = (currentValue, toggledValue, checked) => {
  const current = normalizeMultiValue(currentValue);

  if (toggledValue === ALL_VALUE) {
    return [ALL_VALUE];
  }

  let next = current.filter((v) => v !== ALL_VALUE);

  if (checked) {
    if (!next.includes(toggledValue)) {
      next.push(toggledValue);
    }
  } else {
    next = next.filter((v) => v !== toggledValue);
  }

  return next.length ? next : [ALL_VALUE];
};

/**
 * Normalize a facets API response into { facetKey: [options] }.
 *
 * Expected response structure:
 * {
 *   success: true,
 *   items: {
 *     year: [...],
 *     language: [...],
 *     paper_type: [...],  // snake_case from backend
 *     ...
 *   }
 * }
 *
 * Backend snake_case keys are mapped to frontend camelCase keys.
 *
 * @param {Object} res
 * @return {Object}
 */
function normalizeFacetsResponse(res) {
  const facetsRoot =
    res?.items && typeof res.items === "object" && !Array.isArray(res.items)
      ? res.items
      : res?.facets && typeof res.facets === "object"
      ? res.facets
      : res && typeof res === "object"
      ? res
      : null;

  if (!facetsRoot || typeof facetsRoot !== "object") return {};

  const out = {};

  // Reverse map: snake_case (from API) -> camelCase (for frontend).
  const apiToAttributeMap = Object.fromEntries(
    Object.entries(ATTRIBUTE_TO_API_MAP).map(([camel, snake]) => [
      snake,
      camel,
    ]),
  );

  Object.entries(facetsRoot).forEach(([facetKey, facetItems]) => {
    const normalizedKey = apiToAttributeMap[facetKey] || facetKey;

    if (!Array.isArray(facetItems)) {
      out[normalizedKey] = [ALL_OPTION];
      return;
    }

    const opts = facetItems
      .map((item) => {
        if (item && typeof item === "object") {
          const label = item.label ?? item.name ?? item.title ?? item.value;
          const value = item.value ?? item.slug ?? item.id ?? item.label;

          if (label == null || value == null) return null;

          return {
            label: String(label),
            value: String(value),
          };
        }

        if (typeof item === "string" || typeof item === "number") {
          return {
            label: String(item),
            value: String(item),
          };
        }

        return null;
      })
      .filter(Boolean);

    out[normalizedKey] = [ALL_OPTION, ...opts];
  });

  return out;
}

/**
 * Custom checkbox list control for multi-select filters.
 *
 * @param {Object}   props
 * @param {string}   props.label
 * @param {string[]} props.value
 * @param {Array}    props.options
 * @param {Function} props.onChange
 * @param {boolean}  props.disabled
 * @param {string}   props.emptyText
 */
function CheckboxListControl({
  label,
  value,
  options,
  onChange,
  disabled = false,
  emptyText = __("No options available.", "interactivity-docs"),
}) {
  const [searchTerm, setSearchTerm] = useState("");
  const selectedValues = normalizeMultiValue(value);
  const safeOptions =
    Array.isArray(options) && options.length ? options : [ALL_OPTION];

  // فیلتر براساس جستجو
  const filteredOptions = safeOptions.filter((option) =>
    option.label.toLowerCase().includes(searchTerm.toLowerCase()),
  );

  return (
    <div className="docs-checkbox-list-control">
      {label && (
        <div className="docs-checkbox-list-control__label">
          <strong>{label}</strong>
        </div>
      )}

      {/* فیلد جستجو */}
      {safeOptions.length > 5 && (
        <TextControl
          placeholder={__("Search...", "interactivity-docs")}
          value={searchTerm}
          onChange={setSearchTerm}
          className="docs-checkbox-list-control__search"
        />
      )}

      <div className="docs-checkbox-list-control__items">
        {filteredOptions.length ? (
          filteredOptions.map((option) => {
            const optionValue = String(option.value);
            const isChecked = selectedValues.includes(optionValue);

            return (
              <CheckboxControl
                key={optionValue}
                label={option.label}
                checked={isChecked}
                disabled={disabled}
                onChange={(checked) => {
                  onChange(
                    toggleCheckboxValue(selectedValues, optionValue, checked),
                  );
                }}
              />
            );
          })
        ) : (
          <p>{emptyText}</p>
        )}
      </div>
    </div>
  );
}

/**
 * Main Edit component.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 */
export default function Edit({ attributes, setAttributes }) {
  const {
    type,
    itemsPerPage,
    currentPage,
    showMeta,
    list,
    count,
    year,
    language,
    paperType,
    magazine,
    bookType,
    publication,
    gender,
  } = attributes;

  /**
   * Memoized normalized filter values.
   * Prevents unnecessary re-renders and redundant API calls.
   */
  const selectedYear = useMemo(() => normalizeMultiValue(year), [year]);
  const selectedLanguage = useMemo(
    () => normalizeMultiValue(language),
    [language],
  );
  const selectedPaperType = useMemo(
    () => normalizeMultiValue(paperType),
    [paperType],
  );
  const selectedMagazine = useMemo(
    () => normalizeMultiValue(magazine),
    [magazine],
  );
  const selectedBookType = useMemo(
    () => normalizeMultiValue(bookType),
    [bookType],
  );
  const selectedPublication = useMemo(
    () => normalizeMultiValue(publication),
    [publication],
  );
  const selectedGender = useMemo(() => normalizeMultiValue(gender), [gender]);

  const [isLoadingDocs, setIsLoadingDocs] = useState(false);
  const [docsError, setDocsError] = useState("");

  const [isLoadingFacets, setIsLoadingFacets] = useState(false);
  const [facetsError, setFacetsError] = useState("");

  const [facetOptions, setFacetOptions] = useState({
    year: [ALL_OPTION],
    language: [ALL_OPTION],
    paperType: [ALL_OPTION],
    magazine: [ALL_OPTION],
    bookType: [ALL_OPTION],
    publication: [ALL_OPTION],
    gender: [ALL_OPTION],
  });

  /**
   * Track mounted state to avoid setState after unmount.
   */
  const isMountedRef = useRef(true);

  useEffect(() => {
    return () => {
      isMountedRef.current = false;
    };
  }, []);

  /**
   * Append a filter parameter to URLSearchParams.
   * Converts camelCase attribute names to snake_case API parameters.
   *
   * - Array value with real items (not ALL_VALUE): append each as key[].
   * - Single real value: append as key.
   * - ALL_VALUE or empty: skip.
   *
   * @param {URLSearchParams} params
   * @param {string}          attributeKey - camelCase attribute name
   * @param {string|string[]} value
   */
  const addFilterParam = (params, attributeKey, value) => {
    if (!value) return;

    const apiKey = ATTRIBUTE_TO_API_MAP[attributeKey] || attributeKey;

    if (Array.isArray(value)) {
      const filteredValues = value.filter((v) => v && v !== ALL_VALUE);
      if (!filteredValues.length) return;

      filteredValues.forEach((v) => {
        params.append(`${apiKey}[]`, v);
      });
      return;
    }

    if (value !== ALL_VALUE) {
      params.set(apiKey, value);
    }
  };

  /**
   * Handle checkbox list changes and reset to page 1.
   *
   * @param {string}   key - camelCase attribute name
   * @param {string[]} values
   */
  const handleCheckboxListChange = (key, values) => {
    setAttributes({
      [key]: normalizeMultiValue(values),
      currentPage: 1,
    });
  };

  /**
   * Fetch docs from the /docs endpoint.
   */
  const fetchDocs = useCallback(async () => {
    setDocsError("");
    setIsLoadingDocs(true);

    if (!isValidPostType(type)) {
      setAttributes({ list: [], count: 0 });
      setIsLoadingDocs(false);
      return;
    }

    const params = new URLSearchParams();
    params.set("post_type", type);
    params.set("page", String(currentPage || 1));
    params.set("per_page", String(itemsPerPage || 12));
    params.set("sort", "latest");
    params.set("include_meta", showMeta ? "1" : "0");

    if (type === "paper") {
      addFilterParam(params, "year", selectedYear);
      addFilterParam(params, "language", selectedLanguage);
      addFilterParam(params, "paperType", selectedPaperType);
      addFilterParam(params, "magazine", selectedMagazine);
    } else if (type === "book") {
      addFilterParam(params, "year", selectedYear);
      addFilterParam(params, "language", selectedLanguage);
      addFilterParam(params, "bookType", selectedBookType);
      addFilterParam(params, "publication", selectedPublication);
    } else if (type === "person") {
      addFilterParam(params, "gender", selectedGender);
    }

    try {
      const response = await apiFetch({
        path: `/${REST_NAMESPACE}/docs?${params.toString()}`,
        method: "GET",
      });

      if (!isMountedRef.current) return;

      setAttributes({
        list: response?.items || [],
        count: response?.pagination?.total ?? 0,
      });
    } catch (e) {
      if (!isMountedRef.current) return;

      console.error("Docs fetch error:", e);
      setDocsError(__("Failed to load the list.", "interactivity-docs"));
      setAttributes({ list: [], count: 0 });
    } finally {
      if (isMountedRef.current) {
        setIsLoadingDocs(false);
      }
    }
  }, [
    type,
    itemsPerPage,
    currentPage,
    showMeta,
    selectedYear,
    selectedLanguage,
    selectedPaperType,
    selectedMagazine,
    selectedBookType,
    selectedPublication,
    selectedGender,
    setAttributes,
  ]);

  /**
   * Fetch facets from the /docs/facets endpoint.
   */
  const fetchFacets = useCallback(async () => {
    setFacetsError("");

    if (!isValidPostType(type)) return;

    setIsLoadingFacets(true);

    const params = new URLSearchParams();
    params.set("post_type", type);
    params.set("limit", "200");

    try {
      const res = await apiFetch({
        path: `/${REST_NAMESPACE}/docs/facets?${params.toString()}`,
        method: "GET",
      });

      if (!isMountedRef.current) return;

      const normalized = normalizeFacetsResponse(res);
      const allowedKeys = FACET_KEYS_BY_TYPE[type] || [];

      setFacetOptions((prev) => {
        const next = { ...prev };
        allowedKeys.forEach((k) => {
          next[k] = normalized[k] || [ALL_OPTION];
        });
        return next;
      });
    } catch (e) {
      if (!isMountedRef.current) return;

      console.error("Facets fetch error:", e);
      setFacetsError(__("Failed to load filters.", "interactivity-docs"));
    } finally {
      if (isMountedRef.current) {
        setIsLoadingFacets(false);
      }
    }
  }, [type]);

  /**
   * Refetch docs whenever dependencies change.
   */
  useEffect(() => {
    fetchDocs();
  }, [fetchDocs]);

  /**
   * Refetch facets whenever the post type changes.
   */
  useEffect(() => {
    fetchFacets();
  }, [fetchFacets]);

  /**
   * Calculate the total number of pages.
   */
  const totalPages = useMemo(() => {
    const per = itemsPerPage || 1;
    return count > 0 ? Math.ceil(count / per) : 0;
  }, [count, itemsPerPage]);

  /**
   * Update attributes and reset to page 1.
   *
   * @param {Object} nextAttrs
   */
  const updateAndResetPage = (nextAttrs) => {
    setAttributes({
      ...nextAttrs,
      currentPage: 1,
    });
  };

  return (
    <div {...useBlockProps()}>
      <InspectorControls>
        {/* General settings */}
        <PanelBody title={__("General Settings", "interactivity-docs")}>
          <RadioControl
            label={__("Content Type", "interactivity-docs")}
            selected={type}
            options={[
              { label: __("Papers", "interactivity-docs"), value: "paper" },
              { label: __("Books", "interactivity-docs"), value: "book" },
              { label: __("People", "interactivity-docs"), value: "person" },
            ]}
            onChange={(value) => updateAndResetPage({ type: value })}
          />

          <RangeControl
            label={__("Items per page", "interactivity-docs")}
            value={itemsPerPage}
            onChange={(value) => setAttributes({ itemsPerPage: value })}
            min={1}
            max={100}
          />

          <ToggleControl
            label={__("Show metadata", "interactivity-docs")}
            checked={!!showMeta}
            onChange={(value) => setAttributes({ showMeta: !!value })}
          />

          {docsError && (
            <Notice status="error" isDismissible={false}>
              {docsError}
            </Notice>
          )}

          {facetsError && (
            <Notice status="warning" isDismissible={false}>
              {facetsError}
            </Notice>
          )}
        </PanelBody>

        {/* Paper filters */}
        {type === "paper" && (
          <PanelBody
            title={__("Paper Filters", "interactivity-docs")}
            initialOpen={false}
          >
            <CheckboxListControl
              label={__("Year", "interactivity-docs")}
              value={selectedYear}
              options={facetOptions.year}
              onChange={(values) => handleCheckboxListChange("year", values)}
              disabled={isLoadingFacets}
            />

            <CheckboxListControl
              label={__("Language", "interactivity-docs")}
              value={selectedLanguage}
              options={facetOptions.language}
              onChange={(values) =>
                handleCheckboxListChange("language", values)
              }
              disabled={isLoadingFacets}
            />

            <CheckboxListControl
              label={__("Paper Type", "interactivity-docs")}
              value={selectedPaperType}
              options={facetOptions.paperType}
              onChange={(values) =>
                handleCheckboxListChange("paperType", values)
              }
              disabled={isLoadingFacets}
            />

            <CheckboxListControl
              label={__("Magazine", "interactivity-docs")}
              value={selectedMagazine}
              options={facetOptions.magazine}
              onChange={(values) =>
                handleCheckboxListChange("magazine", values)
              }
              disabled={isLoadingFacets}
            />
          </PanelBody>
        )}

        {/* Book filters */}
        {type === "book" && (
          <PanelBody
            title={__("Book Filters", "interactivity-docs")}
            initialOpen={false}
          >
            <CheckboxListControl
              label={__("Year", "interactivity-docs")}
              value={selectedYear}
              options={facetOptions.year}
              onChange={(values) => handleCheckboxListChange("year", values)}
              disabled={isLoadingFacets}
            />

            <CheckboxListControl
              label={__("Language", "interactivity-docs")}
              value={selectedLanguage}
              options={facetOptions.language}
              onChange={(values) =>
                handleCheckboxListChange("language", values)
              }
              disabled={isLoadingFacets}
            />

            <CheckboxListControl
              label={__("Book Type", "interactivity-docs")}
              value={selectedBookType}
              options={facetOptions.bookType}
              onChange={(values) =>
                handleCheckboxListChange("bookType", values)
              }
              disabled={isLoadingFacets}
            />

            <CheckboxListControl
              label={__("Publisher", "interactivity-docs")}
              value={selectedPublication}
              options={facetOptions.publication}
              onChange={(values) =>
                handleCheckboxListChange("publication", values)
              }
              disabled={isLoadingFacets}
            />
          </PanelBody>
        )}

        {/* Person filters */}
        {type === "person" && (
          <PanelBody
            title={__("Person Filters", "interactivity-docs")}
            initialOpen={false}
          >
            <CheckboxListControl
              label={__("Gender", "interactivity-docs")}
              value={selectedGender}
              options={facetOptions.gender}
              onChange={(values) => handleCheckboxListChange("gender", values)}
              disabled={isLoadingFacets}
            />
          </PanelBody>
        )}
      </InspectorControls>

      {/* Preview */}
      <div className="docs-archive-wrapper">
        {isLoadingDocs ? (
          <div className="spinner-loader" />
        ) : (
          <div className="docs-list">
            {Array.isArray(list) && list.length ? (
              list.map((item) => (
                <div key={item.id} className="doc-item">
                  <h4>{item.title || item.post_title}</h4>
                  {showMeta && item.created_at && (
                    <small>{item.created_at}</small>
                  )}
                </div>
              ))
            ) : (
              <p>{__("No items found.", "interactivity-docs")}</p>
            )}
          </div>
        )}

        {/* Pagination */}
        {totalPages > 1 &&
          (() => {
            const pages = paginate({ current: currentPage, max: totalPages });

            if (!pages) return null;

            return (
              <div className="docs-pagination">
                {pages.items.map((p, idx) =>
                  p.item === "…" ? (
                    <span key={`gap-${idx}`} className="pagination-ellipsis">
                      …
                    </span>
                  ) : (
                    <button
                      key={p.item}
                      type="button"
                      className={p.isCurrent ? "active" : ""}
                      onClick={() => setAttributes({ currentPage: p.item })}
                    >
                      {p.item}
                    </button>
                  ),
                )}
              </div>
            );
          })()}
      </div>
    </div>
  );
}
