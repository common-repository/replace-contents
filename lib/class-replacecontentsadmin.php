<?php
/**
 * Replace Contents
 *
 * @package    Replace Contents
 * @subpackage ReplaceContentsAdmin Management screen
	Copyright (c) 2021- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$replacecontentsadmin = new ReplaceContentsAdmin();

/** ==================================================
 * Management screen
 */
class ReplaceContentsAdmin {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'replace-contents/replacecontents.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'tools.php?page=replacecontents' ) . '">' . __( 'Settings' ) . '</a>';
		}
		return $links;
	}

	/** ==================================================
	 * Add Css and Script
	 *
	 * @since 1.08
	 */
	public function load_custom_wp_admin_style() {
		if ( $this->is_my_plugin_screen() ) {
			wp_enqueue_style( 'jquery-ui-css', plugin_dir_url( __DIR__ ) . 'css/jquery-ui.css', array(), '1.12.1', false );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'replacecontents-admin-js', plugin_dir_url( __DIR__ ) . 'js/jquery.replacecontents.admin.js', array( 'jquery' ), '1.0.0', false );
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 1.08
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'tools_page_replacecontents' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {
		add_management_page( 'Replace Contents', 'Replace Contents', 'manage_options', 'replacecontents', array( $this, 'plugin_options' ) );
	}

	/** ==================================================
	 * Settings and Executes page
	 *
	 * @since 1.00
	 */
	public function plugin_options() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		if ( isset( $_POST['R_max_execution_time'] ) && ! empty( $_POST['R_max_execution_time'] ) ) {
			if ( check_admin_referer( 'rc_tags_in', 'replacecontents_tags_input' ) ) {
				if ( ! empty( $_POST['max_execution_time'] ) ) {
					update_option( 'replace_contents_max_execution_time', absint( $_POST['max_execution_time'] ) );
					echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
				}
			}
		}

		$def_max_execution_time = ini_get( 'max_execution_time' );
		$max_execution_time = get_option( 'replace_contents_max_execution_time', $def_max_execution_time );

		if ( isset( $_POST['Replace'] ) && ! empty( $_POST['Replace'] ) ) {
			if ( check_admin_referer( 'rc_tags_in', 'replacecontents_tags_input' ) ) {
				if ( ! empty( $_POST['tags'] ) && ! empty( $_POST['replace_tags'] ) ) {
					$replace_s = array();
					$tags = filter_var(
						wp_unslash( $_POST['tags'] ),
						FILTER_CALLBACK,
						array(
							'options' => function ( $value ) {
								return sanitize_text_field( htmlentities( $value ) );
							},
						)
					);
					foreach ( $tags as $key => $value ) {
						$replace_s[ $key ]['tags'] = html_entity_decode( $value );
					}
					$replace_tags = filter_var(
						wp_unslash( $_POST['replace_tags'] ),
						FILTER_CALLBACK,
						array(
							'options' => function ( $value ) {
								return sanitize_text_field( htmlentities( $value ) );
							},
						)
					);
					foreach ( $replace_tags as $key => $value ) {
						$replace_s[ $key ]['replace_tags'] = html_entity_decode( $value );
					}

					$filter = null;
					if ( ! empty( $_POST['single_id'] ) ) {
						$flag = 'single';
						$ids[0] = absint( $_POST['single_id'] );
					} else {
						$flag = 'all';
						$ids = array();
						$filter_arr = array();
						if ( ! empty( $_POST['author_id'] ) ) {
							$author_id = absint( $_POST['author_id'] );
							$filter_arr[] = __( 'Author' ) . ':' . get_the_author_meta( 'display_name', $author_id );
							$flag = 'multi';
						} else {
							$author_id = null;
						}
						if ( ! empty( $_POST['type'] ) ) {
							$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
							$type_arr = explode( ',', $type );
							if ( 1 === count( $type_arr ) ) {
								$flag = 'multi';
							}
							$filter_arr[] = __( 'Post' ) . __( 'Type' ) . ':' . $type;
						} else {
							$type_arr = array();
						}
						if ( ! empty( $_POST['status'] ) ) {
							$status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
							$status_arr = explode( ',', $status );
							if ( 1 === count( $status_arr ) ) {
								$flag = 'multi';
							}
							$filter_arr[] = __( 'Post' ) . __( 'Status' ) . ':' . $status;
						} else {
							$status_arr = array();
						}
						if ( ! empty( $_POST['taxonomy_slug'] ) && ! empty( $_POST['term_slug'] ) ) {
							$taxonomy_slug = sanitize_text_field( wp_unslash( $_POST['taxonomy_slug'] ) );
							$term_slug = sanitize_text_field( wp_unslash( $_POST['term_slug'] ) );
							$tax_term = array(
								array(
									'taxonomy' => $taxonomy_slug,
									'field' => 'slug',
									'terms' => $term_slug,
								),
							);
							$term_name = get_term_by( 'slug', $term_slug, $taxonomy_slug )->name;
							$filter_arr[] = __( 'Term', 'replace-contents' ) . ':' . $term_name;
							$flag = 'multi';
						} else {
							$tax_term = array();
						}
						if ( ! empty( $_POST['fromdate'] ) && ! empty( $_POST['todate'] ) ) {
							$fromdate = sanitize_text_field( wp_unslash( $_POST['fromdate'] ) );
							$todate = sanitize_text_field( wp_unslash( $_POST['todate'] ) );
							$date_query = array(
								'after' => $fromdate,
								'before' => $todate,
								'inclusive' => true,
							);
							$filter_arr[] = __( 'Period', 'replace-contents' ) . ':' . $fromdate . '->' . $todate;
							$flag = 'multi';
						} else {
							$date_query = array();
						}
						if ( 'multi' === $flag ) {
							if ( ! empty( $filter_arr ) ) {
								$filter .= __( 'Filter' ) . '[ ' . implode( ',', $filter_arr ) . ' ] | ';
							}
							$args = array(
								'post_type'      => $type_arr,
								'post_status'    => $status_arr,
								'tax_query'      => $tax_term,
								'author'         => $author_id,
								'date_query'     => $date_query,
								'posts_per_page' => -1,
								'orderby'        => 'date',
								'order'          => 'DESC',
							);
							$posts = get_posts( $args );
							foreach ( $posts as $post ) {
								$ids[] += $post->ID;
							}
						}
					}
					@set_time_limit( $max_execution_time );
					do_action( 'replacecontents_update_db_hook', $replace_s, $flag, $filter, $ids );
				}
			}
		}

		$scriptname = admin_url( 'tools.php?page=replacecontents' );

		?>
		<div class="wrap">

		<h2><?php esc_html_e( 'Replace Contents', 'replace-contents' ); ?></h2>

		<details>
		<summary><strong><?php esc_html_e( 'Various links of this plugin', 'replace-contents' ); ?></strong></summary>
		<?php $this->credit(); ?>
		</details>

		<div style="margin: 5px; padding: 5px;">
		<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
			<?php wp_nonce_field( 'rc_tags_in', 'replacecontents_tags_input' ); ?>

			<strong><?php esc_html_e( 'Replaces text and HTML tags in the content as follows.', 'replace-contents' ); ?></strong>
			<div style="margin: 5px; padding: 5px;">
			<table border=1 cellspacing="0" cellpadding="5" bordercolor="#000000" style="border-collapse: collapse;">
			<tr>
			<th><?php esc_html_e( 'Before', 'replace-contents' ); ?></th>
			<th><?php esc_html_e( 'After', 'replace-contents' ); ?></th>
			</tr>
			<?php
			for ( $i = 0; $i <= 3; $i++ ) {
				?>
				<tr>
				<td><input type="text" name="tags[<?php echo esc_attr( $i ); ?>]" /></td>
				<td><input type="text" name="replace_tags[<?php echo esc_attr( $i ); ?>]" /></td>
				</tr>
				<?php
			}
			?>
			</table>
			</div>

			<div style="margin: 5px; padding: 5px;">
				<strong><?php esc_html_e( 'Filter' ); ?></strong>
				<div style="margin: 5px;">
				<?php do_action( 'replacecontents_multi_filter_form' ); ?>
				<p class="description">
				* <?php esc_html_e( 'If you enter a post ID, all other refinements will be ignored.', 'replace-contents' ); ?>
				</p>
				<p class="description">
				* <?php esc_html_e( 'Taxonomy and term slug are required in pairs.', 'replace-contents' ); ?>
				</p>
				<p class="description">
				* <?php esc_html_e( 'Start date and End date are required in pairs.', 'replace-contents' ); ?>
				</p>
				</div>
			</div>

			<?php submit_button( __( 'Replace', 'replace-contents' ), 'large', 'Replace', true ); ?>

			<details style="margin-bottom: 5px;">
			<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Logs', 'replace-contents' ); ?></strong></summary>
			<p class="description">
			<?php esc_html_e( 'Displays the last 100 logs.', 'replace-contents' ); ?>
			</p>
			<?php
			$logs = get_option( 'replace_contents_logs' );
			if ( ! empty( $logs ) ) {
				foreach ( $logs as $value ) {
					?>
					<div style="display: block;padding:5px 5px"><?php echo esc_html( $value ); ?></div>
					<?php
				}
			}
			?>
			</details>

			<details style="margin-bottom: 5px;">
			<summary style="cursor: pointer; padding: 10px; border: 1px solid #ddd; background: #f4f4f4; color: #000;"><strong><?php esc_html_e( 'Execution time', 'replace-contents' ); ?></strong></summary>
			<div style="margin: 5px; padding: 5px;">
				<?php
				if ( ! @set_time_limit( $max_execution_time ) ) {
					$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'replace-contents' ) . '</font>';
					?>
					<p class="description">
					<?php
					/* translators: %1$s: limit max execution time */
					echo wp_kses_post( sprintf( __( 'This server has a fixed execution time at %1$s and cannot be changed.', 'replace-contents' ), $limit_seconds_html ) );
					?>
					</p>
					<?php
				} else {
					$max_execution_time_text = __( 'The number of seconds a script is allowed to run.', 'replace-contents' ) . '(' . __( 'The max_execution_time value defined in the php.ini.', 'replace-contents' ) . '[<font color="red">' . $def_max_execution_time . '</font>])';
					?>
					<p class="description">
					<?php esc_html_e( 'If you get a timeout on replace, increase the value.', 'replace-contents' ); ?>
					</p>
					<p class="description">
					<?php echo wp_kses_post( $max_execution_time_text ); ?>:<input type="number" step="1" min="1" max="9999" style="width: 80px;" name="max_execution_time" value="<?php echo esc_attr( $max_execution_time ); ?>" />
					<?php submit_button( __( 'Change' ), 'large', 'R_max_execution_time', false ); ?>
					</p>
					<?php
				}
				?>
			</div>
			</details>

		</form>
		</div>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'replace-contents' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'replace-contents' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'replace-contents' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php
	}
}


