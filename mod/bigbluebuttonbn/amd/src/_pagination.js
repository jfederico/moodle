// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Pagination module for the recordings table.
 *
 * @module     mod_bigbluebuttonbn/_pagination
 * @copyright  2025 Blindside Networks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import { logMessage } from './_utils';

/**
 * Initializes pagination functionality for the recordings table.
 */
export const setupPagination = () => {
    logMessage("Initializing pagination...");

    const tableContainer = document.querySelector(".mod_bigbluebuttonbn_recordings_table_plain2");
    if (!tableContainer) {
        logMessage("Table container not found!");
        return;
    }

    const rows = Array.from(tableContainer.querySelectorAll(".row.mb-3.align-items-center"));

    // Select pagination buttons.
    const firstPageBtn = document.getElementById("firstPage");
    const prevPageBtn = document.getElementById("prevPage");
    const nextPageBtn = document.getElementById("nextPage");
    const lastPageBtn = document.getElementById("lastPage");
    const pageSelect = document.getElementById("pageSelect");

    if (!firstPageBtn || !prevPageBtn || !nextPageBtn || !lastPageBtn || !pageSelect) {
        logMessage("Pagination buttons not found!");
        return;
    }

    const itemsPerPage = 10;
    let currentPage = 1;
    let totalPages = Math.ceil(rows.length / itemsPerPage);

    /**
     * Updates the visibility of table rows based on the selected page.
     * @param {number} page - The current page to display.
     */
    function renderTable(page) {
        rows.forEach((row, index) => {
            if (index >= (page - 1) * itemsPerPage && index < page * itemsPerPage) {
                row.style.display = "flex";
            } else {
                row.style.display = "none";
            }
        });
    }

    /**
     * Updates pagination buttons and dropdown.
     */
    function updatePaginationControls() {
        pageSelect.innerHTML = "";
        for (let i = 1; i <= totalPages; i++) {
            let option = document.createElement("option");
            option.value = i;
            option.textContent = `Page ${i}`;
            if (i === currentPage) {
                option.selected = true;
            }
            pageSelect.appendChild(option);
        }

        firstPageBtn.disabled = (currentPage === 1);
        prevPageBtn.disabled = (currentPage === 1);
        nextPageBtn.disabled = (currentPage === totalPages);
        lastPageBtn.disabled = (currentPage === totalPages);
    }

    firstPageBtn.addEventListener("click", () => {
        currentPage = 1;
        renderTable(currentPage);
        updatePaginationControls();
    });

    prevPageBtn.addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable(currentPage);
            updatePaginationControls();
        }
    });

    nextPageBtn.addEventListener("click", () => {
        if (currentPage < totalPages) {
            currentPage++;
            renderTable(currentPage);
            updatePaginationControls();
        }
    });

    lastPageBtn.addEventListener("click", () => {
        currentPage = totalPages;
        renderTable(currentPage);
        updatePaginationControls();
    });

    pageSelect.addEventListener("change", (e) => {
        currentPage = parseInt(e.target.value, 10);
        renderTable(currentPage);
        updatePaginationControls();
    });

    renderTable(currentPage);
    updatePaginationControls();
};
