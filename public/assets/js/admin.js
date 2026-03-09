// G-Win Admin JavaScript

// Initialize Quill editors
document.addEventListener('DOMContentLoaded', function() {
    initQuillEditors();
});

function initQuillEditors() {
    document.querySelectorAll('[data-quill]').forEach(function(container) {
        const targetField = container.dataset.quillTarget;
        const hiddenField = document.querySelector(targetField);

        const quill = new Quill(container, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });

        // Set initial content
        if (hiddenField && hiddenField.value) {
            quill.root.innerHTML = hiddenField.value;
        }

        // Sync on form submit
        const form = container.closest('form');
        if (form && hiddenField) {
            form.addEventListener('submit', function() {
                hiddenField.value = quill.root.innerHTML;
            });
        }
    });
}

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Weet u zeker dat u dit wilt verwijderen?');
}

// AJAX helper for admin filtering
async function adminFilter(url, params) {
    const queryString = new URLSearchParams(params).toString();
    const response = await fetch(`${url}?${queryString}`);
    return response.json();
}
