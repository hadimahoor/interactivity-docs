<?php

/**
 * Component: Book cover
 *
 * Cover image column with format-icon overlay for a book card.
 *
 * @package InteractivityDocs
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<!-- Book Cover Column -->
<div
   class="wp-block-column has-global-padding is-content-justification-left is-layout-constrained wp-container-core-column-is-layout-24 wp-block-column-is-layout-constrained"
 style="flex-basis:30%"
>
   <!-- Linked card wrapper -->
   <div
       class="wp-block-group has-shadcn-accent-lighter-background-color has-background has-global-padding is-layout-constrained wp-block-group-is-layout-constrained is-linked"
       style="border-radius:16px"
 >
      <!-- Invisible full-area link for click target -->
     <a
         class="wp-block-group__link"
           data-wp-bind--href="context.item.data.permalink"
           target="_self"
         rel="follow"
           aria-hidden="true"
         tabindex="-1"
      >&nbsp;</a>

       <!-- Cover image with format icon overlay -->
      <div
           class="wp-block-cover is-light has-custom-content-position is-position-top-right has-aspect-ratio"
         style="border-radius:16px; aspect-ratio:1; min-height:unset;"
      >
          <!-- Background color overlay -->
          <span
              aria-hidden="true"
             class="wp-block-cover__background has-background-dim-0 has-background-dim"
             style="background-color:#67b0dc"
           ></span>

          <!-- Cover image -->
           <img
               width="2560"
               height="1707"
              data-wp-bind--src="context.item.data.image"
                data-wp-bind--alt="context.item.data.title"
                class="wp-block-cover__image-background wp-post-image"
             data-object-fit="cover"
                loading="lazy"
             decoding="async"
           >

         <!-- Inner content: post format icon -->
           <div class="wp-block-cover__inner-container has-global-padding is-layout-constrained wp-block-cover-is-layout-constrained">

               <!-- Book format icon -->
              <div class="wp-block-arad-post-format-icon" aria-hidden="true">
                    <svg width="24" height="25" viewBox="0 0 53 61" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                       <path d="M42.3218 0.5H6.69678C3.18115 0.134277 3.54688 0.134277 7.0625V40.8125C0.134277 43.5078 1.7749 45.7344 3.88428 46.7891V54.875H2.94678C1.42334 54.875 0.134277 56.1641 0.134277 57.6875C0.134277 59.3281 1.42334 60.5 2.94678 60.5H43.2593C48.5327 60.5 52.6343 56.3984 52.6343 51.125C52.6343 50.8906 52.6343 50.5391 52.6343 50.1875V10.8125C52.6343 5.1875 48.064 0.5 42.3218 0.5ZM9.50928 47.375H43.2593C45.3687 47.375 47.0093 49.1328 47.0093 51.125C47.0093 53.2344 45.3687 54.875 43.2593 54.875H9.50928V47.375ZM43.2593 41.75H6.69678C6.22803 41.75 5.75928 41.3984 5.75928 40.8125V7.0625C5.75928 6.59375 6.22803 6.125 6.69678 6.125H42.3218C45.0171 6.125 47.0093 8.23438 47.0093 10.8125V42.5703C45.9546 42.1016 44.6655 41.75 43.2593 41.75Z" fill="#ff"/>
                    </svg>
             </div>
         </div>
     </div>
 </div>
</div>
