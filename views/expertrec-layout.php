<!DOCTYPE html>
<html lang="en">
<head>
    <style type="text/css">

        .expertrec-h1 {
            font-size: 1.4em;
            padding-left: 15px;
            padding-bottom: 5px;
            padding-right: 30px;
            border-left: 3px solid #f8743b;
            border-bottom: 3px solid #f8743b;
            margin-bottom: 1.5em;
            letter-spacing: 3px;
            font-weight: 700;
        }

        .expertrec-form {
            display: block;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin: 100px auto;
        }

        .expertrec-label {
            display: block;
            margin-bottom: 1.5em;
        }

        .expertrec-label > input {
            display: none;
        }

        .expertrec-label > i {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
            border: 2px solid #f8743b;
            box-shadow: inset 0 0 0 4px #F1F0F2;
            transition: 0.25s;
        }

        .expertrec-label > span {
            display: inline-block;
            padding-bottom: 3px;
            border-bottom: 2px dotted #f8743b;
            letter-spacing: 3px;
            font-size: 1.2em;
        }

        .expertrec-label > input:checked + i {
            background: #f8743b;
        }

        .expertrec-label:hover {
            cursor: pointer;
        }

        #input_custom_result_page {
            margin-left: 34px;
            margin-bottom: 20px;
            margin-top: 20px;
        }

        .layout_type_outer {
            display: block;
            margin-bottom: 20px;
        }

        #search_path {
            background: #F1F0F2;
            display: block;
            border: none;
            border-bottom: 1px solid #ccc;
            width: 80%;
        }

        #query_parameter {
            background: #F1F0F2;
            display: block;
            border: none;
            border-bottom: 1px solid #ccc;
            width: 80%;
        }

    </style>
</head>
<body>
<div>
    <?php include("expertrec-page-header.php") ?>
    <form class="expertrec-form">
        <h1 class="expertrec-h1">Choose how the results are displayed</h1>
        <?php $options = get_option('expertrec_options');
        $template = $options['template'] ?>
        <label class="expertrec-label">
            <input class="expertrec-radio-btn" id="overlay" type="radio" name="template"
                   value="overlay" <?php echo ($template == 'overlay') ? "checked=checked" : ""; ?>
                   onclick="LayoutFormHideShowCheck()"/>
            <i></i>
            <span>Overlay results</span>
        </label>
        <label class="expertrec-label">
            <input class="expertrec-radio-btn" id="separate" type="radio" name="template"
                   value="separate" <?php echo ($template == 'separate') ? "checked=checked" : ""; ?>
                   onclick="LayoutFormHideShowCheck()"/>
            <i></i>
            <span>Use a separate results page</span>
        </label>
        <div class="exp-field padding-bottom--24">
            <img class="expertrec-loading-img" id="exp-layout-loading"
                 src="<?php echo plugin_dir_url(__DIR__) . 'assets/images/Spin-1s-200px.svg' ?>" alt='loading'
                 style="display: none;">
            <input class="expertrec-align-center" id="layout-update-btn" type="submit" name="submit" value="Update"
                   style="width: 173px; margin-top: 24px; margin-bottom: 24px;">
        </div>

        <div class="padding-top--48">
            <p>For advanced settings of Layout, Please visit our <a
                        href="https://cse.expertrec.com/ecom/ui-customization/layout" target="_blank"> dashboard</a></p>
        </div>
    </form>
</div>
<div>
    <?php include("expertrec-page-footer.php") ?>
</div>
<script type="text/javascript">
    function LayoutFormHideShowCheck() {
        if (document.getElementById('separate').checked) {
            document.getElementById('input_custom_result_page').style.display = 'block';
        } else {
            document.getElementById('input_custom_result_page').style.display = 'none';
        }
    }

    LayoutFormHideShowCheck()
</script>
<div>
    <?php do_action('er/debug', 'In Expertrec layout page') ?>
</div>
</body>
</html>