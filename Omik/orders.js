document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const tableRows = document.querySelectorAll("#ordersTable tbody tr");

  function filterOrders() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value.toLowerCase();

    tableRows.forEach(row => {
      const food = row.children[1].textContent.toLowerCase();
      const paymentType = row.children[5].textContent.toLowerCase();
      const orderStatus = row.children[8].textContent.toLowerCase();

      const matchesSearch = food.includes(searchTerm) || paymentType.includes(searchTerm);
      const matchesStatus = !statusValue || orderStatus.includes(statusValue);

      row.style.display = matchesSearch && matchesStatus ? "" : "none";
    });
  }

  searchInput.addEventListener("keyup", filterOrders);
  statusFilter.addEventListener("change", filterOrders);
});
