<?php

	defined( 'ACCESS' ) or die( 'HTTP/1.0 401 Unauthorized.<br />' );
	set_time_limit(0);
	
	//action
	//idmedia= file avi
	//idmediainfo
	//timeplayed = seconds from start
	//timetotal 
	//quality = quality sd|hd
	//audiotrack = audio track list to ffmpeg
	//subtrack = sub track number
	//bitrate = bitrate 1500
	//acodec = audiocodec
	
	$HTMLRESULT = '';
	if( array_key_exists( 'idmedia', $G_DATA ) ){
        $IDMEDIA = $G_DATA[ 'idmedia' ];
	}else{
        $IDMEDIA = '';
	}
	
	if( array_key_exists( 'idmediainfo', $G_DATA ) ){
        $IDMEDIAINFO = $G_DATA[ 'idmediainfo' ];
	}else{
        $IDMEDIAINFO = '';
	}
	
	if( $IDMEDIA > 0
	&& ( $mi = sqlite_media_getdata( $IDMEDIA ) ) != FALSE 
	&& is_array( $mi )
	&& count( $mi ) > 0
	&& file_exists( $mi[ 0 ][ 'file' ] )
	&& getFileMimeTypeVideo( $mi[ 0 ][ 'file' ] )
	){
        $FMEDIA = $mi[ 0 ][ 'file' ];
        $IDMEDIAINFO = $mi[ 0 ][ 'idmediainfo' ];
	}elseif( $IDMEDIAINFO > 0
	&& ( $mi = sqlite_media_getdata_mediainfo( $IDMEDIAINFO ) ) != FALSE 
	&& is_array( $mi )
	&& count( $mi ) > 0
	&& file_exists( $mi[ 0 ][ 'file' ] )
	&& getFileMimeTypeVideo( $mi[ 0 ][ 'file' ] )
	){
        $FMEDIA = $mi[ 0 ][ 'file' ];
        $IDMEDIA = $mi[ 0 ][ 'idmedia' ];
	}else{
        $FMEDIA = FALSE;
	}
	
	if( $FMEDIA == FALSE ){
        echo get_msg( 'DEF_NOTEXIST' );
	}elseif( !file_exists( $FMEDIA ) ){
        echo get_msg( 'DEF_FILENOTEXIST' );
	}else{
        //EXTRA VARS
        $title = '';
        $ACTIONINFO = '';
        $filename = $FMEDIA;
        $TIMEBLOCK = O_VIDEO_TIMEBLOCK;
        $G_MODE = 'webm';
        if( array_key_exists( 'mode', $G_DATA ) 
        && strlen( $G_DATA[ 'mode' ] ) > 0
        ){
            $G_MODE = $G_DATA[ 'mode' ];
        }
        $G_TIME = 0;
        if( array_key_exists( 'timeplayed', $G_DATA ) 
        && is_numeric( $G_DATA[ 'timeplayed' ] )
        && (int)$G_DATA[ 'timeplayed' ] > -1
        ){
            $G_TIME = (int)$G_DATA[ 'timeplayed' ];
        }elseif( array_key_exists( 'timeplayed', $G_DATA ) 
        && is_numeric( $G_DATA[ 'timeplayed' ] )
        && (int)$G_DATA[ 'timeplayed' ] == -1
        ){
            $G_TIME = sqlite_played_status( $IDMEDIA );
        }
        $G_QUALITY = 'sd';
        if( array_key_exists( 'quality', $G_DATA ) 
        && $G_DATA[ 'quality' ] == 'hd'
        ){
            $G_QUALITY = 'hd';
        }
        $bitrate = 1500;
        if( array_key_exists( 'bitrate', $G_DATA ) 
        && is_numeric( $G_DATA[ 'bitrate' ] )
        ){
            $bitrate = (int)$G_DATA[ 'bitrate' ];
        }
        if( array_key_exists( 'audiotrack', $G_DATA ) 
        && is_numeric( $G_DATA[ 'audiotrack' ] )
        ){
            $audiotrack = (int)$G_DATA[ 'audiotrack' ];
        }else{
            $audiotrack = 1;
        }
        
        if( array_key_exists( 'subtrack', $G_DATA ) 
        && is_numeric( $G_DATA[ 'subtrack' ] )
        ){
            $subtrack = (int)$G_DATA[ 'subtrack' ];
        }else{
            $subtrack = -1;
        }
        
        if( !file_exists( $filename )
        ){
            $ACTIONINFO = get_msg( 'DEF_FILENOTEXIST' );
        }
        
        if( $ACTIONINFO != '' ){
            echo $ACTIONINFO;
        }else{
            
            $dir = $filename;
            
            //pregenerate $segment
            if( $G_TIME > 0 ){
                $extra_params = "-ss " . $G_TIME;
            }else{
                $extra_params = "";
                $G_TIME = 0;
            }
            
            //SET PLAYTIME
            $time = ffmpeg_file_info_lenght_seconds( $dir );
            sqlite_played_replace( $IDMEDIA, $G_TIME, $time );
            
            //variable bitrate to max especified
            if( $G_QUALITY != 'hd' ){
                $G_FFMPEGLVL = '3.0';
                $minbitrate = O_VIDEO_SD_MINBRATE;
                $maxbitrate = O_VIDEO_SD_MAXBRATE;
                $QUALITY = '-vf scale=-1:' . O_VIDEO_SD_HEIGHT;
                //$encoder = 'libvpx';
            }else{
                $G_FFMPEGLVL = '3.0';
                $minbitrate = O_VIDEO_HD_MINBRATE;
                $maxbitrate = O_VIDEO_HD_MAXBRATE;
                $QUALITY = '-vf scale=-1:' . O_VIDEO_HD_HEIGHT;
                //$encoder = 'libvpx-vp9';
            }
            //$QUALITY = '';
            
            //audio +more vol
            $audiovol = O_VIDEO_EXTRA_VOLUME;
            
            //audio track (change 1 to num video tracks)
            $audiotrack = ' -map 0:0 -map 0:' . ( $audiotrack ) . ' ';
            
            //subs track (testing)
            if( $subtrack > -1 
            && is_numeric( $subtrack )
            ){
                //TESTING
                //$subtrack = ' -filter_complex "[0:v][0:s:' . $subtrack . ']overlay" ';
                //$subtrack = ' -vf subtitles="' . escapeshellarg( $dir ) . '":si=' . $subtrack . ' ';
                //$subtrack = ' -filter_complex "[0:v][0:s:0]overlay[v]" -map [v] ';
                //$subtrack = ' -vf subtitles=' . escapeshellarg( $dir ) . ' ';
                //$subtrack = ' -vf "[0:0][0:' . $subtrack . ']overlay[0]" -map [0] ';
                //$subtrack = ' -copyts -vf "subtitles=' . escapeshellarg( $dir ) . ',setpts=PTS-STARTPTS" -sn ';
            }else{
                $subtrack = '';
            }
            
            switch( $G_MODE ){
                //TEST KODI
                case 'direct':
                    //slow
                    $cmd = "cat " . escapeshellarg( $dir ) . "";
                    
                    header( 'Content-type: ' . getFileMimeType( $dir ) );
                break;
                //TEST KODI
                case 'fast':
                    //fast way to kodi
                    $encoder_outformat = 'matroska';
                    $encoder = 'libx264'; //webm 9
                    //" . $subtrack . " " . $audiotrack . "
                    $cmd = O_FFMPEG . " -nostdin " . $extra_params . " -i " . escapeshellarg( $dir ) . " -vcodec " . $encoder . " -crf 23 -preset ultrafast -c:a copy -f " . $encoder_outformat . " - ";
                    
                    header('Content-type: video/matroska');
                break;
                case 'mp4':
                    //testing
                    //$encoder_outformat = 'mpegts';
                    $encoder_outformat = 'mp4';
                    //$encoder = 'h264';
                    $encoder = 'libx264';
                    $AUDIOCODEC = 'aac';
                    //$AUDIOCODEC = 'mp3';
                    //$AUDIOCODEC = 'opus';
                    $cmd = O_FFMPEG . " -nostdin -re " . $extra_params . " -i " . escapeshellarg( $dir ) . " " . $subtrack . " " . $audiotrack . " -c:v " . $encoder . " -quality realtime -b:v " . $minbitrate . " -maxrate " . $minbitrate . " -movflags +faststart -bufsize 1000k -g 74 -strict experimental -pix_fmt yuv420p -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' -level " . $G_FFMPEGLVL . " -profile:v baseline -level 3.0 -preset ultrafast -tune zerolatency -af 'volume=" . $audiovol . "' -c:a " . $AUDIOCODEC . " -ab 64k -f " . $encoder_outformat . " -movflags frag_keyframe+empty_moov - ";
                    
                    header('Content-type: video/mp4');
                break;
                case 'webm2':
                    //slow
                    $AUDIOCODEC = 'libvorbis';
                    $encoder_outformat = 'webm';
                    $encoder = 'libvpx-vp9'; //webm 9
                    $cmd = O_FFMPEG . " -nostdin " . $extra_params . " -i " . escapeshellarg( $dir ) . " " . $subtrack . " " . $audiotrack . " -c:v " . $encoder . " -threads 4 -speed 8 -quality realtime -b:v " . $minbitrate . " -maxrate " . $minbitrate . " -bufsize 1000k -pix_fmt yuv420p -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' -preset baseline " . $QUALITY . " -level " . $G_FFMPEGLVL . " -af 'volume=" . $audiovol . "' -c:a " . $AUDIOCODEC . " -f " . $encoder_outformat . " - ";
                    
                    header('Content-type: video/webm');
                break;
                case 'webm':
                default:
                    //better option
                    $AUDIOCODEC = 'libvorbis';
                    $encoder_outformat = 'webm';
                    $encoder = 'libvpx';
                    $cmd = O_FFMPEG . " -nostdin " . $extra_params . " -i " . escapeshellarg( $dir ) . " " . $subtrack . " " . $audiotrack . " -c:v " . $encoder . " -quality realtime -b:v " . $minbitrate . " -maxrate " . $minbitrate . " -bufsize 1000k -pix_fmt yuv420p -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2' -preset baseline " . $QUALITY . " -level " . $G_FFMPEGLVL . " -af 'volume=" . $audiovol . "' -c:a " . $AUDIOCODEC . " -f " . $encoder_outformat . " - ";
                    
                    header('Content-type: video/webm');
            }
            
            //headers
            @apache_setenv('no-gzip', 1);
            @ini_set('zlib.output_compression', 'Off');
            //header('Content-disposition: inline');
            header('Content-disposition: attachment');
            header("Content-Transfer-Encoding: ­binary");
            
            //force close db
            sqlite_db_close();
            
            //passthru
            if( $_SERVER['REQUEST_METHOD'] != 'HEAD' ){
                passthru( $cmd, $cmdok );
            }
            
        }
    }
    exit();
?>

