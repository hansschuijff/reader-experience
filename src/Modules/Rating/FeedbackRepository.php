<?php

declare(strict_types=1);

namespace ReaderExperience\Modules\Rating;

/**
 * Databasetoegang voor waarderingen. Bevat geen validatie of tokenlogica;
 * dat zit in RatingService.
 */
final class FeedbackRepository {

	public const VALUES = array( 'ja', 'nee' );

	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'rx_feedback';
	}

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
	 * @return array{post_id:int,value:string,token_hash:?string,token_expires:?string,text:?string,comment_id:int,wants_contact:bool}|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT post_id, value, token_hash, token_expires, text, comment_id, wants_contact FROM {$this->table()} WHERE id = %d",
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

	/** Zet de toelichting, maar alleen als er nog geen stond (één keer bruikbaar per rij). */
	public function set_text_if_empty( int $id, string $text ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table()} SET text = %s WHERE id = %d AND (text IS NULL OR text = '')",
				$text,
				$id
			)
		);
	}

	public function set_comment( int $id, int $comment_id ): void {
		global $wpdb;
		$wpdb->update( $this->table(), array( 'comment_id' => $comment_id ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
	}

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

	/** Maakt het token voorgoed onbruikbaar. Aan het einde van de hele (korte) flow, niet per stap. */
	public function invalidate_token( int $id ): void {
		global $wpdb;
		$wpdb->update( $this->table(), array( 'token_hash' => null ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	}

	/** @return array<string,int> */
	public function totals_for_post( int $post_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT value, COUNT(*) AS total FROM {$this->table()} WHERE post_id = %d GROUP BY value", $post_id ),
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
	 * @return array<int,array<string,mixed>>
	 */
	public function recent_with_text( int $limit = 20 ): array {
		global $wpdb;

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

		return (array) $rows;
	}

	/**
	 * @param array{post_id?:int,only_with_content?:bool,orderby?:string,order?:string} $args
	 * @return array{rows:array<int,array<string,mixed>>,total:int}
	 */
	public function paginated( int $page, int $per_page = 20, array $args = array() ): array {
		global $wpdb;

		$post_id           = isset( $args['post_id'] ) ? (int) $args['post_id'] : 0;
		$only_with_content  = $args['only_with_content'] ?? true;
		$orderby          = ( 'post_title' === ( $args['orderby'] ?? '' ) ) ? 'p.post_title' : 'f.created';
		$order            = ( 'ASC' === strtoupper( $args['order'] ?? '' ) ) ? 'ASC' : 'DESC';
		$offset           = max( 0, ( $page - 1 ) * $per_page );

		$where  = array( '1=1' );
		$values = array();

		if ( $post_id > 0 ) {
			$where[]  = 'f.post_id = %d';
			$values[] = $post_id;
		}
		if ( $only_with_content ) {
			// "Interessant om te lezen": een los duimpje zonder toelichting, zonder
			// geplaatste reactie en zonder contactverzoek voegt hier niets toe —
			// die aantallen staan al samengevat in de kolom bij de artikelen.
			$where[] = "( (f.text IS NOT NULL AND f.text != '') OR f.comment_id > 0 OR f.wants_contact = 1 )";
		}

		$where_sql = implode( ' AND ', $where );

		$sql = "SELECT f.id, f.post_id, p.post_title, f.value, f.text, f.comment_id, f.wants_contact, f.contact_name, f.contact_email, f.created
				 FROM {$this->table()} f
				 LEFT JOIN {$wpdb->posts} p ON p.ID = f.post_id
				 WHERE {$where_sql}
				 ORDER BY {$orderby} {$order}
				 LIMIT %d OFFSET %d";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $values, array( $per_page, $offset ) ) ), ARRAY_A );

		$count_sql = "SELECT COUNT(*) FROM {$this->table()} f LEFT JOIN {$wpdb->posts} p ON p.ID = f.post_id WHERE {$where_sql}";
		$total    = $values
			? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) )
			: (int) $wpdb->get_var( $count_sql );

		return array(
			'rows'  => (array) $rows,
			'total' => $total,
		);
	}

	public function delete( int $id ): void {
		global $wpdb;
		$wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Voor het "Filter op artikel"-keuzemenu: alleen artikelen die ook echt
	 * feedback-met-inhoud hebben, niet de hele artikelenlijst van de site.
	 *
	 * @return array<int,array{post_id:int,post_title:string}>
	 */
	public function posts_with_content(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT DISTINCT f.post_id, p.post_title
			 FROM {$this->table()} f
			 LEFT JOIN {$wpdb->posts} p ON p.ID = f.post_id
			 WHERE (f.text IS NOT NULL AND f.text != '') OR f.comment_id > 0 OR f.wants_contact = 1
			 ORDER BY p.post_title ASC",
			ARRAY_A
		);

		return array_map(
			static fn ( array $r ): array => array(
				'post_id'    => (int) $r['post_id'],
				'post_title' => (string) ( $r['post_title'] ?? '' ),
			),
			(array) $rows
		);
	}
}
