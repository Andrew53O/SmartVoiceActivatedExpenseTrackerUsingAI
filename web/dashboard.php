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
        }

        #filterContainer select,
        #filterContainer input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
            font-family: 'Inter', sans-serif;
        }

        .dataTables_wrapper {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            width: 100%;
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

            <label for="dateFilter">Date:</label>
            <input type="date" id="dateFilter">
        </div>

        <div class="table-container">
            <table id="accounting" class="display">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Cost (NTD)</th>
                        <th>Timestamp</th>
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
                        "data": "id"
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
                        "data": "timestamp"
                    }
                ]
            });

            // Filter functionality
            $('#categoryFilter, #dateFilter').on('change', function() {
                table.draw();
                fetchChartData(); // Update chart based on filters
            });

            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    let category = $('#categoryFilter').val();
                    let date = $('#dateFilter').val();
                    let rowCategory = data[2]; // Category column
                    let rowDate = data[4].split(' ')[0]; // Timestamp column

                    if ((category === "" || rowCategory === category) &&
                        (date === "" || rowDate === date)) {
                        return true;
                    }
                    return false;
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
                    type: 'bar',
                    data: {
                        labels: categories.map(cat => cat.charAt(0).toUpperCase() + cat.slice(1)),
                        datasets: [{
                            label: 'Total Expenses (NTD)',
                            data: totals,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255,99,132,1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
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
                        // Apply current filters
                        let category = $('#categoryFilter').val();
                        let date = $('#dateFilter').val();
                        let filteredData = data.filter(record => {
                            return (category === "" || record.category === category) &&
                                (date === "" || record.timestamp.split(' ')[0] === date);
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