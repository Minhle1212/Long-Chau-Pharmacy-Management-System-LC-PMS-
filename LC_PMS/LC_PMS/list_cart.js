let listCart = [];

function checkCart() {
    let cartData = localStorage.getItem('list-cart');
    listCart = cartData ? JSON.parse(cartData) : [];
}

checkCart();


document.querySelectorAll('input[name="fulfillment"]').forEach(radio => {
  radio.addEventListener('change', e => {
    const del = document.getElementById('form-delivery');
    const pick = document.getElementById('form-pickup');
    if (e.target.value === 'delivery') {
      del.style.display = '';
      pick.style.display = 'none';
    } else {
      del.style.display = 'none';
      pick.style.display = '';
    }
  });
});


document.querySelectorAll('input[name="fulfillment"]').forEach(radio => {
  radio.addEventListener('change', e => {
    const delForm = document.getElementById('form-delivery');
    const pickForm = document.getElementById('form-pickup');
    const isDelivery = e.target.value === 'delivery';

    // Toggle visibility
    delForm.style.display  = isDelivery ? '' : 'none';
    pickForm.style.display = isDelivery ? 'none' : '';

    delForm.querySelectorAll('input').forEach(i => i.disabled = !isDelivery);
    pickForm.querySelectorAll('input,select').forEach(i => i.disabled = isDelivery);
  });
});


document.querySelectorAll('input[name="payment_method"]').forEach(radio=>{
    radio.addEventListener('change',e=>{
      document.getElementById('card-details').style.display =
        e.target.value==='card' ? '' : 'none';
      // make the field required only when visible
      document.getElementById('card_number').required =
        e.target.value==='card';
    });
  });



  