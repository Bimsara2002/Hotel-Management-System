function loadSection(section) {
    const content = document.getElementById("content-area");
    content.innerHTML = `<p>Loading ${section}...</p>`;

    fetch(`includes/generalManagerDaata.php?section=${section}`)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(err => {
            content.innerHTML = `<p style="color:red;">Error loading data.</p>`;
            console.error(err);
        });
}
