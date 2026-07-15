<?php
/**
 * Page header partial.
 *
 * Renders a uniform page header: title on the left, optional subtitle
 * underneath, and an actions slot on the right.
 *
 * Usage:
 *   <?= view('components/page_header', [
 *       'title'    => 'BMG Drums',
 *       'subtitle' => 'Manage composting drums and their lifecycle',
 *       'actions'  => '<a href="..." class="btn btn-primary">+ New Drum</a>',
 *   ]) ?>
 */
$title    = $title    ?? '';
$subtitle = $subtitle ?? null;
$actions  = $actions  ?? null;
?>
<div class="page-header">
    <div>
        <?php if ($title): ?>
            <h1><?= esc($title) ?></h1>
        <?php endif; ?>
        <?php if ($subtitle): ?>
            <p class="subtitle"><?= esc($subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($actions): ?>
        <div class="actions">
            <?= $actions /* intentionally not escaped — pre-rendered HTML */ ?>
        </div>
    <?php endif; ?>
</div>
<?php
