<?php

// phpcs:disable MediaWiki.Files.ClassMatchesFilename.NotMatch

/**
 * Manager interactions with the `wiki` table
 *
 * @author DannyS712
 */
class PatchDemoWikiDatabase {

	/** @var mysqli */
	private $database;

	/**
	 * @param mysqli $mysqli
	 */
	public function __construct( $mysqli ) {
		$this->database = $mysqli;
	}

	/**
	 * @param string $wiki
	 * @param string $creator
	 * @param int $createdTime
	 * @param string $branch
	 * @return string|bool True or an error message
	 */
	public function insertNewWiki( string $wiki, string $creator, int $createdTime, string $branch ) {
		$statement = $this->database->prepare( '
			INSERT INTO wikis
			(wiki, creator, created, branch)
			VALUE(?, ?, FROM_UNIXTIME(?), ?)
		' );
		if ( !$statement ) {
			return $this->database->error;
		}

		$statement->bind_param( 'ssis', $wiki, $creator, $createdTime, $branch );
		$statement->execute();
		$statement->close();
		return true;
	}

	/**
	 * @param string $wiki
	 * @param array $patches
	 */
	public function setWikiPatches( string $wiki, array $patches ) {
		$patches = json_encode( $patches );

		$statement = $this->database->prepare( 'UPDATE wikis SET patches = ? WHERE wiki = ?' );
		$statement->bind_param( 'ss', $patches, $wiki );
		$statement->execute();
		$statement->close();
	}

	/**
	 * @param string $wiki
	 * @param array $announcedTasks
	 */
	public function setWikiAnnouncedTasks( string $wiki, array $announcedTasks ) {
		$announcedTasks = json_encode( $announcedTasks );

		$statement = $this->database->prepare( 'UPDATE wikis SET announcedTasks = ? WHERE wiki = ?' );
		$statement->bind_param( 'ss', $announcedTasks, $wiki );
		$statement->execute();
		$statement->close();
	}

	/**
	 * @param string $wiki
	 * @param int $timeToCreate
	 */
	public function setWikiTimeToCreate( string $wiki, int $timeToCreate ) {
		$statement = $this->database->prepare( 'UPDATE wikis SET timeToCreate = ? WHERE wiki = ?' );
		$statement->bind_param( 'is', $timeToCreate, $wiki );
		$statement->execute();
		$statement->close();
	}

}
