<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Target category/page slugs for specific CWV optimizations.
 */
define( 'CWV_TARGET_SLUGS', [
	'estores-enrollables-screen-a-medida',
	'estores-motorizados',
	'estores-sin-taladrar',
	'mecanismos-para-estores',
	'estores-exterior',
	'estores-termicos',
	'mecanismo-para-estores-con-cadena-paqueto-plegable',
	'estores-enrollables-a-medida',
	'estores-salon',
] );

/**
 * Check if the current page matches any of the CWV target slugs or is home.
 *
 * @return bool
 */
function cwv_is_target_page() {
	if ( is_front_page() || is_home() ) {
		return true;
	}
	$slug = get_query_var( 'name' ) ?: ( get_queried_object() ? get_queried_object()->slug ?? '' : '' );
	if ( in_array( $slug, CWV_TARGET_SLUGS, true ) ) {
		return true;
	}
	// Also match WooCommerce product categories.
	if ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		if ( $term && in_array( $term->slug, CWV_TARGET_SLUGS, true ) ) {
			return true;
		}
	}
	// Match pages by slug.
	if ( is_page() ) {
		$page = get_queried_object();
		if ( $page && in_array( $page->post_name, CWV_TARGET_SLUGS, true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

	// Conditional CSS for products with discount: enqueue as stylesheet instead of inline wp_head.
	if ( is_product() ) {
		global $post;
		if ( $post && get_post_meta( $post->ID, 'tiene_descuento', true ) === 'si' ) {
			wp_add_inline_style(
				'hello-elementor-child-style',
				'.summary.entry-summary p.price{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;}'
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

// ============================================================
// CWV #4 — Defer non-critical scripts (analytics, tracking…)
// ============================================================
add_filter( 'script_loader_tag', 'cwv_defer_non_critical_scripts', 10, 3 );
function cwv_defer_non_critical_scripts( $tag, $handle, $src ) {
	$defer_handles = [
		'google-tag-manager',
		'gtag',
		'google-analytics',
		'ga',
		'facebook-pixel',
		'fb-pixel',
		'hotjar',
		'clarity',
	];
	foreach ( $defer_handles as $h ) {
		if ( false !== strpos( $handle, $h ) ) {
			return str_replace( ' src=', ' defer src=', $tag );
		}
	}
	return $tag;
}

// ============================================================
// CWV #6 — Font-display: swap for Google Fonts
// ============================================================
add_filter( 'style_loader_src', 'cwv_google_fonts_display_swap', 10, 2 );
function cwv_google_fonts_display_swap( $src, $handle ) {
	if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
		if ( strpos( $src, 'display=' ) === false ) {
			$src = add_query_arg( 'display', 'swap', $src );
		}
	}
	return $src;
}

// ============================================================
// CWV #1 & #2 — Preconnect + Preload LCP hero per target page
// ============================================================
add_action( 'wp_head', 'cwv_preconnect_and_preload', 1 );
function cwv_preconnect_and_preload() {
	// Preconnect to external origins used by the site.
	$preconnect_origins = [
		'https://fonts.googleapis.com',
		'https://fonts.gstatic.com',
	];
	foreach ( $preconnect_origins as $origin ) {
		echo '<link rel="preconnect" href="' . esc_url( $origin ) . '" crossorigin>' . "\n";
	}

	// DNS-prefetch fallback for older browsers.
	echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
	echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">' . "\n";

	if ( ! cwv_is_target_page() ) {
		return;
	}

	// Map of slug => hero image URL.
	// These are placeholder paths; replace with actual image URLs from the media library.
	$hero_images = [
		''                                                      => 'https://prueba.corticolors.com/wp-content/uploads/imagenes-decorativas/corticolors-tienda-de-estores-en-valencia-online.jpeg',
		'estores-enrollables-screen-a-medida'                   => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-motorizados'                                    => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-sin-taladrar'                                   => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'mecanismos-para-estores'                                => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-exterior'                                       => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-termicos'                                       => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'mecanismo-para-estores-con-cadena-paqueto-plegable'     => 'https://prueba.corticolors.com/wp-content/uploads/mecanismos-estores/mecanismo-estores-cadena-corticolors.jpg',
		'estores-enrollables-a-medida'                           => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-salon'                                          => 'https://prueba.corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
	];

	$current_slug = '';
	if ( is_front_page() || is_home() ) {
		$current_slug = '';
	} elseif ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		$current_slug = $term ? $term->slug : '';
	} elseif ( is_page() ) {
		$page = get_queried_object();
		$current_slug = $page ? $page->post_name : '';
	} else {
		$current_slug = get_query_var( 'name' );
	}

	if ( isset( $hero_images[ $current_slug ] ) && ! empty( $hero_images[ $current_slug ] ) ) {
		$hero_url = esc_url( $hero_images[ $current_slug ] );
		echo '<link rel="preload" as="image" href="' . $hero_url . '" fetchpriority="high">' . "\n";
	}
}

/**
* Quitar productos relacionados en WooCommerce
*/
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/*Detalles cliente Pagina gracias*/
add_action( 'woocommerce_thankyou', 'adding_customers_details_to_thankyou', 10, 1 );
function adding_customers_details_to_thankyou( $order_id ) {
    // Only for non logged in users
    if ( ! $order_id || is_user_logged_in() ) return;

    $order = wc_get_order($order_id); // Get an instance of the WC_Order object

    wc_get_template( 'order/order-details-customer.php', array('order' => $order ));
}

/*Pedidos contrarembolso en espera*/
function wooc_cod_status( $status ) {
return 'on-hold';
}
add_filter( 'woocommerce_cod_process_payment_order_status', 'wooc_cod_status', 15 );


#/Comentarios blog span
add_filter( 'comment_form_defaults', 'custom_reply_title' );
function custom_reply_title( $defaults ){
$defaults['title_reply_before'] = '<span id="reply-title" class="comment-reply-title">';
$defaults['title_reply_after'] = '</span>';
return $defaults;
}

#/Descripción corta productos
  add_action('wp', 'ocultar_descripcion_corta_producto');
function ocultar_descripcion_corta_producto() {
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
}

#/ Quitar "tienda" migas de pan Yoast Seo
function wpseo_remove_store_breadcrumb($links) {
    $store_url = home_url('/tienda/'); // Reemplaza '/tienda/' con la URL real de tu tienda
    foreach ($links as $key => $link) {
        if (isset($link['url']) && $link['url'] == $store_url) {
            unset($links[$key]);
        }
    }
    return $links;
}
add_filter('wpseo_breadcrumb_links', 'wpseo_remove_store_breadcrumb');

// Función para reemplazar las migas de pan de WooCommerce por las de Yoast SEO
function reemplazar_migas_de_pan() {
    if ( function_exists('yoast_breadcrumb') ) {
        yoast_breadcrumb('<nav id="breadcrumbs">','</nav>');
    }
}

// Reemplazar las migas de pan de WooCommerce por las de Yoast SEO
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
add_action('woocommerce_before_main_content', 'reemplazar_migas_de_pan', 20);

// Ocultar otros métodos de envío cuando el envío gratuito está disponible.
function my_hide_shipping_when_free_is_available( $rates ) {
$free = array();
foreach ( $rates as $rate_id => $rate ) {
if ( 'free_shipping' === $rate->method_id ) {
$free[ $rate_id ] = $rate;
break;
}
}
return ! empty( $free ) ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'my_hide_shipping_when_free_is_available', 100 );

// Combiar metadesripción hellow elementor y Yoast
// Creamos la función
function eliminar_meta_sobrante() {
// Eliminamos esa molesta metadesc
remove_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );
}
// llamamos a la función declarada
add_action( 'after_setup_theme', 'eliminar_meta_sobrante' );

/* Remove the default WooCommerce 3 JSON/LD structured data */
function remove_output_structured_data() {
remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 ); // This removes structured data from all frontend pages
}
add_action( 'init', 'remove_output_structured_data' );

// Ocultamos otras formas de envío excepto la recogida en tienda si hay disponible envío gratuito.

add_filter( 'woocommerce_package_rates', 'esl_hide_shipping_when_free_is_available', 10, 2 );

function esl_hide_shipping_when_free_is_available( $rates, $package ) {
	$new_rates = array();
	foreach ( $rates as $rate_id => $rate ) { 
		if ( 'free_shipping' === $rate->method_id ) {
			$new_rates[ $rate_id ] = $rate;
			break;
		}
	}

	if ( ! empty( $new_rates ) ) { 
		foreach ( $rates as $rate_id => $rate ) {
			if ('local_pickup' === $rate->method_id ) {
				$new_rates[ $rate_id ] = $rate;
				break;
			}
		}
		return $new_rates;
	}

	return $rates;
}

// // Valoraciones de producto schema pro
add_filter( 'wp_schema_pro_add_woocommerce_review', '__return_true' );

// Quitar sku de la página del producto
add_filter( 'wc_product_sku_enabled', '__return_false' );



	add_action('woocommerce_product_meta_start', function () {
		ob_start();
	}, 55);

	add_action('woocommerce_product_meta_end', function () {
		$html = ob_get_clean();
		$html = preg_replace('<span class="posted_in">.*<\/span>', '', $html);
		echo $html;
	}, 55);

// Añadir noindex y canonical a feeds (RSS, Atom)
add_action('wp_head', function () {
    if (is_feed()) {
        global $post;

        // Sacar la URL original del contenido
        if (isset($post->ID)) {
            $canonical = get_permalink($post->ID);
        } else {
            $canonical = home_url();
        }

        echo '<meta name="robots" content="noindex, follow">' . "\n";
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }
});




// AUTOR INFORMACION
add_shortcode('autor_rol', function () {
    $author_id = get_the_author_meta('ID');
    if (!$author_id) return '';

    $user = get_user_by('id', $author_id);
    if (!$user || empty($user->roles)) return '';

    $role_key = $user->roles[0]; // primer rol

    // nombre legible del rol
    $wp_roles = wp_roles();
    $role_name = $wp_roles->roles[$role_key]['name'] ?? $role_key;

    return esc_html($role_name);
});


add_shortcode('autor_redes', function () {

    $author_id = get_the_author_meta('ID');
    if (!$author_id) return '';

    // Claves de WordPress → Icono → URL base (si aplica)
    $redes = [
        'facebook'   => ['icon' => 'fab fa-facebook-f', 'prefix' => 'https://'],
        'instagram'  => ['icon' => 'fab fa-instagram',  'prefix' => 'https://'],
        'linkedin'   => ['icon' => 'fab fa-linkedin-in','prefix' => 'https://'],
        'youtube'    => ['icon' => 'fab fa-youtube',    'prefix' => 'https://'],
        'twitter'    => ['icon' => 'fab fa-twitter',    'prefix' => 'https://'],
        'pinterest'  => ['icon' => 'fab fa-pinterest-p','prefix' => 'https://'],
        'soundcloud' => ['icon' => 'fab fa-soundcloud', 'prefix' => 'https://'],
        'tumblr'     => ['icon' => 'fab fa-tumblr',     'prefix' => 'https://'],
        'user_url'   => ['icon' => 'fas fa-globe',      'prefix' => '']
    ];

    $html = '<div class="autor-redes" style="display:flex;gap:10px;align-items:center;">';

    foreach ($redes as $key => $data) {

        // Obtener valor del campo del autor
        if ($key === 'user_url') {
            $url = get_the_author_meta('user_url', $author_id);
        } else {
            $url = get_user_meta($author_id, $key, true);

            // fallback por si el theme usa otro nombre
            if (!$url) {
                $url = get_user_meta($author_id, 'contactmethod_'.$key, true);
            }
        }

        if (!$url) continue; // Si no hay red, no muestra icono

        // Asegurar https si el usuario no lo escribió
        if ($data['prefix'] && !preg_match('~^https?://~i', $url)) {
            $url = $data['prefix'] . ltrim($url, '/');
        }

        $html .= sprintf(
            '<a href="%s" target="_blank" rel="nofollow noopener" style="text-decoration:none;">
                <i class="%s" style="font-size:18px;"></i>
            </a>',
            esc_url($url),
            esc_attr($data['icon'])
        );
    }

    $html .= '</div>';

    return $html;
});




/**
 * [autores_blog columns="4" role_label="1" only_roles="author,editor,administrator"]
 * Muestra usuarios que tengan posts publicados.
 */
add_shortcode('autores_blog', function($atts){

    $atts = shortcode_atts([
        'columns' => 4,
        'only_roles' => '',     // opcional: filtrar roles (csv)
        'role_label' => 1,      // 1=mostrar nombre del rol, 0=ocultar
        'avatar_size' => 160,
    ], $atts);

    $only_roles = array_filter(array_map('trim', explode(',', $atts['only_roles'])));

    // Obtener usuarios (limitamos a quienes tengan al menos 1 post)
    $users = get_users([
        'fields' => ['ID','display_name'],
        'orderby' => 'display_name',
        'order' => 'ASC'
    ]);

    if (empty($users)) return '';

    $cols = max(1, min(6, (int)$atts['columns']));

    $html = '<div class="autores-grid" style="display:grid;gap:24px;grid-template-columns:repeat('.$cols.',minmax(0,1fr));">';

    foreach ($users as $u){

        $user = get_user_by('id', $u->ID);
        if (!$user) continue;

        // Filtrar por roles si se pide
        if (!empty($only_roles)) {
            $has = false;
            foreach ((array)$user->roles as $r) {
                if (in_array($r, $only_roles, true)) { $has = true; break; }
            }
            if (!$has) continue;
        }

        // Contar posts publicados por autor
        $count = count_user_posts($u->ID, 'post', true); // true = solo publicados
        if ($count < 1) continue; // SOLO autores con blogs

        $author_url  = get_author_posts_url($u->ID);
        $avatar      = get_avatar($u->ID, (int)$atts['avatar_size']);
        $name        = esc_html($user->display_name);

        // Rol legible
        $role_name = '';
        if ((int)$atts['role_label'] === 1 && !empty($user->roles)) {
            $role_key = $user->roles[0];
            $wp_roles = wp_roles();
            $role_name = $wp_roles->roles[$role_key]['name'] ?? $role_key;
        }

        $html .= '<div class="autor-card">';

        $html .= '<a href="'.esc_url($author_url).'" class="urlautor">';
        $html .= '<div class="avatarautor">'.$avatar.'</div>';
        $html .= '<div class="nombreautor">'.$name.'</div>';

        if ($role_name) {
            $html .= '<div style="opacity:.85;margin-top:6px;" class="rolautor">'.esc_html($role_name).'</div>';
        }

        $html .= '<div class="contadorautor">'.$count.' artículos</div>';
        $html .= '</a>';

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
});

// Jet Engine Solución temporal ?nocache
add_filter(
    'jet-engine/listings/ajax-listing-url',
    function( $url ) {
        $url = preg_replace( '/\?nocache=\d+\b/', '', $url );
        $url = preg_replace( '/&nocache=\d+\b/', '', $url );
       
        return $url;
    }
);
// Noindex + canonical para URLs con parámetros no indexables
add_filter('wpseo_robots', function($robots) {
    $params = ['nocache', 'ivrating', 'sort', 'utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'gad_source', 'filter_estancia', 'pagenum'];
    foreach ($params as $param) {
        if (isset($_GET[$param])) {
            return 'noindex, follow';
        }
    }
    return $robots;
});

add_action('wp_head', function () {
    $params = ['nocache', 'ivrating', 'sort', 'utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'gad_source', 'filter_estancia', 'pagenum'];
    foreach ($params as $param) {
        if (isset($_GET[$param])) {
            $canonical_url = is_singular() ? get_permalink() : home_url($_SERVER['REQUEST_URI']);
            $canonical_url = strtok($canonical_url, '?');
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
            break;
        }
    }
}, 1);
/* ════════════════════════════════════════════════════════════════════
 *  AJUSTE SCROLL POPUPS ELEMENTOR — Corticolors
 *  Tema hijo: hello-theme-child-master / functions.php
 *
 *  INSTRUCCIONES: borra desde la línea
 *      /*MONOS CREATIVOS AJUSTE SCROLL FICHA PRODUCTO*\/
 *  hasta el final del fichero y pega TODO lo que hay debajo de esta
 *  cabecera (sin la línea <?php del principio).
 * ════════════════════════════════════════════════════════════════════ */


/* ════════════════════════════════════════════════════════════════════
 * VERSIÓN ANTIGUA — MONOS CREATIVOS — DESACTIVADA (no se ejecuta)
 * ────────────────────────────────────────────────────────────────────
 * Fallos: `height:100vh` en el body destruía la posición de scroll;
 * nunca cancelaba el salto del ancla; competía con el foco de
 * Elementor usando dos requestAnimationFrame y perdía la carrera.
 *
 * add_action('wp_footer', function() {
 * ?>
 * <script>
 * (function($) {
 * var fixedPos = 0;
 * $(document).on('click', '.btn-productp, .btn-personalize, a[href^="#"]', function() {
 *   fixedPos = $(window).scrollTop();
 * });
 * $(window).on('elementor/popup/show', function() {
 *   fixedPos = $(window).scrollTop();
 *   $('body').css({ overflow: 'hidden', height: '100vh', 'touch-action': 'none' });
 * });
 * $(window).on('elementor/popup/hide', function() {
 *   $('body').css({ overflow: '', height: '', 'touch-action': '' });
 *   requestAnimationFrame(function() {
 *     requestAnimationFrame(function() {
 *       window.scrollTo(0, fixedPos);
 *       if (window.location.hash) {
 *         history.replaceState(null, null, window.location.pathname + window.location.search);
 *       }
 *     });
 *   });
 * });
 * })(jQuery);
 * </script>
 * <?php
 * }, 999);
 * ════════════════════════════════════════════════════════════════════ */


/* ════════════════════════════════════════════════════════════════════
 * VERSIÓN NUEVA — ACTIVA
 * ────────────────────────────────────────────────────────────────────
 * 1. Guarda la posición en el CLIC (fase de captura, antes de todo).
 * 2. Cancela la navegación SOLO de anclas de esta misma página cuyo
 *    destino no existe (los botones de UNI CPO). Anclas reales intactos.
 * 3. Bloquea el body con position:fixed + top negativo, que conserva
 *    la posición (en vez de height:100vh, que la destruye).
 * 4. Durante la apertura/cierre del popup fuerza preventScroll:true en
 *    focus(): la Navegación accesible sigue funcionando pero ya no
 *    puede arrastrar la página hasta el botón anterior.
 * 5. Restaura la posición con varios reintentos tras el cierre.
 * 6. Sin jQuery: usa los eventos nativos que Elementor dispara en window.
 * 7. Atributos para que WP Rocket / Cloudflare no lo retrasen ni muevan.
 *
 * AJUSTES EN ELEMENTOR (todos los popups):
 *  · Navegación accesible: activada (igual en todos).
 *  · Desactivar el scroll en la página: NO.
 * ════════════════════════════════════════════════════════════════════ */

add_action( 'wp_footer', 'cc_popup_scroll_fix', 999 );
function cc_popup_scroll_fix() {
	if ( is_admin() ) {
		return;
	}
	?>
<style id="cc-popup-lock-css">
body.cc-popup-lock{position:fixed!important;left:0;right:0;width:100%;overflow:hidden!important;}
</style>
<script id="cc-popup-fix" data-no-optimize="1" data-cfasync="false" nowprocket>
(function () {
	'use strict';
	if (window.ccPopupFix) { return; }
	window.ccPopupFix = true;

	var html       = document.documentElement;
	var LOCK       = 'cc-popup-lock';
	var savedY     = null; // posición antes del primer popup abierto
	var openCount  = 0;    // popups abiertos a la vez
	var guardUntil = 0;    // hasta cuándo neutralizamos el scroll del foco

	function currentY() {
		return window.pageYOffset || html.scrollTop || 0;
	}

	function guard(ms) {
		guardUntil = Date.now() + (ms || 1500);
	}

	function restore() {
		if (savedY === null) { return; }
		try {
			window.scrollTo({ top: savedY, left: 0, behavior: 'instant' });
		} catch (err) {
			window.scrollTo(0, savedY);
		}
	}

	/* ── 1. focus() sin scroll mientras el popup se abre o se cierra ── */
	var nativeFocus = HTMLElement.prototype.focus;
	HTMLElement.prototype.focus = function (opts) {
		if (Date.now() < guardUntil) {
			opts = Object.assign({}, opts || {}, { preventScroll: true });
		}
		return nativeFocus.call(this, opts);
	};

	/* ── 2. Clic: guardar posición y cancelar anclas sin destino ── */
	document.addEventListener('click', function (e) {
		var a = e.target && e.target.closest ? e.target.closest('a[href*="#"]') : null;
		if (!a) { return; }

		if (openCount === 0) { savedY = currentY(); }

		var href = a.getAttribute('href') || '';

		// Enlaces de acción de Elementor: dejamos que Elementor actúe.
		if (href.indexOf('#elementor-action') !== -1) { guard(); return; }

		// Solo anclas que apuntan a ESTA misma página.
		if (a.origin !== location.origin ||
			a.pathname !== location.pathname ||
			a.search !== location.search) { return; }

		var id = a.hash.slice(1);
		try { id = decodeURIComponent(id); } catch (err) {}

		if (!id || (!document.getElementById(id) && !document.getElementsByName(id).length)) {
			e.preventDefault(); // evita el salto y que el hash quede en la URL
			guard();
		}
	}, true);

	/* ── 3. Bloqueo y desbloqueo del body ── */
	function lockBody() {
		if (savedY === null) { savedY = currentY(); }
		var gap = window.innerWidth - html.clientWidth; // ancho de la barra de scroll
		var b = document.body;
		b.style.top = (-savedY) + 'px';
		if (gap > 0) { b.style.paddingRight = gap + 'px'; }
		b.classList.add(LOCK);
	}

	function unlockBody() {
		var b = document.body;
		b.classList.remove(LOCK);
		b.style.top = '';
		b.style.paddingRight = '';
		restore();
	}

	function cleanHash() {
		var h = location.hash;
		if (!h) { return; }
		var id = h.slice(1);
		try { id = decodeURIComponent(id); } catch (err) {}
		if (h.indexOf('elementor-action') !== -1 || !document.getElementById(id)) {
			history.replaceState(history.state, '', location.pathname + location.search);
		}
	}

	/* ── 4. Eventos de Elementor (nativos en window) ── */
	/* Reintentos de restauración. Se cancelan en cuanto el usuario
	 * toca la rueda, la pantalla o el teclado: así nunca le "devuelven"
	 * a la posición anterior mientras él mismo está haciendo scroll. */
	var restoreTimers = [];

	function cancelRestore() {
		restoreTimers.forEach(clearTimeout);
		restoreTimers = [];
	}

	['wheel', 'touchstart', 'touchmove', 'keydown', 'mousedown'].forEach(function (ev) {
		window.addEventListener(ev, function () {
			if (openCount === 0) { cancelRestore(); }
		}, { passive: true, capture: true });
	});

	window.addEventListener('elementor/popup/show', function () {
		cancelRestore();
		guard();
		if (openCount === 0) { lockBody(); }
		openCount++;
		cleanHash();
	});

	window.addEventListener('elementor/popup/hide', function () {
		guard();
		openCount = Math.max(0, openCount - 1);

		if (openCount === 0) {
			cancelRestore();
			unlockBody();
			// Reintentos breves por si Elementor mueve el foco un instante
			// después. Solo corrigen si algo ha desplazado la página.
			[50, 150, 300, 600].forEach(function (t) {
				restoreTimers.push(setTimeout(function () {
					if (openCount === 0 && savedY !== null && Math.abs(currentY() - savedY) > 2) {
						restore();
					}
				}, t));
			});
			restoreTimers.push(setTimeout(function () {
				if (openCount === 0) { savedY = null; }
				restoreTimers = [];
			}, 700));
		}
		cleanHash();
	});

	/* ── 5. Red de seguridad: nunca dejar el body bloqueado ── */
	window.addEventListener('pageshow', function () {
		openCount = 0;
		savedY = null;
		document.body.classList.remove(LOCK);
		document.body.style.top = '';
		document.body.style.paddingRight = '';
	});
})();
</script>
	<?php
}
