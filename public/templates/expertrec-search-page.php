<?php
/**
 * Template Name: Expertrec Search Page
 */

?>
<?php get_header(); ?>

<style>
#er_main {
    padding:0 30px;
}
#er_form {
    display: block;
    margin-left: auto;
    margin-right: auto;
    width: 50%;
}
</style>

<div class="wrap">
    <!-- If we want to add search form later in our search page, then 
    just uncomment the below 3 lines of code including the code in php tag -->
    <!-- <div id=er_form>
        <?php //get_search_form(); ?>
    </div> -->
    <div id="er_main">
        <h2>Search Results</h2>
        <ci-search-results></ci-search-results>
    </div><!-- #main -->
</div><!-- #wrap -->

<?php get_footer(); 