<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
       <style>
        body {
            background-color: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            flex: 1;
            padding: 20px;
        }

        .main-header,
        .main-footer {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 10px 20px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
            text-align: center;
        }

        .info-box {
            display: flex;
            align-items: center;
            background-color: #fff;
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
            border-radius: .25rem;
            margin-bottom: 1rem;
            min-height: 80px;
            padding: .5rem;
        }

        .info-box .info-box-icon {
            border-radius: .25rem;
            align-items: center;
            display: flex;
            font-size: 2.5rem;
            justify-content: center;
            text-align: center;
            width: 70px;
            background-color: rgba(0, 0, 0, .1);
            color: #fff;
        }

        .info-box .info-box-content {
            flex: 1;
            padding: 0 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .info-box .info-box-text {
            display: block;
            font-size: .875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .info-box .info-box-number {
            display: block;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .table {
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
        }

        .table thead th {
            background-color: #007bff;
            color: white;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, .05);
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, .1);
        }

        .btn-action {
            margin-right: 5px;
        }

        .badge-estado {
            font-size: 0.85em;
            padding: 0.35em 0.65em;
        }

        @media (max-width: 767.98px) {
            .info-box .info-box-icon {
                font-size: 1.5rem;
                width: 50px;
            }

            .info-box .info-box-number {
                font-size: 1.2rem;
            }

            .table-responsive {
                font-size: 0.95rem;
            }

            .card-title {
                font-size: 1.1rem;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .table td, .table th {
                white-space: nowrap;
            }
        }
    </style>
</head>