<?php
	
	defined( 'ACCESS' ) or die( 'HTTP/1.0 401 Unauthorized.<br />' );
	
	//WEB SCRAPPER CONFIG EXAMPLE
	if( !isset( $G_WEBSCRAPPER ) )
	$G_WEBSCRAPPER = array(
        /*
        //IDENT Scrapper
        'example' => array(
            //Type: torrent|amule|magnets
            'type' => 'torrent',
            //Title: domain.com
            'title' => '',
            //Pass needed to get torrent/amule, from base page search, 1 pass if torrent/amule in next, 2 if hava second page to link, ...
            'passnumber' => 2,
            //HTML Code Format: UTF-8, ANSI, ...
            'htmlformat' => 'UTF-8',
            //Check Duplicates: search if file media title exist and cancel download
            'duplicatescheck' => FALSE,
            //Title Clean, remove strings from title for duplicates scan
            'titleclean' => array(
                'domain.com',
            ),
            //Search Data In Web
            'searchdata' => array(
                //Own search function
                'searchfunction' => '',
                //Web URL to search: 'torrents.com/?q='
                'urlsearch' => '',
                //Web URL to baselist used for 0 search and cron autodownloads: 'torrents.com/'
                'urlbase' => '',
                //URL Append to links: add to links for incomplete URLs: domain.com/
                'linksappend' => '',
                //html object have links: a
                'linksobject' => 'a',
                //String needed in linkTitle to be valid
                'linktitleneeded' => array(),
                //String needed in linkURL to be valid
                'linkurlneeded' => array(),
                //String Exclude in linkTitle to be valid
                'linktitleexclude' => array(),
                //String Exclude in linkURL to be valid
                'linkurlexclude' => array(),
                //POST DATA if needed POST: list fields => values and last is search query param
                'postdata' => array( 'q' => '' ),
                //FILTER SIZE
                //Max File Size: 0 disabled|X megabytes
                'filtersizemax' => 0,
                //FILTER SIZE: get size between text: textpre + XX.XX Gb|Mb + textpos
                'filtersizetextpre' => 'Size: ',
                'filtersizetextpos' => '</span> ',
                //FILTER SIZE: max chars distance from link (-before,+after)
                'filtersizetextdistance' => 1000,
                //FILTER SIZE: especific size(MB)=function( $webscrapperdata, $html, $link )
                'filtersizefunction' => '',
                //DOWNLOAD MULTIPLE
                'downloadmultiple' => FALSE,
            ),
            //Pass Config
            'passdata' => array(
                //Pass 1 Config
                0 => array(
                    //Needed In URL to be valid, if pass not valid search for valid pass to launch
                    'urlvalid' => '',
                    //Next pass: int|FALSE, if FALSE try to download file
                    'passnext' => FALSE,
                    //URL Append to links: add to links for incomplete URLs: domain.com/
                    'linksappend' => '',
                    //html object have links: a or function name to extract (example: webscrap_extract_links_all_html( $html, $title ))
                    'linksobject' => 'a',
                    //String needed in linkTitle to be valid
                    'linktitleneeded' => array(),
                    //String needed in linkURL to be valid
                    'linkurlneeded' => array(),
                    //String Exclude in linkTitle to be valid
                    'linktitleexclude' => array(),
                    //String Exclude in linkURL to be valid
                    'linkurlexclude' => array(),
                    //FILTER SIZE
                    //Max File Size: 0 disabled|X megabytes
                    'filtersizemax' => 0,
                    //FILTER SIZE: get size between text: textpre + XX.XX Gb|Mb + textpos
                    'filtersizetextpre' => 'Size: ',
                    'filtersizetextpos' => '</span> ',
                    //FILTER SIZE: especific size(MB)=function( $html )
                    'filtersizefunction' => '',
                    //DOWNLOAD MULTIPLE
                    'downloadmultiple' => FALSE,
                    //DOWNLOAD function
                    'downloadfunction' => FALSE,
                ),
                //Pass 2 config
                //...
            ),
        ),
        //Next Scrapper
        */
	);
	
	//SCRAPP SEARCH
	
	function webscrapp_search( $wscrapper, $search = '', $debug = PPATH_WEBSCRAP_DEBUG ){
        global $G_WEBSCRAPPER;
        $scrapper = FALSE;
        $result = FALSE;
        
        $exturl = '';
        //searchfunction
        if( array_key_exists( $wscrapper, $G_WEBSCRAPPER ) 
        && ( $scrapperdata = $G_WEBSCRAPPER[ $wscrapper ] ) != FALSE
        && is_array( $scrapperdata )
        && array_key_exists( 'searchdata', $scrapperdata )
        && is_array( $scrapperdata[ 'searchdata' ] )
        && array_key_exists( 'searchfunction', $scrapperdata[ 'searchdata' ] )
        && function_exists( $scrapperdata[ 'searchdata' ][ 'searchfunction' ] )
        ){
            if( $debug ) echo "<br />Search function: " . $scrapperdata[ 'searchdata' ][ 'searchfunction' ];
            $result = $scrapperdata[ 'searchdata' ][ 'searchfunction' ]( $scrapperdata, $search, $debug );
        }elseif( array_key_exists( $wscrapper, $G_WEBSCRAPPER )
        && ( $scrapperdata = $G_WEBSCRAPPER[ $wscrapper ] ) != FALSE
        && is_array( $scrapperdata )
        && array_key_exists( 'searchdata', $scrapperdata )
        && is_array( $scrapperdata[ 'searchdata' ] )
        //Search or Base
        && (
            (
                strlen( $search ) > 0
                && array_key_exists( 'urlsearch', $scrapperdata[ 'searchdata' ] )
                && filter_var( $scrapperdata[ 'searchdata' ][ 'urlsearch' ], FILTER_VALIDATE_URL )
                && ( $exturl = $scrapperdata[ 'searchdata' ][ 'urlsearch' ] ) != FALSE
            )
            || (
                strlen( $search ) == 0
                && array_key_exists( 'urlbase', $scrapperdata[ 'searchdata' ] )
                && filter_var( $scrapperdata[ 'searchdata' ][ 'urlbase' ], FILTER_VALIDATE_URL )
                && ( $exturl = $scrapperdata[ 'searchdata' ][ 'urlbase' ] ) != FALSE
            )
        )
        //Get/Post
        && ( $htmldata = webscrapp_send_data_search( $scrapperdata, $search, $exturl, 5, '', $debug ) ) != FALSE
        ){
            $dom = new DOMDocument();
            if( array_key_exists( 'htmlformat', $scrapperdata ) 
            && strlen( $scrapperdata[ 'htmlformat' ] ) > 0
            ){
                @$dom->loadHTML( mb_convert_encoding( $htmldata, 'HTML-ENTITIES', $scrapperdata[ 'htmlformat' ] ) );
            }else{
                @$dom->loadHTML( $htmldata );
            }
            $neededtitle = $scrapperdata[ 'searchdata' ][ 'linktitleneeded' ];
            $neededurl = $scrapperdata[ 'searchdata' ][ 'linkurlneeded' ];
            $excludetitle = $scrapperdata[ 'searchdata' ][ 'linktitleexclude' ];
            $excludeurl = $scrapperdata[ 'searchdata' ][ 'linkurlexclude' ];
            foreach ( $dom->getElementsByTagName( $scrapperdata[ 'searchdata' ][ 'linksobject' ] ) as $link ){
				$href = $link->getAttribute( "href" );
				$title = $link->nodeValue;
				$title = trim( $title );
				if( strlen( $title ) == 0 ){
                    $title = $link->textContent;
                }
                $title = trim( $title );
				if( strlen( $title ) == 0 ){
                    $title = $link->getAttribute( 'title' );
                }
                $title = trim( $title );
				
                if( $debug ) echo '<br />(Search)LINKs: ' . $title . ' => ' . $href;
                
				$VALID = TRUE;
				
                if( $VALID
				&& array_key_exists( 'linksappend', $scrapperdata[ 'searchdata' ] ) 
                && strlen( $scrapperdata[ 'searchdata' ][ 'linksappend' ] ) > 0
                && !startsWith( $href, 'http' )
                ){
                    $href = $scrapperdata[ 'searchdata' ][ 'linksappend' ] . $href;
                }
                
				if( $VALID
				&& is_array( $excludetitle ) 
				&& count( $excludetitle ) > 0
				){
                    foreach( $excludetitle AS $e ){
                        if( stripos( $title, $e ) !== FALSE ){
                            $VALID = FALSE;
                            break;
                        }
                    }
                }
				if( $VALID
				&& is_array( $excludeurl ) 
				&& count( $excludeurl ) > 0
				){
                    foreach( $excludeurl AS $e ){
                        if( stripos( $href, $e ) !== FALSE ){
                            $VALID = FALSE;
                            break;
                        }
                    }
                }
				if( $VALID
				&& is_array( $neededtitle )
				&& count( $neededtitle ) > 0
				){
                    $VALID = FALSE;
                    foreach( $neededtitle AS $e ){
                        if( stripos( $href, $e ) !== FALSE ){
                            $VALID = TRUE;
                            break;
                        }
                    }
                }
				if( $VALID
				&& is_array( $neededurl ) 
				&& count( $neededurl ) > 0
				){
                    $VALID = FALSE;
                    foreach( $neededurl AS $e ){
                        if( stripos( $href, $e ) !== FALSE ){
                            $VALID = TRUE;
                            break;
                        }
                    }
                }
                if( $debug ) echo "<br />Links Status after Need/Exclude: " . ( $VALID ? 'TRUE' : 'FALSE' );
                
                //CHECK SIZE
                $SIZE = 0;
                if( $VALID
                && array_key_exists( 'filtersizemax', $scrapperdata[ 'searchdata' ] ) 
                && (int)$scrapperdata[ 'searchdata' ][ 'filtersizemax' ] > 0
                ){
                    //filtersizefunction
                    if( array_key_exists( 'filtersizefunction', $scrapperdata[ 'searchdata' ] ) 
                    && strlen( $scrapperdata[ 'searchdata' ][ 'filtersizefunction' ] ) > 0
                    && function_exists( $scrapperdata[ 'searchdata' ][ 'filtersizefunction' ] )
                    && ( $SIZE = $scrapperdata[ 'searchdata' ][ 'filtersizefunction' ]( $webscrapperdata, $htmldata, $href ) ) != FALSE
                    && $SIZE > 0
                    ){
                        if( $debug ) echo '<br />SIZE FUNCTION: ' . $SIZE;
                
                    }elseif( array_key_exists( 'filtersizetextpre', $scrapperdata[ 'searchdata' ] )
                    && array_key_exists( 'filtersizetextpos', $scrapperdata[ 'searchdata' ] ) 
                    && strlen( $scrapperdata[ 'searchdata' ][ 'filtersizetextpre' ] ) > 0
                    && strlen( $scrapperdata[ 'searchdata' ][ 'filtersizetextpos' ] ) > 0
                    && ( $SIZE = webscrapp_get_size( $scrapperdata, $htmldata, $href, 'searchdata', $debug ) ) != FALSE
                    && $SIZE > 0
                    ){
                        if( $debug ) echo '<br />SIZE GENERIC: ' . $SIZE;
                    }
                    if( $SIZE > 0
                    && $SIZE > (int)$scrapperdata[ 'searchdata' ][ 'filtersizemax' ] 
                    ){
                        if( $debug ) echo '<br />' . get_msg( 'WEBSCRAP_CHECKSIZE_KO', FALSE ) . $title . ' ' . formatSizeUnits( $SIZE * 1024 * 1024 );
                        $VALID = FALSE;
                    }else{
                        if( $debug ) echo '<br />' . get_msg( 'WEBSCRAP_CHECKSIZE_OK', FALSE ) . $title . ' ' . formatSizeUnits( $SIZE * 1024 * 1024 );
                    }
                }
                
                if( $VALID 
                && strlen( trim( $title ) ) > 0
                ){
                    if( $debug ) echo "<br />PRETITLE CLEAN: " . $title;
                    if( array_key_exists( 'titleclean', $scrapperdata ) 
                    && is_array( $scrapperdata[ 'titleclean' ] )
                    && count( $scrapperdata[ 'titleclean' ] ) > 0
                    ){
                        foreach( $scrapperdata[ 'titleclean' ] AS $e ){
                            $title = str_ireplace( $e, '', $title );
                        }
                    }
                    if( $debug ) echo "<br />POSTTITLE CLEAN: " . $title;
                    $basetitle = $title;
                    //ADD Size
                    if( $SIZE > 0 ){
                        $title .= ' (' . formatSizeUnits( $SIZE * 1024 * 1024 ) . ')';
                    }
                    //Check exist
                    if( array_key_exists( 'duplicatescheck', $scrapperdata ) 
                    && $scrapperdata[ 'duplicatescheck' ] == TRUE
                    && ( $title_ext = webscrap_check_duplicates( $title ) ) != FALSE
                    ){
                        $title .= $title_ext;
                    }
                    //Add
                    if( !is_array( $result ) ) $result = array();
                    while( array_key_exists( $title, $result ) ){
                        $title = ' ' . $title;
                    }
                    if( strlen( $title ) > 0 ){
                        if( $debug ) echo "<br />Link Added: " . $title . ' => ' . $href;
                        $result[ $title ] = $href;
                    }else{
                        if( $debug ) echo "<br />Link NOT Added: " . $title . ' => ' . $href;
                    }
                }
            }
            
        }
        
        return $result;
	}
	
	function webscrapp_pass( $wscrapper, $pass, $url, $title, $echo = FALSE, $debug = PPATH_WEBSCRAP_DEBUG ){
        global $G_WEBSCRAPPER;
        $scrapper = FALSE;
        $result = FALSE;
        
        if( $debug ){
            $echo = TRUE;
        }
        
        if( $echo ) echo "<br /> START PASS: " . $wscrapper . ' > ' . $pass . ' > ' . $url;
        
        if( array_key_exists( $wscrapper, $G_WEBSCRAPPER ) 
        && ( $scrapperdata = $G_WEBSCRAPPER[ $wscrapper ] ) != FALSE
        && is_array( $scrapperdata )
        && array_key_exists( 'passdata', $scrapperdata )
        && is_array( $scrapperdata[ 'passdata' ] )
        && array_key_exists( $pass, $scrapperdata[ 'passdata' ] )
        //&& filter_var( $url, FILTER_VALIDATE_URL )
        ){
            //Check Valid or search valid $pass
            if( array_key_exists( 'urlvalid', $scrapperdata[ 'passdata' ][ $pass ] ) != FALSE 
            && strlen( $scrapperdata[ 'passdata' ][ $pass ][ 'urlvalid' ] ) > 0
            && stripos( $url, $scrapperdata[ 'passdata' ][ $pass ][ 'urlvalid' ] ) === FALSE
            ){
                if( $echo ) echo '<br />' . get_msg( 'WEBSCRAP_PASS_INVALID', FALSE ) . $pass . ' => ' . $url;
                //Not Valid, search for valid
                foreach( $scrapperdata[ 'passdata' ] AS $k => $epass ){
                    if( array_key_exists( 'urlvalid', $epass ) != FALSE 
                    && strlen( $epass[ 'urlvalid' ] ) > 0
                    && stripos( $url, $epass[ 'urlvalid' ] ) !== FALSE
                    ){
                        $pass = $k;
                        break;
                    }
                }
                if( $echo ) echo '<br />' . get_msg( 'WEBSCRAP_PASS_NEW_VALID', FALSE ) . $pass . ' => ' . $url;
            }
            
            //If Last Path download file
            if( array_key_exists( 'passnext', $scrapperdata[ 'passdata' ][ $pass ] )
            && $scrapperdata[ 'passdata' ][ $pass ][ 'passnext' ] == FALSE
            ){
                if( array_key_exists( 'downloadfunction', $scrapperdata[ 'passdata' ][ $pass ] )
                && function_exists( $scrapperdata[ 'passdata' ][ $pass ][ 'downloadfunction' ] )
                ){
                    //own function
                    if( $scrapperdata[ 'passdata' ][ $pass ][ 'downloadfunction' ]( $url, $debug ) != FALSE ){
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED', FALSE ) . $url;
                        $result = TRUE;
                    }else{
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED_ERROR', FALSE ) . $url;
                    }
                }elseif( array_key_exists( 'type', $scrapperdata )
                && $scrapperdata[ 'type' ] == 'amule'
                ){
                    //Amule
                    if( amuleAdd( $url, $debug ) != FALSE ){
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED', FALSE ) . $url;
                        $result = TRUE;
                    }else{
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED_ERROR', FALSE ) . $url;
                    }
                }elseif( array_key_exists( 'type', $scrapperdata )
                && $scrapperdata[ 'type' ] == 'magnets'
                ){
                    //magnets
                    if( magnetAdd( $url, $debug ) != FALSE ){
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED', FALSE ) . $url;
                        $result = TRUE;
                    }else{
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED_ERROR', FALSE ) . $url;
                    }
                }elseif( array_key_exists( 'type', $scrapperdata )
                && $scrapperdata[ 'type' ] == 'torrent'
                ){
                    //Torrent
                    if( torrentAdd( $url, PPATH_WEBSCRAP_DOWNLOAD . DS . $title, $debug ) != FALSE ){
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED', FALSE ) . $url;
                        $result = TRUE;
                    }else{
                        if( $echo ) echo get_msg( 'WEBSCRAP_FILEDOWNLOADED_ERROR', FALSE ) . $url;
                    }
                }
            }else{
                //Normal Page
                $htmldata = file_get_contents_timed( $url );
                
                $dom = new DOMDocument();
                if( array_key_exists( 'htmlformat', $scrapperdata ) 
                && strlen( $scrapperdata[ 'htmlformat' ] ) > 0
                ){
                    @$dom->loadHTML( mb_convert_encoding( $htmldata, 'HTML-ENTITIES', $scrapperdata[ 'htmlformat' ] ) );
                }else{
                    @$dom->loadHTML( $htmldata );
                }
                $neededtitle = $scrapperdata[ 'passdata' ][ $pass ][ 'linktitleneeded' ];
                $neededurl = $scrapperdata[ 'passdata' ][ $pass ][ 'linkurlneeded' ];
                $excludetitle = $scrapperdata[ 'passdata' ][ $pass ][ 'linktitleexclude' ];
                $excludeurl = $scrapperdata[ 'passdata' ][ $pass ][ 'linkurlexclude' ];
                
                if( function_exists( $scrapperdata[ 'passdata' ][ $pass ][ 'linksobject' ] ) ){
                    $elements = $scrapperdata[ 'passdata' ][ $pass ][ 'linksobject' ]( $htmldata, $title );
                }else{
                    $elements = $dom->getElementsByTagName( $scrapperdata[ 'passdata' ][ $pass ][ 'linksobject' ] );
                }
                
                foreach( $elements AS $link ){
                    if( function_exists( $scrapperdata[ 'passdata' ][ $pass ][ 'linksobject' ] ) ){
                        $href = $link;
                        $titlelink = $title;
                    }else{
                        $href = $link->getAttribute( "href" );
                        $titlelink = $link->nodeValue;
                        if( strlen( $title ) == 0 ){
                            $title = $link->textContent;
                        }
                    }
                    
                    if( array_key_exists( 'linksappend', $scrapperdata[ 'passdata' ][ $pass ] ) 
                    && strlen( $scrapperdata[ 'passdata' ][ $pass ][ 'linksappend' ] ) > 0
                    && !startsWith( $href, 'http' )
                    ){
                        $href = $scrapperdata[ 'passdata' ][ $pass ][ 'linksappend' ] . $href;
                    }
                    
                    if( $debug ) echo '<br />LINK: ' . $titlelink . ' => ' . $href;
                    
                    $VALID = TRUE;
                    if( $VALID
                    && is_array( $excludetitle ) 
                    && count( $excludetitle ) > 0
                    ){
                        foreach( $excludetitle AS $e ){
                            if( stripos( $titlelink, $e ) !== FALSE ){
                                $VALID = FALSE;
                                break;
                            }
                        }
                    }
                    
                    if( $debug ) echo '<br />EXCLUDETITLE-VALID: ' . ( $VALID ? 'TRUE' : 'FALSE' );
                    
                    if( $VALID
                    && is_array( $excludeurl ) 
                    && count( $excludeurl ) > 0
                    ){
                        foreach( $excludeurl AS $e ){
                            if( stripos( $href, $e ) !== FALSE ){
                                $VALID = FALSE;
                                break;
                            }
                        }
                    }
                    
                    if( $debug ) echo '<br />EXCLUDEURL-VALID: ' . ( $VALID ? 'TRUE' : 'FALSE' );
                    
                    if( $VALID
                    && is_array( $neededtitle )
                    && count( $neededtitle ) > 0
                    ){
                        $VALID = FALSE;
                        foreach( $neededtitle AS $e ){
                            if( stripos( $titlelink, $e ) !== FALSE ){
                                $VALID = TRUE;
                                break;
                            }
                        }
                    }
                    
                    if( $debug ) echo '<br />NEEDEDTITLE-VALID: ' . ( $VALID ? 'TRUE' : 'FALSE' );
                    
                    if( $VALID
                    && is_array( $neededurl ) 
                    && count( $neededurl ) > 0
                    ){
                        $VALID = FALSE;
                        foreach( $neededurl AS $e ){
                            if( stripos( $href, $e ) !== FALSE ){
                                $VALID = TRUE;
                                break;
                            }
                        }
                    }
                    
                    if( $debug ) echo '<br />NEEDEDURL-VALID: ' . ( $VALID ? 'TRUE' : 'FALSE' );
                    
                    //CHECK SIZE
                    $SIZE = 0;
                    if( $VALID
                    && array_key_exists( 'filtersizemax', $scrapperdata[ 'passdata' ][ $pass ] ) 
                    && (int)$scrapperdata[ 'passdata' ][ $pass ][ 'filtersizemax' ] > 0
                    ){
                        //filtersizefunction
                        if( array_key_exists( 'filtersizefunction', $scrapperdata[ 'passdata' ][ $pass ] ) 
                        && strlen( $scrapperdata[ 'passdata' ][ $pass ][ 'filtersizefunction' ] ) > 0
                        && function_exists( $scrapperdata[ 'passdata' ][ $pass ][ 'filtersizefunction' ] )
                        && ( $SIZE = $scrapperdata[ 'passdata' ][ $pass ][ 'filtersizefunction' ]( $webscrapperdata, $htmldata, $href ) ) != FALSE
                        && $SIZE > 0
                        ){
                            if( $debug ) echo '<br />SIZE FUNCTION: ' . $SIZE;
                    
                        }elseif( array_key_exists( 'filtersizetextpre', $scrapperdata[ 'passdata' ][ $pass ] )
                        && array_key_exists( 'filtersizetextpos', $scrapperdata[ 'passdata' ][ $pass ] ) 
                        && strlen( $scrapperdata[ 'passdata' ][ $pass ][ 'filtersizetextpre' ] ) > 0
                        && strlen( $scrapperdata[ 'passdata' ][ $pass ][ 'filtersizetextpos' ] ) > 0
                        && ( $SIZE = webscrapp_get_size( $scrapperdata, $htmldata, $href, $pass, $debug ) ) != FALSE
                        && $SIZE > 0
                        ){
                            if( $debug ) echo '<br />SIZE GENERIC: ' . $SIZE;
                        }
                        if( $SIZE > 0
                        && $SIZE > (int)$scrapperdata[ 'passdata' ][ $pass ][ 'filtersizemax' ] 
                        ){
                            if( $echo ) echo '<br />' . get_msg( 'WEBSCRAP_CHECKSIZE_KO', FALSE ) . $titlelink . ' ' . formatSizeUnits( $SIZE * 1024 * 1024 );
                            $VALID = FALSE;
                        }else{
                            if( $echo ) echo '<br />' . get_msg( 'WEBSCRAP_CHECKSIZE_OK', FALSE ) . $titlelink . ' ' . formatSizeUnits( $SIZE * 1024 * 1024 );
                        }
                    }
                    
                    if( $VALID ){
                        if( $debug ) echo '<br />END VALID: ' . $title . ' => ' . $titlelink;
                        if( array_key_exists( 'titleclean', $scrapperdata ) 
                        && is_array( $scrapperdata[ 'titleclean' ] )
                        && count( $scrapperdata[ 'titleclean' ] ) > 0
                        ){
                            foreach( $scrapperdata[ 'titleclean' ] AS $e ){
                                $title = str_ireplace( $e, '', $title );
                            }
                        }
                        $title = trim( $title );
                        //Send Next Pass
                        if( array_key_exists( 'passnext', $scrapperdata[ 'passdata' ][ $pass ] ) 
                        && $scrapperdata[ 'passdata' ][ $pass ][ 'passnext' ] > 0
                        && array_key_exists( $scrapperdata[ 'passdata' ][ $pass ][ 'passnext' ], $scrapperdata[ 'passdata' ] )
                        ){
                            $passnext = $scrapperdata[ 'passdata' ][ $pass ][ 'passnext' ];
                        }else{
                            $passnext = $pass + 1;
                        }
                        
                        if( webscrapp_pass( $wscrapper, $passnext, $href, $title, $echo, $debug ) ){
                            $result = TRUE;
                            //downloadmultiple
                            if( !array_key_exists( 'downloadmultiple', $scrapperdata[ 'passdata' ][ $pass ] ) 
                            || $scrapperdata[ 'passdata' ][ $pass ][ 'downloadmultiple' ] == FALSE
                            ){
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
	}
	
	function downloadFile( $url, $file ){
		$result = TRUE;
		
		if( !file_exists( $file ) ){
            $result = @file_put_contents( $file, @file_get_contents_timed( $url ) );
            
            if( filesize( $file ) == 0 ){
                @unlink( $file );
                $result = FALSE;
            }
        }
		
		return $result;
		
	}
	
	function amuleAdd( $url, $debug = PPATH_WEBSCRAP_DEBUG ){
		//$url = 'ed2K....';
		$result = FALSE;
		
		if( strlen( PPATH_WEBSCRAP_EMULE_CMD ) > 0 ){
            $cmd = str_ireplace( '%ELINK%', $url, PPATH_WEBSCRAP_EMULE_CMD );
            if( $debug ) echo "<br />Amule Add CMD: " . $cmd;
            $result = runExtCommandNoRedirect( $cmd );
            $result = TRUE;
        }
		
		return $result;
		
	}
	
	function magnetAdd( $url, $debug = PPATH_WEBSCRAP_DEBUG ){
		//$url = 'ed2K....';
		$result = FALSE;
		
		if( strlen( PPATH_WEBSCRAP_MAGNETS_CMD ) > 0 ){
            $cmd = str_ireplace( '%MAGNET%', $url, PPATH_WEBSCRAP_MAGNETS_CMD );
            if( $debug ) echo "<br />Magnets Add CMD: " . $cmd;
            $result = runExtCommandNoRedirect( $cmd );
            $result = TRUE;
        }
		
		return $result;
		
	}
	
	function torrentAdd( $url, $file, $debug = PPATH_WEBSCRAP_DEBUG ){
		//$url = 'ed2K....';
		$result = FALSE;
		
		if( !endsWith( $file, '.torrent' ) ){
            $file .= '.torrent';
		}
		
		if( file_exists( $file ) 
		|| file_exists( $file . '.added' ) 
		){
            if( $debug ) echo "<br />Torrent File Exist: " . $file . ' => ' . $url;
            $result = TRUE;
		}elseif( ( $result = downloadFile( $url, $file ) ) != FALSE ){
            if( $debug ) echo "<br />Torrent File Downloaded: " . $file . ' => ' . $url;
            if( strlen( PPATH_WEBSCRAP_TORRENT_CMD ) > 0 ){
                $cmd = str_ireplace( '%FILE%', $url, PPATH_WEBSCRAP_TORRENT_CMD );
                if( $debug ) echo "<br />Torrent Add CMD: " . $md;
                $result = runExtCommandNoRedirect( $cmd );
            }
        }else{
            if( $debug ) echo "<br />Torrent File Download ERROR: " . $file . ' => ' . $url;
        }
		
		return $result;
		
	}
	
	function webscrap_search_updated( $wscrapper, $echo = FALSE, $debug = PPATH_WEBSCRAP_DEBUG ){
		$result = FALSE;
		
        if( ( $links = webscrapp_search( $wscrapper, '', $debug ) ) != FALSE 
        && count( $links ) > 0
        ){
            if( $echo ) echo "<br />";
            if( $echo ) echo "Links: " . count( $links );
                    
            foreach( $links AS $title => $href ){
                if( strpos( $title, 'EXIST:' ) === FALSE ){
                    if( $echo ) echo "<br />";
                    if( $echo ) echo get_msg( 'WEBSCRAP_ADD_URL', FALSE ) . ': ' . $wscrapper . ' => ' . $title . ' => ' . $href;
                    
                    if( webscrapp_pass( $wscrapper, 0, $href, $title, $debug, $debug ) ){
                        if( $echo ) echo "<br />";
                        if( $echo ) echo get_msg( 'WEBSCRAP_ADDOK', FALSE ) . ': ' . $wscrapper . ' => ' . $title . ' => ' . $href;
                    }else{
                        if( $echo ) echo "<br />";
                        if( $echo ) echo get_msg( 'WEBSCRAP_ADDKO', FALSE ) . ': ' . $wscrapper . ' => ' . $title . ' => ' . $href;
                    }
                }else{
                    if( $echo ) echo "<br />";
                    if( $echo ) echo "Exist: " . $title;
                }
            }
		}else{
            if( $echo ) echo "<br />";
            if( $echo ) echo get_msg( 'DEF_EMPTYLIST', FALSE ) . ': ' . $wscrapper;
		}
		
		return $result;
		
	}
	
	//Mb
	function webscrapp_get_size( $webscrapperdata, $html, $link, $pass = 'searchdata', $debug = FALSE ){
        $result = 0;
        $posa = 0;
        $posb = 0;
        $linkpos = 0;
        
        if( $debug ) echo "<br />CHECKING SIZE: " . $link . ' ' . strlen( $html );
        
        if( array_key_exists( $pass, $webscrapperdata ) ){
            if( array_key_exists( 'linksappend', $webscrapperdata[ $pass ] ) 
            && strlen( $webscrapperdata[ $pass ][ 'linksappend'] ) > 0
            ){
                $link = str_ireplace( $webscrapperdata[ $pass ][ 'linksappend'], '', $link );
            }
            $pre = $webscrapperdata[ $pass ][ 'filtersizetextpre' ];
            $pos = $webscrapperdata[ $pass ][ 'filtersizetextpos' ];
            if( array_key_exists( 'filtersizetextdistance', $webscrapperdata[ $pass ] ) 
            && $webscrapperdata[ $pass ][ 'filtersizetextdistance' ] != 0
            && ( $linkpos = stripos( $html, $link ) ) !== FALSE
            ){
                $html = substr( $html, $linkpos, $webscrapperdata[ $pass ][ 'filtersizetextdistance' ] );
            }
        }elseif( array_key_exists( 'passdata', $webscrapperdata ) 
        && array_key_exists( $pass, $webscrapperdata[ 'passdata' ] ) 
        ){
            if( array_key_exists( 'linksappend', $webscrapperdata[ 'passdata' ][ $pass ] ) 
            && strlen( $webscrapperdata[ 'passdata' ][ $pass ][ 'linksappend'] ) > 0
            ){
                $link = str_ireplace( $webscrapperdata[ 'passdata' ][ $pass ][ 'linksappend'], '', $link );
            }
            $pre = $webscrapperdata[ 'passdata' ][ $pass ][ 'filtersizetextpre' ];
            $pos = $webscrapperdata[ 'passdata' ][ $pass ][ 'filtersizetextpos' ];
            if( array_key_exists( 'filtersizetextdistance', $webscrapperdata[ 'passdata' ][ $pass ] ) 
            && $webscrapperdata[ 'passdata' ][ $pass ][ 'filtersizetextdistance' ] != 0
            && ( $linkpos = stripos( $html, $link ) ) !== FALSE
            ){
                $html = substr( $html, $linkpos, $webscrapperdata[ 'passdata' ][ $pass ][ 'filtersizetextdistance' ] );
            }
        }else{
            //$html = '--NO DISTANCE--';
        }
        
        if( $debug ) echo "<br />GET pretag: " . htmlspecialchars( $pre );
        if( $debug ) echo "<br />GET postag: " . htmlspecialchars( $pos );
        if( $debug ) echo "<br />GET linkpos: " . $linkpos;
        if( $debug ) echo "<br />GET posA: " . $posa;
        if( $debug ) echo "<br />GET posB: " . $posb;
        if( $debug ) echo "<br />GET html: " . htmlspecialchars( $html );
        
        if( strlen( $html ) > 20 )
        while( $posa !== FALSE
        && ( $posa = stripos( $html, $pre, $posa ) ) !== FALSE
        && ( $posb = stripos( $html, $pos, $posa ) ) !== FALSE
        && ( $stext = substr( $html, ( $posa + strlen( $pre ) ), ( $posb - $posa ) ) ) !== FALSE
        && strlen( $stext ) > 0
        ){
            if( $debug ) echo "<br />GET TRY: " . htmlspecialchars( $stext );
            if( $debug ) echo "<br />GET posA: " . $posa;
            if( $debug ) echo "<br />GET posB: " . $posb;
            preg_match_all('!\d+(?:\.\d+)?!', $stext, $matches);
            $floats = array_map('floatval', $matches[0]);
            foreach( $floats AS $f ){
                if( $f > 0 ){
                    $result = $f;
                    break;
                }
            }
            
            //Type
            if( stripos( 'mb', $stext ) != FALSE 
            || stripos( 'mib', $stext ) != FALSE
            ){
                //$result *= ( 1024 * 1024 );
            }elseif( stripos( 'gb', $stext ) != FALSE 
            || stripos( 'gib', $stext ) != FALSE
            ){
                $result *= 1024;
            }elseif( stripos( 'tb', $stext ) != FALSE 
            || stripos( 'tib', $stext ) != FALSE
            ){
                $result *= 1024 * 1024;
            }elseif( $result < 100 ){
                $result *= 1024;
            }
            if( $debug ) echo "<br />TRY SIZE: " . $result;
            if( $result > 0 ){
                $posa = FALSE;
            }else{
                $posa++;
            }
        }
        
        return $result;
	}
	
	function webscrapp_send_data_search( $scrapper_data, $search, $url, $time = 5, $sessionid = '', $debug = FALSE ){
        $result = FALSE;
        
        if( strlen( $search ) > 0
        && array_key_exists( 'postdata', $scrapper_data[ 'searchdata' ] ) 
        && is_array( $scrapper_data[ 'searchdata' ][ 'postdata' ] )
        && count( $scrapper_data[ 'searchdata' ][ 'postdata' ] ) > 0
        ){
            $post_data = $scrapper_data[ 'searchdata' ][ 'postdata' ];
            foreach( $post_data AS $k => $v ){
                
            }
            $post_data[ $k ] = $search;
            $result = file_get_contents_timed_post( $url, $post_data, $time, $sessionid, $debug );
        }else{
            $result = file_get_contents_timed( $url . $search, $time, $sessionid, $debug );
        }
        
        return $result;
	}
	
	function webscrap_extract_links_all_html( $html, $title ){
        $result = array();
        $regex = array(
            //primary, full check
            '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#',
            //seconday mode, more lazy
            '#\bhttp[s]?://(?:[a-zA-Z]|[0-9]|[$-_@.&+]|[!*\(\),]|(?:%[0-9a-fA-F][0-9a-fA-F]))+#',
        );
        
        foreach( $regex AS $re ){
            preg_match_all( $re, $html, $match);
            if( is_array( $match )
            && count( $match ) > 0
            && array_key_exists( 0, $match )
            && is_array( $match[ 0 ] )
            ){
                foreach( $match[ 0 ] AS $link ){
                    if( !in_array( $link, $result ) ){
                        while( array_key_exists( $title, $result ) ){
                            $title .= '+';
                        }
                        $result[ $title ] = $link;
                    }
                }
            }
        }
        
        return $result;
    }
    
    //CHECK DUPLICATES
    
    function webscrap_check_duplicates( $title, $debug = PPATH_WEBSCRAP_DEBUG ){
        $result = FALSE;
        $basetitle = $title;
        if( $debug ) echo "<br />CHECKDUPLYS START: " . $title;
        $basetitle = clean_filename( $basetitle );
        $basetitle = preg_replace('/[\W,\d]/', ' ', $basetitle);
        $basetitle = preg_replace('/\s\s+/', ' ', $basetitle);
        $basetitle = trim( $basetitle );
        $basetitle = preg_replace( '/\b\w\b\s?/', '', $basetitle );
        if( $debug ) echo "<br />CHECKDUPLYS CLEANTITLE: " . $basetitle;
        
        if( ( $season = get_media_chapter( $title ) ) != FALSE 
        && is_array( $season )
        && count( $season ) > 1
        ){
            $chapter = $season[ 1 ];
            $season = $season[ 0 ];
            if( $debug ) echo "<br />CHECKDUPLYS CHAPTERS: " . $season . 'x' . $chapter;
            $basetitle = clean_media_chapter( $basetitle );
        }else{
            $chapter = FALSE;
            $season = FALSE;
            if( $debug ) echo "<br />CHECKDUPLYS NO CHAPTERS: " . $basetitle;
        }
        
        $basetitle = str_ireplace( ' ', '%', $basetitle );
        if( strlen( str_ireplace( '%', '' , $basetitle ) ) == 0 ){
            $basetitle = $title;
        }
        
        if( $season != FALSE ){
            $ext_p2p_file = '*' . $season . '{?,??,???}' . $chapter . '*';
        }else{
            $ext_p2p_file = '*';
        }
        $filep2p = PPATH_WEBSCRAP_DOWNLOAD . DS . '*' . str_ireplace( '%', '*' , $basetitle ) . $ext_p2p_file . '.*';
        if( $debug ) echo "<br />CHECKDUPLYS P2P FILE TO CHECK: " . basename( $filep2p );
        if( ( $filesp2p = glob( $filep2p ) ) !== FALSE 
        && is_array( $filesp2p )
        && count( $filesp2p ) > 0
        ){
            if( $debug ) echo "<br />CHECKDUPLYS IS DUPLY P2P: " . basename( $filesp2p[ 0 ] );
        }elseif( ( $idexist = sqlite_media_check_exist_search( $basetitle ) ) != FALSE 
        && ( $idmediad = sqlite_media_getdata( $idexist ) ) != FALSE
        && array_key_exists( 0, $idmediad )
        && is_array( $idmediad[ 0 ] )
        && array_key_exists( 'file', $idmediad[ 0 ] )
        && (
            ( $chapter == FALSE 
            //remake to multiple coincidences
            && array_key_exists( 'idmediainfo', $idmediad[ 0 ] )
            && $idmediad[ 0 ][ 'idmediainfo' ] > 0
            && ( $midata = sqlite_mediainfo_getdata( $idmediad[ 0 ][ 'idmediainfo' ] ) ) != FALSE
            && is_array( $midata )
            && array_key_exists( 0, $midata )
            && is_array( $midata[ 0 ] )
            && array_key_exists( 'season', $midata[ 0 ] )
            && (int)$midata[ 0 ][ 'season' ] <= 0
            )
            ||
            (  
                array_key_exists( 'idmediainfo', $idmediad[ 0 ] )
                && $idmediad[ 0 ][ 'idmediainfo' ] > 0
                && ( $midata = sqlite_mediainfo_getdata( $idmediad[ 0 ][ 'idmediainfo' ] ) ) != FALSE
                && is_array( $midata )
                && array_key_exists( 0, $midata )
                && is_array( $midata[ 0 ] )
                && array_key_exists( 'title', $midata[ 0 ] )
                && ( $findedmi = sqlite_mediainfo_check_exist( $midata[ 0 ][ 'title' ], $season, $chapter ) ) != FALSE
                && ( $midata = sqlite_mediainfo_getdata( $findedmi ) ) != FALSE
                && is_array( $midata )
                && array_key_exists( 0, $midata )
                && is_array( $midata[ 0 ] )
                && array_key_exists( 'title', $midata[ 0 ] )
                && ( $newfile = sqlite_media_getdata_mediainfo( $findedmi, 1 ) ) != FALSE
                && is_array( $newfile )
                && array_key_exists( 0, $newfile )
                && array_key_exists( 'file', $newfile[ 0 ] )
            )
        )
        ){
            if( $debug ) echo "<br />CHECKDUPLYS IS DUPLY: " . $basetitle;
            if( isset( $midata ) 
            && is_array( $midata )
            && array_key_exists( 0, $midata )
            && is_array( $midata[ 0 ] )
            && array_key_exists( 'season', $midata[ 0 ] )
            && $midata[ 0 ][ 'season' ] > 0
            ){
                $ext_season = ' ' . $midata[ 0 ][ 'season' ] . 'x' . $midata[ 0 ][ 'episode' ] . ' ';
            }else{
                $ext_season = '';
            }
            if( isset( $newfile ) 
            && is_array( $newfile )
            && array_key_exists( 0, $newfile )
            && array_key_exists( 'file', $newfile[ 0 ] )
            ){
                $result .= ' (EXIST:' . $idexist . '-' . basename( $newfile[ 0 ][ 'file' ] ) . $ext_season . ')';
            }else{
                $result .= ' (EXIST:' . $idexist . '-' . basename( $idmediad[ 0 ][ 'file' ] ) . $ext_season . ')';
            }
        }else{
            if( $debug ) echo "<br />CHECKDUPLYS NOT DUPLY: " . $basetitle;
        }
        
        return $result;
    }
    
	//EXTRACT LINKS FROM STRING
	
	function extract_links_all( $data ){
        $result = array();
        
        if( ( $l = extract_links( $data ) ) != FALSE ){
            $result = array_merge( $result, $l );
        }
        if( ( $l = extract_magnets( $data ) ) != FALSE ){
            $result = array_merge( $result, $l );
        }
        if( ( $l = extract_elinks( $data ) ) != FALSE ){
            $result = array_merge( $result, $l );
        }
        
        return $result;
    }
    
	function extract_links( $data ){
        $result = array();
        
        preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $data, $match);

        return $match[0];
    }
    
	function extract_magnets( $data ){
        $result = array();
        
        //$pattern = '/^.*\bmagnet\b.*$/m';
        //$pattern = '/magnet:\?xt=urn:tree:tiger:(?P<tth>\w+)/';
        $pattern = '/(magnet:[^"\'\s]*)/';
        preg_match_all( $pattern, $data, $match);
        
        return $match[0];
    }
    
	function extract_elinks( $data ){
        $result = array();
        
        //$pattern = '/^.*\bed2k\b.*$/m';
        $pattern = '/ed2k\:\/\/\|file\|.{1,250}\|[0-9]{8,12}\|[0-9A-F]{32}\|\//m';
        preg_match_all( $pattern, $data, $match);
        
        return $match[0];
    }
    
	function extract_magnets_title( $magnet ){
        $result = '';
        $magnet = urldecode($magnet); 
        preg_match('/(?<=&dn=)\S+/', $magnet, $magnet_link);
        if( array_key_exists( 0, $magnet_link ) ){
            $result = $magnet_link[ 0 ];
        }
        
        return $result;
    }
    
	function extract_elinks_title( $magnet ){
        $result = '';
        $magnet = urldecode($magnet); 
        preg_match('/(?<=file\|)\S*?\|/', $magnet, $magnet_link);
        if( array_key_exists( 0, $magnet_link ) ){
            $result = $magnet_link[ 0 ];
        }
        
        return $result;
    }
    
    function mime_check_torrent( $file ){
        $result = FALSE;
        $mimes = array(
            'application/x-bittorrent', 
            'application/force-download', 
            'application/torrent', 
            'torrent'
        );
        
        if( ( $mime = getFileMimeType( $file ) ) != FALSE 
        && in_array( $mime, $mimes )
        ){
            $result = TRUE;
        }
        
        return $result;
    }
    
    //DOWNLOAD HELPERS
    
	//HELPERS DOWNLOAD
	
	//download from youtube with external program
	function youtube_download( $url, $debug = PPATH_WEBSCRAP_DEBUG ){
        $result = TRUE;
        
        $cmd = PPATH_WEBSCRAP_YOUTUBE_CMD . ' ' . $url;
        if( $debug ) echo "<br />Download Function: " . $cmd;
        $result = run_in_background( $cmd );
        $result = TRUE;
        
        return $result;
	}
	
	//youtube search with ddkgo simple search (TODO SPAM searchs problems)
	function youtube_search( $webscrapper, $search, $debug ){
        $result = FALSE;
        
        $search .= '!v ' . $search;
        $filterurl = 'watch?v';
        $site = '';
        $timed = FALSE;
        if( ( $r = scrap_duckduckgo( $search, $filterurl, $site, $timed ) ) != FALSE ){
            $result = $r;
        }
	
        return $result;
	}
	
	//PLOWSHARE
	
	/*
	hotlink_cc
    uplea
    4share_vn
    faststore
    hdstream_to
    euroshare_eu
    chomikuj
    openload
    zippyshare
    filecloud
    sockshare
    1fichier
    catshare
    hipfile
    bitshare
    filefactory
    rapidgator
    freakshare
    dataport_cz
    fileshark
    netload_in
    lunaticfiles
    uploadrocket
    fileover
    multiupload
    thefilebay
    filedais
    gamefront
    rapidu
    zalaa
    filepost
    espafiles
    uploadboy
    nowdownload_co
    tempshare
    upload_cd
    promptfile
    180upload
    upstore
    keep2share
    myvdrive
    divshare
    uptobox
    uploaded_net
    filepup_net
    letitbit
    solidfiles
    nakido
    turbobit
    megashares
    tezfiles
    fboom_me
    firedrive
    salefiles
    gfile_ru
    sendspace
    ge_tt
    yourvideohost
    bayfiles
    data_hu
    fshare_vn
    sharebeast
    shareonline_biz
    filecore
    filejoker
    uploading
    mediafire
    netkups
    rghost
    ziddu
    flashx
    115
    dl_free_fr
    filebin_ca
    tempsend
    oboom
    datafile
    hexupload
    prefiles
    billionuploads
    ryushare
    4shared
    depositfiles
    filer_net
    anonfiles
    sharehost
    bigfile
    2shared
    bayimg
    nitroflare
    uloz_to
    ultramegabit
    crocko
	*/
	
	function plowshare_downloader( $url, $debug = PPATH_WEBSCRAP_DEBUG ){
        $result = TRUE;
        
        $cmd = PPATH_WEBSCRAP_PLOWSHARE_CMD . " '" . $url . "'";
        if( $debug ) echo "<br />Download Function PLOWSHARE: " . $cmd;
        $result = run_in_background( $cmd );
        $result = TRUE;
        
        return $result;
	}
	
	//WGET DOWNLOADER (for webs with redirect, etc)
	
	function wget_downloader( $url, $debug ){
        $result = FALSE;
        
        $filename = basename( $url );
        $file = PPATH_WEBSCRAP_DOWNLOAD . DS . $filename;
        //Get Data
        $cmd = O_WGET . ' -O "' . $file . '" "' . $url . '"';
        runExtCommand( $cmd );
        $result = TRUE;
		
		return $result;
	}
	
	//JDOWNLOADER
	
	function jdownloader_downloader( $url, $debug = PPATH_WEBSCRAP_DEBUG ){
        $result = TRUE;
        
        //INTERNAL crawljob
        $file = PPATH_WEBSCRAP_JDOWNLOADER_FOLDER . DS . getRandomString() . '.crawljob';
        if( $debug ) echo "<br />Download Function JDOWNLOADER: file_put_contents->" . $file . ' - ' . $url;
        file_put_contents( $file, $url );
        
        $result = TRUE;
        
        return $result;
	}
	
	function externallinks_downloader( $href, $debug = PPATH_WEBSCRAP_DEBUG ){
        $result = FALSE;
        global $G_WEBSCRAPPER;
        
        $wsTitle = get_msg( 'WEBSCRAP_ADDKO', FALSE );
        $wsResult = get_msg( 'WEBSCRAP_NOTHING', FALSE );
        $filetitle = '';
        if( startsWith( $href, 'ed2k:' ) ){
            amuleAdd( $href );
            $wsResult = get_msg( 'WEBSCRAP_ADDOK', FALSE ) . $href;
            $wsTitle = extract_elinks_title( $href );
        }elseif( startsWith( $href, 'magnet:' ) ){
            magnetAdd( $href );
            $wsResult = get_msg( 'WEBSCRAP_ADDOK', FALSE ) . $href;
            $wsTitle = extract_magnets_title( $href );
        }else{
        
            foreach( $G_WEBSCRAPPER AS $wsident => $wsdata ){
                if( array_key_exists( 'searchdata', $wsdata ) 
                && is_array( $wsdata[ 'searchdata' ] )
                && array_key_exists( 'passdata', $wsdata ) 
                && is_array( $wsdata[ 'passdata' ] )
                &&
                (
                    (
                    array_key_exists( 'linksappend', $wsdata[ 'searchdata' ] )
                    && strlen( $wsdata[ 'searchdata' ][ 'linksappend' ] ) > 0
                    && startsWith( $href, $wsdata[ 'searchdata' ][ 'linksappend' ] )
                    ) || (
                    array_key_exists( 'urlbase', $wsdata[ 'searchdata' ] )
                    && strlen( $wsdata[ 'searchdata' ][ 'urlbase' ] ) > 0
                    && startsWith( $href, $wsdata[ 'searchdata' ][ 'urlbase' ] )
                    ) || (
                    array_key_exists( 0, $wsdata[ 'passdata' ] )
                    && is_array( $wsdata[ 'passdata' ][ 0 ] )
                    && array_key_exists( 'linksappend', $wsdata[ 'passdata' ][ 0 ] )
                    && strlen( $wsdata[ 'passdata' ][ 0 ][ 'linksappend' ] ) > 0
                    && startsWith( $href, $wsdata[ 'passdata' ][ 0 ][ 'linksappend' ] )
                    ) || (
                    array_key_exists( 0, $wsdata[ 'passdata' ] )
                    && is_array( $wsdata[ 'passdata' ][ 0 ] )
                    && array_key_exists( 'urlbase', $wsdata[ 'passdata' ][ 0 ] )
                    && strlen( $wsdata[ 'passdata' ][ 0 ][ 'urlbase' ] ) > 0
                    && startsWith( $href, $wsdata[ 'passdata' ][ 0 ][ 'urlbase' ] )
                    )
                )
                ){
                    $wsTitle = $wsdata[ 'title' ];
                    $filetitle = $wsident . '_' . date( 'YmdHis' ) . '_' . getRandomString( 6 );
                    if( webscrapp_pass( $wsident, 0, $href, $filetitle ) ){
                        $wsResult = get_msg( 'WEBSCRAP_ADDOK', FALSE ) . $href;
                        $result = TRUE;
                    }else{
                        $wsResult = get_msg( 'WEBSCRAP_ADDKO', FALSE ) . $href;
                    }
                    break;
                }
            }
        }
        if( $debug ) echo "<br />externallinks_downloader: " . $wsResult;
        
        return $result;
	}
	
?>
