<!DOCTYPE html>
<html lang="en">
<body class="expertrec-plugin-body">
<style>
    /* If we neeed to display-in-line block there it will give the gap between the options so we are giving the font-size of parent element = 0 */
    /* https://stackoverflow.com/questions/18933611/display-inline-block-with-widths-as-percent */
    /* Display Inline-Block with Widths as Percent */
    .form-wrapper {
        font-size: 0;
    }
    .form-wrapper * {
        font-size: 15px;
    }
    .login-form, .signup-form {
        display: none;
    } 
    .signup-form, .login-form {
        min-height: 400px;
    }
    #sign-up:checked ~ .signup-form, #log-in:checked ~ .login-form {
        display: block;
    }
    input.checkbox[type=radio] {
        display: none;
    }
    .labels {
        display: inline-block;
        width: 50%;
        background-color: #b3ffdd;
        background: #b3ffdd;
        text-align: center;
        align-items: center;
        padding-top: 1rem;
        padding-bottom: 1rem;
        background: #e3e8ee;
        font-weight: bold;
        transition: 0.2s all;
    }
    input[type=radio]:checked + label {
        background: #f8743b;
    }
</style>
<div class="expertrec-login-root">
    <div class="expertrec-box-root expertrec-flex-flex expertrec-flex-direction--column"
         style="min-height: 100vh;flex-grow: 1;">
