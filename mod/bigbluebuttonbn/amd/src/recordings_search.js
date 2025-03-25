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
 * Search filter for recordings table.
 *
 * @module     mod_bigbluebuttonbn/recordings_search
 * @copyright  2025 Blindside Networks
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint no-console: "off" */

/**
 * Filters the recordings table by text input across all columns.
 */
export const setupSearch = () => {
    console.log("[recordings_search] setupSearch initialized");

    const searchInput = document.getElementById("recordings-search-input");
    const searchButton = document.getElementById("recordings-search-button");
    const tableContainer = document.querySelector(".mod_bigbluebuttonbn_recordings_table");

    if (!searchInput || !searchButton || !tableContainer) {
        console.warn("Search not initialized: missing elements");
        return;
    }

    const rows = Array.from(tableContainer.querySelectorAll(".row.mb-3.align-items-center"));

    const filterRows = () => {
        const query = searchInput.value.trim().toLowerCase();
        if (!query) {
            rows.forEach(row => {
                row.style.display = "flex";
            });
            return;
        }

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? "flex" : "none";
        });
    };

    searchButton.addEventListener("click", () => {
        console.log("[recordings_search] search button clicked");
        filterRows();
    });

    searchInput.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            filterRows();
        }
    });
};