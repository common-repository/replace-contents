<?php
/**
 * Replace Contents
 *
 * @package    Replace Contents
 * @subpackage ReplaceContents Main function
/*  Copyright (c) 2021- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$replacecontents = new ReplaceContents();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class ReplaceContents {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'replacecontents_update_db_hook', array( $this, 'update_db' ), 10, 4 );
		add_action( 'replacecontents_multi_filter_form', array( $this, 'multi_filter_form' ) );
	}

	/** ==================================================
	 * Update DB
	 *
	 * @param array  $replace_s  [][tags][replace_tags].
	 * @param string $flag  flag.
	 * @param string $filter  filter.
	 * @param array  $ids  Post ID's.
	 * @since 1.00
	 */
	public function update_db( $replace_s, $flag, $filter, $ids ) {

		global $wpdb;

		$line = array();
		$count = 0;

		foreach ( $replace_s as $value ) {
			$tags = $value['tags'];
			$replace_tags = $value['replace_tags'];
			switch ( $flag ) {
				case 'single':
					/* Replace Post ID */
					$result = $wpdb->query(
						$wpdb->prepare(
							"
							UPDATE {$wpdb->prefix}posts
							SET post_content = replace( post_content, %s, %s )
							WHERE ID = %d
							",
							$tags,
							$replace_tags,
							$ids[0]
						)
					);
					break;
				case 'multi':
					/* Replace Multi ID's */
					if ( ! empty( $ids ) ) {
						$prepare_s = array_merge( array( $tags, $replace_tags ), $ids );
						$ds = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
						$sql =
						"
						UPDATE {$wpdb->prefix}posts
						SET post_content = replace( post_content, %s, %s )
						WHERE ID IN ($ds)
						";
						$sql = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $prepare_s ) );
						$result = $wpdb->query( $sql );
					} else {
						$result = 0;
					}
					break;
				case 'all':
					/* Replace All */
					$result = $wpdb->query(
						$wpdb->prepare(
							"
							UPDATE {$wpdb->prefix}posts
							SET post_content = replace( post_content, %s, %s )
							",
							$tags,
							$replace_tags
						)
					);
					break;
			}
			if ( $result ) {
				$line[] = '[ ' . html_entity_decode( $tags ) . ' => ' . html_entity_decode( $replace_tags ) . ' ]( ' . number_format( $result ) . ' )';
				$count = $count + $result;
			}
		}

		if ( ! empty( $line ) ) {
			$replace_log = __( 'Replace', 'replace-contents' ) . implode( ',', $line );
			/* translators: %1$s: total count */
			$log_messages[] = sprintf( __( 'Total %1$s items', 'replace-contents' ), number_format( $count ) ) . ' : ' . $filter . $replace_log;
			$logs = get_option( 'replace_contents_logs' );
			if ( ! empty( $logs ) ) {
				$log_messages = array_merge( $log_messages, $logs );
			}
			$log_messages = array_slice( $log_messages, 0, 100 );
			update_option( 'replace_contents_logs', $log_messages );
			/* translators: %1$s: total count %2$d: query count %3$s: message */
			echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( sprintf( __( 'Replaced total %1$s items in %2$d queries. %3$s %4$s', 'replace-contents' ), number_format( $count ), count( $line ), $filter, $replace_log ) ) . '</li></ul></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'Nothing was replaced.', 'replace-contents' ) . '</li></ul></div>';
		}
	}

	/** ==================================================
	 * Multi filter form
	 *
	 * @since 1.05
	 */
	public function multi_filter_form() {

		?>
		<input type="text" name="single_id" value="" placeholder="<?php echo esc_attr( __( 'Post' ) . ' ID' ); ?>"  style="vertical-align: middle;" />

		<select name="author_id">
		<option value="" selected><?php esc_html_e( 'All users', 'replace-contents' ); ?></option>
		<?php
		$users = get_users(
			array(
				'orderby' => 'nicename',
				'order' => 'ASC',
			)
		);
		foreach ( $users as $user ) {
			?>
			<option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option>
			<?php
		}
		?>
		</select>

		<select name="type">
		<?php
		$post_types = get_post_types( array(), 'objects', 'and' );
		$all_type = array();
		foreach ( $post_types as $post_type ) {
			$all_type[] = $post_type->name;
		}
		?>
		<option value="<?php echo esc_attr( implode( ',', $all_type ) ); ?>" selected><?php esc_html_e( 'All Types' ); ?></option>
		<?php
		foreach ( $post_types as $post_type ) {
			?>
			<option value="<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->label ); ?></option>
			<?php
		}
		?>
		</select>

		<select name="status">
		<?php
		$post_status_text = array(
			'publish' => __( 'Published' ),
			'future' => __( 'Scheduled' ),
			'draft' => __( 'Draft' ),
			'pending' => __( 'Pending' ),
			'private' => __( 'Private' ),
			'trash' => __( 'Trash', 'replace-contents' ),
			'auto-draft' => __( 'Auto Draft', 'replace-contents' ),
			'inherit' => __( 'Inherit', 'replace-contents' ),
		);
		$post_status = get_post_stati();
		$all_status = array_keys( $post_status );
		?>
		<option value="<?php echo esc_attr( implode( ',', $all_status ) ); ?>" selected><?php esc_html_e( 'All Status', 'replace-contents' ); ?></option>
		<?php
		foreach ( $post_status as $key => $value ) {
			if ( array_key_exists( $key, $post_status_text ) ) {
				$name = $post_status_text[ $key ];
			} else {
				$name = $value;
			}
			?>
			<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $name ); ?></option>
			<?php
		}
		?>
		</select>

		<select name="taxonomy_slug">
		<option value="" selected><?php esc_html_e( 'All Taxonomies', 'replace-contents' ); ?></option>
		<?php
		$taxonomies = get_taxonomies();
		$count = 0;
		foreach ( $taxonomies as $value ) {
			if ( 0 === $count ) {
				?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
				<?php
			} else {
				?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
				<?php
			}
			++$count;
		}
		?>
		</select>

		<input type="text" name="term_slug" value="" placeholder="<?php echo esc_attr( __( 'Term', 'replace-contents' ) . ' ' . __( 'Slug' ) ); ?>" style="vertical-align: middle;" />

		<input type="text" name="fromdate" class="date-input" value="" placeholder="<?php esc_attr_e( 'Start date', 'replace-contents' ); ?>" />
		<input type="text" name="todate" class="date-input" value="" placeholder="<?php esc_attr_e( 'End date', 'replace-contents' ); ?>" />
		<?php
	}
}


