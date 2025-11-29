function toggleCart() {
    document.getElementById('cart-slide').classList.toggle('open');
}

function loadCart() {
    fetch('view_cart.php')
    .then(res => res.json())
    .then(cart => {
        const cartItems = document.getElementById('cart-items');
        const cartCount = document.getElementById('cart-count');
        let total = 0;
        cartItems.innerHTML = '';

        cart.forEach(item => {
            total += parseFloat(item.totalPrice);
            const div = document.createElement('div');
            div.classList.add('cart-item');
            div.innerHTML = `
                <img src="${item.foodImage}" alt="${item.foodName}">
                <div class="cart-item-info">
                    <h4>${item.foodName}</h4>
                    <p>$${item.price}</p>
                    <input type="number" value="${item.quantity}" min="1" onchange="updateCart(${item.cartId}, this.value)">
                </div>
                <span class="remove-btn" onclick="removeCart(${item.cartId})">×</span>
            `;
            cartItems.appendChild(div);
        });

        cartCount && (cartCount.textContent = cart.length);
        document.getElementById('cart-total').textContent = total.toFixed(2);
    });
}

function addToCart(foodId, foodName, foodImage, price, quantity=1) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `foodId=${foodId}&foodName=${foodName}&foodImage=${foodImage}&price=${price}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(() => loadCart());
}

function updateCart(cartId, quantity) {
    fetch('update_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cartId=${cartId}&quantity=${quantity}`
    })
    .then(res => res.json())
    .then(() => loadCart());
}

function removeCart(cartId) {
    fetch('remove_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `cartId=${cartId}`
    })
    .then(res => res.json())
    .then(() => loadCart());
}

function checkout() {
    alert('Proceed to checkout page...');
}

document.addEventListener('DOMContentLoaded', loadCart);
