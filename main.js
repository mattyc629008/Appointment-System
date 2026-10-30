// js/main.js

// ─── FAQ Accordion ───
document.querySelectorAll('.faq-question').forEach(q => {
    q.addEventListener('click', () => {
        const ans = q.nextElementSibling;
        const isOpen = q.classList.contains('open');
        document.querySelectorAll('.faq-question').forEach(x => {
            x.classList.remove('open');
            x.nextElementSibling.classList.remove('open');
        });
        if (!isOpen) {
            q.classList.add('open');
            ans.classList.add('open');
        }
    });
});

// ─── Filter toggles ───
document.querySelectorAll('.filter-section h4').forEach(h => {
    h.addEventListener('click', () => {
        const content = h.nextElementSibling;
        if (content) content.style.display = content.style.display === 'none' ? '' : 'none';
    });
});

// ─── Toggle switch ───
document.querySelectorAll('.toggle').forEach(t => {
    t.addEventListener('click', () => {
        t.classList.toggle('on');
        const input = document.getElementById(t.dataset.for);
        if (input) input.value = t.classList.contains('on') ? '1' : '0';
    });
});

// ─── Appointment Type Cards ───
document.querySelectorAll('.appt-type-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.appt-type-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const input = document.getElementById('appointment_type');
        if (input) input.value = card.dataset.type;
    });
});

// ─── Flash alert auto-hide ───
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    });
}, 4000);

// ─── Mobile nav toggle ───
const mobileToggle = document.getElementById('mobileToggle');
if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        document.querySelector('nav').classList.toggle('open');
    });
}
