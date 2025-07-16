<?php
/**
 * Plugin Name: Press This ACF Mod
 * Description: Adds ACF field support and custom taxonomy integration to posts created with the Press This plugin. Hooks into Press This and moves things to the proper locations with javascript.
 * Version:     1.0.0
 * Author:      Jeff Scott
 * Author URI:  https://beergeek.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: press-this-acf-mod
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_head', 'acf_form_head');

/**
 * Save ACF fields and taxonomy when Press This saves a post.
 * Uses the 'press_this_save_post' filter provided by the Press This plugin.
 */
add_filter( 'press_this_save_post', function( $post_data ) {

	if ( isset( $post_data['ID'] ) ) {
		$post_id = $post_data['ID'];
		// Populate $post_data['tax_input'] for all taxonomies so Press This core handles taxonomy assignment
		if ( isset( $_POST['tax_input'] ) && is_array( $_POST['tax_input'] ) ) {
			$post_type = get_post_type( $post_id );
			$taxonomies = get_object_taxonomies( $post_type );
			foreach ( $taxonomies as $taxonomy ) {
				if ( isset( $_POST['tax_input'][ $taxonomy ] ) ) {
					$terms = $_POST['tax_input'][ $taxonomy ];
					if ( is_string( $terms ) ) {
						$terms = array_filter( array_map( 'trim', explode( ',', $terms ) ) );
					}
					$terms = array_map( 'sanitize_text_field', (array) $terms );
					if ( ! isset( $post_data['tax_input'] ) ) {
						$post_data['tax_input'] = [];
					}
					// Merge with any existing (e.g., categories/tags)
					if ( isset( $post_data['tax_input'][ $taxonomy ] ) ) {
						$post_data['tax_input'][ $taxonomy ] = array_unique( array_merge( (array) $post_data['tax_input'][ $taxonomy ], $terms ) );
					} else {
						$post_data['tax_input'][ $taxonomy ] = $terms;
					}
				}
			}
		}
		// Save ACF fields (requires Advanced Custom Fields plugin)
		if ( function_exists( 'update_field' ) && isset( $_POST['acf'] ) ) {
			foreach ( $_POST['acf'] as $field_key => $value ) {
				update_field( $field_key, $value, $post_id );
			}
		}
	}
	return $post_data;
});

/**
 * Inject ACF field group into the Press This UI below the content box.
 * This uses the 'press_this_html_after_content' action if available, or fallback to 'admin_footer'.
 */
function press_this_acf_mod_render_acf_fields() {
	if ( ! function_exists( 'acf_form' ) ) {
		return;
	}

	// Get the current post object (Press This uses a draft post or new post).
	$post = get_default_post_to_edit( 'post', true );
	$post_id = $post ? $post->ID : 0;

	// Only render if we have a valid post ID.
	if ( $post_id ) {
		echo '<div id="press-this-acf-fields" style="margin-top:24px;">';
		acf_form( array(
			'post_id' => $post_id,
			'form' => false, // Do not render a full form, just the fields
			'field_groups' => false, // Show all groups for this post type
			'fields' => false, // Show all fields
			'return' => '',
			'html_submit_button' => '',
		) );
		echo '</div>';
	}
}

// Try to hook after content in Press This UI. If no such hook, fallback to admin_footer.
add_action( 'admin_print_footer_scripts-press-this.php', 'press_this_acf_mod_render_acf_fields' );

/**
 * Enqueue custom JS for custom taxonomy selection in Press This.
 */
function press_this_acf_mod_enqueue_scripts( $hook ) {
	// Only enqueue on Press This admin page
	if ( isset( $hook ) && $hook === 'press-this.php' ) {
		wp_enqueue_script(
			'press-this-acf-mod-js',
			plugins_url( 'press-this-acf-mod.js', __FILE__ ),
			array( 'jquery' ),
			'1.0',
			true
		);
		wp_enqueue_style(
			'press-this-acf-mod-css',
			plugins_url( 'press-this-acf-mod.css', __FILE__ ),
			array(),
			'1.0'
		);
	}
}
add_action( 'admin_enqueue_scripts', 'press_this_acf_mod_enqueue_scripts' );

