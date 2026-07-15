<?php
/**
 * Breadcrumbs partial.
 *
 * Renders a chain of breadcrumb links. Pass `$crumbs` as an array of either:
 *   - a string  (rendered as a current/leaf crumb, not a link)
 *   - an array  with keys 'label' (string) and 'href' (string)
 *
 * Example:
 *   $crumbs = [
 *       ['label' => 'Facility Operations', 'href' => '/dashboard/bmg'],
 *       ['label' => 'Drums',               'href' => '/bmg/drums'],
 *       'BMG-001',  // current leaf
 *   ];
 */
if (empty($crumbs)) {
    return;
}
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <?php $last = count($crumbs) - 1; ?>
    <?php foreach ($crumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?>
            <span class="separator" aria-hidden="true">/</span>
        <?php endif; ?>
        <?php if (is_array($crumb) && isset($crumb['href'])): ?>
            <a href="<?= esc($crumb['href']) ?>"><?= esc($crumb['label']) ?></a>
        <?php else: ?>
            <span class="current" aria-current="page"><?= esc(is_array($crumb) ? ($crumb['label'] ?? '') : $crumb) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php
