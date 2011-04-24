<?php
	function formResult2Xml($result, $formmessages, $tplHelper) {
		$output = '<xml>';
		
		# output each field to the XML as seperate field, eg $createresult['username']
		# wil become <username>blah
		foreach($result as $key => $value) {
			$output .= '<' . $key . '>' . htmlspecialchars($tplHelper->formMessageToString($value)) . '</' . $key . '>';
		} # foreach
		
		# now output each formmessage 
		foreach($formmessages as $formMsgType => $formMsgValues) {
			foreach($formMsgValues as $value) {
				$output .= '<' . $formMsgType . '>' . htmlspecialchars($tplHelper->formMessageToString($value)) . '</' . $formMsgType . '>';
			} # foreach
		} # foreach
		
		$output .= '</xml>';
		return $output;
	} # formResult2Xml
