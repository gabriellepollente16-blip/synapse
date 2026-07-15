<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<?= view('components/breadcrumbs', [
    'crumbs' => [
        ['label' => 'Facility Operations', 'href' => base_url('dashboard/bmg')],
        ['label' => 'Drums',               'href' => base_url('bmg/drums')],
        ['label' => $drum['drum_code'],    'href' => base_url('bmg/drums/' . $drum['id'])],
        'Start Batch',
    ],
]) ?>

<div class="container">

    <?= view('components/page_header', [
        'title'    => 'Start New Batch on ' . $drum['drum_code'],
        'subtitle' => 'Pick the waste type and enter the initial weight to begin composting.',
    ]) ?>

    <?= view('components/flash') ?>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <p style="margin: 0 0 1rem; color: var(--gray-700);">
                    <strong>Drum:</strong> <?= esc($drum['drum_code']) ?> &mdash; <?= esc($drum['name']) ?>
                    <span style="color: var(--gray-500);">(Capacity: <?= number_format((float) $drum['capacity_kg'], 1) ?> kg)</span>
                </p>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="card">
            <div class="card-body">
                <form action="<?= base_url('bmg/batches/create') ?>" method="post" id="startBatchForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="drum_id" value="<?= esc($drum['id']) ?>">

                    <div class="form-group">
                        <label for="waste_category_id">Waste Category *</label>
                        <select name="waste_category_id" id="waste_category_id" required class="syn-select">
                            <option value="">-- Select waste type --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option
                                    value="<?= esc($cat['id']) ?>"
                                    data-yield="<?= esc($cat['expected_yield_pct'] ?? 30) ?>"
                                    data-days="<?= esc($cat['reference_duration_days'] ?? 45) ?>"
                                ><?= esc($cat['name']) ?><?= ! empty($cat['reference_duration_days']) ? ' (' . $cat['reference_duration_days'] . ' days)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="input_weight_kg">Input Weight (kg) *</label>
                        <input type="number" name="input_weight_kg" id="input_weight_kg" required step="0.01" min="0.01"
                               max="<?= esc($drum['capacity_kg']) ?>" placeholder="e.g., 25.50" class="syn-input">
                        <small>Maximum: <?= esc($drum['capacity_kg']) ?> kg (drum capacity)</small>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes (optional)</label>
                        <textarea name="notes" id="notes" rows="2" class="syn-input"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i> Start Batch
                        </button>
                        <a href="<?= base_url('bmg/drums/' . $drum['id']) ?>" class="btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="section" id="estimateCalloutSection" style="display: none;">
        <div class="estimate-callout">
            <div class="estimate-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="estimate-body">
                <div class="estimate-title">Estimated Completion</div>
                <div class="estimate-date" id="estimateDate">—</div>
                <div class="estimate-sub" id="estimateSub"></div>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var select = document.getElementById('waste_category_id');
    var section = document.getElementById('estimateCalloutSection');
    var dateEl = document.getElementById('estimateDate');
    var subEl  = document.getElementById('estimateSub');
    if (!select || !section || !dateEl || !subEl) return;

    function fmtDate(d) {
        return d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function recalc() {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) {
            section.style.display = 'none';
            return;
        }
        var days = parseInt(opt.getAttribute('data-days') || '45', 10);
        if (!days || days < 1) days = 45;
        var start = new Date();
        var end   = new Date(start);
        end.setDate(end.getDate() + days);
        dateEl.textContent = fmtDate(end);
        subEl.textContent  = 'Approximately ' + days + ' day' + (days === 1 ? '' : 's') + ' from today · based on the category reference duration.';
        section.style.display = '';
    }

    select.addEventListener('change', recalc);
    recalc();
})();
</script>

<style>
    .estimate-callout {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #f0f9ff 0%, #ecfeff 100%);
        border: 1px solid #bae6fd;
        border-left: 4px solid #0ea5e9;
        border-radius: 0.625rem;
    }
    .estimate-callout .estimate-icon {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: #0ea5e9;
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .estimate-callout .estimate-body { flex: 1; min-width: 0; }
    .estimate-callout .estimate-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #0369a1;
        margin-bottom: 0.2rem;
    }
    .estimate-callout .estimate-date {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--gray-900);
        margin-bottom: 0.2rem;
    }
    .estimate-callout .estimate-sub {
        font-size: 0.8rem;
        color: var(--gray-500);
    }
</style>

<?= $this->endSection() ?>