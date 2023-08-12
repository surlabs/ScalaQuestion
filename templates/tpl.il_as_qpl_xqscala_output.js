// JavaScript para interactividad
document.querySelectorAll('.scala-table input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', (event) => {
        // Remove any existing highlighted rows
        document.querySelectorAll('.scala-table tr').forEach(row => {
            row.style.backgroundColor = "transparent";
        });
        // Highlight the selected row
        event.target.closest('tr').style.backgroundColor = "rgba(76, 101, 134, 0.1)";
    });
});