<!DOCTYPE html>
<html lang="en">
<head>
    <style type="text/css">

        .expertrec-form {
            display: block;
            position: absolute;
            left: 45%;
            transform: translateX(-50%);
            margin: 100px auto;
            width: 28rem;
        }

        .expertrec-input {
            background: #F1F0F2 !important;
            display: block;
            border: none !important;
            border-bottom: 1px solid #ccc !important;
            width: inherit !important;
        }


    </style>
</head>
<body>
<div>
    <?php include("expertrec-page-header.php") ?>
</div>

    <div id="root"></div>
<!--    <script src="http://localhost:3000/static/js/bundle.js"></script>-->
<script defer="defer" src="https://wordpress.expertrec.com/static/js/main.js"></script><link href="https://wordpress.expertrec.com/static/css/main.css" rel="stylesheet">
<div>
    <?php include("expertrec-page-footer.php") ?>
</div>
<div>
    <?php do_action('er/debug', 'In Expertrec indexing page') ?>
</div>
</body>
</html>
