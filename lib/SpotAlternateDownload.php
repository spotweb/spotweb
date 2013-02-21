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
	protected function resolveUrl($url) {
	  if(!function_exists('curl_init')) {
	    trigger_error('cURL is needed to resolve alternate download urls.', E_NOTICE);
	    return $url;
	  }
	  	  	  
	  // Initialize curl and follow redirects.
    $ch = curl_init($url);
    $chOptions = array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FAILONERROR		 => 1,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HEADER         => TRUE,
      CURLOPT_NOBODY         => TRUE,
      CURLOPT_SSL_VERIFYPEER => FALSE,
      CURLOPT_SSL_VERIFYHOST => FALSE,         
    );
    curl_setopt_array($ch, $chOptions);
    
    // Execute
    curl_exec($ch);
    
    // Check if any error occured
    if(!curl_errno($ch))
    {
     $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
     
     // Close handle
     curl_close($ch);
    
     if ($finalUrl) {
       return $this->resolveMetaRefreshOnUrl($finalUrl);
     }
    }
    
    // Close handle (will occur on error)
    curl_close($ch);
    
    // Return input url, due to error the url could not be resolved.
    return $this->resolveMetaRefreshOnUrl($url);
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
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_NOBODY         => false,
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
	  if(!$this->hasUrlForSpot($this->spot)) {
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
	    return $url;
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
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_NOBODY         => false,
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
      if (simplexml_load_string($body)) {
        $this->nzb = $body;
        return $this->nzb;
      } else {
        $this->nzb = false;
      }
    }

    // Close handle (will occur on error)
    curl_close($ch);
    
    // Return nothing we have a curl error.
    return null;
	}
	
}