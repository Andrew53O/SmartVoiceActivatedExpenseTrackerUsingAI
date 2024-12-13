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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
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
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
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
        }

        #dateFilterOptions {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            padding: 12px;
            z-index: 1;
            border: 1px solid #ddd;
            border-radius: 8px;
            top: 40px;
        }

        #dateFilterOptions a {
            color: black;
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            cursor: pointer;
        }

        #dateFilterOptions a:hover {
            background-color: #f1f1f1;
        }

        .date-range-inputs {
            display: none;
            margin-top: 10px;
        }

        .date-range-inputs input {
            padding: 6px;
            margin-right: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Inter', sans-serif;
            width: 120px;
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
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Voice-Based Accounting Dashboard</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div id="filterContainer">
            <label for="categoryFilter">Category:</label>
            <select id="categoryFilter">
                <option value="">All</option>
                <option value="breakfast">Breakfast</option>
                <option value="lunch">Lunch</option>
                <option value="dinner">Dinner</option>
                <option value="snack">Snack</option>
            </select>

            <label for="dateFilterBtn">Date:</label>
            <button id="dateFilterBtn">All Time</button>
            <div id="dateFilterOptions">
                <a data-value="all">All Time</a>
                <a data-value="today">Today</a>
                <a data-value="week">Week</a>
                <a data-value="month">Month</a>
                <a data-value="year">Year</a>
                <a data-value="day">Select Day</a>
                <a data-value="range">Select Range</a>
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
                        <th>ID</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Cost (NTD)</th>
                        <th>DateTime</th>
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

    <script>
        $(document).ready(function() {
            let table = $('#accounting').DataTable({
                "ajax": {
                    "url": "fetch_data.php",
                    "dataSrc": ""
                },
                "columns": [{
                        "data": "aid"
                    },
                    {
                        "data": "item"
                    },
                    {
                        "data": "category"
                    },
                    {
                        "data": "cost"
                    },
                    {
                        "data": "createdOn"
                    }
                ]
            });

            // Variables to keep track of date filters
            var dateFilterOption = 'all';
            var startDate = '';
            var endDate = '';

            // Initialize date picker inputs
            $("#startDate, #endDate").datepicker({ dateFormat: 'yy-mm-dd' });

            // Toggle the date filter options when the button is clicked
            $("#dateFilterBtn").on('click', function() {
                $("#dateFilterOptions").slideToggle('fast');
            });

            // Handle date filter option selection
            $("#dateFilterOptions a").on('click', function() {
                var value = $(this).data('value');
                $("#dateFilterOptions").hide();
                
                // Format the button text based on selection
                let buttonText = '';
                switch(value) {
                    case 'today':
                        buttonText = 'Today (' + moment().format('MMMM D') + ')';
                        break;
                    case 'week':
                        buttonText = 'This Week (' + moment().startOf('week').format('MMM D') + 
                                    ' - ' + moment().endOf('week').format('MMM D') + ')';
                        break;
                    case 'month':
                        buttonText = 'This Month (' + moment().format('MMMM YYYY') + ')';
                        break;
                    case 'year':
                        buttonText = 'This Year (' + moment().format('YYYY') + ')';
                        break;
                    default:
                        buttonText = $(this).text();
                }
                
                $("#dateFilterBtn").text(buttonText);
                dateFilterOption = value;

                if (value === 'range' || value === 'day') {
                    $(".date-range-inputs").show();
                    if (value === 'day') {
                        $("#endDate").hide();
                        $("#startDate").attr('placeholder', 'Select Date');
                    } else {
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
                    let rowCategory = data[2];
                    if (category && rowCategory !== category) {
                        return false;
                    }

                    let rowDate = data[4].split(' ')[0];
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
            }

            function fetchChartData() {
                $.ajax({
                    url: 'fetch_data.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
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
                    error: function(error) {
                        console.log("Error fetching data for chart:", error);
                    }
                });
            }

            // Initial chart load
            fetchChartData();
        });
    </script>
</body>

</html>