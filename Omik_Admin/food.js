// Sample food items; later fetch from backend
const foods = [
  {
    foodName: 'Cheese Pizza',
    description: 'Good',
    price: 1000.00,
    status: 'have',
    image: 'F:/MAD CW/WhatsApp Image 2025-09-07 at 13.14.06_0dae879b.jpg'
  },
  {
    foodName: 'Veg Burger',
    description: 'Fresh and tasty',
    price: 500.00,
    status: 'have',
    image: 'F:/MAD CW/veg_burger.jpg'
  }
];

// Display food cards
const foodDashboard = document.getElementById('foodDashboard');

foods.forEach(food => {
  const card = document.createElement('div');
  card.className = 'food-card';
  card.innerHTML = `
    <img src="${food.image}" alt="${food.foodName}">
    <div class="info">
      <h3>${food.foodName}</h3>
      <p>${food.description}</p>
      <span>Rs. ${food.price}</span>
    </div>
  `;
  foodDashboard.appendChild(card);
});

// Dashboard navigation function
function openPage(action) {
  alert(`Redirecting to ${action} page...`);
}