/**
 * Output custom taxonomy buttons and modals for Press This UI, emulating categories/tags UX.
 */
/**
 * Output a hierarchical taxonomy UI block (like categories).
 */
function press_this_acf_mod_hierarchical_taxonomy_html( $post, $taxonomy ) {
	$tax_obj = get_taxonomy( $taxonomy );
	if ( ! $tax_obj || ! current_user_can( $tax_obj->cap->assign_terms ) ) {
		return;
	}
	echo '<div aria-label="' . esc_attr( $tax_obj->labels->name ) . '">';
	echo '<ul class="custom-taxonomy-select-' . esc_attr( $taxonomy ) . '">';
	wp_terms_checklist( $post->ID, array( 'taxonomy' => $taxonomy, 'list_only' => true ) );
	echo '</ul>';
	echo '</div>';
}

/**
 * Output a non-hierarchical taxonomy UI block (like tags).
 */
function press_this_acf_mod_nonhierarchical_taxonomy_html( $post, $taxonomy ) {
	$tax_obj = get_taxonomy( $taxonomy );
	if ( ! $tax_obj || ! current_user_can( $tax_obj->cap->assign_terms ) ) {
		return;
	}
	$esc_terms = get_terms_to_edit( $post->ID, $taxonomy );
	if ( ! $esc_terms || is_wp_error( $esc_terms ) ) {
		$esc_terms = '';
	}
	echo '<div class="tagsdiv" id="' . esc_attr( $taxonomy ) . '">';
	echo '<div class="jaxtag">';
	echo '<input type="hidden" name="tax_input[' . esc_attr( $taxonomy ) . ']" class="the-tags" value="' . esc_attr( $esc_terms ) . '">';
	if ( current_user_can( $tax_obj->cap->assign_terms ) ) {
		echo '<div class="ajaxtag hide-if-no-js">';
		echo '<label class="screen-reader-text" for="new-tag-' . esc_attr( $taxonomy ) . '">' . esc_html( $tax_obj->labels->name ) . '</label>';
		echo '<p>';
		echo '<input type="text" id="new-tag-' . esc_attr( $taxonomy ) . '" name="newtag[' . esc_attr( $taxonomy ) . ']" class="newtag form-input-tip" size="16" autocomplete="off" value="" aria-describedby="new-tag-desc">';
		echo '<button type="button" class="tagadd">' . esc_html__( 'Add', 'press-this' ) . '</button>';
		echo '</p>';
		echo '</div>';
		echo '<p class="howto" id="new-tag-desc">' . esc_html( $tax_obj->labels->separate_items_with_commas ) . '</p>';
	}
	echo '</div>';
	echo '<div class="tagchecklist"></div>';
	echo '</div>';
}

