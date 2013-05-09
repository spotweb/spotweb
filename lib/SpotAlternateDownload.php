<?php
/**
 * 
 * This class is used to find alternate download urls for nzb's.
 *
 */
class SpotAlternateDownload {
  protected $spot                 = null;
  protected $alternateDownloadUrl = null;
  protected $nzb                  = null;
  	
  public function __construct($spot) {
    $this->spot = $spot;
  }
  
	/**
	 * 
	 * Check for specific string to check if we have an alternate download url.
	 * @param array $this->spot
	 */	
	public function hasUrlForSpot() {
	  if ($this->alternateDownloadUrl) {
	    return true;
	  }
	  
	  // Array containing url matches. Must contain the first part of the url.
	  $matches = array(
	  	'http://base64.derefer.me',
	    'http://derefer.me',
	    'http://alturl.com',
	  );
	  
	  // Search in the website url
	  if(isset($this->spot['website'])) {
	    foreach ($matches as $needle) {
	      if (strpos($this->spot['website'], $needle) !== false) {
	        // Stop search we have a match
	        $this->alternateDownloadUrl = $this->resolveUrl($this->spot['website']);
	        return true;
	      }
	    }
	  }

	  // We have no alternate yet lets spider the description.
	  if (isset($this->spot['description'])) {
	  	foreach ($matches as $needle) {
	      if (strpos($this->spot['description'], $needle) !== false) {

	        // Stop search we have a match, get the url from the description
	        $url = false;
          preg_match('/\>('.str_replace('/','\/',preg_quote($needle)).'.*)\</',$this->spot['description'], $matches);

          if(isset($matches[1])) {
            $url = $matches[1];
          }

      
	        if ($url) {
	          $this->alternateDownloadUrl = $this->resolveUrl($url);
	          return true;
	        }
	      }
	    }
	  }
	  
	  $this->nzb = false;
	  return false;
	}
	
	/**
	 * 
	 * Find the alternate url 
	 * @param String $data String containing alternate url.
	 */
	protected function resolveUrl($url, $currentUrl=false, $retries=0) {

	  if(!function_exists('curl_init')) {
	    trigger_error('cURL is needed to resolve alternate download urls.', E_NOTICE);
	    return $url;
	  }

	  // Initialize curl and follow redirects.
    $ch = curl_init($url);
    $chOptions = array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FAILONERROR		 => 1,
      CURLOPT_CONNECTTIMEOUT => 30,
      CURLOPT_HEADER         => TRUE,
      CURLOPT_NOBODY         => TRUE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,         
    );
    
    // Only use these curl options if no open base dir is set and php mode is off.
    $manualResolving = true;
	  if (ini_get('open_basedir') == '' && ini_get('safe_mode') == false) {
	    $manualResolving = false;
      $chOptions[CURLOPT_FOLLOWLOCATION] = true;
      $chOptions[CURLOPT_MAXREDIRS]      = 20;
    }    

    curl_setopt_array($ch, $chOptions);            
    
    // Execute (save responce to var for manual following redirects)
    $header = curl_exec($ch);
    
    // Check if any error occured
    if(!curl_errno($ch))
    {
     $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);   
     $HTTPcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
     
     // Close handle
     curl_close($ch);
    
