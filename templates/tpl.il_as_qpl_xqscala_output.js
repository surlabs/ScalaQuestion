// JavaScript for interactivity
document.querySelectorAll('.scala-table input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', (event) => {
        // remove existing highlighted rows
        document.querySelectorAll('.scala-table tr').forEach(row => {
            row.style.backgroundColor = "#fff";
        });
        // highlight the selected row
        event.target.closest('tr').style.backgroundColor = "#d8e1e8";
    });
});