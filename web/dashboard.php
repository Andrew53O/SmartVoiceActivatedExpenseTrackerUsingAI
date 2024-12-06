<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user_id'])) {
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
    <!-- Include DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
        }
        #filterContainer {
            margin-bottom: 20px;
        }
        #filterContainer label {
            margin-right: 10px;
        }
        #chartContainer {
            width: 80%;
            margin: auto;
            margin-top: 40px;
        }
        .logout {
            float: right;
            margin-top: -60px;
        }
    </style>
</head>
<body>
    <h1>Voice-Based Accounting Dashboard</h1>
    <div class="logout">
        <a href="logout.php">Logout</a>
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

    <table id="accountingTable" class="display">
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

    <div id="chartContainer">
        <canvas id="expenseChart"></canvas>
    </div>

    <script>
        $(document).ready(function() {
            let table = $('#accountingTable').DataTable({
                "ajax": {
                    "url": "fetch_data.php",
                    "dataSrc": ""
                },
                "columns": [
                    { "data": "id" },
                    { "data": "item" },
                    { "data": "category" },
                    { "data": "cost" },
                    { "data": "timestamp" }
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