     if ($finalUrl) {
       // We found the final url let return it.
       if($currentUrl == $finalUrl) {
         return $finalUrl;
       }

       // We have a php config using safe mode or open_basedir.
       // Lets do some manual matching for redirects.
       if ($manualResolving) {
         
         if ($HTTPcode == 301 || $HTTPcode == 302) {
           preg_match('/Location:(.*?)\n/', $header, $matches);
           $url = trim(array_pop($matches));
         }


         // We found a redirect url inside the page.
         if ($url != $finalUrl) {
           // Lets try to resolve this again there might be more redirects.
           // Recursive call.
           if ($retries == 20) {
             // Lets stop trying recursive we have to many redirects.
             return $url;
           }
           
           // Recursive call coming up :)
           // Do no return we need to continue for meta refresh checks.
           $finalUrl = $this->resolveUrl($url, $url,$retries++);
         }
         
       }

       // Lets try to find a meta refresh on the page of the current url.
       $url = $this->resolveMetaRefreshOnUrl($finalUrl);
       
       // We found a meta refresh url inside the page.
       if ($url != $finalUrl) {
         // Lets try to resolve this again there might be more redirects.
         // Recursive call.
         if ($retries == 2) {
           // Lets stop trying recursive we have to many redirects.
           return $url;
         }
         
         return $this->resolveUrl($url, $url,$retries++);
       }

       return $finalUrl;
     }
    } else {
      trigger_error(curl_errno($ch) . ': ' . curl_error($ch));
    }
    
    // Close handle (will occur on error)
    curl_close($ch);
    
    // Return input url, due to error the url could not be resolved.
    return $url;
	}
	
	/**
	 * 
	 * Checks for a meta refresh tag on the page given by url.
	 * Returns the new url if one is found. If there is no new url it will return the input url.
	 * 
	 * @param String $url
	 */
	protected function resolveMetaRefreshOnUrl($url)
	{
		if(!function_exists('curl_init')) {
	    trigger_error('cURL is needed to resolve meta refresh urls.', E_NOTICE);
	    return $url;
	  }
    $url = trim($url);
    	  	  
    // Initialize curl
    $ch = curl_init($url);
    $chOptions = array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FAILONERROR		 => 1,
      CURLOPT_CONNECTTIMEOUT => 30,
      CURLOPT_ENCODING       => '',
      CURLOPT_NOBODY         => FALSE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,         
    );
    curl_setopt_array($ch, $chOptions);
    
    // Execute
    $body = curl_exec($ch);
    
    // Check if any error occured
    if(!curl_errno($ch))
    {

      // Get the url.
      if (preg_match('/meta.+?http-equiv\W+?refresh/i',$body)) {
        preg_match('/content.+?url\W+?(.+?)\"/i',$body,$matches);
        if(isset($matches[1])) {
          $url = $matches[1];
        }
      }
  
      // Close handle
      curl_close($ch);
       
      return $url;
    } else {
      trigger_error(curl_errno($ch) . ': ' . curl_error($ch));
    }

    // Close handle (will occur on error)
    curl_close($ch);
    
    // Return input url, due to error the url could not be resolved.
    return $url;
	}
	
	/**
	 * 
	 * Check if we have an url and return it if there is one.
	 * @return String returns the url or null
	 */
	function getUrlForSpot() {
	  if(!$this->hasUrlForSpot()) {
	    $this->nzb = false;
	    return null;
	  }
	  
	  return $this->alternateDownloadUrl;
	}

	public function hasNzb()
	{
	  // Check if we can get an nzb.
	  if ($this->getNzb()) {
	    return true;
	  }
	  
	  return false;
	}
	
	/**
	 * 
	 * Returns nzb file in xml format.
	 */
	public function getNzb()
	{
	  if ($this->nzb) {
	    // \O/ We already found an nzb before. Return the xml!
	    return $this->nzb;
	  } else if ($this->nzb === false) {
	    // We already did a curl request and this results in a badly formed xml.
	    return null;
	  }
	  
		if(!function_exists('curl_init')) {
	    trigger_error('cURL is needed to get the nzb xml.', E_NOTICE);
	    return null;
	  }
	  
	  // Get the alternate url.
    $url = $this->getUrlForSpot();

    // If there is no alternate url return;
    if (!$url) {
      $this->nzb = false;
      return null;
    }

    // Initialize curl.
    $ch = curl_init($url);
    $chOptions = array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FAILONERROR		 => 1,
      CURLOPT_CONNECTTIMEOUT => 30,
      CURLOPT_ENCODING       => '',
      CURLOPT_NOBODY         => FALSE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
    );
    curl_setopt_array($ch, $chOptions);
    
    // Execute
    $body = curl_exec($ch);

    // Check if any error occured
    if(!curl_errno($ch))
    {
      // Close handle
      curl_close($ch);
      // Load the body into simplexml. 
      // If the xml is well formed this will result in true thus returning the xml.
      // Suppress errors if the string is not well formed, where testing here.
      if (@simplexml_load_string($body)) {
        $this->nzb = $body;
        return $this->nzb;
      } else if($body) {
        // we did not get a direct link to an nzb file.
        // more parsing is needed t(*_*t)
        $this->nzb = $this->downloadNzbFrom($url, $body);
        return $this->nzb;
      } else {
        $this->nzb = false;
      }
    } else {
      trigger_error(curl_errno($ch) . ': ' . curl_error($ch));
    }

    // Close handle (will occur on error)
    curl_close($ch);
    
    // Return nothing we have a curl error.
    return null;
	}
	
	/**
	 * 
	 * Cases for calling the specific parse methods
	 * 
	 * @param String $url
	 * @param String $body
	 */
	protected function downloadNzbFrom($url, $body) {
	  // Binsearch
	  if (strpos($url, 'binsearch.info') !== FALSE) {
	    return $this->downloadNzbFromBinsearch($url, $body);
	  }

	  // NZB Index
	  if (strpos($url, 'nzbindex.nl') !== FALSE) {
	    // This function does not use the $body var.
	    return $this->downloadNzbFromNzbindex($url);
	  }

	  // NZB Club
	  if (strpos($url, 'nzbclub.com') !== FALSE) {
	    // This function does not use the $body var.
	    return $this->downloadNzbFromNzbclub($url);
	  }

	  // No support found return ;(
	  return false;
	}
	
	/**
	 * 
	 * Tries to download the actual nzb from binsearch
	 * 
	 * @param String $url
	 * @param String $body
	 */
	protected function downloadNzbFromBinsearch($url, $body) {    
	  // Match to get the nzb id.
	  preg_match('/\q\=([a-z0-9]*)&*/i', $url, $matches);

	  // This match is essential for the download
	  if (!count($matches)) {
	    return false;
	  }

	  // Hardcoded download url.
	  $downloadUrl = 'http://www.binsearch.info/fcgi/nzb.fcgi?q=' . $matches[1];

    $dom = new DOMDocument;
    
    // Suppress errors, html does not have to be well formed to function.
    @$dom->loadHTML( $body );
    $ids = array();
    
    // Fetch table rows from the result page.
    foreach( $dom->getElementsByTagName( 'tr' ) as $tr ) {
      
      // Only continue parsing if the search query is found in the tr.
      if (strpos($tr->nodeValue, $matches[1]) !== false) {
        
        // Get all input fields.
        $fields = $tr->getElementsByTagName('input');
    
        // Check type, we need the checkbox :)
        foreach($fields as $input) {
          if($input->getAttribute('type') == 'checkbox') {
            
            // walk up the DOM tree and check if the next element has the string in the name.
            // this way we only have the download rows left.
            if(
              $input->parentNode && 
              $input->parentNode->nextSibling 
              && strpos($input->parentNode->nextSibling->nodeValue, $matches[1]) !== false) 
            {
              
              // Push name to array. This name is needed to fetch the download.
              $ids[] = $input->getAttribute('name');
            }
          }
          
        }
        
      }
    }
    
    // Fetch the last id assuming our download was the first to post.
    // This step is to prevent accidental porn downloads.
    $id = null;
    if (count($ids)) {
      $id = array_pop($ids);
    }

    // Withoud an id where not going to be able to get the nzb.
    if(!$id){
      return false;
    }
	  
	  $postdata = array(
	    'action' => 'nzb',
	    $id      => $id
	  );

	  return $this->postAndDownloadNzb($downloadUrl, $postdata);
	}
	
	/**
	 * 
	 * Tries to download the actual nzb from nzbindex
	 * 
	 * @param String $url
	 * @param String $body
	 */
	protected function downloadNzbFromNzbindex($url) {
	  // New curl request to get the page again
	  // This time do a request with the cookie that accepts the Disclaimer agreement.
	  
	  // Initialize curl.
    $ch = curl_init($url);
    $chOptions = array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FAILONERROR		 => 1,
      CURLOPT_CONNECTTIMEOUT => 30,
      CURLOPT_ENCODING       => '',
      CURLOPT_NOBODY         => FALSE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_COOKIE         => 'agreed=true'
    );
    curl_setopt_array($ch, $chOptions);
    
    // Execute
    $body = curl_exec($ch);

    // Check if any error occured
    if(curl_errno($ch))
    {
      trigger_error(curl_errno($ch) . ': ' . curl_error($ch));
      return false;
    }

    // Close handle
    curl_close($ch);  
	  
  	// Match to get the nzb id.
    preg_match('/\q\=([a-z0-9]*)&/i', $url, $matches);
    
    // This match is essential for the download
    if (!count($matches)) {
      return false;
    }
  
    $dom = new DOMDocument;
    
    // Suppress errors, html does not have to be well formed to function.
    @$dom->loadHTML( $body );

    // Fetch a tags from the result page.
    foreach( $dom->getElementsByTagName( 'a' ) as $a ) {

      // Search for the direct nzb download link :)
      if(trim(strtolower($a->nodeValue)) == 'download') {
        $url = $a->getAttribute('href');
        
        if(strpos($url, $matches[1]) !== false) {
          // We just found a direct download link for our url.
          // No need for posting, just another get using curl.
          return $this->getAndDownloadNzb($url);
        }
      }
    }
    
    // Could not find the right download link.
    return false;
	}
	
	/**
	 * 
	 * Tries to download the actual nzb from nzbclub
	 * 
	 * @param String $url
	 */
	protected function downloadNzbFromNzbclub($url) {
	  $downloadUrl = str_replace('nzb_view', 'nzb_get', $url) . 'nzb';
	  return $this->getAndDownloadNzb($downloadUrl);
	}
	
	/**
	 * 
	 * Execute a POST to the given url and return the body.
	 * @param String $url
	 * @param array $postdata
	 */
	protected function postAndDownloadNzb($url, Array $postdata) {
	  
	  $fields_string = null;
	  foreach($postdata as $key=>$value) { 
	    $fields_string .= $key.'='.urlencode($value).'&'; 
	  }
    rtrim($fields_string, '&');

	  // Initialize curl.
    $ch = curl_init($url);
    $chOptions = array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FAILONERROR		 => 1,
      CURLOPT_CONNECTTIMEOUT => 30,
      CURLOPT_ENCODING       => '',
      CURLOPT_NOBODY         => FALSE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,
      CURLOPT_POST           => TRUE,
      CURLOPT_POSTFIELDS     => $fields_string
    );
    curl_setopt_array($ch, $chOptions);
    
    // Execute
    $body = curl_exec($ch);
    
    // Check if any error occured
    if(!curl_errno($ch))
    {
      // Close handle
      curl_close($ch);
      
      // Load the body into simplexml. 
      // If the xml is well formed this will result in true thus returning the xml.
      // Suppress errors if the string is not well formed, where testing here.
      if (@simplexml_load_string($body)) {
        return $body;
      } else {
        return false;
      }
      
    } else {
      trigger_error(curl_errno($ch) . ': ' . curl_error($ch));
    }

    return false;
	}
	
	/**
	 * 
	 * Curl GET return xml or false if not well formed.
	 * @param String $url
	 */
	protected function getAndDownloadNzb($url)
	{
    // Initialize curl.
    $ch = curl_init($url);
    $chOptions = array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FAILONERROR		 => 1,
      CURLOPT_CONNECTTIMEOUT => 30,
      CURLOPT_ENCODING       => '',
      CURLOPT_NOBODY         => FALSE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,     
    );
    curl_setopt_array($ch, $chOptions);
    
    // Execute
    $body = curl_exec($ch);

    // Check if any error occured
    if(!curl_errno($ch))
    {
      // Close handle
      curl_close($ch);
      
      // Load the body into simplexml. 
      // If the xml is well formed this will result in true thus returning the xml.
      // Suppress errors if the string is not well formed, where testing here.
      if (@simplexml_load_string($body)) {
        return $body;
      } else {
        return false;
      }
    } else {
      trigger_error(curl_errno($ch) . ': ' . curl_error($ch));
    }

    // Close handle (will occur on error)
    curl_close($ch);
    
    return false;
	}
}
