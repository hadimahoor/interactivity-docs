<li class="doc-card">

<div class="doc-content">

<?php

echo \InteractivityDocs\Support\TemplateLoader::render(
    $blockPath,
    'components/taxonomies',
    ['type' => $type]
);

?>

<h3>

<a
data-wp-text="context.item.data.title"
data-wp-bind--href="context.item.data.slug">
</a>

</h3>

<?php

echo \InteractivityDocs\Support\TemplateLoader::render(
    $blockPath,
    'components/post-actions'
);

?>

</div>

</li>