<!--        <div class="expertrec-loginbackground box-background--white padding-top--64">-->
<!--            <div class="expertrec-loginbackground-gridContainer">-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: top / start / 8 / end;">-->
<!--                    <div class="expertrec-box-root"-->
<!--                         style="background-image: linear-gradient(white 0%, rgb(247, 250, 252) 33%); flex-grow: 1;">-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 4 / 2 / auto / 5;">-->
<!--                    <div class="expertrec-box-root box-divider--light-all-2 animationLeftRight tans3s"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 6 / 5 / auto / 2;">-->
<!--                    <div class="expertrec-box-root box-background--gray100 animationRightLeft"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 7 / start / auto / 6;">-->
<!--                    <div class="expertrec-box-root box-background--orange"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 8 / 4 / auto / 6;">-->
<!--                    <div class="expertrec-box-root box-background--gray100 animationLeftRight tans3s"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 2 / 15 / auto / end;">-->
<!--                    <div class="expertrec-box-root box-divider--light-all-2 animationRightLeft tans4s"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 3 / 14 / auto / end;">-->
<!--                    <div class="expertrec-box-root box-background--orange animationRightLeft"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 4 / 17 / auto / 20;">-->
<!--                    <div class="expertrec-box-root box-background--gray100 animationRightLeft tans4s"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--                <div class="expertrec-box-root expertrec-flex-flex" style="grid-area: 5 / 14 / auto / 17;">-->
<!--                    <div class="expertrec-box-root box-divider--light-all-2 animationRightLeft tans3s"-->
<!--                         style="flex-grow: 1;"></div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
        <div class="expertrec-box-root padding-top--24 expertrec-flex-flex expertrec-flex-direction--column"
             style="flex-grow: 1; z-index: 9;">
            <div class="expertrec-box-root padding-top--48 padding-bottom--24 expertrec-flex-flex flex-justifyContent--center">
                <h1 class="exp"><a class="exp" href="https://www.expertrec.com/" target="_blank">WP Fastest Site Search</a></h1>
            </div>
            <div class="formbg-outer">
                <div class="expertrec-formbg">
                    <div class="formbg-inner padding-horizontal--48 form-wrapper ">
                        <input class="checkbox" type="radio" id="sign-up" name="er-tabs" checked/>
                        <label for="sign-up" class="labels">Create new account</label>
                        <input class="checkbox" type="radio" id="log-in" name="er-tabs"/>
                        <label for="log-in" class="labels">Already have account</label>
                        <div class="signup-form" >
                        <span class="exp padding-bottom--15"> </span>
                            <form class="exp">
                                <div class="exp-field padding-bottom--24">
                                    <label class="exp" for="crawl_site_url">Site URL</label>
                                    <input class="exp" type="crawl_site_url" name="crawl_site_url"
                                        placeholder="Enter Your Wordpress Site URL"
                                        value="<?php echo esc_attr(get_site_url()); ?>">
                                </div>
                                <?php include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                                $is_woocommerce = is_plugin_active('woocommerce/woocommerce.php'); ?>
                                <div class="exp-field padding-bottom--24" <?php if ($is_woocommerce) echo("hidden") ?>>
                                    <label class="exp" for="radio_btn" style="margin-bottom: 24px;">Select Indexing Option (<a
                                                style="color: blue;"
                                                href="https://blog.expertrec.com/which-indexing-to-use-in-expertrec/"
                                                target="_blank">Need help?</a>)</label>
                                    <section type="radio_btn">
                                        <div>
                                            <input type="radio" class="expertrec-radio-btn" id="dbWay" name="indexingWay"
                                                value="db" checked onclick="hideCrawlMsg()">
                                            <label class="radio-label exp-font--12" for="dbWay">
                                                <h2>Real Time Data Sync</h2>
                                                <p class="exp-radio-p">(Faster)</p>
                                            </label>
                                            <h4 class="expertrec-recommended">Recommended</h4>
                                        </div>
                                        <div>
                                            <input class="expertrec-radio-btn" type="radio" id="crawlWay" name="indexingWay"
                                                value="crawl" onclick="showCrawlMsg()">
                                            <label class="radio-label exp-font--12" for="crawlWay">
                                                <h2>Periodic Data Sync</h2>
                                                <p class="exp-radio-p">(Supports pdf, doc, xlsx)</p>
                                            </label>
                                        </div>
                                    </section>
                                </div>
                            </form>
                            <div class="exp-field padding-bottom--24">
                                <!-- <button id="open-child-window"><span>Continue</span></button> -->
                                <input id="open-child-window" type="submit" name="submit" value="Continue">
                            </div>
                        </div>
                        <div class="login-form">
                        <span class="exp padding-bottom--15"> </span>
                            <form class="exp">
                                <div class="exp-field padding-bottom--24">
                                    <label class="exp" for="org_id">Please Enter API key</label>
                                    <input class="expertrec-input" type="input" name="org_id" 
                                    id = "existing_org_id" placeholder="Enter Your Wordpress API key"
                                    value="">
                                </div>
                                <div class="exp-field padding-bottom--24" id="apiKeyHelp">
                                    <label class="exp" for="radio_btn" style="margin-bottom: 24px;">How to get API key ?(<a
                                                style="color: blue;"
                                                href="https://blog.expertrec.com/how-to-find-your-api-key/"
                                                target="_blank">Need help?</a>)</label>
                                </div>
                                <div class="exp-field padding-bottom--24" id="ecomblock" style="display: none;">
                                    <label class="exp1" for="existing_secret_id">Please Enter Secret key</label>
                                    <input class="expertrec-input" type="input" name="org_id" 
                                    id = "existing_secret_id" placeholder="Enter Your secret API key"
                                    value="">
                                </div>
                                <?php include_once(ABSPATH . 'wp-admin/includes/plugin.php');
                                $is_woocommerce = is_plugin_active('woocommerce/woocommerce.php'); ?>
                                <div class="exp-field padding-bottom--24" <?php if ($is_woocommerce) echo("hidden") ?>>
                                    <label class="exp1" for="radio_btn" style="margin-bottom: 24px;">How to get Secret key ?(<a
                                                style="color: blue;"
                                                href="https://blog.expertrec.com/how-to-find-your-api-key/"
                                                target="_blank">Need help?</a>)</label>
                                </div>
                
                            </form>
                            <div class="exp-field padding-bottom--24">
                                <img class="expertrec-loading-img" id="exp-settings-loading"
                                src="<?php echo plugin_dir_url(__DIR__) . 'assets/images/Spin-1s-200px.svg' ?>" alt='loading'
                                style="display: none;">
                                <input id="existing_org_id_continue" type="submit" name="submit" value="Continue">
                                <input id="existing_ECOM_id_continue" type="submit" name="submit" value="Continue" style="display: none;">
                            </div>
                        </div>                        
                    </div>
                </div>
                <div class="footer-link padding-top--24">
                    <div class="listing padding-top--24 padding-bottom--24 expertrec-flex-flex expertrec-center-center">
                        <span class="exp"><a href="https://www.expertrec.com/wordpress-search-plugin/" target="_blank">© Expertrec</a></span>
                        <span class="exp"><a href="mailto:support@expertrec.com" target="_blank">Contact</a></span>
                        <span class="exp"><a href="https://www.expertrec.com/privacy-policy/" target="_blank">Privacy & terms</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div>
    <?php include("expertrec-page-footer.php") ?>
</div>
<script type="text/javascript">
    function hideCrawlMsg() {
        let crawlSelectionMsg = document.getElementById('crawlSelectionMsg');
        if (crawlSelectionMsg) {
            crawlSelectionMsg.style.display = "none";
        }
    }

    function showCrawlMsg() {
        let crawlSelectionMsg = document.getElementById('crawlSelectionMsg');
        if (crawlSelectionMsg) {
            crawlSelectionMsg.style.display = "block";
        }
    }

    function er_login_continue_clicked() {

    }

    hideCrawlMsg();
</script>
<div>
</div>
</body>
</html>
<?php
do_action('er/debug', 'In Expertrec login page');
require_once (plugin_dir_path(__DIR__) . "hooks/expertrecsearch-caller.php");
wp_events("login_page_loaded");