function press_this_acf_mod_render_custom_taxonomies() {
	global $post;
	if ( ! $post ) {
		$post = get_default_post_to_edit( 'post', true );
	}
	$post_type = $post->post_type;
	$taxonomies = get_object_taxonomies( $post_type, 'objects' );
// Exclude specific taxonomies from display
$taxonomies = array_filter( $taxonomies, function( $taxonomy ) {
	return ! in_array( $taxonomy->name, array( 'cttm-markers-tax', 'post_format' ), true );
});
	// Output option panel buttons after tags
	echo '<script>document.addEventListener("DOMContentLoaded",function(){var panel=document.querySelector(".post-options");if(panel){';
	foreach ( $taxonomies as $taxonomy ) {
		if ( in_array( $taxonomy->name, array( 'category', 'post_tag' ), true ) ) {
			continue;
		}
		if ( ! current_user_can( $taxonomy->cap->assign_terms ) ) {
			continue;
		}
		// Button HTML (inserted after tags button)
		$button_id = 'press-this-btn-' . esc_attr( $taxonomy->name );
		$button = sprintf(
			'<button type="button" class="post-option %1$s" id="%2$s">'
			. '<span class="dashicons dashicons-tag"></span>'
			. '<span class="post-option-title">%3$s</span>'
			. '<span class="dashicons post-option-forward"></span>'
			. '</button>',
			esc_attr( $taxonomy->name ),
			esc_attr( $button_id ),
			esc_html( $taxonomy->label )
		);
		echo "var tagsBtn=panel.querySelector('.post-option.tags');if(tagsBtn){tagsBtn.insertAdjacentHTML('afterend','" . addslashes($button) . "');}else{panel.insertAdjacentHTML('beforeend','" . addslashes($button) . "');}";
	}
	echo '}});</script>';
	// Output modals for each taxonomy
	$taxonomy_modals = [];
	foreach ( $taxonomies as $taxonomy ) {
		if ( in_array( $taxonomy->name, array( 'category', 'post_tag' ), true ) ) {
			continue;
		}
		if ( ! current_user_can( $taxonomy->cap->assign_terms ) ) {
			continue;
		}
		$modal_id = 'modal-' . esc_attr( $taxonomy->name );
		$modal_class = 'setting-modal is-off-screen is-hidden taxonomy-' . esc_attr( $taxonomy->name );
		$label = esc_html( $taxonomy->label );
		$tax_block_id = 'tax-block-' . esc_attr( $taxonomy->name );
		$modal_html = '<div class="' . $modal_class . '" id="' . $modal_id . '">';
		$modal_html .= '<button type="button" class="modal-close">'
			. '<span class="dashicons post-option-back"></span>'
			. '<span class="setting-title" aria-hidden="true">' . $label . '</span>'
			. '<span class="screen-reader-text">Back to post options</span>'
			. '</button>';
		$modal_html .= '<div id="' . $tax_block_id . '"><!-- taxonomy block placeholder --></div>';
		$modal_html .= '</div>';
		// Output taxonomy block (hidden, to be moved by JS)
		$modal_html .= '<div style="display:none" id="panel-' . esc_attr( $taxonomy->name ) . '">';
		if ( $taxonomy->hierarchical ) {
			ob_start();
			press_this_acf_mod_hierarchical_taxonomy_html( $post, $taxonomy->name );
			$modal_html .= ob_get_clean();
		} else {
			ob_start();
			press_this_acf_mod_nonhierarchical_taxonomy_html( $post, $taxonomy->name );
			$modal_html .= ob_get_clean();
		}
		$modal_html .= '</div>';
		$taxonomy_modals[] = $modal_html;
	}
	// Output all taxonomy modals at the end of admin_footer
	foreach ( $taxonomy_modals as $modal_html ) {
		echo $modal_html;
	}
	// JS: Move all custom taxonomy .setting-modal divs after the last built-in .setting-modal in .options-panel
	echo '<script>document.addEventListener("DOMContentLoaded",function(){
	var optionsPanel=document.querySelector(".options-panel");
	if(!optionsPanel)return;
	var modals=optionsPanel.querySelectorAll(".setting-modal");
	var lastModal=modals[modals.length-1];
	var customModals=document.querySelectorAll(".setting-modal[class*=\'taxonomy-\']");
	customModals.forEach(function(modal){
		if(lastModal&&modal.parentNode!==optionsPanel){
			optionsPanel.insertBefore(modal,lastModal.nextSibling);
			lastModal=modal;
		}
	});
	});</script>';
	// Only JS to move taxonomy blocks into their modals
	foreach ( $taxonomies as $taxonomy ) {
		if ( in_array( $taxonomy->name, array( 'category', 'post_tag' ), true ) ) {
			continue;
		}
		if ( ! current_user_can( $taxonomy->cap->assign_terms ) ) {
			continue;
		}
		echo '<script>document.addEventListener("DOMContentLoaded",function(){var block=document.getElementById("panel-' . esc_attr( $taxonomy->name ) . '");var holder=document.getElementById("tax-block-' . esc_attr( $taxonomy->name ) . '");if(block&&holder){holder.appendChild(block.firstElementChild);block.remove();}});</script>';
	}

	// Move taxonomy blocks into their modals
	echo '<script>document.addEventListener("DOMContentLoaded",function(){';
	foreach ( $taxonomies as $taxonomy ) {
		if ( in_array( $taxonomy->name, array( 'category', 'post_tag' ), true ) ) {
			continue;
		}
		if ( ! current_user_can( $taxonomy->cap->assign_terms ) ) {
			continue;
		}
		echo 'var modal=document.getElementById("modal-' . esc_attr( $taxonomy->name ) . '");var block=document.getElementById("panel-' . esc_attr( $taxonomy->name ) . '");var holder=document.getElementById("tax-block-' . esc_attr( $taxonomy->name ) . '");if(modal&&block&&holder){holder.appendChild(block.firstElementChild);block.remove();}';
	}
	echo '});</script>';
}
add_action( 'admin_footer', 'press_this_acf_mod_render_custom_taxonomies', 20 );

