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
        }

    </style>
</head>
<body>
<div>
    <?php include("expertrec-page-header.php") ?>
</div>
<div class="expertrec-form">
    <h2>Use ExpertRec's WebApp to make advanced changes</h2>
    <?php $exp_eng = get_option('expertrec_engine'); ?>
    <div class="exp-field" style="padding-bottom: 68px;">
        <a href="<?php echo ($exp_eng == 'db') ? "https://cse.expertrec.com/ecom/ui-customization/advanced/custom-css-editor?fr=wp_plugin" : "https://cse.expertrec.com/csedashboard/looknfeel/basic?fr=wp_plugin" ?>"
           target="_blank">
            <input class="expertrec-align-center" type="submit" value="UI Customization"
                   style="width: 173px; margin-top: 24px; margin-bottom: 24px;">
        </a>
    </div>
    <?php if ($exp_eng != 'db') { ?>
        <div class="exp-field" style="padding-bottom: 68px;">
            <a href="https://cse.expertrec.com/csedashboard/searchconfig/weights?fr=wp_plugin" target="_blank">
                <input class="expertrec-align-center" type="submit" value="Search Ranking"
                       style="width: 173px; margin-top: 24px; margin-bottom: 24px;">
            </a>
        </div>
    <?php } ?>
    <div class="exp-field" style="padding-bottom: 68px;">
        <a href="<?php echo ($exp_eng == 'db') ? "https://cse.expertrec.com/ecom/analytics/overview?fr=wp_plugin" : "https://cse.expertrec.com/csedashboard/analytics/search-analytics?fr=wp_plugin" ?>"
           target="_blank">
            <input class="expertrec-align-center" type="submit" value="Analytics"
                   style="width: 173px; margin-top: 24px; margin-bottom: 24px;">
        </a>
    </div>
    <div class="exp-field" style="padding-bottom: 68px;">
        <a href="<?php echo ($exp_eng == 'db') ? "https://cse.expertrec.com/ecom/script-hooks/pre-init?fr=wp_plugin" : "https://cse.expertrec.com/csedashboard/scripthooks/prescript?fr=wp_plugin" ?>"
           target="_blank">
            <input class="expertrec-align-center" type="submit" value="Custom Scripts"
                   style="width: 173px; margin-top: 24px; margin-bottom: 24px;">
        </a>
    </div>
</div>
<div>
    <?php include("expertrec-page-footer.php") ?>
</div>
<div>
    <?php do_action('er/debug', 'In Expertrec advance page') ?>
</div>
</body>
</html>