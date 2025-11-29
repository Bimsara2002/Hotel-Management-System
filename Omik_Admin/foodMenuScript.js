document.addEventListener("DOMContentLoaded", loadFoods);

function loadFoods() {
    fetch('get_food.php')
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('foodTableBody');
            tbody.innerHTML = '';
            data.forEach(food => {
                const row = `
                    <tr>
                        <td>${food.foodId}</td>
                        <td>${food.foodName}</td>
                        <td>${food.category}</td>
                        <td>${food.price || '-'}</td>
                        <td>${food.status}</td>
                        <td><img src="${food.image}" width="50" height="50"></td>
                        <td><button onclick="editFood(${food.foodId})">Edit</button></td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        });
}

function editFood(foodId) {
    fetch('get_food_sizes.php?foodId=' + foodId)
        .then(res => res.json())
        .then(sizes => {
            document.getElementById('foodId').value = foodId;
            // Load food info (could use get_food.php?foodId=)
            alert("Now you can edit the selected food item.");
        });
}

document.getElementById('addSizeBtn').addEventListener('click', () => {
    const container = document.getElementById('sizesContainer');
    const sizeRow = document.createElement('div');
    sizeRow.classList.add('size-row');
    sizeRow.innerHTML = `
        <input type="text" placeholder="Size (e.g. Small)" name="sizes[size][]" required>
        <input type="number" placeholder="Price" name="sizes[price][]" step="0.01" required>
        <button type="button" onclick="this.parentElement.remove()">Remove</button>`;
    container.appendChild(sizeRow);
});

document.getElementById('foodForm').addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    fetch('save_food.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(alert)
    .then(() => {
        e.target.reset();
        loadFoods();
    });
});
