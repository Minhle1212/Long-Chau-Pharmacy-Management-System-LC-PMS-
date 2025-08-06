

let listCart = [];

function checkCart() {
    let cartData = localStorage.getItem('list-cart');
    listCart = cartData ? JSON.parse(cartData) : [];
}

checkCart();

function addCart($skuProduct) {
    let productsCopy = JSON.parse(JSON.stringify(products));

    let existingProduct = listCart.find(product => product && product.sku === $skuProduct);

    if (!existingProduct) {
        let newProduct = productsCopy.find(product => product.sku === $skuProduct);
        newProduct.quantity = 1;
        listCart.push(newProduct);
    } else {
        existingProduct.quantity++;
    }

    localStorage.setItem('list-cart', JSON.stringify(listCart));

    addCartToHTML();
}

addCartToHTML();
function addCartToHTML(){
    // clear data default
    let listCartHTML = document.querySelector('.list-cart');
    listCartHTML.innerHTML = '';

    let totalHTML = document.querySelector('.total-quantity');
    let totalQuantity = 0;
    let totalPriceHTML = document.querySelector('.total-price');
    let totalPrice = 0;
    let totalQuantityHeader = document.querySelector ('.total-quantity-btn');
    // if has product in Cart
    if(listCart){
        listCart.forEach(product => {
            if(product){
                let newCart = document.createElement('div');
                newCart.classList.add('cart-item');
                newCart.innerHTML =     

                      ` <img src="${product.image_path}" alt="cart-item">
                       <div class="item-content">
                           <div class="cart-item-name">${product.name}</div>
                           <div class="cart-item-quantity">
                               QTY:
                               <span class="value">${product.quantity}</span>
                           </div>
                           <div class="cart-item-price">$${product.price}</div>
                       </div>
                       <div class="quantity">
                           <button onclick="changeQuantity('${product.sku}', '-')">-</button>
                           <button onclick="changeQuantity('${product.sku}', '+')">+</button>
                       </div>
                      `
                    ;
                listCartHTML.appendChild(newCart);
                totalQuantity = totalQuantity + product.quantity;
                totalPrice = totalPrice + product.price * product.quantity;
            }
        })
    }
    totalHTML.innerText            = totalQuantity;
    totalQuantityHeader.innerText  = totalQuantity;
    totalPriceHTML.innerText = '$' + totalPrice;
}


function changeQuantity($skuProduct, $type) {
  switch ($type) {
      case '+':
          listCart.forEach(product => {
              if (product && product.sku === $skuProduct) {
                  product.quantity++;
              }
          });
          break;
      case '-':
          listCart.forEach(product => {
              if (product && product.sku === $skuProduct) {
                  product.quantity--;

                  if (product.quantity <= 0) {
                      listCart = listCart.filter(p => p && p.sku !== $skuProduct);
                  }
              }
          });
          break;
      default:
          break;
  }

  localStorage.setItem('list-cart', JSON.stringify(listCart));
  addCartToHTML();
}
