<?php

	defined( 'ACCESS' ) or die( 'HTTP/1.0 401 Unauthorized.<br />' );
	//admin
	check_mod_admin();
	
	//search
	//pass
	//webscrapper
	
	if( array_key_exists( 'pass', $G_DATA ) ){
        $PASS = $G_DATA[ 'pass' ];
	}else{
        $PASS = 0;
	}
	
	if( array_key_exists( 'search', $G_DATA ) 
	&& strlen( $G_DATA[ 'search' ] ) > 3
	){
        $SEARCH = $G_DATA[ 'search' ];
	}else{
        $SEARCH = '';
	}
	
	if( array_key_exists( 'webscrapper', $G_DATA ) 
	&& array_key_exists( $G_DATA[ 'webscrapper' ], $G_WEBSCRAPPER )
	){
        $SCRAPPER = $G_DATA[ 'webscrapper' ];
	}else{
        echo "Invalid scrapper: ";
        die();
	}
	
	echo "<br />";
	echo get_msg( 'MENU_SEARCH', FALSE ) . ': ' . $SCRAPPER . ' => ' . $SEARCH;
	
	if( ( $links = webscrapp_search( $SCRAPPER, $SEARCH ) ) != FALSE 
	&& count( $links ) > 0
	){
	
	//CLEAN VALUES
	$SCRAPPER = str_replace( "\t", '', $SCRAPPER );
    $SCRAPPER = str_replace( "\n", '', $SCRAPPER );
    $SCRAPPER = str_replace( "\r", '', $SCRAPPER );
        
?>
    <table class='tList'>
        <tr>
            <th colspan='100' class='tCenter'><?php echo get_msg( 'IDENT_DETECTED' ); ?></th>
        </tr>
        <?php  
            foreach( $links AS $t => $href ){
                $t = str_replace( "\t", '', $t );
                $t = str_replace( "\n", '', $t );
                $t = str_replace( "\r", '', $t );
        ?>
        <tr>
            <td class='tCenter'>
                <a class='aIdentSearchResult' href='#' onclick='webscrap_add( "<?php echo addslashes( $SCRAPPER ); ?>", "<?php echo addslashes( $href ); ?>", "<?php echo addslashes( $t ); ?>" )'><?php echo $t; ?>
                </a>
            </td>
        </tr>
        <?php  
            }
        ?>
    </table>
<?php
        
	}elseif( is_array( $links ) 
	&& count( $links ) == 0
	){
        echo "<br />";
        echo get_msg( 'DEF_EMPTYLIST', FALSE );
	}else{
        echo "<br />";
        echo get_msg( 'WEBSCRAP_SEARCH_ERROR', FALSE ) . '' . $SCRAPPER;
	}
?>
