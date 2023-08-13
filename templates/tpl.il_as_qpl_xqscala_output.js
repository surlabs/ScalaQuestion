document.querySelectorAll('.scala-table .row:not(:first-child) .cell').forEach(cell => {
    cell.addEventListener('mouseenter', function() {
        // Highlight the row
        cell.parentElement.classList.add('hover-row');

        // Highlight the column
        const colIndex = Array.from(cell.parentNode.children).indexOf(cell);
        document.querySelectorAll('.scala-table .row:not(:first-child)').forEach(row => {
            if (row.children[colIndex]) {
                row.children[colIndex].classList.add('hover-col');
            }
        });
    });

    cell.addEventListener('mouseleave', function() {
        // Remove highlight from the row
        cell.parentElement.classList.remove('hover-row');

        // Remove highlight from the column
        const colIndex = Array.from(cell.parentNode.children).indexOf(cell);
        document.querySelectorAll('.scala-table .row:not(:first-child)').forEach(row => {
            if (row.children[colIndex]) {
                row.children[colIndex].classList.remove('hover-col');
            }
        });
    });
});
