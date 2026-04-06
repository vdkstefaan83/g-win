// G-Win Frontend JavaScript


// Add to cart function (used across shop pages)
async function addToCart(productId, quantity = 1) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_csrf_token"]')?.value;

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('_csrf_token', csrfToken);

    try {
        const res = await fetch('/api/cart/add', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            updateCartCount(data.cart_count);
            showNotification('Product toegevoegd aan winkelwagen!');
        } else {
            showNotification(data.error || 'Er is een fout opgetreden.', 'error');
        }
    } catch (e) {
        showNotification('Er is een fout opgetreden.', 'error');
    }
}

// Update cart count badge
function updateCartCount(count) {
    const badge = document.getElementById('cart-count');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

// Simple notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white transition-all transform ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
