<?php

class Dao_Sqlite_Comment extends Dao_Base_Comment { 


	/*
	 * Insert commentref, 
	 *   messageid is het werkelijke commentaar id
	 *   nntpref is de id van de spot
	 */
	function addComments($comments, $fullComments = array()) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		$chunks = array_chunk($comments, 1);

		foreach($chunks as $comments) {
			$insertArray = array();

			foreach($comments as $comment) {
				$insertArray[] = vsprintf("('%s', '%s', %d, %d)",
						 Array($this->safe($comment['messageid']),
							   $this->safe($comment['nntpref']),
							   $this->safe($comment['rating']),
							   $this->safe($comment['stamp'])));
			} # foreach

			# Actually insert the batch
			if (!empty($insertArray)) {
				$this->_conn->modify("INSERT INTO commentsxover(messageid, nntpref, spotrating, stamp)
									  VALUES " . implode(',', $insertArray), array());
			} # if
		} # foreach
		$this->commitTransaction();

		if (!empty($fullComments)) {
			$this->addFullComments($fullComments);
		} # if
	} # addComments

	/*
	 * Insert commentfull, assumes there is already an entry in commentsxover
	 */
	function addFullComments($fullComments) {
		$this->beginTransaction();
		
		# Databases can have a maximum length of statements, so we 
		# split the amount of spots in chunks of 100
		$chunks = array_chunk($fullComments, 1);

		foreach($chunks as $fullComments) {
			$insertArray = array();

			foreach($fullComments as $comment) {
				# Kap de verschillende strings af op een maximum van 
				# de datastructuur, de unique keys kappen we expres niet af
				$comment['fromhdr'] = substr($comment['fromhdr'], 0, 127);

				$insertArray[] = vsprintf("('%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s')",
						Array($this->safe($comment['messageid']),
							  $this->safe($comment['fromhdr']),
							  $this->safe($comment['stamp']),
							  $this->safe($comment['user-signature']),
							  $this->safe(serialize($comment['user-key'])),
							  $this->safe($comment['spotterid']),
							  $this->safe(implode("\r\n", $comment['body'])),
							  $this->bool2dt($comment['verified']),
							  $this->safe($comment['user-avatar'])));
			} # foreach

			# Actually insert the batch
			$this->_conn->modify("INSERT INTO commentsfull(messageid, fromhdr, stamp, usersignature, userkey, spotterid, body, verified, avatar)
								  VALUES " . implode(',', $insertArray), array());
		} # foreach

		$this->commitTransaction();
	} # addFullComments

} # Dao_Postgresql_Comment
