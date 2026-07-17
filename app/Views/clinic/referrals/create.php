<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 650px;">
    <div class="card-header"><i class="fas fa-arrow-right-arrow-left" style="margin-right: 0.5rem; color: #F59E0B;"></i> Refer to Counselling</div>
    <div class="card-body">
        <div style="margin-bottom: 1.25rem; padding: 0.75rem; background: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 0.5rem;">
            <p style="font-size: 0.75rem; color: #5B21B6; font-weight: 600;"><i class="fas fa-shield-halved"></i> Privacy Notice</p>
            <p style="font-size: 0.75rem; color: #6D28D9; margin-top: 0.25rem;">Only the student ID, complaint category, and urgency will be shared. No clinical diagnosis or treatment details are included in the referral.</p>
        </div>

        <?php $errors = session()->get('errors') ?? []; ?>

        <form method="POST" action="/clinic/referrals/store" novalidate
              data-synapse-form-dialog
              data-dialog-title="Refer to Counselling"
              data-dialog-icon="fas fa-arrow-right-arrow-left"
              data-dialog-submit-label="Send Referral"
              data-dialog-cancel-label="Cancel">
            <?= csrf_field() ?>
            <input type="hidden" name="student_id" id="student_id" value="<?= $consult['student_id'] ?? ($student['id'] ?? old('student_id')) ?>">
            <?php if (isset($consult)): ?>
                <input type="hidden" name="source_consultation_id" value="<?= $consult['id'] ?>">
            <?php endif ?>

            <?php if (! empty($errors)): ?>
                <div role="alert" id="referral-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div>
                        <strong>Please fix the following before submitting:</strong>
                        <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                            <?php foreach ($errors as $field => $msg): ?>
                                <li><?= esc($msg) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Patient</label>
                <?php if ($consult !== null): ?>
                    <p id="referral-patient" style="font-size: 0.9rem; font-weight: 600; color: #111827;"><?= esc($consult['student_first'] . ' ' . $consult['student_last']) ?> <span style="color: #6B7280; font-weight: 400;">(<?= esc($consult['student_number']) ?>)</span></p>
                <?php elseif ($student !== null): ?>
                    <p id="referral-patient" style="font-size: 0.9rem; font-weight: 600; color: #111827;"><?= esc($student['full_name']) ?> <span style="color: #6B7280; font-weight: 400;">(<?= esc($student['student_number']) ?>)</span></p>
                <?php else: ?>
                    <input id="student_search" type="text" name="student_search" value="<?= old('student_search') ?>"
                        placeholder="Search student name, number, or email"
                        style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;" autocomplete="off">
                    <div id="student_search_results" style="margin-top: 0.75rem;
                        padding: 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; max-height: 220px; overflow-y: auto; background: #FFFFFF;
                        display: none;"></div>
                    <p style="margin-top: 0.75rem; font-size: 0.8rem; color: #6B7280;">Faculty/staff can search for the student and submit a counselling referral.</p>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="referral-reason" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Reason / Complaint Category *</label>
                <textarea id="referral-reason" name="reason" rows="3" required aria-required="true"
                    <?= isset($errors['reason']) ? 'aria-invalid="true" aria-describedby="referral-reason-err"' : '' ?>
                    placeholder="Describe the general reason for referral (e.g. Behaviour concerns, stress, academic difficulty)..."
                    style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= old('reason') ?></textarea>
                <?php if (isset($errors['reason'])): ?>
                    <p id="referral-reason-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['reason']) ?></p>
                <?php endif; ?>
            </div>

            <fieldset style="margin-bottom: 1.25rem; padding: 0; border: 0;">
                <legend style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem; padding: 0;">Priority *</legend>
                <div role="radiogroup" aria-required="true" aria-label="Referral priority"
                    <?= isset($errors['priority']) ? 'aria-invalid="true" aria-describedby="referral-priority-err"' : '' ?>
                    style="display: flex; gap: 0.5rem;">
                    <label style="flex: 1; padding: 0.6rem; border: 2px solid #A7F3D0; border-radius: 0.5rem; text-align: center; cursor: pointer; transition: all 150ms; background: #ECFDF5;">
                        <input type="radio" name="priority" value="routine" checked style="display: none;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: #059669;">🟢 Routine</span>
                    </label>
                    <label style="flex: 1; padding: 0.6rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; text-align: center; cursor: pointer; transition: all 150ms;">
                        <input type="radio" name="priority" value="urgent" style="display: none;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: #D97706;">🟡 Urgent</span>
                    </label>
                    <label style="flex: 1; padding: 0.6rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; text-align: center; cursor: pointer; transition: all 150ms;">
                        <input type="radio" name="priority" value="emergency" style="display: none;">
                        <span style="font-size: 0.8rem; font-weight: 600; color: #DC2626;">🔴 Emergency</span>
                    </label>
                </div>
                <?php if (isset($errors['priority'])): ?>
                    <p id="referral-priority-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['priority']) ?></p>
                <?php endif; ?>
            </fieldset>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/clinic/consultations/<?= $consult['id'] ?>" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #F59E0B; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-paper-plane" style="margin-right: 0.25rem;"></i> Send Referral
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('input[name="priority"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('input[name="priority"]').forEach(r => {
            r.parentElement.style.borderColor = '#E5E7EB';
            r.parentElement.style.background = 'white';
        });
        this.parentElement.style.borderColor = this.value === 'emergency' ? '#FCA5A5' : (this.value === 'urgent' ? '#FDE68A' : '#A7F3D0');
        this.parentElement.style.background = this.value === 'emergency' ? '#FEF2F2' : (this.value === 'urgent' ? '#FFFBEB' : '#ECFDF5');
    });
});

const studentSearch = document.getElementById('student_search');
const searchResults = document.getElementById('student_search_results');
const studentIdField = document.getElementById('student_id');

if (studentSearch && searchResults && studentIdField) {
    let debounce = null;

    function renderResults(results) {
        if (!results.length) {
            searchResults.innerHTML = '<p style="margin:0;color:#6B7280;font-size:0.85rem;">No students found. Try another name, email, or student number.</p>';
            searchResults.style.display = 'block';
            return;
        }

        searchResults.innerHTML = results.map(student => {
            return `<button type="button" class="student-search-result" data-id="${student.id}" data-name="${student.first_name} ${student.last_name}" data-number="${student.student_number}" style="width:100%;text-align:left;padding:0.75rem;border:none;background:none;cursor:pointer;display:block;">` +
                `<strong>${student.first_name} ${student.last_name}</strong><br><span style="font-size:0.8rem;color:#6B7280;">${student.student_number} · ${student.email}</span>` +
                `</button>`;
        }).join('');

        searchResults.style.display = 'block';
        document.querySelectorAll('.student-search-result').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const number = button.getAttribute('data-number');
                studentIdField.value = id;
                studentSearch.value = `${name} (${number})`;
                searchResults.style.display = 'none';
            });
        });
    }

    studentSearch.addEventListener('input', () => {
        const q = studentSearch.value.trim();
        studentIdField.value = '';

        if (debounce) {
            clearTimeout(debounce);
        }

        if (q.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        debounce = setTimeout(() => {
            fetch(`/clinic/students/search?q=${encodeURIComponent(q)}&method=manual`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.json())
                .then(data => {
                    renderResults(data.results || []);
                })
                .catch(() => {
                    searchResults.style.display = 'none';
                });
        }, 250);
    });
}
</script>
<?= $this->endSection() ?>
