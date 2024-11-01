<html lang="en">
<body class="expertrec-plugin-body">
<div>
    <?php include("expertrec-page-header.php") ?>
    <!-- Success installation message and trial period remaining time -->
    <div class="expertrec_search_wrap" data-isExpire="15">
        <div class="search_bar_instalation_message">Search bar installed successfully</div>
        <div class="search-progress-cu-outer">
            <div class="search-progress-count"></div>
            <span>
                        <?php $exp_eng = get_option('expertrec_engine') ?>
                        <a href="<?php echo ($exp_eng == 'db') ? 'https://cse.expertrec.com/ecom/payment?fr=wp_plugin' : 'https://cse.expertrec.com/csedashboard/pricing?fr=wp_plugin' ?>"
                           target="_blank">Upgrade now</a>
                    </span>
        </div>
    </div>
    <div id="expertrec-reindex-header"
         style="text-align: center; margin-bottom: 60px; color: #000; font-size: x-large; font-weight: 600;"><?php echo ($exp_eng == 'db') ? "Indexing Status" : "Click Recrawl button to start crawling again." ?> </div>
    <div id="expertrec-reindex-progress-text"
         style="text-align: center; margin-bottom: 45px; color: #000; font-size: x-large; font-weight: 300;"><span
                id="indexing-message"></div>
    <!-- Indexing progress bars -->
    <?php if ($exp_eng == 'db') { ?>
        <div class="expertrec-align-center expertrec-index-progress">
            <?php include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            $is_woocommerce = is_plugin_active('woocommerce/woocommerce.php'); ?>
            <div class="expertrec-skill" <?php if (!$is_woocommerce) {
                echo("hidden");
            } ?>>
                <div class="expertrec-pb-outer">
                    <div class="expertrec-pb-inner">
                        <div id="exp-indexed-prod">
                            Not Started
                        </div>
                    </div>
                </div>
                <svg class="expertrec-pb-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" width="140px"
                     height="140px">
                    <defs>
                        <linearGradient id="GradientColorProd">
                            <stop offset="0%" stop-color="#f8743b"/>
                            <stop offset="100%" stop-color="#E7576D"/>
                        </linearGradient>
                    </defs>
                    <circle class="expertrec-svg-circle-product" cx="70" cy="70" r="62" stroke-linecap="round"/>
                </svg>
                <div class="expertrec-pb-title">Products</div>
            </div>

            <div class="expertrec-skill">
                <div class="expertrec-pb-outer">
                    <div class="expertrec-pb-inner">
                        <div id="exp-indexed-post">
                            Not Started
                        </div>
                    </div>
                </div>
                <svg class="expertrec-pb-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" width="140px"
                     height="140px">
                    <defs>
                        <linearGradient id="GradientColorPost">
                            <stop offset="0%" stop-color="#f8743b"/>
                            <stop offset="100%" stop-color="#E7576D"/>
                        </linearGradient>
                    </defs>
                    <circle class="expertrec-svg-circle-post" cx="70" cy="70" r="62" stroke-linecap="round"/>
                </svg>
                <div class="expertrec-pb-title">Posts</div>
            </div>

            <div class="expertrec-skill">
                <div class="expertrec-pb-outer">
                    <div class="expertrec-pb-inner">
                        <div id="exp-indexed-page">
                            Not Started
                        </div>
                    </div>
                </div>
                <svg class="expertrec-pb-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" width="140px"
                     height="140px">
                    <defs>
                        <linearGradient id="GradientColorPage">
                            <stop offset="0%" stop-color="#f8743b"/>
                            <stop offset="100%" stop-color="#E7576D"/>
                        </linearGradient>
                    </defs>
                    <circle class="expertrec-svg-circle-page" cx="70" cy="70" r="62" stroke-linecap="round"/>
                </svg>
                <div class="expertrec-pb-title">Pages</div>
            </div>

            <div class="expertrec-skill">
                <div class="expertrec-pb-outer">
                    <div class="expertrec-pb-inner">
                        <div id="exp-indexed-other">
                            Not Started
                        </div>
                    </div>
                </div>
                <svg class="expertrec-pb-svg" xmlns="http://www.w3.org/2000/svg" version="1.1" width="140px"
                     height="140px">
                    <defs>
                        <linearGradient id="GradientColorOther">
                            <stop offset="0%" stop-color="#f8743b"/>
                            <stop offset="100%" stop-color="#E7576D"/>
                        </linearGradient>
                    </defs>
                    <circle class="expertrec-svg-circle-other" cx="70" cy="70" r="62" stroke-linecap="round"/>
                </svg>
                <div class="expertrec-pb-title">Others</div>
            </div>
        </div>
        <div class="expertrec-last-sync">Last successful sync: <span id="exp-sync-time">NA</span></div>
        <div class="expertrec-error-message" id="expertrec-reindex-error" style="display:none"></div>
        <!-- Re-Index button -->
        <div class="exp-field padding-bottom--24" style="display:block;">
            <img class="expertrec-loading-img" id="exp-reindex-loading"
                 src="<?php echo plugin_dir_url(__DIR__) . 'assets/images/Spin-1s-200px.svg' ?>" alt='loading'
                 style="display: none;">
            <input class="expertrec-align-center" id="expertrec-reindex-btn" type="submit" name="submit"
                   value="Re-Index" style="width: 173px; margin-top: 24px; position: relative;">
            <input hidden class="expertrec-align-center" id="expertrec-stop-indexing-btn" type="submit" name="submit"
                   value="Stop Indexing" style="width: 173px; margin-top: 24px; position: relative;">
        </div>
    <?php } else { ?>
        <!-- Crawl Status -->
        <div class="expertrec-last-sync">Crawl Status: <span id="exp-crawl-status">NA</span></div>
        <!-- Pages crawled -->
        <div class="expertrec-last-sync">Total pages crawled: <span id="exp-pages-crawled">NA</span></div>
        <!-- Re-Crawl button -->
        <div class="exp-field padding-bottom--24">
            <img class="expertrec-loading-img" id="exp-recrawl-loading"
                 src="<?php echo plugin_dir_url(__DIR__) . 'assets/images/Spin-1s-200px.svg' ?>" alt='loading'
                 style="display: none;">
            <input class="expertrec-align-center" id="expertrec-recrawl-btn" type="submit" name="submit"
                   value="Re-Crawl" style="width: 173px; margin-top: 24px; position: relative;">
            <input hidden class="expertrec-align-center" id="expertrec-stop-crawl-btn" type="submit" name="submit"
                   value="Stop Crawl" style="width: 173px; margin-top: 24px; position: relative;">
        </div>
    <?php } ?>

    <!-- Hook type selection -->
    <div class="expertrec-install-mode">Install Search Bar option</div>
    <div>
        <section type="" style="margin-left: 10px; margin-right: 20px; margin-top: 45px;">
            <div>
                <?php $options = get_option('expertrec_options');
                $hook = $options['hook_on_existing_input_box']; ?>
                <input type="radio" class="expertrec-radio-btn" id="default" name="searchHook"
                       value="default" <?php if ($hook) {
                    echo "checked=checked";
                } ?> onchange="search_bar_update(this);">
                <label class="radio-label" for="default">
                    <h2>Use the existing Search Bar</h2>
                    <p>Put a faster, smarter search engine behind your search bar. Keep your Wordpress theme's search
                        bar and use Expertrec dropdown results</p>
                </label>
            </div>
            <div>
                <input type="radio" class="expertrec-radio-btn" id="expertrec" name="searchHook"
                       value="expertrec" <?php if (!$hook) {
                    echo "checked=checked";
                } ?> onchange="search_bar_update(this);">
                <label class="radio-label" for="expertrec">
                    <h2>Install New Search Bar</h2>
                    <p>Customize your search result navigation, layout, styles, and text. Enjoy your new powerful
                        accessibility - conscious and mobile optimized search.</p>
                </label>
            </div>
        </section>
    </div>
    <!-- Hook type update button -->
    <div class="exp-field padding-bottom--24">
        <img class="expertrec-loading-img" id="exp-install-loading"
             src="<?php echo plugin_dir_url(__DIR__) . 'assets/images/Spin-1s-200px.svg' ?>" alt='loading'
             style="display: none;">
    </div>
</div>
<div>
    <?php include("expertrec-page-footer.php") ?>
</div>
<div>
    <?php do_action('er/debug', 'In Expertrec home page') ?>
</div>
</body>
</html>