/**
 * Enqueue JS to move ACF fields below the main content textarea in Press This UI.
 */
function press_this_acf_mod_move_acf_fields_js() {
	// Only load on Press This page
	if ( ! isset( $_SERVER['REQUEST_URI'] ) || false === strpos( $_SERVER['REQUEST_URI'], 'press-this.php' ) ) {
		return;
	}
	echo '<script>
	document.addEventListener("DOMContentLoaded", function() {
		var acfFields = document.getElementById("press-this-acf-fields");
		var textarea = document.getElementById("pressthis");
		if (acfFields && textarea) {
			// Insert after the textarea
			textarea.parentNode.insertBefore(acfFields, textarea.nextSibling);
		}
	});
	</script>';
}
add_action( 'admin_footer', 'press_this_acf_mod_move_acf_fields_js', 99 );

/**
 * Move custom taxonomy panels after the tags panel in Press This UI.
 */
function press_this_acf_mod_move_tax_panels_js() {
	// Only run on Press This page
	if ( ! isset( $_SERVER['REQUEST_URI'] ) || false === strpos( $_SERVER['REQUEST_URI'], 'press-this.php' ) ) {
		return;
	}
	echo <<<JS
<script>
document.addEventListener("DOMContentLoaded", function() {
	document.querySelectorAll(".press-this-taxonomy-panel").forEach(function(panel) {
		var taxName = panel.className.match(/taxonomy-([\w-]+)/);
		if (!taxName || !taxName[1]) return;
		taxName = taxName[1];
		var modal = null;
		var modals = document.querySelectorAll('.setting-modal');
		modals.forEach(function(m) {
			if (
				m.id === taxName ||
				m.classList.contains(taxName) ||
				(m.querySelector('.setting-title') && m.querySelector('.setting-title').textContent.toLowerCase().replace(/\s+/g, '_').indexOf(taxName) !== -1)
			) {
				modal = m;
			}
		});
		if (modal) {
			var tagsPanel = modal.querySelector('#post_tag');
			if (tagsPanel) {
				tagsPanel.parentNode.insertBefore(panel, tagsPanel.nextSibling);
			} else {
				modal.appendChild(panel);
			}
		} else {
			var mainTagsPanel = document.getElementById('post_tag');
			if (mainTagsPanel) {
				mainTagsPanel.parentNode.insertBefore(panel, mainTagsPanel.nextSibling);
			}
		}
	});
});
</script>
JS;
}
add_action( 'admin_footer', 'press_this_acf_mod_move_tax_panels_js', 100 );
