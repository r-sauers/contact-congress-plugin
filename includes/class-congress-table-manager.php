<?php
/**
 * A collection of functions to help manage the plugin tables.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Congress
 * @subpackage Congress/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports dbDelta for creating/deleting tables.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/**
 * Import Congress_Table_Transaction
 */
require_once plugin_dir_path( __FILE__ ) . 'class-congress-table-transaction.php';

/**
 * A collection of functions to help manage the plugin tables.
 *
 * @since      1.0.0
 * @package    Congress
 * @subpackage Congress/includes
 * @author     Ryan Sauers <ryan.sauers@exploreveg.org>
 */
class Congress_Table_Manager {

	/**
	 * Gets the real name of a WordPress MySQL table.
	 *
	 * @since    1.0.0
	 * @param string $name is the name of the table.
	 */
	public static function get_table_name( string $name ): string {
		global $wpdb;
		$table_name = $wpdb->prefix . 'congress_' . $name;
		return $table_name;
	}

	/**
	 * Gets the real name of a WordPress MySQL table.
	 *
	 * @since    1.0.0
	 * @param string $name is the name of the table.
	 */
	public static function delete_table( string $name ): void {
		global $wpdb;
		$table_name = self::get_table_name( $name );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$res = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				'DROP TABLE IF EXISTS %i',
				array(
					$table_name,
				)
			)
		);

		if ( false === $res || $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'Contact Congress Failed Database Call: ' .
				$wpdb->last_query . "\n\n" .
				$wpdb->last_error
			);
		}
	}

	/**
	 * Deletes an archived campaign and all of its child entities.
	 *
	 * Creates a transaction error if no change was made.
	 *
	 * @param int                         $id is the id of the campaign to delete.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function delete_archived_campaign( int $id, ?Congress_Table_Transaction $transaction ): Congress_Table_Transaction {
		global $wpdb;
		$campaign_t       = self::get_table_name( 'campaign' );
		$archived_t       = self::get_table_name( 'archived_campaign' );
		$campaign_state_t = self::get_table_name( 'campaign_state' );

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		return $transaction->query(
			function () use ( $id, $wpdb, $campaign_t ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->delete(
					$campaign_t,
					array(
						'id' => $id,
					),
				);
			},
			true
		)->query(
			function () use ( $id, $wpdb, $archived_t ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->delete(
					$archived_t,
					array(
						'id' => $id,
					),
				);
			},
			true
		)->query(
			function () use ( $id, $wpdb, $campaign_state_t ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->delete(
					$campaign_state_t,
					array(
						'campaign_id' => $id,
					),
				);
			}
		);
	}

	/**
	 * Deletes email template(s) and all of its child entities.
	 *
	 * @param ?int                        $id is the id of the email template to delete. If not set,
	 * will delete all email templates in campaign.
	 * @param int                         $campaign_id is the id of the campaign the template is in.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 * @param ?bool                       $error_on_0 will create a transaction error if no email templates deleted.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function delete_email_template(
		?int $id,
		int $campaign_id,
		?Congress_Table_Transaction $transaction,
		?bool $error_on_0 = true
	): Congress_Table_Transaction {
		global $wpdb;
		$email_template_t = self::get_table_name( 'email_template' );

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		if ( null === $campaign_id ) {
			return $transaction->query(
				function () use ( $campaign_id, $wpdb, $email_template_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$email_template_t,
						array(
							'campaign_id' => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		} else {
			return $transaction->query(
				function () use ( $id, $campaign_id, $wpdb, $email_template_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$email_template_t,
						array(
							'id'          => $id,
							'campaign_id' => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		}
	}

	/**
	 * Deletes email(s) and all of its child entities.
	 *
	 * @param ?int                        $id is the id of the email to delete. If not set,
	 * will delete all emails in campaign.
	 * @param int                         $campaign_id is the id of the campaign the template is in.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 * @param ?bool                       $error_on_0 will create a transaction error if no email templates deleted.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function delete_emails(
		?int $id,
		int $campaign_id,
		?Congress_Table_Transaction $transaction,
		?bool $error_on_0 = true
	): Congress_Table_Transaction {
		global $wpdb;
		$email_t = self::get_table_name( 'email' );

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		if ( null === $campaign_id ) {
			return $transaction->query(
				function () use ( $campaign_id, $wpdb, $email_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$email_t,
						array(
							'campaign_id' => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		} else {
			return $transaction->query(
				function () use ( $id, $campaign_id, $wpdb, $email_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$email_t,
						array(
							'id'          => $id,
							'campaign_id' => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		}
	}

	/**
	 * Deletes referer(s) and all of its child entities.
	 *
	 * @param ?int                        $id is the id of the referer to delete. If not set,
	 * will delete all referers in campaign.
	 * @param int                         $campaign_id is the id of the campaign the template is in.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 * @param ?bool                       $error_on_0 will create a transaction error if no email templates deleted.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function delete_referers(
		?int $id,
		int $campaign_id,
		?Congress_Table_Transaction $transaction,
		?bool $error_on_0 = true
	): Congress_Table_Transaction {
		global $wpdb;
		$referer_t = self::get_table_name( 'referer' );

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		if ( null === $campaign_id ) {
			return $transaction->query(
				function () use ( $campaign_id, $wpdb, $referer_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$referer_t,
						array(
							'campaign_id' => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		} else {
			return $transaction->query(
				function () use ( $id, $campaign_id, $wpdb, $referer_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$referer_t,
						array(
							'id'          => $id,
							'campaign_id' => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		}
	}

	/**
	 * Deletes entr(ies) from campaign_excludes_rep and all of its child entities.
	 *
	 * @param ?int                        $rep_id is the id of the representative to delete. If not set,
	 * will delete all entries in campaign.
	 * @param ?int                        $campaign_id is the id of the campaign to delete. If not set,
	 * will delete all entries using representative.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 * @param ?bool                       $error_on_0 will create a transaction error if no email templates deleted.
	 *
	 * @throws Error If both $rep_id and $campaign_id are null.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function delete_campaign_excludes_rep(
		?int $rep_id,
		?int $campaign_id,
		?Congress_Table_Transaction $transaction,
		?bool $error_on_0 = true
	): Congress_Table_Transaction {

		if ( null === $rep_id && null === $campaign_id ) {
			throw '$rep_id and $campaign_id cannot both be null in Congress_Table_Manager::delete_referers';
		}

		global $wpdb;
		$table = self::get_table_name( 'campaign_excludes_rep' );

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		if ( null === $campaign_id ) {
			return $transaction->query(
				function () use ( $rep_id, $wpdb, $table ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$table,
						array(
							'representative' => $rep_id,
						),
					);
				},
				$error_on_0
			);
		} elseif ( null === $rep_id ) {
			return $transaction->query(
				function () use ( $campaign_id, $wpdb, $table ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$table,
						array(
							'campaign' => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		} else {
			return $transaction->query(
				function () use ( $rep_id, $campaign_id, $wpdb, $table ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$table,
						array(
							'representative' => $rep_id,
							'campaign'       => $campaign_id,
						),
					);
				},
				$error_on_0
			);
		}
	}

	/**
	 * Archives an active campaign and deletes all of its child entities.
	 *
	 * Creates a transaction error if no change was made.
	 *
	 * @param int                         $id is the id of the campaign to delete.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function archive_campaign( int $id, ?Congress_Table_Transaction $transaction ): Congress_Table_Transaction {

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		global $wpdb;
		$email_t          = self::get_table_name( 'email' );
		$active_t         = self::get_table_name( 'active_campaign' );
		$archived_t       = self::get_table_name( 'archived_campaign' );
		$campaign_state_t = self::get_table_name( 'campaign_state' );

		$transaction = $transaction->query(
			function () use ( $wpdb, $email_t, $active_t, $archived_t, $campaign_state_t, $id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->query(
					$wpdb->prepare(
						'INSERT INTO %i AS arch (id, email_count) ' .
						'SELECT camp.id, COUNT(email.campaign_id) FROM %i AS camp ' .
						'LEFT JOIN %i AS email ON email.campaign_id = camp.id ' .
						'WHERE camp.id = %d',
						array(
							$archived_t,
							$active_t,
							$email_t,
							$id,
						),
					)
				);
			},
			true
		)->query(
			function () use ( $wpdb, $active_t, $id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->delete(
					$active_t,
					array(
						'id' => $id,
					),
					'%d',
				);
			},
		);

		$transaction = self::delete_email_template(
			campaign_id: $id,
			error_on_0:  false,
			transaction: $transaction
		);
		$transaction = self::delete_emails(
			campaign_id: $id,
			error_on_0:  false,
			transaction: $transaction
		);
		$transaction = self::delete_referers(
			campaign_id: $id,
			error_on_0:  false,
			transaction: $transaction
		);
		$transaction = self::delete_campaign_excludes_rep(
			campaign_id: $id,
			error_on_0:  false,
			transaction: $transaction
		);
		return $transaction;
	}

	/**
	 * Deletes staffer(s) and all of its child entities.
	 *
	 * @param ?int                        $id is the id of the staffer to delete. If not set,
	 * will delete all staffer in campaign.
	 * @param int                         $rep_id is the id of the staffer's rep.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 * @param ?bool                       $error_on_0 will create a transaction error if no email templates deleted.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function delete_staffers(
		?int $id,
		int $rep_id,
		?Congress_Table_Transaction $transaction,
		?bool $error_on_0 = true
	): Congress_Table_Transaction {
		global $wpdb;
		$staffer_t = self::get_table_name( 'staffer' );

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		if ( null === $rep_id ) {
			return $transaction->query(
				function () use ( $rep_id, $wpdb, $staffer_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$staffer_t,
						array(
							'representative' => $rep_id,
						),
					);
				},
				$error_on_0
			);
		} else {
			return $transaction->query(
				function () use ( $id, $rep_id, $wpdb, $staffer_t ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					return $wpdb->delete(
						$staffer_t,
						array(
							'id'             => $id,
							'representative' => $rep_id,
						),
					);
				},
				$error_on_0
			);
		}
	}

	/**
	 * Deletes a representative and all of its child entities.
	 *
	 * Creates a transaction error if no change was made.
	 *
	 * @param int                         $id is the id of the representative to delete.
	 * @param ?Congress_Table_Transaction $transaction is an optional transaction that can be extended.
	 *
	 * @return Congress_Table_Transaction
	 */
	public static function delete_representative( int $id, ?Congress_Table_Transaction $transaction ): Congress_Table_Transaction {
		global $wpdb;
		$rep_t = self::get_table_name( 'representative' );

		if ( null === $transaction ) {
			$transaction = new Congress_Table_Transaction();
		}

		$transaction = $transaction->query(
			function () use ( $id, $wpdb, $rep_t ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return $wpdb->delete(
					$rep_t,
					array(
						'id' => $id,
					),
				);
			},
			true
		);
		$transaction = self::delete_staffers(
			rep_id: $id,
			error_on_0:  false,
			transaction: $transaction
		);
		$transaction = self::delete_campaign_excludes_rep(
			rep_id: $id,
			error_on_0:  false,
			transaction: $transaction
		);
		return $transaction;
	}
}
