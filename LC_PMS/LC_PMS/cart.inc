<!-- Cart -->
<div class="cart">
    <div class="cart-head-text">
        <p>Shopping Cart</p>
        <div class="total-quantity">
            0
        </div>
    </div>
    <div class="list-cart">
        <div class="cart-item">
            <img src="./images/LC001.png">
            <div class="item-content">
                <div class="cart-item-name">Panadol Extra</div>
                <div class="cart-item-quantity">
                    QTY:
                    <span class="value">1</span>
                </div>
                <div class="cart-item-price">$15.00</div>
            </div>
            <div class="quantity">
                <button>-</button>
                <button>+</button>
            </div>
        </div>
    </div>
    <div class="total">
        <p>Total:</p>
        <div class="total-price">
            $40.00
        </div>
    </div>
    <div class="buttons">
        <div class="close">CLOSE</div>
        <button class="check-out" id="btn-check-cart">
                CHECK CART
        </button>
    </div>
</div>
<script>
// CLICK TO REVEAL CART
let iconCart = document.querySelector('.icon-cart');
let cart = document.querySelector('.cart');
let close = document.querySelector('.close');

iconCart.addEventListener('click', function(){
    if(cart.style.right == '0'){
        cart.style.right = '100%';
    }else{
        cart.style.right = '0';
    }
})
close.addEventListener('click', function (){
    cart.style.right = '-100%';
})


// On “Check Cart” click:
document.getElementById('btn-check-cart').addEventListener('click', async () => {
    const r = await syncCartToServer();
    if (r.success) window.location = 'cart.php';
    else window.location = "login.php";
});


async function syncCartToServer() {
    const res = await fetch('sync_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
        items: listCart.map(p => ({ sku: p.sku, quantity: p.quantity }))
        })
    });
    return res.json();
}
</script>