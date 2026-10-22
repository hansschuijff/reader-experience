<?php
/**
 * Database access for ratings.
 *
 * @package ReaderExperience\Modules\Rating
 * @author  Hans Schuijff <@hansschuijff>
 * @license GPL-2.0-only
 * @since   0.1.0
 */

declare( strict_types=1 );

namespace ReaderExperience\Modules\Rating;

use function absint;
use function array_fill;
use function array_fill_keys;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function current_time;
use function gmdate;
use function implode;
use function max;
use function strtoupper;

/**
 * Database access for ratings. Contains no validation or token logic; that lives in RatingService.
 */
final class FeedbackRepository {

	/**
	 * Allowed stored vote values: "like" (thumb up) and "dislike" (thumb down).
	 *
	 * @var string[]
	 */
	public const VALUES = array( 'like', 'dislike' );

	/**
	 * Name of the feedback table.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rx_feedback';
	}

	/**
	 * Stores a new vote.
	 *
	 * @since 0.1.0
	 * @param int    $post_id       Post id.
	 * @param string $value         Vote value.
	 * @param string $token_hash    Hash of the flow token.
	 * @param int    $token_expires Unix timestamp at which the token expires.
	 * @return int The new row id.
	 */
	public function create( int $post_id, string $value, string $token_hash, int $token_expires ): int {
		global $wpdb;

		$wpdb->insert(
			$this->table(),
			array(
				'post_id'       => $post_id,
				'value'         => $value,
				'token_hash'    => $token_hash,
				'token_expires' => gmdate( 'Y-m-d H:i:s', $token_expires ),
				'created'       => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Finds a row.
	 *
	 * @since 0.1.0
	 * @param int $id Row id.
	 * @return array{post_id:int,value:string,token_hash:?string,token_expires:?string,text:?string,comment_id:int,wants_contact:bool}|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT post_id, value, token_hash, token_expires, text, comment_id, wants_contact FROM {$this->table()} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return array(
			'post_id'       => (int) $row['post_id'],
			'value'         => (string) $row['value'],
			'token_hash'    => $row['token_hash'],
			'token_expires' => $row['token_expires'],
			'text'          => $row['text'],
			'comment_id'    => (int) ( $row['comment_id'] ?? 0 ),
			'wants_contact' => ! empty( $row['wants_contact'] ),
		);
	}

	/**
	 * Sets the comment, but only if there was none yet (usable once per row).
	 *
	 * @since 0.1.0
	 * @param int    $id   Row id.
	 * @param string $text The comment.
	 * @return void
	 */
	public function set_text_if_empty( int $id, string $text ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table()} SET text = %s WHERE id = %d AND (text IS NULL OR text = '')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$text,
				$id
			)
		);
	}

	/**
	 * Links the WordPress comment that was created from this feedback.
	 *
	 * @since 0.1.0
	 * @param int $id         Row id.
	 * @param int $comment_id WordPress comment id.
	 * @return void
	 */
	public function set_comment( int $id, int $comment_id ): void {
		global $wpdb;
		$wpdb->update( $this->table(), array( 'comment_id' => $comment_id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
	}

	/**
	 * Stores the contact details of a reader who asked to be contacted.
	 *
	 * @since 0.1.0
	 * @param int    $id    Row id.
	 * @param string $name  Name.
	 * @param string $email Email address.
	 * @return void
	 */
	public function set_contact( int $id, string $name, string $email ): void {
		global $wpdb;
		$wpdb->update(
			$this->table(),
			array(
				'wants_contact' => 1,
				'contact_name'  => $name,
				'contact_email' => $email,
			),
			array( 'id' => $id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Makes the token unusable for good. At the end of the whole (short) flow, not per step.
	 *
	 * @since 0.1.0
	 * @param int $id Row id.
	 * @return void
	 */
	public function invalidate_token( int $id ): void {
		global $wpdb;
		$wpdb->update( $this->table(), array( 'token_hash' => null ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	}

	/**
	 * Number of votes per value for a post.
	 *
	 * @since 0.1.0
	 * @param int $post_id Post id.
	 * @return array<string,int> Count per vote value, zero for values without votes.
	 */
	public function totals_for_post( int $post_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT value, COUNT(*) AS total FROM {$this->table()} WHERE post_id = %d GROUP BY value", $post_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$totals = array_fill_keys( self::VALUES, 0 );
		foreach ( (array) $rows as $row ) {
			if ( isset( $totals[ (string) $row['value'] ] ) ) {
				$totals[ (string) $row['value'] ] = (int) $row['total'];
			}
		}
		return $totals;
	}

	/**
	 * The most recent feedback that has a comment.
	 *
	 * @since 0.1.0
	 * @param int $limit Maximum number of rows.
	 * @return array<int,array<string,mixed>>
	 */
	public function recent_with_text( int $limit = 20 ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and fixed fragments only; values are placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.id, f.post_id, p.post_title, f.value, f.text, f.comment_id, f.wants_contact, f.contact_email, f.created
				 FROM {$this->table()} f
				 LEFT JOIN {$wpdb->posts} p ON p.ID = f.post_id
				 WHERE f.text IS NOT NULL AND f.text != ''
				 ORDER BY f.created DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (array) $rows;
	}

	/**
	 * One page of feedback, for the admin overview.
	 *
	 * @since 0.1.0
	 * @param int                                                                       $page     Page number, starting at 1.
	 * @param int                                                                       $per_page Rows per page.
	 * @param array{post_id?:int,only_with_content?:bool,orderby?:string,order?:string} $args     Filters and sorting.
	 * @return array{rows:array<int,array<string,mixed>>,total:int}
	 */
	public function paginated( int $page, int $per_page = 20, array $args = array() ): array {
		global $wpdb;

		$post_id           = isset( $args['post_id'] ) ? (int) $args['post_id'] : 0;
		$only_with_content = $args['only_with_content'] ?? true;
		$orderby           = ( 'post_title' === ( $args['orderby'] ?? '' ) ) ? 'p.post_title' : 'f.created';
		$order             = ( 'ASC' === strtoupper( $args['order'] ?? '' ) ) ? 'ASC' : 'DESC';
		$offset            = max( 0, ( $page - 1 ) * $per_page );

		$where  = array( '1=1' );
		$values = array();

		if ( $post_id > 0 ) {
			$where[]  = 'f.post_id = %d';
			$values[] = $post_id;
		}
		if ( $only_with_content ) {
			// "Interesting to read": a bare thumb without a comment, without a posted comment and
			// without a contact request adds nothing here. Those totals are already summarised in
			// the column next to the posts.
			$where[] = "( (f.text IS NOT NULL AND f.text != '') OR f.comment_id > 0 OR f.wants_contact = 1 )";
		}

		$where_sql = implode( ' AND ', $where );

		$sql = "SELECT f.id, f.post_id, p.post_title, f.value, f.text, f.comment_id, f.wants_contact, f.contact_name, f.contact_email, f.created
				 FROM {$this->table()} f
				 LEFT JOIN {$wpdb->posts} p ON p.ID = f.post_id
				 WHERE {$where_sql}
				 ORDER BY {$orderby} {$order}
				 LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Only whitelisted fragments and placeholders are interpolated.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $values, array( $per_page, $offset ) ) ), ARRAY_A );

		$count_sql = "SELECT COUNT(*) FROM {$this->table()} f LEFT JOIN {$wpdb->posts} p ON p.ID = f.post_id WHERE {$where_sql}";
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Same reason as above.
		$total = $values
			? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) )
			: (int) $wpdb->get_var( $count_sql );
		// phpcs:enable

		return array(
			'rows'  => (array) $rows,
			'total' => $total,
		);
	}

	/**
	 * Deletes a row.
	 *
	 * @since 0.1.0
	 * @param int $id Row id.
	 * @return void
	 */
	public function delete( int $id ): void {
		global $wpdb;
		$wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Deletes several rows at once.
	 *
	 * @since 0.2.0
	 * @param int[] $ids Row ids.
	 * @return int Number of deleted rows.
	 */
	public function delete_many( array $ids ): int {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( ! $ids ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$deleted      = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table()} WHERE id IN ({$placeholders})", $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return (int) $deleted;
	}

	/**
	 * For the "Filter by article" dropdown: only posts that actually have feedback with content,
	 * not the whole list of posts of the site.
	 *
	 * @since 0.1.0
	 * @return array<int,array{post_id:int,post_title:string}>
	 */
	public function posts_with_content(): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name only; there are no values.
		$rows = $wpdb->get_results(
			"SELECT DISTINCT f.post_id, p.post_title
			 FROM {$this->table()} f
			 LEFT JOIN {$wpdb->posts} p ON p.ID = f.post_id
			 WHERE (f.text IS NOT NULL AND f.text != '') OR f.comment_id > 0 OR f.wants_contact = 1
			 ORDER BY p.post_title ASC",
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map(
			static fn ( array $row ): array => array(
				'post_id'    => (int) $row['post_id'],
				'post_title' => (string) ( $row['post_title'] ?? '' ),
			),
			(array) $rows
		);
	}
}
