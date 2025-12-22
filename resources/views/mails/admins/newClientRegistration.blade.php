<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Customer Registration Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #dddddd;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: #ffffff;
            padding: 10px 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .content h1 {
            font-size: 24px;
            color: #333333;
        }
        .content p {
            font-size: 16px;
            color: #666666;
            line-height: 1.5;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #999999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Customer Registration</h2>
        </div>
        <div class="content">
            <h1>New Customer Registered</h1>
            <p>Dear Administrator,</p>
            <p>We are pleased to inform you that a new customer has submitted their registration form. Please find the details below:</p>
            <p><strong>Name:</strong> {{$user['name']}}</p>
            <p><strong>Email:</strong> {{$user['email']}}</p>
            <p><strong>Phone Number:</strong> {{$user['phone']}}</p>
            <p>Please contact the customer as soon as possible to verify their information and provide further assistance.</p>
            <p>Thank you.</p>
        </div>
        <div class="footer">
            <p>&copy; {{date('Y')}} Xpertbot. All rights reserved.</p>
        </div>
    </div>
</body>
</html>