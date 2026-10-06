<?php
/**
 * The template for displaying the footer.
 *
 * Contains the body & html closing tags.
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) {
	if ( did_action( 'elementor/loaded' ) && hello_header_footer_experiment_active() ) {
		get_template_part( 'template-parts/dynamic-footer' );
	} else {
		get_template_part( 'template-parts/footer' );
	}
}
?>
<?php if ( is_product() ) { ?>
	<style>
		.type-product .woocommerce div.product {
			position: relative;
		}
		

	</style>

<!--  
	<script>
window.addEventListener('load', function() { // Check if the screen width is greater than 768px (desktop) 
    if (window.innerWidth > 768) {
        var gallery = document.querySelector('.woocommerce div.product div.images.woocommerce-product-gallery');
        var stopElement = document.querySelector('.woocommerce-tabs.wc-tabs-wrapper');
        var stopElementRect = stopElement.getBoundingClientRect();
        var galleryHeight = gallery.offsetHeight;
        var stopPoint = stopElementRect.top + window.pageYOffset - galleryHeight - 140;
        window.addEventListener('scroll', function() {
            var scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            console.log("Scroll Position:", scrollPosition);
            console.log("stop-position: " + stopPoint);
            if (scrollPosition < stopPoint) {
                gallery.style.position = 'sticky';
                gallery.style.top = '5%';
            } else {
                gallery.style.top = stopPoint - 200 + 'px';
                gallery.style.position = 'relative';
            }
        });
    }
});
	</script>

-->

<?php } ?>
<?php wp_footer(); ?>

</body>
</html>
