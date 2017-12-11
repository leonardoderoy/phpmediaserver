<?php

	defined( 'ACCESS' ) or die( 'HTTP/1.0 401 Unauthorized.<br />' );
	//admin
	check_mod_admin();
	
	if( array_key_exists( 'idmedia', $G_DATA ) 
	&& is_numeric( $G_DATA[ 'idmedia' ] )
	){
        $IDMEDIA = $G_DATA[ 'idmedia' ];
	}else{
        echo "Invalid ID: idmedia";
        die();
	}
	
	$SEASON = FALSE;
    $EPISODE = FALSE;
	
	if( is_numeric( $IDMEDIA )
	&& ( $media = sqlite_media_getdata( $IDMEDIA ) ) != FALSE 
	&& count( $media ) > 0
	){
        $TITLE = clean_filename( $media[ 0 ][ 'file' ] );
        $FILENAME = basename( $media[ 0 ][ 'file' ] );
        $FILE = $media[ 0 ][ 'file' ];
        //CLEAN INEXISTENT
        if( !file_exists( $FILE ) ){
            echo get_msg( 'DEF_FILENOTEXIST' );
            if( sqlite_media_delete( $IDMEDIA ) ){
                echo get_msg( 'DEF_DELETED' );
            }else{
                echo get_msg( 'DEF_DELETED_ERROR' );
            }
            //echo reloadJS();
            //header("Refresh: 2");
        }else{
            if( ( $d = get_media_chapter( $TITLE ) ) != FALSE
            ){
                $SEASON = (int)$d[ 0 ];
                $EPISODE = (int)$d[ 1 ];
            }
?>
<script type="text/javascript">
$(function () {
    
});
function ident_search_title(){
    $( '#fElementIdentE #action' ).val( 'identifys' );
    var url = '<?php getURL(); ?>?' + $( '#fElementIdentE' ).serialize();
    $( '#dResultidentS' ).html( '' );
    $( '#dResultidentE' ).html( '' );
    $( '#fElementIdentE #imdb' ).val( '' );
    loading_show();
    $.get( url )
    .done( function( data ){
        scrolltop();
        $( '#dResultidentS' ).html( data );
        loading_hide();
    });
    
    return false;
}
function ident_get_set_title( title, imdb ){
    $( '#fElementIdentE #title' ).val( title );
    $( '#fElementIdentE #imdb' ).val( imdb );
    ident_set_title();
    
    return false;
}
function ident_set_title(){
    $( '#dResultidentE' ).html( '' );
    $( '#fElementIdentE #action' ).val( 'identifya' );
    var url = '<?php getURL(); ?>?' + $( '#fElementIdentE' ).serialize();
    loading_show();
    $.get( url )
    .done( function( data ){
        scrolltop();
        $( '#dResultidentA' ).html( data );
        loading_hide();
    });
    
    return false;
}
function ident_unset_idmedia(){
    $( '#fElementIdentE #action' ).val( 'mediaclean' );
    var url = '<?php getURL(); ?>?' + $( '#fElementIdentE' ).serialize();
    loading_show();
    $.get( url )
    .done( function( data ){
        scrolltop();
        $( '#dResultidentS' ).html( data );
        loading_hide();
    });
    
    return false;
}
function ident_force_title(){
    $( '#fElementIdentE #action' ).val( 'identifyforce' );
    var url = '<?php getURL(); ?>?' + $( '#fElementIdentE' ).serialize();
    loading_show();
    $.get( url )
    .done( function( data ){
        scrolltop();
        $( '#dResultidentS' ).html( data );
        loading_hide();
    });
    
    return false;
}
function ident_get_list_episodes(){
    $( '#fElementIdentE #action' ).val( 'identifyliste' );
    var url = '<?php getURL(); ?>?' + $( '#fElementIdentE' ).serialize();
    loading_show();
    $.get( url )
    .done( function( data ){
        scrolltop();
        $( '#dResultidentS' ).html( data );
        loading_hide();
    });
    
    return false;
}
function ident_list_set_title( newtitle ){
    $( '#fElementIdentE #title' ).val( newtitle );
    
    return false;
}
</script>

<br />

<form id='fElementIdentE'>
    
    <input type='hidden' id='r' name='r' value='r' />
    <input type='hidden' id='action' name='action' value='identifys' />
    <input type='hidden' id='idmedia' name='idmedia' value='<?php echo $IDMEDIA; ?>' />
    <table class='tList' style='width:80%;margin: auto;'>
        
        <tr>
            <td colspan='100'><div id='dResultidentA'></div></td>
        </tr>
        
        <tr>
            <td colspan='100'><div id='dResultidentS'></div></td>
        </tr>
        <tr>
            <th><?php echo get_msg( 'MENU_ELEMENT', FALSE ); ?></th>
            <th><?php echo get_msg( 'MENU_TITLE', FALSE ); ?></th>
            <th><?php echo get_msg( 'MENU_IMDB', FALSE ); ?></th>
            <th><?php echo get_msg( 'MENU_SCRAPPER', FALSE ); ?></th>
            <th><?php echo get_msg( 'MENU_TYPE', FALSE ); ?></th>
            <th><?php echo get_msg( 'MENU_SEASON', FALSE ); ?></th>
            <th><?php echo get_msg( 'MENU_EPISODE', FALSE ); ?></th>
            <th><?php echo get_msg( 'MENU_ACTION', FALSE ); ?></th>
        </tr>
        <tr>
            <td>
                <label for='title'><?php echo get_msg( 'MENU_SEARCH', FALSE ); ?>: <?php echo $FILENAME; ?></label>
            </td>
            <td style='width:20%;'>
                <input type='text' id='title' name='title' value='<?php echo $TITLE; ?>' style='width:80%;height:60%;' onkeypress="return event.keyCode != 13;" />
            </td>
            <td style='width:20%;'>
                <input type='text' id='imdb' name='imdb' value='' style='width:80%;height:60%;' onkeypress="return event.keyCode != 13;" />
            </td>
            <td>
                <select id='scrapper' name='scrapper'>
                    <?php
                        foreach( $G_SCRAPPERS AS $t => $v ){
                    ?>
                    <option value='<?php echo $t; ?>'><?php echo $t; ?></option>
                    <?php
                        }
                    ?>
                    
                </select>
            </td>
            <td>
                <select id='stype' name='stype'>
                    <?php
                        foreach( $G_STYPE AS $v ){
                            if( $v == 'series' 
                            && $SEASON != FALSE
                            ){
                                $selected = 'selected';
                            }else{
                                $selected = '';
                            }
                    ?>
                    <option <?php echo $selected; ?> value='<?php echo $v; ?>'><?php echo $v; ?></option>
                    <?php
                        }
                    ?>
                </select>
            </td>
            <td>
                <select id='season' name='season'>
                    <option value=''><?php echo get_msg( 'MENU_SEASON' ); ?></option>
                    <?php
                        for( $v = 1; $v < 30; $v++ ){
                            if( $SEASON == $v
                            ){
                                $selected = 'selected';
                            }else{
                                $selected = '';
                            }
                    ?>
                    <option <?php echo $selected; ?> value='<?php echo $v; ?>'><?php echo $v; ?></option>
                    <?php
                        }
                    ?>
                </select>
            <td>
                <select id='episode' name='episode'>
                    <option value=''><?php echo get_msg( 'MENU_EPISODE' ); ?></option>
                    <?php
                        for( $v = 1; $v < 100; $v++ ){
                            if( $EPISODE == $v
                            ){
                                $selected = 'selected';
                            }else{
                                $selected = '';
                            }
                    ?>
                    <option <?php echo $selected; ?> value='<?php echo $v; ?>'><?php echo $v; ?></option>
                    <?php
                        }
                    ?>
                </select>
            </td>
            </td>
            <td>
                <input onclick='ident_search_title();' type='button' id='bSearch' name='bSearch' value='<?php echo get_msg( 'MENU_SEARCH', FALSE ); ?>' />
                <input onclick='ident_get_list_episodes();' type='button' id='bListEpisodes' name='bListEpisodes' value='<?php echo get_msg( 'MENU_GETEPISODES', FALSE ); ?>' />
                <input onclick='ident_set_title();' type='button' id='bSetTitle' name='bSetTitle' value='<?php echo get_msg( 'MENU_SETTITLE', FALSE ); ?>' />
                <input onclick='ident_force_title();' type='button' id='bForceTitle' name='bForceTitle' value='<?php echo get_msg( 'MENU_SETTITLE_FORCE', FALSE ); ?>' />
                <input onclick='ident_unset_idmedia();' type='button' id='bUnsetIdMedia' name='bUnsetIdMedia' value='<?php echo get_msg( 'MENU_MEDIA_DELETE_ASSING', FALSE ); ?>' />
            </td>
        </tr>
    </table>
	
</form>
<?php
        }
    }else{
        echo get_msg( 'DEF_NOTEXIST' );
    }
?>