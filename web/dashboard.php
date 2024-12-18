<?php
// dashboard.php
session_start();
if (!isset($_SESSION['pid'])) {
    header("Location: login.php");
    exit();
}
require 'db.php';
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Voice Accounting</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <!-- Include Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .dashboard-container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .logout-btn {
            background: #ff4444;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }

        #filterContainer {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #filterContainer select,
        #filterContainer input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
            font-family: 'Inter', sans-serif;
        }

        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            table-layout: fixed;
            margin: 0 !important;
        }

        table.dataTable th,
        table.dataTable td {
            padding: 12px 8px;
            border-bottom: 1px solid #eee;
        }

        table.dataTable thead th {
            background: #f8f9fa;
            font-weight: 600;
            white-space: nowrap;
            position: relative;
        }

        /* Fix for duplicate sort icons */
        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting:after,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_asc:after,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting_desc:after {
            content: "" !important;
            display: none !important;
        }

        table.dataTable thead .sorting,
        table.dataTable thead .sorting_asc,
        table.dataTable thead .sorting_desc {
            background-image: none !important;
        }

        #chartContainer {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .dataTables_length select,
        .dataTables_filter input {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 0 5px;
        }

        .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .dataTables_paginate .paginate_button.current {
            background: #007bff;
            color: white !important;
            border-color: #007bff;
        }

        /* Styles for the date filter button and pop-up */
        #dateFilterBtn {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            background-color: white;
            position: relative;
            min-width: 200px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
        }

        #dateFilterBtn:after {
            content: '▼';
            font-size: 0.8em;
            margin-left: 8px;
        }

        #dateFilterOptions {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 250px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            padding: 8px 0;
            z-index: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            top: 40px;
        }

        #dateFilterOptions a {
            color: black;
            padding: 10px 16px;
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        #dateFilterOptions a span.desc {
            color: #666;
            font-size: 0.9em;
            margin-left: 8px;
        }

        #dateFilterOptions a:hover {
            background-color: #f1f1f1;
        }

        .date-range-inputs {
            display: none;
            margin-top: 10px;
            gap: 8px;
            align-items: center;
        }

        .date-range-inputs input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            width: 130px;
            font-size: 0.9em;
        }

        .date-range-inputs button {
            padding: 6px 12px;
            border: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .date-range-inputs button:hover {
            background-color: #45a049;
        }

        /* Flatpickr customization */
        .flatpickr-calendar {
            font-family: 'Inter', sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .edit-btn, .delete-btn, .save-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 2px;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .save-btn {
            background-color: #2196F3;
            color: white;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Voice-Based Accounting Dashboard</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div id="filterContainer">
            <div class="filter-group">
                <label for="categoryFilter">Category:</label>
                <select id="categoryFilter">
                    <option value="">All</option>
                    <option value="breakfast">Breakfast</option>
                    <option value="lunch">Lunch</option>
                    <option value="dinner">Dinner</option>
                    <option value="snack">Snack</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="dateFilterBtn">Date:</label>
                <button id="dateFilterBtn">All Time</button>
                <div id="dateFilterOptions">
                    <a data-value="all">All Time</a>
                    <a data-value="today">Today <span class="desc">(<?php echo date('M d'); ?>)</span></a>
                    <a data-value="week">This Week <span class="desc">(<?php echo date('M d', strtotime('monday this week')); ?> - <?php echo date('M d', strtotime('sunday this week')); ?>)</span></a>
                    <a data-value="month">This Month <span class="desc">(<?php echo date('F Y'); ?>)</span></a>
                    <a data-value="year">This Year <span class="desc">(<?php echo date('Y'); ?>)</span></a>
                    <a data-value="day">Select Day</a>
                    <a data-value="range">Select Range</a>
                </div>
            </div>

            <div class="date-range-inputs">
                <input type="text" id="startDate" placeholder="Start Date">
                <input type="text" id="endDate" placeholder="End Date">
                <button id="applyDateRange">Apply</button>
            </div>
        </div>

        <div class="table-container">
            <table id="accounting" class="display">
                <thead>
                    <tr>
                        <th>DateTime</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Cost (NTD)</th>
                        <th>Actions</th>  <!-- Add this column -->
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated here via JavaScript -->
                </tbody>
            </table>
        </div>

        <div id="chartContainer">
            <canvas id="expenseChart"></canvas>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Record</h2>
            <form id="editForm">
                <input type="hidden" id="editId">
                <div class="form-group">
                    <label for="editCategory">Category:</label>
                    <select id="editCategory" required>
                        <option value="breakfast">Breakfast</option>
                        <option value="lunch">Lunch</option>
                        <option value="dinner">Dinner</option>
                        <option value="snack">Snack</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editItem">Item:</label>
                    <input type="text" id="editItem" required>
                </div>
                <div class="form-group">
                    <label for="editCost">Cost:</label>
                    <input type="number" id="editCost" required>
                </div>
                <button type="submit" class="save-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Include Toastify JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable first
            let table = $('#accounting').DataTable({
                "processing": true,
                "ajax": {
                    "url": "fetch_data.php",
                    "dataSrc": function(json) {
                        // Add error handling for the response
                        if (json.error) {
                            console.error('Server error:', json.message);
                            return [];
                        }
                        return json || [];
                    }
                },
                "columns": [
                    { 
                        "data": "createdOn",
                        "render": function(data) {
                            return moment(data).format('YYYY-MM-DD HH:mm');
                        }
                    },
                    { "data": "category" },
                    { "data": "item" },
                    { 
                        "data": "cost",
                        "render": function(data) {
                            return parseFloat(data).toLocaleString();
                        }
                    },
                    {
                        "data": "aid",
                        "render": function(data, type, row) {
                            return `
                                <button class="edit-btn" onclick="editRecord(${data})">Edit</button>
                                <button class="delete-btn" onclick="deleteRecord(${data})">Delete</button>
                            `;
                        },
                        "orderable": false
                    }
                ],
                "order": [[0, "desc"]] // Sort by DateTime by default
            });

            // Add category filter change event
            $('#categoryFilter').on('change', function() {
                table.draw();
            });

            // Add error event handler
            table.on('error.dt', function(e, settings, techNote, message) {
                console.error('DataTable error:', message);
                alert('Error loading data. Please check console for details.');
            });

            // Debug DataTable initialization
            table.on('error.dt', function(e, settings, techNote, message) {
                console.log('DataTable error:', message);
            });

            // Variables to keep track of date filters
            var dateFilterOption = 'all';
            var startDate = '';
            var endDate = '';

            // Initialize modern date pickers
            const dateConfig = {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y",
                animate: true
            };

            const startDatePicker = flatpickr("#startDate", {
                ...dateConfig,
                onChange: function(selectedDates, dateStr) {
                    if (dateFilterOption === 'range') {
                        endDatePicker.set('minDate', dateStr);
                    }
                }
            });

            const endDatePicker = flatpickr("#endDate", {
                ...dateConfig,
                onChange: function(selectedDates, dateStr) {
                    if (dateFilterOption === 'range') {
                        startDatePicker.set('maxDate', dateStr);
                    }
                }
            });

            // Toggle the date filter options when the button is clicked
            $("#dateFilterBtn").on('click', function() {
                $("#dateFilterOptions").slideToggle('fast');
            });

            // Handle date filter option selection
            $("#dateFilterOptions a").on('click', function() {
                var value = $(this).data('value');
                $("#dateFilterOptions").hide();
                
                let buttonText = $(this).clone().children().remove().end().text().trim();
                let description = $(this).find('.desc').text() || '';
                
                $("#dateFilterBtn").text(buttonText + ' ' + description);
                dateFilterOption = value;

                if (value === 'range' || value === 'day') {
                    $(".date-range-inputs").show();
                    if (value === 'day') {
                        endDatePicker.destroy();
                        startDatePicker.set('mode', 'single');
                        $("#endDate").hide();
                        $("#startDate").attr('placeholder', 'Select Date');
                    } else {
                        startDatePicker.set('mode', 'single');
                        endDatePicker = flatpickr("#endDate", dateConfig);
                        $("#endDate").show();
                        $("#startDate").attr('placeholder', 'Start Date');
                    }
                } else {
                    $(".date-range-inputs").hide();
                    startDate = '';
                    endDate = '';
                    table.draw();
                    fetchChartData();
                }
            });

            // Apply date range filter
            $("#applyDateRange").on('click', function() {
                startDate = $("#startDate").val();
                endDate = $("#endDate").val() || startDate;
                
                let buttonText = dateFilterOption === 'day' 
                    ? 'Selected Day (' + moment(startDate).format('MMMM D, YYYY') + ')'
                    : 'Date Range (' + moment(startDate).format('MMM D') + 
                      ' - ' + moment(endDate).format('MMM D, YYYY') + ')';
                
                $("#dateFilterBtn").text(buttonText);
                table.draw();
                fetchChartData();
            });

            // Close the date filter options when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#dateFilterBtn, #dateFilterOptions').length) {
                    $("#dateFilterOptions").hide();
                }
            });

            // Clear existing search functions to prevent conflicts
            $.fn.dataTable.ext.search = [];

            // Ensure only one DataTable search function is defined
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    let category = $('#categoryFilter').val();
                    let rowCategory = data[1];
                    if (category && rowCategory !== category) {
                        return false;
                    }

                    let rowDate = data[0].split(' ')[0];
                    if (dateFilterOption === 'all') {
                        return true;
                    } else if (dateFilterOption === 'today') {
                        return rowDate === moment().format('YYYY-MM-DD');
                    } else if (dateFilterOption === 'week') {
                        return moment(rowDate).isSame(moment(), 'week');
                    } else if (dateFilterOption === 'month') {
                        return moment(rowDate).isSame(moment(), 'month');
                    } else if (dateFilterOption === 'year') {
                        return moment(rowDate).isSame(moment(), 'year');
                    } else if (dateFilterOption === 'day') {
                        return rowDate === startDate;
                    } else if (dateFilterOption === 'range') {
                        return moment(rowDate).isBetween(startDate, endDate, undefined, '[]');
                    }
                    return true;
                }
            );

            // Fetch data and create chart
            function createChart(data) {
                try {
                    let categories = ['breakfast', 'lunch', 'dinner', 'snack'];
                    let totals = [0, 0, 0, 0];

                    data.forEach(record => {
                        let index = categories.indexOf(record.category);
                        if (index !== -1) {
                            totals[index] += parseFloat(record.cost);
                        }
                    });

                    let ctx = document.getElementById('expenseChart').getContext('2d');
                    if (window.expenseChart instanceof Chart) {
                        window.expenseChart.destroy();
                    }
                    window.expenseChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: categories.map(cat => cat.charAt(0).toUpperCase() + cat.slice(1)),
                            datasets: [{
                                data: totals,
                                backgroundColor: ['#ff6384','#36a2eb','#ffce56','#4bc0c0']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                    console.log('Chart data:', data); // Debug log
                } catch (error) {
                    console.error('Chart creation error:', error);
                }
            }

            function fetchChartData() {
                $.ajax({
                    url: 'fetch_data.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        console.log('Fetched data:', data); // Debug log
                        let category = $('#categoryFilter').val();
                        let filteredData = data.filter(record => {
                            let recordDate = record.createdOn.split(' ')[0];
                            let withinDateRange = false;

                            if (dateFilterOption === 'all') {
                                withinDateRange = true;
                            } else if (dateFilterOption === 'today') {
                                withinDateRange = recordDate === moment().format('YYYY-MM-DD');
                            } else if (dateFilterOption === 'week') {
                                withinDateRange = moment(recordDate).isSame(moment(), 'week');
                            } else if (dateFilterOption === 'month') {
                                withinDateRange = moment(recordDate).isSame(moment(), 'month');
                            } else if (dateFilterOption === 'year') {
                                withinDateRange = moment(recordDate).isSame(moment(), 'year');
                            } else if (dateFilterOption === 'day') {
                                withinDateRange = recordDate === startDate;
                            } else if (dateFilterOption === 'range') {
                                withinDateRange = moment(recordDate).isBetween(startDate, endDate, undefined, '[]');
                            }

                            let matchesCategory = !category || record.category === category;
                            return withinDateRange && matchesCategory;
                        });
                        createChart(filteredData);
                    },
                    error: function(xhr, status, error) {
                        console.error('Fetch error:', error);
                        console.log('Status:', status);
                        console.log('Response:', xhr.responseText);
                    }
                });
            }

            // Initial chart load
            fetchChartData();

            // Update date descriptions dynamically
            function updateDateDescriptions() {
                let today = moment();
                let weekStart = moment().startOf('week');
                let weekEnd = moment().endOf('week');
                
                $('#dateFilterOptions a[data-value="today"] .desc').text(`(${today.format('MMM D')})`);
                $('#dateFilterOptions a[data-value="week"] .desc').text(`(${weekStart.format('MMM D')} - ${weekEnd.format('MMM D')})`);
                $('#dateFilterOptions a[data-value="month"] .desc').text(`(${today.format('MMMM YYYY')})`);
                $('#dateFilterOptions a[data-value="year"] .desc').text(`(${today.format('YYYY')})`);
            }

            // Call this function when document is ready
            updateDateDescriptions();

            // Function to show notifications
            function showNotification(message, bgColor = "#4CAF50") {
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: bgColor,
                }).showToast();
            }

            window.editRecord = function(id) {
                const row = table.row($(`button[onclick="editRecord(${id})"]`).closest('tr')).data();
                
                $('#editId').val(id);
                $('#editCategory').val(row.category);
                $('#editItem').val(row.item);
                $('#editCost').val(row.cost);
                
                $('#editModal').show();
            };

            window.deleteRecord = function(id) {
                if(confirm('Are you sure you want to delete this record?')) {
                    $.ajax({
                        url: 'delete_record.php',
                        method: 'POST',
                        data: { id: id },
                        success: function(response) {
                            if(response.success) {
                                table.ajax.reload();
                                fetchChartData();
                                // Show notification
                                showNotification('Record deleted successfully.', '#f44336');
                            } else {
                                alert('Error deleting record: ' + (response.message || 'Unknown error'));
                            }
                        },
                        error: function() {
                            alert('Error communicating with server');
                        }
                    });
                }
            };

            // Close modal when clicking on X or outside the modal
            $('.close').click(function() {
                $('#editModal').hide();
            });

            $(window).click(function(event) {
                if (event.target == $('#editModal')[0]) {
                    $('#editModal').hide();
                }
            });

            // Handle edit form submission
            $('#editForm').submit(function(e) {
                e.preventDefault();
                
                const data = {
                    id: $('#editId').val(),
                    category: $('#editCategory').val(),
                    item: $('#editItem').val(),
                    cost: $('#editCost').val()
                };

                $.ajax({
                    url: 'edit_record.php',
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        if(response.success) {
                            $('#editModal').hide();
                            table.ajax.reload();
                            fetchChartData();
                            // Show notification
                            showNotification('Record updated successfully.', '#2196F3');
                        } else {
                            alert('Error updating record: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Error communicating with server');
                    }
                });
            });

            // Keep track of the latest data timestamp
            var latestDataTimestamp = null;

            // Function to fetch the latest data timestamp
            function fetchLatestTimestamp() {
                $.ajax({
                    url: 'fetch_latest_timestamp.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (!latestDataTimestamp) {
                                // Initialize the latest timestamp
                                latestDataTimestamp = response.latestTimestamp;
                            } else if (response.latestTimestamp > latestDataTimestamp) {
                                // New data has arrived
                                latestDataTimestamp = response.latestTimestamp;
                                // Refresh the table and chart
                                table.ajax.reload();
                                fetchChartData();
                                // Show notification
                                showNotification('New data added.');
                            }
                        } else {
                            console.error('Failed to fetch latest timestamp:', response.message);
                        }
                    },
                    error: function() {
                        console.error('Error fetching latest timestamp');
                    }
                });
            }

            // Periodically check for new data every 30 seconds
            setInterval(fetchLatestTimestamp, 30000);

            // Initial fetch of the latest timestamp
            fetchLatestTimestamp();

            // ...existing code...
        });
    </script>

</body>

</html>