<?php

function usage( $err=null ) {
  echo 'Usage: '.$_SERVER['argv'][0]." <file to hide> <host file JPEG|GIF|PNG>\n";
  if( $err ) {
    echo 'Error: '.$err."\n";
  }
  exit();
}

if( $_SERVER['argc'] != 3 ) {
  usage();
}

$src = $_SERVER['argv'][1];
if( !is_file($src) ) {
  usage( 'cannot find source file !' );
}

$dst = $_SERVER['argv'][2];
if( !is_file($dst) ) {
  usage( 'cannot find image destination file !' );
}


// init
$meta = basename( $src );
$meta = base64_encode($meta) . '*';
//var_dump( $meta );

$msg = file_get_contents( $src );
$msg = base64_encode($msg) . '*';
//var_dump( $msg );

$t_info = getimagesize( $dst );
$img_w = $t_info[0];
$img_h = $t_info[1];
$img_t = $t_info['mime'];
$line_length = ($img_w*3) / 8;
$max_length = $line_length * ($img_h-1);

if( $max_length < strlen($msg) || $line_length < strlen($meta) ) {
  usage( 'message is too long or image is too small' );
}

switch( $img_t )
{
  case 'image/jpeg':
    $img = imagecreatefromjpeg( $dst );
    break;
  case 'imge/gif':
    $img = imagecreatefromgif( $dst );
    break;
  case 'image/png':
    $img = imagecreatefrompng( $dst );
    break;
  default:
    usage();
}


// run
inject( $img, $meta, 0 );
inject( $img, $msg, 1 );
// png output because of the quality of the renderer image
imagepng( $img, 'out.png' );


function inject( $img, $data, $start_line )
{
  global $img_w;

  $str = '';
  $l = strlen( $data );
  for( $i=0 ; $i<$l ; $i++) { // convert message to binary
    $str .= sprintf( "%08b", ord($data[$i]) );
  }
  //var_dump( $str );

  $x = 0;
  $y = $start_line;
  $l = strlen( $str );

  for( $i=0 ; $i<$l ; )
  {
    $rgb = _imagecolorat( $img, $x, $y );
    //var_dump( $rgb );

    if( $i < $l ) {
      $red = decbin( $rgb['r'] );
      $red[strlen($red)-1] = $str[$i++];
      $rgb['r'] = bindec( $red );
      //var_dump( $red );
    }

    if( $i < $l ) {
      $green = decbin( $rgb['g'] );
      $green[strlen($green)-1] = $str[$i++];
      $rgb['g'] = bindec( $green );
      //var_dump( $green );
    }

    if( $i < $l ) {
      $blue = decbin( $rgb['b'] );
      $blue[strlen($blue)-1] = $str[$i++];
      $rgb['b'] = bindec( $blue );
      //var_dump( $blue );
    }

    //var_dump( $rgb );
    _imagesetpixel( $img, $x, $y, $rgb );

    $x++;
    if( $x == $img_w ) {
      $x = 0;
      $y++;
    }
  }
}


function _imagesetpixel( $img, $x, $y, $rgb ) {
  $color = imagecolorallocate( $img, $rgb['r'], $rgb['g'], $rgb['b'] );
  imagesetpixel( $img, $x, $y, $color );
  imagecolordeallocate( $img, $color );
}


function _imagecolorat( $img, $x, $y ) {
  $rgb = imagecolorat( $img, $x, $y );
  return array( 'r'=>($rgb>>16)&0xFF, 'g'=>($rgb>>8)&0xFF, 'b' => $rgb&0xFF );
}


exit();

?>
