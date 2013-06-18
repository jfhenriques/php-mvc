<?php

/**
 *  Slitely modified from
 *	https://code.google.com/p/yii/issues/detail?id=2848
 */

function parse_raw_http_request()
{
    if( !isset( $_SERVER['CONTENT_TYPE'] ) )
      return;

    $input = file_get_contents('php://input');
    preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);

  // Adicion as variáveis necessárias ao $_REQUEST
    if( count($matches) == 0 )
    {
      $arrOut = array();
      @parse_str($input, $arrOut);

      if( count( $arrOut ) > 0 )
      {
        foreach($arrOut as $k => $val )
          $_REQUEST[$k] = $val;
      }

      return;
    }

    $boundary = $matches[1];

    $a_blocks = preg_split("/-+$boundary/", $input);
    array_pop($a_blocks);

    foreach ($a_blocks as $id => $block)
    {
      if (empty($block))
        continue;

        preg_match('/name=\"([^\"]*)\".*filename=\"([^\"]+)\"[\n\r]+Content-Type:\s+([^\s]*?)[\n\r]+?([^\n\r].*?)\r$/Us', $block, $matches);

        if ( count($matches) == 0 )
        {
          preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
          $_REQUEST[$matches[1]] = isset( $matches[2] ) ? $matches[2] : "" ;
        }
        else
        {
          $tmp_name = tempnam(sys_get_temp_dir(),'');
          file_put_contents($tmp_name, $matches[4]);
          $size = filesize($tmp_name);

          preg_match('/([a-zA-Z_0-9]+)/s', $matches[1], $name);
          preg_match_all('/\[([a-zA-Z_0-9]*)\]/s', $matches[1], $arr);
          $file = array(
            'name'=>null,
            'type'=>null,
            'tmp_name'=>null,
            'error'=>null,
            'size'=>null,
          );
          $arr = $arr[1];
          $name = $name[1];
          $args = array();
          foreach ($file as $key => &$value)
          {
            $args[]=&$value;
          }
            for ($i = 0; $i < count($arr); $i++)
            {
              for ($k = 0; $k < count($args); $k++)
              {
                $args[$k] = array();
                if ($arr[$i]==''){
                  $args[$k][] = null;
                  $x= count($args[$k])-1;
                  $args[$k] = &$args[$k][$x];
                } else {
                  $args[$k][$arr[$i]] = null;
                  $args[$k] = &$args[$k][$arr[$i]];
                }
              }
            }

          $args[0] = $matches[2]; //filename
          $args[1] = $matches[3]; //type
          $args[2] = $tmp_name; //tmp_name
          $args[3] = 0; //error
          $args[4] = $size; //size
          $_FILES[$name] = $file;
        }
    }
  }
