document.addEventListener("DOMContentLoaded", function() {
    let cells = document.querySelectorAll(".scala-table .cell");
    cells.forEach(function(cell) {
        cell.addEventListener("mouseenter", function() {
            let colIndex = cell.getAttribute("data-col-index");
            let rowIndex = cell.getAttribute("data-row-index");

            // Celda de encabezado de columna
            let headerCell = document.querySelector(".header-cell[data-col-index='" + colIndex + "']");
            // Celda de encabezado de fila
            let rowHeaderCell = cell.closest('.row').querySelector('.row-header-cell');

            cell.classList.add("highlighted");
            if (headerCell) headerCell.classList.add("highlighted");
            if (rowHeaderCell) rowHeaderCell.classList.add("highlighted");
        });

        cell.addEventListener("mouseleave", function() {
            let colIndex = cell.getAttribute("data-col-index");
            let rowIndex = cell.getAttribute("data-row-index");

            let headerCell = document.querySelector(".header-cell[data-col-index='" + colIndex + "']");
            let rowHeaderCell = cell.closest('.row').querySelector('.row-header-cell');

            cell.classList.remove("highlighted");
            if (headerCell) headerCell.classList.remove("highlighted");
            if (rowHeaderCell) rowHeaderCell.classList.remove("highlighted");
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    let radioButtons = document.querySelectorAll('.scala-table input[type="radio"]');

    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            // Primero, quitar la clase 'checked' de cualquier otra celda en la misma fila
            let row = this.closest('.row');
            let cells = row.querySelectorAll('.cell');
            cells.forEach(cell => {
                cell.classList.remove('checked');
            });

            // Luego, añadir la clase 'checked' al contenedor de la celda del radio button seleccionado
            let cellContainer = this.closest('.cell');
            if (this.checked) {
                cellContainer.classList.add('checked');
            }
        });
    });
});
