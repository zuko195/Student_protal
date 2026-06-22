/**
 * SMS — Frontend Validation
 * Validates forms before submission. All errors shown inline below each field.
 */

// ── Helpers ──────────────────────────────────────────────────
function showError(fieldId, msg) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.classList.add('is-invalid');
    let fb = field.parentElement.querySelector('.invalid-feedback');
    if (!fb) {
        fb = document.createElement('div');
        fb.className = 'invalid-feedback';
        field.parentElement.appendChild(fb);
    }
    fb.textContent = msg;
}

function clearErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}

const allowedEmailDomains = ['gmail.com', 'rayblaze.com'];

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}

function getEmailDomain(email) {
    const parts = email.trim().split('@');
    return parts.length === 2 ? parts[1].toLowerCase() : '';
}

function isAllowedEmailDomain(email) {
    return allowedEmailDomains.includes(getEmailDomain(email));
}

function isValidPhone(phone) {
    return /^\+?[0-9]{7,15}$/.test(phone.trim());
}

// ── Login form ───────────────────────────────────────────────
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
        clearErrors('loginForm');
        let valid = true;

        const user = document.getElementById('username') || document.getElementById('email');
        const pass = document.getElementById('password');

        if (user && user.value.trim() === '') {
            showError(user.id, user.id === 'email' ? 'Email is required.' : 'Username is required.');
            valid = false;
        } else if (user && user.id === 'email' && !isValidEmail(user.value)) {
            showError('email', 'Invalid email format.');
            valid = false;
        }

        if (pass && pass.value.trim() === '') {
            showError('password', 'Password is required.');
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
}

// ── Add / Edit Student form ──────────────────────────────────
const studentForm = document.getElementById('studentForm');
if (studentForm) {
    studentForm.addEventListener('submit', function (e) {
        clearErrors('studentForm');
        let valid = true;

        const req = [
            { id: 'full_name', msg: 'Full name is required.' },
            { id: 'course',    msg: 'Course is required.' },
            { id: 'address',   msg: 'Address is required.' },
        ];

        req.forEach(function (r) {
            const el = document.getElementById(r.id);
            if (el && el.value.trim() === '') {
                showError(r.id, r.msg);
                valid = false;
            }
        });

        // Email
        const email = document.getElementById('email');
        if (email) {
            if (email.value.trim() === '') {
                showError('email', 'Email is required.');
                valid = false;
            } else if (!isValidEmail(email.value)) {
                showError('email', 'Invalid email format.');
                valid = false;
            } else if (!isAllowedEmailDomain(email.value)) {
                showError('email', 'Only @gmail.com and @rayblaze.com email addresses are allowed.');
                valid = false;
            }
        }

        // Password (only on add form — edit has optional password)
        const password = document.getElementById('password');
        if (password && password.dataset.required === 'true') {
            if (password.value.trim() === '') {
                showError('password', 'Password is required.');
                valid = false;
            } else if (password.value.length < 8) {
                showError('password', 'Password must be at least 8 characters.');
                valid = false;
            }
        } else if (password && password.value.trim() !== '' && password.value.length < 8) {
            showError('password', 'Password must be at least 8 characters.');
            valid = false;
        }

        // Phone
        const phone = document.getElementById('phone');
        if (phone) {
            if (phone.value.trim() === '') {
                showError('phone', 'Phone number is required.');
                valid = false;
            } else if (!isValidPhone(phone.value)) {
                showError('phone', 'Invalid phone number format.');
                valid = false;
            }
        }

        // Gender
        const gender = document.getElementById('gender');
        if (gender && gender.value === '') {
            showError('gender', 'Please select a gender.');
            valid = false;
        }

        // DOB
        const dob = document.getElementById('dob');
        if (dob) {
            if (dob.value === '') {
                showError('dob', 'Date of birth is required.');
                valid = false;
            } else {
                const d = new Date(dob.value);
                if (isNaN(d.getTime())) {
                    showError('dob', 'Invalid date of birth.');
                    valid = false;
                } else if (d >= new Date()) {
                    showError('dob', 'Date of birth cannot be in the future.');
                    valid = false;
                }
            }
        }

        // Status
        const status = document.getElementById('status');
        if (status && status.value === '') {
            showError('status', 'Status is required.');
            valid = false;
        }

        // Image (only required on add form)
        const profile_image = document.getElementById('profile_image');
        if (profile_image && profile_image.dataset.required === 'true') {
            if (profile_image.files.length === 0) {
                showError('profile_image', 'Profile image is required.');
                valid = false;
            } else {
                const file = profile_image.files[0];
                const ext  = file.name.split('.').pop().toLowerCase();
                if (!['jpg','jpeg','png'].includes(ext)) {
                    showError('profile_image', 'Only jpg, jpeg, and png files are allowed.');
                    valid = false;
                } else if (file.size > 5 * 1024 * 1024) {
                    showError('profile_image', 'Image must not exceed 5 MB.');
                    valid = false;
                }
            }
        } else if (profile_image && profile_image.files.length > 0) {
            const file = profile_image.files[0];
            const ext  = file.name.split('.').pop().toLowerCase();
            if (!['jpg','jpeg','png'].includes(ext)) {
                showError('profile_image', 'Only jpg, jpeg, and png files are allowed.');
                valid = false;
            } else if (file.size > 5 * 1024 * 1024) {
                showError('profile_image', 'Image must not exceed 5 MB.');
                valid = false;
            }
        }

        if (!valid) e.preventDefault();
    });
}

// ── Profile update form ──────────────────────────────────────
const profileForm = document.getElementById('profileForm');
if (profileForm) {
    profileForm.addEventListener('submit', function (e) {
        clearErrors('profileForm');
        let valid = true;

        const phone = document.getElementById('phone');
        if (phone) {
            if (phone.value.trim() === '') {
                showError('phone', 'Phone number is required.');
                valid = false;
            } else if (!isValidPhone(phone.value)) {
                showError('phone', 'Invalid phone number format.');
                valid = false;
            }
        }

        const address = document.getElementById('address');
        if (address && address.value.trim() === '') {
            showError('address', 'Address is required.');
            valid = false;
        }

        const profile_image = document.getElementById('profile_image');
        if (profile_image && profile_image.files.length > 0) {
            const file = profile_image.files[0];
            const ext  = file.name.split('.').pop().toLowerCase();
            if (!['jpg','jpeg','png'].includes(ext)) {
                showError('profile_image', 'Only jpg, jpeg, and png files are allowed.');
                valid = false;
            } else if (file.size > 5 * 1024 * 1024) {
                showError('profile_image', 'Image must not exceed 5 MB.');
                valid = false;
            }
        }

        if (!valid) e.preventDefault();
    });
}

// ── Live image preview ────────────────────────────────────────
const imageInput = document.getElementById('profile_image');
const imagePreview = document.getElementById('imagePreview');
if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function (ev) {
                imagePreview.src = ev.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}
